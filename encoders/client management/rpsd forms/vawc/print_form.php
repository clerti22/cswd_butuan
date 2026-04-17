<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../auth/auth.php';
require_once __DIR__ . '/../../../../auth/db.php';

require_once __DIR__ . '/../../shared/form_access.php';
require_encoder_program_access('VAWC');

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

function render_yes_no_choice(string $value, string $expected): string
{
    $marker = strcasecmp(trim($value), trim($expected)) === 0 ? '☑' : '☐';
    return '<span class="choice-marker">' . escape_html($marker) . '</span>';
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

function render_lined_block(?string $value, int $minimumRows = 5, int $wrapLength = 108): string
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

    $clientQuery = $connection->prepare('SELECT * FROM vawc_table WHERE key_id = ? LIMIT 1');
    if (!$clientQuery) {
        throw new RuntimeException('Failed to prepare VAWC query.');
    }

    $clientQuery->bind_param('i', $clientId);
    $clientQuery->execute();
    $clientResult = $clientQuery->get_result();
    $client = $clientResult ? $clientResult->fetch_assoc() : null;
    $clientQuery->close();

    if (!$client) {
        http_response_code(404);
        echo 'VAWC record not found.';
        exit;
    }

    $familyQuery = $connection->prepare(
        'SELECT member_name, member_age, relationship, education_attainment, occupation, monthly_income
         FROM vawc_family_information_table
         WHERE vawc_id = ?
         ORDER BY id ASC'
    );
    if (!$familyQuery) {
        throw new RuntimeException('Failed to prepare family composition query.');
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

    $treatmentQuery = $connection->prepare(
        'SELECT objectives, activities, responsible_person, resources_needed, expected_output
         FROM vawc_initial_treatment_plan_table
         WHERE vawc_id = ?
         ORDER BY id ASC'
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
$birthDate = format_date_value((string) ($client['birth_date'] ?? ''));
$preparedBy = trim((string) ($client['served_by'] ?? ''));
$notedBy = 'Social Worker';
$fourPs = normalize_yes_no((string) ($client['is_4ps'] ?? ''));
$uct = normalize_yes_no((string) ($client['is_uct'] ?? ''));
$nhts = normalize_yes_no((string) ($client['is_nhts'] ?? ''));
$address = trim(implode(', ', array_filter([
    trim((string) ($client['address'] ?? '')),
    trim((string) ($client['barangay'] ?? '')),
], static fn(string $value): bool => $value !== '')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>VAWC Print Form</title>
  <link rel="stylesheet" href="print-form.css" />
</head>
<body>
  <div class="print-toolbar" aria-label="Print controls">
    <button type="button" id="printFormButton" class="print-btn">Print Form</button>
    <p class="print-hint">Shortcut: <strong>Ctrl + P</strong> / <strong>⌘ + P</strong></p>
  </div>

  <main class="print-document" id="printPage">
    <section class="sheet-page">
      <div class="sheet-page__body">
        <img src="../../../images-logo/header.png" alt="CSWD header" class="sheet-header-image" />

        <div class="title-area">
          <h1>VAWC Intake Sheet</h1>
          <div class="title-area__date">Date: <span class="line-fill line-fill--date"><?= escape_html($createdDate) ?></span></div>
        </div>

        <section class="section-block">
          <h2>I. Identifying Information:</h2>
          <div class="info-grid">
            <div class="field-row field-row--wide"><span class="field-label">Name</span><span class="field-colon">:</span><span class="line-fill"><?= escape_html($fullName) ?></span></div>
            <div class="field-row"><span class="field-label">Contact No.</span><span class="field-colon">:</span><span class="line-fill"><?= escape_html((string) ($client['contact_number'] ?? '')) ?></span></div>
            <div class="field-row field-row--small"><span class="field-label">Age</span><span class="field-colon">:</span><span class="line-fill"><?= escape_html((string) ($client['age'] ?? '')) ?></span></div>
            <div class="field-row field-row--wide"><span class="field-label">Address</span><span class="field-colon">:</span><span class="line-fill"><?= escape_html($address) ?></span></div>
            <div class="field-row"><span class="field-label">B-Date</span><span class="field-colon">:</span><span class="line-fill"><?= escape_html($birthDate) ?></span></div>
            <div class="field-row"><span class="field-label">B-Place</span><span class="field-colon">:</span><span class="line-fill"><?= escape_html((string) ($client['birth_place'] ?? '')) ?></span></div>
            <div class="field-row"><span class="field-label">Educ. Attain.</span><span class="field-colon">:</span><span class="line-fill"><?= escape_html((string) ($client['education'] ?? '')) ?></span></div>
            <div class="field-row"><span class="field-label">Religion</span><span class="field-colon">:</span><span class="line-fill"><?= escape_html((string) ($client['religion'] ?? '')) ?></span></div>
            <div class="field-row"><span class="field-label">Status</span><span class="field-colon">:</span><span class="line-fill"><?= escape_html((string) ($client['civil_status'] ?? '')) ?></span></div>
            <div class="field-row"><span class="field-label">Occupation</span><span class="field-colon">:</span><span class="line-fill"><?= escape_html((string) ($client['occupation'] ?? '')) ?></span></div>
            <div class="field-row"><span class="field-label">Monthly Income</span><span class="field-colon">:</span><span class="line-fill"><?= escape_html((string) ($client['monthly_income'] ?? '')) ?></span></div>
          </div>

          <div class="category-block">
            <div class="category-title">Clientele Category:</div>
            <div class="category-row"><span class="category-label">4P's</span><span class="field-colon">:</span><span>Yes <?= render_yes_no_choice($fourPs, 'Yes') ?></span><span class="category-gap"></span><span>No <?= render_yes_no_choice($fourPs, 'No') ?></span></div>
            <div class="category-row"><span class="category-label">UCT</span><span class="field-colon">:</span><span>Yes <?= render_yes_no_choice($uct, 'Yes') ?></span><span class="category-gap"></span><span>No <?= render_yes_no_choice($uct, 'No') ?></span></div>
            <div class="category-row"><span class="category-label">NHTS</span><span class="field-colon">:</span><span>Yes <?= render_yes_no_choice($nhts, 'Yes') ?></span><span class="category-gap"></span><span>No <?= render_yes_no_choice($nhts, 'No') ?></span></div>
          </div>
        </section>

        <section class="section-block section-block--family">
          <h2>II. Family Composition:</h2>
          <table class="detail-table detail-table--family">
            <thead>
              <tr>
                <th>Name</th>
                <th>Age</th>
                <th>Relationship<br>to client</th>
                <th>Educational<br>Attainment</th>
                <th>Occupation</th>
                <th>Income</th>
              </tr>
            </thead>
            <tbody>
              <?= render_table_rows($familyMembers, ['member_name', 'member_age', 'relationship', 'education_attainment', 'occupation', 'monthly_income'], 7) ?>
            </tbody>
          </table>
        </section>

        <div class="page-number">Page 1 of 3</div>
      </div>

      <footer class="sheet-footer">
        <div class="footer-left">
          <div>Phone: (085) 817-5598</div>
          <div>Email: cswdobutuan@gmail.com</div>
          <div>Website: http://www.butuan.gov.ph</div>
        </div>
        <div class="footer-right">
          <img src="../../../images-logo/footer.png" alt="ButuanOn logo" />
          <div>CSWDD.RPSD.F.024.REV01</div>
        </div>
      </footer>
      <div class="sheet-footer-band"></div>
    </section>

    <section class="sheet-page">
      <div class="sheet-page__body">
        <img src="../../../images-logo/header.png" alt="CSWD header" class="sheet-header-image" />

        <section class="section-block">
          <h2>III. Presenting Problem:</h2>
          <div class="lined-block lined-block--problem"><?= render_lined_block((string) ($client['presenting_problem'] ?? ''), 9) ?></div>
        </section>

        <section class="section-block">
          <h2>IV. Initial Description of the:</h2>
          <div class="description-group">
            <div class="subheading">A. Family Environment</div>
            <div class="lined-block"><?= render_lined_block((string) ($client['family_environment'] ?? ''), 7) ?></div>
          </div>
          <div class="description-group">
            <div class="subheading">B. Community</div>
            <div class="lined-block"><?= render_lined_block((string) ($client['community'] ?? ''), 8) ?></div>
          </div>
          <div class="description-group">
            <div class="subheading">C. Family and History Background:</div>
            <div class="lined-block"><?= render_lined_block((string) ($client['family_history'] ?? ''), 8) ?></div>
          </div>
        </section>

        <div class="page-number">Page 2 of 3</div>
      </div>

      <footer class="sheet-footer">
        <div class="footer-left">
          <div>Phone: (085) 817-5598</div>
          <div>Email: cswdobutuan@gmail.com</div>
          <div>Website: http://www.butuan.gov.ph</div>
        </div>
        <div class="footer-right">
          <img src="../../../images-logo/footer.png" alt="ButuanOn logo" />
          <div>CSWDD.RPSD.F.024.REV01</div>
        </div>
      </footer>
      <div class="sheet-footer-band"></div>
    </section>

    <section class="sheet-page">
      <div class="sheet-page__body">
        <img src="../../../images-logo/header.png" alt="CSWD header" class="sheet-header-image" />

        <section class="section-block">
          <h2>V. Initial Treatment</h2>
          <div class="general-goal-label">General Goal</div>
          <table class="detail-table detail-table--treatment">
            <thead>
              <tr>
                <th>Objectives</th>
                <th>Activities</th>
                <th>Responsible<br>Person</th>
                <th>Resources<br>Needed</th>
                <th>Expected Output</th>
              </tr>
            </thead>
            <tbody>
              <?= render_table_rows($treatmentPlans, ['objectives', 'activities', 'responsible_person', 'resources_needed', 'expected_output'], 4) ?>
            </tbody>
          </table>
        </section>

        <section class="section-block">
          <h2>VI. Recommendation:</h2>
          <div class="lined-block lined-block--recommendation"><?= render_lined_block((string) ($client['recommendation'] ?? ''), 4) ?></div>
        </section>

        <section class="signature-section">
          <div class="signature-block">
            <div class="signature-title">Prepared by:</div>
            <div class="signature-line"><?= escape_html($preparedBy) ?></div>
            <div class="signature-role">VAWC Focal/Worker</div>
          </div>
          <div class="signature-block">
            <div class="signature-title">Noted by:</div>
            <div class="signature-line"><?= escape_html($notedBy) ?></div>
            <div class="signature-role">Social Worker</div>
          </div>
        </section>

        <section class="consent-box">
          <div class="consent-title">Data Privacy Consent</div>
          <p>On behalf of all the members of my household, I confirm that the information I have provided in this form is true and represents accurate information of our household.</p>
          <p>I understand that the data collected from this report will be processed, managed and maintained in a secure database by the City Social Welfare and Development (CSWD). Such data will be used to determine poverty status, serve as basis for research, and in the development and implementation of Social Protection Programs and Services, to promote the interest of the poor.</p>
          <p>I authorized CSWD to manage the information, including personal data obtained from this assessment/case study and allow the processing and controlled disclosure or transfer of data to its development partners and other stakeholders in accordance with the CSWD policies and Data Sharing and the provisions of Republic Act No. 10173 or the Data Privacy Act (DPA) of 2012.</p>
          <div class="client-signature-name"><?= escape_html($fullName) ?></div>
          <div class="client-signature-caption">(NAME AND SIGNATURE OF CLIENT)</div>
        </section>

        <div class="page-number">Page 3 of 3</div>
      </div>

      <footer class="sheet-footer">
        <div class="footer-left">
          <div>Phone: (085) 817-5598</div>
          <div>Email: cswdobutuan@gmail.com</div>
          <div>Website: http://www.butuan.gov.ph</div>
        </div>
        <div class="footer-right">
          <img src="../../../images-logo/footer.png" alt="ButuanOn logo" />
          <div>CSWDD.RPSD.F.024.REV01</div>
        </div>
      </footer>
      <div class="sheet-footer-band"></div>
    </section>
  </main>

  <script src="../shared/print-form.js"></script>
</body>
</html>
