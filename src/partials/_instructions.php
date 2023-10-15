<h2>Instructions</h2>
<p> Please open a ticket <a href="https://github.com/bmlt-enabled/temporary-closures-bmlt/issues" target="_top">https://github.com/bmlt-enabled/temporary-closures-bmlt/issues</a> with problems, questions or comments.</p>
<div id="temporary_closures_accordion">
    <h3 class="help-accordian"><strong>Basic</strong></h3>
    <div>
        <p>[temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;12&quot;]</p>
        <p>Multiple service bodies can be added seperated by a comma like so services=&quot;12,14,15&quot;</p>
        <strong>Attributes:</strong> root_server, services, recursive, display_type, custom_query, sortby
        <p><strong>Shortcode parameters can be combined.</strong></p>
    </div>
    <h3 class="help-accordian"><strong>Shortcode Attributes</strong></h3>
    <div>
        <p>The following shortcode attributes may be used.</p>
        <p><strong>root_server</strong></p>
        <p><strong>services</strong></p>
        <p><strong>recursive</strong></p>
        <p><strong>display_type</strong></p>
        <p><strong>time_format</strong></p>
        <p><strong>custom_query</strong></p>
        <p><strong>sortby</strong></p>
        <p><strong>weekday_language</strong></p>
        <p>A minimum of root_server, and services attribute are required, which would return all towns for that service body seperated by a comma.</p>
        <p>Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot;]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;&nbsp;- root_server</strong></h3>
    <div>
        <p><strong>root_server (required)</strong></p>
        <p>The url to your BMLT root server.</p>
        <p>Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot;]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;&nbsp;- services</strong></h3>
    <div>
        <p><strong>services (required)</strong></p>
        <p>The Service Body ID of the service body you would like to include, to add multiple service bodies just seperate by a comma..</p>
        <p>Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50,37,26&quot;]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;&nbsp;- recursive</strong></h3>
    <div>
        <p><strong>recursive</strong></p>
        <p>To recurse service bodies add recursive=&quot;1&quot;. This can be useful when using a Service Body Parent ID</p>
        <p>Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot; recursive=&quot;1&quot;]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;&nbsp;- display_type</strong></h3>
    <div>
        <p><strong>display_type</strong></p>
        <p>To change the display type add display_type="table" there are three different types <strong>table</strong>, <strong>block</strong></p>, <strong>datatables</strong>
        <p>Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot; display_type=&quot;table"]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;&nbsp;- custom_query</strong></h3>
    <div>
        <p><strong>custom_query</strong></p>
        <p>You can add a custom query from semantic api to filter results, for ex by format `&formats=54`.</p>
        <p>Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot; custom_query=&quot;&formats=54"]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;&nbsp;- sortby</strong></h3>
    <div>
        <p><strong>sortby</strong></p>
        <p>Allows you to use custom sort keys, the default is `location_municipality,weekday_tinyint,start_time`.</p>
        <p>Ex. [temporary_closures root_server="https://www.domain.org/main_server" sortby="weekday_tinyint,location_municipality,start_time"]</p>
    </div>
    <h3 class="help-accordian"><strong>&nbsp;&nbsp;&nbsp;&nbsp;- weekday_language</strong></h3>
    <div>
        <p><strong>weekday_language</strong></p>
        <p>This allows you to change the language of the weekday names. To change language to danish set weekday_language="dk". Currently supported languages are Danish and English, the default is English.</p>
        <p>Ex. [temporary_closures root_server=&quot;https://www.domain.org/main_server&quot; services=&quot;50&quot; weekday_language=&quot;dk"]</p>
    </div>
</div>
