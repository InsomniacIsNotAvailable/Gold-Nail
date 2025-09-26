/* Test Email Helper
 *
 * Usage:
 *   forceSendTestEmail('you@example.com');
 *   forceSendTestEmail('you@example.com', 'Custom Subject', '<p>Hi</p>', { text: 'Hi', secret: 'changeme' });
 */

(function attachForceSendTestEmail(global) {
  const DEFAULT_ENDPOINT = 'backend/api/test_send_email.php';

  function stripBom(s) {
    if (!s) return s;
    // Remove UTF-8 BOM if present
    if (s.charCodeAt(0) === 0xFEFF) return s.slice(1);
    // Also handle BOM expressed as actual character in some cases
    if (s[0] === '\uFEFF') return s.slice(1);
    return s;
  }

  async function parseJsonSafely(resp) {
    // Prefer text -> strip BOM -> JSON.parse to tolerate servers that send BOM
    const raw = await resp.text();
    const clean = stripBom(raw).trim();
    try {
      return JSON.parse(clean);
    } catch (e) {
      const snippet = clean.slice(0, 200);
      throw new Error('Failed to parse JSON response: ' + snippet);
    }
  }

  async function forceSendTestEmail(to, subject = 'Console Test Email', html = '<p>Console Test Email</p>', options = {}) {
    if (!to) throw new Error('Recipient email required');

    const {
      text = null,
      endpoint = DEFAULT_ENDPOINT,
      secret = null,
      headers = {},
      log = true
    } = options;

    const body = { to, subject, html };
    if (text) body.text = text;

    const finalHeaders = Object.assign(
      { 'Content-Type': 'application/json' },
      secret ? { 'X-Test-Secret': secret } : {},
      headers
    );

    const resp = await fetch(endpoint, {
      method: 'POST',
      headers: finalHeaders,
      body: JSON.stringify(body),
      cache: 'no-store'
    });

    const json = await parseJsonSafely(resp);

    if (log) console.log('[forceSendTestEmail] status:', resp.status, json);

    if (!resp.ok || json.ok === false) {
      const err = new Error('Email send failed: ' + (json.error || resp.status));
      err.response = json;
      throw err;
    }
    return json;
  }

  global.forceSendTestEmail = forceSendTestEmail;
})(typeof window !== 'undefined' ? window : this);