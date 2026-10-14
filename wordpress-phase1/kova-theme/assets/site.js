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
      if (!response.ok) {
        const failure = new Error('Unconfirmed receipt');
        failure.status = response.status;
        throw failure;
      }
      if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('Unconfirmed receipt');
      const result = await response.json();
      if (result.received !== true) throw new Error('Unconfirmed receipt');
      status.textContent = 'Your inquiry has been accepted for sending to KOVA. Thank you for sharing your project requirements.';
      form.reset();
      if (requested) document.querySelector('#selected-category-note').hidden = true;
    } catch (error) {
      if (error.status === 422) {
        status.textContent = 'Please review your required fields, email address and input lengths. Your entries are still here.';
      } else if (error.status === 429) {
        status.textContent = 'Too many attempts. Please wait before trying again, or email info@kovasourcing.com directly. Your entries are still here.';
      } else {
      status.textContent = error.name === 'AbortError'
        ? 'We could not confirm receipt in time. Your entries are still here. Please try again or email info@kovasourcing.com directly.'
        : 'We could not confirm that your inquiry was received. Your entries are still here. Please try again or email info@kovasourcing.com directly.';
      }
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

// Progressive scroll reveal: content stays visible without JS or observer support.
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
let revealObserver;
const revealSections = [...document.querySelectorAll('main > section')];
function showSection(section) {
  if (!section) return;
  section.classList.remove('motion-reveal-pending');
  revealObserver?.unobserve(section);
}
function showAllSections() {
  revealObserver?.disconnect();
  revealSections.forEach(showSection);
}
if (!reducedMotion.matches && 'IntersectionObserver' in window) {
  revealObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => { if (entry.isIntersecting) showSection(entry.target); });
  }, {threshold: 0, rootMargin: '0px 0px 80px 0px'});
  revealSections.forEach(section => {
    if (section.getBoundingClientRect().top >= window.innerHeight) {
      section.classList.add('motion-reveal', 'motion-reveal-pending');
      revealObserver.observe(section);
    }
  });
}
reducedMotion.addEventListener('change', event => { if (event.matches) showAllSections(); });
window.addEventListener('beforeprint', showAllSections);
document.addEventListener('focusin', event => showSection(event.target.closest('main > section')));
window.addEventListener('hashchange', () => {
  const target = document.getElementById(location.hash.slice(1));
  showSection(target?.closest('main > section'));
});
// Readable process panels gain keyboard focus without pretending to be buttons.
document.querySelectorAll('.work-timeline > li').forEach(step => { step.tabIndex = 0; });

// Inline validation appears only after field interaction; no submission is enabled here.
if (form) {
  const fields = [...form.querySelectorAll('input:not([name="website"]), select, textarea')];
  function validateField(field) {
    const empty = field.required && !field.value.trim();
    const invalid = empty || !field.validity.valid;
    const id = `feedback-${field.name}`;
    let feedback = document.getElementById(id);
    if (invalid) {
      if (!feedback) {
        feedback = document.createElement('span');
        feedback.id = id;
        feedback.className = 'field-feedback';
        feedback.setAttribute('aria-live', 'polite');
        field.insertAdjacentElement('afterend', feedback);
      }
      feedback.textContent = empty ? 'Please complete this field.'
        : field.type === 'email' ? 'Please enter a valid email address.' : 'Please check this field.';
      field.setAttribute('aria-invalid', 'true');
      const descriptions = new Set((field.getAttribute('aria-describedby') || '').split(' ').filter(Boolean));
      descriptions.add(id);
      field.setAttribute('aria-describedby', [...descriptions].join(' '));
    } else {
      feedback?.remove();
      field.removeAttribute('aria-invalid');
      const descriptions = (field.getAttribute('aria-describedby') || '').split(' ').filter(value => value && value !== id);
      if (descriptions.length) field.setAttribute('aria-describedby', descriptions.join(' '));
      else field.removeAttribute('aria-describedby');
    }
  }
  fields.forEach(field => {
    field.addEventListener('blur', () => validateField(field));
    field.addEventListener('invalid', () => validateField(field));
    field.addEventListener('input', () => { if (field.hasAttribute('aria-invalid')) validateField(field); });
    field.addEventListener('change', () => { if (field.hasAttribute('aria-invalid')) validateField(field); });
  });
  form.addEventListener('reset', () => {
    fields.forEach(field => {
      document.getElementById(`feedback-${field.name}`)?.remove();
      field.removeAttribute('aria-invalid');
      const descriptions = (field.getAttribute('aria-describedby') || '').split(' ').filter(value => value && value !== `feedback-${field.name}`);
      if (descriptions.length) field.setAttribute('aria-describedby', descriptions.join(' '));
      else field.removeAttribute('aria-describedby');
    });
  });
}
