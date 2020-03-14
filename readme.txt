=== Temporary Closures BMLT ===

Contributors: pjaudiomv, bmltenabled
Plugin URI: https://wordpress.org/plugins/temporary-closures-bmlt/
Tags: bmlt, basic meeting list toolbox, Temporary Closures, Temporary Closures BMLT, narcotics anonymous, na
Requires at least: 4.0
Requires PHP: 5.6
Tested up to: 5.3.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Temporary Closures BMLT is a plugin that displays a list of all meetings that have temporary closures. It can be used
to view published or unpublished meetings.

SHORTCODE
Basic: [temporary_closures]
Attributes: root_server, services, recursive, display_type, unpublished, custom_query

-- Shortcode parameters can be combined

== Usage ==

A minimum of root_server, and services attributes are required.

Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot;]

**recursive** to recurse service bodies add recursive=&quot;1&quot;
Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot; recursive=&quot;1&quot;]

**services** to add multiple service bodies just separate by a comma.
Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50,37,26&quot;]

**grace_period** To add a grace period to meeting lookup add grace_period=&quot;15&quot; this would add a 15 minute grace period.
Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot; grace_period=&quot;15&quot;]

**num_results** To limit the number of results add num_results=&quot;5&quot; this would limit results to 5.
Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot; state=&quot;1&quot; num_results=&quot;5&quot;]

**display_type** To change the display type add display_type=&quot;table&quot; there are three different types **simple**, **table**, **block**
Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; display_type=&quot;table&quot;]

== EXAMPLES ==

<a href="https://sca.charlestonna.org/upcoming-meetings/">https://sca.charlestonna.org/upcoming-meetings/</a>

== MORE INFORMATION ==

<a href="https://github.com/pjaudiomv/upcoming-meetings-bmlt" target="_blank">https://github.com/pjaudiomv/upcoming-meetings-bmlt</a>


== Installation ==

This section describes how to install the plugin and get it working.

1. Download and install the plugin from WordPress dashboard. You can also upload the entire Temporary Closures BMLT Plugin folder to the /wp-content/plugins/ directory
2. Activate the plugin through the Plugins menu in WordPress
3. Add [temporary_closures] shortcode to your WordPress page/post.
4. At a minimum assign root_server, services and timezone attributes.

== Screenshots ==

1. screenshot-1.png
2. screenshot-2.png
3. screenshot-3.png

== Changelog ==

= 1.0.0 =

* Initial WordPress submission.
