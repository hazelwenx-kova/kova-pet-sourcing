# PHPMailer dependency

Unmodified PHPMailer 7.1.1 source files from the official repository:
https://github.com/PHPMailer/PHPMailer/tree/1bc1716a507a65e039d4ac9d9adebbbd0d346e15

The exact tag commit was verified by `git ls-remote` and the checkout. LGPL-2.1 licence retained in LICENSE. Only the three SMTP/exception/mail classes are needed; no Composer/build step is required on SiteGround. Check upstream security releases before deployment and keep this dependency updated.

Deploy this directory to `kova-private/phpmailer` OUTSIDE `public_html`. Do not edit the library to disable TLS verification or expose SMTP debugging.

SHA-256 source checksums:
- Exception.php: 22ab858ae438d98f58f41f38ad2191d1b0d59570aebea0463a7948cfae1021b7
- PHPMailer.php: 45599a196ae7944ee2dcd4f3d3da0ac4243513d346b2d77bbd15dbd0c37f7064
- SMTP.php: 522bcf0d07be7e7e00114711db5c9ce2b4d59ac041c5ec36eaadd979d5fa7046
