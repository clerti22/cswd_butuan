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
    <title>CCIMS - CSAE Form</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/umd/lucide.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="csae.css">
</head>

<body>
    <div class="overlay" id="overlay"></div>

    <div class="container">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="../../images-logo/cswd-logo.png" alt="" width="130" height="110">
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
                <a href="#" class="nav-item">
                    <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                        class="bi bi-person-circle" viewBox="0 0 16 16">
                        <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0" />
                        <path fill-rule="evenodd"
                            d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1" />
                    </svg>
                    <span class="nav-text">View Profile</span>
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
                <div class="form-sheet">
                    <header class="form-header">
                        <img class="form-header__logo" src="../../images-logo/cswd-logo.png" alt="CSWD logo">
                        <div class="form-header__text">
                            <p class="form-header__line">Republic of the Philippines</p>
                            <p class="form-header__line form-header__line--bold">CITY GOVERNMENT OF BUTUAN</p>
                            <p class="form-header__line form-header__line--bold">City Social Welfare and Development
                                Department</p>
                            <p class="form-header__line">City Hall Building, J.P. Rosales Ave., Doongan, Butuan City</p>
                        </div>
                        <img class="form-header__logo" src="../../images-logo/butuanseal.png" alt="Butuan City logo">
                    </header>

                    <h1 class="form-title">CSAE Intake Sheet Form</h1>

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

                            <div class="field">
                                <label for="nickname">Nickname:</label>
                                <input id="nickname" type="text">
                            </div>
                            <div class="field">
                                <label for="sex">Sex:</label>
                                <select id="sex">
                                    <option value="" selected></option>
                                    <option>Male</option>
                                    <option>Female</option>
                                </select>
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

                            <div class="field field--span-4">
                                <label for="birth-place">Birth Place:</label>
                                <input id="birth-place" type="text">
                            </div>
                            <div class="field field--span-4">
                                <label for="present-address">Present Address:</label>
                                <input id="present-address" type="text">
                            </div>
                            <div class="field field--span-2">
                                <label for="permanent-address">Permanent Address:</label>
                                <input id="permanent-address" type="text">
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
                            <div class="field field--span-2">
                                <label for="education">Educational Attainment:</label>
                                <input id="education" type="text">
                            </div>
                            <div class="field field--span-2">
                                <label for="school-last">School last Attended:</label>
                                <input id="school-last" type="text">
                            </div>
                            <div class="field field--span-4">
                                <label for="school-address">Address of School:</label>
                                <input id="school-address" type="text">
                            </div>

                            <div class="field">
                                <label for="health-condition">Health Condition:</label>
                                <input id="health-condition" type="text">
                            </div>
                            <div class="field">
                                <label for="contact-number">Contact Number:</label>
                                <input id="contact-number" type="text">
                            </div>
                            <div class="field field--radio">
                                <label>4Ps (?):
                                </label>
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
                            <div class="field field--radio">
                                <label>Philhealth:</label>
                                <div class="radio-group">
                                    <label class="radio-option">
                                        <input type="radio" name="philhealth" value="Yes">
                                        Yes
                                    </label>
                                    <label class="radio-option">
                                        <input type="radio" name="philhealth" value="No">
                                        No
                                    </label>
                                </div>
                            </div>
                        </form>
                    </section>

                    <section class="form-section">
                        <div class="section-title">
                            <span class="section-title__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                                    <path
                                        d="M12 3.5a1 1 0 0 1 1 1v4.14a4 4 0 1 1-2 0V4.5a1 1 0 0 1 1-1Zm-6 6.5a6 6 0 1 0 12 0c0-.7-.13-1.37-.37-2H5.37A6.06 6.06 0 0 0 6 10Z"
                                        fill="currentColor" />
                                    <path
                                        d="M6.5 2.5a1 1 0 0 1 1 1v.5h9v-.5a1 1 0 1 1 2 0v2a1 1 0 0 1-2 0V5h-9v.5a1 1 0 1 1-2 0v-2a1 1 0 0 1 1-1Z"
                                        fill="currentColor" />
                                </svg>
                            </span>
                            <h2>Offense Committed to the Child</h2>
                        </div>

                        <form class="form-grid">
                            <div class="field">
                                <label for="offense-type">Type of Offense:</label>
                                <input id="offense-type" type="text">
                            </div>
                            <div class="field">
                                <label for="incident-date-place">Date &amp; Place of Incident:</label>
                                <div class="input-icon">
                                    <input id="incident-date-place" type="date">
                                </div>
                            </div>
                            <div class="field">
                                <label for="perpetrator-name">Name of Perpetrator/Accused:</label>
                                <input id="perpetrator-name" type="text">
                            </div>

                            <div class="field">
                                <label for="referral-date">Date of Referral to Social Worker:</label>
                                <div class="input-icon">
                                    <input id="referral-date" type="date">
                                </div>
                            </div>
                            <div class="field">
                                <label for="referring-party">Name and Address of Referring Party:</label>
                                <input id="referring-party" type="text">
                            </div>
                            <div class="field">
                                <label for="case-status">Status of the Case:</label>
                                <input id="case-status" type="text">
                            </div>

                            <div class="field">
                                <label for="criminal-case">Criminal Case No.:</label>
                                <input id="criminal-case" type="text">
                            </div>
                            <div class="field">
                                <label for="info-source">Source of Information:</label>
                                <input id="info-source" type="text">
                            </div>
                            <div class="field">
                                <label for="complainant">Complainant:</label>
                                <input id="complainant" type="text">
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
                            <div class="family-table__body" id="familyTableBody">
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
                                        d="M12 3a9 9 0 0 0-9 9c0 5 4 9 9 9s9-4 9-9a9 9 0 0 0-9-9Zm0 4a1 1 0 0 1 1 1v4a1 1 0 0 1-2 0V8a1 1 0 0 1 1-1Zm0 9a1.25 1.25 0 1 1 0-2.5A1.25 1.25 0 0 1 12 16Z"
                                        fill="currentColor" />
                                </svg>
                            </span>
                            <h2>Presenting Problem</h2>
                        </div>
                        <textarea class="form-textarea" name="presenting-problem" rows="3"></textarea>
                    </section>

                    <section class="form-section">
                        <div class="section-title">
                            <span class="section-title__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                                    <path
                                        d="M4 4a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v2h3a2 2 0 0 1 2 2v9a3 3 0 0 1-3 3H8a3 3 0 0 1-3-3V4Z"
                                        fill="currentColor" />
                                    <path
                                        d="M7 9a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H8a1 1 0 0 1-1-1Zm0 4a1 1 0 0 1 1-1h6a1 1 0 1 1 0 2H8a1 1 0 0 1-1-1Z"
                                        fill="currentColor" />
                                    <path
                                        d="M16.5 6a2.5 2.5 0 0 1 0 5H15V6h1.5Z"
                                        fill="currentColor" />
                                </svg>
                            </span>
                            <h2>Background Information</h2>
                        </div>
                        <div class="description-grid">
                            <label class="description-field" for="background-child">
                                <span>Child:</span>
                                <textarea id="background-child" name="background-child" rows="3"></textarea>
                            </label>
                            <label class="description-field" for="background-family">
                                <span>Child's Family:</span>
                                <textarea id="background-family" name="background-family" rows="3"></textarea>
                            </label>
                            <label class="description-field" for="background-community">
                                <span>Child's Community:</span>
                                <textarea id="background-community" name="background-community" rows="3"></textarea>
                            </label>
                        </div>
                    </section>

                    <section class="form-section">
                        <div class="section-title">
                            <span class="section-title__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                                    <path
                                        d="M6 3a2 2 0 0 0-2 2v13a3 3 0 0 0 3 3h9a3 3 0 0 0 3-3V9l-6-6H6Z"
                                        fill="currentColor" />
                                    <path
                                        d="M14 3v6h6"
                                        fill="currentColor" />
                                    <path
                                        d="M8 13h8v2H8v-2Zm0 4h6v2H8v-2Z"
                                        fill="#ffffff" />
                                </svg>
                            </span>
                            <h2>Assessment</h2>
                        </div>
                        <textarea class="form-textarea" name="assessment" rows="3"></textarea>
                    </section>

                    <section class="form-section">
                        <div class="section-title section-title--with-action">
                            <div class="section-title__content">
                                <span class="section-title__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" role="img" aria-hidden="true">
                                        <path
                                            d="M4 12a8 8 0 0 1 14.5-4.7l1.2-1.2a1 1 0 1 1 1.4 1.4l-1.2 1.2A8 8 0 1 1 4 12Z"
                                            fill="currentColor" />
                                        <path
                                            d="M7.5 13.5a.75.75 0 0 1 .75-.75h7.5a.75.75 0 1 1 0 1.5h-7.5a.75.75 0 0 1-.75-.75Zm0-3a.75.75 0 0 1 .75-.75h7.5a.75.75 0 1 1 0 1.5h-7.5a.75.75 0 0 1-.75-.75Z"
                                            fill="currentColor" />
                                    </svg>
                                </span>
                                <h2>Treatment Plan</h2>
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
                                <span>Time frame</span>
                                <span>Person/s Responsible</span>
                                <span>Remarks</span>
                            </div>
                            <div class="treatment-table__body" id="treatmentTableBody">
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
                                        d="M12 2.5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 12 2.5Zm0 2a7.5 7.5 0 0 1 6.9 10.6l-1.7-.7a3.5 3.5 0 0 0-4.7-3.2V7.5a1 1 0 0 0-2 0v5.2a3.5 3.5 0 0 0 4.2 3.4l2.1.9A7.5 7.5 0 1 1 12 4.5Z"
                                        fill="currentColor" />
                                    <path
                                        d="M9.5 13h-1a1 1 0 0 0 0 2h1v1a1 1 0 0 0 2 0v-1h1a1 1 0 0 0 0-2h-1v-1a1 1 0 0 0-2 0v1Z"
                                        fill="currentColor" />
                                </svg>
                            </span>
                            <h2>Recommendation</h2>
                        </div>
                        <textarea class="form-textarea" name="recommendation" rows="3"></textarea>
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
                <h2>Treatment Plan</h2>
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
                    <label for="treatment-timeframe">Time frame:</label>
                    <input id="treatment-timeframe" name="timeframe" type="text">
                </div>
                <div class="field">
                    <label for="treatment-responsible">Person/s Responsible:</label>
                    <input id="treatment-responsible" name="responsible" type="text">
                </div>
                <div class="field">
                    <label for="treatment-remarks">Remarks:</label>
                    <input id="treatment-remarks" name="remarks" type="text">
                </div>
                <div class="modal-actions">
                    <button class="modal-submit" type="submit">Save</button>
                </div>
            </form>
        </div>
    </div>
    <script src="csae.js"></script>


</body>

</html>
