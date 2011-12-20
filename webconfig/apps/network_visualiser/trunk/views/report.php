<?php

/**
 * Nework visualiser controller.
 *
 * @category   Apps
 * @package    Nework_Visualiser
 * @subpackage Views
 * @author     ClearCenter <developer@clearcenter.com>
 * @copyright  2011 ClearCenter
 * @license    http://www.clearcenter.com/Company/terms.html ClearSDN license
 * @link       http://www.clearcenter.com/support/documentation/clearos/network_visualiser/
 */

///////////////////////////////////////////////////////////////////////////////
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\network_visualiser\Network_Visualiser as Network_Visualiser;

$this->lang->load('base');
$this->lang->load('network');
$this->lang->load('network_visualiser');

///////////////////////////////////////////////////////////////////////////////
// Anchors
///////////////////////////////////////////////////////////////////////////////

if ($report_type == Network_Visualiser::REPORT_DETAILED)
    $anchors = array(
        anchor_custom('/app/network_visualiser/simple', lang('base_back'))
    );
else
    $anchors = array();

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

if ($report_type == Network_Visualiser::REPORT_DETAILED) {
    $headers = array(
        lang('network_visualiser_source'),
        lang('network_visualiser_source_port'),
        lang('network_visualiser_protocol'),
        lang('network_visualiser_destination'),
        lang('network_visualiser_destination_port'),
        ($display == 'totalbps' ? lang('network_bandwidth') : lang('network_visualiser_total_transfer'))
    );
} else {
    $headers = array(
        lang('network_visualiser_source'),
        lang('network_visualiser_source_port'),
        lang('network_visualiser_destination'),
        ($display == 'totalbps' ? lang('network_bandwidth') : lang('network_visualiser_total_transfer'))
    );
}

///////////////////////////////////////////////////////////////////////////////
// List table
///////////////////////////////////////////////////////////////////////////////

echo form_open('network_visualiser_report');

if ($report_type == Network_Visualiser::REPORT_SIMPLE) {
    echo summary_table(
        lang('network_visualiser_traffic_summary'),
        $anchors,
        $headers,
        NULL,
        array(
            'id' => 'report',
            'no_action' => TRUE,
            'sorting-type' => array(
                NULL,
                NULL,
                NULL,
                'title-numeric'
            )
        )
    );
} else if ($report_type == Network_Visualiser::REPORT_DETAILED) {
    echo summary_table(
        lang('network_visualiser_traffic_summary'),
        $anchors,
        $headers,
        NULL,
        array(
            'id' => 'report',
            'no_action' => TRUE,
            'sorting-type' => array(
                NULL,
                NULL,
                NULL,
                NULL,
                NULL,
                'title-numeric'
            )
        )
    );
} else if ($report_type == Network_Visualiser::REPORT_GRAPHICAL) {
    echo "<div id='clear-chart' style='height:450px; width:100%;'>";
    echo "    <div style='margin: 30 225 0 225;' class='theme-loading-normal'>";
    echo lang('base_loading');
    echo "    </div>";
    echo "</div>";
}

echo form_close();
echo "<input id='report_display' type='hidden' value='$display'>";
echo "<input id='report_type' type='hidden' value='$report_type'>";
