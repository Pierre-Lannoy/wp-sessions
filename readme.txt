=== Sessions ===
Contributors: PierreLannoy
Tags: authentication, login, protection, role, session
Requires at least: 5.2
Requires PHP: 7.2
Tested up to: 5.3
Stable tag: 1.0.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Powerful sessions manager for WordPress with sessions limiter and full analytics reporting capabilities.

== Description ==

**Sessions** is a powerful sessions manager for WordPress with a multi-criteria sessions limiter and full analytics reporting about logins, logouts and account creation.

You can limit concurrent sessions, on a per role basis for the following criteria:

* count per user;
* count per IP adresses;
* count per country (requires the free [GeoIP Detection](https://wordpress.org/plugins/geoip-detect/) plugin);
* count per device classes and types, client types, browser or OS (requires the free [Device Detector](https://wordpress.org/plugins/device-detector/) plugin).

For each roles defined on your site, you can also block login based on private/public IP ranges, and define idle times for sessions auto-termination.

**Sessions** can report the following main items and metrics:

* KPIs: login success, active sessions, cleaned sessions, active users, turnover and spam sessions;
* active and cleaned sessions details;
* users and sessions variations;
* moves distribution;
* login/logout breakdowns;
* password resets;

**Sessions** is a free and open source plugin for WordPress. It integrates many other free and open source works (as-is or modified). Please, see 'about' tab in the plugin settings to see the details.

= Support =

This plugin is free and provided without warranty of any kind. Use it at your own risk, I'm not responsible for any improper use of this plugin, nor for any damage it might cause to your site. Always backup all your data before installing a new plugin.

Anyway, I'll be glad to help you if you encounter issues when using this plugin. Just use the support section of this plugin page.

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

1. Visit 'PerfOps Settings > Sessions' in the left-hand menu of your WP Admin to adjust settings.
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

Please, see [full changelog](https://github.com/Pierre-Lannoy/wp-sessions/blob/master/CHANGELOG.md) on GitHub.

== Upgrade Notice ==

== Screenshots ==

1. Main Analytics Dashboard
2. Browsers List
3. Browser Details
4. Bots List
5. Bot Details
6. Libraries List
7. OS List
8. OS Details
9. Devices List
10. Device Details
