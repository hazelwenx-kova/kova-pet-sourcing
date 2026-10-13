# SiteGround inquiry deployment

The Contact page is prepared for authenticated SMTP notifications to **info@kovasourcing.com**. The email link works now; form submission remains disabled in static previews. No live website or domain settings have been changed.

## 1. Upload the files

Back up your existing website first. Upload the approved static website files as usual, including the updated `contact.html`, `assets/site.js` and `assets/styles.css`. For this inquiry feature also upload:

- `api/inquiry.php` → `public_html/api/inquiry.php`
- The entire repository folder `server/phpmailer/` → a sibling private folder `kova-private/phpmailer/`, **outside `public_html`**
- `server/config.example.php` → `kova-private/inquiry-config.php`, **outside `public_html`**

Expected server layout:

```text
website-root/
├── kova-private/
│   ├── inquiry-config.php       ← private SMTP credentials
│   ├── phpmailer/
│   │   ├── LICENSE
│   │   └── src/                ← three supplied PHP classes
│   └── state/                  ← created privately by PHP for spam rate limits
└── public_html/
    ├── contact.html
    ├── assets/
    └── api/inquiry.php
```

Do not upload tests, Git metadata, deployment documentation, or `server/` into `public_html`. Use the supplied PHPMailer files; no Composer installation or build is needed on SiteGround. The library is pinned to upstream PHPMailer 7.1.1 with licence and source checksums; check upstream security updates before deployment.

Select a supported **PHP 8.1 or newer** version in SiteGround, with OpenSSL, mbstring, ctype and filter enabled. The handler was tested locally with PHP 8.4. PHP must be able to read the private config/library and write `kova-private/state`. Prefer owner-only directory/file permissions (700/600) when compatible with the account's PHP ownership. Never make credentials or state world-writable.

If your site's permitted private path differs, use a supported server environment variable `KOVA_INQUIRY_CONFIG` pointing to the private config, or change only the non-secret default config path in `api/inquiry.php`. Do not move credentials into `public_html` to work around PHP path restrictions; ask SiteGround for an allowed private path if needed.

## 2. Configure your confirmed SiteGround mailbox

In **Site Tools → Email → Accounts**, find `info@kovasourcing.com`, then open **Mail Configuration → Manual Settings** (labels may vary). Copy the actual outgoing SMTP host, SMTP port and TLS method shown there into the private `inquiry-config.php` file:

- `host`: the outgoing server hostname from SiteGround; no hostname is assumed.
- `port`: the corresponding numeric SMTP port from SiteGround; no port is assumed.
- `security`: `smtps` for implicit SSL/TLS, or `starttls` for explicit STARTTLS, matching the mailbox settings. If unclear, confirm the exact pair with SiteGround.
- `username`: `info@kovasourcing.com`.
- `password`: the mailbox password, entered only in this private server file.
- `allowed_origin`: the exact HTTPS origin hosting the form, without a path or trailing slash. The example uses `https://kovasourcing.com`; adjust if the approved site uses another origin.
- `enabled`: keep `false` until configuration is complete, then set `true` for direct endpoint testing.

Do not paste mailbox passwords into chat, commit them to GitHub, or put them in HTML/JavaScript. TLS certificate and hostname verification are enabled; do not disable verification to resolve SMTP errors. Resolve the hostname/port/TLS settings or trust configuration with SiteGround instead. Public SMTP debugging is disabled.

The sender, SMTP envelope sender and notification recipient are fixed to `info@kovasourcing.com`. Only a validated customer's email is used as Reply-To. No automatic customer email is sent.

## 3. Test before enabling the website form

Leave `contact.html` with `data-endpoint=""` while testing. PHP cannot run on GitHub Pages.

After private configuration is complete and `enabled` is `true`, use a local API client (such as Postman) to send a JSON POST to `https://kovasourcing.com/api/inquiry.php`, with `Content-Type: application/json` and `Origin: https://kovasourcing.com` (or your configured origin). Use a real test email you control:

```json
{
  "name": "Test Contact",
  "company": "Test Brand",
  "email": "YOUR_OWN_VALID_EMAIL",
  "country": "United Kingdom",
  "category": "Both",
  "support": "Supplier Research",
  "quantity": "Test inquiry only",
  "details": "Deployment test: please verify the notification and Reply-To.",
  "website": ""
}
```

