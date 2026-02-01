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

// Sample clients data
const clients = [
  { fullName: "Tanjiro Kamado", age: 16, sex: "Male", address: "123 Anime St.", program: "DPSD", contact: "09171234567", dateOfService: "2024-06-01" },
  { fullName: "Nezuko Kamado", age: 14, sex: "    Female", address: "123 Anime St.", program: "OSCA/PDAO", contact: "09179876543", dateOfService: "2024-06-02" },
  { fullName: "Zenitsu Agatsuma", age: 17, sex: "Male", address: "456 Manga Ave.", program: "DPSD", contact: "09172345678", dateOfService: "2024-06-03" },
  { fullName: "Inosuke Hashibira", age: 18, sex: "Male", address: "789 Shonen Rd.", program: "DPSD", contact: "09173456789", dateOfService: "2024-06-04" },
  // … add more records
];

let currentPage = 1;
const pageSize = 5;
let sortKey = null;
let sortAsc = true;
let searchTerm = "";

// Render table with filtering, sorting, pagination
function renderClients() {
  const tbody = document.querySelector("#clientsTable tbody");

  // filter by search term
  let data = clients.filter(c =>
    c.fullName.toLowerCase().includes(searchTerm) ||
    c.address.toLowerCase().includes(searchTerm) ||
    c.program.toLowerCase().includes(searchTerm) ||
    c.contact.toLowerCase().includes(searchTerm)
  );

  // sort
  if (sortKey) {
    data.sort((a, b) => {
      let valA = a[sortKey];
      let valB = b[sortKey];
      if (typeof valA === "string") valA = valA.toLowerCase();
      if (typeof valB === "string") valB = valB.toLowerCase();
      if (valA < valB) return sortAsc ? -1 : 1;
      if (valA > valB) return sortAsc ? 1 : -1;
      return 0;
    });
  }

  // pagination
  const totalPages = Math.ceil(data.length / pageSize) || 1;
  if (currentPage > totalPages) currentPage = totalPages;
  const start = (currentPage - 1) * pageSize;
  const paginated = data.slice(start, start + pageSize);

  // render rows
  tbody.innerHTML = "";
  paginated.forEach(c => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${c.fullName}</td>
      <td>${c.age}</td>
      <td>${c.sex}</td>
      <td>${c.address}</td>
      <td>${c.program}</td>
      <td>${c.contact}</td>
      <td>${new Date(c.dateOfService).toLocaleDateString()}</td>
      <td>
        <button onclick="showClient('${c.fullName}')">Show</button>
        <button onclick="editClient('${c.fullName}')">Edit</button>
      </td>
    `;
    tbody.appendChild(tr);
  });

  // update pagination info
  document.getElementById("pageInfo").textContent = `Page ${currentPage} of ${totalPages}`;
  document.getElementById("prevPage").disabled = currentPage === 1;
  document.getElementById("nextPage").disabled = currentPage === totalPages;
}

// Actions
function showClient(name) { alert("Showing details for " + name); }
function editClient(name) { alert("Editing record for " + name); }

// Pagination buttons
document.getElementById("prevPage").addEventListener("click", () => {
  if (currentPage > 1) { currentPage--; renderClients(); }
});
document.getElementById("nextPage").addEventListener("click", () => {
  currentPage++; renderClients();
});

// Sorting by clicking headers
document.querySelectorAll("#clientsTable thead th[data-key]").forEach(th => {
  th.addEventListener("click", () => {
    const key = th.dataset.key;
    if (sortKey === key) {
      sortAsc = !sortAsc;
    } else {
      sortKey = key;
      sortAsc = true;
    }
    renderClients();
  });
});

// Search input (live filtering)
document.getElementById("search").addEventListener("input", e => {
  searchTerm = e.target.value.toLowerCase();
  currentPage = 1; // reset to first page
  renderClients();
});

// Initial render
renderClients();

// Chart.js for bar chart
import Chart from "https://cdn.jsdelivr.net/npm/chart.js";

const ctx = document.getElementById("divisionChart").getContext("2d");
new Chart(ctx, {
  type: "bar",
  data: {
    labels: ["RPSD", "OSCA/PDAO", "DPSD"],
    datasets: [{
      label: "Clients Served",
      data: [750, 600, 1000],
      backgroundColor: ["#3b82f6", "#10b981", "#f59e0b"]
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { display: false }
    }
  }
});

// Tabs toggle
document.querySelectorAll(".tab").forEach(tab => {
  tab.addEventListener("click", () => {
    document.querySelectorAll(".tab").forEach(t => t.classList.remove("active"));
    tab.classList.add("active");

    const target = tab.dataset.tab;
    document.querySelectorAll(".tab-content").forEach(c => c.classList.add("hidden"));
    document.getElementById(target).classList.remove("hidden");
  });
});

// Recent activities data
let activities = [
  { user: "Kie Kamado", action: "Logged In", division: "DPSD", time: "Today 15:03" },
  { user: "Shinobu Kocho", action: "Logged Out", division: "PWD", time: "Today 15:01" },
  { user: "Shinobu Kocho", action: "Added New Client", division: "", time: "Today 15:00" }
];

// Render activities list
function renderActivities() {
  const list = document.getElementById("activitiesList");
  list.innerHTML = "";
  activities.forEach(act => {
    const li = document.createElement("li");
    li.innerHTML = `<strong>${act.user}</strong> ${act.action}${act.division ? " (" + act.division + ")" : ""} - ${act.time}`;
    list.appendChild(li);
  });
}

// Add new activity dynamically
function addActivity(user, action, division, time) {
  activities.unshift({ user, action, division, time }); // add to top
  if (activities.length > 10) activities.pop(); // keep list manageable
  renderActivities();
}

// Initial render
renderActivities();

// Example: simulate a new activity after 5 seconds
setTimeout(() => {
  addActivity("Nezuko Kamado", "Logged In", "OSCA/PDAO", "Today 15:05");
}, 5000);





