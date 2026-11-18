# Contributing

Thanks for helping with MF StaffCore Lite.

This repository is the free edition (version 0.1.13). Pro-only modules stay in the Pro package: Documents, Awards, KPIs / performance, Backup & Restore, and Biometric sync.

## Bug reports

Use GitHub issues, or the WordPress.org forum for usage questions:

https://wordpress.org/support/plugin/mf-staffcore-lite/

Remove real employee, payroll, and attendance data before you post.

## Translations

The text domain is `mf-staffcore-lite`. Start from `languages/mf-staffcore-lite.pot`. Steps are in `languages/README.md`.

## Pull requests

- Target `main`.
- PHP 8.1 or newer. Keep `declare(strict_types=1);` on new PHP files.
- User-facing strings go through WordPress i18n functions with the `mf-staffcore-lite` text domain.
- Do not commit secrets, `.env` files, or customer data.

By contributing, you agree that your changes to the PHP code are licensed under the GNU GPL v2.0 or later, the same license as the plugin.