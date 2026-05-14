<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../auth/auth.php';
require_once __DIR__ . '/../../../../auth/db.php';

require_login(['super admin', 'admin']);

function escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function build_full_name(array $record): string
{
    $parts = [
        trim((string) ($record['first_name'] ?? '')),
        trim((string) ($record['middle_name'] ?? '')),
        trim((string) ($record['last_name'] ?? '')),
        trim((string) ($record['suffix'] ?? '')),
    ];

    return trim(implode(' ', array_values(array_filter($parts, static fn(string $part): bool => $part !== ''))));
}

function parse_csv_values(string $value): array
{
    $items = array_filter(array_map('trim', explode(',', $value)), static fn(string $item): bool => $item !== '');
    $map = [];

    foreach ($items as $item) {
        $map[mb_strtolower($item)] = true;
    }

    return $map;
}

function checkbox(bool $isChecked): string
{
    return $isChecked ? '☑' : '☐';
}

$clientId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($clientId === false || $clientId === null) {
    http_response_code(400);
    echo 'Invalid or missing client id.';
    exit;
}

$client = null;
$familyRows = [];
$membershipRows = [];

try {
    $connection = get_database_connection();

    $clientQuery = $connection->prepare(
        'SELECT * FROM pyap_table WHERE key_id = ? LIMIT 1'
    );

    if (!$clientQuery) {
        throw new RuntimeException('Failed to prepare client query.');
    }

    $clientQuery->bind_param('i', $clientId);
    $clientQuery->execute();

    $clientResult = $clientQuery->get_result();
    $client = $clientResult ? $clientResult->fetch_assoc() : null;
    $clientQuery->close();

    if (!$client) {
        http_response_code(404);
        echo 'PYAP record not found.';
        exit;
    }

    $familyQuery = $connection->prepare(
        'SELECT member_name, relationship, gender, age, grade_year, isy, osy, work_labor_experience, income
         FROM pyap_family_composition_table
         WHERE pyap_id = ?
         ORDER BY id ASC'
    );

    if ($familyQuery) {
        $familyQuery->bind_param('i', $clientId);
        $familyQuery->execute();
        $familyResult = $familyQuery->get_result();

        if ($familyResult) {
            while ($row = $familyResult->fetch_assoc()) {
                $familyRows[] = [
                    'member_name' => (string) ($row['member_name'] ?? ''),
                    'relationship' => (string) ($row['relationship'] ?? ''),
                    'gender' => (string) ($row['gender'] ?? ''),
                    'age' => (string) ($row['age'] ?? ''),
                    'grade_year' => (string) ($row['grade_year'] ?? ''),
                    'isy' => (string) ($row['isy'] ?? ''),
                    'osy' => (string) ($row['osy'] ?? ''),
                    'work_labor_experience' => (string) ($row['work_labor_experience'] ?? ''),
                    'income' => (string) ($row['income'] ?? ''),
                ];
            }
        }

        $familyQuery->close();
    }

    $membershipQuery = $connection->prepare(
        'SELECT organization_name, position_held, year
         FROM pyap_membership_table
         WHERE pyap_id = ?
         ORDER BY id ASC'
    );

    if ($membershipQuery) {
        $membershipQuery->bind_param('i', $clientId);
        $membershipQuery->execute();
        $membershipResult = $membershipQuery->get_result();

        if ($membershipResult) {
            while ($row = $membershipResult->fetch_assoc()) {
                $membershipRows[] = [
                    'organization_name' => (string) ($row['organization_name'] ?? ''),
                    'position_held' => (string) ($row['position_held'] ?? ''),
                    'year' => (string) ($row['year'] ?? ''),
                ];
            }
        }

        $membershipQuery->close();
    }

    $connection->close();
} catch (Throwable $exception) {
    http_response_code(500);
    echo 'Unable to load print form data.';
    exit;
}

$fullName = build_full_name($client);
$fullAddress = trim((string) ($client['address'] ?? ''));
if (trim((string) ($client['barangay'] ?? '')) !== '') {
    $fullAddress = trim($fullAddress . ', ' . (string) ($client['barangay'] ?? ''));
}

$assistanceOptions = [
    'Educational Program (SAP)',
    'Food Subsidy',
    'Sports & Recreation Program',
    'Drug Awareness',
    'Government Outreach Program',
    'Livelihood Program',
    'Internship Outreach Program',
    'Mental Health Awareness',
    'Leadership Training',
    'Summer Job',
    'Others / Please Specify',
];

$interventionOptions = [
    'Skills Training',
    'Livelihood',
    'Others / Please Specify',
];

