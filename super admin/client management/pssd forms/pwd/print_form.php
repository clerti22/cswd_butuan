<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../auth/auth.php';
require_once __DIR__ . '/../../../../auth/db.php';

require_login(['super admin', 'admin']);

function escape_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
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
                address, barangay, religion, sex, educational_attainment, occupation, monthly_income, civil_status,
                contact_number, services_needed, created_at, served_by
         FROM pwd_table
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
        echo 'PWD record not found.';
        exit;
    }

    $householdQuery = $connection->prepare(
        'SELECT member_name, member_age, member_gender, relationship_to_pwd, member_occupation
         FROM pwd_household_table
         WHERE pwd_key_id = ?
         ORDER BY household_id ASC'
    );

    if (!$householdQuery) {
        throw new RuntimeException('Failed to prepare household query.');
    }

    $householdQuery->bind_param('i', $clientId);
    $householdQuery->execute();

    $householdResult = $householdQuery->get_result();
    if ($householdResult) {
        while ($row = $householdResult->fetch_assoc()) {
            $householdMembers[] = [
                'member_name' => (string) ($row['member_name'] ?? ''),
                'member_age' => (string) ($row['member_age'] ?? ''),
                'member_gender' => (string) ($row['member_gender'] ?? ''),
                'relationship' => (string) ($row['relationship_to_pwd'] ?? ''),
                'member_occupation' => (string) ($row['member_occupation'] ?? ''),
            ];
        }
    }

    $householdQuery->close();
    $connection->close();
} catch (Throwable $exception) {
    http_response_code(500);
    echo 'Unable to load print form data.';
    exit;
}

$fullName = trim(implode(' ', array_filter([
    (string) ($client['first_name'] ?? ''),
    (string) ($client['middle_name'] ?? ''),
    (string) ($client['last_name'] ?? ''),
    (string) ($client['suffix'] ?? ''),
], static fn(string $part): bool => trim($part) !== '')));

$birthDate = '';
if (!empty($client['date_of_birth'])) {
    $timestamp = strtotime((string) $client['date_of_birth']);
    if ($timestamp !== false) {
        $birthDate = date('m/d/Y', $timestamp);
    }
}

