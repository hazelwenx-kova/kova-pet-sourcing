<?php
/** Explicit, one-time staging2 homepage configuration. Never runs on activation. */
defined('ABSPATH') || exit;
function kova_home_setup_domain_checks(): array {
    $domain = 'staging2.kovasourcing.com';
    $checks = [];
    foreach (['home', 'siteurl'] as $option) {
        $url = (string) get_option($option);
        $host = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
        $scheme = (string) wp_parse_url($url, PHP_URL_SCHEME);
        $checks[$option] = ['actual' => $scheme . '://' . $host, 'allowed' => $host === $domain && $scheme === 'https'];
    }
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $checks['HTTP_HOST'] = ['actual' => $host ?: '(未提供)', 'allowed' => $host === $domain || $host === $domain . ':443'];
    return $checks;
}
function kova_home_setup_domain_allowed(): bool {
    foreach (kova_home_setup_domain_checks() as $check) if (!$check['allowed']) return false;
    return true;
}
function kova_home_setup_admin_allowed(): bool {
    // Standard WordPress administrator capability, also supports multisite super admins.
    return current_user_can('manage_options');
}
function kova_home_setup_candidates(): array {
    return get_posts([
        'post_type' => 'page', 'post_status' => 'publish', 'numberposts' => -1,
        'meta_key' => '_wp_page_template', 'meta_value' => 'templates/page-home.php',
        'orderby' => 'ID', 'order' => 'ASC', 'suppress_filters' => true,
    ]);
}
function kova_home_setup_state(): array {
    $id = (int) get_option('page_on_front');
    $page = get_post($id);
    return [
        'show_on_front' => (string) get_option('show_on_front'),
        'page_on_front' => $id,
        'page_for_posts' => (int) get_option('page_for_posts'),
        'page_is_published_home' => $page && $page->post_type === 'page'
            && $page->post_status === 'publish'
            && get_post_meta($id, '_wp_page_template', true) === 'templates/page-home.php',
        'theme_is_kova' => get_stylesheet() === 'kova-theme',
        'completed' => (bool) get_option('_kova_home_setup_v1'),
    ];
}
function kova_home_setup_apply(int $id, string $nonce, bool $confirmed): array {
    if (!kova_home_setup_admin_allowed()) throw new RuntimeException('仅管理员可以设置首页。');
    if (!kova_home_setup_domain_allowed()) throw new RuntimeException('此工具仅允许在 HTTPS staging2.kovasourcing.com 使用。');
    if (!wp_verify_nonce($nonce, 'kova_staging2_home_setup')) throw new RuntimeException('验证已过期或无效，请刷新页面后重新确认。');
    if (!$confirmed) throw new RuntimeException('请先勾选页面与测试站确认框。');
    if (get_option('_kova_home_setup_v1')) throw new RuntimeException('一次性设置已经完成。本次未修改任何配置。');
    if (get_stylesheet() !== 'kova-theme') throw new RuntimeException('请先确认测试站当前启用 KOVA Sourcing 主题。');
    $ids = array_map(fn($page) => (int) $page->ID, kova_home_setup_candidates());
    if (!in_array($id, $ids, true)) throw new RuntimeException('所选页面不是已发布的 KOVA Home，请刷新后重新选择。');
    if ((int) get_option('page_for_posts') === $id) throw new RuntimeException('所选 Home 同时被指定为文章页。为避免修改 page_for_posts，本工具已停止，请先单独检查文章页配置。');
    if (!add_option('_kova_home_setup_lock', time(), '', false)) throw new RuntimeException('另一个设置请求正在处理，请稍后刷新。');
    $oldFront = get_option('page_on_front');
    $oldMode = get_option('show_on_front');
    $oldPosts = get_option('page_for_posts');
    try {
        // Recheck completion under the lock to reject concurrent/stale confirmations.
        if (get_option('_kova_home_setup_v1')) throw new RuntimeException('一次性设置已经完成。本次未修改任何配置。');
        update_option('page_on_front', $id);
        update_option('show_on_front', 'page');
        $state = kova_home_setup_state();
        if ($state['page_on_front'] !== $id || $state['show_on_front'] !== 'page'
            || !$state['page_is_published_home'] || get_option('page_for_posts') !== $oldPosts) {
            update_option('page_on_front', $oldFront);
            update_option('show_on_front', $oldMode);
            throw new RuntimeException('配置未通过保存后核对，首页两项设置已尝试恢复。请检查其他插件或选项过滤器。');
        }
        if (!add_option('_kova_home_setup_v1', ['page_id' => $id, 'user_id' => get_current_user_id(), 'time' => time()], '', false)) {
            update_option('page_on_front', $oldFront);
            update_option('show_on_front', $oldMode);
            throw new RuntimeException('无法保存完成标记，首页两项设置已尝试恢复。');
        }
        return kova_home_setup_state();
    } finally {
        delete_option('_kova_home_setup_lock');
    }
}
add_action('admin_menu', function () {
    // Visibility is independent of domain checks: administrators must be able to see why execution is blocked.
    if (kova_home_setup_admin_allowed()) {
        add_management_page('设置 KOVA 首页', '设置 KOVA 首页（staging2）', 'manage_options', 'kova-home-setup', 'kova_home_setup_screen');
    }
});
function kova_home_setup_screen(): void {
    if (!kova_home_setup_admin_allowed()) {
        wp_die('此工具仅供具有 manage_options 权限的管理员使用。', '', ['response' => 403]);
    }
    echo '<div class="wrap"><h1>一次性设置 KOVA 首页</h1><p>仅更新当前测试站的 show_on_front 和 page_on_front。不会更改文章页配置、页面内容、分类、主题设计或邮件设置。</p>';
    echo '<h2>运行环境检查</h2><p>管理工具可以显示诊断信息，但只有全部域名检查通过才能执行。插件版本：0.1.2；文件已加载。</p><table class="widefat striped"><tbody>';
    foreach (kova_home_setup_domain_checks() as $name => $check) {
        echo '<tr><th>' . esc_html($name) . '</th><td>' . esc_html($check['actual']) . '</td><td>' . ($check['allowed'] ? '通过' : '未通过') . '</td></tr>';
    }
    echo '<tr><th>manage_options</th><td colspan="2">通过</td></tr></tbody></table>';
    if (!kova_home_setup_domain_allowed()) {
        echo '<div class="notice notice-warning"><p>当前仅显示诊断信息，首页设置操作已禁用。要求 home 和 siteurl 为 https://staging2.kovasourcing.com，请求 Host 为 staging2.kovasourcing.com（或 :443）。工具不会自动修改这些网址。若某项仍显示正式域名，请先核对测试站克隆配置；不要绕过检查。</p></div></div>';
        return;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            $rawId = $_POST['kova_home_id'] ?? '';
            $rawNonce = $_POST['_wpnonce'] ?? '';
            if (!is_string($rawId) || !ctype_digit($rawId) || !is_string($rawNonce)) throw new RuntimeException('请求格式无效。');
            kova_home_setup_apply((int) $rawId, wp_unslash($rawNonce), isset($_POST['kova_confirm']) && $_POST['kova_confirm'] === '1');
            echo '<div class="notice notice-success"><p>首页配置已保存并通过核对。请继续在新窗口验证实际根网址显示。</p></div>';
        } catch (Throwable $error) {
            echo '<div class="notice notice-error"><p>' . esc_html($error->getMessage()) . '</p></div>';
        }
    }
    $state = kova_home_setup_state();
    echo '<h2>当前配置与验证</h2><table class="widefat striped"><tbody>';
    foreach (['show_on_front','page_on_front','page_for_posts'] as $name) echo '<tr><th>' . esc_html($name) . '</th><td>' . esc_html((string) $state[$name]) . '</td></tr>';
    echo '<tr><th>静态首页配置</th><td>' . ($state['show_on_front'] === 'page' && $state['page_is_published_home'] ? '通过：已指向发布的 KOVA Home' : '尚未通过') . '</td></tr>';
    echo '<tr><th>KOVA 主题</th><td>' . ($state['theme_is_kova'] ? '已启用' : '尚未启用') . '</td></tr>';
    echo '<tr><th>一次性操作</th><td>' . ($state['completed'] ? '已完成；再次提交不会更改设置' : '尚未执行') . '</td></tr></tbody></table>';
    if (!get_pages(['number' => 1])) echo '<div class="notice notice-warning"><p>WordPress 的 get_pages() 当前没有返回页面，这可能解释首页设置选项消失。Reading 设置页可能将首页模式重置为 posts；请继续排查影响页面查询的插件或代码。</p></div>';
    if (!$state['completed']) {
        $pages = kova_home_setup_candidates();
        if (!$pages) echo '<p>未找到已发布且使用 KOVA Home 模板的页面。未修改任何设置。</p>';
        else {
            echo '<h2>确认首页页面</h2><form method="post">';
            wp_nonce_field('kova_staging2_home_setup');
            foreach ($pages as $page) {
                echo '<p><label><input type="radio" name="kova_home_id" value="' . esc_attr((string) $page->ID) . '" required' . (count($pages) === 1 ? ' checked' : '') . '> ' . esc_html($page->post_title) . ' — ID ' . esc_html((string) $page->ID) . ' — ' . esc_html(get_permalink($page)) . '</label></p>';
            }
            echo '<p><label><input type="checkbox" name="kova_confirm" value="1" required> 确认上述页面是 KOVA 首页，且正在操作 staging2 测试站。</label></p>';
            submit_button('确认设置 KOVA 首页');echo '</form>';
        }
    }
    echo '<p><a target="_blank" rel="noopener" href="' . esc_url(home_url('/')) . '">打开根网址验证首页</a></p><p>此处验证的是 WordPress 配置与页面状态，不等同于真实浏览器页面验证。设置后请清理测试站缓存，检查根网址、Home、Logo 和 Cat Products。手工自定义的 Home 菜单链接如仍是 /kova-home/，需在菜单中改为根网址。</p></div>';
}

// A direct diagnostic entry also helps if another plugin changes the Tools menu UI.
add_action('admin_notices', function () {
    if (($GLOBALS['pagenow'] ?? '') === 'plugins.php' && kova_home_setup_admin_allowed()) {
        echo '<div class="notice notice-info"><p>KOVA Content 0.1.2：工具代码已加载。<a href="' . esc_url(admin_url('tools.php?page=kova-home-setup')) . '">打开 KOVA 首页环境检查</a>（写入仍仅限 staging2）。</p></div>';
    }
});
