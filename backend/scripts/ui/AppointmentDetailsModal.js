(function () {
  function statusClass(s) {
    switch ((s || '').trim()) {
      case 'New': return 'status-new';
      case 'Confirmed': return 'status-in-progress';
      case 'Completed': return 'status-resolved';
      case 'Cancelled': return 'status-cancelled';
      default: return 'status-new';
    }
  }

  function buildModal(id) {
    var overlay = document.createElement('div');
    overlay.id = id;
    overlay.className = 'modal-overlay';
    overlay.setAttribute('aria-hidden', 'true');
    overlay.innerHTML =
      '<div class="modal-content details-modal" role="dialog" aria-modal="true" aria-labelledby="appointmentDetailsTitle">' +
      '  <button class="modal-close-btn" data-close aria-label="Close">&times;</button>' +
      '  <h3 id="appointmentDetailsTitle">Appointment Details</h3>' +
      '  <div class="form-group"><label>Name:</label><p data-f="name"></p></div>' +
      '  <div class="form-group"><label>Email:</label><p data-f="email"></p></div>' +
      '  <div class="form-group"><label>Number:</label><p data-f="phone"></p></div>' +
      '  <div class="form-group"><label>Date:</label><p data-f="date"></p></div>' +
      '  <div class="form-group"><label>Time:</label><p data-f="time"></p></div>' +
      '  <div class="form-group"><label>Purpose:</label><p data-f="purpose"></p></div>' +
      '  <div class="form-group"><label>Status:</label><p data-f="status"></p></div>' +
      '  <div class="form-group"><label>Message:</label><p data-f="message"></p></div>' +
      '  <div class="modal-actions">' +
      '    <button type="button" class="modal-btn cancel" data-close>Close</button>' +
      '  </div>' +
      '</div>';
    document.body.appendChild(overlay);
    return overlay;
  }

  function getRefs(overlay) {
    return {
      name: overlay.querySelector('[data-f="name"]'),
      email: overlay.querySelector('[data-f="email"]'),
      phone: overlay.querySelector('[data-f="phone"]'),
      date: overlay.querySelector('[data-f="date"]'),
      time: overlay.querySelector('[data-f="time"]'),
      purpose: overlay.querySelector('[data-f="purpose"]'),
      status: overlay.querySelector('[data-f="status"]'),
      message: overlay.querySelector('[data-f="message"]'),
      title: overlay.querySelector('#appointmentDetailsTitle')
    };
  }

  class AppointmentDetailsModal {
    constructor(options) {
      options = options || {};
      this.id = options.id || 'appointmentDetailsModal';
      this.overlay = document.getElementById(this.id) || buildModal(this.id);
      this.refs = getRefs(this.overlay);

      // Hidden by default
      this.overlay.style.display = 'none';

      var self = this;
      this.overlay.addEventListener('click', function (e) {
        if (e.target === self.overlay) self.close();
      });
      this.overlay.querySelectorAll('[data-close]').forEach(function (btn) {
        btn.addEventListener('click', function () { self.close(); });
      });
    }

    open(data) {
      data = data || {};
      var raw = data.appointment_datetime || '';
      var parts = raw.split(' ');
      var dateStr = parts[0] || '';
      var timeStr = parts[1] ? parts[1].slice(0, 5) : '';

      if (this.refs.name) this.refs.name.textContent = data.name || '';
      if (this.refs.email) this.refs.email.textContent = data.email || '';
      if (this.refs.phone) this.refs.phone.textContent = data.phone || data.number || '';
      if (this.refs.date) this.refs.date.textContent = dateStr;
      if (this.refs.time) this.refs.time.textContent = timeStr;
      if (this.refs.purpose) this.refs.purpose.textContent = data.purpose || '';

      if (this.refs.status) {
        const txt = (data.status || '').trim();
        this.refs.status.textContent = txt;
      }

      if (this.refs.message) this.refs.message.textContent = (data.message || '').trim();

      this.overlay.classList.add('active');
      this.overlay.setAttribute('aria-hidden', 'false');
      this.overlay.style.display = 'flex';
    }

    close() {
      this.overlay.classList.remove('active');
      this.overlay.setAttribute('aria-hidden', 'true');
      this.overlay.style.display = 'none';
    }
  }

  window.AppointmentDetailsModal = AppointmentDetailsModal;
})();