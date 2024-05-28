# Changelog
All notable changes to **Sessions** are documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **Sessions** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [3.0.0] - 2024-05-28

### Added
- [BC] To enable installation on more heterogeneous platforms, the plugin now adapts its internal logging mode to already loaded libraries.

### Changed
- Updated DecaLog SDK from version 4.1.0 to version 5.0.0.

### Fixed
- PHP error with some plugins like Woocommerce Paypal Payments.

## [2.14.0] - 2024-05-07

### Changed
- The plugin now adapts its requirements to the PSR-3 loaded version.

## [2.13.3] - 2024-05-04

### Fixed
- PHP error when DecaLog is not installed.

## [2.13.2] - 2024-05-04

### Fixed
- PHP error when DecaLog is not installed.

## [2.13.1] - 2024-05-04

### Changed
- Updated DecaLog SDK from version 3.0.0 to version 4.1.0.
- Minimal required WordPress version is now 6.2.

## [2.13.0] - 2024-03-02

### Added
- Compatibility with WordPress 6.5.

### Changed
- Minimal required WordPress version is now 6.1.
- Minimal required PHP version is now 8.1.

## [2.12.0] - 2023-10-25

### Added
- Compatibility with WordPress 6.4.

### Fixed
- With PHP 8.2, in some edge cases, deprecation warnings may be triggered when viewing analytics.

## [2.11.0] - 2023-07-12

### Added
- Compatibility with WordPress 6.3.

### Changed
- The color for `shmop` test in Site Health is now gray to not worry to much about it (was previously orange).

## [2.10.0] - 2023-04-28

