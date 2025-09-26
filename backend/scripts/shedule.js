document.addEventListener('DOMContentLoaded', async () => {
    const datetimeSelect = document.getElementById('appointment_datetime');
    if (!datetimeSelect) return;

    // Placeholder
    let placeholder = datetimeSelect.querySelector('option[value=""]');
    if (!placeholder) {
        placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Select a Date and Time';
        placeholder.disabled = true;
        placeholder.selected = true;
    }
    datetimeSelect.innerHTML = '';
    datetimeSelect.appendChild(placeholder);

    // Helper to resolve API URL relative to this script
    function urlFromScript(rel) {
        const thisScript = Array.from(document.getElementsByTagName('script'))
            .find(s => (s.getAttribute('src') || '').includes('backend/scripts/shedule.js'));
        const base = thisScript ? new URL('.', thisScript.src) : new URL('.', window.location.href);
        return new URL(rel, base).toString();
    }

    async function fetchFreeSlots() {
        const apiUrl = urlFromScript('../api/appointCRUD.php?action=free_slots&days=14&startHour=7&endHour=20&offsetDays=1');
        try {
            const res = await fetch(apiUrl, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
            const raw = await res.text();
            const clean = raw.replace(/^\uFEFF/, '').trim();
            if (!res.ok) {
                console.warn('[Booking] free_slots failed', { status: res.status, body: clean.slice(0, 200) });
                return [];
            }
            try {
                return JSON.parse(clean);
            } catch (err) {
                console.warn('[Booking] free_slots JSON parse failed', err, clean.slice(0, 200));
                return [];
            }
        } catch (e) {
            console.warn('[Booking] free_slots network error', e);
            return [];
        }
    }

    // Initial population from server
    const slots = await fetchFreeSlots();
    slots.forEach(s => {
        const option = document.createElement('option');
        option.value = s.value;
        option.textContent = s.text + (s.booked ? ' (Booked)' : '');
        option.disabled = !!s.booked;
        datetimeSelect.appendChild(option);
    });

    const enabledCount = Array.from(datetimeSelect.options).filter(o => o.value && !o.disabled).length;
    if (enabledCount === 0) {
        const noOpt = document.createElement('option');
        noOpt.value = '';
        noOpt.textContent = 'No available slots. Please check later.';
        noOpt.disabled = true;
        datetimeSelect.appendChild(noOpt);
    }

    // Submit handler: only attach on public booking pages.
    // Admin sets window.DISABLE_SCHEDULE_SUBMIT = true to reuse slot population without double-submit.
    const form = document.getElementById('appointmentForm');
    if (form && !window.DISABLE_SCHEDULE_SUBMIT) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const nameEl = document.getElementById('name');
            const emailEl = document.getElementById('email');
            const phoneEl = document.getElementById('phone');
            const purposeEl = document.getElementById('purpose');
            const messageEl = document.getElementById('message');
            const dtEl = document.getElementById('appointment_datetime');

            const selectedValue = dtEl ? dtEl.value : '';
            const body = {
                name: nameEl ? nameEl.value : '',
                email: emailEl ? emailEl.value : '',
                phone: phoneEl ? phoneEl.value : '',
                purpose: purposeEl ? purposeEl.value : '',
                message: messageEl ? messageEl.value : '',
                appointment_datetime: selectedValue
            };

            console.log('[Booking] Creating appointment…', body);

            // Resolve helper
            function urlFromScript(rel) {
                const thisScript = Array.from(document.getElementsByTagName('script'))
                    .find(s => (s.getAttribute('src') || '').includes('backend/scripts/shedule.js'));
                const base = thisScript ? new URL('.', thisScript.src) : new URL('.', window.location.href);
                return new URL(rel, base).toString();
            }

            const createUrl = urlFromScript('../api/appointCRUD.php?action=create');

            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            let res, json;
            try {
                res = await fetch(createUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body),
                    cache: 'no-store'
                });
                const raw = await res.text();
                const clean = raw.replace(/^\uFEFF/, '').trim();
                console.log('[Booking] Raw response:', clean);
                try { json = JSON.parse(clean); } catch (parseErr) {
                    console.error('[Booking] JSON parse error:', parseErr, clean.slice(0, 200));
                    if (submitBtn) submitBtn.disabled = false;
                    return;
                }
            } catch (err) {
                console.error('[Booking] Network/parse error:', err);
                if (submitBtn) submitBtn.disabled = false;
                return;
            }

            console.log('[Booking] API JSON:', json);

            if (!res.ok) {
                if (res.status === 409) {
                    console.warn('[Booking] Time slot conflict (server):', json, { selectedValue });
                    // Probe server for exact matches of this datetime
                    try {
                        const probeUrl = urlFromScript('../api/appointCRUD.php?action=probe&dt=' + encodeURIComponent(selectedValue));
                        const pRes = await fetch(probeUrl, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
                        const pRaw = await pRes.text();
                        const pClean = pRaw.replace(/^\uFEFF/, '').trim();
                        const pJson = JSON.parse(pClean);
                        console.info('[Booking] Probe result:', pJson);
                    } catch (e) {
                        console.warn('[Booking] Probe failed:', e);
                    }

                    if (dtEl && dtEl.value) {
                        const opt = Array.from(dtEl.options).find(o => o.value === dtEl.value);
                        if (opt && !opt.disabled) {
                            opt.disabled = true;
                            if (!opt.textContent.endsWith(' (Booked)')) opt.textContent += ' (Booked)';
                        }
                    }
                } else {
                    console.error('[Booking] Create failed:', json);
                }
                if (submitBtn) submitBtn.disabled = false;
                return;
            }

            if (json.email_attempted) {
                console.log('[Booking] Email attempted; status:', json.email_receipt, 'time(ms):', json.email_time_ms, 'msgId:', json.email_messageId);
            } else {
                console.warn('[Booking] Email not attempted (no/invalid email).');
            }

            if (json.email_receipt === 'sent') {
                console.log('[Booking] Receipt sent ✅ Redirecting…');
                window.location.href = 'Homepage.php';
            } else if (json.email_receipt === 'skipped') {
                console.warn('[Booking] Receipt skipped (no valid email).');
                // No alert; stay on page and re-enable submit
                if (submitBtn) submitBtn.disabled = false;
            } else {
                console.error('[Booking] Receipt error ❌', json.email_error);
                // No alert; stay on page and re-enable submit
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    } else {
        if (!form) {
            console.warn('[Booking] #appointmentForm not found; submit handler not attached.');
        } else if (window.DISABLE_SCHEDULE_SUBMIT) {
            console.log('[Booking] Submit handler disabled by admin context.');
        }
    }
});