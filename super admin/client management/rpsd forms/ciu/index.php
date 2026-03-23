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
    <title>CCIMS -CIU Form</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/umd/lucide.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="ciu.css">
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
                <section class="ciu-form">
                    <div class="ciu-header">
                        <div class="ciu-logos">
                            <img src="../../../images-logo/butuanseal.png" alt="Butuan City Seal">
                            <div class="ciu-title">
                                <p>Republic of the Philippines</p>
                                <p><strong>City Government of Butuan</strong></p>
                                <p><strong>City Social Welfare and Development Department</strong></p>
                                <p>City Hall Building, J.P. Rosales Ave., Doongan, Butuan City</p>
                            </div>
                            <img src="../../../images-logo/cswd-logo.png" alt="CSWD Logo">
                        </div>
                        <h2>CIU Intake Sheet Form</h2>
                    </div>

                    <form class="ciu-body">
                        <div class="form-grid">
                            <label class="field">
                                First Name:
                                <input type="text" name="first_name">
                            </label>
                            <label class="field">
                                Middle Name:
                                <input type="text" name="middle_name">
                            </label>
                            <label class="field">
                                Last Name:
                                <input type="text" name="last_name">
                            </label>
                            <label class="field">
                                Suffix:
                                <input type="text" name="suffix">
                            </label>

                            <label class="field">
                                Birth Date:
                                <input type="date" name="birth_date">
                            </label>
                            <label class="field span-2">
                                Birth Place:
                                <input type="text" name="birth_place">
                            </label>
                            <label class="field">
                                Age:
                                <input type="text" name="age">
                            </label>

                            <div class="field">
                                Sex:
                                <div class="radio-group">
                                    <label><input type="radio" name="sex" value="male"> Male</label>
                                    <label><input type="radio" name="sex" value="female"> Female</label>
                                </div>
                            </div>
                            <label class="field">
                                Civil Status:
                                <select name="civil_status">
                                    <option value="">Select</option>
                                    <option value="single">Single</option>
                                    <option value="married">Married</option>
                                    <option value="widowed">Widowed</option>
                                    <option value="separated">Separated</option>
                                    <option value="annulled">Annulled</option>
                                </select>
                            </label>
                            <label class="field span-2">
                                Contact Number:
                                <input type="text" name="contact_number">
                            </label>

                            <label class="field">
                                Monthly Income:
                                <input type="text" name="monthly_income">
                            </label>
                            <label class="field">
                                Religion:
                                <select name="religion">
                                    <option value="">Select</option>
                                    <option value="roman_catholic">Roman Catholic</option>
                                    <option value="islam">Islam</option>
                                    <option value="protestant">Protestant</option>
                                    <option value="iglesia_ni_cristo">Iglesia ni Cristo</option>
                                    <option value="others">Others</option>
                                </select>
                            </label>
                            <label class="field span-2">
                                Occupation:
                                <input type="text" name="occupation">
                            </label>

                            <label class="field">
                                Educational Attainment:
                                <input type="text" name="education">
                            </label>
                            <label class="field span-2">
                                Address:
                                <input type="text" name="address">
                            </label>
                            <label class="field">
                                Barangay:
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
                        </div>

                        <div class="ciu-section">
                            <p class="section-title">Member(s) of:</p>
                            <div class="checkbox-grid">
                                <label class="checkbox-item">
                                    <input type="checkbox" name="member_senior"> Senior Citizen
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="member_solo_parent"> Solo Parent
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="member_pwd"> PWD
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="member_4ps"> 4Ps
                                </label>
                            </div>
                            <label class="field others-field">
                                Others Specify:
                                <input type="text" name="member_others">
                            </label>
                        </div>

                        <div class="ciu-section">
                            <p class="section-title">Services Needed:</p>
                            <div class="services-grid">
                                <label class="checkbox-item">
                                    <input type="checkbox" name="service_prescribed_medicine"> Prescribed Medicine Assistance
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="service_certificate_minor"> Certificate of Travelling Minor
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="service_hospital_bill"> Hospital Bill Assistance
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="service_tesda_certificate"> TESDA Certificate
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="service_burial"> Burial Assistance
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="service_laboratory"> Laboratory Assistance
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="service_legal_assistance"> Certificate to avail for Free Legal Assistance
                                </label>
                                <label class="field others-field">
                                    Others Specify:
                                    <input type="text" name="service_others">
                                </label>
                            </div>
                        </div>

                        <div class="ciu-section family-section">
                            <div class="family-header">
                                <p class="section-title">Family Composition</p>
                                <button class="family-add-btn" type="button" id="openFamilyModal">
                                    <span class="family-add-icon">+</span> Add
                                </button>
                            </div>
                            <div class="family-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Age</th>
                                            <th>Gender</th>
                                            <th>Relationship to the client</th>
                                            <th>Educational Attainment</th>
                                            <th>Occupation</th>
                                            <th>Income</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="7"></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <button class="submit-btn" type="submit">Submit</button>
                        </div>
                    </form>
                </section>
            </main>
        </div>
    </div>

    <div class="modal" id="familyModal" aria-hidden="true">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Family Composition Details</h2>
                <button class="modal-close" type="button" id="closeFamilyModal">&times;</button>
            </div>
            <form class="modal-form">
                <label>
                    Name
                    <input type="text" name="family_name">
                </label>
                <label>
                    Age
                    <input type="number" name="family_age" min="0">
                </label>
                <label>
                    Gender
                    <select name="family_gender">
                        <option value="">Select</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                </label>
                <label>
                    Relationship to the client
                    <input type="text" name="family_relationship">
                </label>
                <label>
                    Educational Attainment
                    <input type="text" name="family_education">
                </label>
                <label>
                    Occupation
                    <input type="text" name="family_occupation">
                </label>
                <label>
                    Income
                    <input type="text" name="family_income">
                </label>
                <button class="submit-btn" type="button" id="saveFamilyEntry">Save</button>
            </form>
        </div>
    </div>
    <script src="../../shared/client-record-mode.js"></script>
    <script src="../../shared/form-submit-confirm.js"></script>
    <script src="ciu.js"></script>


</body>

</html>
