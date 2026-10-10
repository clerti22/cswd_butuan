<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../auth/auth.php';
require_once __DIR__ . '/../../../includes/navigation.php';
$isEncoderArea = strpos(__DIR__, DIRECTORY_SEPARATOR . 'encoders' . DIRECTORY_SEPARATOR) !== false;
if ($isEncoderArea) {
    require_once __DIR__ . '/../../shared/form_access.php';
    require_encoder_program_access('PWD-2');
} else {
    require_login(['super admin', 'admin']);
}

require_once __DIR__ . '/../../../../auth/db.php';
$connection = get_database_connection();
$nextPwdNumber = '';
if (!isset($_GET['id'])) {
    $countQuery = $connection->query('SELECT COUNT(*) AS c FROM pwd_table2');
    $count = (int) ($countQuery->fetch_assoc()['c'] ?? 0);
    $nextPwdNumber = sprintf('16-0202-000-%04d', $count + 1);
}

$sidebar = $isEncoderArea ? render_encoder_sidebar('clients', '../../..') : render_super_admin_sidebar('clients', '../../..');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCIMS - PWD-2 Application Form</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="pwd2.css">
</head>
<body>
    <div class="overlay" id="overlay"></div>
    <div class="container">
        <?php echo $sidebar; ?>
        <div class="main-content">
            <header class="topbar">
                <button class="menu-btn" id="openSidebar" aria-label="Open sidebar"><span></span><span></span><span></span></button>
                <h1 class="topbar-title">Client Management/PWD-2</h1>
            </header>
            <main class="form-page">
                <form id="pwd2Form" class="pwd2-card" method="POST" novalidate>
                    <input type="hidden" name="record_id" id="recordId">
                    <header class="form-heading">
                        <img src="../../../images-logo/butuanseal.png" alt="Butuan City Seal">
                        <div><strong>Department of Health</strong><p>Philippine Registry for Persons with Disabilities Version 4.0</p><h2>Application Form for PWD</h2></div>
                        <img src="../../../images-logo/cswd-logo.png" alt="CSWD Logo">
                    </header>
                    <div class="top-row"><div class="radio-row"><label><input type="radio" name="application_type" value="New Applicant"> New Applicant</label><label><input type="radio" name="application_type" value="Renewal"> Renewal</label></div><label class="field date-issued">Date Issued:<input type="date" name="date_issued"></label></div>
                    <section><h3><i class="bi bi-person"></i> Identifying Information</h3><div class="grid four"><label>First Name:<input name="first_name" required></label><label>Middle Name:<input name="middle_name"></label><label>Last Name:<input name="last_name" required></label><label>Suffix:<input name="suffix"></label><label>Date of Birth:<input type="date" name="birth_date" required></label><label>Civil Status:<select name="civil_status"><option value=""></option><option>Single</option><option>Separated</option><option>Cohabitation (live-in)</option><option>Married</option><option>Widow/er</option></select></label><label>PWD Number(16-0202-000-):<input name="pwd_number" value="<?= htmlspecialchars($nextPwdNumber, ENT_QUOTES, 'UTF-8') ?>" readonly></label><label>Age:<input type="number" min="0" name="age" required></label><div style="display:flex; flex-direction:column; gap:5px;"><label>Gender:</label><div style="display:flex; gap:10px;"><label><input type="radio" name="sex" value="Male" required> Male</label><label><input type="radio" name="sex" value="Female" required> Female</label></div></div></div>
                    <div><h4>Blood Type:</h4><div class="check-list" data-name="blood_type" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:15px;"></div></div>
                    <div class="disability-grid"><div><h4>Types of Disabilities:</h4><div class="check-list" data-name="disability_types_primary"></div></div><div><h4>Types of Disabilities:</h4><div class="two-col"><div class="check-list" data-name="disability_causes_left"></div><div class="check-list" data-name="disability_causes_right"></div></div></div></div></section>
                    <section><h3><i class="bi bi-house-door"></i> Residence Address</h3><div class="grid three"><label>House No. & Street:<input name="house_no_street"></label><label>Barangay: <input list="barangay-list" name="barangay" id="barangay">
                                <datalist id="barangay-list">
                                    <option value="Maon">
                                    <option value="Golden Ribbon">
                                    <option value="Agao">
                                    <option value="Rajah Soliman">
                                    <option value="Silongan">
                                    <option value="Diego Silang">
                                    <option value="Dagohoy">
                                    <option value="Lapu-lapu">
                                    <option value="Urduja">
                                    <option value="Sikatuna">
                                    <option value="Humabon">
                                    <option value="Tandang Sora">
                                    <option value="Leon Kilat">
                                    <option value="Limaha">
                                    <option value="San Ignacio">
                                    <option value="Ong Yiu">
                                    <option value="Fort Poyohon">
                                    <option value="Obrero">
                                    <option value="Buhangin">
                                    <option value="Baan Riverside">
                                    <option value="Mahogany">
                                    <option value="Bading">
                                    <option value="Holy Redeemer">
                                    <option value="Imadejas">
                                    <option value="Jose P. Rizal">
                                    <option value="New Society Village">
                                    <option value="Bayanihan">
                                    <option value="Agusan Pequeño">
                                    <option value="Ambago">
                                    <option value="Amparo">
                                    <option value="Ampayon">
                                    <option value="Anticala">
                                    <option value="Antongalon">
                                    <option value="Aupagan">
                                    <option value="Baan Km.3">
                                    <option value="Babag">
                                    <option value="Bancasi">
                                    <option value="Banza">
                                    <option value="Baobaoan">
                                    <option value="Basag">
                                    <option value="Bilay">
                                    <option value="Bitan-agan">
                                    <option value="Bit-os">
                                    <option value="Bobon">
                                    <option value="Bonbon">
                                    <option value="Bugsukan">
                                    <option value="Cabcabon">
                                    <option value="Camayahan">
                                    <option value="Dankias">
                                    <option value="De Oro">
                                    <option value="Don Francisco">
                                    <option value="Doongan">
                                    <option value="Dulag">
                                    <option value="Dumalagan">
                                    <option value="Florida">
                                    <option value="Kinamlutan">
                                    <option value="Lemon">
                                    <option value="Libertad">
                                    <option value="Los Angeles">
                                    <option value="Lumbocan">
                                    <option value="Maguinda">
                                    <option value="Mahay">
                                    <option value="Maibu">
                                    <option value="Mandamo">
                                    <option value="Manila de Bugabus">
                                    <option value="Manuel J. Santos">
                                    <option value="Masao">
                                    <option value="Maug">
                                    <option value="Nongnong">
                                    <option value="Pagatpatan">
                                    <option value="Pangabugan">
                                    <option value="Pianing">
                                    <option value="Pigdaulan">
                                    <option value="Pinamanculan">
                                    <option value="Salvacion">
                                    <option value="San Mateo">
                                    <option value="San Vicente">
                                    <option value="Santo Niño">
                                    <option value="Sumile">
                                    <option value="Sumilihon">
                                    <option value="Tagabaca">
                                    <option value="Taguibo">
                                    <option value="Taligaman">
                                    <option value="Tiniwisan">
                                    <option value="Tungao">
                                    <option value="Villa Kananga">
                                </datalist>
