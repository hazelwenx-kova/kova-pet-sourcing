# KOVA Phase 2 — 中文安装、编辑与测试指南

本次只准备文件，不自动安装到 staging2，不连接你的数据库，不修改正式站。保留 Home、Cat、八个猫分类、ACF 字段和 0.1.2 首页设置工具。主题和插件更新版本均为 **0.2.0**。

## 1. 更新文件，仅限 staging2

1. 先备份 staging2 的数据库、uploads 和当前主题/插件文件，保持密码保护。
2. 在 staging2“外观 → 主题 → 安装主题 → 上传主题”上传新版 `kova-theme.zip`，选择替换已有 KOVA Sourcing 主题。不是安装第二个不同名称的主题；文件夹仍为 `kova-theme`。
3. 在“插件 → 安装插件 → 上传插件”上传新版 `kova-content.zip`，替换已有 KOVA Content。保留激活状态；ACF Free 继续启用。
4. 确认主题和插件均为 0.2.0。先检查 Home、Cat、图片替换、已隐藏猫分类和根首页正常。
5. **不要重跑 Phase 1 Import，不要重设首页，不要删除旧页面或备份，不要使用 SiteGround 推送到正式站。**安装/激活不会自动导入、发布或配置表单。

代码目录沿用 `wordpress-phase1/` 是为保持已安装主题和插件的路径，新增模板和 Phase 2 工具均包含在新版 ZIP 中。

## 2. 独立 Phase 2 导入

打开“工具 → KOVA Phase 2 Import”。预检会列出五页和八个狗分类：

- `create`：将创建缺少记录。五页为草稿，狗分类在图片/文字完整后发布。
- `preserve`：已识别的记录保留，已编辑内容、状态、图片、排序和隐藏不被重置。
- `conflict`：发现已有同 slug、同名或对应模板页面/分类；默认停止全部导入，不创建 `-2` 页。

确认正在操作测试站并已有备份，再点击 **Import missing Phase 2 content**。

一般首次运行创建五个草稿页、八个 Dog 分类和 12 张独立媒体图片（若已有同标记图片会复用）。重复执行不会新增重复记录；已完成记录即使有字段被清空，也不会重新填回。媒体导入中断可修复原因后重试。

### 遇到冲突

不要删除旧 Elementor 页面或文章来消除冲突。

只有现有页面已使用对应 KOVA 模板、目标 slug 正确且没有其他导入标记时，工具才允许勾选“确认关联”。关联不会覆盖现有字段/正文/标题/发布状态，只补充缺少的字段。Dog 分类只有明确属于 Dog、不是同时属于 Cat、且没有其他导入标记时才可关联。

旧 Elementor 备份、同名但不同 slug 的页面、占用目标路径的旧文章，以及多个候选记录会阻止自动关联。此时先记录预检中的 ID、标题和路径，交由我们制定明确处理方式，不要自行覆盖。已有标记记录的人工改名也会保留；如其 slug 与目标不符，需要你明确确认后单独调整。

## 3. 发布五页与验证 URL

逐页预览，确认后**仅在密码保护的测试站发布**：

| 页面 | 模板 | slug / URL |
|---|---|---|
| Dog Products | KOVA Dog Products | `/dog-products/` |
| Sourcing Services | KOVA Sourcing Services | `/sourcing-services/` |
| How We Work | KOVA How We Work | `/how-we-work/` |
| About KOVA | KOVA About KOVA | `/about-kova/` |
| Contact | KOVA Contact | `/contact/` |

首页继续 `/`；Cat 继续 `/cat-products/`。导入器不更改现有首页、文章页或全局固定链接。

若是普通 `?page_id=` 链接，先确认备份后由你在测试站“设置 → 固定链接”选择合适的文章名称模式；你目前已有漂亮链接时不用重复修改。若出现路径 404，在测试站保存一次固定链接并清理测试站缓存，仍有问题再检查 rewrite 配置。

默认导航使用 WordPress 页面 ID 获取链接。已存的同站 `/reference/*.html` 自定义链接会在输出时解析为新页面链接，不覆盖数据库原值。若你自行建立了链接到旧 Elementor 页或正式站的菜单，请在“外观 → 菜单”逐项选择新的 WordPress 页面并保存。模板已经不使用参考路径作为导航目标，但旧参考文件仍保留作备份；不要删除。

## 4. 日常后台编辑

五页均使用 ACF Free 的 Page copy、Buttons & links、Images 标签页。布局、字体、颜色和断点固定，正文不需要 Elementor，也不需购买 ACF Pro。

Dog Products 使用现有 **Sourcing Categories → Cat / Dog** 分类管理：仅显示 Dog 分类。独立修改图片、介绍、Product options、Customization to discuss、Display order 和显示开关。不要将 Dog 同时标记为 Cat。原有猫记录完全保留，不应为狗产品复用猫图片。

服务、流程、About 的图片从媒体库逐张替换。Contact 原设计没有产品图，因此没有新增装饰图片；其页面文字与链接独立编辑，表单本身由 Fluent Forms 管理。

## 5. 创建正式 Fluent Forms 询盘表单

已知测试站只有 **Contact Form Demo（ID 1）**。本更新不会绑定、修改或启用它，也不会新建或自动导入 Fluent Forms 表单。

在 Fluent Forms → All Forms → Add New 创建空白正式表单，命名为 **KOVA Sourcing Inquiry**。新表单 ID 由 WordPress 生成，请勿假设是 2，也不要填写 Demo ID 1。根据以下表格建立八个字段：

