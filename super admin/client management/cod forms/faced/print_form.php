<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../auth/auth.php';
require_once __DIR__ . '/../../../../auth/db.php';

require_login(['super admin', 'admin']);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function yes_mark(string $value, string $target): string
{
    return strtolower(trim($value)) === strtolower(trim($target)) ? '✓' : '';
}

$controlNumber = trim((string) ($_GET['control_number'] ?? ''));
if ($controlNumber === '') {
    http_response_code(400);
    echo 'Missing control number.';
    exit;
}

$record = null;
$familyRows = [];

try {
    $connection = get_database_connection();

    $stmt = $connection->prepare('SELECT * FROM faced_table WHERE control_number = ? LIMIT 1');
    $stmt->bind_param('s', $controlNumber);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$record) {
        http_response_code(404);
        echo 'FACED record not found.';
        exit;
    }

    $familyStmt = $connection->prepare('SELECT family_member, relation_to_head, birthdate, age, sex, highest_educational_attainment, occupation, remarks FROM faced_family_information_table WHERE control_number = ? ORDER BY id ASC');
    $familyStmt->bind_param('s', $controlNumber);
    $familyStmt->execute();
    $familyResult = $familyStmt->get_result();
    if ($familyResult) {
        while ($row = $familyResult->fetch_assoc()) {
            $familyRows[] = $row;
        }
    }
    $familyStmt->close();

    $connection->close();
} catch (Throwable $error) {
    http_response_code(500);
    echo 'Unable to load FACED print data.';
    exit;
}

