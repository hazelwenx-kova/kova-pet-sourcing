'use strict';
// Category context only. Fluent Forms owns validation, AJAX submission, spam checks and server messages.
document.addEventListener('DOMContentLoaded', () => {
  const container = document.querySelector('.kova-fluent-wrapper');
  if (!container) return;
  const requested = (new URLSearchParams(location.search).get('category') || '').slice(0, 160);
  if (!requested) return;
  const note = container.querySelector('#kova-selected-category');
  if (note) { note.textContent = `Selected sourcing category: ${requested}`; note.hidden = false; }
  const category = container.querySelector('select[name="category"]');
  if (category) {
    const normalized = requested.replaceAll(' and ', ' & ');
    const shared = ['Pet Beds & Comfort','Feeding & Drinking Accessories','Toys & Enrichment','Grooming & Care','Travel & Carriers'];
    const value = normalized === 'Both' || shared.includes(normalized) ? 'Both'
      : /^cat(?:\s|$)/i.test(normalized) ? 'Cat Products'
      : /^dog(?:\s|$)/i.test(normalized) || normalized === 'Collars, Harnesses & Leashes' ? 'Dog Products' : 'Other / Not Sure';
    if ([...category.options].some(option => option.value === value)) {
      category.value = value; category.dispatchEvent(new Event('change', {bubbles:true}));
    }
  }
});
