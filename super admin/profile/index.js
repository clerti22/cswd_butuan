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

openBtn?.addEventListener('click', openSidebar);
closeBtn?.addEventListener('click', closeSidebar);
overlay?.addEventListener('click', closeSidebar);

document.querySelectorAll('.nav-item:not([data-logout-confirm])').forEach((item) => {
    item.addEventListener('click', (event) => {
        const hrefValue = item.getAttribute('href') || '';
        const isPlaceholderLink = hrefValue === '#' || hrefValue.trim() === '';

        if (isPlaceholderLink) {
            event.preventDefault();
        }

        if (window.innerWidth < 1024) {
            closeSidebar();
        }
    });
});

window.addEventListener('resize', () => {
    if (window.innerWidth >= 1024) {
        closeSidebar();
    }
});

const profileData = window.superAdminProfile || {};
const profileForm = document.getElementById('profileForm');
const passwordForm = document.getElementById('passwordForm');
const profileAlert = document.getElementById('profileAlert');
const passwordAlert = document.getElementById('passwordAlert');
const editButton = document.getElementById('edit-button');
const saveButton = document.getElementById('save-button');
const cancelButton = document.getElementById('cancel-button');
const changePasswordButton = document.getElementById('change-password-button');

let originalProfile = {
    full_name: profileData.full_name || '',
    username: profileData.username || '',
    role: profileData.role || 'Super Admin',
    email: profileData.email || '',
    age: profileData.age || '',
    gender: profileData.gender || '',
    position: profileData.position || '',
    phone_number: profileData.phone_number || '',
};

function setAlert(element, type, message) {
    if (!element) {
        return;
    }

    if (!message) {
        element.textContent = '';
        element.className = 'form-alert hidden';
        return;
    }

    element.textContent = message;
    element.className = `form-alert ${type}`;
}

function clearFieldErrors(formElement) {
    formElement.querySelectorAll('.field-error').forEach((node) => {
        node.textContent = '';
    });

    formElement.querySelectorAll('.input-error').forEach((field) => {
        field.classList.remove('input-error');
    });
}

function setFieldErrors(formElement, errors = {}) {
    clearFieldErrors(formElement);

    Object.entries(errors).forEach(([fieldName, message]) => {
        const field = formElement.querySelector(`[name="${fieldName}"]`);
        const errorNode = formElement.querySelector(`[data-error-for="${fieldName}"]`);

        if (field) {
            field.classList.add('input-error');
        }

        if (errorNode) {
            errorNode.textContent = message;
        }
    });
}

function fillProfileForm(data) {
    Object.entries(data).forEach(([field, value]) => {
        const input = profileForm.elements.namedItem(field);
        if (input) {
            input.value = value ?? '';
        }
    });
}

function setProfileReadOnly(isReadOnly) {
    Array.from(profileForm.elements).forEach((element) => {
        if (!(element instanceof HTMLElement)) {
            return;
        }

        if (element.name === 'role') {
            element.setAttribute('readonly', 'readonly');
            element.setAttribute('disabled', 'disabled');
            return;
        }

        if (element.tagName === 'SELECT') {
            if (isReadOnly) {
                element.setAttribute('disabled', 'disabled');
            } else {
                element.removeAttribute('disabled');
            }
        } else if (isReadOnly) {
            element.setAttribute('readonly', 'readonly');
        } else {
            element.removeAttribute('readonly');
        }
    });

    editButton.classList.toggle('hidden', !isReadOnly);
    saveButton.classList.toggle('hidden', isReadOnly);
    cancelButton.classList.toggle('hidden', isReadOnly);
}

function getProfileFormData() {
    const formData = new FormData(profileForm);
    return {
        full_name: String(formData.get('full_name') || '').trim(),
        username: String(formData.get('username') || '').trim(),
        role: String(formData.get('role') || '').trim(),
        email: String(formData.get('email') || '').trim(),
        age: String(formData.get('age') || '').trim(),
        gender: String(formData.get('gender') || '').trim(),
        position: String(formData.get('position') || '').trim(),
        phone_number: String(formData.get('phone_number') || '').trim(),
    };
}

