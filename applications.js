// js/applications.js
const state = {
  data: [],
  filterStatus: '',
  filterType: ''
};

function fmtDate(iso) {
  try {
    const d = new Date(iso);
    return d.toLocaleString('lv-LV');
  } catch {
    return iso;
  }
}

function badgeForStatus(st) {
  const map = {
    'jauns': 'badge-new',
    'procesā': 'badge-inprogress',
    'apstiprināts': 'badge-approved',
    'atteikts': 'badge-declined'
  };
  return map[st] || 'badge-new';
}

function renderTable(rows) {
  const container = document.querySelector('#apps-cards');
  const empty = document.querySelector('#apps-empty');
  container.innerHTML = '';

  if (!rows.length) {
    empty.style.display = 'block';
    return;
  }
  empty.style.display = 'none';

  rows.forEach(r => {
    const card = document.createElement('div');
    card.className = 'application-card';
    card.innerHTML = `
      <h3>${r.animal_name} (${r.animal_type})</h3>
      <p><strong>Statuss:</strong> <span class="badge ${badgeForStatus(r.status)}">${r.status}</span></p>
      <p><strong>Pieteicējs:</strong> ${r.applicant_name || ''}</p>
      <p><strong>Ziņa:</strong> ${r.message || '-'}</p>
      <p class="muted">${fmtDate(r.created_at)}</p>
    `;
    container.appendChild(card);
  });
}

async function loadData() {
  const params = new URLSearchParams();
  if (state.filterStatus) params.set('status', state.filterStatus);
  if (state.filterType) params.set('type', state.filterType);

  try {
    const res = await fetch(`/app/my_applications.php?` + params.toString(), {
      headers: { 'Cache-Control': 'no-store' }
    });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const json = await res.json();
    if (!json.ok) throw new Error(json.error || 'Unknown error');
    state.data = json.data || [];
    renderTable(state.data);
  } catch (e) {
    console.error('Failed to load applications:', e);
    document.querySelector('#apps-error').style.display = 'block';
  }
}

function attachFilters() {
  const statusSel = document.querySelector('#filter-status');
  const typeSel = document.querySelector('#filter-type');
  statusSel.addEventListener('change', () => {
    state.filterStatus = statusSel.value;
    loadData();
  });
  typeSel.addEventListener('change', () => {
    state.filterType = typeSel.value;
    loadData();
  });

  document.querySelector('#refresh-btn').addEventListener('click', () => loadData());
}

document.addEventListener('DOMContentLoaded', () => {
  attachFilters();
  loadData();
});
