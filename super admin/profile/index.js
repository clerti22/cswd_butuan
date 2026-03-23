const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const openBtn = document.getElementById('openSidebar');
const closeBtn = document.getElementById('closeSidebar');

function openSidebar() {
    sidebar.classList.add('open');
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeSidebar() {
    sidebar.classList.remove('open');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
}

openBtn.addEventListener('click', openSidebar);
closeBtn.addEventListener('click', closeSidebar);
overlay.addEventListener('click', closeSidebar);

<<<<<<< HEAD
<<<<<<< HEAD
<<<<<<< HEAD

// Close sidebar when clicking nav items on mobile
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', () => {
=======
=======
>>>>>>> parent of 47d2b9b (Update page.html)
=======
>>>>>>> parent of 0454dfa (Fix super admin nav click handling)
const logoutLink = document.getElementById('logoutLink');

if (logoutLink) {
    logoutLink.addEventListener('click', (e) => {
        e.preventDefault();
        closeSidebar();
        window.location.href = '/auth/logout.php';
    });
}

// Close sidebar when clicking placeholder nav items on mobile
document.querySelectorAll('.nav-item:not(#logoutLink)').forEach(item => {
    item.addEventListener('click', (e) => {
        const hrefValue = item.getAttribute('href') || '';
        const isPlaceholderLink = hrefValue === '#' || hrefValue.trim() === '';

        if (isPlaceholderLink) {
            e.preventDefault();
        }

<<<<<<< HEAD
<<<<<<< HEAD
>>>>>>> parent of 47d2b9b (Update page.html)
=======
>>>>>>> parent of 47d2b9b (Update page.html)
=======
>>>>>>> parent of 0454dfa (Fix super admin nav click handling)
        if (window.innerWidth < 1024) {
            closeSidebar();
        }
    });
});

// Handle window resize
window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) {
        closeSidebar();
    }
});

const profileData = window.superAdminProfile || {};

document.getElementById('name').value = profileData.name || '';
document.getElementById('username').value = profileData.username || '';
document.getElementById('role').value = profileData.role || 'Super Admin';
document.getElementById('email').value = profileData.email || 'No email available';
