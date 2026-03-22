<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../../auth/auth.php';
require_login(['super admin']);


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCIMS - Solo Parent Form</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/umd/lucide.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="soloparent.css">
</head>

<body>
    <div class="overlay" id="overlay"></div>

    <div class="container">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="../../../images-logo/cswd-logo.png" alt="" width="130" height="110">
                <p class="sidebar-title">CCIMS Dashboard</p>
                <button class="close-btn" id="closeSidebar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>

            <div class="nav-menu">
                <a href="#" class="nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-people nav-icon" viewBox="0 0 16 16">
                        <path
                            d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4" />
                    </svg>
                    <span class="nav-text">Social Workers Management</span>
                </a>

                <a href="#" class="nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-people nav-icon" viewBox="0 0 16 16">
                        <path
                            d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4" />
                    </svg>
                    <span class="nav-text">Clients Management</span>
                </a>

                <a href="#" class="nav-item">
                    <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                        viewBox="0 0 1024 1024">
                        <path fill="#ffff"
                            d="M864 409.6a192 192 0 0 1-37.888 349.44A256.064 256.064 0 0 1 576 960h-96a32 32 0 1 1 0-64h96a192.064 192.064 0 0 0 181.12-128H736a32 32 0 0 1-32-32V416a32 32 0 0 1 32-32h32c10.368 0 20.544.832 30.528 2.432a288 288 0 0 0-573.056 0A193.235 193.235 0 0 1 256 384h32a32 32 0 0 1 32 32v320a32 32 0 0 1-32 32h-32a192 192 0 0 1-96-358.4a352 352 0 0 1 704 0zM256 448a128 128 0 1 0 0 256V448zm640 128a128 128 0 0 0-128-128v256a128 128 0 0 0 128-128z" />
                    </svg>
                    <span class="nav-text">Program & Services</span>
                </a>

                <a href="#" class="nav-item">
                    <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                        <path fill="#ffffff"
                            d="M12 6a1 1 0 0 0-1 1v10a1 1 0 0 0 2 0V7a1 1 0 0 0-1-1Zm-5 6a1 1 0 0 0-1 1v4a1 1 0 0 0 2 0v-4a1 1 0 0 0-1-1Zm10-2a1 1 0 0 0-1 1v6a1 1 0 0 0 2 0v-6a1 1 0 0 0-1-1Zm2-8H5a3 3 0 0 0-3 3v14a3 3 0 0 0 3 3h14a3 3 0 0 0 3-3V5a3 3 0 0 0-3-3Zm1 17a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1Z" />
                    </svg>

                    <span class="nav-text">Report</span>

                </a>

                <a href="#" class="nav-item">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <span class="nav-text">Feedback</span>
                    <span class="nav-badge">12</span>
                </a>


            </div>

            <div class="sidebar-footer">
                <a href="#" class="nav-item">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                        class="bi bi-door-open nav-icon" viewBox="0 0 16 16">
                        <path d="M8.5 10c-.276 0-.5-.448-.5-1s.224-1 .5-1 .5.448.5 1-.224 1-.5 1" />
                        <path
                            d="M10.828.122A.5.5 0 0 1 11 .5V1h.5A1.5 1.5 0 0 1 13 2.5V15h1.5a.5.5 0 0 1 0 1h-13a.5.5 0 0 1 0-1H3V1.5a.5.5 0 0 1 .43-.495l7-1a.5.5 0 0 1 .398.117M11.5 2H11v13h1V2.5a.5.5 0 0 0-.5-.5M4 1.934V15h6V1.077z" />
                    </svg>
                    <span class="nav-text">Logout</span>
                </a>
            </div>
        </nav>

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
                    <p class="form-header__line">Republic of the Philippines</p>
                    <p class="form-header__line">Local Social Welfare and Development Office</p>
                    <p class="form-header__line">City/Municipality,Region</p>
                    <h1 class="form-header__title">APPLICATION FORM FOR SOLO PARENT</h1>
                </header>

                <section class="form-section">
                    <div class="section-title">
                        <span class="section-title__icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                                <path
                                    d="M12 12.75a4.25 4.25 0 1 0-4.25-4.25A4.26 4.26 0 0 0 12 12.75Zm0 1.5c-3.65 0-6.75 1.88-6.75 4.25V20h13.5v-1.5c0-2.37-3.1-4.25-6.75-4.25Z"
                                    fill="currentColor" />
                            </svg>
                        </span>
                        <h2>Identifying Information</h2>
                    </div>

                    <form id="soloParentForm" method="post" action="soloparent_submit.php">
                        <div class="form-grid">
                        <div class="field">
                            <label for="first-name">First Name:</label>
                            <input id="first-name" name="first_name" type="text" />
                        </div>
                        <div class="field">
                            <label for="middle-name">Middle Name:</label>
                            <input id="middle-name" name="middle_name" type="text" />
                        </div>
                        <div class="field">
                            <label for="last-name">Last Name:</label>
                            <input id="last-name" name="last_name" type="text" />
                        </div>
                        <div class="field">
                            <label for="suffix">Suffix:</label>
                            <input id="suffix" name="suffix" type="text" />
                        </div>

                        <div class="field">
                            <label for="birth-date">Date of Birth:</label>
                            <div class="input-icon">
                                <input id="birth-date" name="date_of_birth" type="date" />
                            </div>
                        </div>
                        <div class="field field--span-2">
                            <label for="birth-place">Place of Birth:</label>
                            <input id="birth-place" name="place_of_birth" type="text" />
                        </div>
                        <div class="field">
                            <label for="age">Age:</label>
                            <input id="age" name="age" type="text" />
                        </div>

                        <div class="field field--span-2">
                            <label for="address">Address:</label>
                            <input id="address" name="address" type="text" />
                        </div>

                        
                        <div class="field">
                            <label for="religion">Religion:</label>
                            <input id="religion" name="religion" type="text" />
                        </div>
                        <div class="field">
                            <label for="sex">Sex:</label>
                            <select name="sex" id="sex">
                                <option value=""></option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>

                        <div class="field field--span-2">
                            <label for="education">Educational Attainment:</label>
                            <input id="education" name="educational_attainment" type="text" />
                        </div>
                        <div class="field">
                            <label for="occupation">Occupation:</label>
                            <input id="occupation" name="occupation" type="text" />
                        </div>

                        <div class="field">
                            <label for="company">Company/Agency:</label>
                            <input id="company" name="company_agency" type="text" />
                        </div>
                        <div class="field">
                            <label for="civil-status">Civil Status:</label>
                            <select id="civil-status" name="civil_status">
                                <option value="" selected></option>
                                <option>Single</option>
                                <option>Married</option>
                                <option>Separated</option>
                                <option>Widowed</option>
                            </select>
                        </div>
                        <div class="field field--span-2">
                            <label>Employment Status:</label>
                            <div class="radio-group">
                                <label class="radio-option">
                                    <input type="radio" name="employment_status" value="Employed" />
                                    Employed
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="employment_status" value="Self-Employed" />
                                    Self-Employed
                                </label>
                                <label class="radio-option">
                                    <input type="radio" name="employment_status" value="Not Employed" />
                                    Not - Employed
                                </label>
                            </div>
                        </div>

                        <div class="field">
                            <label for="contact">Contact Number:</label>
                            <input id="contact" name="contact_number" type="text" />
                        </div>
                        <div class="field">
                            <label for="income">Monthly Income:</label>
                            <input id="income" name="monthly_income" type="text" />
                        </div>
                        <div class="field">
                            <label for="philsy">Philsys Card Number:</label>
                            <input id="philsy" name="philsys_card_number" type="text" />
                        </div>

                        <div class="field">
                            <label for="email">Email Address:</label>
                            <input id="email" name="email_address" type="email" />
                        </div>
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
                        </div>

                        

                    <div class="eligibility-section">
                        <div class="eligibility-grid">
                            <div class="eligibility-item">
                                <p class="eligibility-title">Pantawid Beneficiary(?):</p>
                                <div class="radio-group">
                                    <label class="radio-option">
                                        <input type="radio" name="pantawid_beneficiary" value="yes" />
                                        Yes
                                    </label>
                                    <label class="radio-option">
                                        <input type="radio" name="pantawid_beneficiary" value="no" />
                                        No
                                    </label>
                                </div>
                            </div>
                            <div class="eligibility-item">
                                <p class="eligibility-title">Indigenous Person(?):</p>
                                <div class="radio-group">
                                    <label class="radio-option">
                                        <input type="radio" name="indigenous_person" value="yes" />
                                        Yes
                                    </label>
                                    <label class="radio-option">
                                        <input type="radio" name="indigenous_person" value="no" />
                                        No
                                    </label>
                                </div>
                            </div>
                            <div class="eligibility-item">
                                <p class="eligibility-title">LGBTQ+(?):</p>
                                <div class="radio-group">
                                    <label class="radio-option">
                                        <input type="radio" name="lgbtq_plus" value="yes" />
                                        Yes
                                    </label>
                                    <label class="radio-option">
                                        <input type="radio" name="lgbtq_plus" value="no" />
                                        No
                                    </label>
                                </div>
                            </div>
                            <div class="eligibility-item">
                                <p class="eligibility-title">PWD(?):</p>
                                <div class="radio-group">
                                    <label class="radio-option">
                                        <input type="radio" name="pwd_status" value="yes" />
                                        Yes
                                    </label>
                                    <label class="radio-option">
                                        <input type="radio" name="pwd_status" value="no" />
                                        No
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="eligibility-inputs">
                            <div class="field">
                                <label for="pantawid-household">Pantawid Beneficiary,if yes (Household ID#):</label>
                                <input id="pantawid-household" name="pantawid_household_id" type="text" placeholder="Disabled" disabled />
                            </div>
                            <div class="field">
                                <label for="indigenous-affiliation">Name of Affliation,if yes (Household ID#):</label>
                                <input id="indigenous-affiliation" name="indigenous_affiliation_name" type="text" placeholder="Disabled" disabled />
                            </div>
                        </div>
                    </div>
                </section>
                <section class="form-section">
                    <div class="section-title section-title--with-action">
                        <div class="section-title__content">
                            <span class="section-title__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                                    <path
                                        d="M12 3.172 2.5 11.25h1.5V21h6v-6h4v6h6v-9.75h1.5L12 3.172Zm-1 7.578V19.5H6V12.75l6-5.109 6 5.109V19.5h-5V12.75h-2Z"
                                        fill="currentColor" />
                                </svg>
                            </span>
                            <h2>Household Composition</h2>
                        </div>
                        <button class="add-household-btn" type="button" id="openHouseholdModal">
                            <i class="bi bi-person-plus-fill" aria-hidden="true"></i>
                            Add Household
                        </button>
                    </div>

                    <table class="table household-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Sex</th>
                                <th>Relationship</th>
                                <th>Age</th>
                                <th>Birthdate</th>
                                <th>Civil</th>
                                <th>Educational</th>
                                <th>Occupation</th>
                                <th>Monthly</th>
                            </tr>
                        </thead>
                        <tbody id="householdTableBody">
                            <tr class="household-empty-row">
                                <td colspan="9">No household members added yet.</td>
                            </tr>
                        </tbody>
                    </table>
                    <p class="household-note">
                        Note: Include family member and other members of the household especially minor children.
                    </p>
                </section>
                <section class="form-section">
                    <div class="field field--span-4" style="margin-top: 4em;">
                        <label for="solo-classification">
                            Classification/Circumstances of being a solo parent(Dahilan bakit naging solo parent?):
                        </label>
                        <textarea id="solo-classification" name="solo_classification" rows="4"></textarea>
                    </div>
                    <div class="field field--span-4" style="margin-top: 4em;">
                        <label for="solo-needs">
                            Needs/Problems of being a solo parent(Kinakailangan/Problema ng isang solo parent?):
                        </label>
                        <textarea id="solo-needs" name="solo_needs" rows="4"></textarea>
                        <input id="household-members-payload" type="hidden" name="household_members" value="[]" />
                    </div>
                    <div class="form-actions">
                        <button class="submit-form-btn" type="submit" id="submitSoloParent">Submit</button>
                    </div>
                </section>
                    </form>
            </main>
        </div>
    </div>

    <div class="modal" id="householdModal" aria-hidden="true" role="dialog" aria-modal="true">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Household Composition</h2>
                <button class="modal-close" type="button" id="closeHouseholdModal" aria-label="Close modal">
                    &times;
                </button>
            </div>
            <div class="modal-form-grid">
                <div class="field">
                    <label for="householdName">Name</label>
                    <input id="householdName" name="household_name" type="text" placeholder="Full name" />
                </div>
                <div class="field">
                    <label for="householdSex">Sex</label>
                    <select id="householdSex" name="household_sex">
                        <option value="" selected></option>
                        <option>Male</option>
                        <option>Female</option>
                    </select>
                </div>
                <div class="field">
                    <label for="householdRelationship">Relationship</label>
                    <input id="householdRelationship" name="household_relationship" type="text" placeholder="Relationship" />
                </div>
                <div class="field">
                    <label for="householdAge">Age</label>
                    <input id="householdAge" name="household_age" type="text" placeholder="Age" />
                </div>
                <div class="field">
                    <label for="householdBirthdate">Birthdate</label>
                    <input id="householdBirthdate" name="household_birthdate" type="date" />
                </div>
                <div class="field">
                    <label for="householdCivil">Civil Status</label>
                    <select id="householdCivil" name="household_civil_status">
                        <option value="" selected></option>
                        <option>Single</option>
                        <option>Married</option>
                        <option>Separated</option>
                        <option>Widowed</option>
                    </select>
                </div>
                <div class="field">
                    <label for="householdEducational">Educational</label>
                    <input id="householdEducational" name="household_educational" type="text" placeholder="Educational" />
                </div>
                <div class="field">
                    <label for="householdOccupation">Occupation</label>
                    <input id="householdOccupation" name="household_occupation" type="text" placeholder="Occupation" />
                </div>
                <div class="field">
                    <label for="householdMonthly">Monthly Income</label>
                    <input id="householdMonthly" name="household_monthly_income" type="text" placeholder="Monthly" />
                </div>
            </div>
            <div class="modal-actions">
                <button class="modal-submit" type="button" id="submitHousehold">Submit</button>
            </div>
        </div>
    </div>
    <script src="../../shared/form-submit-confirm.js"></script>
    <script src="soloparent.js"></script>


</body>

</html>
