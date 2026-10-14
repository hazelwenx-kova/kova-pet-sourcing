# KOVA custom WordPress theme — Phase 2

Current theme/plugin packages are 0.2.0, adding five fixed editable pages to the verified Phase 1 build and preserving the 0.1.2 homepage tool. Folder names remain unchanged for safe WordPress replacement updates.

Read [Phase 2 Chinese guide](PHASE2-GUIDE-ZH.md) and [Phase 2 validation](PHASE2-VALIDATION.md) first. Historical Phase 1 notes below describe the original milestone, not the current final navigation.

New development files: `tools/build_phase2.py`, `kova-content/data/phase2-content.json`, `phase2-import.php`, `contact-integration.php`, five PHP templates, eight independent Dog image assets, Contact-only CSS/JS, and local tests. Do **not** rerun the Phase 1 generator to produce Phase 2: its templates/field keys/schema must remain unchanged.

Contact uses a manually selected, explicitly enabled real Fluent Forms shortcode. Demo form ID 1 is not configured. No static submission handler or SMTP service is enabled by these packages.

---

# KOVA custom WordPress theme — Phase 1

Fixed PHP layouts for Home and Cat Products, derived from the approved static `kova-preview` reference at `ae6327e5de6e2a0a0d0111a4e78c06ec33c08c33`.

- `kova-theme`: standalone theme, shared navigation/footer, original CSS/JS/local fonts and design assets, fixed editable PHP templates.
- `kova-content`: non-public sourcing-category CPT, Cat/Dog taxonomy, ACF Free local field groups, capability/nonce-protected, staging-only incremental importer.
- `kova-content/data/initial-content.json`: original text, eight Cat categories in original order, independent image references and field schema. Runtime defaults, not remote executable input.
- `tools/build_reference.py`: development-only generator; BeautifulSoup4/Pillow required. Do not run against a changed static source without reviewing schema changes. Existing imported fields are never migrated destructively by this tool.
- `tests`: local browser/layout/image, ACF admin and import/edit-preservation checks.
- `packages`: generated WordPress upload ZIPs. Rebuild with `python wordpress-phase1/tools/package.py` from repository root after final checks.

Requires WordPress 6.6+ and PHP 8.1+ by declared minimum; tested versions are documented separately. ACF Free must be installed independently for the editing UI. No ACF Pro field types, Elementor dependency, build step on hosting, external font requests or animation framework.

Import uses stable `_kova_seed_key` markers on pages, categories and attachments and fills only missing metadata. It preserves existing values (including explicit empty text), page status, category order and visibility. Concurrent runs are locked. Partial runs can be retried; trash/missing-file cases produce actionable errors rather than duplicating records. It is not a database synchronization or overwrite/reset tool. Imported pages are drafts; categories have no public archives or product detail pages.

Theme and plugin folders must retain the names `kova-theme` and `kova-content`. Keep the companion plugin installed so content types survive future theme changes. Site content remains in WordPress's database/uploads; deployment ZIPs are not backups of subsequent owner edits.

Other five pages are clearly scoped static references inside the theme. They link back to the WordPress installation through a read-only routing query. Contact submission stays disabled. They will be replaced by WordPress templates in a later phase. No production redirects are installed in Phase 1.

See [Chinese installation/editing guide](IMPORT-GUIDE-ZH.md) and [validation evidence](VALIDATION.md).
