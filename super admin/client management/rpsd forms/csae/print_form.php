<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../auth/auth.php';
require_once __DIR__ . '/../../../../auth/db.php';

require_login(['super admin', 'admin']);

function escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function format_date_value(?string $value): string
{
    $trimmed = trim((string) $value);
    if ($trimmed === '') {
        return '';
    }

    $timestamp = strtotime($trimmed);
    return $timestamp === false ? $trimmed : date('m/d/Y', $timestamp);
}

function build_full_name(array $record): string
{
    return trim(implode(' ', array_filter([
        trim((string) ($record['first_name'] ?? '')),
        trim((string) ($record['middle_name'] ?? '')),
        trim((string) ($record['last_name'] ?? '')),
        trim((string) ($record['suffix'] ?? '')),
    ], static fn(string $value): bool => $value !== '')));
}

function normalize_yes_no(?string $value): string
{
    $normalized = strtolower(trim((string) $value));

    if (in_array($normalized, ['yes', 'y', '1', 'true'], true)) {
        return 'Yes';
    }

    if (in_array($normalized, ['no', 'n', '0', 'false'], true)) {
        return 'No';
    }

    return trim((string) $value);
}

function resolve_foreign_key_column(mysqli $connection, string $tableName, array $candidates): string
{
    $escapedTableName = $connection->real_escape_string($tableName);
    $columnsResult = $connection->query("SHOW COLUMNS FROM `{$escapedTableName}`");
    if (!$columnsResult) {
        throw new RuntimeException('Unable to inspect related table columns.');
    }

    $columns = [];
    while ($column = $columnsResult->fetch_assoc()) {
        $field = trim((string) ($column['Field'] ?? ''));
        if ($field !== '') {
            $columns[$field] = true;
        }
    }
    $columnsResult->free();

    foreach ($candidates as $candidate) {
        if (isset($columns[$candidate])) {
            return $candidate;
        }
    }

    throw new RuntimeException('Unable to resolve related table foreign key column.');
}

function render_table_rows(array $rows, array $columns, int $minimumRows): string
{
    $html = '';
    $rowCount = max($minimumRows, count($rows));

    for ($index = 0; $index < $rowCount; $index++) {
        $row = $rows[$index] ?? [];
        $html .= '<tr>';

        foreach ($columns as $column) {
            $html .= '<td>' . escape_html((string) ($row[$column] ?? '')) . '</td>';
        }

        $html .= '</tr>';
    }

    return $html;
}

function render_lined_block(?string $value, int $minimumRows = 5, int $wrapLength = 110): string
{
    $text = trim((string) $value);
    $wrapped = $text === '' ? [] : preg_split('/\r\n|\r|\n/', wordwrap($text, $wrapLength, "\n", true));
    $lines = is_array($wrapped) ? array_values(array_filter(array_map('strval', $wrapped), static fn(string $line): bool => $line !== '')) : [];
    $rowCount = max($minimumRows, count($lines));

    $html = '';
    for ($index = 0; $index < $rowCount; $index++) {
        $html .= '<div class="lined-block__row">' . escape_html($lines[$index] ?? '') . '</div>';
    }

    return $html;
}

$clientId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($clientId === false || $clientId === null) {
    http_response_code(400);
    echo 'Invalid or missing client id.';
    exit;
}

$client = null;
$familyMembers = [];
$treatmentPlans = [];

