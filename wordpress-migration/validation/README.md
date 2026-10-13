# Validation scope

## Source and local environment

- Static reference branch: `kova-preview`, commit `1a1b471`.
- Elementor official annotated tag `4.3.4`, commit `840f5f54b774b8a04587501d9cd23706dc700024`.
- Local WordPress tag `7.1.3`, commit `fc9832bef919c2a08541248db23af90d14ffb082`.
- Local PHP 8.4; isolated SQLite test database (not the SiteGround database).
- No Pro, Blocksy or Fluent Forms was installed or configured in the local fixture. No staging/live access was performed.

## Passed

The actual Elementor 4.3.4 PHP library `Source_Local::import_template()` imported both JSON files. Native containers, headings, text editors, images, buttons and nested accordions were available. The local test then manually associated Media Library attachment IDs, using the same assets supplied in this package, and rendered the actual Elementor PHP components. See `php-import-report.json`.

The delivered JSON keeps image IDs/URLs empty for safe manual media selection on the password-protected staging site. Local test attachment IDs, URLs, login credentials and database files are not part of the delivery. The local preview validates rendered page content and scoped CSS; it does not reproduce the Blocksy header/footer.

## Not passed / not yet tested

The official WordPress plugin installation archive download returned HTTP 403 from this environment. The checked-out official source lacks compiled frontend/editor assets. Therefore no full Elementor editor session, drag/drop editing, save/reopen workflow or complete plugin JS interaction was verified. The source/PHP validation must not be described as a fully validated production-plugin installation.

The local source emitted PHP 8.4 deprecation notices and document-status warnings in the CLI test context, while imports and native rendering completed. This remains a limitation, not a claimed clean complete WordPress compatibility run.

Real Blocksy theme version, WordPress/PHP version, password-protected media selection, cached CSS, page-template integration, responsive editor controls, and staging visual/editability approval remain required. Nested Accordion settings and PHP markup were checked; the full plugin's JavaScript/accessibility behavior still needs staging testing.

## Reproducible package checks

Run `python3 wordpress-migration/tools/validate_package.py` from the repository. It checks JSON shape, native widget allowlist, collapsed accordions, per-image labels/files, approved copy, WordPress link targets and preservation of the static source. This structural check is additional evidence, not a substitute for a real Elementor import.
