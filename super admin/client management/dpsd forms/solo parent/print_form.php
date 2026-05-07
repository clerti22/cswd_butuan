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

function format_date_value(?string $value, string $format = 'm/d/Y'): string
{
    $rawValue = trim((string) $value);
    if ($rawValue === '' || $rawValue === '0000-00-00') {
        return '';
    }

    $timestamp = strtotime($rawValue);
    if ($timestamp === false) {
        return '';
    }

    return date($format, $timestamp);
}

function format_currency($value): string
{
    $numericValue = is_numeric($value) ? (float) $value : 0.0;
    if (abs($numericValue) < 0.00001) {
        return '';
    }

    return number_format($numericValue, 2);
}

function normalize_yes_no(?string $value): string
{
    $normalized = strtolower(trim((string) $value));
    return $normalized === 'yes' ? 'yes' : 'no';
}

function render_yes_no(string $value): string
{
    $normalized = normalize_yes_no($value);

    return sprintf(
        'Y <span class="inline-choice">%s</span>&nbsp;&nbsp;&nbsp;N <span class="inline-choice">%s</span>',
        $normalized === 'yes' ? '/' : '',
        $normalized === 'no' ? '/' : ''
    );
}

function render_employment_status(?string $value): string
{
    $normalized = strtolower(trim((string) $value));
    $options = [
        'employed' => 'Employed',
        'self-employed' => 'Self-employed',
        'not employed' => 'Not employed',
    ];

    $parts = [];
    foreach ($options as $key => $label) {
        $marker = $normalized === $key ? '/' : '';
        $parts[] = sprintf('%s <span class="inline-choice">%s</span>', $label, $marker);
    }

    return implode('&nbsp;&nbsp;&nbsp;', $parts);
}

$clientId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($clientId === false || $clientId === null) {
    http_response_code(400);
    echo 'Invalid or missing client id.';
    exit;
}

$client = null;
$householdMembers = [];

try {
    $connection = get_database_connection();

    $clientQuery = $connection->prepare(
        'SELECT key_id, control_number, first_name, middle_name, last_name, suffix, date_of_birth, place_of_birth, age,
                division, program, address, barangay, religion, sex, educational_attainment, occupation, company_agency,
                civil_status, employment_status, contact_number, monthly_income, philsys_card_number, email_address,
                pantawid_beneficiary, pantawid_household_id, indigenous_person, indigenous_affiliation_name, lgbtq_plus,
                pwd_status, solo_classification, solo_needs, served_by, created_at
         FROM soloparent_info_table
         WHERE key_id = ?
         LIMIT 1'
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
        echo 'Solo Parent record not found.';
        exit;
    }

    $householdQuery = $connection->prepare(
        'SELECT household_name, household_sex, household_relationship, household_age, household_birthdate,
                household_civil_status, household_educational, household_occupation, household_monthly_income
         FROM household_members_soloparent
         WHERE solo_parent_id = ?
         ORDER BY id ASC'
    );

    if (!$householdQuery) {
        throw new RuntimeException('Failed to prepare household query.');
    }

    $householdQuery->bind_param('i', $clientId);
    $householdQuery->execute();
    $householdResult = $householdQuery->get_result();

    if ($householdResult) {
        while ($row = $householdResult->fetch_assoc()) {
            $householdMembers[] = $row;
        }
    }

    $householdQuery->close();
    $connection->close();
} catch (Throwable $exception) {
    http_response_code(500);
    echo 'Unable to load print form data.';
    exit;
}

$fullName = build_full_name($client);
$fullAddress = trim(implode(', ', array_values(array_filter([
    trim((string) ($client['address'] ?? '')),
    trim((string) ($client['barangay'] ?? '')),
], static fn(string $part): bool => $part !== ''))));

$householdRows = max(4, count($householdMembers));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Solo Parent Print Form</title>
  <link rel="stylesheet" href="print-form.css" />
