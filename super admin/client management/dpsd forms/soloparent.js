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

const pantawidInput = document.getElementById('pantawid-household');
const pantawidRadios = document.querySelectorAll('input[name="pantawid-beneficiary"]');
const indigenousInput = document.getElementById('indigenous-affiliation');
const indigenousRadios = document.querySelectorAll('input[name="indigenous-person"]');

const toggleRadioInput = (radios, input) => {
    if (!input || !radios.length) {
        return;
    }

    const isYesSelected = Array.from(radios).some((radio) => radio.checked && radio.value === 'yes');
    input.disabled = !isYesSelected;
    input.placeholder = isYesSelected ? '' : 'Disabled';

    if (!isYesSelected) {
        input.value = '';
    }
};

const registerRadioToggle = (radios, input) => {
    if (!input || !radios.length) {
        return;
    }

    radios.forEach((radio) => {
        radio.addEventListener('change', () => toggleRadioInput(radios, input));
    });

    toggleRadioInput(radios, input);
};

registerRadioToggle(pantawidRadios, pantawidInput);
registerRadioToggle(indigenousRadios, indigenousInput);
