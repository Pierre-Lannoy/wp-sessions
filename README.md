# Device Detector
[![version](https://badgen.net/github/release/Pierre-Lannoy/wp-sessions/)](https://wordpress.org/plugins/sessions/)
[![php](https://badgen.net/badge/php/7.2+/green)](https://wordpress.org/plugins/sessions/)
[![wordpress](https://badgen.net/badge/wordpress/5.2+/green)](https://wordpress.org/plugins/sessions/)
[![license](https://badgen.net/github/license/Pierre-Lannoy/wp-sessions/)](/license.txt)

__Device Detector__ is a full featured analytics reporting and management tool that detects all devices accessing your WordPress site.

See [WordPress directory page](https://wordpress.org/plugins/sessions/). 

For each call made to your site, __Device Detector__ analyzes the sent header, detects the device doing the call (and its characteristics) and can:

* modify the `is_mobile()` WordPress core function to be more precise and reliable;
* add some CSS classes to the `body` tag of your site (many classes to choose from);
* let you use the result of the detection in your own developments;
* record detected characteristics for reporting.

__Device Detector__ can report the following main items and characteristics:

* KPIs: number of hits, class breakdown, clients types and engines;
* Classes: Bot, Desktop, Mobile and Other;
* Devices types: Camera, Car Browser, Console, Feature Phone, Phablet, Portable Media Player, Smart Display, Smartphone, Tablet, TV;
* Client types: Application Library, Browser, Feed Reader, Media Player, Mobile Application, PIM.
* Technical characteristics and versions of all browsers;
* Device identification: brand, model, etc.;
* OS identification: name, version, etc.;
* Calling channel: site backend, site frontend, cron job, Ajax request, XML-RPC request, Rest API request, Atom/RDF/RSS feed;

For a full list of items, characteristics and supported devices, please see the 'devices' tab in the plugin settings.

__Device Detector__ supports multisite report delegation and per site configuration (see FAQ).

Based on the amazing [Matomo](https://github.com/matomo-org/matomo) UDD, Device Detector is a free and open source plugin for WordPress. It integrates many other free and open source works (as-is or modified). Please, see 'about' tab in the plugin settings to see the details.

## Installation

1. From your WordPress dashboard, visit _Plugins | Add New_.
2. Search for 'Device Detector'.
3. Click on the 'Install Now' button.

You can now activate __Device Detector__ from your _Plugins_ page.

## Support

For any technical issue, or to suggest new idea or feature, please use [GitHub issues tracker](https://github.com/Pierre-Lannoy/wp-sessions/issues). Before submitting an issue, please read the [contribution guidelines](CONTRIBUTING.md).

Alternatively, if you have usage questions, you can open a discussion on the [WordPress support page](https://wordpress.org/support/plugin/sessions/). 

## Contributing

__Device Detector__ lets you use its detection features inside your own plugins or themes. To understand how it works and how to use it, please read the [developer documentation](DEVELOPER.md).

Before submitting an issue or a pull request, please read the [contribution guidelines](CONTRIBUTING.md).

> ⚠️ The `master` branch is the current development state of the plugin. If you want a stable, production-ready version, please pick the last official [release](https://github.com/Pierre-Lannoy/wp-sessions/releases).

## Smoke tests
[![WP compatibility](https://plugintests.com/plugins/sessions/wp-badge.svg)](https://plugintests.com/plugins/sessions/latest)
[![PHP compatibility](https://plugintests.com/plugins/sessions/php-badge.svg)](https://plugintests.com/plugins/sessions/latest)