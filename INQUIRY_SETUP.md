# Enable real KOVA inquiries before launch

The static website remains compatible with SiteGround. The frontend does not require a build or server framework. Nothing has been deployed, and no endpoint or real business email has been invented.

## Current preview

- Business contact is `[BUSINESS_EMAIL_PLACEHOLDER]`; replace it with your verified business address.
- `contact.html` has `data-endpoint=""`. The Send Sourcing Inquiry button is disabled and the preview notice explains why.
- No mailto submission, file download, fake success or locally stored inquiry is used.
- Existing category links map to the four broad categories. The selected specific category is displayed and supplied as `source_category` if a configured endpoint is used.
- A hidden `website` field is a basic honeypot. Browser checks alone do not provide server protection.

## Required server or form-service setup

1. Choose a real HTTPS submission endpoint: a SiteGround-hosted PHP handler with securely configured email/queue delivery, or a reputable form service. Configure the verified recipient and any provider credentials on the server or service dashboard. Never place SMTP passwords, private API keys or private keys in HTML/JavaScript.
2. Implement or adapt the following contract before setting the endpoint. If a service uses another response format, update the frontend adapter to that provider’s documented confirmation format and test it. Do not consider an arbitrary 200 response proof of receipt.
3. Validate all required fields, email, maximum lengths and select values on the server. Reject unexpected fields, unsafe header content and filled honeypots. HTML validation can be bypassed.
4. Add rate limiting and spam controls on the server; CAPTCHA can be added if necessary. Restrict allowed browser origins, and configure CORS for JSON POSTs if cross-origin. For same-origin backend protections, use appropriate CSRF/origin checks. This frontend sends requests with `credentials: 'omit'`; an authenticated cookie-based endpoint needs a deliberately tested adapter.
5. Confirm the inquiry is accepted into reliable processing/storage before returning confirmation. Configure and test recipient delivery and failure handling separately. Implement server idempotency where needed to handle uncertain retries without duplicate delivery.
6. Define data handling and retention with the selected service. Update `contact.html` privacy copy to reflect confirmed use and processors, not assumed encryption/storage/deletion promises.
7. Replace the email placeholder. Set `data-endpoint` on the form to the configured absolute `https://...` address only after backend verification. An invalid/non-HTTPS endpoint keeps submissions disabled. The setup notice is then hidden by JavaScript.
8. Test real submissions and inbox receipt before launch, plus validation rejection, provider errors, network failures, duplicate clicks, timeout/retry and spam rejection. No real backend or recipient delivery has been tested in this review build.

## Endpoint contract

`POST` over HTTPS with `Content-Type: application/json` and `Accept: application/json`.

Example structure (fictional sample data, not credentials):

```json
{
  "name": "Example Contact",
  "company": "Example Brand",
  "email": "contact@example.com",
  "country": "United Kingdom",
  "category": "Both",
  "support": "Supplier Research",
  "quantity": "An estimated range",
  "details": "Materials, branding and project requirements",
  "website": "",
  "source_category": "Pet Beds & Comfort"
}
```

Required: `name` (120 characters max), `email` (254), `country` (100), `category`, `details` (6000). Optional: `company` (160), `support`, `quantity` (120), `source_category` (160). Allow category/support values only from the selects in `contact.html`. `website` must be empty.

After confirmed acceptance, respond with a 2xx HTTP status, `Content-Type: application/json`, and:

```json
{"received": true}
```

Use appropriate non-2xx statuses for invalid requests, spam, rate limits or delivery-system errors, with no sensitive details. The frontend never inserts raw backend error text into the page. Missing or malformed confirmation is treated as unconfirmed receipt. It retains user entries on errors, disables duplicate in-flight submits and aborts the browser request after 20 seconds. A timeout does not establish whether the server accepted the request; the UI says receipt could not be confirmed.
