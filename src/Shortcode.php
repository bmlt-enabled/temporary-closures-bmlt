<?php

namespace TemporaryClosures;

require_once 'Settings.php';
require_once 'Helpers.php';

class Shortcode
{
    private $settings;
    private $helper;

    public function __construct()
    {
        $this->settings = new Settings();
        $this->helper = new Helpers();
    }

    public function render($atts = [], $content = null): string
    {
        $defaults = $this->getDefaultValues();
        $args = shortcode_atts($defaults, $atts);

        $rootServerErrorMessage = '<p><strong>Contacts BMLT Error: Root Server missing. Please Verify you have entered a Root Server.</strong></p>';
        $servicesErrorMessage = '<p><strong>Temporary Closures Error: Services missing. Please verify you have entered a service body id using the \'services\' shortcode attribute</strong></p>';

        if (empty($args['root_server'])) {
            return $rootServerErrorMessage;
        }

        if ($args['services'] == '') {
            return $servicesErrorMessage;
        }

        $days_of_the_week = ($args['weekday_language'] == 'dk') ? ["Søndag", "Mandag", "Tirsdag", "Onsdag", "Torsdag", "Fredag", "Lørdag"] : ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

        $content = '<div id="temporary_closures_div">';

        $meeting_results = $this->helper->getMeetingsJson($args['services'], $args['recursive'], $args['custom_query'], $args['unpublished'], $args['sortby']);
        $out_time_format = ($args['time_format'] == '24') ? 'G:i' : 'g:i a';

        switch ($args['display_type']) {
            case 'block':
                $content .= $this->helper->meetingsJson2Html($meeting_results, true, null, $out_time_format, $days_of_the_week);
                break;

            case 'datatables':
                $content .= '<script type="text/javascript">
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
                $content .= $this->helper->meetingsJson2DataTables($meeting_results, $out_time_format, $days_of_the_week);
                break;

            default:
                $content .= $this->helper->meetingsJson2Html($meeting_results, false, null, $out_time_format, $days_of_the_week);
                break;
        }

        $content .= '</div>';
        return $content;
    }

    private function getDefaultValues(): array
    {
        $services_data_dropdown   = explode(',', $this->settings->options['service_body_dropdown']);
        $services_dropdown    = $this->helper->arraySafeGet($services_data_dropdown, 1);
        return [
                'root_server'       => $this->settings->options['root_server'],
                'services'          => $services_dropdown,
                'recursive'         => $this->settings->options['recursive'],
                'display_type'      => $this->settings->options['display_type'],
                'time_format'       => $this->settings->options['time_format'],
                'weekday_language'  => $this->settings->options['weekday_language'],
                'unpublished'       => $this->settings->options['unpublished'],
                'custom_query'      => $this->settings->options['custom_query'],
                'sortby'            => $this->settings->options['sortby']
        ];
    }
}
