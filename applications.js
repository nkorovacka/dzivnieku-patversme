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
  const tbody = document.querySelector('#apps-table tbody');
  const empty = document.querySelector('#apps-empty');
  tbody.innerHTML = '';

  if (!rows.length) {
    empty.style.display = 'block';
    return;
  }
  empty.style.display = 'none';

  rows.forEach(r => {
    const tr = document.createElement('tr');

    tr.innerHTML = `
      <td>
        <div class="animal">
          <div class="animal__name">${r.animal_name}</div>
          <div class="animal__type">${r.animal_type}</div>
        </div>
      </td>
      <td>
        <div class="applicant">
          <div>${r.applicant_name}</div>
          <a href="mailto:${r.applicant_email}">${r.applicant_email}</a>
          ${r.applicant_phone ? `<div class="muted">${r.applicant_phone}</div>` : ''}
        </div>
      </td>
      <td>${r.shelter_branch || '-'}</td>
      <td><span class="badge ${badgeForStatus(r.status)}">${r.status}</span></td>
      <td>${fmtDate(r.created_at)}</td>
      <td class="message-cell" title="${r.message || ''}">
        ${r.message ? r.message.substring(0, 60) + (r.message.length > 60 ? '…' : '') : '-'}
      </td>
    `;

    tbody.appendChild(tr);
  });
}

async function loadData() {
  const params = new URLSearchParams();
  if (state.filterStatus) params.set('status', state.filterStatus);
  if (state.filterType) params.set('type', state.filterType);

  const res = await fetch(`/api/my_applications.php?` + params.toString(), {
    headers: { 'Cache-Control': 'no-store' }
  });
  const json = await res.json();
  if (!json.ok) {
    console.error(json);
    document.querySelector('#apps-error').style.display = 'block';
    return;
  }
  state.data = json.data;
  renderTable(state.data);
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
