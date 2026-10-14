# Phase 1 validation and limits

## Executed locally

Environment: WordPress **7.1.3**, PHP **8.4.24**, **ACF Free 6.8.10** (upstream `AdvancedCustomFields/acf` checkout `fa4ceb7`), SQLite integration for the isolated local database, Chromium with Playwright. Elementor was disabled for these tests. No SiteGround access or credentials were used.

- PHP syntax: theme, templates, plugin, importer and PHP test passed.
- JavaScript syntax: original vanilla JavaScript passed; theme CSS/JS are byte-for-byte copies of the approved static version.
- Actual WP bootstrap, theme activation, ACF field registration and import completed. Two draft pages, eight categories and **14 independent media attachments** created on first import.
- Repeat import created **zero** pages/categories/media, preserving existing records. Additional tests confirmed copy edits, order and visibility survive reimport; anonymous import is rejected.
- All eight category titles, descriptions, product options, customization text and original order checked against the static source manifest. All referenced independent media files exist.
- Complete rendered Home/Cat main-page text matches the original static reference (whitespace normalized).
- Browser comparisons at **1440 × 1000**, **768 × 1024**, **390 × 844**: shared header/footer, main sections, grids, cards and headings match reference geometry within **1.1 CSS px**. Image tile pixel comparisons passed (mean RGB difference <6/255; cropped WebP images were re-encoded). This is local evidence, not a guarantee of exact rendering on every browser.
- Browser assets loaded, no frontend JS exceptions or horizontal overflow at those sizes; keyboard accordion operation, initially collapsed panels, mobile menu/Escape, scroll reveal and reduced motion passed.
- **Actual ACF admin UI tested:** edited headline text, saved and observed changed frontend content; selected another image through the native Media Library, saved and verified frontend image style changed; restored original content. No admin JS exceptions in that flow.
- Category inquiry links reached the disabled Contact reference with category context; remaining five static references returned HTTP 200. Contact notice is visible and submission remains disabled. No SMTP or inquiry email was sent.
- Original static files remain untouched on the migration branch; changes are contained in `wordpress-phase1/`.

## Not yet validated

- Your staging WordPress, PHP and ACF versions; SiteGround's MySQL environment, hosting restrictions, image thumbnail generation, cache/image optimization and password protection integration.
- Installation through your staging WordPress upload UI and visual rendering under its installed plugins.
- Safari/iOS and every possible viewport, arbitrary long replacement copy or very differently framed images. Layout templates are fixed; changed text length can naturally change section height.
- Production deployment, domain settings, live redirects, SMTP acceptance or delivery. These were not attempted.
- Full editable migration of Dog Products, Sourcing Services, How We Work, About and Contact. They remain static reference copies in Phase 1. The plugin supports Dog category records, but the Dog reference page does not read them yet.

Declared minimum versions in package headers (WP 6.6 / PHP 8.1) are design targets, **not executed compatibility tests**. Validate on the actual staging version before approving broader migration or launch.

## Repeatable local checks

Use an isolated local WordPress fixture with the theme, KOVA Content and ACF Free active. Import/publish the two pages and set the imported Home as front page **only in the fixture**. Keep the unchanged static reference server available.

```sh
KOVA_WP_ROOT=/path/to/local-wordpress php wordpress-phase1/tests/check_content.php
STATIC_BASE=http://127.0.0.1:8001 WP_BASE=http://127.0.0.1:8100 CAT_ID=79 python wordpress-phase1/tests/check_browser.py
KOVA_ADMIN_COOKIE_FILE=/tmp/ephemeral-local-auth.json WP_BASE=http://127.0.0.1:8100 HOME_ID=70 python wordpress-phase1/tests/check_admin.py
```

Use fixture-specific page IDs. Browser tests require Playwright/system Chromium and Pillow. Admin test uses a local-only, short-lived cookie file with `name`, `cookie`, `auth_name`, `auth`; no cookie/password/SMTP secret is included in this repository. The admin test temporarily edits and restores local Home content; do not run it against staging/live. The PHP content test is guarded to `WP_ENVIRONMENT_TYPE=local` and restores its temporary category edits in `finally`.
