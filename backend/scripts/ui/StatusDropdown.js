(function () {
  class StatusDropdown {
    constructor(options = {}) {
      this.statuses = options.statuses || ['New', 'Confirmed', 'Completed', 'Cancelled'];
      this.#ensureStyles(); // inject scoped styles if missing
      this.root = this.#build();
      this.onSelect = null;
      this.isOpen = false;

      this.boundDocClick = (e) => { if (!this.root.contains(e.target)) this.close(); };
      this.boundResize = () => this.close();
      this.boundScroll = () => this.close();
      this.boundKey = (e) => { if (e.key === 'Escape') this.close(); };
    }

    #ensureStyles() {
      if (document.getElementById('status-dropdown-styles')) return;
      const css = `
      .status-dropdown{position:absolute;top:0;left:0;z-index:2000;background:#fff;border:1px solid #ddd;border-radius:10px;box-shadow:0 12px 24px rgba(0,0,0,.12);padding:6px;opacity:0;transform:translateY(-6px);pointer-events:none;transition:opacity .18s ease,transform .18s ease;min-width:180px}
      .status-dropdown.open{opacity:1;transform:translateY(0);pointer-events:auto}
      .status-dropdown .status-list{list-style:none;margin:0;padding:4px}
      .status-dropdown .status-item{padding:10px 12px;border-radius:8px;cursor:pointer;color:#2c3e50;user-select:none;transition:background-color .12s ease,color .12s ease;outline:none}
      .status-dropdown .status-item:hover,.status-dropdown .status-item:focus{background:#f2f6fb}
      .status-dropdown .status-item.is-current{background:#eaf2ff;font-weight:600}
      .status-dropdown .status-item[data-status="Cancelled"]{color:#7f8c8d}
      .status-dropdown .status-item[data-status="Cancelled"]:hover,.status-dropdown .status-item[data-status="Cancelled"]:focus{background:#eef2f3}
      `;
      const style = document.createElement('style');
      style.id = 'status-dropdown-styles';
      style.type = 'text/css';
      style.appendChild(document.createTextNode(css));
      document.head.appendChild(style);
    }

    #build() {
      const el = document.createElement('div');
      el.className = 'status-dropdown';
      el.setAttribute('role', 'menu');
      el.setAttribute('aria-hidden', 'true');
      el.innerHTML = `<ul class="status-list"></ul>`;
      document.body.appendChild(el);
      el.addEventListener('click', (e) => e.stopPropagation());
      return el;
    }

    #renderItems(currentStatus) {
      const ul = this.root.querySelector('.status-list');
      ul.innerHTML = '';
      this.statuses.forEach(s => {
        const li = document.createElement('li');
        li.className = 'status-item' + (s === currentStatus ? ' is-current' : '');
        li.setAttribute('role', 'menuitemradio');
        li.setAttribute('aria-checked', s === currentStatus ? 'true' : 'false');
        li.dataset.status = s;
        li.tabIndex = 0;
        li.textContent = s;
        li.addEventListener('click', () => {
          const cb = this.onSelect;
          this.close();
          if (typeof cb === 'function') cb(s);
        });
        li.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); li.click(); }
        });
        ul.appendChild(li);
      });
    }

    #addGlobalListeners() {
      setTimeout(() => {
        document.addEventListener('click', this.boundDocClick, { passive: true });
        window.addEventListener('resize', this.boundResize, { passive: true });
        window.addEventListener('scroll', this.boundScroll, { passive: true });
        document.addEventListener('keydown', this.boundKey);
      }, 0);
    }

    #removeGlobalListeners() {
      document.removeEventListener('click', this.boundDocClick);
      window.removeEventListener('resize', this.boundResize);
      window.removeEventListener('scroll', this.boundScroll);
      document.removeEventListener('keydown', this.boundKey);
    }

    open(anchorEl, currentStatus, onSelect) {
      this.close();

      this.onSelect = onSelect;
      this.#renderItems(currentStatus);

      const rect = anchorEl.getBoundingClientRect();
      const docY = window.scrollY || document.documentElement.scrollTop;
      const docX = window.scrollX || document.documentElement.scrollLeft;

      const GAP = 6;
      this.root.style.minWidth = `${Math.max(rect.width, 180)}px`;
      this.root.style.top = `${rect.bottom + docY + GAP}px`;

      // make visible to measure width, then align
      this.root.style.left = `-9999px`;
      this.root.classList.add('open');
      this.root.setAttribute('aria-hidden', 'false');

      const width = this.root.offsetWidth || 200;
      const preferRight = rect.right + width + 20 <= window.innerWidth;
      const left = preferRight ? rect.left + docX : (rect.right + docX - width);
      this.root.style.left = `${Math.max(8, left)}px`;

      this.isOpen = true;
      this.#addGlobalListeners();
    }

    close() {
      if (!this.isOpen && !this.root.classList.contains('open')) {
        this.#removeGlobalListeners();
        return;
      }
      this.root.classList.remove('open');
      this.root.setAttribute('aria-hidden', 'true');
      this.isOpen = false;
      this.#removeGlobalListeners();
    }
  }

  window.StatusDropdown = StatusDropdown;
})();