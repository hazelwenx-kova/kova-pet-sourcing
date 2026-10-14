<?php
/** KOVA presentation only; sourcing content is owned by the companion plugin. */
defined('ABSPATH') || exit;
add_action('after_setup_theme', function () {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
    register_nav_menus(['primary' => 'KOVA primary navigation', 'footer' => 'KOVA footer navigation']);
});
add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('kova', get_template_directory_uri() . '/assets/styles.css', [], '0.2.0');
    wp_enqueue_script('kova', get_template_directory_uri() . '/assets/site.js', [], '0.2.0', ['strategy' => 'defer', 'in_footer' => true]);
    if (kova_current_key() === 'contact') {
        wp_enqueue_style('kova-contact-fluent', get_template_directory_uri().'/assets/contact-fluent.css', ['kova'], '0.2.0');
        wp_enqueue_script('kova-contact-fluent', get_template_directory_uri().'/assets/contact-fluent.js', [], '0.2.0', ['strategy'=>'defer','in_footer'=>true]);
    }
});
function kova_data(): array {
    if (function_exists('kova_content_data')) return kova_content_data();
    return ['pages' => [], 'global' => [], 'categories' => []];
}
function kova_page_id(string $key): int {
    $ids = get_posts(['post_type' => 'page', 'post_status' => ['publish','draft','private','pending'], 'meta_key' => '_kova_seed_key', 'meta_value' => 'page:' . $key, 'numberposts' => 1, 'fields' => 'ids']);
    return $ids ? (int) $ids[0] : 0;
}
function kova_current_key(): string {
    $id = get_queried_object_id();
    $key = str_replace('page:', '', (string) get_post_meta($id, '_kova_seed_key', true));
    if ($key !== '') return $key;
    foreach (array_keys(kova_data()['pages'] ?? []) as $page) {
        if (get_post_meta($id, '_wp_page_template', true) === 'templates/page-'.$page.'.php') return $page;
    }
    return '';
}
function kova_value(string $field, ?int $id = null, ?string $page = null) {
    $id = $id ?? get_queried_object_id();
    $page = $page ?? kova_current_key();
    if ($id === 0) { $id = kova_page_id('home'); $defaults = kova_data()['global'] ?? []; }
    else $defaults = kova_data()['pages'][$page]['fields'] ?? [];
    // Read raw values: no dependency on ACF formatting and deliberate empty edits are retained.
    if ($id && metadata_exists('post', $id, $field)) return get_post_meta($id, $field, true);
    return $defaults[$field]['default'] ?? '';
}
function kova_text(string $field, ?int $id = null): string {
    return wp_kses((string) kova_value($field, $id), ['br' => [], 'em' => [], 'strong' => [], 'span' => ['class' => []]]);
}
function kova_url(string $url): string {
    if (str_starts_with($url, '#') || preg_match('~^(https?://|mailto:|tel:)~i', $url)) return $url;
    [$file, $query] = array_pad(explode('?', $url, 2), 2, '');
    $map = ['index.html'=>'home', 'cat-products.html'=>'cat-products', 'dog-products.html'=>'dog-products', 'sourcing-services.html'=>'sourcing-services', 'how-it-works.html'=>'how-we-work', 'about.html'=>'about-kova', 'contact.html'=>'contact'];
    if (isset($map[$file])) {
        if ($map[$file] === 'home') $base = home_url('/');
        else {
            $id = kova_page_id($map[$file]);
            $base = $id ? get_permalink($id) : home_url('/'.$map[$file].'/');
        }
    } else return $url;
    if (!$query) return $base;
    parse_str($query, $args);
    return add_query_arg($args, $base);
}
function kova_link(string $field): string { return kova_resolve_legacy_url((string) kova_value($field)); }
function kova_image_url(string $field, string $page): string {
    $v = kova_value($field, null, $page);
    if (is_numeric($v) && (int) $v > 0) {
        $url = wp_get_attachment_url((int) $v);
        if ($url) return $url;
    }
    $default = kova_data()['pages'][$page]['fields'][$field]['default'] ?? '';
    return get_template_directory_uri() . '/' . $default;
}
function kova_image_style(string $field, string $page): string {
    // An attachment URL from WP, serialized as a CSS string; outer HTML attribute is escaped in the template.
    return 'background-image:url(' . wp_json_encode(esc_url_raw(kova_image_url($field, $page))) . ');background-size:' . (kova_data()['pages'][$page]['fields'][$field]['size'] ?? 'cover') . ';background-position:' . (kova_data()['pages'][$page]['fields'][$field]['position'] ?? 'center') . ';';
}
function kova_image_alt(string $field, string $page): string {
    $v = kova_value($field, null, $page);
    if (is_numeric($v)) {
        $alt = get_post_meta((int) $v, '_wp_attachment_image_alt', true);
        if ($alt !== '') return $alt;
    }
    return kova_data()['pages'][$page]['fields'][$field]['alt'] ?? '';
}
function kova_layout_class(): string { return kova_data()['pages'][kova_current_key()]['html_class'] ?? ''; }
function kova_navigation(string $location = 'primary'): void {
    if (has_nav_menu($location)) {
        // Flat links retain the static header structure; no dropdown layout is offered in Phase 1.
        $items = wp_get_nav_menu_items(get_nav_menu_locations()[$location]);
        foreach ($items ?: [] as $item) {
            if ((int) $item->menu_item_parent !== 0) continue;
            $resolved = kova_resolve_legacy_url($item->url);
            $current = (int) $item->object_id === get_queried_object_id() || untrailingslashit($resolved) === untrailingslashit(get_permalink(get_queried_object_id()));
            echo '<a href="' . esc_url($resolved) . '"' . ($current ? ' aria-current="page"' : '') . '>' . esc_html($item->title) . '</a>';
        }
        return;
    }
    $links = ['index.html'=>'Home','cat-products.html'=>'Cat Products','dog-products.html'=>'Dog Products','sourcing-services.html'=>'Sourcing Services','how-it-works.html'=>'How We Work','about.html'=>'About KOVA','contact.html'=>'Contact'];
    if ($location === 'footer') unset($links['index.html']);
    foreach ($links as $file=>$label) {
        $key = ['index.html'=>'home','how-it-works.html'=>'how-we-work','about.html'=>'about-kova'][$file] ?? pathinfo($file, PATHINFO_FILENAME);
        echo '<a href="' . esc_url(kova_url($file)) . '"' . ($key === kova_current_key() ? ' aria-current="page"' : '') . '>' . esc_html($label) . '</a>';
    }
}
function kova_render_categories(string $species): void {
    $posts = function_exists('kova_categories') ? kova_categories($species) : [];
    foreach ($posts as $category) {
        $id = $category->ID;
        $image = (int) get_post_meta($id, 'kova_image', true);
        $src = wp_get_attachment_url($image);
        $alt = get_post_meta($image, '_wp_attachment_image_alt', true);
        $prefix = $species === 'dog' ? 'Dog' : 'Cat';
        $context = preg_match('/^'.strtolower($prefix).'(?:\s|$)/i', $category->post_title) ? $category->post_title : $prefix . ' ' . $category->post_title;
        $inquiry = add_query_arg('category', $context, kova_url('contact.html'));
        $arrow = '<svg class="arrow-icon" aria-hidden="true" viewBox="0 0 20 20" fill="none"><path d="M5 15 15 5M5 5h10v10" stroke="currentColor" stroke-width="1.2"></path></svg>';
        echo '<article class="category-card"><a class="category-image" href="' . esc_url($inquiry) . '" aria-label="' . esc_attr('Discuss sourcing for ' . $category->post_title) . '"><div class="photo-tile" role="img" aria-label="' . esc_attr($alt) . '" style="' . esc_attr('background-image:url(' . wp_json_encode(esc_url_raw($src ?: '')) . ');background-size:100% 100%;background-position:center;') . '"></div><span class="image-arrow" aria-hidden="true">' . $arrow . '</span></a><div class="category-copy"><h3>' . esc_html($category->post_title) . '</h3><p>' . esc_html(get_post_meta($id, 'kova_description', true)) . '</p><details class="sourcing-details ' . esc_attr($species === 'dog' ? 'dog-card-options' : 'cat-card-options') . '"><summary>Explore sourcing options <span class="options-indicator" aria-hidden="true"></span></summary><dl><dt>Product options:</dt><dd>' . esc_html(get_post_meta($id, 'kova_options', true)) . '</dd><dt>Customization to discuss:</dt><dd>' . esc_html(get_post_meta($id, 'kova_customization', true)) . '</dd></dl></details><a class="text-link" href="' . esc_url($inquiry) . '">Request Sourcing ' . $arrow . '</a></div></article>';
    }
}
// Reference pages link back to this WP installation using a small, read-only routing query.
add_action('template_redirect', function () {
    $key = isset($_GET['kova_page']) ? sanitize_key(wp_unslash($_GET['kova_page'])) : '';
    if (array_key_exists($key, kova_data()['pages'] ?? []) && ($id = kova_page_id($key))) {
        wp_safe_redirect(get_permalink($id)); exit;
    }
});
add_action('admin_notices', function () {
    if (!function_exists('kova_content_data')) echo '<div class="notice notice-warning"><p>KOVA: Activate the KOVA Content companion plugin and import Phase 1 content before reviewing pages.</p></div>';
});

