# Phase 2 validation and practical limits

Base: `57477f9461c52a3b35d1fd67edece9efcfe747ee` (the verified 0.1.2 menu fix). Development is on an independent `wordpress-theme-phase2` branch. Static files and backups remain unchanged. No staging/live access, deployment, database connection or mail credentials were used.

## Executed locally

WordPress **7.1.3**, PHP **8.4.24**, **ACF Free 6.8.10**, SQLite local test integration, Chromium/Playwright. Elementor was disabled. These exact results do not establish compatibility with every older declared minimum version or SiteGround/MySQL configuration.

- First isolated Phase 2 import: **5 draft pages, 8 Dog categories, 12 media attachments**. Second import: **0 new pages/categories/media**.
- Conflicting existing slug was detected before any import. Explicit matching-page association required confirmation and preserved values. Existing empty text, edited Dog description, visibility and sort order survived repetition.
- Exact original Dog category titles, descriptions, product options/customization and 1–8 order verified against the static reference. Images are separate attachments, not Cat images.
- Snapshot comparison preserved Home/Cat posts and metadata, all eight Cat categories, and `show_on_front`, `page_on_front`, `page_for_posts`, permalink structure and the homepage-tool completion marker.
- Phase 1 importer remains restricted to its original source data, not the merged Phase 2 manifest. Phase 1 content tests and both 0.1.2 homepage-tool tests passed after integration.
- PHP syntax checks and both vanilla JavaScript files' syntax checks passed.
- **Actual ACF admin UI** tested on temporary local-only pages for all five templates: textarea save reflected on the frontend; all four pages with images saved a Media Library selection and changed the frontend image URL/style. Contact displayed its integration fields and an honest unconfigured slot. Temporary test pages were removed; imported pages were not edited by that UI test.
- **21 page/viewport combinations** (seven pages × desktop 1440, tablet 768, mobile 390) passed. Six non-Contact pages matched original rendered main copy and main sections/header/footer/grid/card/headings within **1.1 CSS px**. Image pixel comparisons had mean RGB difference <6/255, including all eight independently cropped Dog tiles. WebP re-encoding accounts for small pixel differences.
- Contact hero/sidebar text and geometry preserved; sidebar stretching/page height naturally differ while there is no configured form. No fake/static submission form is rendered.
- All rendered local navigation/button URLs returned HTTP 200; anchor targets exist; none targeted `/reference/`. Category context reached `/contact/`. Mobile menu/Escape, native keyboard accordions, hover/zoom, process focus, scroll reveal and reduced motion passed. No frontend JS exceptions or horizontal overflow at the tested sizes.
- Fluent Forms integration **contract** checked with an explicitly mocked provider: missing/disabled/nonexistent/unpublished/Demo cases rejected, explicit enabled valid integer ID delegated to shortcode, privacy displayed. This is **not** a real Fluent Forms installation/submission/email test.

## Fluent Forms limits

The user confirmed staging2 currently has only **Contact Form Demo ID 1**. No ID is prefilled, no form is automatically created, and no submission is enabled. A real formal inquiry form must be created and configured by the owner using the guide.

Fluent Forms **6.2.15** is not available in the local fixture. Official plugin ZIP download requests were rejected by the execution environment (HTTP 403). An upstream 6.2.14 source checkout was inspected to confirm shortcode/data APIs and CSS classes; it was **not installed or shipped**, and it does not count as runtime validation of 6.2.15. Actual form appearance, validation, duplicate-click/spam handling, Entries receipt and authenticated email delivery remain owner acceptance tests in staging2.

## Remaining staging checks

- Current WordPress/PHP/ACF versions, MySQL, uploads and thumbnail generation, SiteGround cache/image optimization, password protection and rewrite/permalink routing.
- Existing manually created menus/links to unrelated old WordPress pages. Known same-site static references are mapped at render time without overwriting their database values; unrelated custom destinations need explicit owner review.
- Safari/iOS, replacement images with very different framing, arbitrary long new copy, and real Fluent Forms containers/notifications.
- Privacy wording must match the chosen real form/data-handling configuration. No mail delivery or retention guarantee is claimed.

## Reproduce local tests

Use an isolated local WordPress fixture, install both updated packages and ACF Free, import Phase 2, publish the five pages only locally, retain imported Home as front page, enable pretty permalinks and a local PHP router. Do not run edit tests against staging/live.

```sh
KOVA_WP_ROOT=/path/to/local-wp php wordpress-phase1/tests/check_phase2_content.php
KOVA_WP_ROOT=/path/to/local-wp php wordpress-phase1/tests/check_contact_contract.php
STATIC_BASE=http://127.0.0.1:8001 WP_BASE=http://127.0.0.1:8101 python wordpress-phase1/tests/check_phase2_browser.py
KOVA_WP_ROOT=/path/to/local-wp KOVA_PHP=/path/to/php WP_BASE=http://127.0.0.1:8101 python wordpress-phase1/tests/check_phase2_admin.py
```

Playwright/system Chromium and Pillow are development-only dependencies. The admin test creates short-lived local cookies in memory and temporary fixture pages, edits them, then removes only those test records. No cookies, SMTP secrets or staging credentials are included in the ZIPs or repository.
