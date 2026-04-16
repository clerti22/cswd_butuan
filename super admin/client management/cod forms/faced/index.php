<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../auth/auth.php';
require_once __DIR__ . '/../../../includes/navigation.php';
require_login(['super admin', 'admin']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCIMS - FACED Form</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/umd/lucide.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="faced.css">
</head>
<body>
<div class="overlay" id="overlay"></div>
<div class="container">
    <?php echo render_super_admin_sidebar('clients', '../../..'); ?>
    <div class="main-content">
        <header class="topbar">
            <button class="menu-btn" id="openSidebar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
            <h3 class="topbar-title">Clients Management</h3>
        </header>
        <main class="form-page">
            <div class="alert-box" id="alertBox" hidden></div>
            <div class="form-sheet">
                <header class="form-header">
                    <img class="form-header__logo" src="../../../images-logo/cswd-logo.png" alt="CSWD logo">
                    <div class="form-header__text">
                        <p>Republic of the Philippines</p>
                        <p class="bold">CITY GOVERNMENT OF BUTUAN</p>
                        <p class="bold">City Social Welfare and Development Department</p>
                        <p>City Hall Building, J.P. Rosales Ave., Doongan, Butuan City</p>
                    </div>
                    <img class="form-header__logo" src="../../../images-logo/butuanseal.png" alt="Butuan City logo">
                </header>

                <section class="form-section">
                    <h2 class="section-title"><i class="bi bi-geo-alt" aria-hidden="true"></i> Location of the affected family</h2>
                    <div class="form-grid three">
                        <label>Region<input id="region" type="text"></label>
                        <label>Province<input id="province" type="text"></label>
                        <label>District<input id="district" type="text"></label>
                        <label>City/Municipality<input id="city" type="text"></label>
                        <label>Barangay<input id="barangay" type="text"></label>
                        <label>Evacuation Center/Evacuation Site<input id="evacuation_site" type="text"></label>
                    </div>
                </section>

                <section class="form-section">
                    <h2 class="section-title"><i class="bi bi-people" aria-hidden="true"></i> Head of the Family</h2>
                    <div class="form-grid four">
                        <label>First Name<input id="first_name" type="text"></label>
                        <label>Middle Name<input id="middle_name" type="text"></label>
                        <label>Last Name<input id="last_name" type="text"></label>
                        <label>Suffix<input id="suffix" type="text"></label>

                        <label>Birth Date<input id="birth_date" type="date"></label>
                        <label class="span-2">Birth Place<input id="birth_place" type="text"></label>
                        <label>Age<input id="age" type="number" min="0"></label>

                        <fieldset><legend>Sex</legend><label><input type="radio" name="sex" value="Male"> Male</label><label><input type="radio" name="sex" value="Female"> Female</label></fieldset>
                        <label>Civil Status<select id="civil_status"><option value=""></option><option>Single</option><option>Married</option><option>Widowed</option><option>Separated</option></select></label>
                        <label class="span-2">Contact Number<input id="contact_number" type="text" maxlength="11"></label>

                        <label>Mother's Maiden Name<input id="mother_maiden_name" type="text"></label>
                        <label>Religion<input id="religion" type="text"></label>
                        <label class="span-2">Occupation<input id="occupation" type="text"></label>

                        <label>Monthly Family Net Income<input id="monthly_income" type="text"></label>
                        <label>ID Card Presented<input id="id_card_presented" type="text"></label>
                        <label class="span-2">ID Card Number<input id="id_card_number" type="text"></label>

                        <fieldset><legend>4P's Beneficiary(?):</legend><label><input type="radio" name="is_4ps" value="Yes"> Yes</label><label><input type="radio" name="is_4ps" value="No"> No</label></fieldset>
                        <fieldset><legend>IP(?):</legend><label><input type="radio" name="is_ip" value="Yes"> Yes</label><label><input type="radio" name="is_ip" value="No"> No</label></fieldset>
                        <label class="span-2">Type of Ethnicity<input id="ethnicity_type" type="text"></label>
                        <label class="span-4">Full Address<input id="full_address" type="text"></label>
                    </div>
                </section>

                <section class="form-section">
                    <div class="family-head">
                        <h2 class="section-title"><i class="bi bi-people" aria-hidden="true"></i> Family Information</h2>
                        <button id="openFamilyModal" class="add-btn" type="button">+ Add</button>
                    </div>
                    <div class="family-table-wrap">
                        <table class="family-table">
                            <thead><tr><th>Family Member</th><th>Relation to family head</th><th>Birthdate</th><th>Age</th><th>Sex</th><th>Highest Educational Attainment</th><th>Occupation</th><th>Remarks</th></tr></thead>
                            <tbody id="familyTableBody"><tr><td colspan="8"></td></tr></tbody>
                        </table>
                    </div>

                    <div class="summary-grid">
                        <label>No. of Vulnerable Family Members<input id="vulnerable_count" type="number" min="0"></label>
                        <label>No. of Older Persons<input id="older_persons" type="number" min="0"></label>
                        <label>No. of Pregnant Women<input id="pregnant_women" type="number" min="0"></label>
                        <label>No. of Lactating Women<input id="lactating_women" type="number" min="0"></label>
                        <label>No. of PWDs due to Medical Condition/s<input id="pwds_medical" type="number" min="0"></label>
                    </div>

                    <div class="choice-grid">
                        <fieldset><legend>House Ownership</legend><label><input type="radio" name="house_ownership" value="Owner"> Owner</label><label><input type="radio" name="house_ownership" value="Renter"> Renter</label><label><input type="radio" name="house_ownership" value="Sharer"> Sharer</label></fieldset>
                        <fieldset><legend>Shelter Damage Classification</legend><label><input type="radio" name="shelter_damage" value="Partially Damaged"> Partially Damaged</label><label><input type="radio" name="shelter_damage" value="Totally Damaged"> Totally Damaged</label></fieldset>
                    </div>

                    <div class="form-actions"><button class="form-submit-btn" id="submitBtn" type="button">Submit</button></div>
                </section>
            </div>
        </main>
    </div>
</div>

<div class="modal" id="familyModal" aria-hidden="true">
    <div class="modal-card">
        <div class="modal-header"><h3>Family Information</h3><button id="closeFamilyModal" type="button">&times;</button></div>
        <div class="modal-alert" id="modalAlert" hidden></div>
        <form id="familyForm" class="form-grid two">
            <label>Family Member<input name="family_member" type="text"></label>
            <label>Relation to family head<input name="relation" type="text"></label>
            <label>Birthdate<input name="birthdate" type="date"></label>
            <label>Age<input name="age" type="number" min="0"></label>
            <label>Sex<select name="sex"><option value=""></option><option>Male</option><option>Female</option></select></label>
            <label>Highest Educational Attainment<input name="education" type="text"></label>
            <label>Occupation<input name="occupation" type="text"></label>
            <label>Remarks<input name="remarks" type="text"></label>
            <div class="form-actions"><button class="form-submit-btn" type="submit">Save</button></div>
        </form>
    </div>
</div>

<script src="../../shared/form-submit-confirm.js"></script>
<script src="../../shared/client-record-mode.js"></script>
<script src="faced.js"></script>
</body>
</html>