### Added
- New blocking type "fallback page" to redirect user when he/she is not authorized to login (thanks to [drmustafa1](https://github.com/drmustafa1)).

### Changed
- Improved speed of sessions lookup on large WordPress users base.
- Improved messages handling.
- Details added (in the UI) about allowing logins from all/public/private IP ranges.

## [2.9.1] - 2023-03-02

### Fixed
- [SEC005] CSRF vulnerability / [CVE-2023-27444](https://www.cve.org/CVERecord?id=CVE-2023-27444) (thanks to [Mika](https://patchstack.com/database/researcher/5ade6efe-f495-4836-906d-3de30c24edad) from [Patchstack](https://patchstack.com)).

## [2.9.0] - 2023-02-24

The developments of PerfOps One suite, of which this plugin is a part, is now sponsored by [Hosterra](https://hosterra.eu).

Hosterra is a web hosting company I founded in late 2022 whose purpose is to propose web services operating in a European data center that is water and energy efficient and ensures a first step towards GDPR compliance.

This sponsoring is a way to keep PerfOps One plugins suite free, open source and independent.

### Added
- Compatibility with WordPress 6.2.

### Changed
- Improved loading by removing unneeded jQuery references in public rendering (thanks to [Kishorchand](https://github.com/Kishorchandth)).

### Fixed
- In some edge-cases, detecting IP may produce PHP deprecation warnings (thanks to [YR Chen](https://github.com/stevapple)).

## [2.8.0] - 2022-10-06

### Added
- Compatibility with WordPress 6.1.
- [WPCLI] The results of `wp sessions` commands are now logged in [DecaLog](https://wordpress.org/plugins/decalog/).

### Changed
- Improved ephemeral cache in analytics.
- [WPCLI] The results of `wp sessions` commands are now prefixed by the product name.

### Fixed
- [SEC004] Moment.js library updated to 2.29.4 / [Regular Expression Denial of Service (ReDoS)](https://github.com/moment/moment/issues/6012).

## [2.7.0] - 2022-04-20

### Added
- Compatibility with WordPress 6.0.

### Changed
- Site Health page now presents a much more realistic test about object caching.
- Updated DecaLog SDK from version 2.0.2 to version 3.0.0.

### Fixed
- [SEC003] Moment.js library updated to 2.29.2 / [CVE-2022-24785](https://github.com/advisories/GHSA-8hfj-j24r-96c4).

## [2.6.2] - 2022-01-17

### Fixed
- The Site Health page may launch deprecated tests.

## [2.6.1] - 2022-01-17

### Fixed
- There may be name collisions with internal APCu cache.
- An innocuous Mysql error may be triggered at plugin activation.

## [2.6.0] - 2021-12-28

### Added
- Compatibility with PHP 8.1.
- New option to delete all sessions of users resetting their passwords (thanks to [mrdexters1](https://profiles.wordpress.org/mrdexters1/) for the suggestion).

### Changed
- Charts allow now to display more than 2 months of data.
- Improved timescale computation and date display for all charts.
- Refactored cache mechanisms to fully support Redis and Memcached.
- Bar charts have now a resizable width.
- Updated DecaLog SDK from version 2.0.0 to version 2.0.2.
- Updated PerfOps One library from 2.2.1 to 2.2.2.
- Improved bubbles display when width is less than 500px (thanks to [Pat Ol](https://profiles.wordpress.org/pasglop/)).
- The tables headers have now a better contrast (thanks to [Paul Bonaldi](https://profiles.wordpress.org/bonaldi/)).

### Fixed
- Object caching method may be wrongly detected in Site Health status (thanks to [freshuk](https://profiles.wordpress.org/freshuk/)).
- The console menu may display an empty screen (thanks to [Renaud Pacouil](https://www.laboiteare.fr)).

## [2.5.0] - 2021-12-07

### Added
- Compatibility with WordPress 5.9.
- New button in settings to install recommended plugins.
- The available hooks (filters and actions) are now described in `HOOKS.md` file.

### Changed
- Improved update process on high-traffic sites to avoid concurrent resources accesses.
- Better publishing frequency for metrics.
- [BC] The filter `perfopsone_advanced_controls` has been renamed in `perfopsone_show_advanced` for consistency reasons.
- X axis for graphs have been redesigned and are more accurate.
- Updated labels and links in plugins page.
- Updated the `README.md` file.

### Fixed
- Country translation with i18n module may be wrong.
- Plugin's advanced settings are not translatable.

## [2.4.1] - 2021-09-20

### Changed
- The sessions list now displays all users' roles (thanks to [ShamiraO](https://github.com/ShamiraO)).

### Fixed
- [SEC002] In some cases, "cumulative privileges" maybe interpreted as "least privileges" (thanks to [ShamiraO](https://github.com/ShamiraO)).
- With multiple roles per user, session idle time (when less than one hour) may be wrongly computed.

## [2.4.0] - 2021-09-07

### Added
- It's now possible to hide the main PerfOps One menu via the `poo_hide_main_menu` filter or each submenu via the `poo_hide_analytics_menu`, `poo_hide_consoles_menu`, `poo_hide_insights_menu`, `poo_hide_tools_menu`, `poo_hide_records_menu` and `poo_hide_settings_menu` filters (thanks to [Jan Thiel](https://github.com/JanThiel)).

### Changed
- Updated DecaLog SDK from version 1.2.0 to version 2.0.0.
- Improved message in case there's no session to delete.

### Fixed
- There may be name collisions for some functions if version of WordPress is lower than 5.6.
- The main PerfOps One menu is not hidden when it doesn't contain any items (thanks to [Jan Thiel](https://github.com/JanThiel)).
- In some very special conditions, the plugin may be in the default site language rather than the user's language.
- The PerfOps One menu builder is not compatible with Admin Menu Editor plugin (thanks to [dvokoun](https://wordpress.org/support/users/dvokoun/)).

## [2.3.1] - 2021-08-11

### Changed
- New redesigned UI for PerfOps One plugins management and menus (thanks to [Loïc Antignac](https://github.com/webaxones), [Paul Bonaldi](https://profiles.wordpress.org/bonaldi/), [Axel Ducoron](https://github.com/aksld), [Laurent Millet](https://profiles.wordpress.org/wplmillet/), [Samy Rabih](https://github.com/samy) and [Raphaël Riehl](https://github.com/raphaelriehl) for their invaluable help).
- It's now possible to set an idle time of 36, 48 and 72 hours.
- There's now a `perfopsone_advanced_controls` filter to display advanced plugin settings.

### Fixed
- In some conditions, the plugin may be in the default site language rather than the user's language.

## [2.3.0] - 2021-06-22

### Added
- Compatibility with WordPress 5.8.
- Integration with DecaLog SDK.
- Traces and metrics collation and publication.
- It's now possible to customize messages in case of forbidden access (thanks to [Jon Cuevas](https://wordpress.org/support/users/archondigital/)).
- New option, available via settings page and wp-cli, to disable/enable metrics collation.
 
### Changed
- It's now possible to set an idle time of 15, 30 and 45 minutes (thanks to [pgray](https://wordpress.org/support/users/pgray/)).
- Improved internal IP detection: support for cloud load balancers.
- It's now possible to set from 1 to 9 sessions per user.
- [WP-CLI] `sessions status` command now displays DecaLog SDK version too.

### Fixed
- Sessions is not compatible with PHP 7.2 (thanks to [chernenkopetro](https://wordpress.org/support/users/chernenkopetro/)).

## [2.2.0] - 2021-02-24

### Added
- Compatibility with WordPress 5.7.
- New values and more granularity for cookie durations and idle timeouts.

### Changed
- Consistent reset for settings.
- Improved translation loading.
- [WP_CLI] `sessions` command have now a definition and all synopsis are up to date.

### Fixed
- In Site Health section, Opcache status may be wrong (or generates PHP warnings) if OPcache API usage is restricted.

## [2.1.0] - 2020-12-03

### Added
- Compatibility with WPS Hide Login.
- Compatibility with Loginizer.
- Compatibility with LifterLMS.

### Fixed
- Sorting sessions by "idle" field may produce errors.
- Limiter may fail to limit with some early-initializer plugins (thanks to [vadimfish](https://wordpress.org/support/users/vadimfish/)).

## [2.0.0] - 2020-11-28

### Added
- [WP-CLI] New command to manage active sessions: see `wp help sessions active` for details.
- [WP-CLI] New command to set role operation mode: see `wp help sessions mode` for details.
- [WP-CLI] New command to toggle on/off main settings: see `wp help sessions settings` for details.
- [WP-CLI] New command to display Sessions status: see `wp help sessions status` for details.
- [WP-CLI] New command to display Sessions: see `wp help sessions analytics` for details.
- Privileges computation can be set as 'cumulative' or 'least' on sites using multiple roles per users. May be a breaking change if you're in this case, please verify your settings.
- New failsafe for `auth_cookie_expired` hook to avoid infinite loops.
- New Site Health "info" section about shared memory.
- Compatibility with WordPress 5.6.

### Changed
- The analytics dashboard now displays a warning if analytics features are not activated.
- Improvement in privileges computation and enforcement.
- Improvement in the way roles are detected.
- Improved layout for language indicator.
- If GeoIP support is not done via [IP Locator](https://wordpress.org/plugins/ip-locator/), the flags are now correctly downgraded to emojis.
- Anonymous proxies, satellite providers and private networks are now fully detected when [IP Locator](https://wordpress.org/plugins/ip-locator/) is installed.
- Admin notices are now set to "don't display" by default.
- Improved IP detection (thanks to [Ludovic Riaudel](https://github.com/lriaudel)).
- Improved changelog readability.
- The integrated markdown parser is now [Markdown](https://github.com/cebe/markdown) from Carsten Brandt.
- Prepares PerfOps menus to future versions.

### Fixed
- [SEC001] User may be wrongly detected in XML-RPC or Rest API calls.
- The remote IP can be wrongly detected when behind some types of reverse-proxies.
- The count of cleaned sessions may be wrong when "Delete All Sessions" is used.
- In admin dashboard, the statistics link is visible even if analytics features are not activated.
- With Firefox, some links are unclickable in the Control Center (thanks to [Emil1](https://wordpress.org/support/users/milouze/)).
- When site is in english and a user choose another language for herself/himself, menu may be stuck in english.
- Some graph labels are wrong.
- The analytics page contains unclosed HTML tags.

### Removed
- Parsedown as integrated markdown parser.
- Strict vs. permissive mode "feature" as the plugin is now pretty stable.

## [1.2.0] - 2020-08-27

### Added
- Compatibility with WordPress 5.5.
- Enhanced compatibility with Jetpack SSO.
- Support for data feeds - reserved for future use.

### Changed
- The positions of PerfOps menus are pushed lower to avoid collision with other plugins (thanks to [Loïc Antignac](https://github.com/webaxones)).

### Fixed
- There's a PHP warning when an admin log in for the first time.
- While connecting via SSO, cookie durations may be wrongly computed.

### Removed
- Support for the "Block and send a WordPress error" method when Jetpack SSO is used (because Jetpack SSO can't handle it).

## [1.1.4] - 2020-06-29
### Changed
- In sessions list (tools), clicking on the user name now redirects to its profile edit page.
- Full compatibility with PHP 7.4.
- Automatic switching between memory and transient when a cache plugin is installed without a properly configured Redis / Memcached.

### Fixed
- When a session is already expired, the time detail in sessions list may be blank.

## [1.1.3] - 2020-05-22

### Changed
- KPI for active sessions is now a ratio.
- Better consistency between KPI and chart for active sessions.
- Better consistency between KPI and chart for cleaned sessions.
- Better precision for cleaned sessions breakdown.

## [1.1.2] - 2020-05-15

### Changed
- Supports now Wordfence alerting system inconsistency.

### Fixed
- When used for the first time, settings checkboxes may remain checked after being unchecked.
- When Wordfence locks out an account, a warning maybe wrongly sent to [DecaLog](https://wordpress.org/plugins/decalog/).

## [1.1.1] - 2020-05-05

### Changed
- Expired sessions cookies are now counted as cleaned sessions.

### Fixed
- There's an error while activating the plugin when the server is Microsoft IIS with Windows 10.
- The counted deleted user may be wrong in KPIs.
- Batch sessions deletion are wrongly counted.
- With Microsoft Edge, some layouts may be ugly.

## [1.1.0] - 2020-04-12

### Added
- It's now possible to set the maximum number of IP addresses per user.
- It's now possible to override the (weak) WordPress IP detection (this setting is strongly recommended).
- It's now possible to refresh IP when a session is resumed (this setting is strongly recommended).
- Now compatible with Jetpack SSO.
- Now compatible with Next Active Directory Integration SSO.
- Compatibility with [DecaLog](https://wordpress.org/plugins/decalog/) early loading feature.
- Full integration with [IP Locator](https://wordpress.org/plugins/ip-locator/).
- Integration with Wordfence.
- Partial compatibility with miniOrange SAML SSO.

### Changed
- Active sessions deleted by an admin are now counted as cleaned sessions.
- In site health "info" tab, the boolean are now clearly displayed.
- Better display of KPIs when there's no (or not yet) data to compute.

### Fixed
- Some typos in the settings screen.

### Removed
- Dependency to "Geolocation IP Detection" plugin. Nevertheless, this plugin can be used as a fallback solution.
- Flagiconcss as library. If there's no other way, flags will be rendered as emoji.

## [1.0.0] - 2020-03-24

Initial release