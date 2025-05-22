DecaLog is fully usable from command-line, thanks to [WP-CLI](https://wp-cli.org/). You can set DecaLog options, view past or current triggered events and much more, without using a web browser.

1. [Viewing events](#viewing-events) - `wp log tail`
2. [Triggering events](#triggering-events) - `wp log send`
3. [Accessing metrics](#accessing-metrics) - `wp log metrics`
4. [Managing loggers](#managing-loggers) - `wp log logger`
5. [Using logger types](#managing-loggers) - `wp log type`
6. [Managing listeners](#managing-loggers) - `wp log listener`
7. [Getting DecaLog status](#getting-decalog-status) - `wp log status`
8. [Managing main settings](#managing-main-settings) - `wp log settings`
9. [Misc flags](#misc-flags)
10. [Piping and storing](#piping-and-storing)

## Viewing events

DecaLog lets you use command-line to view past and currents events. All is done via the `wp log tail [<count>] [--level=<level>] [--filter=<filter>] [--format=<format>] [--col=<columns>] [--theme=<theme>] [--yes]` command.

If you don't specify `<count>`, DecaLog will launch an interactive logging session: it will display events as soon as they occur on your site. To quit this session, hit `CTRL+C`.

If you specify a value for `<count>` between 1 to 60, DecaLog will show you the *count* last events triggered on your site.

> Note the `tail` command needs shared memory support on your server, both for web server and command-line configuration. If it's not already the case, you must activate the ***shmop*** PHP module.

Whether it's an interactive session or viewing past events, you can filter what is displayed as follows:

### Minimal level

To display only events having a minimal level, use `--level=<level>` parameter. `<level>` can be `info`, `notice`, `warning`, `error`, `critical`, `alert` or `emergency`.

> Note you can not use `debug` as minimal level because the internal logger which capture events for the tail command rejects them for this level.

### Field filters

You can filter displayed events on fields too. To do it, use the `--filter=<filter>` parameter. `<filter>` is a json string containing "field":"regexp" pairs. The available fields are: 'channel', 'message', 'class', 'source', 'code', 'site_id' (on multisite only), 'user_id', 'remote_ip', 'url', 'verb', 'server', 'referrer', 'file', 'line', 'classname' and 'function'.

Each regular expression must be surrounded by `/` like that: `"remote_ip":"/(135\.|164\.)/"` and the whole filter must start with `'{` and end with `}'` (see examples).

### Outputted format

You can tailor the outputted format to fit your needs with the `--format=<format>` parameter. There's 3 available formats:
- `wp`: that's the default format; it shows (after the standard details) the source component and the message.
- `http`: it shows (after the standard details) the verb (get, post, etc.), IP source and relative url.
- `php`: it shows (after the standard details) in which portion of code the event was triggered.

### Columns count

By default, DecaLog will output each event string on a 160 character basis. If you want to change it, use `--col=<columns>` where `<columns>` is an integer between 80 and 400.

### Colors scheme

To change the default color scheme to something more *eyes-saving*, use `--theme`.

If you prefer, you can even suppress all colorization with the standard `--no-color` flag.

### Examples

To see all "live" events, type the following command:
```console
pierre@dev:~$ wp log tail
...
```

To see past alerts for user 1, type the following command:
```console
pierre@dev:~$ wp log tail 20 --filter='{"user_id":"/1+/"}' --level=alert
...
```

To see "live" events only triggered by WordPress core on cron calls, type the following command:
```console
pierre@dev:~$ wp log tail --filter='{"channel":"/cron/", "class":"/core/"}'
...
```

To see "live" error that occur with specific http verbs on Rest API calls, type the following command:
```console
pierre@dev:~$ wp log tail --filter='{"verb":"/(options|connect|trace)/", "class":"/api/"}' --level=error --format=http
...
```

## Triggering events

You can trigger events right from your scripts with the `wp log send <info|notice|warning|error|critical|alert> <message>` command where `<message>` is a string surrounded by quotes.

> This method is reserved to scripts; if you're a theme or plugin developer, you can trigger events right from your theme or plugin by using the [DecaLog SDK](https://decalog.io/).

### Error code

You can specify a numerical error code with `--code=<code>`. If not specified, the error code will be 0.

### Examples

To trigger a simple notice event, type the following command:
```console
pierre@dev:~$ wp log send notice 'All good!'
Success: message sent.
```

To trigger an error event with a 500 error code, type the following command:
```console
pierre@dev:~$ wp log send error 'Something went wrong' --code=500
Success: message sent.
```

## Accessing Metrics

With the `wp log metrics <list|dump|get> [<metrics_id>] [--format=<format>] [--detail=<detail>] [--stdout]` command you can get insight about metrics .

> Execution time for page rendering and initialization / shutdown sequences are not available in command line mode.

### Listing metrics

To get a list of currently collated metrics, use `wp log metrics list`.

### Dumping metrics

To dump all the currently collated metrics, use `wp log metrics dump`.

### Getting metric value

To get the value of a specific metric, use `wp log metrics get`.

### Examples

To get the value of the `wordpress_php_php_execution_latency` metric, type the following command:
```console
pierre@dev:~$ wp log metrics get wordpress_php_php_execution_latency
Success: wordpress_php_php_execution_latency current value is 4.0021340847015
```

## Managing loggers

With the `wp log logger <list|start|pause|clean|purge|remove|add|set> [<uuid_or_type>] [--settings=<settings>] [--detail=<detail>] [--format=<format>] [--yes] [--stdout]` command you can perform all available operations on loggers.

### Listing loggers

To obtain a list of set loggers, use `wp log logger list`.

### Starting or pausing loggers

To change the status of a logger, use `wp log logger <start|pause> <uuid>` where `<uuid>` is the identifier of the logger.

### Cleaning or purging loggers

Some loggers allow to be cleaned (deletion of stale records) or purged (deletion of all records). To initiate such an operation on a logger, use `wp log logger <clean|purge> <uuid>` where `<uuid>` is the identifier of the logger.

### Removing a logger

To permanently remove a logger, use `wp log logger remove <uuid>` where `<uuid>` is the identifier of the logger.

### Modifying a logger

To modify a logger, use `wp log logger set <uuid> --settings=<settings>` where: 

- `<uuid>` is the identifier of the logger.
- `<settings>` a json string containing ***"parameter":value*** pairs. The available parameters can be browsed with the `wp log type describe` command (see [using logger types](#managing-loggers)).
                                                                                                                                         
`<settings>`  must start with `'{` and end with `}'` (see examples).

### Adding a logger

To add a logger, use `wp log logger add <type> --settings=<settings>` where:

- `<type>` is the type of the logger. The available types can be obtained with the `wp log type list` command (see [using logger types](#managing-loggers)).
- `<settings>` a json string containing ***"parameter":value*** pairs. The available parameters can be browsed with the `wp log type describe` command (see [using logger types](#managing-loggers)).
                                                                                                                                         
`<settings>`  must start with `'{` and end with `}'` (see examples).

### Examples

To list the loggers, type the following command:
```console
pierre@dev:~$ wp log logger list
+--------------------------------------+-------------------------+---------------------------------+---------+
| uuid                                 | type                    | name                            | running |
+--------------------------------------+-------------------------+---------------------------------+---------+
| f1ee25c7-d9fe-42ee-86df-9394b411e2a7 | Pushover                | Alerting for helpdesk team      | no      |
| 93f84673-a623-4c15-825e-d867f35565ff | ChromePHP               | Debug in Chrome                 | no      |
| 078e124b-2122-4f03-91e9-2bbf70964618 | Browser console         | Debug in FireFox                | no      |
| 9553830a-75e7-4405-80c5-8bf726ccf45c | PHP error log           | Detailed logs                   | no      |
| 37cf1c00-d67d-4e7d-9518-e579f01407a7 | WordPress events log    | Events shared with sites admins | yes     |
| 5bacf078-2a1f-4c43-8961-4d8ca647661b | Rotating files          | Files on backup server          | yes     |
| df59a30d-dc30-4771-a4a5-a654f0a5cd46 | Fluentd                 | Full logs for StackDriver       | no      |
| 6d9943e5-b4fa-4dee-8b02-93a9294b1373 | Syslog                  | Logs on Synology                | no      |
| dce759b8-e9e8-4e9c-b62a-7d5c9fe160bf | Shared memory           | System auto-logger              | yes     |
| 8e2ee516-6f8d-40d1-ac16-c3e61274a41a | Slack                   | Warnings in #SysOps channel     | no      |
+--------------------------------------+-------------------------+---------------------------------+---------+
```

To start the logger identified by 'c40c59dc-5e34-44a1-986d-e1ecb520e3ca', type the following command:
```console
pierre@dev:~$ wp log logger start c40c59dc-5e34-44a1-986d-e1ecb520e3ca
Success: logger c40c59dc-5e34-44a1-986d-e1ecb520e3ca is now running.
```

To purge the logger identified by 'c40c59dc-5e34-44a1-986d-e1ecb520e3ca' without confirmation prompt, type the following command:
```console
pierre@dev:~$ wp log logger purge c40c59dc-5e34-44a1-986d-e1ecb520e3ca --yes
Success: logger c40c59dc-5e34-44a1-986d-e1ecb520e3ca successfully purged.
```

To remove the logger identified by 'c40c59dc-5e34-44a1-986d-e1ecb520e3ca' without confirmation prompt, type the following command:
```console
pierre@dev:~$ wp log logger remove c40c59dc-5e34-44a1-986d-e1ecb520e3ca --yes
Success: logger c40c59dc-5e34-44a1-986d-e1ecb520e3ca successfully removed.
```

To change the settings of the logger identified by 'c40c59dc-5e34-44a1-986d-e1ecb520e3ca', type the following command:
```console
pierre@dev:~$ wp log logger set c40c59dc-5e34-44a1-986d-e1ecb520e3ca --settings='{"proc_trace": false, "level":"warning"}'
Success: logger c40c59dc-5e34-44a1-986d-e1ecb520e3ca successfully set.
```

To add a WordPress logger, type the following command:
```console
pierre@dev:~$ wp log logger add WordpressHandler --settings='{"rotate": 8000, "purge": 5, "level":"warning", "proc_wp": true}'
Success: logger 5b09be13-16f6-4ced-972e-98408df0fd49 successfully created.
```

## Using logger types

With the `wp log type <list|describe> [<logger_type>] [--format=<format>]` command you can query all available types for logger creation / modification and obtain description of corresponding settings. This command helps you to fine-tune loggers via the command-line.

### Listing types

To obtain a list of available types, use `wp log type list`.

### Describing types

To obtain the detail of a specific type, use `wp log type describe <logger_type>` where `<logger_type>` is one of the type listed by the `wp log type list` command.

In addition to a general description "sheet", this command outputs a detailed listing of the available settings that can be used in the `wp log logger set` and `wp log logger add` commands.

### Examples

To list the types, type the following command:
```console
pierre@dev:~$ wp log type list
+-----------------------+-----------+-------------------------+------------+
| type                  | class     | name                    | version    |
+-----------------------+-----------+-------------------------+------------+
| BrowserConsoleHandler | debugging | Browser console         | 2.0.2      |
| ChromePHPHandler      | debugging | ChromePHP               | 2.0.2      |
| ElasticCloudHandler   | logging   | Elastic Cloud           | 2.0.0-dev1 |
| FluentHandler         | logging   | Fluentd                 | 2.0.0-dev1 |
| LogentriesHandler     | logging   | Logentries & insightOps | 2.0.0-dev1 |
| LogglyHandler         | logging   | Loggly                  | 2.0.2      |
| MailHandler           | alerting  | Mail                    | 2.0.0-dev1 |
| ErrorLogHandler       | logging   | PHP error log           | 2.0.2      |
| PshHandler            | alerting  | Pushover                | 2.0.0-dev1 |
| RotatingFileHandler   | logging   | Rotating files          | 2.0.2      |
| SematextHandler       | logging   | Sematext                | 2.0.0-dev1 |
| SlackWebhookHandler   | alerting  | Slack                   | 2.0.2      |
| StackdriverHandler    | logging   | Stackdriver             | 2.0.0-dev1 |
| SumoSysHandler        | logging   | Sumo Logic cloud-syslog | 2.0.0-dev1 |
| SyslogUdpHandler      | logging   | Syslog                  | 2.0.2      |
| WordpressHandler      | logging   | WordPress events log    | 2.0.0-dev1 |
+-----------------------+-----------+-------------------------+------------+
```

To obtain details about the WordpressHandler type, type the following command:
```console
pierre@dev:~$ wp log type describe WordpressHandler
              
WordPress events log - WordpressHandler
An events log stored in your WordPress database and available right in your admin dashboard.

Minimal Level

debug

Parameters

* Name - Used only in admin dashboard.
  - field name: name
  - field type: string
  - default value: "New Logger"

* Minimal level - Minimal reported level.
  - field name: level
  - field type: string
  - default value: "debug"
  - available values:
     "emergency": A panic condition. WordPress is unusable.
     "alert": A major operating error that undoubtedly affects the operations. It requires immediate investigation and corrective treatment.
     "critical": An operating error that undoubtedly affects the operations. It requires investigation and corrective treatment.
     "error": A minor operating error that may affects the operations. It requires investigation and preventive treatment.
     "warning": A significant condition indicating a situation that may lead to an error if recurring or if no action is taken. Does not usually affect the operations.
     "notice": A normal but significant condition. Now you know!
     "info": A standard information, just for you to know… and forget!
     "debug": An information for developers and testers. Only used for events related to application/system debugging.

* Events - Maximum number of events stored in this events log (0 for no limit).
  - field name: rotate
  - field type: integer
  - default value: 10000
  - range: [0-10000000]

[...]

* Reported details: Backtrace - Allows to log the full PHP and WordPress call stack.
  - field name: proc_trace
  - field type: boolean
  - default value: false

Example

{"rotate": 10000, "purge": 15}

```

## Managing listeners

With the `wp log listener <list|enable|disable|auto-on|auto-off> [<listener_id>] [--detail=<detail>] [--format=<format>] [--yes] [--stdout]` command you can perform all available operations on listeners.

### Listing listeners

To obtain a list of available listeners, use `wp log listener list`.

### Enabling or disabling listeners

To enable or disable a listener, use `wp log listener <enable|disable> <listener_id>` where `<listener_id>` is the identifier of the listener.

> You can individually enable or disable a listener nevertheless, if DecaLog is set to "auto-on", it will have no effect: all available listeners will be listening.

### Auto listening

To change auto-listening mode, use `wp log listener <auto-on|auto-off>`.

### Examples

To list the listeners, type the following command:
```console
pierre@dev:~$ wp log listener list
+----------------+----------------------------+-----------+---------+
| id             | name                       | available | enabled |
+----------------+----------------------------+-----------+---------+
| wpdb           | Database                   | yes       | auto    |
| itsec          | iThemes Security           | no        | auto    |
| jetpack        | Jetpack                    | yes       | auto    |
| php            | PHP                        | yes       | auto    |
| psr3           | PSR-3 compliant listeners  | yes       | auto    |
| redirection    | Redirection                | yes       | auto    |
| updraftplus    | UpdraftPlus Backup/Restore | no        | auto    |
| user-switching | User Switching             | no        | auto    |
| w3tc           | W3 Total Cache             | no        | auto    |
| woo            | WooCommerce                | no        | auto    |
| wordfence      | Wordfence Security         | no        | auto    |
| wpcore         | WordPress core             | yes       | auto    |
| wpmu           | WordPress MU               | no        | auto    |
| wsal           | WP Activity Log            | no        | auto    |
| supercache     | WP Super Cache             | no        | auto    |
+----------------+----------------------------+-----------+---------+
```

To enable the listener identified by 'php', type the following command:
```console
pierre@dev:~$ wp log listener enable php
Success: the listener php is now enabled.
```

To deactivate auto-listening without confirmation prompt, type the following command:
```console
pierre@dev:~$ wp log listener auto-off --yes
Success: auto-listening is now deactivated.
```

## Getting DecaLog status

To get detailed status and operation mode, use the `wp log status` command.

## Managing main settings

To toggle on/off main settings, use `wp log settings <enable|disable> <early-loading|auto-logging|auto-start>`.

### Available settings

- `early-loading`: if activated, DecaLog will be loaded before all other plugins (recommended).
- `auto-logging`: if activated, DecaLog will silently start the features needed by live console.
- `auto-start`: if activated, when a new logger is added it automatically starts.

### Example

To disable early-loading without confirmation prompt, type the following command:
```console
pierre@dev:~$ wp log settings disable early-loading --yes
Success: early-loading is now deactivated.
```

## Misc flags

For most commands, DecaLog lets you use the following flags:
- `--yes`: automatically answer "yes" when a question is prompted during the command execution.
- `--stdout`: outputs a clean STDOUT string so you can pipe or store result of command execution (see [piping and storing](#piping-and-storing)).

> It's not mandatory to use `--stdout` when using `--format=count` or `--format=ids`: in such cases `--stdout` is assumed.

## Piping and storing

As DecaLog outputs only the element that makes the most sense when you use the `--stdout` flag, you can pipe commands the way you are used to doing it.

The `wp log logger ... --stdout`, for example, will in most case return the logger uuid. So you can "chain" commands to create, set and start a logger in one line:

```console
pierre@dev:~$ wp log logger add WordpressHandler --stdout | xargs wp log logger set --settings='{"name":"Nice logger!"}' --stdout | xargs wp log logger start
Success: logger f75dc435-2c63-4f16-bb29-cf77a478da4a is now running.
```

On the same "scheme" you can pause all set loggers by iterating the `wp log logger pause` on all uuid returned by `wp log logger list`:
```console
pierre@dev:~$ wp log logger list --format=ids | xargs -0 -d ' ' -I % wp log logger pause %
The logger c40c59dc-5e34-44a1-986d-e1ecb520e3ca is already paused.
The logger f1ee25c7-d9fe-42ee-86df-9394b411e2a7 is already paused.
The logger 93f84673-a623-4c15-825e-d867f35565ff is already paused.
The logger 078e124b-2122-4f03-91e9-2bbf70964618 is already paused.
The logger 9553830a-75e7-4405-80c5-8bf726ccf45c is already paused.
Success: logger 37cf1c00-d67d-4e7d-9518-e579f01407a7 is now paused.
Success: logger 5bacf078-2a1f-4c43-8961-4d8ca647661b is now paused.
The logger df59a30d-dc30-4771-a4a5-a654f0a5cd46 is already paused.
The logger 6d9943e5-b4fa-4dee-8b02-93a9294b1373 is already paused.
The logger 972d417f-2294-4888-8bfe-bf038e39f8e8 is already paused.
The logger 29fdb590-41a8-4a1d-98e5-465a7be10a96 is already paused.
Error: system loggers can't be managed.
The logger 83fdd893-0979-4bbb-848b-d38e8fbf813d is already paused.
The logger 9c6e7967-a1b7-447c-9ed5-ec73853a6867 is already paused.
The logger 8e2ee516-6f8d-40d1-ac16-c3e61274a41a is already paused.
```

You can use, of course, `--stdout` to store command result in variable when you write scripts:

```bash
#!/bin/bash

uuid=$(wp log logger start 37cf1c00-d67d-4e7d-9518-e579f01407a7 --stdout)
echo $uuid
```

And, as DecaLog sets exit code, you can use `$?` to write scripts too:

```bash
#!/bin/bash

wp log logger add FluentHandler --stdout | xargs wp log logger start

if [ $? -eq 0 ]
then
  wp log send notice "All right!"
else
  wp log send error "Unable to start, aborting..."
  exit 1
fi

# continue
```

> To know the meaning of DecaLog exit codes, just use the command `wp log exitcode list`.