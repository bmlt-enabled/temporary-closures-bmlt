<?php

namespace TemporaryClosures;

class Helpers
{
    const BASE_API_ENDPOINT = "/client_interface/json/?switcher=";
    const HTTP_RETRIEVE_ARGS = array(
        'headers' => array(
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:105.0) Gecko/20100101 Firefox/105.0 +ListLocationsBMLT'
        ),
        'timeout' => 601
    );
    const MIDNIGHT = '00:00:00';
    const NOON = '12:00:00';

    public static function arraySafeGet(array $array, $key, $default = null)
    {
        return $array[$key] ?? $default;
    }

    private function getRemoteResponse(string $root_server, array $queryParams = [], string $switcher = 'GetSearchResults'): array
    {

        $url = $root_server . self::BASE_API_ENDPOINT . $switcher;

        if (!empty($queryParams)) {
            $url .= '&' . http_build_query($queryParams);
        }

        $response = wp_remote_get($url, self::HTTP_RETRIEVE_ARGS);

        if (is_wp_error($response)) {
            return ['status' => 'error', 'message' => 'Error fetching data from server: ' . $response->get_error_message()];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data)) {
            return ['status' => 'error', 'message' => 'Received empty data from server.'];
        }

        return ['status' => 'success', 'data' => $data];
    }

    public function testRootServer($root_server)
    {
        if (!$root_server) {
            return '';
        }

        $response = $this->getRemoteResponse($root_server, [], 'GetServerInfo');
        if ($response['status'] === 'error' || !is_array($response['data'])) {
            return '';
        }

        $data = $response['data'];

        return (isset($data[0]) && is_array($data[0]) && array_key_exists("version", $data[0])) ? $data[0]["version"] : '';
    }

    public function getServiceBodies(string $root_server): array
    {
        $response = $this->getRemoteResponse($root_server, [], 'GetServiceBodies');

        if ($response['status'] === 'error') {
            return [];
        } else {
            return $response['data'];
        }
    }

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
    public function buildMeetingTime($inputTime, $outputFormat)
    {
        if ($inputTime === self::MIDNIGHT && $outputFormat === 'g:i A') {
            return htmlspecialchars('Midnight');
        } elseif ($inputTime === self::NOON && $outputFormat === 'g:i A') {
            return htmlspecialchars('Noon');
        } else {
            return htmlspecialchars(date($outputFormat, strtotime($inputTime)));
        }
    }


    public function authenticateRootServer()
    {
        $query_string = http_build_query(array(
            'admin_action' => 'login',
            'c_comdef_admin_login' => $this->options['bmlt_user'],
            'c_comdef_admin_password' => $this->options['bmlt_pass'], '&'));
        return $this->get($this->options['root_server'] . "/local_server/server_admin/json.php?" . $query_string);
    }

    public function getRootServerRequest(string $url, string $unpublishedStatus)
    {
        $cookies = null;
        if ($unpublishedStatus == "1") {
            $auth_response = $this->authenticateRootServer();
            $cookies = wp_remote_retrieve_cookies($auth_response);
        }

        return $this->get($url, $cookies);
    }

    public function get($url, $cookies = null)
    {
        $args = [
            'timeout' => '120',
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:105.0) Gecko/20100101 Firefox/105.0'
            ],
            'cookies' => $cookies ?? null
        ];

        return wp_remote_get($url, $args);
    }

    public function getMeetingsJson($services, $recursive, $custom_query, $unpublished, $sortby, $root_server)
    {
        if (empty($sortby)) {
            $sortby = 'location_municipality,weekday_tinyint,start_time';
        }
        $serviceBodies = explode(',', $services);
        $services_query = '';
        foreach ($serviceBodies as $serviceBody) {
            $services_query .= '&services[]=' . $serviceBody;
        }
        $url = rtrim($root_server, '/') . "/client_interface/json/?switcher=GetSearchResults&sort_keys=$sortby" . $services_query . $custom_query . ($recursive == "1" ? "&recursive=1" : "") . ($unpublished == "1" ? "&advanced_published=-1" : "");
        $results = $this->getRootServerRequest($url, $unpublished);
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
            $ret .= '<td id="temp_closures_day"  data-order="' . $meeting['weekday_tinyint'] . '">' . htmlspecialchars($days_of_the_week[intval($meeting['weekday_tinyint'] - 1)]) . '</td>';
            $ret .= '<td id="temp_closures_time" data-order="' . str_replace(":", "", $meeting['start_time']) . '">' . $this->buildMeetingTime($meeting['start_time'], $in_time_format) . '</td>';
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
                $ret = $in_block ? '<div class="bmlt_simple_meetings_div"' . ($in_container_id ? ' id="' . htmlspecialchars($in_container_id) . '"' : '') . '>' : '<table class="bmlt_simple_meetings_table"' . ($in_container_id ? ' id="' . htmlspecialchars($in_container_id) . '"' : '') . ' cellpadding="0" cellspacing="0" summary="Meetings">';
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

                            $weekday = htmlspecialchars($days_of_the_week[intval($meeting['weekday_tinyint'] - 1)]);
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

                                    $ret .= '<div class="bmlt_simple_meeting_weekday_div_' . $current_weekday . '">';
                                    $weekday_div = true;
                                    if (isset($in_http_vars['weekday_header']) && $in_http_vars['weekday_header']) {
                                        $ret .= '<div id="weekday-start-' . $current_weekday . '" class="weekday-header weekday-index-' . $current_weekday . '">' . htmlspecialchars($weekday) . '</div>';
                                    }
                                }

                                $ret .= $in_block ? '<div class="bmlt_simple_meeting_one_meeting_div bmlt_alt_' . intval($alt) . '">' : '<tr class="bmlt_simple_meeting_one_meeting_tr bmlt_alt_' . intval($alt) . '">';
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
}