</label><label>City:<input name="city"></label><label class="wide">Province:<input name="province"></label><label class="wide">Region:<input name="region"></label></div></section>
                    <section><h3><i class="bi bi-person-square"></i> Contact Details</h3><div class="grid three"><label>Landline No.:<input name="landline_no"></label><label>Mobile No.:<input name="mobile_no"></label><label>Email Address:<input type="email" name="email_address"></label></div></section>
                    <section class="split"><div><h3><i class="bi bi-mortarboard"></i> Educational Attainment:</h3><div class="radio-list two" data-name="educational_attainment"></div></div><div><h3><i class="bi bi-briefcase"></i> Status of Employment:</h3><div class="radio-list" data-name="status_of_employment"></div></div><div><h3><i class="bi bi-briefcase"></i> Category of Employment:</h3><div class="radio-list" data-name="category_of_employment"></div></div><div><h3><i class="bi bi-briefcase"></i> Types of Employment:</h3><div class="radio-list two" data-name="types_of_employment"></div></div></section>
                    <section><h3><i class="bi bi-briefcase"></i> Occupation:</h3><div class="radio-list occupation two" data-name="occupation"></div><div class="grid two"><label>Others Specify:<input name="occupation_others"></label><label>Skills:<input name="skills"></label></div></section>
                    <section><h3><i class="bi bi-building"></i> Organization Information:</h3><div class="grid two"><label>Organization Affiliated:<input name="organization_affiliated"></label><label>Contact Person:<input name="organization_contact_person"></label><label>Office Address:<input name="office_address"></label><label>Telephone Nos.:<input name="telephone_nos"></label></div></section>
                    <section><h3><i class="bi bi-card-text"></i> ID Reference No.:</h3><div class="grid three"><label>SSS No.:<input name="sss_no"></label><label>GSIS No.:<input name="gsis_no"></label><label>PhilHealth No.:<input name="philhealth_no"></label><label class="wide">PAG-IBIG No.:<input name="pagibig_no"></label><label class="wide">PSN No.:<input name="psn_no"></label></div></section>
                    <section><h3><i class="bi bi-people"></i> Family Background:</h3><div class="grid three"><label>Last Name(Father):<input name="father_last_name"></label><label>First Name(Father):<input name="father_first_name"></label><label>Middle Name(Father):<input name="father_middle_name"></label><label>Last Name(Mother):<input name="mother_last_name"></label><label>First Name(Mother):<input name="mother_first_name"></label><label>Middle Name(Mother):<input name="mother_middle_name"></label><label class="span-2">Guardian's Name/Contact Person:<input name="guardian_name_contact_person"></label><label>Contact No.:<input name="guardian_contact_no"></label></div></section>
                    <section><h3><i class="bi bi-award"></i> Accomplished by:</h3><div class="radio-row"><label><input type="radio" name="accomplished_by" value="Applicant"> Applicant</label><label><input type="radio" name="accomplished_by" value="Guardian"> Guardian</label><label><input type="radio" name="accomplished_by" value="Representative"> Representative</label></div><div class="grid three"><label>Last Name:<input name="accomplished_last_name"></label><label>First Name:<input name="accomplished_first_name"></label><label>Middle Name:<input name="accomplished_middle_name"></label></div></section>
                    <section><h3><i class="bi bi-diamond"></i> Others:</h3><div class="grid four"><label class="span-3">Name of Certifying Physician:<input name="certifying_physician_name"></label><label>License No.:<input name="physician_license_no"></label><label class="full">Processing Officer:<input name="processing_officer"></label><label class="full">Approving Officer:<input name="approving_officer"></label><label class="full">Encoder:<input name="encoder"></label><label class="full">Name of Reporting Unit(Office/Section):<input name="reporting_unit"></label><label class="full">Control No.:<input name="control_no"></label></div></section>
                    <div class="actions"><button type="submit">Submit</button><a id="printLink" class="print-link" hidden>Print</a></div>
                </form>
            </main>
        </div>
    </div>
    <script src="pwd2.js"></script>
</body>
</html>
