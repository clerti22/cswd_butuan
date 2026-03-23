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
  { fullName: "Franklin Clinton", age: 79, sex: "M", address: "Lemon", role: "Admin", status: "Active", contact: "09298395345", dateCreated: "03-18-2019" },
  { fullName: "Maria Dela Cruz", age: 42, sex: "F", address: "Barangay 2", role: "Client", status: "Active", contact: "09171234567", dateCreated: "11-05-2021" },
  { fullName: "Juan Santos", age: 33, sex: "M", address: "Barangay 4", role: "Client", status: "Inactive", contact: "09081231234", dateCreated: "07-21-2020" },
  { fullName: "Aira Villanueva", age: 28, sex: "F", address: "Barangay 1", role: "Client", status: "Active", contact: "09991239876", dateCreated: "02-12-2023" },
  { fullName: "Kenji Tanaka", age: 36, sex: "M", address: "Barangay 5", role: "Client", status: "Active", contact: "09351234567", dateCreated: "09-30-2022" },
];

const socialWorkersData = [
  { name: "Shinobu Kocho", age: 29, address: "Barangay 3", action: true },
  { name: "Kie Kamado", age: 31, address: "Barangay 1", action: true },
  { name: "Giyu Tomioka", age: 35, address: "Barangay 4", action: true },
  { name: "Mitsuri Kanroji", age: 27, address: "Barangay 2", action: true },
  { name: "Tengen Uzui", age: 33, address: "Barangay 5", action: true },
];

const rankingDivisions = [
  { name: "DPSD Division", total: 55 },
  { name: "RPSD Division", total: 2 },
  { name: "OSCA/PDAO Division", total: 1 },
];

const rankingBarangays = [
  { name: "Barangay 1", total: 18 },
  { name: "Barangay 2", total: 14 },
  { name: "Barangay 3", total: 11 },
  { name: "Barangay 4", total: 9 },
  { name: "Barangay 5", total: 7 },
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
    const headers = ["Full Name", "Age", "Sex", "Address", "Role", "Status", "Contact Details", "Date of Created", "Action"];
    headers.forEach(h => {
      const th = document.createElement("th");
      th.textContent = h;
      tableHeadRow.appendChild(th);
    });

    const filtered = clientsData.filter(r =>
      r.fullName.toLowerCase().includes(query) ||
      r.address.toLowerCase().includes(query) ||
      r.role.toLowerCase().includes(query) ||
      r.status.toLowerCase().includes(query)
    );

    filtered.forEach(row => {
      const tr = document.createElement("tr");

      tr.innerHTML = `
        <td>${row.fullName}</td>
        <td>${row.age}</td>
        <td>${row.sex}</td>
        <td>${row.address}</td>
        <td>${row.role}</td>
        <td>
          <span style="display:inline-flex;align-items:center;gap:6px;">
            <span style="width:10px;height:10px;border-radius:999px;background:${row.status === "Active" ? "#16a34a" : "#9ca3af"};display:inline-block;"></span>
            ${row.status}
          </span>
        </td>
        <td>${row.contact}</td>
        <td>${row.dateCreated}</td>
        <td class="action-btns">
          <button class="btn-edit" type="button">Edit</button>
          <button class="btn-show" type="button">Show</button>
        </td>
      `;

      tableBody.appendChild(tr);
    });
  } else {
    const headers = ["Name", "Age", "Address", "Action"];
    headers.forEach(h => {
      const th = document.createElement("th");
      th.textContent = h;
      tableHeadRow.appendChild(th);
    });

    const filtered = socialWorkersData.filter(r =>
      r.name.toLowerCase().includes(query) ||
      r.address.toLowerCase().includes(query)
    );

    filtered.forEach(row => {
      const tr = document.createElement("tr");

      tr.innerHTML = `
        <td>${row.name}</td>
        <td>${row.age}</td>
        <td>${row.address}</td>
        <td class="action-btns">
          <button class="btn-show" type="button">Show</button>
        </td>
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
const tabBarangays = document.getElementById("tabBarangays");
const rankList = document.getElementById("rankList");

let currentRank = "divisions";

function renderRankings() {
  if (!rankList) return;
  rankList.innerHTML = "";

  const list = currentRank === "divisions" ? rankingDivisions : rankingBarangays;

  list.forEach(item => {
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
  currentRank = "divisions";
  tabDivisions.classList.add("active");
  tabBarangays.classList.remove("active");
  renderRankings();
});

tabBarangays?.addEventListener("click", () => {
  currentRank = "barangays";
  tabBarangays.classList.add("active");
  tabDivisions.classList.remove("active");
  renderRankings();
});


// ===== CANVASJS CHART =====
window.addEventListener("load", () => {
  // render table + rankings first
  setActiveTable("clients");
  renderRankings();

  // read totals from the cards so they always match
  const dpsd = Number(document.getElementById("dpsdTotal")?.textContent || 0);
  const osca = Number(document.getElementById("oscaTotal")?.textContent || 0);
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
        { label: "OSCA/PDAO", y: osca, indexLabel: String(osca) },
        { label: "DPSD", y: dpsd, indexLabel: String(dpsd) },
      ]
    }]
  });

  chart.render();
});
