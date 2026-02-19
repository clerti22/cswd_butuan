<?php

declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth.php';
require_login(['super admin']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCIMS Client Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/umd/lucide.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="index.css">
</head>

<body>
    <div class="overlay" id="overlay"></div>

    <div class="container">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="../images-logo/cswd-logo.png" alt="" width="130" height="110">
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
                <section class="client-management">
                    <div class="tabs" role="tablist" aria-label="Client categories">
                        <button class="tab active" type="button" role="tab" aria-selected="true" data-tab="dpsd">
                            DPSD
                        </button>
                        <button class="tab" type="button" role="tab" aria-selected="false" data-tab="pssd">
                            PSSD
                        </button>
                        <button class="tab" type="button" role="tab" aria-selected="false" data-tab="rpsd">
                            RPSD
                        </button>
                    </div>

                    <div class="toolbar">
                        <input class="search-input" type="text" placeholder="">
                        <button class="btn btn-search" type="button">
                            <i class="bi bi-search"></i>
                            Search
                        </button>
                        <button class="btn btn-filter" type="button">
                            <i class="bi bi-funnel"></i>
                            Filter
                        </button>
                        <div class="add-client">
                            <button class="btn btn-add" type="button" id="addClientBtn" aria-haspopup="true"
                                aria-expanded="false">
                            <i class="bi bi-plus-lg"></i>
                            Add Client
                            </button>
                            <div class="add-client-menu" id="addClientMenu" role="menu" aria-hidden="true">
                                <button type="button" class="menu-item" role="menuitem">PYAP</button>
                                <button type="button" class="menu-item" role="menuitem">Solo Parent</button>
                                <button type="button" class="menu-item" role="menuitem">PWUD</button>
                            </div>
                        </div>
                    </div>

                    <div class="table-card">
                        <table class="client-table">
                            <thead>
                                <tr>
                                    
                                    <th>Control Number</th>
                                    <th>Full Name</th>
                                    <th>Age</th>
                                    <th>Sex</th>
                                    <th>Barangay</th>
                                    <th>Program</th>
                                    <th>Division</th>
                                    <th>Contact Details</th>
                                    <th>Date of Service</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody class="tab-panel active" data-tab-content="dpsd">
                               
                            </tbody>
                            <tbody class="tab-panel" data-tab-content="pssd">
                               
                            </tbody>
                            <tbody class="tab-panel" data-tab-content="rpsd">
                                
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination">
                        <button class="pager-btn" type="button">Prev</button>
                        <button class="pager-btn" type="button">Next</button>
                    </div>
                </section>
            </main>
        </div>
    </div>
    <script src="index.js"></script>


</body>

</html>
