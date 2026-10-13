<?php
/** Copy OUTSIDE public_html as kova-private/inquiry-config.php. Never commit real credentials. */
return [
    'enabled' => false, // Enable for direct endpoint testing before enabling the website form.
    'allowed_origin' => 'https://kovasourcing.com', // Exact origin of the production form; no trailing slash.
    'smtp' => [
        'host' => '', // Copy from Site Tools > Email > Accounts > Mail Configuration > Manual Settings.
        'port' => 0, // Copy the SMTP port; no default is assumed.
        'security' => '', // 'smtps' for implicit TLS or 'starttls' for explicit TLS, matching SiteGround settings.
        'username' => 'info@kovasourcing.com',
        'password' => '', // Enter only in the private server file; never in chat, Git or public_html.
    ],
];
