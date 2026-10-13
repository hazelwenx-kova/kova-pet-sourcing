# KOVA Sourcing

A responsive, seven-page English website built with HTML, CSS and vanilla JavaScript. No build step or runtime dependencies. This is a review milestone; no live site has been modified.

## Local preview

From this checkout:

```sh
python3 -m http.server 8001 --bind 127.0.0.1
```

Open the local server in your browser. All pages also work as static files. Stop the server with Ctrl+C.

## Structure

- `index.html`: homepage
- `cat-products.html`: six cat sourcing categories
- `dog-products.html`: six dog sourcing categories
- `about.html`: independent Shenzhen-based service positioning
- `other-pet-products.html`: compatibility redirect to Dog Products
- `sourcing-services.html`: seven coordination services
- `how-it-works.html`: five-stage process
- `contact.html`: inquiry preparation form
- `assets/styles.css`, `assets/site.js`: shared styling and behavior
- `assets/images/`: optimized local visual concept images and social sharing image
- `sitemap.xml`, `robots.txt`: search discovery

Headers and footers are static HTML repeated on each page. Update all seven when changing shared navigation. There are no external fonts, scripts, trackers or image requests.

## Inquiry behavior and submission integration

The current form validates required fields and email format, preselects categories from category links, and downloads a plain-text inquiry file. It does **not** send email, upload data or store entries on a server. There is deliberately no invented contact email.

Before enabling real submissions:

1. Select a submission service or a SiteGround-hosted backend and configure the verified recipient address securely. Never put API keys or mail credentials in frontend JavaScript.
2. Implement server-side validation, length limits, rate limiting/spam protection, safe email construction and error handling. Do not trust browser validation alone.
3. Replace the download handler in `assets/site.js` with a request to the configured HTTPS endpoint. Disable duplicate submissions and show success only after the endpoint confirms receipt. Preserve entered text on errors.
4. Update the form button, preparation notice and privacy copy in `contact.html` to describe actual data processing, service providers, retention and a verified contact route.
5. Test successful receipt, validation rejection, endpoint failure and retry before publishing. Update any affected contact links or copy.

## Imagery and launch replacements

Cat and dog products have equal importance throughout the site. The homepage includes a seven-category Pet Products overview and equally sized Cat Products and Dog Products links. Both dedicated pages have six categories with expandable sourcing details. Materials and customization descriptions are possibilities to discuss, not confirmed supplier capabilities.

Headings use locally hosted DM Serif Display and body text uses DM Sans, sourced from the Google Fonts repository under the SIL Open Font License. License files are retained in `assets/fonts/`. No external font request is needed.

The redesign uses photorealistic AI-generated **review placeholders**, not third-party photographs or actual supplier products. Generation was used because licensed photo download requests were blocked by this environment’s network policy. No Scruffs text, photography, branding or code was used. On-page notices identify the conceptual imagery.

- The homepage hero uses equally sized cat and dog lifestyle panels from the category image sheets described below. Replace both with properly licensed photography before launch. `hero-concept.webp` is an unused retained asset from the earlier design.
- `assets/images/category-concepts.webp`: six equal panels, arranged 3 columns × 2 rows: cat beds, trees, toys, feeding, travel and grooming. Replace all six category visuals with licensed photography. The panels are shown using CSS background positioning in `assets/styles.css`; when replacing with individual files, change `.panel-0` through `.panel-5` to their own background images and `background-size: cover`.
- `assets/images/dog-concepts.webp`: four equal panels in a 2 × 2 grid: beds, walking, feeding and travel. Replace all four. Update `.dog-panel-0` through `.dog-panel-3` similarly.
- `assets/images/social-card.png`: social sharing card showing the balanced cat and dog hero. Refresh if imagery or branding changes.

The homepage reuses the cat and dog contact sheets to avoid extra hero image requests. Assets are served locally with no third-party trackers, fonts or image services. Uncompressed generated originals are retained outside the checkout in `/workspace/generated_images` and are not part of the deployment bundle.

Update accessible image labels, on-page concept notices and this documentation when final photos are added. Retain license and attribution records. Do not imply pictured products are stocked, owned, manufactured or supplied by KOVA. This review build is not ready for launch until final photography and inquiry handling are confirmed.

## SiteGround deployment

1. Review the site and confirm copy, imagery, domain and inquiry behavior. Connect a real submission endpoint if online inquiries are required.
2. Back up the existing `public_html` contents before uploading. Resolve conflicts with any existing homepage files without deleting unrelated content.
3. In SiteGround File Manager or via SFTP, upload the eight `.html` files, `assets/`, `robots.txt` and `sitemap.xml` into the domain's `public_html`. Do not upload `.git`, development tools, tests or documentation. No Node service or build is required.
4. Enable HTTPS through SiteGround's SSL settings and configure the preferred canonical host as `https://kovasourcing.com`. Confirm all pages and assets load over HTTPS.
5. Check navigation and mobile menu, form behavior, 404 responses, social image and sitemap on the deployed domain. Clear SiteGround caches after updates.

Canonical URLs, sitemap and Open Graph URLs currently use `https://kovasourcing.com`. Update these consistently if the final host differs. `robots.txt` permits indexing: use hosting access protection for a private staging copy and only publish to the production domain when ready. No SiteGround access or deployment was attempted here.

## Validation

A browser smoke check is included in `tests/check_site.py`. With the preview server running, execute `python3 tests/check_site.py` in an environment with Python Playwright and `/usr/bin/chromium` installed. These are development-only tools, not website dependencies.

The check verifies local links/assets, all seven pages at desktop (1440px), tablet (768px) and mobile (390px), image loading, horizontal overflow, mobile navigation and Escape handling, category preselection, required-field validation and inquiry file contents. JavaScript syntax is checked with `node --check assets/site.js`.
