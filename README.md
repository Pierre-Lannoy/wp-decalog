# WordPress plugin boilerplate

## Usage and initialization
### Renaming
- ***plugin_name***: the name of the plugin, in human-readable format.
- ***plugin_slug***: the slug of the plugin, must be spinal case.
- ***plugin_class***: the class base name, basically the ***plugin_slug*** in upper snake case.
- ***plugin_namespace***: the namespace base for the plugin.
- ***plugin_acronym***: the acronym of the plugin.
#### Files
The file containing the following strings in their names must be renamed:
- `wp-plugin-boilerplate` must be replaced by `plugin-slug`
#### Strings
The following strings in files must be globally renamed:
- `wp-plugin-boilerplate` to ***plugin-slug***
- `WordPress plugin boilerplate` to ***plugin_name***
- `Wp_Plugin_Boilerplate` to ***plugin_class***
- `WPPluginBoilerplate` to ***plugin_namespace***
- `wppb` to lowercase ***plugin_acronym***
- `WPPB` to uppercase ***plugin_acronym***