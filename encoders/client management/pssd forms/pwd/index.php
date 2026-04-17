<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../auth/auth.php';
require_once __DIR__ . '/../../../includes/navigation.php';
require_once __DIR__ . '/../../shared/form_access.php';
require_encoder_program_access('PWD');


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCIMS - PWD Form</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/umd/lucide.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="pwd.css">
</head>

<body>
    <div class="overlay" id="overlay"></div>

    <div class="container">
        <!-- Sidebar -->
        <?php echo render_encoder_sidebar('clients', '../../..'); ?>

        <div class="main-content">
            <header class="topbar">
                <button class="menu-btn" id="openSidebar" aria-label="Open sidebar">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <h1 class="topbar-title">Client Management/PWD</h1>
            </header>

            <main class="form-page">
                <section class="form-header">
                    <div class="header-logos">
                        <img src="../../../images-logo/butuanseal.png" alt="Butuan City Seal">
                        <div class="header-text">
                            <p>Republic of the Philippines</p>
                            <p class="strong">CITY GOVERNMENT OF BUTUAN</p>
                            <p class="strong">City Social Welfare and Development Department</p>
                            <p>City Hall Building, J.P. Rosales Ave., Doongan, Butuan City</p>
                        </div>
                        <img src="../../../images-logo/cswd-logo.png" alt="CSWD Logo">
                    </div>
                    <h2>PWD Intake Form</h2>
                </section>

                <form id="pwudForm" method="POST" novalidate>
                    <section class="form-section">
                        <div class="section-title">
                            <i class="bi bi-person-circle"></i>
                            <span>Identifying Information</span>
                        </div>

                        <div class="form-grid">
                            <label class="field">
                                First Name:
                                <input type="text" name="first_name" id="firstName" required />
                            </label>
                            <label class="field">
                                Middle Name:
                                <input type="text" name="middle_name" id="middleName" />
                            </label>
                            <label class="field">
                                Last Name:
                                <input type="text" name="last_name" id="lastName" required />
                            </label>
                            <label class="field">
                                Suffix:
                                <input type="text" name="suffix" id="suffix" />
                            </label>

                            <label class="field">
                                Date of Birth:
                                <input type="date" name="date_of_birth" id="dateOfBirth" required />
                            </label>
                            <label class="field span-2">
                                Place of Birth:
                                <input type="text" name="place_of_birth" id="placeOfBirth" required />
                            </label>
                            <label class="field">
                                Age:
                                <input type="number" min="0" name="age" id="age" required />
                            </label>

                            <label class="field">
                                Address:
                                <input type="text" name="address" id="address" required />
                            </label>
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
                            <label class="field">
                                Religion:
                                <input type="text" name="religion" id="religion" required />
                            </label>
                            <label class="field">
                                Sex:
                                <select name="sex" id="sex" required>
                                    <option value="" selected></option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </label>

                            <label class="field span-2">
                                Educational Attainment:
                                <input type="text" name="educational_attainment" id="educationalAttainment" required />
                            </label>
                            <label class="field span-2">
                                Occupation:
                                <input type="text" name="occupation" id="occupation" required />
                            </label>

                            <label class="field">
                                Monthly Income:
                                <input type="number" min="0" step="0.01" name="monthly_income" id="monthlyIncome" required />
                            </label>
                            <label class="field">
                                Civil Status:
                                <select name="civil_status" id="civilStatus" required>
                                    <option value="" selected></option>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Separated">Separated</option>
                                    <option value="Widowed">Widowed</option>
                                </select>
                            </label>
                            <label class="field span-2">
                                Contact Number:
                                <input type="text" name="contact_number" id="contactNumber" required />
                            </label>

                            <label class="field span-4">
                                Services Needed:
                                <textarea rows="4" name="services_needed" id="servicesNeeded" required></textarea>
                            </label>
                        </div>
                    </section>

                    <section class="form-section">
                        <div class="section-title">
                            <i class="bi bi-people-fill"></i>
                            <span>Household Members</span>
                        </div>

                        <div class="table-card">
                            <table class="info-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Age</th>
                                        <th>Gender</th>
                                        <th>Relationship</th>
                                        <th>Occupation</th>
                                    </tr>
                                </thead>
                                <tbody id="householdMembersBody">
                                    <tr class="placeholder-row">
                                        <td colspan="5">No household member added yet.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <button class="add-button" type="button" id="openHouseholdModal">
                            <span class="button-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <path
                                        d="M12 5a1 1 0 0 1 1 1v5h5a1 1 0 1 1 0 2h-5v5a1 1 0 1 1-2 0v-5H6a1 1 0 1 1 0-2h5V6a1 1 0 0 1 1-1Z" />
                                </svg>
                            </span>
                            Add
                        </button>
                        
                        <p id="formMessage" class="form-message" role="alert"></p>
                    </section>
                    <div style="text-align: center;">
                        <button  type="submit"
                            id="submitPwudForm">Submit</button>
                    </div>
                    <input type="hidden" name="household_members" id="householdMembersInput" value="[]" />
                </form>
            </main>
        </div>
    </div>

    <div class="modal" id="householdModal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Household Member</h2>
                <a class="modal-close" type="button" id="closeHouseholdModal" aria-label="Close modal">✕</a>
            </div>
            <div class="modal-form-grid" id="householdModalForm">
                <label class="modal-field">
                    <span>Name</span>
                    <input type="text" name="household_name" id="householdName" placeholder="Name" required />
                </label>
                <label class="modal-field">
                    <span>Age</span>
                    <input type="number" min="0" name="household_age" id="householdAge" placeholder="Age" required />
                </label>
                <label class="modal-field">
                    <span>Gender</span>
                    <select name="household_gender" id="householdGender" required>
                        <option value="" selected></option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                    </select>
                </label>
                <label class="modal-field">
                    <span>Relationship</span>
                    <input type="text" name="household_relationship" id="householdRelationship" placeholder="Relationship" required />
                </label>
                <label class="modal-field span-2">
                    <span>Occupation</span>
                    <input type="text" name="household_occupation" id="householdOccupation" placeholder="Occupation" required />
                </label>
            </div>
            <div class="modal-actions">
                <button class="modal-submit" type="button" id="saveHouseholdMember">Save</button>
            </div>
        </div>
    </div>
    <script src="../../shared/client-record-mode.js"></script>
    <script src="../../shared/form-submit-confirm.js"></script>
    <script src="pwd.js"></script>


</body>

</html>
