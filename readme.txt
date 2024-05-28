=== Sessions ===
Contributors: PierreLannoy, hosterra
Tags: authentication, login, protection, role, session
Requires at least: 6.2
Requires PHP: 8.1
Tested up to: 6.5
Stable tag: 3.0.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Powerful sessions manager for WordPress with sessions limiter and full analytics reporting capabilities.

== Description ==

**Sessions** is a powerful sessions manager for WordPress with a multi-criteria sessions limiter and full analytics reporting about logins, logouts and account creation. It relies on the standard WordPress sessions manager and add it extra features and controls.

> 🎁 Give this plugin a drive test on a free dummy site: [One-Click Test!](https://tastewp.com/new/?pre-installed-plugin-slug=sessions)

You can limit concurrent sessions, on a per role basis for the following criteria:

* count per user;
* count per IP adresses;
* count per country (requires the free [IP Locator](https://wordpress.org/plugins/ip-locator/) plugin);
* count per device classes and types, client types, browser or OS (requires the free [Device Detector](https://wordpress.org/plugins/device-detector/) plugin).

For each roles defined on your site, you can also block login based on private/public IP ranges, and define idle times for sessions auto-termination.

You can also set a maximum number of IPs used for each user - useful to limit credential sharing between many people.

**Sessions** can report the following main items and metrics:

* KPIs: login success, active sessions, cleaned sessions, active users, turnover and spam sessions;
* active and cleaned sessions details;
* users and sessions variations;
* moves distribution;
* login/logout breakdowns;
* password resets;

**Sessions** supports a set of WP-CLI commands to:
    
* manage WordPress active sessions (list, kill) - see `wp help sessions active` for details;
* toggle on/off main settings - see `wp help sessions settings` for details;
* modify operations mode - see `wp help sessions mode` for details;
* display sessions and accounts statistics - see `wp help sessions analytics` for details.

For a full help on WP-CLI commands in Sessions, please [read this guide](https://perfops.one/sessions-wpcli).

> **Sessions** is part of [PerfOps One](https://perfops.one/), a suite of free and open source WordPress plugins dedicated to observability and operations performance.

**Sessions** is a free and open source plugin for WordPress. It integrates many other free and open source works (as-is or modified). Please, see 'about' tab in the plugin settings to see the details.

= Support =

This plugin is free and provided without warranty of any kind. Use it at your own risk, I'm not responsible for any improper use of this plugin, nor for any damage it might cause to your site. Always backup all your data before installing a new plugin.

Anyway, I'll be glad to help you if you encounter issues when using this plugin. Just use the support section of this plugin page.

= Privacy =

This plugin, as any piece of software, is neither compliant nor non-compliant with privacy laws and regulations. It is your responsibility to use it - by activating the corresponding options or services - with respect for the personal data of your users and applicable laws.

This plugin doesn't set any cookie in the user's browser.

This plugin may handle personally identifiable information (PII). If the GDPR or CCPA or similar regulation applies to your case, you must adapt your processes (consent management, security measure, treatment register, etc.).

= Donation =

If you like this plugin or find it useful and want to thank me for the work done, please consider making a donation to [La Quadrature Du Net](https://www.laquadrature.net/en) or the [Electronic Frontier Foundation](https://www.eff.org/) which are advocacy groups defending the rights and freedoms of citizens on the Internet. By supporting them, you help the daily actions they perform to defend our fundamental freedoms!

== Installation ==

= From your WordPress dashboard =

1. Visit 'Plugins > Add New'.
2. Search for 'Sessions'.
3. Click on the 'Install Now' button.
4. Activate Sessions.

= From WordPress.org =

1. Download Sessions.
2. Upload the `sessions` directory to your `/wp-content/plugins/` directory, using your favorite method (ftp, sftp, scp, etc...).
3. Activate Sessions from your Plugins page.

= Once Activated =

1. Visit 'PerfOps One > Control Center > Sessions' in the left-hand menu of your WP Admin to adjust settings.
2. Enjoy!

== Frequently Asked Questions ==

= What are the requirements for this plugin to work? =

You need at least **WordPress 5.2** and **PHP 7.2**.

= Can this plugin work on multisite? =

Yes. It is designed to work on multisite too. Network Admins can use all the plugin features. Sites Admins have no access to the plugin features.

= Where can I get support? =

Support is provided via the official [WordPress page](https://wordpress.org/support/plugin/sessions/).

= Where can I report a bug? =
 
You can report bugs and suggest ideas via the [GitHub issue tracker](https://github.com/Pierre-Lannoy/wp-sessions/issues) of the plugin.

== Changelog ==

Please, see [full changelog](https://perfops.one/sessions-changelog).

== Upgrade Notice ==

== Screenshots ==

1. Active Sessions Management
2. Main Analytics Dashboard
3. Sessions Options Per Roles
4. Plugin Options
5. Embedded Details In User Profile