$assistanceSelected = parse_csv_values((string) ($client['assistance_received'] ?? ''));
$interventionSelected = parse_csv_values((string) ($client['intervention_needed'] ?? ''));
$currentDate = date('F d, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PYAP Membership Information Sheet</title>
  <link rel="stylesheet" href="print-form.css" />
</head>
<body>
  <div class="print-toolbar" aria-label="Print controls">
    <button type="button" id="printFormButton" class="print-btn">Print Form</button>
    <p class="print-hint">Shortcut: <strong>Ctrl + P</strong> / <strong>⌘ + P</strong></p>
  </div>

  <main class="print-page" id="printPage">
    <header class="print-page-header" aria-label="PYAP form header">
      <table class="pyap-header" role="presentation">
        <tr>
          <td class="pyap-header__seal-cell">
            <img src="../../../images-logo/butuanseal.png" alt="Butuan City Seal" class="pyap-header__seal" />
          </td>
          <td class="pyap-header__text-cell">
            <div class="pyap-header__text">
              <p>Republic of the Philippines</p>
              <p>CITY GOVERNMENT OF <span class="pyap-header__city">B<span class="pyap-header__city-accent">U</span>TUAN</span></p>
              <p class="pyap-header__department">City Social Welfare and Development Department</p>
              <p>Butuan City</p>
            </div>
          </td>
        </tr>
      </table>
      <div class="pyap-header__rule" aria-hidden="true"></div>
    </header>
    <div class="pyap-header__rule" aria-hidden="true"></div>

    <section class="section">
      <h4>I. PERSONAL DATA:</h4>
      <div class="line-row">
        <span>Name: <?= escape_html($fullName) ?></span>
        <span>Age: <?= escape_html((string) ($client['age'] ?? '')) ?></span>
        <span>Gender: <?= escape_html((string) ($client['sex'] ?? '')) ?></span>
        <span>Nationality: <?= escape_html((string) ($client['nationality'] ?? '')) ?></span>
      </div>
      <div class="line-row">
        <span>Address: <?= escape_html($fullAddress) ?></span>
        <span>Status: <?= escape_html((string) ($client['civil_status'] ?? '')) ?></span>
        <span>Cell No.: <?= escape_html((string) ($client['contact_number'] ?? '')) ?></span>
        <span>Religion: <?= escape_html((string) ($client['religion'] ?? '')) ?></span>
      </div>
      <div class="line-row">
        <span>Place of Birth: <?= escape_html((string) ($client['place_of_birth'] ?? '')) ?></span>
        <span>Occupation: <?= escape_html((string) ($client['occupation'] ?? '')) ?></span>
        <span>Income: <?= escape_html((string) ($client['income_data'] ?? '')) ?></span>
      </div>
      <div class="line-row class-row">
        <span>In School Youth: <?= escape_html((string) ($client['in_school_youth'] ?? '')) ?></span>
        <span>Out of School Youth: <?= escape_html((string) ($client['out_of_school_youth'] ?? '')) ?></span>
        <span>Other Disadvantage Youth: <?= escape_html((string) ($client['other_disadvantage_youth'] ?? '')) ?></span>
        <span>IP's: <?= escape_html((string) ($client['ips'] ?? '')) ?></span>
      </div>
    </section>

    <section class="section">
      <h4>FAMILY COMPOSITION</h4>
      <table class="family-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Relationship</th>
            <th>Sex</th>
            <th>Age</th>
            <th>Grad / Year</th>
            <th>I S Y</th>
            <th>O S Y</th>
            <th>Work/Labor Experience</th>
            <th>Income</th>
          </tr>
        </thead>
        <tbody>
          <?php for ($i = 0; $i < 6; $i++): ?>
            <?php $row = $familyRows[$i] ?? null; ?>
            <tr>
              <td><?= escape_html((string) ($row['member_name'] ?? '')) ?></td>
              <td><?= escape_html((string) ($row['relationship'] ?? '')) ?></td>
              <td><?= escape_html((string) ($row['gender'] ?? '')) ?></td>
              <td><?= escape_html((string) ($row['age'] ?? '')) ?></td>
              <td><?= escape_html((string) ($row['grade_year'] ?? '')) ?></td>
              <td><?= escape_html((string) ($row['isy'] ?? '')) ?></td>
              <td><?= escape_html((string) ($row['osy'] ?? '')) ?></td>
              <td><?= escape_html((string) ($row['work_labor_experience'] ?? '')) ?></td>
              <td><?= escape_html((string) ($row['income'] ?? '')) ?></td>
            </tr>
          <?php endfor; ?>
        </tbody>
      </table>

      <div class="line-block">
        <p>School Last Attended: <?= escape_html((string) ($client['school_last_attended'] ?? '')) ?> <span class="month-year">Month & Year <?= escape_html((string) ($client['school_last_attended_month_year'] ?? '')) ?></span></p>
        <p>Skills/Interest/Hobbies: <?= escape_html((string) ($client['skills_interest_hobbies'] ?? '')) ?></p>
        <p>Trainings attended: <?= escape_html((string) ($client['trainings_attended'] ?? '')) ?></p>
      </div>
    </section>

    <section class="section intervention-wrap">
      <div class="column">
        <h4>II Assistance & Intervention Received from CSWD & other Agencies:</h4>
        <div class="check-grid">
          <?php foreach ($assistanceOptions as $option): ?>
            <?php $isChecked = isset($assistanceSelected[mb_strtolower($option)]); ?>
            <p><?= checkbox($isChecked) ?> <?= escape_html($option) ?></p>
          <?php endforeach; ?>
        </div>
        <p class="specify">Please Specify: <?= escape_html((string) ($client['assistance_specify'] ?? '')) ?></p>
      </div>

      <div class="column small-col">
        <h4>IV Intervention needed:</h4>
        <?php foreach ($interventionOptions as $option): ?>
          <?php $isChecked = isset($interventionSelected[mb_strtolower($option)]); ?>
          <p><?= checkbox($isChecked) ?> <?= escape_html($option) ?></p>
        <?php endforeach; ?>
        <p class="specify">Please Specify: <?= escape_html((string) ($client['intervention_specify'] ?? '')) ?></p>
      </div>
    </section>

    <section class="section">
      <h4>IV. Membership to Organization, Clubs, Society:</h4>
      <table class="member-table">
        <thead>
          <tr>
            <th>Name of Organization</th>
            <th>Position Held, if any</th>
            <th>Year</th>
          </tr>
        </thead>
        <tbody>
          <?php for ($i = 0; $i < 4; $i++): ?>
            <?php $row = $membershipRows[$i] ?? null; ?>
            <tr>
              <td><?= escape_html((string) ($row['organization_name'] ?? '')) ?></td>
              <td><?= escape_html((string) ($row['position_held'] ?? '')) ?></td>
              <td><?= escape_html((string) ($row['year'] ?? '')) ?></td>
            </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </section>

    <section class="section consent">
      <h4>Data Privacy Consent</h4>
      <p>I confirm that the information I have provided in this form is true and represents accurate information.</p>
      <p>I understand that the data collected from this report will be processed, managed and maintained in a secure database by the City Social Welfare and Development (CSWD). Such data will used to serve as basis for research and in the development and implementation of Social Protection and Services, to promote and interest of the Out of School Youth and other Disadvantage Youth.</p>
      <p>I authorized CSWD to manage the information, including personal data obtained from this assessment/case study and allow the processing and controlled disclosure of transfer of data to its development partners and other stakeholders in accordance with the CSWD policies and data sharing and the provisions of Republic Act. No. 10173 of the Data Policy Act (DPA) of 2012.</p>
      <p class="youth-name"><?= escape_html($fullName) ?></p>
      <p class="youth-label">Name & Signature of the Youth</p>
    </section>

    <section class="signature-section">
      <div class="signatories">
        <div>
          <p class="name">ARIEL JOY J. TANSO</p>
          <p>Youth Service Provider</p>
          <p class="date">Date: <?= escape_html($currentDate) ?></p>
        </div>
        <div>
          <p class="name">FE C. BALASON, RSW</p>
          <p>SWO IV / DPSD Chief</p>
          <p class="date">Date: <?= escape_html($currentDate) ?></p>
        </div>
      </div>
    </section>

    <footer class="sheet-footer" aria-label="Contact footer">
      <div class="footer-content">
        <div class="footer-left">
          <div class="footer-row"><span class="footer-label">Phone:</span><span class="footer-value">+639562241679 / +639816016317</span></div>
          <div class="footer-row"><span class="footer-label">Email:</span><span class="footer-value underline">cswdobutuan@gmail.com</span></div>
          <div class="footer-row"><span class="footer-label">Website:</span><span class="footer-value underline">http://www.butuan.gov.ph</span></div>
        </div>
        <div class="footer-right">
          <img src="../pwud/print form/footer.png" alt="ButuanOn Logo" class="footer-logo" />
          <p class="footer-code">CSWDO.DPSD.F.014.REV 00</p>
        </div>
      </div>
    </footer>
  </main>

  <script src="print-form.js"></script>
</body>
</html>