Confirm all of the following:

1. The endpoint returns HTTP 200 and JSON `received: true` **only after SMTP accepts the email**.
2. The notification appears in the SiteGround inbox for `info@kovasourcing.com` (also check spam). SMTP acceptance is not a guarantee of inbox delivery.
3. From is KOVA Sourcing / `info@kovasourcing.com`; Reply-To is your validated test email. Replying goes to that test address.
4. Missing required fields, invalid email, unknown select values, excessive lengths, header-injection attempts and filled honeypot return errors and do not send mail.
5. Wrong origins, non-HTTPS requests and non-JSON requests are rejected. PHP must report the real HTTPS state; if the secure request is rejected, resolve server configuration with SiteGround rather than trusting arbitrary forwarded headers.
6. Disabled/missing config or unavailable SMTP never returns success. Error responses must not reveal credentials or internal SMTP diagnostics.

If you prefer browser testing, use an access-protected SiteGround staging copy with its own approved HTTPS origin and correct private configuration. Confirm real delivery there before changing production form configuration. Do not activate the GitHub Pages preview.

## 4. Enable the SiteGround form after successful testing

Only in the **SiteGround production copy** of `contact.html`, set the form's `data-endpoint` to:

```html
data-endpoint="https://kovasourcing.com/api/inquiry.php"
```

Use your actual approved origin if different. Keep the repository/GitHub Pages preview disabled unless a separately approved server-backed preview is configured and tested. With a valid endpoint the script enables the button and hides the preview setup notice. Without JavaScript the button stays disabled.

Review the privacy wording for the confirmed setup. Remove the sentence saying submission is disabled in this preview from the production copy only. Add any applicable retention/contact/privacy policy details after deciding the actual process; do not invent storage or deletion guarantees.

Then send a final browser inquiry, check inbox and Reply-To again, test desktop/mobile layouts, and clear SiteGround caches after uploads. No domain/DNS changes are needed for this code. If delivery fails because of mailbox or domain authentication configuration, confirm the relevant action with SiteGround before changing it.

## Validation and spam controls

The handler accepts JSON POSTs with required `name`, `email`, `country`, `category` and `details`; company/support/quantity/source-category are optional. Fields have server-side character limits matching the form. The total body is limited to 32 KB; select values are allowlisted. Single-line fields reject control characters before trimming, preventing email header injection.

The Website honeypot is hidden from visitors and excluded from keyboard navigation but remains in the submitted payload. The server rejects it when filled. Private rate counters enforce 5 valid attempts per client IP per 15 minutes and 50 site-wide per 15 minutes, including failed SMTP attempts. Only keyed IP hashes, time/count data and a private random key are stored; inquiry bodies are not stored in that state file. The actual mail notification is handled by the mailbox service. Review PHP's `REMOTE_ADDR` behavior with SiteGround if proxies cause shared client addresses; the code deliberately does not trust arbitrary X-Forwarded-For headers. Rate limiting and a honeypot reduce spam, but are not a guarantee; add a separately tested CAPTCHA or hosting controls if needed.

The frontend prevents duplicate in-flight submissions. A network timeout can leave SMTP acceptance uncertain; check the inbox before retrying. Stronger retry deduplication would require a separately designed server idempotency mechanism.

## Validation completed in this repository

- PHP syntax and actual PHP integration tests passed.
- A local simulated SMTP server used authentication and a certificate signed by a locally trusted test CA; both acceptance and rejection were exercised, including rejection of a mismatched certificate hostname. No real emails were sent.
- Browser tests cover disabled preview, form validation, honeypot, duplicate clicks, confirmed-success and error responses, category mapping and responsive layouts.
- **SiteGround SMTP settings, mailbox credentials and real inbox delivery remain unconfigured and untested.**

Run development tests with a local static preview plus `python3 tests/check_site.py` and `python3 tests/check_contact.py`. With PHP CLI (OpenSSL, mbstring, ctype and filter) installed, `python3 tests/check_php_inquiry.py` launches its own isolated PHP/SMTP fixtures. These are test-only tools, not deployment dependencies.
