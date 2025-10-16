const state = {
  data: [],
  filterStatus: ""
};

function badgeForStatus(st) {
  const map = {
    "gaida apstiprinājumu": "badge-new",
    "apstiprinats": "badge-approved",
    "atteikts": "badge-declined"
  };
  return map[st] || "badge-new";
}

function renderCards(rows) {
  const list = document.querySelector("#apps-cards");
  const empty = document.querySelector("#apps-empty");
  const error = document.querySelector("#apps-error");
  list.innerHTML = "";

  if (!rows.length) {
    empty.style.display = "block";
    error.style.display = "none";
    return;
  }

  empty.style.display = "none";
  error.style.display = "none";

  rows.forEach(r => {
    const card = document.createElement("div");
    card.className = "application-card";
    card.innerHTML = `
      <div class="app-image">
        <img src="${r.image}" alt="${r.animal_name}">
        <span class="badge ${badgeForStatus(r.status)}">${r.status}</span>
      </div>
      <div class="app-body">
        <h3>${r.animal_name}</h3>
        <p><strong>Dzīvnieks:</strong> ${r.animal_type}</p>
        <p><strong>Datums:</strong> ${r.date} plkst. ${r.time}</p>
        <p><strong>Piezīmes:</strong> ${r.message || "—"}</p>
        <p class="muted">Pieteikts: ${new Date(r.created_at).toLocaleDateString("lv-LV")}</p>
        <button class="btn-cancel" data-id="${r.id}">❌ Atteikties</button>
      </div>
    `;
    list.appendChild(card);
  });

  // piesaistām pogām dzēšanas funkciju
  document.querySelectorAll(".btn-cancel").forEach(btn => {
    btn.addEventListener("click", async () => {
      const id = btn.dataset.id;
      if (!confirm("Vai tiešām vēlies atteikties no šī pieteikuma?")) return;

      try {
        const res = await fetch("app/delete_application.php", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "id=" + encodeURIComponent(id)
        });
        const json = await res.json();
        if (json.ok) {
          alert("Pieteikums veiksmīgi dzēsts.");
          loadData();
        } else {
          alert("Kļūda: " + json.error);
        }
      } catch (err) {
        alert("Neizdevās sazināties ar serveri.");
      }
    });
  });
}

async function loadData() {
  const params = new URLSearchParams();
  if (state.filterStatus) params.set("status", state.filterStatus);

  try {
    const res = await fetch("app/my_applications.php?" + params.toString(), {
      headers: { "Cache-Control": "no-store" }
    });
    const json = await res.json();
    if (!json.ok) throw new Error(json.error);
    state.data = json.data;
    renderCards(state.data);
  } catch (err) {
    console.error(err);
    document.querySelector("#apps-error").style.display = "block";
  }
}

document.addEventListener("DOMContentLoaded", () => {
  const statusFilter = document.getElementById("filter-status");
  const refreshBtn = document.getElementById("refresh-btn");

  statusFilter.addEventListener("change", () => {
    state.filterStatus = statusFilter.value;
    loadData();
  });

  refreshBtn.addEventListener("click", () => {
    loadData();
  });

  loadData();
});
