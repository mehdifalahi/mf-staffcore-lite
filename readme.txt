=== MF StaffCore Lite: HR Management ===
Contributors: mehdifalahi
Donate link: https://mehdifalahi.dev/product/mf-staffcore-wp
Tags: hr, employees, payroll, attendance, leave
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.13
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Free HR management for WordPress teams: employees, attendance, leave, payroll, and assets. Fully functional, no employee cap.

== Description ==

**MF StaffCore Lite: HR Management** is a modular HR management plugin for WordPress. It gives small teams a clean admin panel (Tabler UI) for everyday HR work, without locking you into wp-admin only.

= What you get in Lite =

* **Employees**: profiles, departments, positions (no limit on employee count)
* **Attendance**: track time records
* **Leave requests**: request and approve leave
* **Payroll**: payslips and employee self-service payslips
* **Assets**: assets, categories, maintenance, suppliers
* **Users & permissions**: Administrator, HR Manager, and Employee roles, including bulk-linking WordPress users
* **Notifications**: in-app bell and inbox (no email required)
* **Frontend panel**: open the same HR UI outside `/wp-admin`
* Responsive sidebar (desktop + mobile)

= Employee self-service =

Employees can use My Profile, My Leave, and My Payslips for their **own** records only.

= Lite limits =

Lite has no employee cap and no locked built-in features. The only difference from Pro is a set of additional modules that ship only in Pro: Documents, Awards, KPIs / performance reviews, Backup & Restore, and Biometric device sync.

Need those extra modules? See [MF StaffCore Pro](https://mehdifalahi.dev/product/mf-staffcore-wp) (**$39 USD**, one-time purchase, no license key).

= Privacy =

StaffCore stores HR data in your WordPress database. Lite does **not** phone home for license checks. Optional outbound links (for example Upgrade to Pro) open the author product page when you choose them.

== Installation ==

1. Upload the `mf-staffcore` folder to `/wp-content/plugins/` (or install the ZIP via **Plugins → Add New → Upload Plugin**).
2. Activate **MF StaffCore Lite: HR Management** through the **Plugins** screen.
3. Open **MF StaffCore** from the WordPress admin menu (or use the frontend panel URL from **Settings → Frontend Panel**).
4. Go to **Settings → Users** and link your WordPress account if prompted, then configure **Company Information** and **Permissions**.

== Frequently Asked Questions ==

= What is the difference between Lite and Pro? =

Lite is free with no employee cap and every core HR module fully functional, including bulk-linking WordPress users. Pro adds Documents, Awards & recognition, KPIs & performance reviews, Backup & restore, and Biometric attendance sync. Details: [product page](https://mehdifalahi.dev/product/mf-staffcore-wp).

= Can I install Lite and Pro on the same site? =

No. Both use the plugin folder slug `mf-staffcore`. Activate **only one** edition per site.

= Does Pro require a license key? =

No. Pro is a separate download after purchase. Updates come from your purchase channel, not from WordPress.org.

= Where do employees open the HR panel? =

Administrators can share the **Frontend panel** URL (Settings → Frontend Panel). Logged-in users with an active StaffCore account see the same menus their role allows.

= Is my data sent to the plugin author? =

No automatic telemetry or license “phone home” on Lite. HR data stays on your site.

== Screenshots ==

1. Dashboard overview
2. Employees list with filters
3. Attendance module
4. Leave requests
5. Payroll / payslips

== Changelog ==

= 0.1.13 =
* Every wp_ajax handler calls check_ajax_referer() inline before reading POST (Plugin Check / review visibility)
* Document bookmarkable frontend GET entry flag (no mutation nonce)
* Pro activation merges default permissions for newly available Pro modules after Lite

= 0.1.12 =
* Pro install ZIP is WordPress Upload–compatible (single mf-staffcore/ root); marketplace docs ship as a separate *-marketplace.zip

= 0.1.11 =
* Remove Pro unlock filter (Guideline 5); Lite plan context never unlocks Pro
* Upgrade dompdf to 3.1.6
* Theme init via wp_register_script / wp_add_inline_script (no raw script echo)
* AJAX save: verify nonce before reading POST; tighten permissions JSON whitelist
* Sanitize list-filter GET inputs with absint / wp_unslash

= 0.1.10 =
* Fix WordPress ZIP install failure (Could not copy file. mf-staffcore\core\) by packaging with forward-slash paths

= 0.1.9 =
* Fix Lite activation fatal: do not call removed Pro-only Biometric cron when module is absent from ZIP
* Lite PlanRegistry treats Pro module IDs as not shipped (not runtime-locked trialware)

= 0.1.8 =
* Guideline 5: Lite build strips Pro wiring (PlanRegistry stub, menus, Ajax, ServiceContainer) so no locked built-in features remain in the package
* Text domain aligned with WordPress.org slug: mf-staffcore-lite
* Sanitize employee allowances JSON and bulk-delete / bulk-link ID payloads
* Removed Cropper.js; My Profile photo crop uses built-in canvas UI

= 0.1.7 =
* Removed employee cap and bulk-link restriction from Lite (no locked built-in features)
* Pro-only modules (Documents, Awards, KPIs/Performance, Backup, Biometric) physically excluded from the Lite package, not just hidden
* Enqueue static CSS/JS instead of inline tags; sanitize ADMS/AJAX inputs; composer.json included in package
* Tested up to 7.0

= 0.1.6 =
* Plugin Check: remaining EscapeOutput (leave/payslip day counts) and PreparedSQL false positives

= 0.1.5 =
* Plugin Check ERROR fixes: escaped view output, local Tom Select/printThis, SQL/filesystem compliance, Tested up to 7.0

= 0.1.4 =
* Plugin Check: exception message escaping plugin-wide; dev files excluded from Lite ZIP

= 0.1.3 =
* Plugin Check: TablerIcon print helpers, exception escaping, i18n translators comments / placeholders

= 0.1.2 =
* i18n compliance for WordPress Plugin Check (literal translation strings, translators comments)
* Soft Upgrade to Pro UX for Lite builds

= 0.1.1 =
* Initial Lite release for WordPress.org
* Employees, departments, positions
* Attendance, leave requests, payroll, my payslips
* Assets, categories, maintenance, suppliers
* Users, permissions, company information, frontend panel
* In-app notifications, Tabler admin UI, mobile nav
* Soft Upgrade to Pro prompts (sidebar, settings card)

== Upgrade Notice ==

= 0.1.7 =
No employee cap, no locked features, Pro-only modules fully removed from the Lite package. Recommended update.

= 0.1.6 =
Clears remaining Plugin Check ERROR findings from review scan.

= 0.1.5 =
Plugin Check ERROR compliance for WordPress.org review.

= 0.1.4 =
Plugin Check escaping and packaging fixes for WordPress.org review.

= 0.1.3 =
Plugin Check compliance fixes for WordPress.org review.

= 0.1.2 =
i18n and Plugin Check fixes. Recommended update before submitting to WordPress.org.

= 0.1.1 =
First public Lite release. For Pro modules see https://mehdifalahi.dev/product/mf-staffcore-wp