$emptyRows = max(12 - count($familyRows), 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FACED Print Form</title>
  <link rel="stylesheet" href="print-form.css">
</head>
<body>
  <div class="toolbar"><button type="button" id="printFormButton">Print Form</button></div>
  <main class="sheet">
    <header class="faced-header">
      <div class="faced-header__left">
        <img class="logo-box" src="../../../images-logo/cswd-logo.png" alt="CSWD logo">
        <div class="header-copy">
          <p>Republic of the Philippines</p>
          <p>Department of Social Welfare and Development</p>
          <p>FAMILY ASSISTANCE CARD IN EMERGENCIES AND DISASTERS (FACED)</p>
        </div>
      </div>
      <div class="serial-box">
        <p>THIS CARD IS NOT FOR SALE</p><p>SOCIAL WORKER'S COPY</p><p>OFFICIAL USE ONLY</p>
        <span>SERIAL NUMBER</span>
      </div>
    </header>

    <section class="section-title">LOCATION OF THE AFFECTED FAMILY</section>
    <div class="two-col">
      <div>1. REGION <span><?= e((string) ($record['region'] ?? '')) ?></span></div>
      <div>4. CITY/MUNICIPALITY <span><?= e((string) ($record['city_municipality'] ?? '')) ?></span></div>
      <div>2. PROVINCE <span><?= e((string) ($record['province'] ?? '')) ?></span></div>
      <div>5. BARANGAY <span><?= e((string) ($record['barangay'] ?? '')) ?></span></div>
      <div>3. DISTRICT <span><?= e((string) ($record['district'] ?? '')) ?></span></div>
      <div>6. EVACUATION CENTER/SITE <span><?= e((string) ($record['evacuation_site'] ?? '')) ?></span></div>
    </div>

    <section class="section-title">HEAD OF THE FAMILY</section>
    <div class="two-col">
      <div>7. LAST NAME <span><?= e((string) ($record['last_name'] ?? '')) ?></span></div>
      <div>15. CIVIL STATUS <span><?= e((string) ($record['civil_status'] ?? '')) ?></span></div>
      <div>8. FIRST NAME <span><?= e((string) ($record['first_name'] ?? '')) ?></span></div>
      <div>16. MOTHER'S MAIDEN NAME <span><?= e((string) ($record['mother_maiden_name'] ?? '')) ?></span></div>
      <div>9. MIDDLE NAME <span><?= e((string) ($record['middle_name'] ?? '')) ?></span></div>
      <div>17. RELIGION <span><?= e((string) ($record['religion'] ?? '')) ?></span></div>
      <div>10. NAME EXT. <span><?= e((string) ($record['suffix'] ?? '')) ?></span></div>
      <div>18. OCCUPATION <span><?= e((string) ($record['occupation'] ?? '')) ?></span></div>
      <div>11. BIRTHDATE <span><?= e((string) ($record['birth_date'] ?? '')) ?></span></div>
      <div>19. MONTHLY FAMILY NET INCOME <span><?= e((string) ($record['monthly_income'] ?? '')) ?></span></div>
      <div>12. AGE <span><?= e((string) ($record['age'] ?? '')) ?></span></div>
      <div>20. ID CARD PRESENTED <span><?= e((string) ($record['id_card_presented'] ?? '')) ?></span></div>
      <div>13. BIRTHPLACE <span><?= e((string) ($record['birth_place'] ?? '')) ?></span></div>
      <div>21. ID CARD NUMBER <span><?= e((string) ($record['id_card_number'] ?? '')) ?></span></div>
      <div>14. SEX [<?= yes_mark((string) ($record['sex'] ?? ''), 'Male') ?>] Male [<?= yes_mark((string) ($record['sex'] ?? ''), 'Female') ?>] Female</div>
      <div>22. CONTACT NUMBER <span><?= e((string) ($record['contact_number'] ?? '')) ?></span></div>
    </div>

    <div class="line-row">23. PERMANENT ADDRESS <span><?= e((string) ($record['full_address'] ?? '')) ?></span></div>
    <div class="line-row compact">24. OTHERS [<?= yes_mark((string) ($record['is_4ps'] ?? ''), 'Yes') ?>] 4Ps Beneficiary &nbsp;&nbsp; [<?= yes_mark((string) ($record['is_ip'] ?? ''), 'Yes') ?>] IP - Type of Ethnicity <span><?= e((string) ($record['ethnicity_type'] ?? '')) ?></span></div>

    <section class="section-title">25. FAMILY INFORMATION</section>
    <table>
      <thead><tr><th>FAMILY MEMBERS</th><th>RELATION TO FAMILY HEAD</th><th>BIRTHDATE</th><th>AGE</th><th>SEX</th><th>HIGHEST EDUCATIONAL ATTAINMENT</th><th>OCCUPATION</th><th>REMARKS</th></tr></thead>
      <tbody>
      <?php foreach ($familyRows as $row): ?>
        <tr><td><?= e((string) $row['family_member']) ?></td><td><?= e((string) $row['relation_to_head']) ?></td><td><?= e((string) $row['birthdate']) ?></td><td><?= e((string) $row['age']) ?></td><td><?= e((string) $row['sex']) ?></td><td><?= e((string) $row['highest_educational_attainment']) ?></td><td><?= e((string) $row['occupation']) ?></td><td><?= e((string) $row['remarks']) ?></td></tr>
      <?php endforeach; ?>
      <?php for ($i=0;$i<$emptyRows;$i++): ?><tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr><?php endfor; ?>
      </tbody>
    </table>

    <div class="five-col">
      <div>26. NO. OF VULNERABLE FAMILY MEMBERS: <strong><?= e((string) ($record['vulnerable_count'] ?? '')) ?></strong></div>
      <div>No. of Older Persons: <strong><?= e((string) ($record['older_persons'] ?? '')) ?></strong></div>
      <div>No. of Pregnant Women: <strong><?= e((string) ($record['pregnant_women'] ?? '')) ?></strong></div>
      <div>No. of Lactating Women: <strong><?= e((string) ($record['lactating_women'] ?? '')) ?></strong></div>
      <div>No. of PWDs: <strong><?= e((string) ($record['pwds_medical'] ?? '')) ?></strong></div>
    </div>
    <div class="two-col bottom-choice">
      <div>27. HOUSE OWNERSHIP [<?= yes_mark((string) ($record['house_ownership'] ?? ''), 'Owner') ?>] Owner [<?= yes_mark((string) ($record['house_ownership'] ?? ''), 'Renter') ?>] Renter [<?= yes_mark((string) ($record['house_ownership'] ?? ''), 'Sharer') ?>] Sharer</div>
      <div>28. SHELTER DAMAGE CLASSIFICATION [<?= yes_mark((string) ($record['shelter_damage'] ?? ''), 'Partially Damaged') ?>] Partially Damaged [<?= yes_mark((string) ($record['shelter_damage'] ?? ''), 'Totally Damaged') ?>] Totally Damaged</div>
    </div>
  </main>
  <script src="print-form.js"></script>
</body>
</html>
