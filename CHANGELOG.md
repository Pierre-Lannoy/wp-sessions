# Changelog
All notable changes to **Sessions** are documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **Sessions** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased - Will be 2.0.0]

### Added
- New failsafe for `auth_cookie_expired` hook to avoid infinite loops.
- New Site Health "info" section about shared memory.

### Changed
- The analytics dashboard now displays a warning if analytics features are not activated.
- Improvement in the way roles are detected.
- Improved layout for language indicator.
- If GeoIP support is not done via [IP Locator](https://wordpress.org/plugins/ip-locator/), the flags are now correctly downgraded to emojis.
- Admin notices are now set to "don't display" by default.
- Improved IP detection  (thanks to [Ludovic Riaudel](https://github.com/lriaudel)).
- Improved changelog readability.
- The integrated markdown parser is now [Markdown](https://github.com/cebe/markdown) from Carsten Brandt.
- Prepares PerfOps menus to future 5.6 version of WordPress.

### Fixed
- [SEC001] User may be wrongly detected in XML-RPC or Rest API calls.
- The remote IP can be wrongly detected when behind some types of reverse-proxies.
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