try {
    $connection = get_database_connection();

    $clientQuery = $connection->prepare('SELECT * FROM csae_table WHERE key_id = ? LIMIT 1');
    if (!$clientQuery) {
        throw new RuntimeException('Failed to prepare CSAE query.');
    }

    $clientQuery->bind_param('i', $clientId);
    $clientQuery->execute();
    $clientResult = $clientQuery->get_result();
    $client = $clientResult ? $clientResult->fetch_assoc() : null;
    $clientQuery->close();

    if (!$client) {
        http_response_code(404);
        echo 'CSAE record not found.';
        exit;
    }

    $familyForeignKey = resolve_foreign_key_column($connection, 'csae_family_information_table', ['case_id', 'csae_id']);
    $familyQuery = $connection->prepare(
        sprintf(
            'SELECT member_name, member_age, relationship, education_attainment, occupation, monthly_income FROM csae_family_information_table WHERE %s = ? ORDER BY id ASC',
            $familyForeignKey
        )
    );
    if (!$familyQuery) {
        throw new RuntimeException('Failed to prepare family query.');
    }

    $familyQuery->bind_param('i', $clientId);
    $familyQuery->execute();
    $familyResult = $familyQuery->get_result();
    if ($familyResult) {
        while ($row = $familyResult->fetch_assoc()) {
            $familyMembers[] = $row;
        }
    }
    $familyQuery->close();

    $treatmentForeignKey = resolve_foreign_key_column($connection, 'csae_treatment_plan_table', ['case_id', 'csae_id']);
    $treatmentQuery = $connection->prepare(
        sprintf(
            'SELECT objectives, activities, timeframe, responsible_person, remarks FROM csae_treatment_plan_table WHERE %s = ? ORDER BY id ASC',
            $treatmentForeignKey
        )
    );
    if (!$treatmentQuery) {
        throw new RuntimeException('Failed to prepare treatment plan query.');
    }

    $treatmentQuery->bind_param('i', $clientId);
    $treatmentQuery->execute();
    $treatmentResult = $treatmentQuery->get_result();
    if ($treatmentResult) {
        while ($row = $treatmentResult->fetch_assoc()) {
            $treatmentPlans[] = $row;
        }
    }
    $treatmentQuery->close();

    $connection->close();
} catch (Throwable $exception) {
    http_response_code(500);
    echo 'Unable to load print form data.';
    exit;
}

