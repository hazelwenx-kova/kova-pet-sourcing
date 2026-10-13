'use strict';
const toggle = document.querySelector('.menu-toggle');
const nav = document.querySelector('#navigation');
function closeMenu() { nav.classList.remove('open'); toggle.setAttribute('aria-expanded', 'false'); }
toggle.addEventListener('click', () => { const open = toggle.getAttribute('aria-expanded') !== 'true'; toggle.setAttribute('aria-expanded', String(open)); nav.classList.toggle('open', open); });
document.addEventListener('keydown', event => { if (event.key === 'Escape' && toggle.getAttribute('aria-expanded') === 'true') { closeMenu(); toggle.focus(); } });
document.addEventListener('click', event => { if (!nav.contains(event.target) && !toggle.contains(event.target)) closeMenu(); });
window.matchMedia('(min-width: 801px)').addEventListener('change', closeMenu);
document.querySelector('#year').textContent = new Date().getFullYear();
const form = document.querySelector('#inquiry-form');
if (form) {
  const requested = new URLSearchParams(location.search).get('category');
  const select = document.querySelector('#category');
  const match = [...select.options].find(option => option.value === requested || option.textContent.replaceAll(' & ', ' and ') === requested);
  if (match) select.value = match.value;
  form.addEventListener('submit', event => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    const labels = {name:'Name', company:'Company / Brand', email:'Email', country:'Country / Region', category:'Product Category', requirements:'Product Requirements', quantity:'Estimated Order Quantity', message:'Message'};
    const fields = [...new FormData(form)].map(([key, value]) => `${labels[key]}: ${String(value).trim() || 'Not specified'}`).join('\n\n');
    const blob = new Blob([`KOVA Sourcing — Project Inquiry\n\n${fields}\n\nPrepared locally. This inquiry has not been sent to KOVA.\n`], {type:'text/plain;charset=utf-8'});
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a'); link.href = url; link.download = 'kova-sourcing-inquiry.txt'; document.body.append(link); link.click(); link.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
    document.querySelector('#form-status').textContent = 'Your inquiry file is ready. It has not been sent to KOVA. Your entries remain here so you can review or edit them.';
  });
}
