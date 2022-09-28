# Temporary Closures BMLT

Temporary Closures BMLT is a plugin that displays a list of all meetings that have temporary closures. It can be used
to view published or unpublished meetings.

## Use Cases

New England region created a format `TC - Temporary Closure` and unpublished then assigned this format to all of them.
Then we could display just the unpublished meetings with that format using the custom query Ex. `&formats=54`. New 
England did it this way because we have some meetings that are unpublished for other reasons beside temporary closures.

You could also just have it display all unpublished meetings and not use a seperate format.

SHORTCODE

Basic: [temporary_closures]

Attributes: root_server, services, recursive, display_type, custom_query, sortby

-- Shortcode parameters can be combined


## Usage

A minimum of root_server, and services attributes are required.

Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot;]

**recursive** to recurse service bodies add recursive=&quot;1&quot;
Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot; recursive=&quot;1&quot;]

**services** to add multiple service bodies just separate by a comma.
Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50,37,26&quot;]

**display_type** To change the display type add display_type=&quot;table&quot; there are three different types **table**, **block**,  **datatables**
Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; display_type=&quot;table&quot;]

**custom_query** You can add a custom query from semantic api to filter results, for ex by format `&formats=54`.
Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; custom_query=&quot;&formats=54&quot;]

**sortby** Allows you to use custom sort keys, the default is `location_municipality,weekday_tinyint,start_time`.
Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; sortby=&quot;weekday_tinyint,location_municipality,start_time&quot;]


## EXAMPLES

<a href="https://sca.charlestonna.org/temporary-closures-bmlt/">https://sca.charlestonna.org/temporary-closures-bmlt/</a>


## Installation

This section describes how to install the plugin and get it working.

1. Download and install the plugin from WordPress dashboard. You can also upload the entire Temporary Closures BMLT Plugin folder to the /wp-content/plugins/ directory
2. Activate the plugin through the Plugins menu in WordPress
3. Add [temporary_closures] shortcode to your WordPress page/post.
4. At a minimum assign root_server, and services attributes.


## Changelog

### 1.2.1

* Using jQuery no conflict.
* Fixed PHP warning.

### 1.2.0

* Added datatables to display_type, this will display data in sortable table.

### 1.1.1

* CSS Tweaks.
* Removed unneeded map link.

### 1.1.0

* Added custom sort option `sortby`.

### 1.0.1

* Bug fixes.

### 1.0.0

* Initial WordPress submission.
