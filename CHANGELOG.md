# Changelog
All notable changes to **Sessions** are documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **Sessions** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased - will be 1.1.1]
### Fixed
- Batch deletion are wrongly counted.

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
### Initial release