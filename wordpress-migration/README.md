# KOVA WordPress migration — Phase 1

**Source:** unchanged `kova-preview` static site at `1a1b471`.
**Target:** Blocksy + Elementor Free 4.3.4, with Companion 2.1.58 and Fluent Forms 6.2.15 already present on the protected staging site. No staging/live changes performed. Inquiry integration is out of scope.

## Deliverables

- `templates/kova-home.json`: native editable Home template
- `templates/kova-cat-products.json`: native editable Cat Products template, eight native Nested Accordions
- `assets/images/`: 14 independent WebP files extracted/rendered from existing preview imagery; no new product imagery generated
- `assets/media-manifest.json`, `assets/MEDIA-LIST.md`: 18 Image Widget positions mapped to assets and alt text
- `assets/kova-logo.png`: existing wordmark rendered as a media-compatible PNG for Blocksy
- `assets/fonts/`: existing DM Serif Display and DM Sans with licence files
- `kova-elementor.css`: approximately 15 KB, scoped two-page layout/style layer for Elementor Free
- `导入指南.md`: Chinese import, media assignment and acceptance instructions
- `Blocksy设置.md`: header/footer, palette, page-template and link settings for approval
- `validation/`: actual source/PHP import results and limited native-render screenshots
- `tools/build_templates.py`, `tools/validate_package.py`: reproducible conversion and package checks

No HTML widget, iframe, shortcode, Pro widget or embedded full-page HTML is used. Paragraph and inline text content is in native Text Editor widgets; headings, buttons and images are separate native widgets.

## Media assignment is required

Delivered Image Widgets have empty media URLs/IDs. Upload the independent files to WordPress Media Library and select them using the Navigator labels `图片：filename.webp`. This avoids claiming that an ordinary JSON import can automatically retrieve images from a password-protected site or install attachments from a local directory. Until that step is complete, imported image widgets are blank.

## Validation boundary

Both templates were successfully imported with the actual Elementor 4.3.4 PHP template-library importer in isolated WordPress 7.1.3 / PHP 8.4. Native PHP rendering and supplied CSS were checked at 1440px, 768px and 390px, with locally assigned media attachments.

This is **not** a fully validated Elementor distribution/editor or Blocksy staging migration. Compiled plugin frontend/editor assets were unavailable because the official download was blocked; local source also produced deprecation/CLI context notices. Editor save/reopen, complete plugin JS, actual Blocksy and staging visual/editability checks remain outstanding. Details are in `validation/README.md`.

The two draft pages should be imported and approved before preparing the other five. Links to those five pages are reserved WordPress slugs, not completed destinations. No form activation, menu publication, homepage assignment, theme activation, staging push or live deployment is included.
