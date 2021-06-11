# DecaLog
[![version](https://badgen.net/github/release/Pierre-Lannoy/wp-decalog/)](https://wordpress.org/plugins/decalog/)
[![php](https://badgen.net/badge/php/7.2+/green)](https://wordpress.org/plugins/decalog/)
[![wordpress](https://badgen.net/badge/wordpress/5.2+/green)](https://wordpress.org/plugins/decalog/)
[![license](https://badgen.net/github/license/Pierre-Lannoy/wp-decalog/)](/license.txt)

__Capture and log events, metrics and traces on your site. Make WordPress observable - finally!__

See [WordPress directory page](https://wordpress.org/plugins/decalog/) or [official website](https://perfops.one/decalog).

De-facto standard stack for WordPress observability, __DecaLog__ provides reliable and powerful logging, monitoring and tracing features for WordPress core, PHP, database, plugins and themes.

__DecaLog__ captures events generated by WordPress core, PHP, database, plugins and themes, collates metrics and KPIs and follows traces of the full WordPress execution. It has the ability to enrich these events, metrics and traces with many details regarding their triggering, before storing them in WordPress database or passing them to external services.

If you don't want to use external services, __DecaLog__ provides all the tools to leverage all the benefits of observability right in the admin dashboard. It supports multisite logs delegation and contains many features to help to protect personal information (user pseudonymization, IP obfuscation, etc.).

For a full list of supported - internal or third-party - services please, jump to the official [supported services list](https://perfops.one/decalog#services).

__DecaLog__ can be used in dev/debug phases or on production sites: it has nearly no resource impact on the server. It provides an extensive set of WP-CLI commands to help operations too.

> __DecaLog__ is part of [PerfOps One](https://perfops.one/), a suite of free and open source WordPress plugins dedicated to observability and operations performance.

## Installation

1. From your WordPress dashboard, visit _Plugins | Add New_.
2. Search for 'DecaLog'.
3. Click on the 'Install Now' button.

You can now activate __DecaLog__ from your _Plugins_ page.

## Support

For any technical issue, or to suggest new idea or feature, please use [GitHub issues tracker](https://github.com/Pierre-Lannoy/wp-decalog/issues). Before submitting an issue, please read the [contribution guidelines](CONTRIBUTING.md).

Alternatively, if you have usage questions, you can open a discussion on the [WordPress support page](https://wordpress.org/support/plugin/decalog/). 

## Contributing

__DecaLog__ lets you use its logging features inside your plugins or themes. To understand how it works and how to use it to log your own events, metrics and traces, please read the [DecaLog SDK Documentation](https://decalog.io/).

Before submitting an issue or a pull request, please read the [contribution guidelines](CONTRIBUTING.md).

> ⚠️ The `master` branch is the current development state of the plugin. If you want a stable, production-ready version, please pick the last official [release](https://github.com/Pierre-Lannoy/wp-decalog/releases).

## Smoke tests
[![WP compatibility](https://plugintests.com/plugins/decalog/wp-badge.svg)](https://plugintests.com/plugins/decalog/latest)
[![PHP compatibility](https://plugintests.com/plugins/decalog/php-badge.svg)](https://plugintests.com/plugins/decalog/latest)