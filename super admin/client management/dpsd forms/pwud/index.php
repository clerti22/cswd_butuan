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
    <title>CCIMS - PWUD Form</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/umd/lucide.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="pwud.css">
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

        <div class="main-content">
            <header class="topbar">
                <button class="menu-btn" id="openSidebar" aria-label="Open sidebar">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>
                <h1 class="topbar-title">Program & Services / PWUD</h1>
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
                    <h2>PWUD Intake Form</h2>
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
                                <input type="hidden" name="fullname" id="fullname" value="<?php echo $fullname; ?>"/>
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
                                <input type="number" min="0" step="0.01" name="monthly_income" id="monthlyIncome"
                                    required />
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
                    <input type="text" name="household_relationship" id="householdRelationship"
                        placeholder="Relationship" required />
                </label>
                <label class="modal-field span-2">
                    <span>Occupation</span>
                    <input type="text" name="household_occupation" id="householdOccupation" placeholder="Occupation"
                        required />
                </label>
            </div>
            <div class="modal-actions">
                <button class="modal-submit" type="button" id="saveHouseholdMember">Save</button>
            </div>
        </div>
    </div>
    <script src="pwud.js"></script>


</body>

</html>