| 标签 | 控件 | 必填 | 推荐 Name Attribute | 提示 / 选项 |
|---|---|---|---|---|
| Name | Single Line Text | 是 | `name` | Your full name |
| Company / Brand | Single Line Text | 否 | `company` | Your company or brand |
| Email | Email | 是 | `email` | you@example.com |
| Country / Region | Single Line Text | 是 | `country` | Where your business is based |
| Product Category | Dropdown | 是 | `category` | Cat Products / Dog Products / Both / Other / Not Sure |
| Type of Support | Dropdown | 否 | `support` | Supplier Research / OEM/ODM Customization / Sample Coordination / Production Follow-up / Order / Shipment Coordination / Not Sure Yet |
| Estimated Order Quantity | Single Line Text | 否 | `quantity` | An approximate quantity or range, if known |
| Project Details | Paragraph Text | 是 | `details` | Product ideas, materials, specifications, branding, packaging, target market and timing |

Name 使用一个单行字段即可，不必拆分 First Name / Last Name。设置下拉提示为空值，例如 Choose a category、Choose support (optional)，Product Category 的选项 value 与上述英文名称一致。`category` 是自动填入猫狗来源信息所需的字段名；若使用别的名称，页面仍显示来源提示，但不会自动选中下拉项。

在表单编辑器用三组 Two-column Container 排列：Name/Company、Email/Country、Category/Support；Quantity 和 Details 全宽。使用免费版容器，不需要 Pro。手机上样式自动叠为单列。标签置于输入框上方，Details 建议六行左右，提交按钮为 **Send Sourcing Inquiry**。

尽量关闭独立表单主题的夸张背景、圆角等；主题提供仅限 Contact 的中性色和字体样式，不会影响其他 Fluent Forms 表单。启用当前版本可用的内置 honeypot/spam protection。Email 使用 Email 控件并保留必填与格式校验；有长度限制设置时采用 Name 120、Company 160、Email 254、Country 100、Quantity 120、Details 6000。不要依赖前端样式替代服务端校验。

表单字段及防垃圾信息的具体菜单位置可能随 Fluent Forms 版本不同，需要在你安装的 **6.2.15** 中确认；该版本真实嵌入/提交尚未在本地匹配环境验证。

## 6. 邮件通知与送达测试

在正式表单 Settings & Integrations → Email Notifications：

- 开启一项管理员通知，收件人为 **info@kovasourcing.com**。
- From Email 使用 **info@kovasourcing.com**，From Name 使用 KOVA Sourcing。
- Reply-To 用编辑器的动态字段选择器选择已校验的 Email 字段（通常为 `{inputs.email}`）；不要把客户地址用作 From。
- Subject 可用 New KOVA Sourcing Inquiry；正文使用 All Submitted Data，必要时加提交页面链接。不使用 Demo 的通知配置。
- 确认发送方式：如 WordPress 已有经过验证的认证邮件/SMTP 配置，直接复用。若尚无，可以由你选用一个合适的 SMTP 配置插件；本主题不自带邮件凭据，也不会替你安装额外插件。
- SiteGround 的主机名、端口和 TLS 设置从 Site Tools → Email → Accounts → Mail Configuration 获取，不猜测这些值。密码只输入服务器后台安全配置，不放 GitHub、ACF 页面字段或聊天中。

表单确认信息可写：**Thank you. Your inquiry has been received.** 这表示表单服务器接收，不应写成“邮件已送达”。实际送达必须查看 info 邮箱；Entries 收到记录但邮箱未收到时，说明提交与邮件环节需要分别排查。

本阶段不要更改正式网站、域名 DNS 或发送大量测试邮件。先用少量无敏感信息的测试数据验证通知及 Reply-To。

## 7. 嵌入 Contact

1. 在 All Forms 复制正式表单 ID 或 Shortcode。确认它不是 Contact Form Demo，且处于可用/Published 状态。
2. 打开 WordPress **Contact** 页面，找到“**KOVA — Fluent Forms integration**”。
3. 在“正式询盘表单 ID”填写真实 ID；核对隐私文字与实际流程，再打开“启用已配置的正式表单”，保存页面。
4. 检查 `/contact/` 显示的是 Fluent Forms 的真实表单，不是旧 HTML 表单。插件不可用、ID 无效、表单未发布或标题包含 Demo 时，仅显示开发提示，不显示假表单。
5. 测试必填、错误 Email、提交失败/成功、Entries、邮箱通知、Reply-To、重复点击、垃圾信息防护；测试分类卡进入 Contact 后的来源提示及下拉预选。
6. 若任何关键环节失败，关闭 Contact 的启用开关，页面恢复明确的开发提示和邮件联系入口。无需改代码。

表单提交、错误和成功反馈均由 Fluent Forms 提供，KOVA 没有伪造 success，也没有自行激活原静态 PHP/SMTP 处理器。主题的原有 JS 不会接管 Fluent Forms 表单提交。

## 8. 验收与回退

- 检查七页 desktop/tablet/mobile；Dog 八卡的图片、裁切、折叠面板与原静态页一致。
- 逐页编辑一句文字，替换一张图片，确认仅该项变化。
- 隐藏/排序 Dog 分类，确认 Cat 不变；保留原有 Cat 人工编辑状态，不按导入默认值重置。
- 检查 Header、Footer、Logo、按钮、分类 inquiry 链接和页面锚点；不能再进入 `/reference/*.html`。
- 重复运行 Phase 2 Import：新增数量应为 0，人工编辑应保留。
- Home 根首页、八个猫分类、已有 ACF 及旧 Elementor 备份仍正常。
- 最后由你确认 Fluent Forms 6.2.15 的布局、提交和真实邮件送达。

如需回退，恢复之前备份的 KOVA 主题与插件文件；数据库内容不会因为替换文件被删除。旧版本无法展示 Phase 2 的新模板，完整回退效果可能需要恢复测试站数据库/菜单备份。不要将测试站备份覆盖到正式站。
