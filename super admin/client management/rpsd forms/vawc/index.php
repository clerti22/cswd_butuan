<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../auth/auth.php';
require_once __DIR__ . '/../../includes/navigation.php';
require_login(['super admin']);


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCIMS Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/umd/lucide.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="vawc.css">
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
                <div class="form-sheet">
                    <header class="form-header">
                        <img class="form-header__logo" src="../../../images-logo/cswd-logo.png" alt="CSWD logo">
                        <div class="form-header__text">
                            <p class="form-header__line">Republic of the Philippines</p>
                            <p class="form-header__line form-header__line--bold">CITY GOVERNMENT OF BUTUAN</p>
                            <p class="form-header__line form-header__line--bold">City Social Welfare and Development Department</p>
                            <p class="form-header__line">City Hall Building, J.P. Rosales Ave., Doongan, Butuan City</p>
                        </div>
                        <img class="form-header__logo" src="../../../images-logo/butuanseal.png" alt="Butuan City logo">
                    </header>

                    <h1 class="form-title">VAWC Intake Sheet Form</h1>

                    <section class="form-section">
                        <div class="section-title">
                            <span class="section-title__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                                    <path
                                        d="M12 12.75a4.25 4.25 0 1 0-4.25-4.25A4.26 4.26 0 0 0 12 12.75Zm0 1.5c-3.65 0-6.75 1.88-6.75 4.25V20h13.5v-1.5c0-2.37-3.1-4.25-6.75-4.25Z"
                                        fill="currentColor" />
                                </svg>
                            </span>
                            <h2>Personal Information</h2>
                        </div>

                        <form class="form-grid">
                            <div class="field">
                                <label for="first-name">First Name:</label>
                                <input id="first-name" type="text">
                            </div>
                            <div class="field">
                                <label for="middle-name">Middle Name:</label>
                                <input id="middle-name" type="text">
                            </div>
                            <div class="field">
                                <label for="last-name">Last Name:</label>
                                <input id="last-name" type="text">
                            </div>
                            <div class="field">
                                <label for="suffix">Suffix:</label>
                                <input id="suffix" type="text">
                            </div>

                            <div class="field field--span-2">
                                <label for="birth-place">Birth Place:</label>
                                <input id="birth-place" type="text">
                            </div>
                            <div class="field">
                                <label for="birth-date">Birth Date:</label>
                                <div class="input-icon">
                                    <input id="birth-date" type="date">
                                </div>
                            </div>
                            <div class="field">
                                <label for="age">Age:</label>
                                <input id="age" type="text">
                            </div>

                            <div class="field field--span-2">
                                <label for="education">Educational Attainment:</label>
                                <input id="education" type="text">
                            </div>
                            <div class="field field--span-2">
                                <label for="religion">Religion:</label>
                                <select id="religion">
                                    <option value="" selected></option>
                                    <option>Roman Catholic</option>
                                    <option>Islam</option>
                                    <option>Protestant</option>
                                    <option>Others</option>
                                </select>
                            </div>

                            <div class="field field--span-2">
                                <label for="address">Address:</label>
                                <input id="address" type="text">
                            </div>

                            <div class="field field--span-2">
                                <label for="barangay">Barangay:</label>
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
                            </div>

                            <div class="field">
                                <label for="occupation">Occupation:</label>
                                <input id="occupation" type="text">
                            </div>
                            <div class="field">
                                <label for="income">Monthly Income:</label>
                                <input id="income" type="text">
                            </div>
                            <div class="field">
                                <label for="contact">Contact Number:</label>
                                <input id="contact" type="text">
                            </div>
                            <div class="field">
                                <label for="civil-status">Civil Status:</label>
                                <select id="civil-status">
                                    <option value="" selected></option>
                                    <option>Single</option>
                                    <option>Married</option>
                                    <option>Separated</option>
                                    <option>Widowed</option>
                                </select>
                            </div>

                            <div class="field field--span-4">
                                <label class="clientele-label">Clientele Category:</label>
                                <div class="clientele-grid">
                                    <div class="clientele-item">
                                        <span>4P's (?):
                                        </span>
                                        <div class="radio-group">
                                            <label class="radio-option">
                                                <input type="radio" name="4ps" value="Yes">
                                                Yes
                                            </label>
                                            <label class="radio-option">
                                                <input type="radio" name="4ps" value="No">
                                                No
                                            </label>
                                        </div>
                                    </div>
                                    <div class="clientele-item">
                                        <span>UCT (?):
                                        </span>
                                        <div class="radio-group">
                                            <label class="radio-option">
                                                <input type="radio" name="uct" value="Yes">
                                                Yes
                                            </label>
                                            <label class="radio-option">
                                                <input type="radio" name="uct" value="No">
                                                No
                                            </label>
                                        </div>
                                    </div>
                                    <div class="clientele-item">
                                        <span>NHTS (?):
                                        </span>
                                        <div class="radio-group">
                                            <label class="radio-option">
                                                <input type="radio" name="nhts" value="Yes">
                                                Yes
                                            </label>
                                            <label class="radio-option">
                                                <input type="radio" name="nhts" value="No">
                                                No
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </section>
                    <section class="form-section">
                        <div class="section-title section-title--with-action">
                            <div class="section-title__content">
                                <span class="section-title__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                                        <path
                                            d="M12 12.75a4.25 4.25 0 1 0-4.25-4.25A4.26 4.26 0 0 0 12 12.75Zm0 1.5c-3.65 0-6.75 1.88-6.75 4.25V20h13.5v-1.5c0-2.37-3.1-4.25-6.75-4.25Z"
                                            fill="currentColor" />
                                    </svg>
                                </span>
                                <h2>Family Information</h2>
                            </div>
                            <button class="add-btn family-add-btn" type="button" id="openFamilyModal">
                                <svg aria-hidden="true" viewBox="0 0 24 24">
                                    <path
                                        d="M12 5.75a1 1 0 0 1 1 1v4.25H17a1 1 0 1 1 0 2h-4v4.25a1 1 0 1 1-2 0V13h-4a1 1 0 1 1 0-2h4V6.75a1 1 0 0 1 1-1Z"
                                        fill="currentColor" />
                                </svg>
                                Add
                            </button>
                        </div>

                        <div class="family-table">
                            <div class="family-table__header">
                                <span>Name</span>
                                <span>Age</span>
                                <span>Relationship</span>
                                <span>Education Attainment</span>
                                <span>Occupation</span>
                                <span>Income</span>
                            </div>
                            <div class="family-table__body">
                                <div class="family-table__row family-table__row--empty">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        <div class="section-title">
                            <span class="section-title__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                                    <path
                                        d="M12 2.75c-1.1 0-2.11.6-2.64 1.56L3.42 15.35a3 3 0 0 0 2.64 4.4h11.88a3 3 0 0 0 2.64-4.4l-5.94-11.04A2.98 2.98 0 0 0 12 2.75Zm0 4.5a1 1 0 0 1 1 1v4.5a1 1 0 1 1-2 0v-4.5a1 1 0 0 1 1-1Zm0 9.5a1.25 1.25 0 1 1 0-2.5 1.25 1.25 0 0 1 0 2.5Z"
                                        fill="currentColor" />
                                </svg>
                            </span>
                            <h2>Presenting Problem</h2>
                        </div>
                        <textarea class="form-textarea" name="presenting-problem" rows="4"></textarea>
                    </section>
                    <section class="form-section">
                        <div class="section-title">
                            <span class="section-title__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                                    <path
                                        d="M4.5 3.75h10a2 2 0 0 1 2 2v1h3a1 1 0 0 1 1 1v11a2 2 0 0 1-2 2h-14a2 2 0 0 1-2-2v-13a2 2 0 0 1 2-2Zm0 2a.5.5 0 0 0-.5.5v13a.5.5 0 0 0 .5.5h14a.5.5 0 0 0 .5-.5v-10.5h-2.5v5a1 1 0 0 1-1 1h-9a1 1 0 0 1-1-1v-7h9.5v-1a.5.5 0 0 0-.5-.5h-10Z"
                                        fill="currentColor" />
                                    <path
                                        d="M7.5 10.25h6a.75.75 0 0 0 0-1.5h-6a.75.75 0 0 0 0 1.5Zm0 3h6a.75.75 0 0 0 0-1.5h-6a.75.75 0 0 0 0 1.5Z"
                                        fill="currentColor" />
                                </svg>
                            </span>
                            <h2>Initial Description of the (below):</h2>
                        </div>
                        <div class="description-grid">
                            <label class="description-field" for="family-environment">
                                <span>Family Environment:</span>
                                <textarea id="family-environment" name="family-environment" rows="3"></textarea>
                            </label>
                            <label class="description-field" for="community">
                                <span>Community:</span>
                                <textarea id="community" name="community" rows="3"></textarea>
                            </label>
                            <label class="description-field" for="family-history">
                                <span>Family and History Background:</span>
                                <textarea id="family-history" name="family-history" rows="3"></textarea>
                            </label>
                        </div>
                    </section>
                    <section class="form-section">
                        <div class="section-title section-title--with-action">
                            <div class="section-title__content">
                                <span class="section-title__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                                        <path
                                            d="M4 12a8 8 0 0 1 14.5-4.7l1.2-1.2a1 1 0 1 1 1.4 1.4l-1.2 1.2A8 8 0 1 1 4 12Zm8-4a1 1 0 0 0-1 1v2H9a1 1 0 0 0 0 2h2v2a1 1 0 0 0 2 0v-2h2a1 1 0 0 0 0-2h-2V9a1 1 0 0 0-1-1Z"
                                            fill="currentColor" />
                                    </svg>
                                </span>
                                <h2>Initial Treatment</h2>
                            </div>
                            <button class="add-btn family-add-btn" type="button" id="openTreatmentModal">
                                <svg aria-hidden="true" viewBox="0 0 24 24">
                                    <path
                                        d="M12 5.75a1 1 0 0 1 1 1v4.25H17a1 1 0 1 1 0 2h-4v4.25a1 1 0 1 1-2 0V13h-4a1 1 0 1 1 0-2h4V6.75a1 1 0 0 1 1-1Z"
                                        fill="currentColor" />
                                </svg>
                                Add
                            </button>
                        </div>

                        <div class="treatment-table">
                            <div class="treatment-table__header">
                                <span>Objectives</span>
                                <span>Activities</span>
                                <span>Responsible Person</span>
                                <span>Resources Needed</span>
                                <span>Expected Output</span>
                            </div>
                            <div class="treatment-table__body">
                                <div class="treatment-table__row treatment-table__row--empty">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="form-section">
                        <div class="section-title">
                            <span class="section-title__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                                    <path
                                        d="M12 2.5a9.5 9.5 0 1 0 9.5 9.5 9.51 9.51 0 0 0-9.5-9.5Zm0 2a7.5 7.5 0 0 1 6.9 10.6l-1.7-.7a3.5 3.5 0 0 0-4.7-3.2V7.5a1 1 0 0 0-2 0v5.2a3.5 3.5 0 0 0 4.2 3.4l2.1.9A7.5 7.5 0 1 1 12 4.5Z"
                                        fill="currentColor" />
                                    <path
                                        d="M9.5 13h-1a1 1 0 0 0 0 2h1v1a1 1 0 0 0 2 0v-1h1a1 1 0 0 0 0-2h-1v-1a1 1 0 0 0-2 0v1Z"
                                        fill="currentColor" />
                                </svg>
                            </span>
                            <h2>Recommendation</h2>
                        </div>
                        <textarea class="form-textarea" name="recommendation" rows="4"></textarea>
                        <div class="form-actions">
                            <button class="form-submit-btn" type="button">Submit</button>
                        </div>
                    </section>
                </div>
            </main>
        </div>
    </div>
    <div class="modal" id="familyModal" aria-hidden="true">
        <div class="modal-content family-modal">
            <div class="family-modal__header">
                <h2>Family Information</h2>
                <button class="family-modal__close" type="button" id="closeFamilyModal" aria-label="Close">
                    &times;
                </button>
            </div>
            <form class="modal-form-grid" id="familyForm">
                <div class="field">
                    <label for="family-name">Name:</label>
                    <input id="family-name" name="name" type="text" required>
                </div>
                <div class="field">
                    <label for="family-age">Age:</label>
                    <input id="family-age" name="age" type="text" required>
                </div>
                <div class="field">
                    <label for="family-relationship">Relationship:</label>
                    <input id="family-relationship" name="relationship" type="text" required>
                </div>
                <div class="field">
                    <label for="family-education">Education Attainment:</label>
                    <input id="family-education" name="education" type="text" required>
                </div>
                <div class="field">
                    <label for="family-occupation">Occupation:</label>
                    <input id="family-occupation" name="occupation" type="text" required>
                </div>
                <div class="field">
                    <label for="family-income">Income:</label>
                    <input id="family-income" name="income" type="text" required>
                </div>
                <div class="modal-actions">
                    <button class="modal-submit" type="submit">Save</button>
                </div>
            </form>
        </div>
    </div>
    <div class="modal" id="treatmentModal" aria-hidden="true">
        <div class="modal-content family-modal">
            <div class="family-modal__header">
                <h2>Initial Treatment</h2>
                <button class="family-modal__close" type="button" id="closeTreatmentModal" aria-label="Close">
                    &times;
                </button>
            </div>
            <form class="modal-form-grid" id="treatmentForm">
                <div class="field">
                    <label for="treatment-objectives">Objectives:</label>
                    <input id="treatment-objectives" name="objectives" type="text">
                </div>
                <div class="field">
                    <label for="treatment-activities">Activities:</label>
                    <input id="treatment-activities" name="activities" type="text">
                </div>
                <div class="field">
                    <label for="treatment-responsible">Responsible Person:</label>
                    <input id="treatment-responsible" name="responsible" type="text">
                </div>
                <div class="field">
                    <label for="treatment-resources">Resources Needed:</label>
                    <input id="treatment-resources" name="resources" type="text">
                </div>
                <div class="field">
                    <label for="treatment-output">Expected Output:</label>
                    <input id="treatment-output" name="output" type="text">
                </div>
                <div class="modal-actions">
                    <button class="modal-submit" type="submit">Save</button>
                </div>
            </form>
        </div>
    </div>
    <script src="../../shared/client-record-mode.js"></script>
    <script src="../../shared/form-submit-confirm.js"></script>
    <script src="vawc.js"></script>


</body>

</html>