function validateProfileData(data) {
    const errors = {};
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const phoneDigits = data.phone_number.replace(/[\s()+-]/g, '');

    if (!data.full_name) {
        errors.full_name = 'Full name is required.';
    }

    if (!data.username) {
        errors.username = 'Username is required.';
    } else if (!/^[A-Za-z0-9._-]{3,50}$/.test(data.username)) {
        errors.username = 'Username must be 3-50 characters and may only contain letters, numbers, dots, underscores, and dashes.';
    }

    if (!data.email) {
        errors.email = 'Email is required.';
    } else if (!emailPattern.test(data.email)) {
        errors.email = 'Please enter a valid email address.';
    }

    if (!data.age) {
        errors.age = 'Age is required.';
    } else {
        const ageValue = Number(data.age);
        if (!Number.isInteger(ageValue) || ageValue < 1 || ageValue > 130) {
            errors.age = 'Age must be a valid number between 1 and 130.';
        }
    }

    if (!data.gender) {
        errors.gender = 'Gender is required.';
    }

    if (!data.position) {
        errors.position = 'Position is required.';
    }

    if (!data.phone_number) {
        errors.phone_number = 'Phone number is required.';
    } else if (!/^\d{10,15}$/.test(phoneDigits)) {
        errors.phone_number = 'Phone number must contain 10 to 15 digits.';
    }

    return errors;
}

function validatePasswordData(data) {
    const errors = {};

    if (!data.current_password) {
        errors.current_password = 'Current password is required.';
    }

    if (!data.new_password) {
        errors.new_password = 'New password is required.';
    } else if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/.test(data.new_password)) {
        errors.new_password = 'New password must be at least 8 characters and include uppercase, lowercase, and a number.';
    }

    if (!data.confirm_password) {
        errors.confirm_password = 'Please confirm your new password.';
    } else if (data.new_password !== data.confirm_password) {
        errors.confirm_password = 'Confirm password must match the new password.';
    }

    return errors;
}

async function postJson(url, payload) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
    });

    const result = await response.json().catch(() => ({
        success: false,
        message: 'Unexpected server response.',
    }));

    if (!response.ok || result.success === false) {
        const error = new Error(result.message || 'Request failed.');
        error.errors = result.errors || {};
        throw error;
    }

    return result;
}

editButton.addEventListener('click', () => {
    setAlert(profileAlert, '', '');
    clearFieldErrors(profileForm);
    setProfileReadOnly(false);
});

cancelButton.addEventListener('click', () => {
    fillProfileForm(originalProfile);
    clearFieldErrors(profileForm);
    setAlert(profileAlert, '', '');
    setProfileReadOnly(true);
});

saveButton.addEventListener('click', async () => {
    const data = getProfileFormData();
    const errors = validateProfileData(data);
    setFieldErrors(profileForm, errors);

    if (Object.keys(errors).length > 0) {
        setAlert(profileAlert, 'error', 'Please correct the highlighted fields.');
        return;
    }

    const confirmed = window.confirm('Are you sure you want to update your profile? Please double check your details.');
    if (!confirmed) {
        return;
    }

    saveButton.disabled = true;
    cancelButton.disabled = true;

    try {
        const result = await postJson('update_profile.php', data);
        originalProfile = result.profile;
        fillProfileForm(originalProfile);
        clearFieldErrors(profileForm);
        setAlert(profileAlert, 'success', result.message || 'Profile updated successfully.');
        setProfileReadOnly(true);
    } catch (error) {
        setFieldErrors(profileForm, error.errors || {});
        setAlert(profileAlert, 'error', error.message || 'Unable to update profile right now.');
    } finally {
        saveButton.disabled = false;
        cancelButton.disabled = false;
    }
});

passwordForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const formData = new FormData(passwordForm);
    const data = {
        current_password: String(formData.get('current_password') || ''),
        new_password: String(formData.get('new_password') || ''),
        confirm_password: String(formData.get('confirm_password') || ''),
    };

    const errors = validatePasswordData(data);
    setFieldErrors(passwordForm, errors);

    if (Object.keys(errors).length > 0) {
        setAlert(passwordAlert, 'error', 'Please correct the highlighted fields.');
        return;
    }

    const confirmed = window.confirm('Are you sure you want to change your password?');
    if (!confirmed) {
        return;
    }

    changePasswordButton.disabled = true;

    try {
        const result = await postJson('change_password.php', data);
        passwordForm.reset();
        clearFieldErrors(passwordForm);
        setAlert(passwordAlert, 'success', result.message || 'Password successfully changed.');
    } catch (error) {
        setFieldErrors(passwordForm, error.errors || {});
        setAlert(passwordAlert, 'error', error.message || 'Unable to change password right now.');
    } finally {
        changePasswordButton.disabled = false;
    }
});

fillProfileForm(originalProfile);
setProfileReadOnly(true);
