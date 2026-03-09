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

// Close sidebar when clicking nav items on mobile
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', (e) => {
        e.preventDefault();
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
})

const adminRegistrationForm = document.getElementById('admin-registration-form');
const registrationAlert = document.getElementById('registration-alert');
const registrationState = window.adminRegistrationState || null;

function showRegistrationAlert(message, status) {
    if (!registrationAlert) {
        return;
    }

    registrationAlert.textContent = message;
    registrationAlert.classList.remove('success', 'error');

    if (status === 'success' || status === 'error') {
        registrationAlert.classList.add(status);
    }
}

if (registrationState && registrationState.old && adminRegistrationForm) {
    Object.entries(registrationState.old).forEach(([fieldName, fieldValue]) => {
        const field = adminRegistrationForm.elements.namedItem(fieldName);
        if (field && typeof fieldValue === 'string') {
            field.value = fieldValue;
        }
    });

    if (registrationState.message && registrationState.status) {
        showRegistrationAlert(registrationState.message, registrationState.status);
    }
}

if (adminRegistrationForm) {
    adminRegistrationForm.addEventListener('submit', (event) => {
        const requiredFields = [
            'full_name',
            'username',
            'phone_number',
            'email',
            'gender',
            'age',
            'role',
            'password',
            'confirm_password',
        ];

        const hasMissingField = requiredFields.some((fieldName) => {
            const field = adminRegistrationForm.elements.namedItem(fieldName);
            return !field || !field.value.trim();
        });

        if (hasMissingField) {
            event.preventDefault();
            showRegistrationAlert('Please complete all fields before submitting.', 'error');
            return;
        }


        const emailField = adminRegistrationForm.elements.namedItem('email');
        if (emailField && !emailField.value.trim().toLowerCase().includes('@gmail.com')) {
            event.preventDefault();
            showRegistrationAlert('Email must contain @gmail.com.', 'error');
            return;
        }

        const passwordField = adminRegistrationForm.elements.namedItem('password');
        const confirmPasswordField = adminRegistrationForm.elements.namedItem('confirm_password');

        if (passwordField.value !== confirmPasswordField.value) {
            event.preventDefault();
            showRegistrationAlert('Password and confirm password do not match.', 'error');
        }
    });
}
