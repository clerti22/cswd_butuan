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
});



const card = document.getElementById("card");

function clearCard() {
    card.innerHTML = "";
}

//controls, add btn,search and filter
function createControls() {
    const controls = document.createElement("div");
    controls.className = "controls";

    const leftControls = document.createElement("div");
    leftControls.className = "left-controls";

    const searchInput = document.createElement("input");
    searchInput.type = "text";
    searchInput.className = "search-input";

    const searchBtn = document.createElement("button");
    searchBtn.className = "btn btn-search";
    searchBtn.textContent = "🔍 Search";

    const filterBtn = document.createElement("button");
    filterBtn.className = "btn btn-filter";
    filterBtn.textContent = "⚙ Filter";

    leftControls.append(searchInput, searchBtn, filterBtn);

    const addBtn = document.createElement("button");
    addBtn.className = "btn btn-add";
    addBtn.textContent = "＋ Add Client";

    controls.append(leftControls, addBtn);
    return controls;
}


//tables tags

function createTable(headers, rows) {
    const table = document.createElement("table");

    const thead = document.createElement("thead");
    const trHead = document.createElement("tr");

    headers.forEach(text => {
        const th = document.createElement("th");
        th.textContent = text;
        trHead.appendChild(th);
    });

    thead.appendChild(trHead);

    const tbody = document.createElement("tbody");

    rows.forEach(row => {
        const tr = document.createElement("tr");

        row.forEach(cell => {
            const td = document.createElement("td");
            td.textContent = cell;
            tr.appendChild(td);
        });

        const actionTd = document.createElement("td");
        const actionDiv = document.createElement("div");
        actionDiv.className = "action-btns";

        const editBtn = document.createElement("button");
        editBtn.className = "btn-edit";
        editBtn.textContent = "Edit";

        const showBtn = document.createElement("button");
        showBtn.className = "btn-show";
        showBtn.textContent = "Show";

        actionDiv.append(editBtn, showBtn);
        actionTd.appendChild(actionDiv);
        tr.appendChild(actionTd);

        tbody.appendChild(tr);
    });

    table.append(thead, tbody);
    return table;
}




function renderDPSD() {
    clearCard();

    const headers = [
        "Full Name", "Age", "Sex", "Address",
        "Services Availed", "Sector",
        "Contact Details", "Date of Service", "Action"
    ];

    const rows = [
        ["Trevor Phillips", "32", "M", "Ampayon", "PYAP Monitoring", "Youth Welf.", "09663059855", "09-19-2025"],
        ["Michael Townley", "30", "M", "Doongan", "Availment of As.", "PWD", "09348652928", "09-19-2025"],
        ["Tanjiro Kamado", "25", "M", "Libertad", "Renewed ID", "Solo Parent", "09357607934", "12-1-2024"]
    ];

    card.append(createControls(), createTable(headers, rows));
}


function renderPSSD() {
    clearCard();

    const headers = [
        "Full Name", "Age", "Sex", "Address",
        "Services Availed", "Sector",
        "Contact Details", "Date of Service", "Action"
    ];

    const rows = [
        ["Bill Gates", "32", "M", "Ampayon", "Assistive Device", "Senior Citizen", "09663059855", "09-19-2025"],
        ["Mark Zuckerberg", "30", "M", "Doongan", "Availment of As.", "PWD", "09348652928", "09-19-2025"],
        ["Jeff Bezos", "25", "M", "Libertad", "Physical Therapy", "PWD", "09357607934", "12-1-2024"]
    ];

    card.append(createControls(), createTable(headers, rows));
}


function renderRPSD() {
    clearCard();

    const headers = [
        "Full Name", "Age", "Sex", "Address",
        "Services Availed", "Sector",
        "Contact Details", "Date of Service", "Action"
    ];

    const rows = [
        ["Bill Gates", "32", "M", "Ampayon", "Service Ambot", "CAR", "09663059855", "09-19-2025"],
        ["Mark Zuckerberg", "30", "M", "Doongan", "Availment of As.", "CICL", "09348652928", "09-19-2025"],
        ["Jeff Bezos", "25", "M", "Libertad", "Physical Therapy", "VAWC", "09357607934", "12-1-2024"]
    ];

    card.append(createControls(), createTable(headers, rows));
}



const tabs = document.querySelectorAll(".tab");

tabs.forEach(tab => {
    tab.addEventListener("click", () => {
        tabs.forEach(t => t.classList.remove("active"));
        tab.classList.add("active");

        const selected = tab.dataset.tab;

        if (selected === "dpsd") renderDPSD();
        if (selected === "pssd") renderPSSD();
        if (selected === "rpsd") renderRPSD();
    });
});

// Default tab
renderDPSD();


