<?php

declare(strict_types=1);

if (!isset($printConfig) || !is_array($printConfig)) {
    throw new RuntimeException('Print configuration is required.');
}

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

function build_full_name(array $record, string $prefix = ''): string
{
    $firstName = (string) ($record[$prefix . 'first_name'] ?? '');
    $middleName = (string) ($record[$prefix . 'middle_name'] ?? '');
    $lastName = (string) ($record[$prefix . 'last_name'] ?? '');
    $suffix = (string) ($record[$prefix . 'suffix'] ?? '');

    return trim(implode(' ', array_filter([
        trim($firstName),
        trim($middleName),
        trim($lastName),
        trim($suffix),
    ], static fn(string $value): bool => $value !== '')));
}

function normalize_multiselect(?string $value): array
{
    $trimmed = trim((string) $value);
    if ($trimmed === '') {
        return [];
    }

    $decoded = json_decode($trimmed, true);
    if (is_array($decoded)) {
        return array_values(array_filter(array_map(
            static fn($item): string => trim((string) $item),
            $decoded
        ), static fn(string $item): bool => $item !== ''));
    }

    return array_values(array_filter(array_map(
        static fn(string $item): string => trim($item),
        preg_split('/\s*,\s*/', $trimmed) ?: []
    ), static fn(string $item): bool => $item !== ''));
}

function is_selected_option(string $label, array $selectedValues): bool
{
    $normalizedLabel = strtolower(trim($label));
    foreach ($selectedValues as $selectedValue) {
        if (strtolower(trim((string) $selectedValue)) === $normalizedLabel) {
            return true;
        }
    }

    return false;
}

function render_check_option(string $label, array $selectedValues): string
{
    $marker = is_selected_option($label, $selectedValues) ? '☑' : '☐';
    return '<span class="check-option-item"><span class="check-marker">' . escape_html($marker) . '</span><span>' . escape_html($label) . '</span></span>';
}

