This plugin has a number of hooks that you can use, as developer or as a user, to customize the user experience or to give access to extended functionalities.

## Addition of custom actions to events in WordPress events viewers
It is possible to add custom actions for each event displayed in the WordPress events viewers. It can be done in the events list view or right in the single event view (the "boxed" one).

### Events list view
For the list view, you can use the `decalog_events_list_actions_for_event`, `decalog_events_list_actions_for_source`, `decalog_events_list_actions_for_time`, `decalog_events_list_actions_for_site`, `decalog_events_list_actions_for_user` and `decalog_events_list_actions_for_ip` filters to add (for each of the corresponding columns) a icon associated with an action (an url).

> Note "site" column is only displayed when in WordPress Multisite.

The format of the filtered value is an array of array(s). Each of the deepest array MUST contain 3 fields:

* `url`: the full url of the action to perfom. This url is opened in a new tab of the user's browser.
* `hint`: the text displayed while hovering the icon.
* `icon`: the "index" of the icon. Since DecaLog embeds the [Feather icon library](https://feathericons.com/), you can choose any index of this library.

#### Example
To add an "eye" icon near the IP (in list view) to perform a quick lookup of each IP with infobyip.com service:
```php
  add_filter(
    'decalog_events_list_actions_for_ip',
    function( $actions, $item ) {
      $actions[] = [
        'url'  => 'https://www.infobyip.com/ip-' . $item['remote_ip'] . '.html',
        'hint' => 'Get information about this IP',
        'icon' => 'eye',
      ];
      return $actions;
    },
    10,
    2
  );
```

### Single event view
For the single event view, you can use the `decalog_event_view_actions_for_event`, `decalog_event_view_actions_for_content`, `decalog_event_view_actions_for_php`, `decalog_event_view_actions_for_device`, `decalog_event_view_actions_for_wp`, `decalog_event_view_actions_for_http`, `decalog_event_view_actions_for_wpbacktrace` and `decalog_event_view_actions_for_phpbacktrace` filters to add (for each of the corresponding box) a text associated with an action (an url).

The format of the filtered value is an array of array(s). Each of the deepest array MUST contain 2 fields:

* `url`: the full url of the action to perfom. This url is opened in a new tab of the user's browser.
* `text`: the text of the link anchor.

#### Example
To add a "ban" action text in the "HTTP request" box:
```php
  add_filter(
    'decalog_event_view_actions_for_http',
    function( $actions, $item ) {
      $actions[] = [
        'url'  => '/wp-admin/admin.php?page=ban-ip&ip=' . $item['remote_ip'],
        'text' => 'Permanently ban ' . $item['remote_ip'],
      ];
      return $actions;
    },
    10,
    2
  );
```

### Available event fields
Each item passed to the filter as second parameter is an array containing details about the current event. The fields are as follow:

* `logger_id` _string_: the unique logger id;
* `id` _integer_: the unique event id (for this specific logger id);
* `timestamp` _string_: the date of the event, respecting the format `Y-m-d H:i:s`;
* `level` _string_: the [level](LOGGING.md#levels) in {`'emergency'`, `'alert'`, `'critical'`, `'error'`, `'warning'`, `'notice'`, `'info'`, `'debug'`, `'unknown'`};
* `channel` _string_: the [channel](LOGGING.md#anatomy-of-an-event) in {`'cli'`, `'cron'`, `'ajax'`, `'xmlrpc'`, `'api'`, `'feed'`, `'wback'`, `'wfront'`, `'unknown'`};
* `class` _string_: the [class](LOGGING.md#anatomy-of-an-event) of the source in {`'core'`, `'plugin'`, `'theme'`, `'db'`, `'php'`};
* `component` _string_: the name of the [source](LOGGING.md#anatomy-of-an-event);
* `version` _string_: the version of the [source](LOGGING.md#anatomy-of-an-event);
* `code` _int_: the [error code](LOGGING.md#codes);
* `message` _string_: the message associated to the event;
* `site_id` _integer_: the unique site id where the event was triggered;
* `site_name` _string_: the name of the site where the event was triggered;
* `user_id` _integer_: the unique user id for whom the event was triggered - may be pseudonymized;
* `user_name` _string_: the name of the user for whom the event was triggered - may be pseudonymized;
* `user_session` _string_: the user's session hash;
* `remote_ip` _string_: the remote IP doing the request - may be obfuscated.
* `country_code` _string_: the country code (iso3166/alpha2) associated with the remote IP if [IP Locator](https://github.com/Pierre-Lannoy/wp-ip-locator) is installed, otherwise an empty string;
* `url` _string_: the requested local url;
* `verb` _string_: the verb of the inbound request in {`'get'`, `'head'`, `'post'`, `'put'`, `'delete'`, `'connect'`, `'options'`, `'trace'`, `'patch'`, `'unknown'`};
* `server` _string_: the target server of the inbound request;
* `referrer` _string_: the request referrer, if any;
* `user_agent` _string_: the full user agent string of the client doing the inbound request;
* `file` _string_: the file where the event was triggered;
* `line` _int_: the line where the event was triggered;
* `classname` _string_: the class name where the event was triggered;
* `function` _string_: the function where the event was triggered;
* `trace` _string_: the serialized full callstack triggering the event.

## Addition of custom actions to traces in WordPress traces viewers
It is possible to add custom actions for each trace displayed in the WordPress traces viewers. It works exactly the same way as for the events.

### Traces list view
For the list view, you can use the `decalog_traces_list_actions_for_trace`, `decalog_traces_list_actions_for_duration`, `decalog_traces_list_actions_for_time`, `decalog_traces_list_actions_for_site` and `decalog_traces_list_actions_for_user` filters to add (for each of the corresponding columns) a icon associated with an action (an url).

### Single trace view
For the single trace view, you can use the `decalog_trace_view_actions_for_trace` and `decalog_trace_view_actions_for_wp` filters to add (for each of the corresponding box) a text associated with an action (an url).

### Available trace fields
Each item passed to the filter as second parameter is an array containing details about the current trace. The fields are as follow:

* `logger_id` _string_: the unique logger id;
* `id` _integer_: the unique trace id (for this specific logger id);
* `trace_id` _string_: the main TraceID;
* `timestamp` _string_: the date of the trace, following the format `Y-m-d H:i:s`;
* `channel` _string_: the [channel](TRACING.md#anatomy-of-a-trace) in {`'cli'`, `'cron'`, `'ajax'`, `'xmlrpc'`, `'api'`, `'feed'`, `'wback'`, `'wfront'`, `'unknown'`};
* `duration` _integer_: the full duration (in ms.) of the trace;
* `scount` _integer_: the number of spans in the trace;
* `site_id` _integer_: the unique site id where the trace was recorded;
* `site_name` _string_: the name of the site where the trace was recorded;
* `user_id` _integer_: the unique user id for whom the trace was recorded - may be pseudonymized;
* `user_name` _string_: the name of the user for whom the trace was recorded - may be pseudonymized;
* `user_session` _string_: the user's session hash;
* `spans` _string_: the serialized full spans array.

## Error level customization
PHP error levels are supernumemary compared to the logger levels. The [mapping](https://github.com/Pierre-Lannoy/wp-decalog/blob/3.10.0/includes/listeners/class-phplistener.php#L38-L54) translating one from the other can be customized with the `decalog_error_level_map` filter.

### Example
Log the `E_DERECATED` and `E_USER_DEPRECATED` errors as `DEBUG` level.
```php
use \Decalog\Logger;

add_filter('decalog_error_level_map', function($levels) {
  $levels[E_DEPRECATED] = Logger::DEBUG;
  $levels[E_USER_DEPRECATED] = Logger::DEBUG;
  return $levels;
});
```

## Customization of PerfOps One menus
You can use the `poo_hide_main_menu` filter to completely hide the main PerfOps One menu or use the `poo_hide_analytics_menu`, `poo_hide_consoles_menu`, `poo_hide_insights_menu`, `poo_hide_tools_menu`, `poo_hide_records_menu` and `poo_hide_settings_menu` filters to selectively hide submenus.

### Example
Hide the main menu:
```php
  add_filter( 'poo_hide_main_menu', '__return_true' );
```

## Customization of the admin bar
You can use the `poo_hide_adminbar` filter to completely hide this plugin's item(s) from the admin bar.

### Example
Remove this plugin's item(s) from the admin bar:
```php
  add_filter( 'poo_hide_adminbar', '__return_true' );
```

## Advanced settings and controls
By default, advanced settings and controls are hidden to avoid cluttering admin screens. Nevertheless, if this plugin have such settings and controls, you can force them to display with `perfopsone_show_advanced` filter.

### Example
Display advanced settings and controls in admin screens:
```php
  add_filter( 'perfopsone_show_advanced', '__return_true' );
```
