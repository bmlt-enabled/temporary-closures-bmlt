=== Temporary Closures BMLT ===

Contributors: pjaudiomv, bmltenabled
Plugin URI: https://wordpress.org/plugins/temporary-closures-bmlt/
Tags: bmlt, basic meeting list toolbox, Temporary Closures, Temporary Closures BMLT, narcotics anonymous, na
Requires at least: 4.0
Requires PHP: 7.2
Tested up to: 6.0.2
Stable tag: 1.3.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Temporary Closures BMLT is a plugin that displays a list of all meetings that have temporary closures. It can be used
to view published or unpublished meetings.

SHORTCODE
Basic: [temporary_closures]
Attributes: root_server, services, recursive, display_type, custom_query, sortby

-- Shortcode parameters can be combined

== Usage ==

A minimum of root_server, and services attributes are required.

Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot;]

**recursive** to recurse service bodies add recursive=&quot;1&quot;
Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot; recursive=&quot;1&quot;]

**services** to add multiple service bodies just separate by a comma.
Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50,37,26&quot;]

**display_type** To change the display type add display_type=&quot;table&quot; there are three different types **table**, **block**, **datatables**
Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; display_type=&quot;table&quot;]

**custom_query** You can add a custom query from semantic api to filter results, for ex by format `&formats=54`.
Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; custom_query=&quot;&formats=54&quot;]

**sortby** Allows you to use custom sort keys, the default is `location_municipality,weekday_tinyint,start_time`.
Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; sortby=&quot;weekday_tinyint,location_municipality,start_time&quot;]

== EXAMPLES ==

<a href="https://sca.charlestonna.org/temporary-closures-bmlt/">https://sca.charlestonna.org/temporary-closures-bmlt/</a>

== MORE INFORMATION ==

<a href="https://github.com/bmlt-enabled/temporary-closures-bmlt" target="_blank">https://github.com/bmlt-enabled/temporary-closures-bmlt</a>


== Installation ==

This section describes how to install the plugin and get it working.

1. Download and install the plugin from WordPress dashboard. You can also upload the entire Temporary Closures BMLT Plugin folder to the /wp-content/plugins/ directory
2. Activate the plugin through the Plugins menu in WordPress
3. Add [temporary_closures] shortcode to your WordPress page/post.
4. At a minimum assign root_server, and services attributes.

== Screenshots ==

1. screenshot-1.png

== Changelog ==

= 1.3.1 =

* Fix for User-Agent issue that appears to be present on SiteGround hosted root servers.
* Various PHP 8.1 fixes.

= 1.3.0 =

* Updated version logic for BMLT 3.0.0 compatibility.

= 1.2.1 =

* Using jQuery no conflict.
* Fixed PHP warning.

= 1.2.0 =

* Added datatables to display_type, this will display data in sortable table.

= 1.1.1 =

* CSS Tweaks.
* Removed unneeded map link.

= 1.1.0 =

* Added custom sort option sortby.

= 1.0.1 =

* Bug fixes.

= 1.0.0 =

* Initial WordPress submission.