function render_text_block(?string $value, int $minimumRows = 4): string
{
    $text = trim((string) $value);
    $lines = $text === '' ? [] : preg_split('/\r\n|\r|\n/', wordwrap($text, 92, "\n", true));
    $lines = is_array($lines) ? array_values($lines) : [];
    $rowCount = max($minimumRows, count($lines));

    $html = '';
    for ($index = 0; $index < $rowCount; $index++) {
        $line = $lines[$index] ?? '';
        $html .= '<div class="lined-text-row">' . escape_html($line) . '</div>';
    }

    return $html;
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

$clientId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($clientId === false || $clientId === null) {
    http_response_code(400);
    echo 'Invalid or missing client id.';
    exit;
}

$classificationOptions = [
    'Children in Street Situations',
    'Trafficking in Person',
    'Child Laborer',
    'Abandoned/Neglected Children',
    'Children Sexually Abused and Exploited',
    'Children In Conflict with the Law (CICL)',
    'Children at Risk(CAR)',
    'Children Involved in Armed Conflict',
    'Children in Situation of Armed Conflict',
    'Mentally Challenged Individuals',
    'Stranded Individuals',
    'Community Work Service Clients',
    'Adoption',
    'Foster Care',
    'Online Sexual Abuse or Exploitation of Children (OSAEC)',
    'Children Sexual Abuse or Exploitation Materials (CSAEM)',
    'Foundling',
    'Person Deprived of Liberty (PDL)',
];

$abuseOptions = ['Pisikal', 'Emosyonal', 'Mental', 'Sexual', 'Pinansyal'];

$client = null;
$familyMembers = [];
$treatmentPlans = [];

try {
    $connection = get_database_connection();

    $clientQuery = $connection->prepare(
        sprintf(
            'SELECT * FROM %s WHERE key_id = ? LIMIT 1',
            $printConfig['mainTable']
        )
    );

    if (!$clientQuery) {
        throw new RuntimeException('Failed to prepare main form query.');
    }

    $clientQuery->bind_param('i', $clientId);
    $clientQuery->execute();
    $clientResult = $clientQuery->get_result();
    $client = $clientResult ? $clientResult->fetch_assoc() : null;
    $clientQuery->close();

    if (!$client) {
        http_response_code(404);
        echo escape_html((string) ($printConfig['notFoundMessage'] ?? 'Record not found.'));
        exit;
    }

    $familyQuery = $connection->prepare(
        sprintf(
            'SELECT member_name, member_age, member_gender, member_status, member_relation, member_work, member_income, member_remarks
             FROM %s
             WHERE %s = ?
             ORDER BY id ASC',
            $printConfig['familyTable'],
            $printConfig['familyForeignKey']
        )
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
        sprintf(
            'SELECT objectives, activities, responsible_person, resources_needed, expected_output
             FROM %s
             WHERE %s = ?
             ORDER BY id ASC',
            $printConfig['treatmentTable'],
            $printConfig['treatmentForeignKey']
        )
    );

    if (!$treatmentQuery) {
        throw new RuntimeException('Failed to prepare initial treatment query.');
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
$spouseName = build_full_name($client, 'spouse_');
$classificationSelections = normalize_multiselect((string) ($client['classification_tags'] ?? ''));
$abuseSelections = normalize_multiselect((string) ($client['abuse_tags'] ?? ''));
$presentAddress = trim((string) ($client['present_address'] ?? ''));
$permanentAddress = trim((string) ($client['permanent_address'] ?? ''));
$preparedBy = trim((string) ($printConfig['preparedBy'] ?? ''));
$notedBy = trim((string) ($client['served_by'] ?? ''));
$formLabel = (string) ($printConfig['formLabel'] ?? 'General Intake Sheet');
$formCode = (string) ($printConfig['formCode'] ?? 'CSWDD.F.053.REV01');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= escape_html((string) ($printConfig['pageTitle'] ?? $formLabel . ' Print Form')) ?></title>
  <link rel="stylesheet" href="../shared/print-form.css" />
</head>
<body>
  <div class="print-toolbar" aria-label="Print controls">
    <button type="button" id="printFormButton" class="print-btn">Print Form</button>
    <p class="print-hint">Shortcut: <strong>Ctrl + P</strong> / <strong>⌘ + P</strong></p>
  </div>

  <main class="print-document" id="printPage">
    <section class="sheet-page sheet-page--first">
      <img class="sheet-header-image" src="../../../images-logo/header.png" alt="CSWD header" />

      <div class="sheet-page-content">
      <section class="sheet-title-block">
        <div class="sheet-title-block__meta">Petsa Nga Gi Interbyu: <span class="line-value line-value--short"><?= escape_html(format_date_value((string) ($client['interview_date'] ?? ''))) ?></span></div>
        <h1><?= escape_html($formLabel) ?></h1>
      </section>

      <section class="sheet-section">
        <h2>I. IDENTIFYING INFORMATION</h2>
        <div class="two-column-grid identity-grid">
          <div class="field-line field-line--name"><span class="field-label">Ngalan:</span> <span class="line-value"><?= escape_html((string) ($client['last_name'] ?? '')) ?></span><span class="field-note">(Ulahing)</span></div>
          <div class="option-line option-line--grouped">
            <span>Edad: <span class="line-value line-value--mini"><?= escape_html((string) ($client['age'] ?? '')) ?></span></span>
            <span>Sex:</span>
            <span class="stacked-options"><?= render_check_option('Babaye', [((string) ($client['sex'] ?? '')) === 'Female' ? 'Babaye' : '']) ?><?= render_check_option('Lalaki', [((string) ($client['sex'] ?? '')) === 'Male' ? 'Lalaki' : '']) ?></span>
            <span>Civil Status:</span>
            <span class="stacked-options"><?= render_check_option('M', [((string) ($client['civil_status'] ?? '')) === 'Married' ? 'M' : '']) ?><?= render_check_option('S', [((string) ($client['civil_status'] ?? '')) === 'Single' ? 'S' : '']) ?><?= render_check_option('W', [((string) ($client['civil_status'] ?? '')) === 'Widowed' ? 'W' : '']) ?></span>
          </div>
          <div class="name-parts-row">
            <span class="line-value"><?= escape_html((string) ($client['first_name'] ?? '')) ?></span>
            <span class="line-value"><?= escape_html((string) ($client['middle_name'] ?? '')) ?></span>
            <span class="line-value"><?= escape_html(trim(((string) ($client['suffix'] ?? '')))) ?></span>
            <span class="name-parts-note">(Una)</span>
            <span class="name-parts-note">(Tunga)</span>
            <span class="name-parts-note">(Suffix)</span>
          </div>
          <div class="field-line"><span class="field-label">Nickname:</span> <span class="line-value"><?= escape_html((string) ($client['nickname'] ?? '')) ?></span></div>
          <div class="field-line"><span class="field-label">Edukasyon:</span> <span class="line-value"><?= escape_html((string) ($client['education'] ?? '')) ?></span></div>
          <div class="field-line"><span class="field-label">Birthday:</span> <span class="line-value"><?= escape_html(format_date_value((string) ($client['birth_date'] ?? ''))) ?></span></div>
          <div class="field-line"><span class="field-label">Kasamtangan nga Address:</span> <span class="line-value"><?= escape_html($presentAddress) ?></span></div>
          <div class="field-line"><span class="field-label">Lugar nga natawhan:</span> <span class="line-value"><?= escape_html((string) ($client['birth_place'] ?? '')) ?></span></div>
          <div class="field-line"><span class="field-label">Permanenteng Address:</span> <span class="line-value"><?= escape_html($permanentAddress) ?></span></div>
        </div>

        <div class="field-line"><span class="field-label">Kung minyo nga babae:</span> <span class="line-value"><?= escape_html($spouseName) ?></span></div>

        <div class="three-column-grid compact-grid top-gap-small">
          <div class="field-line"><span class="field-label">Relihiyon:</span> <span class="line-value"><?= escape_html((string) ($client['religion'] ?? '')) ?></span></div>
          <div class="field-line"><span class="field-label">Gibana-bana nga binulan nga kita:</span> <span class="line-value"><?= escape_html((string) ($client['monthly_income_estimate'] ?? '')) ?></span></div>
          <div class="field-line"><span class="field-label">Tinubdan sa Kita:</span> <span class="line-value"><?= escape_html((string) ($client['income_source'] ?? '')) ?></span></div>
        </div>
        <div class="field-line"><span class="field-label">4Ps/PWD/PhilHealth:</span> <span class="line-value line-value--short"><?= escape_html((string) ($client['assistance_status'] ?? '')) ?></span></div>
      </section>

      <section class="sheet-section two-column-grid page-one-columns">
        <div>
          <h2>BINULAN NGA GASTOS SA PAMILYA</h2>
          <div class="expenses-list">
            <div class="field-line"><span class="field-label">Pagkaon (P)</span><span class="line-value line-value--amount"><?= escape_html((string) ($client['food_expense'] ?? '')) ?></span></div>
            <div class="field-line"><span class="field-label">Balay ug Lote</span><span class="line-value line-value--amount"><?= escape_html((string) ($client['house_lot_expense'] ?? '')) ?></span></div>
            <div class="field-line"><span class="field-label">Edukasyon</span><span class="line-value line-value--amount"><?= escape_html((string) ($client['education_expense'] ?? '')) ?></span></div>
            <div class="field-line"><span class="field-label">Transportasyon</span><span class="line-value line-value--amount"><?= escape_html((string) ($client['transport_expense'] ?? '')) ?></span></div>
            <div class="field-line"><span class="field-label">Miscellaneous</span><span class="line-value line-value--amount"><?= escape_html((string) ($client['misc_expense'] ?? '')) ?></span></div>
            <div class="field-line"><span class="field-label">Total na binulan nga gastos</span><span class="line-value line-value--amount"><?= escape_html((string) ($client['total_expenses'] ?? '')) ?></span></div>
            <div class="field-line"><span class="field-label">Total na binulan nga kita</span><span class="line-value line-value--amount"><?= escape_html((string) ($client['total_income'] ?? '')) ?></span></div>
            <div class="field-line"><span class="field-label">Net Income (P)</span><span class="line-value line-value--amount"><?= escape_html((string) ($client['net_income'] ?? '')) ?></span></div>
          </div>

          <div class="subsection-title">CLASSIFICATION SA MINOR/KLIYENTE</div>
          <div class="checklist-grid">
            <?php foreach ($classificationOptions as $index => $option): ?>
              <div class="checklist-item">
                <span class="checklist-index"><?= escape_html((string) ($index + 1)) ?>.</span>
                <?= render_check_option($option, $classificationSelections) ?>
              </div>
            <?php endforeach; ?>
            <div class="checklist-item checklist-item--other">
              <span class="checklist-index">19.</span>
              <?= render_check_option('others', $classificationSelections) ?>
              <span class="inline-other"><?= escape_html((string) ($client['classification_other'] ?? '')) ?></span>
            </div>
          </div>
        </div>

        <div>
          <div class="subsection-title">CLASSIFICATION SA PANGABUSO</div>
          <div class="stacked-checklist">
            <?php foreach ($abuseOptions as $option): ?>
              <div><?= render_check_option($option, $abuseSelections) ?></div>
            <?php endforeach; ?>
            <div><?= render_check_option('Others', $abuseSelections) ?> <span class="inline-other"><?= escape_html((string) ($client['abuse_other'] ?? '')) ?></span></div>
          </div>

          <div class="subsection-title top-gap">OFFENSE COMMITTED BY THE CHILD</div>
          <div class="field-line"><span class="field-label">Type of Offense:</span> <span class="line-value"><?= escape_html((string) ($client['type_of_offense'] ?? '')) ?></span></div>
          <div class="field-line"><span class="field-label">Date of Apprehension:</span> <span class="line-value"><?= escape_html(format_date_value((string) ($client['date_of_apprehension'] ?? ''))) ?></span></div>
          <div class="field-line"><span class="field-label">By whom:</span> <span class="line-value"><?= escape_html((string) ($client['by_whom'] ?? '')) ?></span></div>
          <div class="field-line"><span class="field-label">Date of Offense Committed:</span> <span class="line-value"><?= escape_html(format_date_value((string) ($client['date_of_offense'] ?? ''))) ?></span></div>
          <div class="field-line"><span class="field-label">Source of information:</span> <span class="line-value"><?= escape_html((string) ($client['source_of_information'] ?? '')) ?></span></div>
          <div class="field-line"><span class="field-label">Complainant:</span> <span class="line-value"><?= escape_html((string) ($client['complainant'] ?? '')) ?></span></div>
        </div>
      </section>

      <section class="sheet-section">
        <h2>II. PROBLEMA NGA GIPRESENTAR</h2>
        <div class="lined-text-block"><?= render_text_block((string) ($client['presented_problem'] ?? ''), 4) ?></div>
      </section>

      <section class="sheet-section sheet-section--family">
        <h2>III. KOMPOSISYON SA PANIMALAY</h2>
        <div class="section-help-text">(Lakip ang mga membro sa pamilya nga mikaon o nag-ambit sa parehas nga kusina)</div>
        <table class="detail-table detail-table--family">
          <thead>
            <tr>
              <th>Pangalan</th>
              <th>Edad</th>
              <th>Gender</th>
              <th>Status Sibil</th>
              <th>Relasyon sa Kliyente</th>
              <th>Trabaho</th>
              <th>Binulan nga Kita</th>
              <th>Remarks</th>
            </tr>
          </thead>
          <tbody>
            <?= render_table_rows($familyMembers, ['member_name', 'member_age', 'member_gender', 'member_status', 'member_relation', 'member_work', 'member_income', 'member_remarks'], 5) ?>
          </tbody>
        </table>
      </section>

      </div>

      <footer class="sheet-footer">
        <div class="footer-left">
          <div>Phone: +639562241679 / +639816016317</div>
          <div>Email: cswdobutuan@gmail.com</div>
          <div>Website: http://www.butuan.gov.ph</div>
        </div>
        <div class="footer-right">
          <img src="../../../images-logo/footer.png" alt="ButuanOn logo" />
          <div><?= escape_html($formCode) ?></div>
        </div>
      </footer>
      <div class="sheet-footer-band"></div>
    </section>

    <section class="sheet-page sheet-page--next">
      <img class="sheet-header-image" src="../../../images-logo/header.png" alt="CSWD header" />

      <div class="sheet-page-content">
      <section class="sheet-section">
        <h2>IV. INITIAL DESCRIPTION OF THE:</h2>
        <div class="description-group">
          <div class="subsection-title">A. Family Environment:</div>
          <div class="lined-text-block"><?= render_text_block((string) ($client['family_environment'] ?? ''), 5) ?></div>
        </div>
        <div class="description-group">
          <div class="subsection-title">B. Community:</div>
          <div class="lined-text-block"><?= render_text_block((string) ($client['community_description'] ?? ''), 5) ?></div>
        </div>
        <div class="description-group">
          <div class="subsection-title">C. Family and History Background:</div>
          <div class="lined-text-block"><?= render_text_block((string) ($client['family_history_background'] ?? ''), 5) ?></div>
        </div>
      </section>

      <section class="sheet-section">
        <h2>V. INITIAL TREATMENT PLAN</h2>
        <div class="general-goal-label">General Goal:</div>
        <table class="detail-table detail-table--treatment">
          <thead>
            <tr>
              <th>Objectives</th>
              <th>Activities</th>
              <th>Responsible Person</th>
              <th>Resources Needed</th>
              <th>Expected Output</th>
            </tr>
          </thead>
          <tbody>
            <?= render_table_rows($treatmentPlans, ['objectives', 'activities', 'responsible_person', 'resources_needed', 'expected_output'], 5) ?>
          </tbody>
        </table>
      </section>

      <section class="sheet-section">
        <h2>VI. REKOMMENDASYON</h2>
        <div class="lined-text-block"><?= render_text_block((string) ($client['recommendation'] ?? ''), 3) ?></div>
      </section>

      <section class="signature-section">
        <div class="signature-block">
          <div class="signature-title">Prepared by:</div>
          <div class="signature-line"><?= escape_html($preparedBy) ?></div>
          <div class="signature-role">Clerk</div>
        </div>
        <div class="signature-block">
          <div class="signature-title">Noted by:</div>
          <div class="signature-line"><?= escape_html($notedBy) ?></div>
          <div class="signature-role">Social worker</div>
        </div>
      </section>

      <section class="consent-box">
        <div class="consent-title">Data Privacy Consent</div>
        <p>On behalf of all the members of my households, I confirm that the information I have provided in this form is true and represents accurate information of our household.</p>
        <p>I understand that the data collected from this validation will be processed, managed and maintained in a secure database by the City Social Welfare and Development (CSWD). Such data will be used to determine poverty status, serve as basis for research, and in the development and implementation of Social Protection Programs and Services, to promote the interest of the poor.</p>
        <p>I authorized CSWD to manage the information, including personal data obtained from this household validation activity and allow the processing and controlled disclosure or transfer of data to its development partners and other stakeholders in accordance with the CSWD policies and Data Sharing and the Provisions of Republic Act No. 10173 of the Data Privacy Act (DPA) of 2012.</p>
        <div class="client-signature-name"><?= escape_html($fullName) ?></div>
        <div class="client-signature-caption">(Name and Signature of Client)</div>
      </section>

      </div>

      <footer class="sheet-footer">
        <div class="footer-left">
          <div>Phone: +639562241679 / +639816016317</div>
          <div>Email: cswdobutuan@gmail.com</div>
          <div>Website: http://www.butuan.gov.ph</div>
        </div>
        <div class="footer-right">
          <img src="../../../images-logo/footer.png" alt="ButuanOn logo" />
          <div><?= escape_html($formCode) ?></div>
        </div>
      </footer>
      <div class="sheet-footer-band"></div>
    </section>
  </main>

  <script src="../shared/print-form.js"></script>
</body>
</html>
