<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../auth/auth.php';
require_once __DIR__ . '/../../../../auth/db.php';

require_login(['super admin', 'admin', 'encoder']);

function escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function format_date_value(?string $value, string $format = 'm/d/Y'): string
{
    $trimmed = trim((string) $value);
    if ($trimmed === '') {
        return '';
    }

    $timestamp = strtotime($trimmed);
    return $timestamp === false ? $trimmed : date($format, $timestamp);
}

function format_time_value(?string $value): string
{
    $trimmed = trim((string) $value);
    if ($trimmed === '') {
        return '';
    }

    $timestamp = strtotime($trimmed);
    return $timestamp === false ? $trimmed : date('h:i A', $timestamp);
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

function is_truthy_value(?string $value): bool
{
    $normalized = strtolower(trim((string) $value));
    return in_array($normalized, ['yes', 'y', '1', 'true', 'checked', 'on'], true);
}

function normalize_lookup(?string $value): string
{
    return strtolower(trim((string) $value));
}

function render_checkbox(bool $checked): string
{
    return $checked ? '☑' : '☐';
}

function civil_status_state(?string $civilStatus): array
{
    $normalized = normalize_lookup($civilStatus);
    $standardMap = [
        'single' => ['single', 'single parent'],
        'widow' => ['widow', 'widowed'],
        'married' => ['married'],
        'separated' => ['separated'],
    ];

    foreach ($standardMap as $label => $acceptedValues) {
        if (in_array($normalized, $acceptedValues, true)) {
            return [
                'single' => $label === 'single',
                'widow' => $label === 'widow',
                'married' => $label === 'married',
                'separated' => $label === 'separated',
                'others' => false,
                'others_text' => '',
            ];
        }
    }

    return [
        'single' => false,
        'widow' => false,
        'married' => false,
        'separated' => false,
        'others' => $normalized !== '',
        'others_text' => trim((string) $civilStatus),
    ];
}

function family_gender_state(?string $gender): array
{
    $normalized = normalize_lookup($gender);

    return [
        'male' => in_array($normalized, ['male', 'm'], true),
        'female' => in_array($normalized, ['female', 'f'], true),
        'display' => trim((string) $gender),
    ];
}

$clientId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($clientId === false || $clientId === null) {
    http_response_code(400);
    echo 'Invalid or missing client id.';
    exit;
}

$client = null;
$familyMembers = [];

try {
    $connection = get_database_connection();

    $clientQuery = $connection->prepare('SELECT * FROM ciu_table WHERE key_id = ? LIMIT 1');
    if (!$clientQuery) {
        throw new RuntimeException('Failed to prepare CIU query.');
    }

    $clientQuery->bind_param('i', $clientId);
    $clientQuery->execute();
    $clientResult = $clientQuery->get_result();
    $client = $clientResult ? $clientResult->fetch_assoc() : null;
    $clientQuery->close();

    if (!$client) {
        http_response_code(404);
        echo 'CIU record not found.';
        exit;
    }

    $familyQuery = $connection->prepare(
        'SELECT member_name, member_age, member_gender, relationship_to_client, education_attainment, occupation, income
         FROM ciu_family_composition_table
         WHERE ciu_id = ?
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

    $connection->close();
} catch (Throwable $exception) {
    http_response_code(500);
    echo 'Unable to load print form data.';
    exit;
}

$fullName = build_full_name($client);
$createdDate = format_date_value((string) ($client['created_at'] ?? ''));
$createdTime = format_time_value((string) ($client['created_at'] ?? ''));
$birthDate = format_date_value((string) ($client['birth_date'] ?? ''));
$civilStatus = civil_status_state((string) ($client['civil_status'] ?? ''));
$preparedBy = $fullName;
$receivedBy = trim((string) ($client['served_by'] ?? 'CIU Clerk'));
$memberOtherText = trim((string) ($client['member_others'] ?? ''));
$serviceOtherText = trim((string) ($client['service_others'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CIU Print Form</title>
  <link rel="stylesheet" href="print-form.css" />
</head>
<body>
  <div class="print-toolbar" aria-label="Print controls">
    <button type="button" id="printFormButton" class="print-btn">Print Form</button>
    <p class="print-hint">Shortcut: <strong>Ctrl + P</strong> / <strong>⌘ + P</strong></p>
  </div>

  <main class="print-page" id="printPage">
    <table class="print-template" role="presentation">
      <thead class="print-template__head">
        <tr>
          <td>
            <header class="print-page-header">
              <img src="../../dpsd forms/pwud/print form/header.png" alt="CSWD Header" class="header-image" />
            </header>
          </td>
        </tr>
      </thead>
      <tbody class="print-template__body">
        <tr>
          <td>
            <section class="page-content">
      <section class="meta-title-block">
        <div class="meta-grid">
          <div class="meta-line"><span class="meta-label">Control No.</span><span class="meta-value"><?= escape_html((string) ($client['control_number'] ?? '')) ?></span></div>
          <div class="meta-line"><span class="meta-label">Date</span><span class="meta-value"><?= escape_html($createdDate) ?></span></div>
          <div class="meta-line"><span class="meta-label">Time</span><span class="meta-value"><?= escape_html($createdTime) ?></span></div>
        </div>
        <h1>GENERAL INTAKE SHEET</h1>
        <p class="meta-subtitle">(CIU INTAKE SHEET FORM)</p>
      </section>

      <section class="section-block">
        <h2>IDENTIFYING DATA</h2>
        <div class="field-grid field-grid--three">
          <div class="field-line field-line--span-2"><span class="field-label">Name</span><span class="field-value"><?= escape_html($fullName) ?></span></div>
          <div class="field-line"><span class="field-label">Gender</span><span class="field-value"><?= escape_html((string) ($client['sex'] ?? '')) ?></span></div>
          <div class="field-line"><span class="field-label">Age</span><span class="field-value"><?= escape_html((string) ($client['age'] ?? '')) ?></span></div>
          <div class="field-line field-line--span-2"><span class="field-label">Address</span><span class="field-value"><?= escape_html((string) ($client['address'] ?? '')) ?></span></div>
          <div class="field-line"><span class="field-label">Contact No.</span><span class="field-value"><?= escape_html((string) ($client['contact_number'] ?? '')) ?></span></div>
          <div class="field-line"><span class="field-label">B-date</span><span class="field-value"><?= escape_html($birthDate) ?></span></div>
          <div class="field-line field-line--span-2"><span class="field-label">B-place</span><span class="field-value"><?= escape_html((string) ($client['birth_place'] ?? '')) ?></span></div>
          <div class="field-line"><span class="field-label">Religion</span><span class="field-value"><?= escape_html((string) ($client['religion'] ?? '')) ?></span></div>
          <div class="field-line"><span class="field-label">Educ-Attn</span><span class="field-value"><?= escape_html((string) ($client['education'] ?? '')) ?></span></div>
          <div class="field-line"><span class="field-label">Occupation</span><span class="field-value"><?= escape_html((string) ($client['occupation'] ?? '')) ?></span></div>
          <div class="field-line"><span class="field-label">Monthly Income</span><span class="field-value"><?= escape_html((string) ($client['monthly_income'] ?? '')) ?></span></div>
        </div>
      </section>

      <section class="option-sections">
        <div class="option-card">
          <h2>CIVIL STATUS</h2>
          <div class="option-grid option-grid--two">
            <div class="option-item"><span class="check-marker"><?= escape_html(render_checkbox($civilStatus['single'])) ?></span><span>Single</span></div>
            <div class="option-item"><span class="check-marker"><?= escape_html(render_checkbox($civilStatus['widow'])) ?></span><span>Widow</span></div>
            <div class="option-item"><span class="check-marker"><?= escape_html(render_checkbox($civilStatus['married'])) ?></span><span>Married</span></div>
            <div class="option-item"><span class="check-marker"><?= escape_html(render_checkbox($civilStatus['separated'])) ?></span><span>Separated</span></div>
            <div class="option-item option-item--full"><span class="check-marker"><?= escape_html(render_checkbox($civilStatus['others'])) ?></span><span>Others:</span><span class="option-fill"><?= escape_html((string) $civilStatus['others_text']) ?></span></div>
          </div>
        </div>

        <div class="option-card">
          <h2>MEMBER(S) OF</h2>
          <div class="option-grid option-grid--two">
            <div class="option-item"><span class="check-marker"><?= escape_html(render_checkbox(is_truthy_value((string) ($client['member_senior'] ?? '')))) ?></span><span>Senior Citizen</span></div>
            <div class="option-item"><span class="check-marker"><?= escape_html(render_checkbox(is_truthy_value((string) ($client['member_solo_parent'] ?? '')))) ?></span><span>Solo Parent</span></div>
            <div class="option-item"><span class="check-marker"><?= escape_html(render_checkbox(is_truthy_value((string) ($client['member_pwd'] ?? '')))) ?></span><span>PWD</span></div>
            <div class="option-item"><span class="check-marker"><?= escape_html(render_checkbox(is_truthy_value((string) ($client['member_4ps'] ?? '')))) ?></span><span>4P's</span></div>
            <div class="option-item option-item--full"><span class="check-marker"><?= escape_html(render_checkbox($memberOtherText !== '')) ?></span><span>Others:</span><span class="option-fill"><?= escape_html($memberOtherText) ?></span></div>
          </div>
        </div>
      </section>

      <section class="section-block section-block--tight">
        <h2>SERVICES NEEDED</h2>
        <div class="option-grid option-grid--services">
          <div class="option-item"><span class="check-marker"><?= escape_html(render_checkbox(is_truthy_value((string) ($client['service_prescribed_medicine'] ?? '')))) ?></span><span>Prescribed Medicine Assistance</span></div>
          <div class="option-item"><span class="check-marker"><?= escape_html(render_checkbox(is_truthy_value((string) ($client['service_certificate_minor'] ?? '')))) ?></span><span>Certificate of Travelling Minor</span></div>
          <div class="option-item"><span class="check-marker"><?= escape_html(render_checkbox(is_truthy_value((string) ($client['service_hospital_bill'] ?? '')))) ?></span><span>Hospital Bill Assistance</span></div>
          <div class="option-item"><span class="check-marker"><?= escape_html(render_checkbox(is_truthy_value((string) ($client['service_tesda_certificate'] ?? '')))) ?></span><span>TESDA Certificate</span></div>
          <div class="option-item"><span class="check-marker"><?= escape_html(render_checkbox(is_truthy_value((string) ($client['service_burial'] ?? '')))) ?></span><span>Burial Assistance</span></div>
          <div class="option-item"><span class="check-marker"><?= escape_html(render_checkbox(is_truthy_value((string) ($client['service_laboratory'] ?? '')))) ?></span><span>Laboratory Assistance</span></div>
          <div class="option-item"><span class="check-marker"><?= escape_html(render_checkbox(is_truthy_value((string) ($client['service_legal_assistance'] ?? '')))) ?></span><span>Free Legal Assistance (PAO/IBP)</span></div>
          <div class="option-item option-item--full"><span class="check-marker"><?= escape_html(render_checkbox($serviceOtherText !== '')) ?></span><span>Others:</span><span class="option-fill"><?= escape_html($serviceOtherText) ?></span></div>
        </div>
      </section>

      <section class="section-block section-block--grow">
        <h2>FAMILY COMPOSITION</h2>
        <table class="detail-table detail-table--family">
          <thead>
            <tr>
              <th rowspan="2">Name</th>
              <th rowspan="2">Age</th>
              <th colspan="2">Gender</th>
              <th rowspan="2">Relationship to the Client</th>
              <th rowspan="2">Educ. Attn.</th>
              <th rowspan="2">Occupation</th>
              <th rowspan="2">Income (Monthly)</th>
            </tr>
            <tr>
              <th>Male</th>
              <th>Female</th>
            </tr>
          </thead>
          <tbody>
            <?php for ($index = 0, $rowCount = max(6, count($familyMembers)); $index < $rowCount; $index++): ?>
              <?php
                $member = $familyMembers[$index] ?? [];
                $genderState = family_gender_state((string) ($member['member_gender'] ?? ''));
              ?>
              <tr>
                <td><?= escape_html((string) ($member['member_name'] ?? '')) ?></td>
                <td class="text-center"><?= escape_html((string) ($member['member_age'] ?? '')) ?></td>
                <td class="text-center"><?= escape_html(render_checkbox($genderState['male'])) ?></td>
                <td class="text-center"><?= escape_html(render_checkbox($genderState['female'])) ?></td>
                <td><?= escape_html((string) ($member['relationship_to_client'] ?? '')) ?></td>
                <td><?= escape_html((string) ($member['education_attainment'] ?? '')) ?></td>
                <td><?= escape_html((string) ($member['occupation'] ?? '')) ?></td>
                <td><?= escape_html((string) ($member['income'] ?? '')) ?></td>
              </tr>
            <?php endfor; ?>
          </tbody>
        </table>
      </section>

      <footer class="signature-footer">
        <div class="signature-block">
          <div class="signature-label">Prepared by:</div>
          <div class="signature-line"><?= escape_html($preparedBy) ?></div>
          <div class="signature-caption">Client</div>
        </div>
        <div class="signature-block">
          <div class="signature-label">Received by:</div>
          <div class="signature-line"><?= escape_html($receivedBy) ?></div>
          <div class="signature-caption">CIU Clerk</div>
        </div>
      </footer>
            </section>
          </td>
        </tr>
      </tbody>
      <tfoot class="print-template__foot">
        <tr>
          <td>
            <footer class="print-page-footer sheet-footer" aria-label="Contact footer">
              <div class="footer-content">
                <div class="footer-left">
                  <div class="footer-row"><span class="footer-label">Phone:</span><span class="footer-value">+639562241679 / +639816016317</span></div>
                  <div class="footer-row"><span class="footer-label">Email:</span><span class="footer-value underline">cswdobutuan@gmail.com</span></div>
                  <div class="footer-row"><span class="footer-label">Website:</span><span class="footer-value underline">http://www.butuan.gov.ph</span></div>
                </div>
                <div class="footer-right">
                  <p class="footer-code">CSWDD.F.053.REV01</p>
                  <img src="../../dpsd forms/pwud/print form/footer.png" alt="ButuanOn Logo" class="footer-logo" />
                </div>
              </div>
              <div class="footer-bar" aria-hidden="true"></div>
            </footer>
          </td>
        </tr>
      </tfoot>
    </table>
  </main>

  <script src="../shared/print-form.js"></script>
</body>
</html>
