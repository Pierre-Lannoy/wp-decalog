[DecaLog](https://perfops.one/decalog) have the ability to log WordPress traces.

These __traces__ can be sent to specialized services or displayed in the WordPress admin.

## Anatomy of a trace
A __trace__ belongs to a __channel__, which is the type of "execution pipe" where the __trace__ was recorded. It can take the following values: `CLI` (command-line interface), `CRON` (cron job), `AJAX` (Ajax request), `XMLRPC` (XML-RPC request), `API` (Rest API request), `FEED` (Atom/RDF/RSS feed), `WBACK` (site backend), `WFRONT` (site frontend).

Each __trace__ contains a list of __spans__ which are mainly composed of:
- A __process__, which can be: `core`, `plugin`, `theme`, `db`, `php`.
- A __name__, which is given in the form "Component / Operation".
- A __start time__, which is the starting timestamp of the span.
- A __duration__, which is the duration of the span.

All fields, times and tags/labels are handled and automatically set by DecaLog.

![Typical trace sent by DecaLog and visualized in Grafana](https://perfops.one/assets/images/traces-example.jpg "Typical trace sent by DecaLog and visualized in Grafana")
_Typical trace sent by DecaLog and visualized in Grafana_
