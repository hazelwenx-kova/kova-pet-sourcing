'use strict';
const toggle = document.querySelector('.menu-toggle');
const nav = document.querySelector('#navigation');
function closeMenu() { nav.classList.remove('open'); toggle.setAttribute('aria-expanded', 'false'); }
toggle.addEventListener('click', () => { const open = toggle.getAttribute('aria-expanded') !== 'true'; toggle.setAttribute('aria-expanded', String(open)); nav.classList.toggle('open', open); });
document.addEventListener('keydown', event => { if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') { closeMenu(); toggle.focus(); } });
document.addEventListener('click', event => { if (!nav.contains(event.target) && !toggle.contains(event.target)) closeMenu(); });
window.matchMedia('(min-width: 801px)').addEventListener('change', closeMenu);
document.querySelector('#year').textContent = new Date().getFullYear();
// Configure the HTTPS endpoint in contact.html only after backend setup and testing.
// Contract: POST JSON; a 2xx JSON response containing {"received": true} confirms receipt.
const form = document.querySelector('#inquiry-form');
if (form) {
  const status = document.querySelector('#form-status');
  const button = document.querySelector('#send-inquiry');
  const requested = (new URLSearchParams(location.search).get('category') || '').slice(0, 160);
  const select = document.querySelector('#category');
  if (requested) {
    const shared = ['Pet Beds & Comfort', 'Feeding & Drinking Accessories', 'Toys & Enrichment', 'Grooming & Care', 'Travel & Carriers'];
    const normalized = requested.replaceAll(' and ', ' & ');
    if (normalized === 'Both' || shared.includes(normalized)) select.value = 'Both';
    else if (/^cat(?:\s|$)/i.test(normalized)) select.value = 'Cat Products';
    else if (/^dog(?:\s|$)/i.test(normalized) || normalized === 'Collars, Harnesses & Leashes') select.value = 'Dog Products';
    else select.value = 'Other / Not Sure';
    const note = document.querySelector('#selected-category-note');
    note.textContent = `Selected sourcing category: ${requested}`;
    note.hidden = false;
  }
  let endpoint = null;
  try {
    const configured = form.dataset.endpoint.trim();
    if (configured) {
      const candidate = new URL(configured);
      if (candidate.protocol === 'https:' && !candidate.username && !candidate.password && !candidate.hash) endpoint = candidate.href;
    }
  } catch { /* Invalid or absent configuration keeps submissions disabled. */ }
  button.disabled = !endpoint;
  document.querySelector('#submission-setup').hidden = Boolean(endpoint);
  let pending = false;
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (pending) return;
    if (!endpoint) {
      status.textContent = 'Online submissions are not enabled in this preview. No inquiry has been sent.';
      return;
    }
    // Whitespace-only required text is not a useful inquiry.
    for (const name of ['name', 'country', 'details']) {
      const field = form.elements.namedItem(name);
      field.setCustomValidity(field.value.trim() ? '' : 'Please complete this field.');
    }
    if (!form.reportValidity()) return;
    if (form.elements.namedItem('website').value.trim()) {
      status.textContent = 'Unable to send this inquiry. Please review the form and try again.';
      return;
    }
    pending = true;
    button.disabled = true;
    form.setAttribute('aria-busy', 'true');
    status.textContent = 'Sending your inquiry…';
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), 20000);
    const payload = Object.fromEntries([...new FormData(form)].map(([key, value]) => [key, String(value).trim()]));
    if (requested) payload.source_category = requested;
    try {
      const response = await fetch(endpoint, {
        method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        credentials: 'omit', body: JSON.stringify(payload), signal: controller.signal
      });
      if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) throw new Error('Unconfirmed receipt');
      const result = await response.json();
      if (result.received !== true) throw new Error('Unconfirmed receipt');
      status.textContent = 'Your inquiry has been received. Thank you for sharing your project requirements.';
      form.reset();
      if (requested) document.querySelector('#selected-category-note').hidden = true;
    } catch (error) {
      status.textContent = error.name === 'AbortError'
        ? 'We could not confirm receipt in time. Your entries are still here. Please try again or use the published business contact address once available.'
        : 'We could not confirm that your inquiry was received. Your entries are still here. Please try again or use the published business contact address once available.';
    } finally {
      clearTimeout(timer);
      pending = false;
      button.disabled = false;
      form.removeAttribute('aria-busy');
    }
  });
  for (const name of ['name', 'country', 'details']) {
    form.elements.namedItem(name).addEventListener('input', event => event.target.setCustomValidity(''));
  }
}