$createdDate = '';
if (!empty($client['created_at'])) {
    $timestamp = strtotime((string) $client['created_at']);
    if ($timestamp !== false) {
        $createdDate = date('m/d/Y', $timestamp);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PWD Print Form</title>
  <link rel="stylesheet" href="../../dpsd forms/pwud/print form/print-form.css" />
</head>
<body>
  <div class="print-toolbar" aria-label="Print controls">
    <button type="button" id="printFormButton" class="print-btn">Print Form</button>
    <p class="print-hint">Shortcut: <strong>Ctrl + P</strong> / <strong>⌘ + P</strong></p>
  </div>

  <main class="print-page" id="printPage">
    <header class="pwud-header" aria-label="PWUD form header">
      <img src="../../../images-logo/butuanseal.png" alt="Butuan City Seal" class="pwud-header__seal" />
      <div class="pwud-header__text">
        <p class="pwud-header__line pwud-header__line--small">Republic of the Philippines</p>
        <p class="pwud-header__line pwud-header__line--city">CITY GOVERNMENT OF <span>BUTUAN</span></p>
        <p class="pwud-header__line pwud-header__line--dept">City Social Welfare and Development Department</p>
        <p class="pwud-header__line pwud-header__line--place">Butuan City</p>
      </div>
    </header>
    <div class="pwud-header__rule" aria-hidden="true"></div>

    <section class="meta-row">
      <div></div>
      <div class="meta-boxes">
        <div class="meta-item">
          <span class="box" aria-hidden="true"></span>
          <span>SCSR. NO. <?= escape_html((string) ($client['control_number'] ?? '')) ?></span>
        </div>
        <div class="meta-item">
          <span class="box" aria-hidden="true"></span>
          <span>CERT. NO. ______________</span>
        </div>
      </div>
    </section>

    <section class="title-row">
      <h1>PWD INTAKE SHEET</h1>
      <p>Date: <?= escape_html($createdDate) ?></p>
    </section>

    <section class="identifying-data">
      <h2>Identifying Data:</h2>
      <div class="info-grid">
        <p><span class="label">Name</span><span class="colon">:</span><?= escape_html($fullName) ?></p>
        <p><span class="label">Gender</span><span class="colon">:</span><?= escape_html((string) ($client['sex'] ?? '')) ?></p>
        <p><span class="label">Age</span><span class="colon">:</span><?= escape_html((string) ($client['age'] ?? '')) ?></p>
        <p><span class="label">Address</span><span class="colon">:</span><?= escape_html(trim(((string) ($client['address'] ?? '')) . ', ' . ((string) ($client['barangay'] ?? '')))) ?></p>
        <p><span class="label">B-date</span><span class="colon">:</span><?= escape_html($birthDate) ?></p>
        <p><span class="label">B-Place</span><span class="colon">:</span><?= escape_html((string) ($client['place_of_birth'] ?? '')) ?></p>
        <p><span class="label">Educ. Attn.</span><span class="colon">:</span><?= escape_html((string) ($client['educational_attainment'] ?? '')) ?></p>
        <p><span class="label">Religion</span><span class="colon">:</span><?= escape_html((string) ($client['religion'] ?? '')) ?></p>
        <p><span class="label">Occupation</span><span class="colon">:</span><?= escape_html((string) ($client['occupation'] ?? '')) ?></p>
        <p><span class="label">Monthly Income</span><span class="colon">:</span><?= escape_html(number_format((float) ($client['monthly_income'] ?? 0), 2)) ?></p>
        <p><span class="label">Contact No.</span><span class="colon">:</span><?= escape_html((string) ($client['contact_number'] ?? '')) ?></p>
        <p><span class="label">Civil Status</span><span class="colon">:</span><?= escape_html((string) ($client['civil_status'] ?? '')) ?></p>
      </div>
      <p class="services"><span class="label">Services Needed</span>: <?= escape_html((string) ($client['services_needed'] ?? '')) ?></p>
    </section>

    <section class="family-section">
      <h2>Family Composition</h2>
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Age</th>
            <th>Gender</th>
            <th>Relationship<br>with the Client</th>
            <th>Occupation</th>
          </tr>
        </thead>
        <tbody>
          <?php if (count($householdMembers) > 0): ?>
            <?php foreach ($householdMembers as $member): ?>
              <tr>
                <td><?= escape_html((string) ($member['member_name'] ?? '')) ?></td>
                <td><?= escape_html((string) ($member['member_age'] ?? '')) ?></td>
                <td><?= escape_html((string) ($member['member_gender'] ?? '')) ?></td>
                <td><?= escape_html((string) ($member['relationship'] ?? '')) ?></td>
                <td><?= escape_html((string) ($member['member_occupation'] ?? '')) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="5">No household records found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>

    <section class="signatures">
      <div class="sign-block">
        <p>Prepared by:</p>
        <p class="line-name"><?= escape_html($fullName) ?></p>
        <p>Client</p>
      </div>
      <div class="sign-block">
        <p>Reviewed by:</p>
        <p class="line-name"><?= escape_html((string) ($client['served_by'] ?? '')) ?></p>
        <p>Social Worker</p>
      </div>
    </section>

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
          <p class="tuv-subtitle">QMS 23 93 0116</p>
        </div>

        <div class="footer-right">
          <img src="../../../images-logo/footer.png" alt="ButuanOn Logo" class="footer-logo" />
          <p class="footer-code">CSWDD.RSPD.R.017.REV02</p>
        </div>
      </div>
    </footer>
  </main>

  <script src="../../dpsd forms/pwud/print form/print-form.js"></script>
</body>
</html>
