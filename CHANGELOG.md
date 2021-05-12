# Changelog
All notable changes to **DecaLog** are documented in this *changelog*.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and **DecaLog** adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased - Will be 3.0.0]

### Added
- DecaLog now supports metrics collecting, forwarding and publishing.
- DecaLog now supports traces collecting and publishing.
- New "metrics" tab in settings to list all currently collected metrics.
- DecaLog now collects extended metrics for: PHP, WordPress core, database, WordPress multisite and plugins / themes using [WordPress DecaLog SDK](https://github.com/Pierre-Lannoy/wp-decalog-sdk).
- DecaLog now collects minimalist metrics for Wordfence.
- DecaLog now collects extended traces for WordPress core and plugins / themes using [WordPress DecaLog SDK](https://github.com/Pierre-Lannoy/wp-decalog-sdk).
- New logger to send events to Datadog.
- New logger to send metrics to a Prometheus instance (via Pushgateway).
- New logger to publish metrics as endpoint for Prometheus scrapping.
- New logger to send metrics to InfluxDB Cloud or to an on-premise InfluxDB 2 instance or cluster.
- New logger to send traces to a Jaeger collector.
- New logger to send traces to a Zipkin instance.
- [WP-CLI] New command to list, dump or get metrics: see `wp help log metrics` for details.
- [BC] The version of DecaLog API is now `v3`.

### Changed
- Redesigned loggers list.
- Upgraded Lock library from version 2.1 to version 2.2.
- Improved internal IP detection: support for cloud load balancers.
- [WP-CLI] All commands now support the `--stdout` flag.
- [WP-CLI] The `wp log logger list` command now accepts a logger id or logger type as filter parameter.
- [WP-CLI] The `wp log listener list` command now accepts a listener id as filter parameter.
- [WP-CLI] The `wp log type list` command now accepts a logger type as filter parameter.
- [WP-CLI] Updated documentation.
- Wordfence listener is much more precise while logging events.

### Fixed
- [WP-CLI] The `wp log send` doesn't set an exit code in case of failure.
- [WP-CLI] Typos in inline help.
- Messages may be wrongly truncated in live console.
- Detecting database version on WordPress prior to 5.5 generates an error (thanks to [Sébastien Gastard](https://profiles.wordpress.org/sgastard/)).

## [2.4.2] - 2021-03-01

### Removed
- Some currently unused logger types (will be in next release).

## [2.4.1] - 2021-03-01

### Changed
- [WP-CLI] Changing the color scheme for the `tail` command is now done via the `--theme=<theme>` parameter.

### Fixed
- [WP-CLI] There's an error in the `tail` command synopsis (thanks to [aspsa](https://wordpress.org/support/users/aspsa/)).
- Site Health may produce PHP notice while listing loggers details.

## [2.4.0] - 2021-02-25

### Added
- New logger to send crash reports to Bugsnag.
- New logger to send crash reports to Raygun.
- New logger to send exceptions to Google (Universal) Analytics.
- New logger to send logs to an Elasticsearch instance.
- New logger to send logs to a Loki instance.
- New logger to send logs to Grafana Cloud service.
- New listener for bbPress.
- New listener for BuddyPress.
- New listener for Action Scheduler library.
- New listeners for Stripe gateways (standard, Amelia and Forminator) libraries.
- New 'environment' context metadata for all external loggers.
- Compatibility with WordPress 5.7.
- New setting to override live console and local access privileges when in development or staging environments (thanks to [sebastienserre](https://github.com/sebastienserre) for the suggestion).
- New automatic bootstrap listener allowing to catch PHP events occurring before DecaLog is loaded.
- It's now possible to filter by session ID in WordPress events viewer.
- The current session user blinks in WordPress events viewer.
- Core listener now reports stuck/unstuck posts.
- New "early-loading auto diagnostic" in Site Health.
- The WordPress events viewer allows to manage sessions for a user if [Sessions](https://wordpress.org/plugins/sessions/) plugin is installed.

### Changed
- When OPcache API is restricted by `restrict_api` configuration directive, OPcache configuration is no more monitored.
- Detection of PHP version upgrade/downgrade now takes care of web vs. command-line difference.
- Detection of OPcache configuration changes now takes care of web vs. command-line difference.
- DecaLog now propagates `traceID` and `sessionID` for all loggers supporting it.
- Upgraded Monolog library from version 2.0.2 to version 2.2.0.
- Consistent reset for settings.
- Improved translation loading.
- Now detects and emits warning when a call is sandboxed (theme/plugin editor, for instance).
- Improved display in WordPress event viewer when backtraces are not available.
- [WP_CLI] `log` command have now a definition and all synopsis are up to date.
- The display of verb labels in events viewer has been improved.
- Better PHP introspection and backtrace cleaning, parsing and rendering.
- Check on "mandatory" processors are now done for loggers which need it.
- Improved hash handling and reporting for users and IPs.
- Improved self monitoring to handle loggers internal errors.
- Code refactoring led to a huge execution speed gain: DecaLog is now 30% faster.
- Improved message for deleted posts/pages in core listener.
- Elastic Cloud logger is renamed for better consistency.
- Now fully detects versions for MariaDB, Percona, MySQL and PostgreSQL (and so, fully detects migrations and upgrades).

### Fixed
- [SEC002] The password for Elastic Cloud logger is in plain text in "Site Health Info" page.
- DecaLog doesn't correctly honour previous error handler calls (thanks to [ajoah](https://github.com/ajoah)).
- DecaLog jams the plugin/theme editor while editing PHP files (thanks to [ajoah](https://github.com/ajoah)).
- In Site Health section, Opcache status may be wrong (or generates PHP warnings) if OPcache API usage is restricted.
- PHP notice when trying to display details for "System auto-logger".
- DecaLog may log multiple times a change about environment type.
- When disabling "early loading", the mu-plugin is not always removed.

### Removed
- DecaLog internal watchdog as it is no longer necessary.

## [2.3.0] - 2020-11-23

### Added
- Supports for new application passwords events (WordPress 5.6 and higher).
- Compatibility with WordPress 5.6.

### Changed
- Improvement in the way roles are detected.
- Console now starts automatically in the admin dashboard if checked.
- Anonymous proxies, satellite providers and private networks are now fully detected when [IP Locator](https://wordpress.org/plugins/ip-locator/) is installed.
- Better web console layout.

### Fixed
- [SEC001] User may be wrongly detected in XML-RPC or Rest API calls.
- Deleting a comment may trigger an error (thanks to [jimmy19742](https://wordpress.org/support/users/jimmy19742/)).
- When site is in english and a user choose another language for herself/himself, menu may be stuck in english.
- When shared memory is not available, it is wrongly reported as an emergency.

## [2.2.2] - 2020-10-16

### Fixed
- The DecaLog menus may be hidden when they should be visible (thanks to [Emil1](https://wordpress.org/support/users/milouze/)).

## [2.2.1] - 2020-10-13

### Changed
- Hardening (once again) IPs detection.
- Prepares PerfOps menus to future 5.6 version of WordPress.

### Fixed
- [WP_CLI] The command `wp log type list --format=table` sometimes triggers an error. 
- The remote IP can be wrongly detected when in AWS or GCP environments.

## [2.2.0] - 2020-10-11

### Added
- DecaLog now integrates [Spyc](https://github.com/mustangostang/spyc) as yaml parser.
- DecaLog now warns user when "DEBUG" level is chosen for a logger.

### Changed
- Strongly improved yaml and json output for `wp log type list`, `wp log logger list` and `wp log listener list`.
- For loggers allowing it, default level is now "INFO" (it was previously "DEBUG").
- [WP-CLI] Improved documentation.

### Fixed
- The remote IP can be wrongly detected when behind some types of reverse-proxies.
- [WP-CLI] The `wp log type list --format=json` fails to render right json output.

## [2.1.0] - 2020-10-05

### Added
- [WP-CLI] PHP shmop module status added to the `wp log status` command.

### Fixed
- [WP-CLI] With some PHP configurations, there may be a (big) delay in the display of lines.
- In wp-cli help, some arguments are not described.
- Some typos in wp-cli help.

## [2.0.1] - 2020-10-03

### Changed
- Improved IP detection  (thanks to [Ludovic Riaudel](https://github.com/lriaudel)).
- Improved orders and sections in "options" settings tab.

### Fixed
- Console source code is not fully compatible with PHP 7.2.

## [2.0.0] - 2020-09-30

### Added
- New live console-in-browser to see events as soon as they occur.
- [WP-CLI] New command to display (past or current) events in console: see `wp help log tail` for details.
- [WP-CLI] New command to display DecaLog status: see `wp help log status` for details.
- [WP-CLI] New command to send messages to running loggers: see `wp help log send` for details.
- [WP-CLI] New command to toggle on/off main settings: see `wp help log settings` for details.
- [WP-CLI] New command to manage loggers (list, start, pause, clean, purge, remove, add and set): see `wp help log logger` for details.
- [WP-CLI] New command to view available logger types (list and describe): see `wp help log type` for details.
- [WP-CLI] New command to manage listeners (list, enable, disable and auto-listening on/off): see `wp help log listener` for details.
- New tab in plugin settings for WP-CLI commands.
- New Site Health "info" section about shared memory.
- A warning is shown in the settings page if `shmop` PHP module is not enabled.

### Changed
- The PHP listener now takes care of activated/deactivated modules between web server and command-line configurations.
- The consistency checker has been improved. 
- Improved layout for language indicator.
- If GeoIP support is not done via [IP Locator](https://wordpress.org/plugins/ip-locator/), the flags are now correctly downgraded to emojis.
- Improved file names and paths normalization in backtraces.
- In WordPress viewer, the client detail is now "Local shell" if the call is made from local command-line.
- Admin notices are now set to "don't display" by default.
- The integrated markdown parser is now [Markdown](https://github.com/cebe/markdown) from Carsten Brandt.

### Fixed
- The rotating file logger wrongly skips events when sent from external process.
- For some logger types the minimal level may be wrongly set to "debug" at creation.
- Some typos in processors' names.
- The WordPress viewer may display wrong details about "generic" devices.
- The call to OPcache functions may trigger a PHP warning.
- With Firefox, some links are unclickable in the Control Center (thanks to [Emil1](https://wordpress.org/support/users/milouze/)).

### Removed
- The "HTTP request" box of WordPress viewer is no more displayed if the event is triggered from local command-line.
- It's no more possible to modify/remove/start/pause a system logger.
- Parsedown as integrated markdown parser.

## [1.14.0] - 2020-09-04

### Added
- New logger to send logs to Sumo Logic cloud-syslog.
- New listener for UpdraftPlus Backup/Restore plugin.
- New listener for iThemes Security plugin.
- Detection of environment type changes (feature introduced in WordPress 5.5).

### Changed
- The Syslog logger can now send extended timestamps (RFC5424) if needed.
- The positions of PerfOps menus are pushed lower to avoid collision with other plugins. (thanks to [Loïc Antignac](https://github.com/webaxones)).
- The selector for WordPress events logs is now sorted: running first, paused after (thanks to [Loïc Antignac](https://github.com/webaxones)).

### Fixed
- Some typos in "add a logger" screen.

## [1.13.0] - 2020-07-20

### Added
- Compatibility with WordPress 5.5.

### Changed
- Optimized early loading.
- Improved installation/uninstallation and activation/deactivation processes.
- In WordPress logger, the shown columns are now automatically set.

### Fixed
- Uninstalling the plugin may produce a PHP error (thanks to [Emil1](https://wordpress.org/support/users/milouze/)).
- In some conditions, some tables may not be deleted while uninstalling.

### Removed
- The screen options in WordPress logger (as it is now automatically set).

## [1.12.8] - 2020-07-15

### Fixed
- PHP deprecated warning emitted while debugging WordPress cache.
- The WordPress events may be not purged when it should be (thanks to [Emil1](https://wordpress.org/support/users/milouze/)).

## [1.12.7] - 2020-06-29

### Changed
- Full compatibility with PHP 7.4.
- Automatic switching between memory and transient when a cache plugin is installed without a properly configured Redis / Memcached.

### Fixed
- The WordPress events may be wrongly purged when '0' is set as a limit (thanks to [Emil1](https://wordpress.org/support/users/milouze/)).

## [1.12.6] - 2020-05-15

### Changed
- Supports now Wordfence alerting system inconsistency.

### Fixed
- When used for the first time, settings checkboxes may remain checked after being unchecked.

## [1.12.5] - 2020-05-05

### Changed
- The WordPress events tables are now deleted when plugin is uninstalled.

### Fixed
- There's an error while activating the plugin when the server is Microsoft IIS with Windows 10.
- Some tabs may be hidden when site is switched in another language.
- With Microsoft Edge, some layouts may be ugly.

### Removed
- The "channel" starting the "message" from Stackdriver formatter, because channel is now usable as "summary field" in Stackdriver interface.

## [1.12.4] - 2020-04-10

### Changed
- Improved way to handle fatal errors in PHP listener.
- "Updated user" messages have now "INFO" type (was "NOTICE" previously).
- "Logged-in user" messages have now "NOTICE" type (was "INFO" previously).

### Fixed
- Some main settings may be not saved.

## [1.12.3] - 2020-04-07

### Changed
- Forces mu-plugin dir creation if it doesn't exist.

### Fixed
- The doc blocks of some classes wrongly reference Fluentd (thanks to [Nicolas Juen](https://github.com/Rahe)).

## [1.12.2] - 2020-04-07

### Changed
- Removes the mu-plugin to the plugin update list.

## [1.12.1] - 2020-04-07

### Fixed
- The version number of the mu-plugin is wrong.

## [1.12.0] - 2020-04-07

### Added
- New logger to send logs to Elastic Cloud / Elastic Cloud Enterprise.
- New logger to send logs to Sematext.
- New option to early load DecaLog as a mu-plugin.
- Full integration with [IP Locator](https://wordpress.org/plugins/ip-locator/).

### Changed
- There's now a flag for each IP address in WordPress events logs (when a GeoIP detection handler is installed).
- The settings page has now the standard WordPress style.
- Better styling in "PerfOps Settings" page.
- In site health "info" tab, the boolean are now clearly displayed.
- Displaying of IPv6 addresses has been improved.

### Fixed
- The update indicator is sometimes hidden.
- If there's no GeoIP detection handler, a wrong flag is shown for public IPs.
- An error may appear when updating plugin's empty tables.
- Some placeholders (in text input) may have a wrong example value.

### Removed
- Dependency to "Geolocation IP Detection" plugin. Nevertheless, this plugin can be used as a fallback solution.
- Flagiconcss as library. If there's no other way, flags will be rendered as emoji.
- Integrated migration helpers prior to 1.9.x.

## [1.11.0] - 2020-03-09

### Added
- New logger to send logs to Google Stackdriver via Google-Fluentd.

### Changed
- Improved IP detection for multi-proxying.
- Double quotation mark `"` is now replaced by a left double quotation mark `“` in event message, context and extra.
- Single quotation mark `'` is now replaced by a grave accent in event message, context and extra.
- "HTTP request" reported details have been fully redesigned.
- Better styling in "PerfOps Settings" page.

### Fixed
- In some cases, the referer and method are wrongly detected.
- In some cases, the "screen options" tab may be invisible.

## [1.10.0] - 2020-03-01

### Added
- New listener for Wordfence plugin.
- Full integration with PerfOps.One suite.
- Compatibility with WordPress 5.4.

### Changed
- New menus (in the left admin bar) for accessing features: "PerfOps Records" and "PerfOps Settings".

### Fixed
- The shutdown action of [APCu Manager](https://wordpress.org/plugins/apcu-manager/) can cause a PHP notice in DecaLog.

### Removed
- Compatibility with WordPress versions prior to 5.2.
- Old menus entries, due to PerfOps integration.

## [1.9.1] - 2020-02-13

### Changed
- The method for remote IP detection has been improved.
- All loggers are now paused when deactivating/reactivating DecaLog (thanks to [jimmy19742](https://wordpress.org/support/users/jimmy19742/)).

### Fixed
- Warning is generated when WP Security Audit Log set out-of-scope severity levels.

## [1.9.0] - 2020-01-22

### Added
- New listener for WP-Optimize plugin.
- New listener for Redirection plugin.
- Full compatibility with [Device Detector](https://wordpress.org/plugins/device-detector/).
- Full compatibility with [APCu Manager](https://wordpress.org/plugins/apcu-manager/).
- The user-agent is now collected as an extra field when HTTP request is selected as reported details.
- In WordPress logger, if Device Detector is installed, a new box displays device, bot or client details.
- In WordPress logger, if GeoIP is installed, a flag is displayed after the "from" IP.

### Changed
- The number limit of items in traces is now fixed at 40.

### Fixed
- Typos in developer's documentation.

### Removed
- The self reference in recorded stack traces.

## [1.8.0] - 2020-01-02

### Added
- Full compatibility (for internal cache) with Redis and Memcached.
- Using APCu rather than database transients if APCu is available.
- New Site Health "status" sections about OPcache and object cache. 
- New Site Health "status" section about i18n extension for non `en_US` sites.
- New Site Health "info" sections about OPcache and object cache.
- New Site Health "info" section about the plugin itself.
- New Site Health "info" section about loggers settings. 

### Changed
- Upgraded Monolog library from version 2.0.1 to version 2.0.2.
- The SQL listener now generates multiple critical errors if there's more than one SQL error during page rendering.

### Fixed
- Updating plugin from prior versions may generates a (innocuous) warning.

## [1.7.2] - 2019-12-18

### Changed
- Error message field can now handle up to 64K characters in WordPress events logs.
- Traces can now contain up to 64K characters in WordPress events logs.
- All fields can now handle emojis.
- The cleaning cron job is now launched hourly.

### Fixed
- Some debug events might be ignored when they shouldn't.
- Some plugin options may be not saved when needed (thanks to [Lucas Bustamante](https://github.com/Luc45)).

## [1.7.1] - 2019-12-12

### Changed
- Improved layout for mobile usage. 

### Fixed
- In rare conditions, displayed/hidden columns in WordPress logger are not saved.

## [1.7.0] - 2019-12-03

### Added
- Full compatibility with [MailArchiver](https://wordpress.org/plugins/mailarchiver/).

### Changed
- The status of the user in the event viewer is now clearly visible. 

### Fixed
- Removing a WordPress logger may produce a wrong backtrace. 
- The selector for number of displayed lines (in WordPress logger) show sometimes a wrong value.

### Removed
- As a result of the Plugin Team's request, the auto-update feature has been removed.

## [1.6.1] - 2019-11-22

### Changed
- Upgraded Monolog library from version 2.0.0 to version 2.0.1.
- Events for `wp_ajax_sample_permalink` hook are now rendered at debug level.
- The events levels from WP Security Audit Log listener are now more consistent. 
- Unit symbols and abbreviations are now visually differentiated.
- There's now a non-breaking space between values and units.

### Fixed
- Some very long fields may be displayed outside the box in the WordPress events logs. 
- With some OPcache configurations there may be PHP warning in "CoreListener".
- Some cached items may not be deleted when needed.

## [1.6.0] - 2019-11-11

### Added
- New listener for WooCommerce plugin.
- New listener for Jetpack plugin.
- New listener for WP Security Audit Log plugin.
- New listener for W3 Total Cache plugin.
- New listener for WP Super Cache plugin.
- New logger to send logs to Solawinds Loggly.
- New logger to send logs to Logentries / insightOps.
- New "Content" box in event viewer (for WordPress events logs) to display detailed error code and message.

### Changed
- PHP listener now detects all OPcache resets or status changes.
- Message size (for WordPress events logs) has been increased from 1000 to 7500 characters.
- Improved display for message column in WordPress events logs.
- Upgraded Feather library from version 4.22.1 to version 4.24.1.
- The PHP and WordPress backtraces are now cleaned from DecaLog and Monolog references.
- The name and help message for backtraces settings are more clear.
- cURL timeouts have now an "error" level (it was previously a "critical" one).

### Fixed
- Non blocking HTTP request may sometimes generate a "Global Timeout" event in core listener (thanks to [Julio Potier](https://github.com/JulioPotier)).
- Changelog date of version 1.5.3 is wrong.

### Security
- [PRV001] In case of failed login, the username may appear in clear text logs even if pseudonymisation is activated.

## [1.5.3] - 2019-11-01

### Fixed
- The message for PHP upgrading/downgrading was wrongly named "WordPress" (instead of "PHP").
- A PHP notice may appear when enqueuing some plugin assets.

## [1.5.2] - 2019-10-24

### Changed
- Normalization of cache IDs to avoid name collisions.
- Developer's documentation modified as wp.org [now allows](https://meta.trac.wordpress.org/ticket/3791) PHP7.1+ code.

### Fixed
- Some cached elements may be autoloaded even if not needed.
- [MultiSite] The "what's new?" screen is only viewable by network admin.
- [MultiSite] Action link in sites list for network admins.
- [MultiSite] Action link in "my sites" for local admins.

## [1.5.1] - 2019-10-05

### Changed
- New logo, more in line with the plugin topic.
- The (nag) update message has now a link to display changelog.

## [1.5.0] - 2019-10-04

### Added
- Compatibility with WordPress 5.3.
- It's now possible to use public CDN to serve DecaLog scripts and stylesheets (see _Settings | DecaLog | Options_).

### Changed
- Finally better IP reporting with local address fallback.
- Improved information message when in developer preview or release candidate version.
- The right logo is now displayed in the "about box".

### Removed
- "Compatibility Mode" for Monolog 2, as wp.org [now allows](https://meta.trac.wordpress.org/ticket/3791) PHP7.1+ code.

## [1.4.1] - 2019-09-17

### Changed
- Better IP reporting with local address fallback.
- Better timezone detection for multisites.

### Fixed
- In some cases, the remote IP is not correctly set (for sites behind a proxy).

## [1.4.0] - 2019-09-13

### Added
- Full support for [User Switching](https://wordpress.org/plugins/user-switching/) plugin.
- New events in core listener for posts (trashed, untrashed, drafted, draft saved, published, privately published, scheduled, unscheduled, pending review).
- New events in core listener for comments (created, updated, deleted, marked as "spam", marked as "not spam", trashed, untrashed, approved, unapproved, duplicate triggered).
- New events in core listener for menus (created, updated, deleted, item added, item updated).
- New events in core listener for users (updated, role added).
- New events in WPMU listener for users (marked as "spam", marked as "not spam").
- The core listener can now detect plugin and theme installations/updates.
- The core listener can now detect translations updates.

### Changed
- Plugin activation/deactivation give the full plugin name (instead of its slug).
- Events regarding "options" and "transients" now differentiate site/network operations.
- Events relating to HTTP error codes (outbound requests) are now classified according to their severity.
- The word "blog" has been replaced by "site" in events messages.
- Some help strings have been modified to be more clear.
- Lower severity of serialized json messages (triggered by `wp_die` handler) from criticial to debug.

### Removed
- "Switch Blog" event (for WPMU) because lack of documentation.

### Fixed
- PHP notice when accessing a multisites log as a local admin.
- PHP notice when displaying an event with no backtrace.
- Removing a user of a site may produce an inconsistent log message.

## [1.3.0] - 2019-09-11

### Added
- New listener for WordPress multisite specific events.
- New listener for [htaccess Server-Info & Server-Status](https://wordpress.org/plugins/htaccess-server-info-server-status/) plugin.
- Links to support, site and GitHub repository in plugin list.
- New class (Decalog\Logger) to use as a standard PSR-3 logger (for plugins and themes developers).
- New shortcodes to query the plugin statistics.

### Removed
- WordPress release now excludes GitHub .wordpress-org directory.

### Fixed
- A WordPress logger may record log in the wrong table in multisites instances.

## [1.2.1] - 2019-09-05

### Changed
- Pushover logger now allows to set socket timeout.
- Watchdog (for self listening) is now fully operational for PHP and MySQL channels.

### Fixed
- ChromePHP logger header size limit is unsuitable for the most recent Chromium versions (thanks to [dotMastaz](https://github.com/dotMastaz)).

## [1.2.0] - 2019-08-31

### Added
- The WordPress listener can now detect version upgrading/downgrading.
- The PHP listener can now detect version upgrading/downgrading.
- The PHP listener can now detect extensions activation/deactivation.
- The database listener can now detect version upgrading/downgrading.
- The plugin now embeds its own inline help (help tab in all screens).

### Changed
- Upgraded Monolog version from 2.0.0-beta-2 to 2.0.0.

### Security
- In a multisite, a non-admin user was able to read the name of a logger (not its content).

## [1.1.1] - 2019-08-29

### Changed
- Better 'Page not found' detection and report.
- Better handling of malformed `wp_die` calls.
- 'Component' become 'source' in WordPress events viewer.
- Some events have new levels and/or messages to comply with rules described in `DEVELOPER.md`.

### Fixed
- WordPress formatter may (wrongly) emit warnings when source ip is unknown.

## [1.1.0] - 2019-08-28

### Added
- A test message can now be sent to a specific logger, from the loggers list.

### Changed
- Status (in loggers list) have now their own column.
- Polishing loggers list.
- The `README.md` displays badges.

### Removed
- DecaLog admin pages no longer render emoji.
- The section "install from GitHub" has been removed from `README.md`.

### Fixed
- Url of the EFF website was wrong in `readme.txt`.

## [1.0.1] - 2019-08-26

### Changed
- Language file (`.pot`) is updated.
- Main file has now a consistent GPL version license.
- In admin dashboard, `&` character is now outputted in UTF, not with its HTML entity equivalent.

### Fixed
- Erroneous date in `CHANGELOG.md` is changed.
- Wrongly rendered unordered lists in `readme.txt` are fixed.

## [1.0.0] - 2019-08-26

Initial release
