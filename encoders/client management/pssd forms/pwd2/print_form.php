<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../auth/auth.php';
require_once __DIR__ . '/../../../../auth/db.php';
$isEncoderArea = strpos(__DIR__, DIRECTORY_SEPARATOR . 'encoders' . DIRECTORY_SEPARATOR) !== false;
if ($isEncoderArea) {
    require_once __DIR__ . '/../../shared/form_access.php';
    require_encoder_program_access('PWD-2');
} else {
    require_login(['super admin', 'admin']);
}
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$id) { http_response_code(422); exit('Invalid record id.'); }
$conn = get_database_connection();
$stmt = $conn->prepare('SELECT * FROM pwd_table2 WHERE key_id = ? LIMIT 1');
$stmt->bind_param('i', $id);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
if (!$record) { http_response_code(404); exit('PWD-2 record not found.'); }
function e($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8'); }

function circle(bool $checked): string {
    if ($checked) return '<svg width="8" height="8" viewBox="0 0 10 10"><circle cx="5" cy="5" r="4" fill="#000" stroke="#000" stroke-width="1"/></svg>';
    return '<svg width="8" height="8" viewBox="0 0 10 10"><circle cx="5" cy="5" r="4" fill="none" stroke="#000" stroke-width="1"/></svg>';
}

function square(bool $checked): string {
    if ($checked) return '<svg width="8" height="8" viewBox="0 0 10 10"><rect x="1" y="1" width="8" height="8" fill="none" stroke="#000" stroke-width="1"/><path d="M 2 5 L 4 7 L 8 2" fill="none" stroke="#000" stroke-width="1.5"/></svg>';
    return '<svg width="8" height="8" viewBox="0 0 10 10"><rect x="1" y="1" width="8" height="8" fill="none" stroke="#000" stroke-width="1"/></svg>';
}

$hasDisability = function(string $type) use ($record): bool {
    $types = array_map('trim', explode(',', (string)$record['disability_types_primary']));
    return in_array($type, $types, true);
};

$hasCauseL = function(string $type) use ($record): bool {
    $types = array_map('trim', explode(',', (string)$record['disability_causes_left']));
    return in_array($type, $types, true);
};

$hasCauseR = function(string $type) use ($record): bool {
    $types = array_map('trim', explode(',', (string)$record['disability_causes_right']));
    return in_array($type, $types, true);
};

$hasBlood = function(string $type) use ($record): bool {
    $types = array_map('trim', explode(',', (string)$record['blood_type']));
    return in_array($type, $types, true);
};

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>PWD Registration Form</title>
<style>
@page { size: A4 portrait; margin: 0; }
body { margin: 0; font-family: Arial, sans-serif; font-size: 8px; line-height: 1.1; background: #fff; color: #000; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
.page { width: 210mm; height: 297mm; padding: 5mm 8mm; margin: 0 auto; position: relative; box-sizing: border-box; }
.header-logos { display: flex; align-items: center; justify-content: flex-start; gap: 5px; margin-bottom: 5px; }
.logo-circle { width: 38px; height: 38px; border-radius: 50%; object-fit: contain; }
.header-text { margin-left: 10px; }
.header-text h1 { margin: 0; font-size: 13px; font-weight: bold; }
.header-text h2 { margin: 2px 0; font-size: 10px; font-weight: bold; }
.header-text h3 { margin: 2px 0 0 0; font-size: 12px; font-weight: bold; }

.form-box { border: 2px solid #000; width: 100%; display: flex; flex-direction: column; box-sizing: border-box; }
.r { display: flex; width: 100%; border-bottom: 1px solid #000; box-sizing: border-box; }
.r:last-child { border-bottom: none; }
.r.no-bb { border-bottom: none; }
.c { border-right: 1px solid #000; padding: 2px 4px; box-sizing: border-box; display: flex; flex-direction: column; }
.c:last-child { border-right: none; }
.c.no-br { border-right: none; }

.bold { font-weight: bold; }
.val { font-weight: normal; margin-top: 2px; font-size: 9px; min-height: 10px; }
.inline-val { font-weight: normal; margin-left: 4px; font-size: 9px; }
.title { font-weight: bold; font-size: 8px; }

.check-item { display: inline-flex; align-items: center; gap: 3px; margin-right: 5px; margin-bottom: 2px; }

.bottom-section { display: flex; justify-content: space-between; margin-top: 5px; }
.req-box { border: 1.5px solid #000; padding: 4px 6px; width: 55%; font-size: 7px; }
.req-box h4 { margin: 0 0 3px 0; font-size: 8px; text-decoration: underline; }
.req-box ol { margin: 0; padding-left: 12px; }
.req-box li { margin-bottom: 2px; }
.footer-logo-area { width: 40%; display: flex; flex-direction: column; justify-content: flex-end; align-items: flex-start; }
.footer-logo { width: 120px; object-fit: contain; }

/* Utility width classes */
.w-10 { width: 10%; } .w-15 { width: 15%; } .w-20 { width: 20%; } .w-25 { width: 25%; } .w-30 { width: 30%; } .w-33 { width: 33.33%; } .w-35 { width: 35%; } .w-40 { width: 40%; } .w-50 { width: 50%; } .w-60 { width: 60%; } .w-75 { width: 75%; } .w-100 { width: 100%; }
</style>
</head>
<body>
<div class="page">
    <div class="header-logos">
        <img src="../../../images-logo/butuanseal.png" class="logo-circle" alt="">
        <img src="../../../images-logo/cswd-logo.png" class="logo-circle" alt="">
        <!-- Add placeholders or empty circles if needed for the other 2 logos -->
        <div class="header-text">
            <h1>DEPARTMENT OF HEALTH</h1>
            <h2>Philippine Registry For Persons with Disability Version 4.0</h2>
            <h3>Application Form</h3>
        </div>
    </div>

    <div class="form-box">
        <div class="r">
            <div class="w-85" style="display:flex; flex-direction:column; width: 85%;">
                <div class="r">
                    <div class="c w-60 bold" style="display:flex; flex-direction:row; align-items:center; gap: 10px;">
                        1. 
                        <div class="check-item"><?= circle($record['application_type'] === 'New Applicant') ?> NEW APPLICANT</div>
                        <div class="check-item"><?= circle($record['application_type'] === 'Renewal') ?> RENEWAL</div>
                    </div>
                    <div class="c w-40 bold">
                        1.1. DATE ISSUED: <span class="val"><?= e($record['date_issued']) ?></span>
                    </div>
                </div>
                <div class="r">
                    <div class="c w-60 bold">
                        2. PERSONS WITH DISABILITY NUMBER <span style="font-size:6px; font-weight:normal;">(RR-PPMM-BBB-NNNNNNN)*</span><br>
                        <span class="val" style="font-size:12px; letter-spacing:1px;"><?= e($record['pwd_number']) ?></span>
                    </div>
                    <div class="c w-40 bold">
                        3. DATE APPLIED * <span style="font-size:6px; font-weight:normal;">(mm/dd/yyyy)</span><br>
                        <span class="val"><?= e(substr($record['created_at'], 0, 10)) ?></span>
                    </div>
                </div>
                <div class="r no-bb">
                    <div class="c w-100 bold">4. PERSONAL INFORMATION*</div>
                </div>
            </div>
            <div class="c w-15" style="width:15%; justify-content:center; align-items:center; text-align:center;">
                place 1"x1"<br>Photo here
            </div>
        </div>

        <div class="r" style="text-align:center;">
            <div class="c w-25 bold">LAST NAME<br><span class="val"><?= e($record['last_name']) ?></span></div>
            <div class="c w-35 bold">FIRST NAME<br><span class="val"><?= e($record['first_name']) ?></span></div>
            <div class="c w-25 bold">MIDDLE NAME<br><span class="val"><?= e($record['middle_name']) ?></span></div>
            <div class="c w-15 bold">SUFFIX<br><span class="val"><?= e($record['suffix']) ?></span></div>
        </div>

        <div class="r">
            <div class="c w-30 bold">
                5. DATE OF BIRTH:* <span style="font-size:6px; font-weight:normal;">(mm/dd/yyyy)</span>
                <span class="inline-val"><?= e($record['birth_date']) ?></span>
            </div>
            <div class="c w-15 bold">
                AGE:* <span class="inline-val"><?= e($record['age']) ?></span>
            </div>
            <div class="c w-25 bold" style="display:flex; flex-direction:row; align-items:center; gap: 5px;">
                Gender:* 
                <div class="check-item"><?= circle($record['sex'] === 'Male') ?> MALE</div>
                <div class="check-item"><?= circle($record['sex'] === 'Female') ?> FEMALE</div>
            </div>
            <div class="c w-30 bold">
                BLOOD TYPE:*<br>
                <div style="display:flex; flex-wrap:wrap; gap:3px; font-size:7px;">
                    <div class="check-item"><?= square($hasBlood('A+')) ?> A+</div>
                    <div class="check-item"><?= square($hasBlood('B+')) ?> B+</div>
                    <div class="check-item"><?= square($hasBlood('AB+')) ?> AB+</div>
                    <div class="check-item"><?= square($hasBlood('O+')) ?> O+</div>
                    <div class="check-item"><?= square($hasBlood('A-')) ?> A-</div>
                    <div class="check-item"><?= square($hasBlood('B-')) ?> B-</div>
                    <div class="check-item"><?= square($hasBlood('AB-')) ?> AB-</div>
                    <div class="check-item"><?= square($hasBlood('O-')) ?> O-</div>
                </div>
            </div>
        </div>

        <div class="r">
            <div class="c w-100 bold" style="display:flex; align-items:center; gap: 15px;">
                7. CIVIL STATUS:*
                <div class="check-item"><?= circle($record['civil_status'] === 'Single') ?> Single</div>
                <div class="check-item"><?= circle($record['civil_status'] === 'Separated') ?> Separated</div>
                <div class="check-item"><?= circle($record['civil_status'] === 'Cohabitation (live-in)') ?> Cohabitation (live-in)</div>
                <div class="check-item"><?= circle($record['civil_status'] === 'Married') ?> Married</div>
                <div class="check-item"><?= circle($record['civil_status'] === 'Widow/er') ?> Widow/er</div>
            </div>
        </div>

        <div class="r">
            <div class="c w-50">
                <div class="bold mb-2">8. TYPE OF DISABILITY:*</div>
                <div style="display:flex;">
                    <div style="width:50%; display:flex; flex-direction:column; gap:2px;">
                        <div class="check-item"><?= square($hasDisability('Deaf or Hard of Hearing')) ?> Deaf or Hard of Hearing</div>
                        <div class="check-item"><?= square($hasDisability('Intellectual Disability')) ?> Intellectual Disability</div>
                        <div class="check-item"><?= square($hasDisability('Learning Disability')) ?> Learning Disability</div>
                        <div class="check-item"><?= square($hasDisability('Mental/Psychosocial Disability')) ?> Mental/Psychosocial Disability</div>
                        <div class="check-item"><?= square($hasDisability('Orthopedic/Physical Disability')) ?> Orthopedic/Physical Disability</div>
                    </div>
                    <div style="width:50%; display:flex; flex-direction:column; gap:2px;">
                        <div class="check-item"><?= square($hasDisability('Multiple Disabilities')) ?> Multiple Disabilities</div>
                        <div class="check-item"><?= square($hasDisability('Speech and Language Impairment')) ?> Speech and Language Impairment</div>
                        <div class="check-item"><?= square($hasDisability('Visual Disability')) ?> Visual Disability</div>
                        <div class="check-item"><?= square($hasDisability('Cancer (RA11215)')) ?> Cancer (RA11215)</div>
                        <div class="check-item"><?= square($hasDisability('Rare Disease')) ?> Rare Disease</div>
                    </div>
                </div>
            </div>
            <div class="c w-50">
                <div class="bold mb-2">9. CAUSE OF DISABILITY:*</div>
                <div style="display:flex;">
                    <div style="width:50%; display:flex; flex-direction:column; gap:2px;">
                        <div class="check-item bold"><?= square(count(array_filter(explode(',', $record['disability_causes_left']))) > 0) ?> Congenital / Inborn</div>
                        <div style="padding-left: 10px; display:flex; flex-direction:column; gap:2px;">
                            <div class="check-item"><?= square($hasCauseL('ADHD')) ?> ADHD</div>
                            <div class="check-item"><?= square($hasCauseL('Cerebral Palsy')) ?> Cerebral Palsy</div>
                            <div class="check-item"><?= square($hasCauseL('Down Syndrome')) ?> Down Syndrome</div>
                        </div>
                    </div>
                    <div style="width:50%; display:flex; flex-direction:column; gap:2px;">
                        <div class="check-item bold"><?= square(count(array_filter(explode(',', $record['disability_causes_right']))) > 0) ?> Acquired</div>
                        <div style="padding-left: 10px; display:flex; flex-direction:column; gap:2px;">
                            <div class="check-item"><?= square($hasCauseR('Chronic Illness')) ?> Chronic Illness</div>
                            <div class="check-item"><?= square($hasCauseR('Cerebral Palsy')) ?> Cerebral Palsy</div>
                            <div class="check-item"><?= square($hasCauseR('Injury')) ?> Injury</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="r no-bb"><div class="c w-100 bold">10. RESIDENCE ADDRESS:*</div></div>
        <div class="r">
            <div class="c w-30 title">House No. & Street:*<br><span class="val"><?= e($record['house_no_street']) ?></span></div>
            <div class="c w-20 title">Barangay:*<br><span class="val"><?= e($record['barangay']) ?></span></div>
            <div class="c w-20 title">City:*<br><span class="val"><?= e($record['city']) ?></span></div>
            <div class="c w-15 title">Province:*<br><span class="val"><?= e($record['province']) ?></span></div>
            <div class="c w-15 title">Region:*<br><span class="val"><?= e($record['region']) ?></span></div>
        </div>

        <div class="r no-bb"><div class="c w-100 bold">11. CONTACT DETAILS:*</div></div>
        <div class="r">
            <div class="c w-33 title">Landline No.:<br><span class="val"><?= e($record['landline_no']) ?></span></div>
            <div class="c w-33 title">Mobile No.:<br><span class="val"><?= e($record['mobile_no']) ?></span></div>
            <div class="c w-33 title">E-mail Address:<br><span class="val"><?= e($record['email_address']) ?></span></div>
        </div>

        <!-- Row 12 complex grid -->
        <div class="r">
            <!-- Col 1 -->
            <div class="c w-35">
                <div class="bold mb-2">12. EDUCATIONAL ATTAINMENT:*</div>
                <div style="display:flex;">
                    <div style="width:50%; display:flex; flex-direction:column; gap:2px;">
                        <div class="check-item"><?= circle($record['educational_attainment'] === 'None') ?> None</div>
                        <div class="check-item"><?= circle($record['educational_attainment'] === 'Kindergarten') ?> Kindergarten</div>
                        <div class="check-item"><?= circle($record['educational_attainment'] === 'Elementary') ?> Elementary</div>
                        <div class="check-item"><?= circle($record['educational_attainment'] === 'Junior High School') ?> Junior High School</div>
                    </div>
                    <div style="width:50%; display:flex; flex-direction:column; gap:2px;">
                        <div class="check-item"><?= circle($record['educational_attainment'] === 'Senior High School') ?> Senior High School</div>
                        <div class="check-item"><?= circle($record['educational_attainment'] === 'College') ?> College</div>
                        <div class="check-item"><?= circle($record['educational_attainment'] === 'Vocational') ?> Vocational</div>
                        <div class="check-item"><?= circle($record['educational_attainment'] === 'Post Graduate') ?> Post Graduate</div>
                    </div>
                </div>
                <div style="border-top: 1px solid #000; margin: 2px -4px; padding: 2px 4px;">
                    <div class="bold mb-2">13. STATUS OF EMPLOYMENT:*</div>
                    <div style="display:flex; flex-direction:column; gap:2px;">
                        <div class="check-item"><?= circle($record['status_of_employment'] === 'Employed') ?> Employed</div>
                        <div class="check-item"><?= circle($record['status_of_employment'] === 'Unemployed') ?> Unemployed</div>
                        <div class="check-item"><?= circle($record['status_of_employment'] === 'Self-employed') ?> Self-employed</div>
                    </div>
                </div>
                <div style="border-top: 1px solid #000; margin: 2px -4px; padding: 2px 4px;">
                    <div class="bold mb-2">13 a. CATEGORY OF EMPLOYMENT:*</div>
                    <div style="display:flex; flex-direction:column; gap:2px;">
                        <div class="check-item"><?= circle($record['category_of_employment'] === 'Government') ?> Government</div>
                        <div class="check-item"><?= circle($record['category_of_employment'] === 'Private') ?> Private</div>
                    </div>
                </div>
            </div>
            
            <!-- Col 2 -->
            <div class="c w-25">
                <div class="bold mb-2">13 b. TYPES OF EMPLOYMENT:*</div>
                <div style="display:flex; flex-direction:column; gap:2px;">
                    <div class="check-item"><?= circle($record['types_of_employment'] === 'Permanent/Regular') ?> Permanent/Regular</div>
                    <div class="check-item"><?= circle($record['types_of_employment'] === 'Seasonal') ?> Seasonal</div>
                    <div class="check-item"><?= circle($record['types_of_employment'] === 'Casual') ?> Casual</div>
                    <div class="check-item"><?= circle($record['types_of_employment'] === 'Emergency') ?> Emergency</div>
                </div>
            </div>

            <!-- Col 3 -->
            <div class="c w-40">
                <div class="bold mb-2">14. OCCUPATION:*</div>
                <div style="display:flex; flex-direction:column; gap:2px;">
                    <div class="check-item"><?= circle($record['occupation'] === 'Managers') ?> Managers</div>
                    <div class="check-item"><?= circle($record['occupation'] === 'Professionals') ?> Professionals</div>
                    <div class="check-item"><?= circle($record['occupation'] === 'Technicians and Associate Professionals') ?> Technicians and Associate Professionals</div>
                    <div class="check-item"><?= circle($record['occupation'] === 'Clerical Support Workers') ?> Clerical Support Workers</div>
                    <div class="check-item"><?= circle($record['occupation'] === 'Service and Sales Workers') ?> Service and Sales Workers</div>
                    <div class="check-item"><?= circle($record['occupation'] === 'Skilled Agricultural, Forestry and Fishery Workers') ?> Skilled Agricultural, Forestry and Fishery Workers</div>
                    <div class="check-item"><?= circle($record['occupation'] === 'Craft and related Trade Workers') ?> Craft and related Trade Workers</div>
                    <div class="check-item"><?= circle($record['occupation'] === 'Plant and Machine Operators and Assemblers') ?> Plant and Machine Operators and Assemblers</div>
                    <div class="check-item"><?= circle($record['occupation'] === 'Elementary Occupations') ?> Elementary Occupations</div>
                    <div class="check-item"><?= circle($record['occupation'] === 'Armed Forces Occupations') ?> Armed Forces Occupations</div>
                    <div class="check-item"><?= circle($record['occupation'] === 'Others') ?> Others, Specify: <span class="val" style="display:inline; border-bottom:1px solid #000; padding:0 10px;"><?= e($record['occupation_others']) ?></span></div>
                </div>
            </div>
        </div>

        <div class="r">
            <div class="c w-100 bold">SKILLS: <span class="inline-val"><?= e($record['skills']) ?></span></div>
        </div>

        <div class="r no-bb"><div class="c w-100 bold">15. ORGANIZATION INFORMATION:</div></div>
        <div class="r">
            <div class="c w-25 title">Organization Affiliated:<br><span class="val"><?= e($record['organization_affiliated']) ?></span></div>
            <div class="c w-25 title">Contact Person:<br><span class="val"><?= e($record['organization_contact_person']) ?></span></div>
            <div class="c w-25 title">Office Address:<br><span class="val"><?= e($record['office_address']) ?></span></div>
            <div class="c w-25 title">Telephone Nos.:<br><span class="val"><?= e($record['telephone_nos']) ?></span></div>
        </div>

        <div class="r no-bb"><div class="c w-100 bold">16. ID REFERENCE NO.:</div></div>
        <div class="r">
            <div class="c w-20 title">SSS No.:<br><span class="val"><?= e($record['sss_no']) ?></span></div>
            <div class="c w-20 title">GSIS No.:<br><span class="val"><?= e($record['gsis_no']) ?></span></div>
            <div class="c w-20 title">PAG-IBIG No.:<br><span class="val"><?= e($record['pagibig_no']) ?></span></div>
            <div class="c w-20 title">PSN No.:<br><span class="val"><?= e($record['psn_no']) ?></span></div>
            <div class="c w-20 title">PhilHealth No.:<br><span class="val"><?= e($record['philhealth_no']) ?></span></div>
        </div>

        <div class="r" style="text-align:center;">
            <div class="c w-25 bold" style="text-align:left;">17. FAMILY BACKGROUND:</div>
            <div class="c w-25 title">LAST NAME</div>
            <div class="c w-25 title">FIRST NAME</div>
            <div class="c w-25 title">MIDDLE NAME</div>
        </div>
        <div class="r" style="text-align:center;">
            <div class="c w-25 title" style="text-align:right;">FATHER'S NAME:</div>
            <div class="c w-25 val"><?= e($record['father_last_name']) ?></div>
            <div class="c w-25 val"><?= e($record['father_first_name']) ?></div>
            <div class="c w-25 val"><?= e($record['father_middle_name']) ?></div>
        </div>
        <div class="r" style="text-align:center;">
            <div class="c w-25 title" style="text-align:right;">MOTHER'S NAME:</div>
            <div class="c w-25 val"><?= e($record['mother_last_name']) ?></div>
            <div class="c w-25 val"><?= e($record['mother_first_name']) ?></div>
            <div class="c w-25 val"><?= e($record['mother_middle_name']) ?></div>
        </div>
        <div class="r">
            <div class="c w-75 title">GUARDIAN'S NAME/CONTACT PERSON:* <span class="inline-val"><?= e($record['guardian_name_contact_person']) ?></span></div>
            <div class="c w-25 title">CONTACT NO.:* <span class="inline-val"><?= e($record['guardian_contact_no']) ?></span></div>
        </div>

        <div class="r" style="text-align:center;">
            <div class="c w-25 bold" style="text-align:left;">18. ACCOMPLISHED BY:*</div>
            <div class="c w-25 title">LAST NAME</div>
            <div class="c w-25 title">FIRST NAME</div>
            <div class="c w-25 title">MIDDLE NAME</div>
        </div>
        <div class="r" style="text-align:center;">
            <div class="c w-25 title" style="text-align:left;"><div class="check-item"><?= circle($record['accomplished_by'] === 'Applicant') ?> APPLICANT</div></div>
            <div class="c w-25 val"><?= $record['accomplished_by'] === 'Applicant' ? e($record['accomplished_last_name']) : '' ?></div>
            <div class="c w-25 val"><?= $record['accomplished_by'] === 'Applicant' ? e($record['accomplished_first_name']) : '' ?></div>
            <div class="c w-25 val"><?= $record['accomplished_by'] === 'Applicant' ? e($record['accomplished_middle_name']) : '' ?></div>
        </div>
        <div class="r" style="text-align:center;">
            <div class="c w-25 title" style="text-align:left;"><div class="check-item"><?= circle($record['accomplished_by'] === 'Guardian') ?> GUARDIAN</div></div>
            <div class="c w-25 val"><?= $record['accomplished_by'] === 'Guardian' ? e($record['accomplished_last_name']) : '' ?></div>
            <div class="c w-25 val"><?= $record['accomplished_by'] === 'Guardian' ? e($record['accomplished_first_name']) : '' ?></div>
            <div class="c w-25 val"><?= $record['accomplished_by'] === 'Guardian' ? e($record['accomplished_middle_name']) : '' ?></div>
        </div>
        <div class="r" style="text-align:center;">
            <div class="c w-25 title" style="text-align:left;"><div class="check-item"><?= circle($record['accomplished_by'] === 'Representative') ?> REPRESENTATIVE</div></div>
            <div class="c w-25 val"><?= $record['accomplished_by'] === 'Representative' ? e($record['accomplished_last_name']) : '' ?></div>
            <div class="c w-25 val"><?= $record['accomplished_by'] === 'Representative' ? e($record['accomplished_first_name']) : '' ?></div>
            <div class="c w-25 val"><?= $record['accomplished_by'] === 'Representative' ? e($record['accomplished_middle_name']) : '' ?></div>
        </div>

        <div class="r">
            <div class="c w-75 bold">19. NAME OF CERTIFYING PHYSICIAN: <span class="inline-val"><?= e($record['certifying_physician_name']) ?></span></div>
            <div class="c w-25 bold">LICENSE NO.: <span class="inline-val"><?= e($record['physician_license_no']) ?></span></div>
        </div>
        <div class="r"><div class="c w-100 bold">20. PROCESSING OFFICER:* <span class="inline-val"><?= e($record['processing_officer']) ?></span></div></div>
        <div class="r"><div class="c w-100 bold">21. APPROVING OFFICER:* <span class="inline-val"><?= e($record['approving_officer']) ?></span></div></div>
        <div class="r"><div class="c w-100 bold">22. ENCODER:* <span class="inline-val"><?= e($record['encoder']) ?></span></div></div>
        <div class="r"><div class="c w-100 bold">23. NAME OF REPORTING UNIT (OFFICE/SECTION):* <span class="inline-val"><?= e($record['reporting_unit']) ?></span></div></div>
        <div class="r"><div class="c w-100 bold">24. CONTROL NO.:* <span class="inline-val"><?= e($record['control_number']) ?></span></div></div>
    </div>

    <div class="bottom-section">
        <div class="footer-logo-area">
            <!-- Add placeholder for Butuan ON logo -->
            <h1 style="color:#0055a4; font-style:italic; font-size:24px; margin:0; font-family:Arial Black, sans-serif;">BUTUAN<span style="color:#8cc63f;">ON</span></h1>
            <p style="font-size: 7px; margin: 0; font-style: italic;">A City Ascending!</p>
        </div>
        
        <div class="req-box">
            <h4>New/Renewal of PWD-IDC REQUIREMENTS :</h4>
            <ol>
                <li>Duly filled-up PWD-RFs (form)</li>
                <li>Certificate of Disability</li>
                <li>Barangay Certification</li>
                <li>Recent ID picture (2pcs 1"x1" for IDC & 2pcs. 2"x2" for booklet)</li>
                <li>Photocopy of Birth/Marriage Certificate or 1 valid ID</li>
                <li>Authorization Letter</li>
            </ol>
        </div>
    </div>
    <div style="text-align: right; font-size: 7px; font-weight: bold; margin-top: 2px;">
        CSWD.F.051.REV01
    </div>
</div>
<script>
window.onload = function() {
    window.print();
};
</script>
</body>
</html>