add_action('wp_head', function () {
    $description = kova_data()['pages'][kova_current_key()]['description'] ?? '';
    if ($description !== '') echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
});

function kova_resolve_legacy_url(string $url): string {
    $path = (string) wp_parse_url($url, PHP_URL_PATH);
    $file = basename($path);
    $files = ['index.html','cat-products.html','dog-products.html','sourcing-services.html','how-it-works.html','about.html','contact.html'];
    $host = wp_parse_url($url, PHP_URL_HOST);
    $local = !$host || strtolower($host) === strtolower((string) wp_parse_url(home_url(), PHP_URL_HOST));
    if ($local && in_array($file,$files,true)) {
        $query = wp_parse_url($url,PHP_URL_QUERY); $hash = wp_parse_url($url,PHP_URL_FRAGMENT);
        return kova_url($file.($query ? '?'.$query : '')).($hash ? '#'.$hash : '');
    }
    // The original Home slug may be retained as a custom menu link after static-home setup.
    if ($local && untrailingslashit($path) === untrailingslashit((string)wp_parse_url(home_url('/kova-home/'),PHP_URL_PATH))) return home_url('/');
    return $url;
}
function kova_render_inquiry_form(): void {
    if (function_exists('kova_contact_render')) kova_contact_render(get_queried_object_id());
    else echo '<div class="submission-setup"><p>Online enquiries are not configured. Please email <a href="mailto:info@kovasourcing.com">info@kovasourcing.com</a>.</p></div>';
}
