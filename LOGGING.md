
## Anatomy of an event
An ___event___ is composed of:
- A __channel__, which is the type of "execution pipe" that triggered the ___event___. It can take the following values: `CLI` (command-line interface), `CRON` (cron job), `AJAX` (Ajax request), `XMLRPC` (XML-RPC request), `API` (Rest API request), `FEED` (Atom/RDF/RSS feed), `WBACK` (site backend), `WFRONT` (site frontend).
- A __level__, which represents the severity of the ___event___. This level is set by the ___listener___, regarding what triggered the ___event___. It can take the following values (from the lowest severity to the highest severity): `DEBUG`, `INFO`, `NOTICE`, `WARNING`, `ERROR`, `CRITICAL`, `ALERT`, `EMERGENCY`. For a detail on how it is used, please read "Events standards" in [Conventions](#conventions).
- A __timestamp__, which is the time when ___event___ was triggered.
- A versioned __source__, which is the component or the subsystem where the ___event___ is triggered. It may be things like `PHP`/`7.2` or `WordPress`/`5.2.2` and so on...
- The __class__ of the source, which can take the following values: `core`, `plugin`, `theme`, `db`, `php`.
- A __message__ in plain text. It is always in English: messages are not localized.
- A numerical __code__, which may be everything which makes sense regarding the ___event___ (an error code, for instance).

Depending on each ___loggers___ settings, an ___event___ may contains many other fields which are automatically detected and filled by DecaLog.


## Conventions
To be fully compatible with DecaLog, your ___listener___ (or other pull requests you would like to submit) must respect some rules and adhere to some standards:

### Events standards

#### Levels
In order to be similar to other log management systems and to maintain consistency between all the DecaLog ___listeners___, the ___levels___ are used as follows:
* `DEBUG` - Only used for events related to application/system debugging. Must not concern standard, important or critical events. _Ex.: "Plugin table xxx updated.", "Textdomain yyy loaded."_.
* `INFO` - Simple informational messages which can be forgotten. _Ex.: "User xxx is logged-out.", "New comment on post yyy."_.
* `NOTICE` - Normal but significant conditions. _Ex.: "The configuration of plugin xxx was modified.", "The database is 70% full."_.
* `WARNING` - A significant condition indicating a situation that may lead to an error if recurring or if no action is taken. _Ex.: "Page not found.", "Comment flood triggered."_.
* `ERROR` - Minor operating error which requires investigation and preventive treatment. _Ex.: "The file could not be opened.", "The feature could not be loaded."_.
* `CRITICAL` - Operating error which requires investigation and corrective treatment. _Ex.: "Uncaught Exception!", "Database error in query xxx."_.
* `ALERT` - Major operating error which requires immediate corrective treatment. _Ex.: "The WordPress database is corrupted."_.
* `EMERGENCY` - A panic condition (unusable system). _Ex.: "The WordPress database is down.", "Parse error: syntax error, unexpected 'if' (T_IF)."_.

#### Codes
If the ___event___ relate to a HTTP condition, the ___code___ must be, as much as possible, the HTTP response code.

The code `0` means: "unknown", "not significant" or "not an error".

#### Messages
The first 30-40 characters of the message must allow the user to understand, at a glance, what the message is.