$fullName = build_full_name($client);
$createdDate = format_date_value((string) ($client['created_at'] ?? ''));
$incidentDate = format_date_value((string) ($client['incident_date'] ?? ''));
$referralDate = format_date_value((string) ($client['referral_date'] ?? ''));
$birthDate = format_date_value((string) ($client['birth_date'] ?? ''));
$preparedBy = trim((string) ($client['served_by'] ?? ''));
$fourPs = normalize_yes_no((string) ($client['is_4ps'] ?? ''));
$philhealth = normalize_yes_no((string) ($client['philhealth'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CSAE Print Form</title>
  <link rel="stylesheet" href="print-form.css" />
</head>
<body>
  <div class="print-toolbar" aria-label="Print controls">
    <div class="toolbar-actions">
      <button type="button" id="printFormButton" class="print-btn">Print Form</button>
      <button type="button" id="editSignatoriesButton" class="print-btn print-btn--secondary">Edit Signatories</button>
    </div>
    <p class="print-hint">Shortcut: <strong>Ctrl + P</strong> / <strong>⌘ + P</strong></p>
  </div>

  <main class="print-document" id="printPage">
    <section class="sheet-page">
      <div class="sheet-page-content">
        <header class="solo-print-header" aria-label="City government header">
          <img src="../../../images-logo/cswd-logo.png" alt="City of Butuan Official Seal" class="city-seal-logo" />
          <div class="government-text-block">
            <div class="gov-line gov-line-1">Republic of the Philippines</div>
            <div class="gov-line gov-line-2">CITY GOVERNMENT OF <span class="city-name-highlight">BUTUAN</span></div>
            <div class="gov-line gov-line-3">City Social Welfare and Development Department</div>
            <div class="gov-line gov-line-4">Butuan City</div>
          </div>
        </header>
        <div class="header-rule-wrap" aria-hidden="true"></div>

        <div class="title-area">
          <div class="title-area__date">Date: <span><?= escape_html($createdDate) ?></span></div>
          <h1>GENERAL INTAKE SHEET (CHILD ABUSE)</h1>
        </div>

        <section class="section-block">
          <h2>I. IDENTIFYING INFORMATION</h2>
          <div class="field-grid field-grid--two">
            <div class="field-line field-line--wide"><span class="field-label">Name of the Child:</span><span class="field-value"><?= escape_html($fullName) ?></span></div>
            <div class="field-line"><span class="field-label">Nickname:</span><span class="field-value"><?= escape_html((string) ($client['nickname'] ?? '')) ?></span></div>
            <div class="field-line"><span class="field-label">Age:</span><span class="field-value"><?= escape_html((string) ($client['age'] ?? '')) ?></span></div>
            <div class="field-line"><span class="field-label">Sex:</span><span class="field-value"><?= escape_html((string) ($client['sex'] ?? '')) ?></span></div>
            <div class="field-line"><span class="field-label">Birthdate:</span><span class="field-value"><?= escape_html($birthDate) ?></span></div>
            <div class="field-line"><span class="field-label">Birthplace:</span><span class="field-value"><?= escape_html((string) ($client['birth_place'] ?? '')) ?></span></div>
            <div class="field-line"><span class="field-label">Educational Attainment:</span><span class="field-value"><?= escape_html((string) ($client['education'] ?? '')) ?></span></div>
            <div class="field-line"><span class="field-label">Health Condition:</span><span class="field-value"><?= escape_html((string) ($client['health_condition'] ?? '')) ?></span></div>
            <div class="field-line field-line--wide"><span class="field-label">School last Attended:</span><span class="field-value"><?= escape_html((string) ($client['school_last_attended'] ?? '')) ?></span></div>
            <div class="field-line field-line--wide"><span class="field-label">Address of School:</span><span class="field-value"><?= escape_html((string) ($client['school_address'] ?? '')) ?></span></div>
            <div class="field-line field-line--wide"><span class="field-label">Present Address:</span><span class="field-value"><?= escape_html((string) ($client['present_address'] ?? '')) ?></span></div>
            <div class="field-line field-line--wide"><span class="field-label">Permanent Address:</span><span class="field-value"><?= escape_html((string) ($client['permanent_address'] ?? '')) ?></span></div>
            <div class="field-line"><span class="field-label">Contact no.:</span><span class="field-value"><?= escape_html((string) ($client['contact_number'] ?? '')) ?></span></div>
            <div class="field-line"><span class="field-label">4 P's:</span><span class="field-value"><?= escape_html($fourPs) ?></span></div>
            <div class="field-line"><span class="field-label">PhilHealth:</span><span class="field-value"><?= escape_html($philhealth) ?></span></div>
          </div>
        </section>

        <section class="section-block">
          <h2>II. OFFENSE COMMITTED TO THE CHILD</h2>
          <div class="field-grid field-grid--two">
            <div class="field-line field-line--wide"><span class="field-label">Type of Offense:</span><span class="field-value"><?= escape_html((string) ($client['offense_type'] ?? '')) ?></span></div>
            <div class="field-line field-line--wide"><span class="field-label">Date &amp; Place of Incident:</span><span class="field-value"><?= escape_html($incidentDate) ?></span></div>
            <div class="field-line field-line--wide"><span class="field-label">Name of Perpetrator/Accused:</span><span class="field-value"><?= escape_html((string) ($client['perpetrator_name'] ?? '')) ?></span></div>
            <div class="field-line field-line--wide"><span class="field-label">Date of Referral to Social Worker:</span><span class="field-value"><?= escape_html($referralDate) ?></span></div>
            <div class="field-line field-line--wide"><span class="field-label">Name and Address of Referring Party:</span><span class="field-value"><?= escape_html((string) ($client['referring_party'] ?? '')) ?></span></div>
            <div class="field-line"><span class="field-label">Status of the Case:</span><span class="field-value"><?= escape_html((string) ($client['case_status'] ?? '')) ?></span></div>
            <div class="field-line"><span class="field-label">Criminal Case No.:</span><span class="field-value"><?= escape_html((string) ($client['criminal_case_no'] ?? '')) ?></span></div>
            <div class="field-line"><span class="field-label">Source of Information:</span><span class="field-value"><?= escape_html((string) ($client['info_source'] ?? '')) ?></span></div>
            <div class="field-line"><span class="field-label">Complainant:</span><span class="field-value"><?= escape_html((string) ($client['complainant'] ?? '')) ?></span></div>
          </div>
        </section>

        <section class="section-block section-block--fill">
          <h2>III. FAMILY COMPOSITION</h2>
          <table class="detail-table detail-table--family">
            <thead>
              <tr>
                <th>Name</th>
                <th>Age</th>
                <th>Relationship</th>
                <th>Educ. Attt.</th>
                <th>Occupation</th>
                <th>Income</th>
              </tr>
            </thead>
            <tbody>
              <?= render_table_rows($familyMembers, ['member_name', 'member_age', 'relationship', 'education_attainment', 'occupation', 'monthly_income'], 8) ?>
            </tbody>
          </table>
        </section>
      </div>

      <footer class="sheet-footer" aria-label="Contact footer">
        <div class="footer-content">
          <div class="footer-left">
            <img src="../../../images-logo/4ea40ea1-15b1-44ac-b015-6fab929d199e.jpg" alt="CSWDO QR Code" class="footer-qr" />
            <div class="footer-contact-text">
              <p>Jose Rosales Ave. Brgy. Tandang Sora, Butuan City</p>
              <p>Email: cswdd@butuan.gov.ph</p>
              <p>Phone: 0956-224-1679</p>
              <p>www.butuan.gov.ph</p>
            </div>
          </div>

          <div class="footer-center">
            <p class="tuv-title">TUVNORD</p>
            <p class="tuv-subtitle">Certificate Registration No. PHP</p>
            <p class="tuv-subtitle">QMS 23 95 0116</p>
          </div>

          <div class="footer-right">
            <img src="../../../images-logo/footer.png" alt="ButuanOn Logo" class="footer-logo" />
            <p class="footer-code">CSWDD.RSPD.R.017.REV02</p>
          </div>
        </div>
        <div class="footer-bar" aria-hidden="true"></div>
      </footer>
    </section>

    <section class="sheet-page">
      <div class="sheet-page-content">
        <header class="solo-print-header" aria-label="City government header">
          <img src="../../../images-logo/cswd-logo.png" alt="City of Butuan Official Seal" class="city-seal-logo" />
          <div class="government-text-block">
            <div class="gov-line gov-line-1">Republic of the Philippines</div>
            <div class="gov-line gov-line-2">CITY GOVERNMENT OF <span class="city-name-highlight">BUTUAN</span></div>
            <div class="gov-line gov-line-3">City Social Welfare and Development Department</div>
            <div class="gov-line gov-line-4">Butuan City</div>
          </div>
        </header>
        <div class="header-rule-wrap" aria-hidden="true"></div>

        <section class="section-block">
          <h2>I. PRESENTING PROBLEM</h2>
          <div class="lined-block lined-block--medium"><?= render_lined_block((string) ($client['presenting_problem'] ?? ''), 7, 112) ?></div>
        </section>

        <section class="section-block">
          <h2>II. BACKGROUND INFORMATION</h2>
          <div class="subsection-block">
            <h3>A. Child</h3>
            <div class="lined-block"><?= render_lined_block((string) ($client['background_child'] ?? ''), 5, 112) ?></div>
          </div>
          <div class="subsection-block">
            <h3>B. Child's Family</h3>
            <div class="lined-block"><?= render_lined_block((string) ($client['background_family'] ?? ''), 5, 112) ?></div>
          </div>
          <div class="subsection-block">
            <h3>C. Child's Community</h3>
            <div class="lined-block"><?= render_lined_block((string) ($client['background_community'] ?? ''), 5, 112) ?></div>
          </div>
        </section>

        <section class="section-block section-block--fill">
          <h2>III. ASSESSMENT</h2>
          <div class="lined-block lined-block--tall"><?= render_lined_block((string) ($client['assessment'] ?? ''), 10, 112) ?></div>
        </section>
      </div>

      <footer class="sheet-footer" aria-label="Contact footer">
        <div class="footer-content">
          <div class="footer-left">
            <img src="../../../images-logo/4ea40ea1-15b1-44ac-b015-6fab929d199e.jpg" alt="CSWDO QR Code" class="footer-qr" />
            <div class="footer-contact-text">
              <p>Jose Rosales Ave. Brgy. Tandang Sora, Butuan City</p>
              <p>Email: cswdd@butuan.gov.ph</p>
              <p>Phone: 0956-224-1679</p>
              <p>www.butuan.gov.ph</p>
            </div>
          </div>

          <div class="footer-center">
            <p class="tuv-title">TUVNORD</p>
            <p class="tuv-subtitle">Certificate Registration No. PHP</p>
            <p class="tuv-subtitle">QMS 23 95 0116</p>
          </div>

          <div class="footer-right">
            <img src="../../../images-logo/footer.png" alt="ButuanOn Logo" class="footer-logo" />
            <p class="footer-code">CSWDD.RSPD.R.017.REV02</p>
          </div>
        </div>
        <div class="footer-bar" aria-hidden="true"></div>
      </footer>
    </section>

    <section class="sheet-page">
      <div class="sheet-page-content">
        <header class="solo-print-header" aria-label="City government header">
          <img src="../../../images-logo/cswd-logo.png" alt="City of Butuan Official Seal" class="city-seal-logo" />
          <div class="government-text-block">
            <div class="gov-line gov-line-1">Republic of the Philippines</div>
            <div class="gov-line gov-line-2">CITY GOVERNMENT OF <span class="city-name-highlight">BUTUAN</span></div>
            <div class="gov-line gov-line-3">City Social Welfare and Development Department</div>
            <div class="gov-line gov-line-4">Butuan City</div>
          </div>
        </header>
        <div class="header-rule-wrap" aria-hidden="true"></div>

        <section class="section-block">
          <h2>VII. TREATMENT PLAN</h2>
          <table class="detail-table detail-table--treatment">
            <thead>
              <tr>
                <th>Objective</th>
                <th>Activities</th>
                <th>Time frame</th>
                <th>Person/s Responsible</th>
                <th>Remarks</th>
              </tr>
            </thead>
            <tbody>
              <?= render_table_rows($treatmentPlans, ['objectives', 'activities', 'timeframe', 'responsible_person', 'remarks'], 6) ?>
            </tbody>
          </table>
        </section>

        <section class="section-block">
          <h2>IV. RECOMMENDATION</h2>
          <div class="lined-block lined-block--medium"><?= render_lined_block((string) ($client['recommendation'] ?? ''), 6, 112) ?></div>
        </section>

        <section class="signature-grid">
          <div class="signature-card">
            <div class="signature-card__label">Prepared by:</div>
            <div class="signature-card__line" id="preparedByName"><?= escape_html($preparedBy) ?></div>
            <div class="signature-card__role">Social Welfare Officer / Staff</div>
          </div>
          <div class="signature-card">
            <div class="signature-card__label">Conformed by:</div>
            <div class="signature-card__line"></div>
            <div class="signature-card__role">Client / Guardian</div>
          </div>
          <div class="signature-card signature-card--centered">
            <div class="signature-card__label">Noted by:</div>
            <div class="signature-card__line" id="notedByName"></div>
            <div class="signature-card__role">Supervising Social Worker</div>
          </div>
        </section>
        <div class="modal-overlay" id="signatoriesModal" aria-hidden="true">
          <div class="modal" role="dialog" aria-modal="true" aria-labelledby="signatoriesModalTitle">
            <h3 id="signatoriesModalTitle">Edit CSAE Signatories</h3>
            <form id="signatoriesForm">
              <label class="modal-field">
                Prepared By
                <input type="text" id="preparedByNameInput" />
              </label>
              <label class="modal-field">
                Noted By
                <input type="text" id="notedByNameInput" />
              </label>
              <div class="modal-actions">
                <button type="button" id="cancelSignatoriesButton" class="print-btn print-btn--secondary">Cancel</button>
                <button type="submit" class="print-btn">Save</button>
              </div>
            </form>
          </div>
        </div>

        <section class="privacy-consent">
          <div class="privacy-consent__title">DATA PRIVACY CONSENT</div>
          <p>
            I hereby certify that the information provided in this General Intake Sheet is true and correct to the best of my knowledge.
            I voluntarily allow the City Social Welfare and Development Department to collect, record, organize, store, update and use
            the personal information contained in this document for case management, assessment, referral, intervention planning, and
            other lawful social welfare purposes consistent with Republic Act No. 10173 or the Data Privacy Act of 2012.
          </p>
          <p>
            I understand that this information shall be handled with confidentiality and disclosed only to authorized personnel,
            partner agencies, or institutions whenever necessary for the delivery of appropriate services and in accordance with
            applicable laws, policies and data sharing protocols.
          </p>
          <div class="privacy-consent__signature-line"></div>
          <div class="privacy-consent__signature-caption">Name &amp; Signature of Client</div>
        </section>
      </div>

      <footer class="sheet-footer" aria-label="Contact footer">
        <div class="footer-content">
          <div class="footer-left">
            <img src="../../../images-logo/4ea40ea1-15b1-44ac-b015-6fab929d199e.jpg" alt="CSWDO QR Code" class="footer-qr" />
            <div class="footer-contact-text">
              <p>Jose Rosales Ave. Brgy. Tandang Sora, Butuan City</p>
              <p>Email: cswdd@butuan.gov.ph</p>
              <p>Phone: 0956-224-1679</p>
              <p>www.butuan.gov.ph</p>
            </div>
          </div>

          <div class="footer-center">
            <p class="tuv-title">TUVNORD</p>
            <p class="tuv-subtitle">Certificate Registration No. PHP</p>
            <p class="tuv-subtitle">QMS 23 95 0116</p>
          </div>

          <div class="footer-right">
            <img src="../../../images-logo/footer.png" alt="ButuanOn Logo" class="footer-logo" />
            <p class="footer-code">CSWDD.RSPD.R.017.REV02</p>
          </div>
        </div>
        <div class="footer-bar" aria-hidden="true"></div>
      </footer>
    </section>
  </main>

  <script src="print-form.js"></script>
</body>
</html>