</head>
<body>
  <div class="print-toolbar" aria-label="Print controls">
    <button type="button" id="printFormButton" class="print-btn">Print Form</button>
    <p class="print-hint">Shortcut: <strong>Ctrl + P</strong> / <strong>⌘ + P</strong></p>
  </div>

  <main class="print-page" id="printPage">
    <table class="document-table" aria-label="Solo Parent application form">
      <tr>
        <td colspan="12" class="header-cell custom-header-cell no-border-bottom">
          <div class="solo-print-header" aria-label="City government header">
            <img src="../../../images-logo/cswd-logo.png" alt="City of Butuan Official Seal" class="city-seal-logo" />
            <div class="government-text-block">
              <div class="gov-line gov-line-1">Republic of the Philippines</div>
              <div class="gov-line gov-line-2">CITY GOVERNMENT OF <span class="city-name-highlight">BUTUAN</span></div>
              <div class="gov-line gov-line-3">City Social Welfare and Development Department</div>
              <div class="gov-line gov-line-4">Butuan City</div>
            </div>
          </div>
          <div class="header-rule-wrap" aria-hidden="true"></div>
        </td>
      </tr>
      <tr>
        <td colspan="12" class="form-title-cell no-border-top no-border-bottom">
          <div class="form-title">APPLICATION FORM FOR SOLO PARENT</div>
        </td>
      </tr>
      <tr>
        <td colspan="12" class="case-number-cell no-border-top">
          <span class="case-label">Case Number:</span>
          <span class="case-value"><?= escape_html((string) ($client['control_number'] ?? '')) ?></span>
          <span class="case-note">(10 digit PSGC-YYMM-sequential 6 digits)</span>
        </td>
      </tr>

      <tr><td colspan="12" class="section-heading">I. IDENTIFYING INFORMATION</td></tr>
      <tr>
        <td colspan="4" class="label-cell"><strong>Full Name:</strong> <span class="value-text"><?= escape_html($fullName) ?></span></td>
        <td colspan="8" class="label-cell centered"><strong>Philsys Card Number:</strong> <span class="value-text"><?= escape_html((string) ($client['philsys_card_number'] ?? '')) ?></span></td>
      </tr>
      <tr>
        <td colspan="3" class="label-cell"><strong>Date of Birth:</strong> <span class="value-text"><?= escape_html(format_date_value((string) ($client['date_of_birth'] ?? ''))) ?></span></td>
        <td colspan="2" class="label-cell"><strong>Age:</strong> <span class="value-text"><?= escape_html((string) ($client['age'] ?? '')) ?></span></td>
        <td colspan="3" class="label-cell"><strong>Place of Birth:</strong> <span class="value-text"><?= escape_html((string) ($client['place_of_birth'] ?? '')) ?></span></td>
        <td colspan="4" class="label-cell"><strong>Sex:</strong> <span class="value-text"><?= escape_html((string) ($client['sex'] ?? '')) ?></span></td>
      </tr>
      <tr>
        <td colspan="12" class="label-cell"><strong>Address:</strong> <span class="value-text"><?= escape_html($fullAddress) ?></span></td>
      </tr>
      <tr>
        <td colspan="6" class="label-cell"><strong>Educational Attainment:</strong> <span class="value-text"><?= escape_html((string) ($client['educational_attainment'] ?? '')) ?></span></td>
        <td colspan="6" class="label-cell"><strong>Civil Status:</strong> <span class="value-text"><?= escape_html((string) ($client['civil_status'] ?? '')) ?></span></td>
      </tr>
      <tr>
        <td colspan="6" class="label-cell"><strong>Occupation:</strong> <span class="value-text"><?= escape_html((string) ($client['occupation'] ?? '')) ?></span></td>
        <td colspan="6" class="label-cell"><strong>Religion:</strong> <span class="value-text"><?= escape_html((string) ($client['religion'] ?? '')) ?></span></td>
      </tr>
      <tr>
        <td colspan="6" class="label-cell"><strong>Company/Agency:</strong> <span class="value-text"><?= escape_html((string) ($client['company_agency'] ?? '')) ?></span></td>
        <td colspan="6" class="label-cell"><strong>Monthly Income:</strong> <span class="value-text"><?= escape_html(format_currency($client['monthly_income'] ?? null)) ?></span></td>
      </tr>
      <tr>
        <td colspan="12" class="label-cell"><strong>Employment Status:</strong> <span class="value-text"><?= render_employment_status((string) ($client['employment_status'] ?? '')) ?></span></td>
      </tr>
      <tr>
        <td colspan="6" class="label-cell"><strong>Contact number/s:</strong> <span class="value-text"><?= escape_html((string) ($client['contact_number'] ?? '')) ?></span></td>
        <td colspan="6" class="label-cell"><strong>Email Address:</strong> <span class="value-text"><?= escape_html((string) ($client['email_address'] ?? '')) ?></span></td>
      </tr>
      <tr>
        <td colspan="3" class="label-cell"><strong>Pantawid Beneficiary?</strong> <span class="value-text"><?= render_yes_no((string) ($client['pantawid_beneficiary'] ?? 'no')) ?></span></td>
        <td colspan="4" class="label-cell"><strong>Indigenous Person?</strong> <span class="value-text"><?= render_yes_no((string) ($client['indigenous_person'] ?? 'no')) ?></span></td>
        <td colspan="3" class="label-cell"><strong>LGBTQ+?</strong> <span class="value-text"><?= render_yes_no((string) ($client['lgbtq_plus'] ?? 'no')) ?></span></td>
        <td colspan="2" class="label-cell"><strong>PWD?</strong> <span class="value-text"><?= render_yes_no((string) ($client['pwd_status'] ?? 'no')) ?></span></td>
      </tr>
      <tr>
        <td colspan="3" class="subnote-cell">If Yes Household ID #:<span class="value-text inline-note"><?= escape_html((string) ($client['pantawid_household_id'] ?? '')) ?></span></td>
        <td colspan="7" class="subnote-cell">If Yes, Name of Affiliation:<span class="value-text inline-note"><?= escape_html((string) ($client['indigenous_affiliation_name'] ?? '')) ?></span></td>
        <td colspan="2" class="subnote-cell"></td>
      </tr>

      <tr><td colspan="12" class="section-heading">II. HOUSEHOLD COMPOSITION</td></tr>
      <tr class="table-header-row">
        <td colspan="2" class="centered"><strong>Name</strong></td>
        <td colspan="1" class="centered"><strong>Sex</strong></td>
        <td colspan="2" class="centered"><strong>Relationship</strong></td>
        <td colspan="1" class="centered"><strong>Age</strong></td>
        <td colspan="2" class="centered"><strong>Birthdate</strong></td>
        <td colspan="1" class="centered"><strong>Civil</strong></td>
        <td colspan="1" class="centered"><strong>Educational</strong></td>
        <td colspan="1" class="centered"><strong>Occupation</strong></td>
        <td colspan="1" class="centered"><strong>Monthly Income</strong></td>
      </tr>
      <?php for ($index = 0; $index < $householdRows; $index++): ?>
        <?php $member = $householdMembers[$index] ?? null; ?>
        <tr class="household-row">
          <td colspan="2"><?= escape_html((string) ($member['household_name'] ?? '')) ?></td>
          <td colspan="1" class="centered"><?= escape_html((string) ($member['household_sex'] ?? '')) ?></td>
          <td colspan="2"><?= escape_html((string) ($member['household_relationship'] ?? '')) ?></td>
          <td colspan="1" class="centered"><?= escape_html((string) ($member['household_age'] ?? '')) ?></td>
          <td colspan="2" class="centered"><?= escape_html(format_date_value((string) ($member['household_birthdate'] ?? ''))) ?></td>
          <td colspan="1" class="centered"><?= escape_html((string) ($member['household_civil_status'] ?? '')) ?></td>
          <td colspan="1" class="centered"><?= escape_html((string) ($member['household_educational'] ?? '')) ?></td>
          <td colspan="1" class="centered"><?= escape_html((string) ($member['household_occupation'] ?? '')) ?></td>
          <td colspan="1" class="right-align"><?= escape_html(format_currency($member['household_monthly_income'] ?? null)) ?></td>
        </tr>
      <?php endfor; ?>
      <tr>
        <td colspan="12" class="note-row"><em>NOTE: Include family member and other members of the household especially minor children. Use back side for additional members.</em></td>
      </tr>

      <tr><td colspan="12" class="section-heading">III. Classification/Circumstances of being a solo parent (Dahilan bakit naging solo parent)?</td></tr>
      <tr>
        <td colspan="12" class="text-area-cell multiline"><?= nl2br(escape_html((string) ($client['solo_classification'] ?? ''))) ?></td>
      </tr>

      <tr><td colspan="12" class="section-heading">IV. Needs/Problems of being a solo parent (Kinakailangan/Problema ng isang solo parent)?</td></tr>
      <tr>
        <td colspan="12" class="text-area-cell multiline"><?= nl2br(escape_html((string) ($client['solo_needs'] ?? ''))) ?></td>
      </tr>

      <tr><td colspan="12" class="section-heading">V. IN CASE OF EMERGENCY</td></tr>
      <tr>
        <td colspan="6" class="label-cell"><strong>Name:</strong> <span class="value-text placeholder-line">&nbsp;</span></td>
        <td colspan="6" class="label-cell"><strong>Relationship:</strong> <span class="value-text placeholder-line">&nbsp;</span></td>
      </tr>
      <tr>
        <td colspan="6" class="label-cell"><strong>Address:</strong> <span class="value-text placeholder-line">&nbsp;</span></td>
        <td colspan="6" class="label-cell"><strong>Contact number/s:</strong> <span class="value-text placeholder-line">&nbsp;</span></td>
      </tr>

      <tr>
        <td colspan="12" class="certification-cell">
          I hereby certify that the information given above are true and correct. I further understand that any misinterpretation that may have made will subject me to criminal and civil liabilities provided for by existing laws. In addition, I hereby give my consent to share the information above to the member agencies of the Inter-Agency Coordinating and Monitoring Committee on solo parents.
        </td>
      </tr>
      <tr>
        <td colspan="4" class="signature-cell centered"><span class="signature-line"></span><br />Signature/Thumbmark over Printed Name</td>
        <td colspan="4" class="signature-cell centered"><span class="signature-line date-line"></span><br />Date</td>
        <td colspan="4" class="signature-cell"></td>
      </tr>

      <tr><td colspan="12" class="for-office-heading">FOR SPD/SPO USE ONLY</td></tr>
      <tr>
        <td colspan="12" class="office-use-cell">
          <table class="office-table">
            <tr>
              <td class="office-status-label"><strong>STATUS</strong></td>
              <td class="office-status-box"></td>
              <td class="office-status-text">New</td>
              <td class="office-status-box"></td>
              <td class="office-status-text">Renewal</td>
              <td class="office-status-box"></td>
              <td class="office-status-text">Terminated</td>
              <td class="office-status-box"></td>
              <td class="office-status-text">Disapproved</td>
            </tr>
            <tr>
              <td colspan="5" class="office-field">Solo Parent Identification Card Number: ____________________</td>
              <td colspan="4" class="office-field">Date Issuance: ____________________</td>
            </tr>
            <tr>
              <td colspan="6" class="office-field">Solo parent category: ________________________________</td>
              <td colspan="3" class="office-field">Beneficiary Code: ____________________</td>
            </tr>
          </table>
        </td>
      </tr>
      <tr>
        <td colspan="12" class="footer-main-cell">
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
        </td>
      </tr>
    </table>
  </main>

  <script src="print-form.js"></script>
</body>
</html>
