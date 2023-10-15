<?php

namespace TemporaryClosures;

require_once 'Helpers.php';

class Settings
{
    private $helper;
    public $optionsName = 'temporary_closures_options';

    public function __construct()
    {
        $this->getOptions();
        $this->helper = new Helpers();
        add_action("admin_notices", [$this, "isRootServerMissing"]);
    }

    public function createMenu(string $baseFile): void
    {
        add_options_page(
            'Temporary Closures BMLT', // Page Title
            'Temporary Closures BMLT', // Menu Title
            'activate_plugins',    // Capability
            'temporary-closures-bmlt', // Menu Slug
            [$this, 'adminOptionsPage'] // Callback function to display the page content
        );
        add_filter('plugin_action_links_' . $baseFile, [$this, 'filterPluginActions'], 10, 2);
    }

    public function adminOptionsPage()
    {
        if (!empty($_POST['temporaryclosuressave']) && wp_verify_nonce($_POST['_wpnonce'], 'temporaryclosuresupdate-options')) {
            $this->updateAdminOptions();
            $this->printSuccessMessage();
        }
        $this->printAdminForm();
    }


    private function updateAdminOptions()
    {
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
    }

    private function printSuccessMessage()
    {
        echo '<div class="updated"><p>Success! Your changes were successfully saved!</p></div>';
    }

    private function getConnectionStatus()
    {
        $this_connected = $this->helper->testRootServer($this->options['root_server']);
        return $this_connected ? [
            'msg' => "<span style='color: #00AD00;'><div style='font-size: 16px;vertical-align: text-top;' class='dashicons dashicons-smiley'></div>Version {$this_connected}</span>",
            'status' => true
        ] : [
            'msg' => "<p><div style='color: #f00;font-size: 16px;vertical-align: text-top;' class='dashicons dashicons-no'></div><span style='color: #f00;'>Connection to Root Server Failed.  Check spelling or try again.  If you are certain spelling is correct, Root Server could be down.</span></p>",
            'status' => false
        ];
    }

    private function printAdminForm()
    {
        $connectionStatus = $this->getConnectionStatus();
        $serviceBodies = $this->helper->getServiceBodies($this->options['root_server']);
        ?>
        <div class="wrap">
            <h2>Temporary Closures BMLT</h2>
            <form style="display:inline!important;" method="POST" id="temporary_closures_options" name="temporary_closures_options">
                <?php wp_nonce_field('temporaryclosuresupdate-options'); ?>

                <!-- Connection Status Display -->
                <div style="margin-top: 20px; padding: 0 15px;" class="postbox">
                    <h3>BMLT Root Server URL</h3>
                    <p>Example: https://domain.org/main_server</p>
                    <ul>
                        <li>
                            <label for="root_server">Default Root Server: </label>
                            <input id="root_server" type="text" size="50" name="root_server" value="<?php echo esc_attr($this->options['root_server']); ?>" />
                            <?php echo $connectionStatus['msg']; ?>
                        </li>
                    </ul>
                </div>

                <!-- Service Body Section -->
                <div style="padding: 0 15px;" class="postbox">
                    <h3>Service Body</h3>
                    <p>This service body will be used when no service body is defined in the shortcode.</p>
                    <ul>
                        <li>
                            <label for="service_body_dropdown">Default Service Body: </label>
                            <select style="display:inline;" onchange="getTemporaryClosuresValueSelected()" id="service_body_dropdown" name="service_body_dropdown" class="temporary_closures_service_body_select">
                                <?php if ($connectionStatus['status']) { ?>
                                    <?php $unique_areas = $this->helper->getAreas($this->options['root_server']); ?>
                                    <?php asort($unique_areas); ?>
                                    <?php foreach ($unique_areas as $key => $unique_area) { ?>
                                        <?php $area_data          = explode(',', $unique_area); ?>
                                        <?php $area_name          = $this->helper->arraySafeGet($area_data, 0); ?>
                                        <?php $area_id            = $this->helper->arraySafeGet($area_data, 1); ?>
                                        <?php $area_parent        = $this->helper->arraySafeGet($area_data, 2); ?>
                                        <?php $area_parent_name   = $this->helper->arraySafeGet($area_data, 3); ?>
                                        <?php $option_description = $area_name . " (" . $area_id . ") " . $area_parent_name . " (" . $area_parent . ")" ?>
                                        <?php $is_data = explode(',', esc_html($this->options['service_body_dropdown'])); ?>
                                        <?php if ($area_id == $this->helper->arraySafeGet($is_data, 1)) { ?>
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
    public function filterPluginActions($links)
    {
        // If your plugin is under a different top-level menu than Settings (IE - you changed the function above to something other than add_options_page)
        // Then you're going to want to change options-general.php below to the name of your top-level page
        $settings_link = '<a href="options-general.php?page=temporary-closures-bmlt">Settings</a>';
        array_unshift($links, $settings_link);
        // before other links
        return $links;
    }

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

    public function isRootServerMissing()
    {
        $root_server = $this->options['root_server'];
        if (empty($root_server)) {
            $url = esc_url(admin_url('options-general.php?page=temporary-closures-bmlt'));
            echo '<div id="message" class="error">';
            echo '<p>Missing BMLT Root Server in settings for Contacts BMLT.</p>';
            echo "<p><a href='{$url}'>Temporary Closures BMLT Settings</a></p>";
            echo '</div>';
        }
    }
}
