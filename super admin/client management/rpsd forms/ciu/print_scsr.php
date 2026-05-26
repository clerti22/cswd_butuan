<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../auth/auth.php';
require_once __DIR__ . '/../../../../auth/db.php';

require_login(['super admin', 'admin']);

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
        'SELECT member_name, member_age, relationship_to_client, education_attainment, occupation, income
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
$birthDate = format_date_value((string) ($client['birth_date'] ?? ''));
$clienteleCategory = trim((string) ($client['member_others'] ?? '')) !== '' ? trim((string) $client['member_others']) : 'Member/s from CIU';
$presentProblem = trim((string) ($client['present_problem'] ?? ''));
$recommendation = trim((string) ($client['recommendation'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SCSR Print Form</title>
  <link rel="stylesheet" href="print-scsr.css" />
</head>
<body>
  <div class="print-toolbar" aria-label="Print controls">
    <div class="toolbar-actions">
      <button type="button" id="printFormButton" class="print-btn">Print Form</button>
      <button type="button" id="editPreparedByButton" class="print-btn print-btn--secondary">Edit Prepared By</button>
    </div>
    <p class="print-hint">Shortcut: <strong>Ctrl + P</strong> / <strong>⌘ + P</strong></p>
  </div>

  <main class="print-page" id="printPage">
    <table class="print-template" role="presentation">
      <thead class="print-template__head">
        <tr>
          <td>
            <header class="print-page-header">
              <table class="scsr-header" role="presentation">
                <tr>
                  <td class="scsr-header__seal-cell">
                    <img src="../../../images-logo/butuanseal.png" alt="Butuan City Seal" class="scsr-header__seal" />
                  </td>
                  <td class="scsr-header__text-cell">
                    <div class="scsr-header__text">
                      <p>Republic of the Philippines</p>
                      <p>CITY GOVERNMENT OF <span class="scsr-header__city">B<span class="scsr-header__city-accent">U</span>TUAN</span></p>
                      <p class="scsr-header__department">City Social Welfare and Development Department</p>
                      <p>Butuan City</p>
                    </div>
                  </td>
                </tr>
              </table>
              <div class="scsr-header__rule" aria-hidden="true"></div>
            </header>
          </td>
        </tr>
      </thead>

      <tbody class="print-template__body">
        <tr>
          <td>
            <section class="page-content">
              <section class="meta-row">
                <div>Control No: <span class="line"><?= escape_html((string) ($client['control_number'] ?? '')) ?></span></div>
                <div>Date: <span class="line"><?= escape_html($createdDate) ?></span></div>
              </section>

              <h1>SOCIAL CASE STUDY REPORT</h1>

              <section class="section-block">
                <h2>I. Identifying Data:</h2>
                <div class="identifying-grid">
                  <div><span>Name</span><span>:</span><span><?= escape_html($fullName) ?></span></div>
                  <div><span>Gender</span><span>:</span><span><?= escape_html((string) ($client['sex'] ?? '')) ?></span></div>
                  <div><span>Age</span><span>:</span><span><?= escape_html((string) ($client['age'] ?? '')) ?></span></div>
                  <div><span>Address</span><span>:</span><span><?= escape_html((string) ($client['address'] ?? '')) ?></span></div>
                  <div><span>B-Date</span><span>:</span><span><?= escape_html($birthDate) ?></span></div>
                  <div><span>B-Place</span><span>:</span><span><?= escape_html((string) ($client['birth_place'] ?? '')) ?></span></div>
                  <div><span>Educ. Attn.</span><span>:</span><span><?= escape_html((string) ($client['education'] ?? '')) ?></span></div>
                  <div><span>Religion</span><span>:</span><span><?= escape_html((string) ($client['religion'] ?? '')) ?></span></div>
                  <div><span>Status</span><span>:</span><span><?= escape_html((string) ($client['civil_status'] ?? '')) ?></span></div>
                  <div><span>Occupation</span><span>:</span><span><?= escape_html((string) ($client['occupation'] ?? '')) ?></span></div>
                  <div><span>Monthly income</span><span>:</span><span><?= escape_html((string) ($client['monthly_income'] ?? '')) ?></span></div>
                  <div><span>Contact No.</span><span>:</span><span><?= escape_html((string) ($client['contact_number'] ?? '')) ?></span></div>
                  <div><span>Clientele Category</span><span>:</span><span><?= escape_html($clienteleCategory) ?></span></div>
                </div>
              </section>

              <section class="section-block">
                <h2>II. Immediate Family</h2>
                <table class="family-table">
                  <thead>
                    <tr>
                      <th>Name</th>
                      <th>Age</th>
                      <th>Relationship</th>
                      <th>Educ. Attnt.</th>
                      <th>Occupation</th>
                      <th>Income</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php for ($index = 0, $rowCount = max(4, count($familyMembers)); $index < $rowCount; $index++): ?>
                      <?php $member = $familyMembers[$index] ?? []; ?>
                      <tr>
                        <td><?= escape_html((string) ($member['member_name'] ?? '')) ?></td>
                        <td><?= escape_html((string) ($member['member_age'] ?? '')) ?></td>
                        <td><?= escape_html((string) ($member['relationship_to_client'] ?? '')) ?></td>
                        <td><?= escape_html((string) ($member['education_attainment'] ?? '')) ?></td>
                        <td><?= escape_html((string) ($member['occupation'] ?? '')) ?></td>
                        <td><?= escape_html((string) ($member['income'] ?? '')) ?></td>
                      </tr>
                    <?php endfor; ?>
                  </tbody>
                </table>
              </section>

              <section class="section-block">
                <h2>III. Presenting Problem:</h2>
                <div class="text-area"><?= nl2br(escape_html($presentProblem)) ?></div>
              </section>

              <section class="section-block">
                <h2>IV. Evaluation and Recommendation</h2>
                <div class="text-area"><?= nl2br(escape_html($recommendation)) ?></div>
              </section>

              <section class="signature-block">
                <p>Prepared by:</p>
                <p id="preparedByName">Name and signature of registered social worker</p>
                <p id="preparedByPosition">Position</p>
                <p id="preparedByLicense">License Number and Validity</p>
                <br>
                <p>Noted by:</p>
                <p><strong>GOLDA V. VERGARA-POCON, RSW, MSSW, CESE</strong></p>
                <p>CGDH-II/CSWDO</p>
              </section>
              <div class="modal-overlay" id="preparedByModal" aria-hidden="true">
                <div class="modal" role="dialog" aria-modal="true" aria-labelledby="preparedByModalTitle">
                  <h3 id="preparedByModalTitle">Edit Prepared By</h3>
                  <form id="preparedByForm">
                    <label class="modal-field">Name and Signature
                      <input type="text" id="preparedByNameInput" />
                    </label>
                    <label class="modal-field">Position
                      <input type="text" id="preparedByPositionInput" />
                    </label>
                    <label class="modal-field">License Number
                      <input type="text" id="preparedByLicenseNumberInput" />
                    </label>
                    <label class="modal-field">Validity
                      <input type="date" id="preparedByValidityInput" />
                    </label>
                    <div class="modal-actions">
                      <button type="button" id="cancelPreparedByButton" class="print-btn print-btn--secondary">Cancel</button>
                      <button type="submit" class="print-btn">Save</button>
                    </div>
                  </form>
                </div>
              </div>

              <section class="privacy-consent privacy-consent--page-two">
                <div class="privacy-title">Data Privacy Consent</div>
                <p>On behalf of all the members of my household, I confirm that the information I have provided in this form is true and represents accurate information of our household.</p>
                <p>I understand that the data collected from this report will be processed, managed and maintained in a secure database by the City Social Welfare and Development (CSWD). Such data will be used to determine poverty status, serve as basis for research, and in the development and implementation of Social Protection Programs and Services, to promote the interest of the poor.</p>
                <p>I authorize CSWD to manage the information, including personal data obtained from this assessment/case study and allow the processing and controlled disclosure or transfer of data to its development partners and other stakeholders in accordance with the CSWD policies and Data Sharing and the provisions of Republic Act No. 10173 or the Data Policy Act (DPA) of 2012.</p>
                <div class="consent-signature">(NAME AND SIGNATURE OF CLIENT)</div>
              </section>

              <section class="time-row">
                <div>Time Intake <span>:</span> <?= escape_html(format_time_value((string) ($client['created_at'] ?? ''))) ?></div>
                <div>Time Released <span>:</span></div>
              </section>
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
      </tfoot>
    </table>
  </main>

  <script src="print-scsr.js"></script>
</body>
</html>
