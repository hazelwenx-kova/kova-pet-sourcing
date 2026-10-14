# staging2 一次性首页设置工具（KOVA Content 0.1.2）

此更新仅增加插件管理工具，不更新主题、模板、ACF 字段或内容数据。不会在安装/启用时自动改变首页。

## 更新与执行

1. 备份 staging2 数据库，确认正在登录 **https://staging2.kovasourcing.com/wp-admin/**。
2. “插件 → 安装插件 → 上传插件”，上传本分支 `packages/kova-content.zip`。WordPress 提示同名插件已存在时，选择**使用上传的插件替换当前插件**。不要删除旧插件后重建内容，不需要重跑 Phase 1 Import，也不需要更新主题安装包。
3. 确认 KOVA Content 已启用、版本为 **0.1.2**，测试站仍使用 KOVA Sourcing 主题。
4. 打开“工具 → **设置 KOVA 首页（staging2）**”。也可以直接访问 `https://staging2.kovasourcing.com/wp-admin/tools.php?page=kova-home-setup`。
5. 工具自动列出已发布且使用 **KOVA Home** 模板的页面，显示名称、**页面 ID** 和当前链接。只有一项时自动选择；若有多个，必须选择正确的 Home。
6. 核对页面 ID，勾选确认框，再点击“**确认设置 KOVA 首页**”。
7. 工具保存后重新读取并显示：`show_on_front = page`、`page_on_front = 所选 ID`、保持原值的 `page_for_posts`、页面状态/模板核对和完成状态。
8. 清理**测试站**缓存，在新窗口打开 `https://staging2.kovasourcing.com/`。确认 Home 内容、ACF 编辑、Cat Products 页面及分类正常；分别点击页头和页脚 Logo，确认返回根网址。
9. 默认导航的 Home 会随 WordPress 首页配置自动指向 `/`。如果你使用的菜单中 Home 是手工自定义的 `/kova-home/` 链接，请在“外观 → 菜单”将该项改为 `https://staging2.kovasourcing.com/`。工具不修改任何菜单项。

**只更新插件，不部署到正式站，不使用 SiteGround“推送到正式站”。**插件内容和现有数据库数据不会因替换插件文件而重新导入。

## 范围与限制

- 工具要求 WordPress 的 home、siteurl 均为 HTTPS 且主机为 `staging2.kovasourcing.com`，当前请求 Host 也必须匹配。正式域名、其他 staging 域名和普通本地网址均无法操作。
- 必须具有 WordPress 管理员的 `manage_options` 权限，并通过 nonce 和人工确认。Home 发布状态和模板在提交时重新核对。
- 成功后保存一次性完成标记，不再显示执行表单；旧请求重放和再次提交不会改变配置。
- 不改变 `page_for_posts`。如果所选 Home 已被指定为文章页，工具会停止，交由管理员单独检查该冲突。
- 验证页面明确区分“配置/页面状态核对”与“浏览器实际效果”。缓存、其他插件的查询/重定向、Reading 设置页重新覆盖选项，仍需要在测试站检查。
- 如果 `get_pages()` 不返回页面，工具显示警告：这可能解释静态首页选项缺失；WordPress Reading 页面还可能将首页模式重置为 posts。请继续排查页面查询相关插件/代码，不应通过反复执行工具掩盖这个原因。
- 如果设置后配置再次被其他代码覆盖，工具会显示当前核对未通过，但不允许自动重复写入。请先诊断覆盖来源。
- 工具没有删除、修改或重新导入页面/文章/分类，也不启用表单或邮件。

## 已完成测试

在隔离的本地 WordPress 7.1.3 / PHP 8.4.24 中，通过进程内模拟 staging2 选项和请求 Host 测试（没有连接 staging2）：

- 正式域名、其他 staging、非 HTTPS、错误请求 Host、匿名用户、无效 nonce、未确认及错误页面 ID 全部被拒绝。
- 自动识别模板对应页面，确认表单显示 ID、nonce 与人工确认框。
- 两个首页选项保存成功；WordPress 根查询选择 Home，Home permalink 指向根网址。
- `page_for_posts`、现有页面/文章数量、Home/Cat ACF 元数据保持不变。
- 完成状态正确显示；重复操作被拒绝。
- PHP 语法检查通过。

尚未在 SiteGround 执行或验证实际缓存/重定向。测试程序 `tests/check_home_setup.php` 仅允许 CLI + local fixture，临时修改本地首页配置并在 finally 中恢复。交付工具不含本地域名绕过开关。

## 0.1.2 菜单显示修复

0.1.1 ZIP 经检查包含并加载了全部工具代码。问题在于菜单注册与执行共用域名条件：home、siteurl、HTTPS 或 HTTP_HOST 任一不匹配，就静默隐藏整个菜单；硬编码 Administrator 角色还可能排除拥有管理员权限的自定义角色或 multisite super admin。尚未读取你的测试站配置，不能确定实际是哪一项不匹配。

0.1.2 将菜单显示与执行分开：具有 manage_options 权限的管理员可看到菜单和环境诊断；只有原来的 staging2 域名检查全部通过，才显示确认表单并允许写入。正式域名与其他域名只能看到只读诊断，不能设置首页。

Plugins 页面另有“工具代码已加载”提示及直接入口。若 Tools 菜单受其他插件影响，可点击该入口，或直接打开 `tools.php?page=kova-home-setup`。诊断表列出 home/siteurl 的协议和主机、HTTP_HOST 及通过状态；未通过时不会自动修改网址。若仍出现旧版本提示，请核对更新包是否替换成功及服务器 PHP 缓存。

本地新增测试执行了实际 admin_menu 注册，确认在不匹配域名也可看到菜单，诊断不修改任何设置、无确认表单；无管理权限用户看不到菜单。原有域名、nonce、一次性写入和数据保留测试重新通过。新 ZIP 已逐文件与源码比对通过。
