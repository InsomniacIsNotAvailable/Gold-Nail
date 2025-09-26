// appointments.js (DB-connected)

document.addEventListener('DOMContentLoaded', () => {
  const addAppointmentBtn = document.getElementById('addAppointmentBtn');
  const addAppointmentModal = document.getElementById('addAppointmentModal');
  const closeModalBtn = document.getElementById('closeModalBtn');
  const cancelAppointmentBtn = document.getElementById('cancelAppointmentBtn');
  const appointmentForm = document.getElementById('appointmentForm');
  const appointmentsTableBody = document.getElementById('appointmentsTableBody');
  const totalAppointmentsDisplay = document.getElementById('totalAppointments');
  const pendingAppointmentsDisplay = document.getElementById('pendingAppointments');

  const detailsModal = new window.AppointmentDetailsModal();
  const statusDropdown = new window.StatusDropdown();

  let totalAppointments = 0;
  let pendingAppointments = 0;

  function statusClass(s) {
    switch (s) {
      case 'New': return 'status-new';
      case 'Confirmed': return 'status-in-progress';
      case 'Completed': return 'status-resolved';
      case 'Cancelled': return 'status-cancelled';
      default: return 'status-new';
    }
  }

  function updateMetrics() {
    totalAppointmentsDisplay.textContent = totalAppointments;
    pendingAppointmentsDisplay.textContent = pendingAppointments;
  }
  function recalcMetrics(rows) {
    totalAppointments = rows.length;
    pendingAppointments = rows.filter(r => r.status === 'New' || r.status === 'Confirmed').length;
    updateMetrics();
  }

  function renderRow(row) {
    const tr = document.createElement('tr');
    tr.dataset.id = row.id;

    const raw = row.appointment_datetime || '';
    const parts = raw.split(' ');
    const dateStr = parts[0] || '';
    const timeStr = parts[1] ? parts[1].slice(0, 5) : '';

    tr.innerHTML = `
      <td>${dateStr}</td>
      <td>${timeStr}</td>
      <td><div class="customer-cell"><span class="customer-name">${row.name ?? ''}</span></div></td>
      <td>${row.purpose ?? ''}</td>
      <td><div class="status-badge ${statusClass(row.status)}">${row.status}</div></td>
      <td>
        <button class="action-btn details-btn">Details</button>
        <button class="action-btn edit-btn">Edit</button>
        <button class="action-btn delete-btn">Delete</button>
      </td>
    `;
    return tr;
  }

  async function loadAppointments() {
    try {
      const rows = await window.AppointmentsApi.list();
      if (!Array.isArray(rows)) {
        console.error('Load failed: list did not return an array', rows);
        return;
      }
      appointmentsTableBody.innerHTML = '';
      rows.forEach(r => appointmentsTableBody.appendChild(renderRow(r)));
      recalcMetrics(rows);
    } catch (e) {
      console.error('Load failed:', e);
    }
  }

  addAppointmentBtn?.addEventListener('click', () => {
    addAppointmentModal.style.display = 'flex';
    addAppointmentModal.classList.add('active');
  });

  function closeModal() {
    addAppointmentModal.classList.remove('active');
    addAppointmentModal.style.display = 'none';
    appointmentForm.reset();
    const dt = document.getElementById('appointment_datetime');
    if (dt && !dt.querySelector('option[value=""]')) {
      const ph = document.createElement('option');
      ph.value = '';
      ph.disabled = true;
      ph.selected = true;
      ph.textContent = 'Select a Date and Time';
      dt.insertBefore(ph, dt.firstChild);
    }
  }
  closeModalBtn?.addEventListener('click', closeModal);
  cancelAppointmentBtn?.addEventListener('click', closeModal);

  appointmentForm?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const name = document.getElementById('name')?.value?.trim() || '';
    const email = document.getElementById('email')?.value?.trim() || '';
    const phone = document.getElementById('phone')?.value?.trim() || '';
    const appointment_datetime = document.getElementById('appointment_datetime')?.value || '';
    const purpose = document.getElementById('purpose')?.value || '';
    const message = document.getElementById('message')?.value || '';
    const payload = { name, email, phone, appointment_datetime, purpose, message };

    try {
      const created = await window.AppointmentsApi.create(payload);
      appointmentsTableBody.prepend(renderRow(created));
      totalAppointments += 1;
      if (created.status === 'New' || created.status === 'Confirmed') pendingAppointments += 1;
      updateMetrics();
      closeModal();
    } catch (e) {
      console.error('Create failed:', e);
    }
  });

  appointmentsTableBody?.addEventListener('click', async (event) => {
    const rowEl = event.target.closest('tr');
    if (!rowEl) return;
    const id = Number(rowEl.dataset.id);

    if (event.target.classList.contains('details-btn')) {
      try {
        const data = await window.AppointmentsApi.get(id);
        detailsModal.open(data);
      } catch (e) {
        console.error('Details failed:', e);
      }
    } else if (event.target.classList.contains('delete-btn')) {
      try {
        const badge = rowEl.querySelector('td:nth-child(5) div');
        const current = badge?.textContent || 'New';
        await window.AppointmentsApi.delete(id);
        if (current === 'New' || current === 'Confirmed') pendingAppointments -= 1;
        totalAppointments -= 1;
        rowEl.remove();
        updateMetrics();
      } catch (e) {
        console.error('Delete failed:', e);
      }
    } else if (event.target.classList.contains('edit-btn')) {
      const badge = rowEl.querySelector('td:nth-child(5) div');
      const current = badge?.textContent || 'New';
      statusDropdown.open(event.target, current, async (selected) => {
        if (!selected || selected === current) return;
        try {
          const updated = await window.AppointmentsApi.update(id, { status: selected });
          const wasPending = current === 'New' || current === 'Confirmed';
          const nowPending = updated.status === 'New' || updated.status === 'Confirmed';
          if (wasPending && !nowPending) pendingAppointments -= 1;
          if (!wasPending && nowPending) pendingAppointments += 1;
          badge.className = `status-badge ${statusClass(updated.status)}`;
          badge.textContent = updated.status;
          updateMetrics();
        } catch (e) {
          console.error('Update failed:', e);
        }
      });
    }
  });

  loadAppointments();
});

