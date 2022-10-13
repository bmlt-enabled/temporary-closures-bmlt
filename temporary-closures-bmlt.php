<?php
/*
Plugin Name: Temporary Closures BMLT
Plugin URI: https://wordpress.org/plugins/temporary-closures-bmlt/
Contributors: pjaudiomv, bmltenabled
Author: pjaudiomv
Description: Temporary Closures BMLT is a plugin that displays a list of all meetings that have temporary closures. It can be used to view published or unpublished meetings.
Version: 1.3.1
Install: Drop this directory into the "wp-content/plugins/" directory and activate it.
*/
/* Disallow direct access to the plugin file */
if (basename($_SERVER['PHP_SELF']) == basename(__FILE__)) {
    // die('Sorry, but you cannot access this page directly.');
}

if (!class_exists("temporaryClosures")) {
// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable Squiz.Classes.ValidClassName.NotCamelCaps
    class temporaryClosures
// phpcs:enable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:enable Squiz.Classes.ValidClassName.NotCamelCaps
    {
        public $optionsName = 'temporary_closures_options';
        public $options = array();
        public function __construct()
        {
            $this->getOptions();
            if (is_admin()) {
                // Back end
                add_action("admin_notices", array(&$this, "isRootServerMissing"));
                add_action("admin_enqueue_scripts", array(&$this, "enqueueBackendFiles"), 500);
                add_action("admin_menu", array(&$this, "adminMenuLink"));
            } else {
                // Front end
                add_action("wp_enqueue_scripts", array(&$this, "enqueueFrontendFiles"));
                add_shortcode('temporary_closures', array(
                    &$this,
                    "temporaryClosuresMain"
                ));
            }
            // Content filter
            add_filter('the_content', array(
                &$this,
                'filterContent'
            ), 0);
        }

        public function isRootServerMissing()
        {
            $root_server = $this->options['root_server'];
            if ($root_server == '') {
                echo '<div id="message" class="error"><p>Missing BMLT Root Server in settings for Temporary Closures BMLT.</p>';
                $url = admin_url('options-general.php?page=temporary-closures-bmlt.php');
                echo "<p><a href='$url'>Temporary Closures BMLT Settings</a></p>";
                echo '</div>';
            }
            add_action("admin_notices", array(
                &$this,
                "clearAdminMessage"
            ));
        }

        public function clearAdminMessage()
        {
            remove_action("admin_notices", array(
                &$this,
                "isRootServerMissing"
            ));
        }

        public function temporaryClosures()
        {
            $this->__construct();
        }

        public function filterContent($content)
        {
            return $content;
        }

        /**
         * @param $hook
         */
        public function enqueueBackendFiles($hook)
        {
            if ($hook == 'settings_page_temporary-closures-bmlt') {
                wp_enqueue_style('temporary-closures-admin-ui-css', plugins_url('css/redmond/jquery-ui.css', __FILE__), false, '1.11.4', false);
                wp_enqueue_style("chosen", plugin_dir_url(__FILE__) . "css/chosen.min.css", false, "1.2", 'all');
                wp_enqueue_script("chosen", plugin_dir_url(__FILE__) . "js/chosen.jquery.min.js", array('jquery'), "1.2", true);
                wp_enqueue_script('temporary-closures-admin', plugins_url('js/temporary_closures_admin.js', __FILE__), array('jquery'), filemtime(plugin_dir_path(__FILE__) . "js/temporary_closures_admin.js"), false);
                wp_enqueue_script('common');
                wp_enqueue_script('jquery-ui-accordion');
            }
        }

        public function enqueueFrontendFiles($hook)
        {
            wp_enqueue_style('temporary-closures', plugin_dir_url(__FILE__) . 'css/temporary_closures.css', false, '1.15', 'all');
            wp_enqueue_script('datatables', '//cdn.datatables.net/1.10.20/js/jquery.dataTables.min.js', array('jquery'));
            wp_enqueue_style('datatables-style', '//cdn.datatables.net/1.10.20/css/jquery.dataTables.min.css');
        }

        public function testRootServer($root_server)
        {
            $results = $this->get("$root_server/client_interface/json/?switcher=GetServerInfo");
            $httpcode = wp_remote_retrieve_response_code($results);
            $response_message = wp_remote_retrieve_response_message($results);
            if ($httpcode != 200 && $httpcode != 302 && $httpcode != 304 && ! empty($response_message)) {
                //echo '<p>Problem Connecting to BMLT Root Server: ' . $root_server . '</p>';
                return false;
            };
            $results = json_decode(wp_remote_retrieve_body($results), true);
            return is_array($results) && array_key_exists("version", $results[0]) ? $results[0]["version"] : '';
        }

        public function arraySafeGet($arr, $i = 0)
        {
            return is_array($arr) ? $arr[$i] ?? '': '';
        }

        public function temporaryClosuresMain($atts, $content = null)
        {
            $args = shortcode_atts(
                array(
                    "root_server"       => '',
                    'services'          => '',
                    'recursive'         => '',
                    'display_type'      => '',
                    'time_format'       => '',
                    'weekday_language'  => '',
                    'unpublished'       => '',
                    'custom_query'      => '',
                    'sortby'            => ''
                ),
                $atts
            );

            $area_data_dropdown   = explode(',', $this->options['service_body_dropdown']);
            $services_dropdown    = $this->arraySafeGet($area_data_dropdown, 1);

            $root_server          = ($args['root_server']       != '' ? $args['root_server']       : $this->options['root_server']);
            $services             = ($args['services']          != '' ? $args['services']          : $services_dropdown);
            $recursive            = ($args['recursive']         != '' ? $args['recursive']         : $this->options['recursive']);
            $unpublished          = ($args['unpublished']       != '' ? $args['unpublished']       : $this->options['unpublished']);
            $custom_query         = ($args['custom_query']      != '' ? $args['custom_query']      : $this->options['custom_query']);
            $sortby               = ($args['sortby']            != '' ? $args['sortby']            : $this->options['sortby']);
            $display_type         = ($args['display_type']      != '' ? $args['display_type']      : $this->options['display_type_dropdown']);
            $time_format          = ($args['time_format']       != '' ? $args['time_format']       : $this->options['time_format_dropdown']);
            $weekday_language     = ($args['weekday_language']  != '' ? $args['weekday_language']  : $this->options['weekday_language_dropdown']);

            if ($weekday_language == 'dk') {
                $days_of_the_week = [1 => "Søndag", "Mandag", "Tirsdag", "Onsdag", "Torsdag", "Fredag", "Lørdag"];
            } else {
                $days_of_the_week = [1 => "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
            }


            if ($root_server == '') {
                return '<p><strong>Temporary Closures Error: Root Server missing. Please Verify you have entered a Root Server using the \'root_server\' shortcode attribute</strong></p>';
            }
            if ($services == '') {
                return '<p><strong>Temporary Closures Error: Services missing. Please verify you have entered a service body id using the \'services\' shortcode attribute</strong></p>';
            }

            $output = '';

            $meeting_results = $this->getMeetingsJson($services, $recursive, $custom_query, $unpublished, $sortby);

            if ($time_format == '24') {
                $out_time_format = 'G:i';
            } else {
                $out_time_format = 'g:i a';
            }

            if ($display_type != '' && $display_type == 'block') {
                $output .= '<div id="temporary_closures_div">';
                $output .= $this->meetingsJson2Html($meeting_results, true, null, $out_time_format, $days_of_the_week);
                $output .= '</div>';
            } else if ($display_type != '' && $display_type == 'datatables') {
                $output .= '<script type="text/javascript">
                        var $tc = jQuery.noConflict;
                        jQuery(document).ready(function($tc){
                            $tc("#temp_closures_dt").DataTable({
                                language: {
                                    search: "",
                                    searchPlaceholder: "Search Closed Meetings"
                                },
                                "order": [[ 0, "asc" ], [ 1, "asc" ]],
                                "paging": false,
                                "scrollY": "500px",
                                "scrollCollapse": true,
                                "info" : false,
                            });
                        });</script>';
                $output .= '<div id="temporary_closures_div">';
                $output .= $this->meetingsJson2DataTables($meeting_results, $out_time_format, $days_of_the_week);
                $output .= '</div>';
            } else { // table
                $output .= '<div id="temporary_closures_div">';
                $output .= $this->meetingsJson2Html($meeting_results, false, null, $out_time_format, $days_of_the_week);
                $output .= '</div>';
            }
            return $output;
        }

        /**
         * @desc Adds the options sub-panel
         */
        public function getAreas($root_server)
        {
            $results = $this->get("$root_server/client_interface/json/?switcher=GetServiceBodies");
            $result = json_decode(wp_remote_retrieve_body($results), true);
            if (is_wp_error($results)) {
                echo '<div style="font-size: 20px;text-align:center;font-weight:normal;color:#F00;margin:0 auto;margin-top: 30px;"><p>Problem Connecting to BMLT Root Server</p><p>' . $root_server . '</p><p>Error: ' . $result->get_error_message() . '</p><p>Please try again later</p></div>';
                return 0;
            }

            $unique_areas = array();
            foreach ($result as $value) {
                $parent_name = 'None';
                foreach ($result as $parent) {
                    if ($value['parent_id'] == $parent['id']) {
                        $parent_name = $parent['name'];
                    }
                }
                $unique_areas[] = $value['name'] . ',' . $value['id'] . ',' . $value['parent_id'] . ',' . $parent_name;
            }
            return $unique_areas;
        }

        public function adminMenuLink()
        {
            // If you change this from add_options_page, MAKE SURE you change the filterPluginActions function (below) to
            // reflect the page file name (i.e. - options-general.php) of the page your plugin is under!
            add_options_page('Temporary Closures BMLT', 'Temporary Closures BMLT', 'activate_plugins', basename(__FILE__), array(
                &$this,
                'adminOptionsPage'
            ));
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(
                &$this,
                'filterPluginActions'
            ), 10, 2);
        }
        /**
         * Adds settings/options page
         */
        public function adminOptionsPage()
        {
            if (!isset($_POST['temporaryclosuressave'])) {
                $_POST['temporaryclosuressave'] = false;
            }
            if ($_POST['temporaryclosuressave']) {
                if (!wp_verify_nonce($_POST['_wpnonce'], 'temporaryclosuresupdate-options')) {
                    die('Whoops! There was a problem with the data you posted. Please go back and try again.');
                }
                $this->options['root_server']                = esc_url_raw($_POST['root_server']);
                $this->options['service_body_dropdown']      = sanitize_text_field($_POST['service_body_dropdown']);
                $this->options['recursive']                  = sanitize_text_field($_POST['recursive']) ?? '1';
                $this->options['unpublished']                = sanitize_text_field($_POST['unpublished']) ?? '';
                $this->options['sortby']                     = sanitize_text_field($_POST['sortby']);
                $this->options['bmlt_user']                  = sanitize_text_field($_POST['bmlt_user']);
                $this->options['bmlt_pass']                  = sanitize_text_field($_POST['bmlt_pass']);
                $this->options['custom_query']               = sanitize_text_field($_POST['custom_query']);
                $this->options['display_type_dropdown']      = sanitize_text_field($_POST['display_type_dropdown']);
                $this->options['time_format_dropdown']       = sanitize_text_field($_POST['time_format_dropdown']);
                $this->options['weekday_language_dropdown']  = sanitize_text_field($_POST['weekday_language_dropdown']);

                $this->saveAdminOptions();
                echo '<div class="updated"><p>Success! Your changes were successfully saved!</p></div>';
            }
            ?>
            <div class="wrap">
                <h2>Temporary Closures BMLT</h2>
                <form style="display:inline!important;" method="POST" id="temporary_closures_options" name="temporary_closures_options">
                    <?php wp_nonce_field('temporaryclosuresupdate-options'); ?>
                    <?php $this_connected = $this->testRootServer($this->options['root_server']); ?>
                    <?php $connect = "<p><div style='color: #f00;font-size: 16px;vertical-align: text-top;' class='dashicons dashicons-no'></div><span style='color: #f00;'>Connection to Root Server Failed.  Check spelling or try again.  If you are certain spelling is correct, Root Server could be down.</span></p>"; ?>
                    <?php if ($this_connected != false) { ?>
                        <?php $connect = "<span style='color: #00AD00;'><div style='font-size: 16px;vertical-align: text-top;' class='dashicons dashicons-smiley'></div>Version ".$this_connected."</span>"?>
                        <?php $this_connected = true; ?>
                    <?php } ?>
                    <div style="margin-top: 20px; padding: 0 15px;" class="postbox">
                        <h3>BMLT Root Server URL</h3>
                        <p>Example: https://domain.org/main_server</p>
                        <ul>
                            <li>
                                <label for="root_server">Default Root Server: </label>
                                <input id="root_server" type="text" size="50" name="root_server" value="<?php echo $this->options['root_server']; ?>" /> <?php echo $connect; ?>
                            </li>
                        </ul>
                    </div>
                    <div style="padding: 0 15px;" class="postbox">
                        <h3>Service Body</h3>
                        <p>This service body will be used when no service body is defined in the shortcode.</p>
                        <ul>
                            <li>
                                <label for="service_body_dropdown">Default Service Body: </label>
                                <select style="display:inline;" onchange="getTemporaryClosuresValueSelected()" id="service_body_dropdown" name="service_body_dropdown" class="temporary_closures_service_body_select">
                                    <?php if ($this_connected) { ?>
                                        <?php $unique_areas = $this->getAreas($this->options['root_server']); ?>
                                        <?php asort($unique_areas); ?>
                                        <?php foreach ($unique_areas as $key => $unique_area) { ?>
                                            <?php $area_data          = explode(',', $unique_area); ?>
                                            <?php $area_name          = $this->arraySafeGet($area_data); ?>
                                            <?php $area_id            = $this->arraySafeGet($area_data, 1); ?>
                                            <?php $area_parent        = $this->arraySafeGet($area_data, 2); ?>
                                            <?php $area_parent_name   = $this->arraySafeGet($area_data, 3); ?>
                                            <?php $option_description = $area_name . " (" . $area_id . ") " . $area_parent_name . " (" . $area_parent . ")" ?>
                                            <?php $is_data = explode(',', esc_html($this->options['service_body_dropdown'])); ?>
                                            <?php if ($area_id == $this->arraySafeGet($is_data, 1)) { ?>
                                                <option selected="selected" value="<?php echo $unique_area; ?>"><?php echo $option_description; ?></option>
                                            <?php } else { ?>
                                                <option value="<?php echo $unique_area; ?>"><?php echo $option_description; ?></option>
                                            <?php } ?>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <option selected="selected" value="<?php echo $this->options['service_body_dropdown']; ?>"><?php echo 'Not Connected - Can not get Service Bodies'; ?></option>
                                    <?php } ?>
                                </select>
                                <div style="display:inline; margin-left:15px;" id="txtSelectedValues1"></div>
                                <p id="txtSelectedValues2"></p>

                                <input type="checkbox" id="recursive" name="recursive" value="1" <?php echo ($this->options['recursive'] == "1" ? "checked" : "") ?>/>
                                <label for="recursive">Recurse Service Bodies</label>
                            </li>
                        </ul>
                    </div>
                    <div style="padding: 0 15px;" class="postbox">
                        <h3>Custom Query</h3>
                        <p>Ex. &formats=54</p>
                        <ul>
                            <li>
                                <input type="text" id="custom_query" name="custom_query" value="<?php echo $this->options['custom_query']; ?>">
                            </li>
                        </ul>
                    </div>
                    <div style="padding: 0 15px;" class="postbox">
                        <h3>Custom Sort</h3>
                        <p>Default sort keys are: location_municipality,weekday_tinyint,start_time</p>
                        <ul>
                            <li>
                                <input type="text" id="sortby" name="sortby" value="<?php echo $this->options['sortby']; ?>">
                            </li>
                        </ul>
                    </div>
                    <div style="padding: 0 15px;" class="postbox">
                        <h3>Display Unpublished</h3>
                        <p>Allows for displaying of ONLY unpublished meetings. (Must set BMLT Credentials)</p>
                        <ul>
                            <li>
                                <input type="checkbox" id="unpublished" name="unpublished" value="1" <?php echo ($this->options['unpublished'] == "1" ? "checked" : "") ?>/>
                                <label for="unpublished">Use Unpublished Meetings</label>
                            </li>
                            <li>
                                <label for="bmlt_user">BMLT User: </label>
                                <input type="text" id="bmlt_user" name="bmlt_user" value="<?php echo $this->options['bmlt_user']; ?>">
                            </li>
                            <li>
                                <label for="bmlt_pass">BMLT Password: </label>
                                <input type="password" id="bmlt_pass" name="bmlt_pass" value="<?php echo $this->options['bmlt_pass']; ?>">
                            </li>
                        </ul>
                    </div>
                    <div style="margin-top: 20px; padding: 0 15px;" class="postbox">
                        <h3>Attribute Options</h3>
                        <ul>
                            <li>
                                <label for="display_type_dropdown">Display Type: </label>
                                <select style="display:inline;" id="display_type_dropdown" name="display_type_dropdown"  class="display_type_select">
                                    <?php if ($this->options['display_type_dropdown'] == 'block') { ?>
                                        <option value="table">HTML (bmlt table)</option>
                                        <option selected="selected" value="block">HTML (bmlt block)</option>
                                        <option value="datatables">DataTables</option>
                                        <?php
                                    } else if ($this->options['display_type_dropdown'] == 'datatables') { ?>
                                        <option value="table">HTML (bmlt table)</option>
                                        <option value="block">HTML (bmlt block)</option>
                                        <option selected="selected" value="datatables">DataTables</option>
                                        <?php
                                    } else { ?>
                                    <option selected="selected" value="table">HTML (bmlt table)</option>
                                    <option value="block">HTML (bmlt block)</option>
                                    <option value="datatables">DataTables</option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </li>
                            <li>
                                <label for="time_format_dropdown">Time Format: </label>
                                <select style="display:inline;" id="time_format_dropdown" name="time_format_dropdown"  class="time_format_select">
                                    <?php if ($this->options['time_format_dropdown'] == '24') { ?>
                                        <option selected="selected" value="24">24 Hour</option>
                                        <option value="12">12 Hour</option>
                                        <?php
                                    } else { ?>
                                        <option value="24">24 Hour</option>
                                        <option selected="selected" value="12">12 Hour</option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </li>
                            <li>
                                <label for="weekday_language_dropdown">Weekday Language: </label>
                                <select style="display:inline;" id="weekday_language_dropdown" name="weekday_language_dropdown"  class="weekday_language_select">
                                    <?php if ($this->options['weekday_language_dropdown'] == 'dk') { ?>
                                        <option selected="selected" value="dk">Danish</option>
                                        <option value="en">English</option>
                                        <?php
                                    } else { ?>
                                        <option value="dk">Danish</option>
                                        <option selected="selected" value="en">English</option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </li>
                            <?php if ($this->options['display_type_dropdown'] === 'simple') { ?>
                                <li>
                                    <input type="checkbox" id="location_text" name="location_text" value="1" <?php echo ($this->options['location_text'] == "1" ? "checked" : "") ?>/>
                                    <label for="location_text">Show Location Text (for simple display)</label>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                    <input type="submit" value="SAVE CHANGES" name="temporaryclosuressave" class="button-primary" />
                </form>
                <br/><br/>
                <?php include 'partials/_instructions.php'; ?>
            </div>
            <script type="text/javascript">getTemporaryClosuresValueSelected();</script>
            <?php
        }

        /**
         * @desc Adds the Settings link to the plugin activate/deactivate page
         * @param $links
         * @param $file
         * @return mixed
         */
        public function filterPluginActions($links, $file)
        {
            // If your plugin is under a different top-level menu than Settings (IE - you changed the function above to something other than add_options_page)
            // Then you're going to want to change options-general.php below to the name of your top-level page
            $settings_link = '<a href="options-general.php?page=' . basename(__FILE__) . '">' . __('Settings') . '</a>';
            array_unshift($links, $settings_link);
            // before other links
            return $links;
        }
        /**
         * Retrieves the plugin options from the database.
         * @return array
         */
        public function getOptions()
        {
            // Don't forget to set up the default options
            if (!$theOptions = get_option($this->optionsName)) {
                $theOptions = array(
                    "root_server"               => '',
                    "service_body_dropdown"     => '',
                    'recursive'                 => '1',
                    'display_type_dropdown'     => 'table',
                    'time_format'               => '12',
                    'weekday_language_dropdown' => 'en',
                    'unpublished'               => '',
                    'sortby'                    => 'location_municipality,weekday_tinyint,start_time',
                    'bmlt_user'                 => '',
                    'bmlt_pass'                 => '',
                    'custom_query'              => ''
                );
                update_option($this->optionsName, $theOptions);
            }
            $this->options = $theOptions;
            $this->options['root_server'] = untrailingslashit(preg_replace('/^(.*)\/(.*php)$/', '$1', $this->options['root_server']));
        }
        /**
         * Saves the admin options to the database.
         */
        public function saveAdminOptions()
        {
            $this->options['root_server'] = untrailingslashit(preg_replace('/^(.*)\/(.*php)$/', '$1', $this->options['root_server']));
            update_option($this->optionsName, $this->options);
            return;
        }

        /**
         * @param $services
         * @param $recursive
         * @param $custom_query
         * @param $unpublished
         * @param $sortby
         * @return array
         */
        public function getMeetingsJson($services, $recursive, $custom_query, $unpublished, $sortby)
        {
            if (empty($sortby)) {
                $sortby = 'location_municipality,weekday_tinyint,start_time';
            }
            $serviceBodies = explode(',', $services);
            $services_query = '';
            foreach ($serviceBodies as $serviceBody) {
                $services_query .= '&services[]=' . $serviceBody;
            }

            $results = $this->getConfiguredRootServerRequest("/client_interface/json/?switcher=GetSearchResults&sort_keys=$sortby" .$services_query . $custom_query . ($recursive == "1" ? "&recursive=1" : "") . ($unpublished == "1" ? "&advanced_published=-1" : ""));
            $body = wp_remote_retrieve_body($results);

            return json_decode($body, true);
        }

        /*******************************************************************/
        /**
         * \brief  This returns the search results in a datatable.
         * @param $meetings
         * @param null $in_time_format
         * @param null $days_of_the_week
         * @return string
         */
        public function meetingsJson2DataTables(
            $meetings,                ///< The results.
            $in_time_format = null,  // Time format
            $days_of_the_week = null
        ) {
            $bmlt_search_endpoint =  $this->get($this->options['root_server'] . "/client_interface/json/?switcher=GetServiceBodies");
            $serviceBodies = json_decode(wp_remote_retrieve_body($bmlt_search_endpoint));

            $ret = '';
            $ret .= '<table id="temp_closures_dt"  class="tem_closures_display" style="width:95%">';
            $ret .= '<thead id="temp_closures_head">';
            $ret .= '<tr>';
            $ret .= '<td id="temp_closures_day_header" class="selected">Day</td>';
            $ret .= '<td id="temp_closures_time_header">Time</td>';
            $ret .= '<td id="temp_closures_area_header">Area</td>';
            $ret .= '<td id="temp_closures_name_header">Meeting Name</td>';
            $ret .= '<td id="temp_closures_address_header">Address</td>';
            $ret .= '</tr>';
            $ret .= '</thead>';
            $ret .= '<tbody id="temp_closures_body">';

            foreach ($meetings as $meeting) {
                $serviceBodyName = '';
                foreach ($serviceBodies as $serviceBody) {
                    if ($serviceBody->id == $meeting["service_body_bigint"]) {
                        $serviceBodyName = $serviceBody->name;
                    }
                }

                $ret .= '<tr>';
                $ret .= '<td id="temp_closures_day"  data-order="' .$meeting['weekday_tinyint']. '">' . htmlspecialchars($days_of_the_week[intval($meeting['weekday_tinyint'])]) . '</td>';
                $ret .= '<td id="temp_closures_time" data-order="' .str_replace(":", "", $meeting['start_time']) . '">' . $this->buildMeetingTime($meeting['start_time'], $in_time_format) . '</td>';
                $ret .= '<td id="temp_closures_area">' . $serviceBodyName . '</td>';
                $ret .= '<td id="temp_closures_name">' . $meeting["meeting_name"] . '</td>';
                $ret .= '<td id="temp_closures_address">' . $meeting["location_street"] . " " . $meeting["location_municipality"] . ", " . $meeting["location_province"] . " " . $meeting["location_postal_code_1"] . '</td>';
                $ret .= '</tr>';
            }
            $ret .= '</tbody>';
            $ret .= '</table>';

            return $ret;
        }
        /*******************************************************************/
        /**
         * \brief  This returns the search results, in whatever form was requested.
         * \returns XHTML data. It will either be a table, or block elements.
         * @param $results
         * @param bool $in_block
         * @param null $in_container_id
         * @param null $in_time_format
         * @param null $days_of_the_week
         * @return string
         */
        public function meetingsJson2Html(
            $results,                ///< The results.
            $in_block = false,       ///< If this is true, the results will be sent back as block elements (div tags), as opposed to a table. Default is false.
            $in_container_id = null, ///< This is an optional ID for the "wrapper."
            $in_time_format = null,  // Time format
            $days_of_the_week = null
        ) {
            $current_weekday = -1;

            $ret = '';

            // What we do, is to parse the JSON return. We'll pick out certain fields, and format these into a table or block element return.
            if ($results) {
                if (is_array($results) && count($results)) {
                    $ret = $in_block ? '<div class="bmlt_simple_meetings_div"'.($in_container_id ? ' id="'.htmlspecialchars($in_container_id).'"' : '').'>' : '<table class="bmlt_simple_meetings_table"'.($in_container_id ? ' id="'.htmlspecialchars($in_container_id).'"' : '').' cellpadding="0" cellspacing="0" summary="Meetings">';
                    $result_keys = array();
                    foreach ($results as $sub) {
                        $result_keys = array_merge($result_keys, $sub);
                    }
                    $keys = array_keys($result_keys);
                    $weekday_div = false;

                    $alt = 1;   // This is used to provide an alternating class.
                    for ($count = 0; $count < count($results); $count++) {
                        $meeting = $results[$count];

                        if ($meeting) {
                            if ($alt == 1) {
                                $alt = 0;
                            } else {
                                $alt = 1;
                            }

                            if (is_array($meeting) && count($meeting)) {
                                if (count($meeting) > count($keys)) {
                                    $keys[] = 'unused';
                                }

                                $location_borough = htmlspecialchars(trim(stripslashes($meeting['location_city_subsection'])));
                                $location_neighborhood = htmlspecialchars(trim(stripslashes($meeting['location_neighborhood'])));
                                $location_province = htmlspecialchars(trim(stripslashes($meeting['location_province'])));
                                $location_nation = htmlspecialchars(trim(stripslashes($meeting['location_nation'])));
                                $location_postal_code_1 = htmlspecialchars(trim(stripslashes($meeting['location_postal_code_1'])));
                                $location_municipality = htmlspecialchars(trim(stripslashes($meeting['location_municipality'])));
                                $town = '';

                                if ($location_municipality) {
                                    if ($location_borough) {
                                        // We do it this verbose way, so we will scrag the comma if we want to hide the town.
                                        $town = "<span class=\"c_comdef_search_results_borough\">$location_borough</span><span class=\"bmlt_separator bmlt_separator_comma c_comdef_search_results_municipality_separator\">, </span><span class=\"c_comdef_search_results_municipality\">$location_municipality</span>";
                                    } else {
                                        $town = "<span class=\"c_comdef_search_results_municipality\">$location_municipality</span>";
                                    }
                                } elseif ($location_borough) {
                                    $town = "<span class=\"c_comdef_search_results_municipality_borough\">$location_borough</span>";
                                }

                                if ($location_province) {
                                    if ($town) {
                                        $town .= '<span class="bmlt_separator bmlt_separator_comma c_comdef_search_results_province_separator">, </span>';
                                    }

                                    $town .= "<span class=\"c_comdef_search_results_province\">$location_province</span>";
                                }

                                if ($location_postal_code_1) {
                                    if ($town) {
                                        $town .= '<span class="bmlt_separator bmlt_separator_comma c_comdef_search_results_zip_separator">, </span>';
                                    }

                                    $town .= "<span class=\"c_comdef_search_results_zip\">$location_postal_code_1</span>";
                                }

                                if ($location_nation) {
                                    if ($town) {
                                        $town .= '<span class="bmlt_separator bmlt_separator_comma c_comdef_search_results_nation_separator">, </span>';
                                    }

                                    $town .= "<span class=\"c_comdef_search_results_nation\">$location_nation</span>";
                                }

                                if ($location_neighborhood) {
                                    $town_temp = '';

                                    if ($town) {
                                        $town_temp = '<span class="bmlt_separator bmlt_separator_paren bmlt_separator_open_paren bmlt_separator_neighborhood_open_paren"> (</span>';
                                    }

                                    $town_temp .= "<span class=\"c_comdef_search_results_neighborhood\">$location_neighborhood</span>";

                                    if ($town) {
                                        $town_temp .= '<span class="bmlt_separator bmlt_separator_paren bmlt_separator_close_paren bmlt_separator_neighborhood_close_paren">)</span>';
                                    }

                                    $town .= $town_temp;
                                }

                                $weekday = htmlspecialchars($days_of_the_week[intval($meeting['weekday_tinyint'])]);
                                $time = $this->buildMeetingTime($meeting['start_time'], $in_time_format);

                                $address = '';
                                $location_text = htmlspecialchars(trim(stripslashes($meeting['location_text'])));
                                $street = htmlspecialchars(trim(stripslashes($meeting['location_street'])));
                                $info = htmlspecialchars(trim(stripslashes($meeting['location_info'])));

                                if ($location_text) {
                                    $address = "<span class=\"bmlt_simple_list_location_text\">$location_text</span>";
                                }

                                if ($street) {
                                    if ($address) {
                                        $address .= '<span class="bmlt_separator bmlt_separator_comma bmlt_simple_list_location_street_separator">, </span>';
                                    }

                                    $address .= "<span class=\"bmlt_simple_list_location_street\">$street</span>";
                                }

                                if ($info) {
                                    if ($address) {
                                        $address .= '<span class="bmlt_separator bmlt_separator_space bmlt_simple_list_location_info_separator"> </span>';
                                    }

                                    $address .= "<span class=\"bmlt_simple_list_location_info\">($info)</span>";
                                }

                                $name = htmlspecialchars(trim(stripslashes($meeting['meeting_name'])));

                                if ($time && $weekday && $address) {
                                    $meeting_weekday = $meeting['weekday_tinyint'];

                                    if (7 < $meeting_weekday) {
                                        $meeting_weekday = 1;
                                    }

                                    if (($current_weekday != $meeting_weekday) && $in_block) {
                                        if ($current_weekday != -1) {
                                            $weekday_div = false;
                                            $ret .= '</div>';
                                        }

                                        $current_weekday = $meeting_weekday;

                                        $ret .= '<div class="bmlt_simple_meeting_weekday_div_'.$current_weekday.'">';
                                        $weekday_div = true;
                                        if (isset($in_http_vars['weekday_header']) && $in_http_vars['weekday_header']) {
                                            $ret .= '<div id="weekday-start-'.$current_weekday.'" class="weekday-header weekday-index-'.$current_weekday.'">'.htmlspecialchars($weekday).'</div>';
                                        }
                                    }

                                    $ret .= $in_block ? '<div class="bmlt_simple_meeting_one_meeting_div bmlt_alt_'.intval($alt).'">' : '<tr class="bmlt_simple_meeting_one_meeting_tr bmlt_alt_'.intval($alt).'">';
                                    $ret .= $in_block ? '<div class="bmlt_simple_meeting_one_meeting_town_div">' : '<td class="bmlt_simple_meeting_one_meeting_town_td">';
                                    $ret .= $town;
                                    $ret .= $in_block ? '</div>' : '</td>';
                                    $ret .= $in_block ? '<div class="bmlt_simple_meeting_one_meeting_name_div">' : '<td class="bmlt_simple_meeting_one_meeting_name_td">';

                                    if ($name) {
                                        $ret .= $name;
                                    } else {
                                        $ret .= 'NA Meeting';
                                    }

                                    $ret .= $in_block ? '</div>' : '</td>';

                                    $ret .= $in_block ? '<div class="bmlt_simple_meeting_one_meeting_time_div">' : '<td class="bmlt_simple_meeting_one_meeting_time_td">';
                                    $ret .= $time;
                                    $ret .= $in_block ? '</div>' : '</td>';

                                    $ret .= $in_block ? '<div class="bmlt_simple_meeting_one_meeting_weekday_div">' : '<td class="bmlt_simple_meeting_one_meeting_weekday_td">';
                                    $ret .= $weekday;
                                    $ret .= $in_block ? '</div>' : '</td>';

                                    $ret .= $in_block ? '<div class="bmlt_simple_meeting_one_meeting_address_div">' : '<td class="bmlt_simple_meeting_one_meeting_address_td">';
                                    $ret .= $address;
                                    $ret .= $in_block ? '</div>' : '</td>';

                                    $ret .= $in_block ? '<div class="bmlt_clear_div"></div></div>' : '</tr>';
                                }
                            }
                        }
                    }

                    if ($weekday_div && $in_block) {
                        $ret .= '</div>';
                    }

                    $ret .= $in_block ? '</div>' : '</table>';
                }
            }

            return $ret;
        }

        /*******************************************************************/
        /** \brief This creates a time string to be displayed for the meeting.
         * The display is done in non-military time, and "midnight" and
         * "noon" are substituted for 12:59:00, 00:00:00 and 12:00:00
         *
         * \returns a string, containing the HTML rendered by the function.
         * @param $in_time
         * @param $time_format
         * @return string
         */
        public function buildMeetingTime($in_time, $time_format) ///< A string. The value of the time field.
        {

            $time = null;

            if (($in_time == "00:00:00") || ($in_time >= "23:55:00") && $time_format == 'g:i A') {
                $time = htmlspecialchars('Midnight');
            } elseif ($in_time == "12:00:00" && $time_format == 'g:i A') {
                $time = htmlspecialchars('Noon');
            } else {
                $time = htmlspecialchars(date($time_format, strtotime($in_time)));
            }

            return $time;
        }

        public function authenticateRootServer()
        {
            $query_string = http_build_query(array(
                'admin_action' => 'login',
                'c_comdef_admin_login' => $this->options['bmlt_user'],
                'c_comdef_admin_password' => $this->options['bmlt_pass'], '&'));
            return $this->get($this->options['root_server']."/local_server/server_admin/json.php?" . $query_string);
        }
        public function requiresAuthentication()
        {
            if ($this->options['unpublished'] == "1") {
                return true;
            } else {
                return false;
            }
        }
        public function getRooServerRequest($url)
        {
            $cookies = null;
            if ($this->requiresAuthentication()) {
                $auth_response = $this->authenticateRootServer();
                $cookies = wp_remote_retrieve_cookies($auth_response);
            }

            return $this->get($url, $cookies);
        }

        public function getConfiguredRootServerRequest($url)
        {
            return $this->getRooServerRequest($this->options['root_server']."/".$url);
        }

        public function get($url, $cookies = null)
        {
            $args = array(
                'timeout' => '120',
                'headers' => array(
                    'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:105.0) Gecko/20100101 Firefox/105.0'
                ),
                'cookies' => isset($cookies) ? $cookies : null
            );

            return wp_remote_get($url, $args);
        }
    }
    //End Class TemporaryClosures
}
// end if
// instantiate the class
if (class_exists("temporaryClosures")) {
    $TemporaryClosures_instance = new temporaryClosures();
}
?>
