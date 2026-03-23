<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../auth/auth.php';
require_once __DIR__ . '/../../../includes/navigation.php';
require_login(['super admin']);


?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCIMS - PYAP Form</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/umd/lucide.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="pyap.css">
</head>

<body>
    <div class="overlay" id="overlay"></div>

    <div class="container">
        <!-- Sidebar -->
        <?php echo render_super_admin_sidebar('clients', '../../..'); ?>

        <!-- Main Content -->
        <div class="main-content">
            <header class="topbar">
                <button class="menu-btn" id="openSidebar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <h3 class="topbar-title" style="font-weight: bold;">Clients Management</h3>
            </header>

            <main class="form-page">
                <header class="form-header">
                    <img class="seal" src="../../../images-logo/butuanseal.png" alt="City of Butuan Seal">
                    <div class="header-text">
                        <p>Republic of the Philippines</p>
                        <p class="header-strong">CITY GOVERNMENT OF BUTUAN</p>
                        <p class="header-strong">City Social Welfare and Development Department</p>
                        <p>City Hall Building, J.P. Rosales Ave., Doongan, Butuan City</p>
                    </div>
                    <img class="seal" src="../../../images-logo/cswd-logo.png" alt="CSWD Seal">
                </header>

                <section class="title-block">
                    <h1>PAG-ASA YOUTH ASSOCIATION OF THE PHILIPPINES</h1>
                    <h2>MEMBERSHIP INFORMATION SHEET</h2>
                </section>

                <form id="pyapForm" method="POST" action="pyap_submit.php">
                    <section class="form-section">
                        <div class="section-title">
                            <span class="title-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path
                                        d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Zm0 2c-4.42 0-8 2-8 4.5V20h16v-1.5c0-2.5-3.58-4.5-8-4.5Z" />
                                </svg>
                            </span>
                            <span>Identifying Information</span>
                        </div>

                        <div class="form-grid">
                            <label class="field"><span>First Name:</span><input type="text" name="first_name"></label>
                            <label class="field"><span>Middle Name:</span><input type="text" name="middle_name"></label>
                            <label class="field"><span>Last Name:</span><input type="text" name="last_name"></label>
                            <label class="field"><span>Suffix:</span><input type="text" name="suffix"></label>

                            <label class="field"><span>Income:</span><input type="text" name="income_data"></label>
                            <label class="field"><span>Place of Birth:</span><input type="text"
                                    name="place_of_birth"></label>
                            <label class="field"><span>Nationality:</span><input type="text" name="nationality"></label>
                            <label class="field"><span>Age:</span><input type="number" min="0" name="age"></label>

                            <label class="field"><span>Address:</span><input type="text" name="address"></label>
                            <label class="field"><span>Barangay:</span>

                               
                                <input list="barangay-list" name="barangay" id="barangay">
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


                            </label>
                            <label class="field"><span>Religion:</span><input type="text" name="religion"></label>
                            <label class="field">
                                <span>Sex:</span>
                                <select name="sex">
                                    <option value="">Select</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </label>
                            
                            <label class="field"><span>In School Youth:</span><input type="text"
                                    name="in_school_youth"></label>
                            <label class="field"><span>Out of School Youth:</span><input type="text"
                                    name="out_of_school_youth"></label>
                            <label class="field"><span>Other Disadvantage Youth:</span><input type="text"
                                    name="other_disadvantage_youth"></label>
                            <label class="field"><span>IP's:</span><input type="text" name="ips"></label>

                            <label class="field"><span>Occupation:</span><input type="text" name="occupation"></label>
                            <label class="field"><span>Civil Status:</span>
                                <select name="civil_status" id="civil_status">
                                    <option value=""></option>
                                    <option value="single">Single</option>
                                    <option value="married">Married</option>
                                    <option value="separated">Separated</option>
                                    <option value="divorced">Divorced</option>
                                    <option value="widowed">Widowed</option>
                                </select>
                            </label>
                            <label class="field span-2"><span>Contact Number:</span><input type="text"
                                    name="contact_number"></label>

                            <label class="field span-2"><span>School Last Attended:</span><input type="text"
                                    name="school_last_attended"></label>
                            <label class="field span-2"><span>Month &amp; Year:</span><input type="month"
                                    name="school_last_attended_month_year"></label>

                            <label class="field span-2"><span>Trainings Attended:</span><input type="text"
                                    name="trainings_attended"></label>
                            <label class="field span-2"><span>Skills/Interest/Hobbies:</span><input type="text"
                                    name="skills_interest_hobbies"></label>
                        </div>
                    </section>

                    <div class="form2-content">
                        <section class="section">
                            <div class="section-title">
                                <span class="title-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24">
                                        <path
                                            d="M7 2h10a3 3 0 0 1 3 3v14a3 3 0 0 1-3 3H7a3 3 0 0 1-3-3V5a3 3 0 0 1 3-3Zm0 2a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1V5a1 1 0 0 0-1-1H7Zm2 4h6v2H9V8Zm0 4h6v2H9v-2Z" />
                                    </svg>
                                </span>
                                <h2>Family Composition</h2>
                            </div>

                            <div class="table-card">
                                <table class="family-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Relationship</th>
                                            <th>Gender</th>
                                            <th>Age</th>
                                            <th>Grade Yr.</th>
                                            <th>ISY</th>
                                            <th>OSY</th>
                                            <th>Work/Labor Experience</th>
                                            <th>Income</th>
                                        </tr>
                                    </thead>
                                    <tbody id="familyTableBody">
                                        <tr class="placeholder-row">
                                            <td colspan="9">No family member added yet.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <input class="modal-toggle" type="checkbox" id="family-modal-toggle">
                            <label class="add-button" for="family-modal-toggle"><span class="button-icon"
                                    aria-hidden="true"><svg viewBox="0 0 24 24">
                                        <path
                                            d="M12 5a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H6a1 1 0 1 1 0-2h5V6a1 1 0 0 1 1-1Z" />
                                    </svg></span>Add</label>

                            <div class="modal">
                                <label class="modal-backdrop" for="family-modal-toggle"></label>
                                <div class="modal-card" role="dialog" aria-modal="true"
                                    aria-labelledby="family-modal-title">
                                    <div class="modal-header">
                                        <h3 id="family-modal-title">Add Family Composition</h3><label
                                            class="modal-close" for="family-modal-toggle"
                                            aria-label="Close modal">✕</label>
                                    </div>
                                    <div class="modal-body">
                                        <label class="modal-field"><span>Name</span><input id="family_name"
                                                name="family_name" type="text" placeholder="Name"></label>
                                        <label class="modal-field"><span>Relationship</span><input
                                                id="family_relationship" name="family_relationship" type="text"
                                                placeholder="Relationship"></label>
                                        <label class="modal-field"><span>Gender</span><input id="family_gender"
                                                name="family_gender" type="text" placeholder="Gender"></label>
                                        <label class="modal-field"><span>Age</span><input id="family_age"
                                                name="family_age" type="number" min="0" placeholder="Age"></label>
                                        <label class="modal-field"><span>Grade Yr.</span><input id="family_grade_year"
                                                name="family_grade_year" type="text" placeholder="Grade Yr."></label>
                                        <label class="modal-field"><span>ISY</span><input id="family_isy"
                                                name="family_isy" type="text" placeholder="ISY"></label>
                                        <label class="modal-field"><span>OSY</span><input id="family_osy"
                                                name="family_osy" type="text" placeholder="OSY"></label>
                                        <label class="modal-field span-2"><span>Work/Labor Experience</span><input
                                                id="family_work_labor_experience" name="family_work_labor_experience"
                                                type="text" placeholder="Work/Labor Experience"></label>
                                        <label class="modal-field"><span>Income</span><input id="family_income"
                                                name="family_income" type="text" placeholder="Income"></label>
                                    </div>
                                    <div class="modal-actions"><label class="modal-button ghost"
                                            for="family-modal-toggle">Cancel</label><button class="modal-button"
                                            id="saveFamilyBtn" type="button">Save</button></div>
                                </div>
                            </div>
                        </section>

                        <section class="section">
                            <div class="section-title"><span class="title-icon" aria-hidden="true"><svg
                                        viewBox="0 0 24 24">
                                        <path d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h6v6h-6v-6Z" />
                                    </svg></span>
                                <h2>Assistance &amp; Intervention Received from CSWD &amp; other Agencies:</h2>
                            </div>
                            <div class="checkbox-grid" id="assistanceCheckboxes">
                                <label><input type="checkbox" name="assistance_received[]"
                                        value="Social Amelioration Program (SAP)"> Social Amelioration Program
                                    (SAP)</label>
                                <label><input type="checkbox" name="assistance_received[]" value="Food Subsidy"> Food
                                    Subsidy</label>
                                <label><input type="checkbox" name="assistance_received[]" value="Summer Job"> Summer
                                    Job</label>
                                <label><input type="checkbox" name="assistance_received[]" value="Teenage Pregnancy">
                                    Teenage Pregnancy</label>
                                <label><input type="checkbox" name="assistance_received[]" value="Drug Awareness"> Drug
                                    Awareness</label>
                                <label><input type="checkbox" name="assistance_received[]"
                                        value="Government Outreach Program"> Government Outreach Program</label>
                                <label><input type="checkbox" name="assistance_received[]" value="Livelihood Program">
                                    Livelihood Program</label>
                                <label><input type="checkbox" name="assistance_received[]"
                                        value="Internship Outreach Program"> Internship Outreach Program</label>
                                <label><input type="checkbox" name="assistance_received[]"
                                        value="Mental Health Awareness"> Mental Health Awareness</label>
                                <label><input type="checkbox" name="assistance_received[]" value="Leadership Training">
                                    Leadership Training</label>
                            </div>
                            <div class="specify"><label for="assist-specify">Please Specify:</label><input
                                    id="assist-specify" name="assistance_specify" type="text"></div>
                        </section>

                        <div class="section-row">
                            <section class="section">
                                <div class="section-title"><span class="title-icon" aria-hidden="true"><svg
                                            viewBox="0 0 24 24">
                                            <path d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h6v6h-6v-6Z" />
                                        </svg></span>
                                    <h2>Intervention needed:</h2>
                                </div>
                                <div class="checkbox-grid two-columns" id="interventionCheckboxes">
                                    <label><input type="checkbox" name="intervention_needed[]" value="Skills Training">
                                        Skills Training</label>
                                    <label><input type="checkbox" name="intervention_needed[]" value="Livelihood">
                                        Livelihood</label>
                                </div>
                                <div class="specify"><label for="intervention-specify">Please Specify:</label><input
                                        id="intervention-specify" name="intervention_specify" type="text"></div>
                            </section>

                            <section class="section membership-section">
                                <div class="section-title"><span class="title-icon" aria-hidden="true"><svg
                                            viewBox="0 0 24 24">
                                            <path d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h6v6h-6v-6Z" />
                                        </svg></span>
                                    <h2>Membership on Organization, Clubs, Society:</h2>
                                </div>
                                <div class="table-card">
                                    <table class="membership-table">
                                        <thead>
                                            <th>Name of Organization</th>
                                            <th>Position Held, if any</th>
                                            <th>Year</th>
                                        </thead>
                                        <tbody id="membershipTableBody">
                                            <tr class="placeholder-row">
                                                <td colspan="3">No membership added yet.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <input class="modal-toggle" type="checkbox" id="membership-modal-toggle">
                                <label class="add-button" for="membership-modal-toggle"><span class="button-icon"
                                        aria-hidden="true"><svg viewBox="0 0 24 24">
                                            <path
                                                d="M12 5a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H6a1 1 0 1 1 0-2h5V6a1 1 0 0 1 1-1Z" />
                                        </svg></span>Add</label>

                                <div class="modal">
                                    <label class="modal-backdrop" for="membership-modal-toggle"></label>
                                    <div class="modal-card" role="dialog" aria-modal="true"
                                        aria-labelledby="membership-modal-title">
                                        <div class="modal-header">
                                            <h3 id="membership-modal-title">Add Membership</h3><label
                                                class="modal-close" for="membership-modal-toggle"
                                                aria-label="Close modal">✕</label>
                                        </div>
                                        <div class="modal-body">
                                            <label class="modal-field"><span>Name of Organization</span><input
                                                    id="membership_organization_name"
                                                    name="membership_organization_name" type="text"
                                                    placeholder="Organization name"></label>
                                            <label class="modal-field"><span>Position Held, if any</span><input
                                                    id="membership_position_held" name="membership_position_held"
                                                    type="text" placeholder="Position held"></label>
                                            <label class="modal-field"><span>Year</span><input id="membership_year"
                                                    name="membership_year" type="text" placeholder="Year"></label>
                                        </div>
                                        <div class="modal-actions"><label class="modal-button ghost"
                                                for="membership-modal-toggle">Cancel</label><button class="modal-button"
                                                id="saveMembershipBtn" type="button">Save</button></div>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>

                    <input type="hidden" id="familyCompositionInput" name="family_compositions">
                    <input type="hidden" id="membershipsInput" name="memberships">
                    <p id="formMessage" class="form-message"></p>
                    <div class="submit-container"><button type="submit" class="submit-btn-main">Submit</button></div>
                </form>


            </main>
        </div>
    </div>
    <script src="../../shared/client-record-mode.js"></script>
    <script src="../../shared/form-submit-confirm.js"></script>
    <script src="pyap.js"></script>

</body>

</html>