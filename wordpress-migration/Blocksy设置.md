# Blocksy 设置参考（尚未应用）

仅供批准后在 staging 配置。Companion 2.1.58 已确认；Blocksy 主题自身版本尚未确认。本包不导入或覆盖全站主题设置。

| 项目 | 建议设置 |
|---|---|
| 全站背景 | `#FAF8F4` |
| 浅色辅助背景 | `#F1EDE6` |
| Oatmeal | `#DED4C7` |
| 正文 | `#302F2C` |
| 次要文字 | `#68655F` |
| Sage | `#929C8B`，限小面积强调 |
| 站点最大宽度 | 1380px；桌面两侧约 50px，平板32px，手机20px |
| 两页布局 | 无侧栏，隐藏重复页面标题，Elementor Full Width，保留主题页眉页脚 |
| 页面正文 | 模板内使用 DM Serif Display / DM Sans，CSS 本地加载，不需要 Elementor Pro Custom Fonts |

## 页眉

- 使用 Blocksy Header Builder 的原生 Logo 和 Menu 元素，不把导航放进 Elementor 页面。
- Logo 选择 `assets/kova-logo.png`，宽度参考 90px，保留图像比例和“KOVA Sourcing”替代文字。
- 主行高度参考104px，背景 `#FAF8F4`；顶部信息行可参考32px和 `#F1EDE6`。
- 主菜单最终顺序：Home、Cat Products、Dog Products、Sourcing Services、How We Work、About KOVA、Contact。
- 此阶段只有两个草稿页。不要为了填满菜单先发布其他占位页；导航和 homepage 设置待 Phase 2/批准时配置。
- 手机使用 Blocksy 原生移动菜单，避免同时启用另一个 Elementor 导航。

## 页脚

- 使用 Blocksy Footer Builder 的 Logo/Widget Area/Menu/Copyright 等可用元素；不需要 Pro Theme Builder。
- 左栏：Logo、`Pet Products Sourcing, Made Personal.`、`Independent sourcing & coordination.`、`Shenzhen, China · For international pet brands.`
- 右栏：现有页面导航，手机纵向排列。
- 底栏：版权与 `Considered products. Clearer connections.`；使用 Blocksy 自带年份功能（若当前版本可用），否则按实际年份填写。
- 本包只交付页面正文，未声称全局页眉页脚已自动重建。

## 链接目标

模板使用 WordPress 路径，保留产品分类 query 信息：

| 静态页面 | WordPress 目标 |
|---|---|
| index.html | `/`（正式设置首页后） |
| cat-products.html | `/cat-products/` |
| dog-products.html | `/dog-products/` |
| sourcing-services.html | `/sourcing-services/` |
| how-it-works.html | `/how-we-work/` |
| about.html | `/about-kova/` |
| contact.html | `/contact/` |

其余五页未创建前，这些目标不保证可访问。若你选择不同 slug，需要在对应原生 Button 或文本链接中修改。不配置 Fluent Forms、PHP 或 SMTP。
