// ===== SIDEBAR (your original) =====
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

// Close sidebar when clicking nav items on mobile
document.querySelectorAll('.nav-item').forEach(item => {
  item.addEventListener('click', () => {
    if (window.innerWidth < 1024) closeSidebar();
  });
});

// Handle window resize
window.addEventListener('resize', () => {
  if (window.innerWidth >= 1024) closeSidebar();
});


// ===== DASHBOARD DATA =====
const clientsData = [
  { controlNumber: "C-2026-0001", fullName: "Franklin Clinton", age: 79, sex: "M", barangay: "Lemon", contactDetails: "09298395345" },
  { controlNumber: "C-2026-0002", fullName: "Maria Dela Cruz", age: 42, sex: "F", barangay: "Barangay 2", contactDetails: "09171234567" },
  { controlNumber: "C-2026-0003", fullName: "Juan Santos", age: 33, sex: "M", barangay: "Barangay 4", contactDetails: "09081231234" },
  { controlNumber: "C-2026-0004", fullName: "Aira Villanueva", age: 28, sex: "F", barangay: "Barangay 1", contactDetails: "09991239876" },
  { controlNumber: "C-2026-0005", fullName: "Kenji Tanaka", age: 36, sex: "M", barangay: "Barangay 5", contactDetails: "09351234567" },
];

const socialWorkersData = [
  { fullName: "Shinobu Kocho", email: "shinobu.kocho@cswd.gov.ph", phoneNumber: "09170000001" },
  { fullName: "Kie Kamado", email: "kie.kamado@cswd.gov.ph", phoneNumber: "09170000002" },
  { fullName: "Giyu Tomioka", email: "giyu.tomioka@cswd.gov.ph", phoneNumber: "09170000003" },
  { fullName: "Mitsuri Kanroji", email: "mitsuri.kanroji@cswd.gov.ph", phoneNumber: "09170000004" },
  { fullName: "Tengen Uzui", email: "tengen.uzui@cswd.gov.ph", phoneNumber: "09170000005" },
];

const rankingDivisions = [
  { name: "DPSD Division", total: 55 },
  { name: "RPSD Division", total: 2 },
  { name: "PSSD Division", total: 1 },
];

// ===== TABLE TOGGLE + RENDER =====
const btnClients = document.getElementById("btnClients");
const btnSW = document.getElementById("btnSW");
const tableHeadRow = document.getElementById("tableHeadRow");
const tableBody = document.getElementById("tableBody");
const searchInput = document.getElementById("searchInput");
const btnSearch = document.getElementById("btnSearch");

let currentTable = "clients";

function setActiveTable(next) {
  currentTable = next;

  btnClients.classList.toggle("active", next === "clients");
  btnSW.classList.toggle("active", next === "sw");

  // placeholder like screenshot (blank-ish input)
  searchInput.value = "";
  searchInput.placeholder = "";

  renderTable();
}

function renderTable() {
  if (!tableHeadRow || !tableBody) return;

  tableHeadRow.innerHTML = "";
  tableBody.innerHTML = "";

  const query = (searchInput.value || "").trim().toLowerCase();

  if (currentTable === "clients") {
    const headers = ["Control Number", "Full Name", "Age", "Sex", "Barangay", "Contact Details"];
    headers.forEach(h => {
      const th = document.createElement("th");
      th.textContent = h;
      tableHeadRow.appendChild(th);
    });

    const filtered = clientsData.filter(r =>
      r.controlNumber.toLowerCase().includes(query) ||
      r.fullName.toLowerCase().includes(query) ||
      r.barangay.toLowerCase().includes(query) ||
      r.contactDetails.toLowerCase().includes(query)
    );

    filtered.forEach(row => {
      const tr = document.createElement("tr");

      tr.innerHTML = `
        <td>${row.controlNumber}</td>
        <td>${row.fullName}</td>
        <td>${row.age}</td>
        <td>${row.sex}</td>
        <td>${row.barangay}</td>
        <td>${row.contactDetails}</td>
      `;

      tableBody.appendChild(tr);
    });
  } else {
    const headers = ["Full Name", "Email", "Phone Number"];
    headers.forEach(h => {
      const th = document.createElement("th");
      th.textContent = h;
      tableHeadRow.appendChild(th);
    });

    const filtered = socialWorkersData.filter(r =>
      r.fullName.toLowerCase().includes(query) ||
      r.email.toLowerCase().includes(query) ||
      r.phoneNumber.toLowerCase().includes(query)
    );

    filtered.forEach(row => {
      const tr = document.createElement("tr");

      tr.innerHTML = `
        <td>${row.fullName}</td>
        <td>${row.email}</td>
        <td>${row.phoneNumber}</td>
      `;

      tableBody.appendChild(tr);
    });
  }
}

btnClients?.addEventListener("click", () => setActiveTable("clients"));
btnSW?.addEventListener("click", () => setActiveTable("sw"));
btnSearch?.addEventListener("click", renderTable);

// live search while typing
searchInput?.addEventListener("input", renderTable);


// ===== RANKINGS TOGGLE =====
const tabDivisions = document.getElementById("tabDivisions");
const rankList = document.getElementById("rankList");

function renderRankings() {
  if (!rankList) return;
  rankList.innerHTML = "";

  rankingDivisions.forEach(item => {
    const div = document.createElement("div");
    div.className = "rank-item";
    div.innerHTML = `
      <div class="rank-icon"><i class="bi bi-people-fill"></i></div>
      <div>
        <div class="rank-name">${item.name}</div>
        <div class="rank-sub">${item.total} Clients</div>
      </div>
    `;
    rankList.appendChild(div);
  });
}

tabDivisions?.addEventListener("click", () => {
  tabDivisions.classList.add("active");
  renderRankings();
});


// ===== CANVASJS CHART =====
window.addEventListener("load", () => {
  // render table + rankings first
  setActiveTable("clients");
  renderRankings();

  // read totals from the cards so they always match
  const dpsd = Number(document.getElementById("dpsdTotal")?.textContent || 0);
  const pssd = Number(document.getElementById("pssdTotal")?.textContent || 0);
  const rpsd = Number(document.getElementById("rpsdTotal")?.textContent || 0);

  const chart = new CanvasJS.Chart("chartContainer", {
    animationEnabled: true,
    exportEnabled: true,
    theme: "light1",
    axisY: { includeZero: true },
    data: [{
      type: "column",
      indexLabelFontColor: "#5A5757",
      indexLabelFontSize: 14,
      indexLabelPlacement: "outside",
      dataPoints: [
        { label: "RPSD", y: rpsd, indexLabel: String(rpsd) },
        { label: "PSSD", y: pssd, indexLabel: String(pssd) },
        { label: "DPSD", y: dpsd, indexLabel: String(dpsd) },
      ]
    }]
  });

  chart.render();
});
