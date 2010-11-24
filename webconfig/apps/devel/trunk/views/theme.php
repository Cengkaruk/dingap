<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 ClearFoundation
// Copyright 2010 jQuery Authors
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//
///////////////////////////////////////////////////////////////////////////////

$this->load->helper('form');
$this->load->helper('url');

?>

<h1>Theme Viewer</h1>
<p>This page displays all the standard widgets used in a ClearOS system.</p>

<?php

///////////////////////////////////////////////////////////////////////////////
// Grid
///////////////////////////////////////////////////////////////////////////////

echo "
<h2>Data Grids</h2>
<ul>
	<li>Add search toolbar when size &gt; ?</li>
	<li>Add edit/delete/create buttons</li>
</ul>
";

$grid_title = 'Grid Title';
$grid_columns = array();

echo "
<table id='grid'></table>
<div id='gridpagination'></div>
";

///////////////////////////////////////////////////////////////////////////////
// Forms
///////////////////////////////////////////////////////////////////////////////

echo "
<h2>Forms</h2>
<p>For forms, the labels and fieldsets are the elements that need to be styled
here.  Other widgets (e.g. select boxes, checkboxes, buttons) are defined 
elsewhere on this page.</p>

<h3>Summary Form</h3>
<p>This type of form is common for webconfig pages that have a group of 
similar objects.</p>
";

echo "
<h3>Common Form</h3>
";

$cities = array('Toronto', 'Erin', 'Orem');

echo form_open('date');
echo form_fieldset('Form Title');
echo "
    <div>" .
        form_label('City', 'city') . " " .
		cos_form_dropdown('city', $cities) . "
    </div>
    <div>" .
        form_label('Postal Code', 'postalcode') . " " .
		form_input('postalcode', set_value('postalcode', $postalcode)) . " " . form_error('postalcode') . "
    </div>
    <div>" .
		button_set_open('radioset') .
		form_submit_update('submit') . 
		anchor('/dhcp', 'Cancel') . 
		button_set_close('radioset') . "
    </div>
";
echo form_fieldset_close();
echo form_close();


///////////////////////////////////////////////////////////////////////////////
// Dialog Box
///////////////////////////////////////////////////////////////////////////////

echo "
<h2>Dialog Box</h2>
<p>A dialog box is used to display a single question to the end user.</p>
";

echo anchor_custom('#', 'dialog_test_link', 'Click Here');

echo dialogbox(
    'dialogtest', 
    'ClearOS Dialog Title',
    'Are you sure this dialog box is themed the way you want it?'
);


///////////////////////////////////////////////////////////////////////////////
// Buttons and Anchors
///////////////////////////////////////////////////////////////////////////////

echo "
<h2>Buttons and Anchors</h2>
<p>Buttons and anchors should be styled identically.  What is an 
&quot;anchor&quot;?  An anchor is an href URL that performs a specific
request.  For example, /app/user/add anchor is a request to show the web form
for adding a new user.  This anchor should look just like an Add button as 
opposed to the usual href link shown in a web browser.</p>

<h3>Buttons</h3>
<p>These buttons extend the existing form_submit() function in CodeIgniter.</p>
";

echo form_submit_add('submit');
echo form_submit_update('submit');
echo form_submit_delete('submit');

echo form_submit_previous('submit');
echo form_submit_next('submit');

echo "
<h3>Anchors</h3>
<p>These anchors should look identical to the above buttons.</p>
";

echo anchor_add('submit');
echo anchor_update('submit');
echo anchor_delete('submit');

echo anchor_previous('submit');
echo anchor_next('submit');


echo "
<h3>Button and Anchor Sets</h3>
<p>Buttons are often grouped together as a set.  For example, a firewall rule
has 3 actions: edit, delete, disable.  These buttons can be grouped.</p>
";

echo button_set_open('radioset');
echo form_submit_update('submit');
echo form_submit_disable('submit');
echo form_submit_delete('submit');
echo button_set_close('radioset');


///////////////////////////////////////////////////////////////////////////////
// Radio and Checkboxes
///////////////////////////////////////////////////////////////////////////////

echo "
<h2>Radio Boxes and Checkboxes</h2>
";

echo "<div>";
echo form_label('Checkbox', 'mycheckbox');
echo form_checkbox('mycheckbox', 'accept', TRUE);
echo "</div>";

echo "<div>";
echo form_label('Radio', 'myradio') . "\n";
echo form_radio('myradio', 'accept', TRUE) . "\n";
echo "</div>";


echo "
<h3>Radio Box and Checkbox Sets</h3>
<p>Radio boxes and checkboxes can be grouped and labeled.</p>
";

// FIXME form_radio_set_open/close should be named to include checkboxes?
echo button_set_open();
echo form_radio_set_item('radio1', 'radio', 'Choice #1');
echo form_radio_set_item('radio2', 'radio', 'Choice #2');
echo form_radio_set_item('radio3', 'radio', 'Choice #3');
echo button_set_close('radioset');


///////////////////////////////////////////////////////////////////////////////
// Select Boxes
///////////////////////////////////////////////////////////////////////////////

echo "
<h2>Select Boxes</h2>
";

$cities = array('Toronto', 'Erin', 'Orem');
echo cos_form_dropdown('city', $cities);


///////////////////////////////////////////////////////////////////////////////
// Info Boxes
///////////////////////////////////////////////////////////////////////////////

echo "
<h2>Info Boxes</h2>
<p>The following info boxes are used to display short informationation messages.</p>
";

echo infobox_critical("This is a critical notice.");
echo infobox_warning("This is a warning.");
echo infobox_highlight("This is some bit of information worth highlighting.");


///////////////////////////////////////////////////////////////////////////////
// Help Box
///////////////////////////////////////////////////////////////////////////////

echo "
<h2>Help Box</h2>
<p>Help can be shown inline on webconfig pages.  The help box is optional and 
can be disabled if desired (notably, in mobile themes).</p>
";

echo helpbox("This is a help box");



///////////////////////////////////////////////////////////////////////////////
// Progress Bar
///////////////////////////////////////////////////////////////////////////////

echo "<h2>Progress Bar</h2>\n";

echo progress_bar('progressbartest');

?>

<!-- Tabs -->
<h2>Tabs</h2>
<div id="tabs">
	<ul>
		<li><a href="#tabs-1">First</a></li>
		<li><a href="#tabs-2">Second</a></li>
		<li><a href="#tabs-3">Third</a></li>
	</ul>
	<div id="tabs-1">Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</div>
	<div id="tabs-2">Phasellus mattis tincidunt nibh. Cras orci urna, blandit id, pretium vel, aliquet ornare, felis.</div>
	<div id="tabs-3">Nam dui erat, auctor a, dignissim quis, sollicitudin eu, felis.</div>
</div>

<!-- Date Picker -->
<h2>Date Picker</h2>
<div id="datepicker"></div>

<!-- Slider -->
<h2>Slider</h2>
<div id="slider"></div>

<!-- Icons -->
<h2>Icons</h2>
<ul id="icons" class="ui-widget ui-helper-clearfix">

<li class="ui-state-default ui-corner-all" title=".ui-icon-carat-1-n"><span class="ui-icon ui-icon-carat-1-n"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-carat-1-ne"><span class="ui-icon ui-icon-carat-1-ne"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-carat-1-e"><span class="ui-icon ui-icon-carat-1-e"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-carat-1-se"><span class="ui-icon ui-icon-carat-1-se"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-carat-1-s"><span class="ui-icon ui-icon-carat-1-s"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-carat-1-sw"><span class="ui-icon ui-icon-carat-1-sw"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-carat-1-w"><span class="ui-icon ui-icon-carat-1-w"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-carat-1-nw"><span class="ui-icon ui-icon-carat-1-nw"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-carat-2-n-s"><span class="ui-icon ui-icon-carat-2-n-s"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-carat-2-e-w"><span class="ui-icon ui-icon-carat-2-e-w"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-triangle-1-n"><span class="ui-icon ui-icon-triangle-1-n"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-triangle-1-ne"><span class="ui-icon ui-icon-triangle-1-ne"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-triangle-1-e"><span class="ui-icon ui-icon-triangle-1-e"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-triangle-1-se"><span class="ui-icon ui-icon-triangle-1-se"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-triangle-1-s"><span class="ui-icon ui-icon-triangle-1-s"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-triangle-1-sw"><span class="ui-icon ui-icon-triangle-1-sw"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-triangle-1-w"><span class="ui-icon ui-icon-triangle-1-w"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-triangle-1-nw"><span class="ui-icon ui-icon-triangle-1-nw"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-triangle-2-n-s"><span class="ui-icon ui-icon-triangle-2-n-s"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-triangle-2-e-w"><span class="ui-icon ui-icon-triangle-2-e-w"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-1-n"><span class="ui-icon ui-icon-arrow-1-n"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-1-ne"><span class="ui-icon ui-icon-arrow-1-ne"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-1-e"><span class="ui-icon ui-icon-arrow-1-e"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-1-se"><span class="ui-icon ui-icon-arrow-1-se"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-1-s"><span class="ui-icon ui-icon-arrow-1-s"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-1-sw"><span class="ui-icon ui-icon-arrow-1-sw"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-1-w"><span class="ui-icon ui-icon-arrow-1-w"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-1-nw"><span class="ui-icon ui-icon-arrow-1-nw"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-2-n-s"><span class="ui-icon ui-icon-arrow-2-n-s"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-2-ne-sw"><span class="ui-icon ui-icon-arrow-2-ne-sw"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-2-e-w"><span class="ui-icon ui-icon-arrow-2-e-w"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-2-se-nw"><span class="ui-icon ui-icon-arrow-2-se-nw"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowstop-1-n"><span class="ui-icon ui-icon-arrowstop-1-n"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowstop-1-e"><span class="ui-icon ui-icon-arrowstop-1-e"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowstop-1-s"><span class="ui-icon ui-icon-arrowstop-1-s"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowstop-1-w"><span class="ui-icon ui-icon-arrowstop-1-w"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-1-n"><span class="ui-icon ui-icon-arrowthick-1-n"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-1-ne"><span class="ui-icon ui-icon-arrowthick-1-ne"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-1-e"><span class="ui-icon ui-icon-arrowthick-1-e"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-1-se"><span class="ui-icon ui-icon-arrowthick-1-se"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-1-s"><span class="ui-icon ui-icon-arrowthick-1-s"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-1-sw"><span class="ui-icon ui-icon-arrowthick-1-sw"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-1-w"><span class="ui-icon ui-icon-arrowthick-1-w"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-1-nw"><span class="ui-icon ui-icon-arrowthick-1-nw"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-2-n-s"><span class="ui-icon ui-icon-arrowthick-2-n-s"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-2-ne-sw"><span class="ui-icon ui-icon-arrowthick-2-ne-sw"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-2-e-w"><span class="ui-icon ui-icon-arrowthick-2-e-w"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthick-2-se-nw"><span class="ui-icon ui-icon-arrowthick-2-se-nw"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthickstop-1-n"><span class="ui-icon ui-icon-arrowthickstop-1-n"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthickstop-1-e"><span class="ui-icon ui-icon-arrowthickstop-1-e"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthickstop-1-s"><span class="ui-icon ui-icon-arrowthickstop-1-s"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowthickstop-1-w"><span class="ui-icon ui-icon-arrowthickstop-1-w"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowreturnthick-1-w"><span class="ui-icon ui-icon-arrowreturnthick-1-w"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowreturnthick-1-n"><span class="ui-icon ui-icon-arrowreturnthick-1-n"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowreturnthick-1-e"><span class="ui-icon ui-icon-arrowreturnthick-1-e"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowreturnthick-1-s"><span class="ui-icon ui-icon-arrowreturnthick-1-s"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowreturn-1-w"><span class="ui-icon ui-icon-arrowreturn-1-w"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowreturn-1-n"><span class="ui-icon ui-icon-arrowreturn-1-n"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowreturn-1-e"><span class="ui-icon ui-icon-arrowreturn-1-e"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowreturn-1-s"><span class="ui-icon ui-icon-arrowreturn-1-s"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowrefresh-1-w"><span class="ui-icon ui-icon-arrowrefresh-1-w"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowrefresh-1-n"><span class="ui-icon ui-icon-arrowrefresh-1-n"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowrefresh-1-e"><span class="ui-icon ui-icon-arrowrefresh-1-e"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrowrefresh-1-s"><span class="ui-icon ui-icon-arrowrefresh-1-s"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-4"><span class="ui-icon ui-icon-arrow-4"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-arrow-4-diag"><span class="ui-icon ui-icon-arrow-4-diag"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-extlink"><span class="ui-icon ui-icon-extlink"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-newwin"><span class="ui-icon ui-icon-newwin"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-refresh"><span class="ui-icon ui-icon-refresh"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-shuffle"><span class="ui-icon ui-icon-shuffle"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-transfer-e-w"><span class="ui-icon ui-icon-transfer-e-w"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-transferthick-e-w"><span class="ui-icon ui-icon-transferthick-e-w"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-folder-collapsed"><span class="ui-icon ui-icon-folder-collapsed"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-folder-open"><span class="ui-icon ui-icon-folder-open"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-document"><span class="ui-icon ui-icon-document"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-document-b"><span class="ui-icon ui-icon-document-b"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-note"><span class="ui-icon ui-icon-note"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-mail-closed"><span class="ui-icon ui-icon-mail-closed"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-mail-open"><span class="ui-icon ui-icon-mail-open"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-suitcase"><span class="ui-icon ui-icon-suitcase"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-comment"><span class="ui-icon ui-icon-comment"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-person"><span class="ui-icon ui-icon-person"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-print"><span class="ui-icon ui-icon-print"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-trash"><span class="ui-icon ui-icon-trash"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-locked"><span class="ui-icon ui-icon-locked"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-unlocked"><span class="ui-icon ui-icon-unlocked"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-bookmark"><span class="ui-icon ui-icon-bookmark"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-tag"><span class="ui-icon ui-icon-tag"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-home"><span class="ui-icon ui-icon-home"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-flag"><span class="ui-icon ui-icon-flag"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-calculator"><span class="ui-icon ui-icon-calculator"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-cart"><span class="ui-icon ui-icon-cart"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-pencil"><span class="ui-icon ui-icon-pencil"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-clock"><span class="ui-icon ui-icon-clock"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-disk"><span class="ui-icon ui-icon-disk"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-calendar"><span class="ui-icon ui-icon-calendar"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-zoomin"><span class="ui-icon ui-icon-zoomin"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-zoomout"><span class="ui-icon ui-icon-zoomout"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-search"><span class="ui-icon ui-icon-search"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-wrench"><span class="ui-icon ui-icon-wrench"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-gear"><span class="ui-icon ui-icon-gear"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-heart"><span class="ui-icon ui-icon-heart"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-star"><span class="ui-icon ui-icon-star"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-link"><span class="ui-icon ui-icon-link"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-cancel"><span class="ui-icon ui-icon-cancel"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-plus"><span class="ui-icon ui-icon-plus"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-plusthick"><span class="ui-icon ui-icon-plusthick"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-minus"><span class="ui-icon ui-icon-minus"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-minusthick"><span class="ui-icon ui-icon-minusthick"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-close"><span class="ui-icon ui-icon-close"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-closethick"><span class="ui-icon ui-icon-closethick"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-key"><span class="ui-icon ui-icon-key"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-lightbulb"><span class="ui-icon ui-icon-lightbulb"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-scissors"><span class="ui-icon ui-icon-scissors"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-clipboard"><span class="ui-icon ui-icon-clipboard"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-copy"><span class="ui-icon ui-icon-copy"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-contact"><span class="ui-icon ui-icon-contact"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-image"><span class="ui-icon ui-icon-image"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-video"><span class="ui-icon ui-icon-video"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-script"><span class="ui-icon ui-icon-script"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-alert"><span class="ui-icon ui-icon-alert"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-info"><span class="ui-icon ui-icon-info"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-notice"><span class="ui-icon ui-icon-notice"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-help"><span class="ui-icon ui-icon-help"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-check"><span class="ui-icon ui-icon-check"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-bullet"><span class="ui-icon ui-icon-bullet"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-radio-off"><span class="ui-icon ui-icon-radio-off"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-radio-on"><span class="ui-icon ui-icon-radio-on"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-pin-w"><span class="ui-icon ui-icon-pin-w"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-pin-s"><span class="ui-icon ui-icon-pin-s"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-play"><span class="ui-icon ui-icon-play"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-pause"><span class="ui-icon ui-icon-pause"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-seek-next"><span class="ui-icon ui-icon-seek-next"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-seek-prev"><span class="ui-icon ui-icon-seek-prev"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-seek-end"><span class="ui-icon ui-icon-seek-end"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-seek-first"><span class="ui-icon ui-icon-seek-first"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-stop"><span class="ui-icon ui-icon-stop"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-eject"><span class="ui-icon ui-icon-eject"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-volume-off"><span class="ui-icon ui-icon-volume-off"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-volume-on"><span class="ui-icon ui-icon-volume-on"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-power"><span class="ui-icon ui-icon-power"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-signal-diag"><span class="ui-icon ui-icon-signal-diag"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-signal"><span class="ui-icon ui-icon-signal"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-battery-0"><span class="ui-icon ui-icon-battery-0"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-battery-1"><span class="ui-icon ui-icon-battery-1"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-battery-2"><span class="ui-icon ui-icon-battery-2"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-battery-3"><span class="ui-icon ui-icon-battery-3"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-plus"><span class="ui-icon ui-icon-circle-plus"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-minus"><span class="ui-icon ui-icon-circle-minus"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-close"><span class="ui-icon ui-icon-circle-close"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-triangle-e"><span class="ui-icon ui-icon-circle-triangle-e"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-triangle-s"><span class="ui-icon ui-icon-circle-triangle-s"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-triangle-w"><span class="ui-icon ui-icon-circle-triangle-w"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-triangle-n"><span class="ui-icon ui-icon-circle-triangle-n"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-arrow-e"><span class="ui-icon ui-icon-circle-arrow-e"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-arrow-s"><span class="ui-icon ui-icon-circle-arrow-s"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-arrow-w"><span class="ui-icon ui-icon-circle-arrow-w"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-arrow-n"><span class="ui-icon ui-icon-circle-arrow-n"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-zoomin"><span class="ui-icon ui-icon-circle-zoomin"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-zoomout"><span class="ui-icon ui-icon-circle-zoomout"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-circle-check"><span class="ui-icon ui-icon-circle-check"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-circlesmall-plus"><span class="ui-icon ui-icon-circlesmall-plus"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-circlesmall-minus"><span class="ui-icon ui-icon-circlesmall-minus"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-circlesmall-close"><span class="ui-icon ui-icon-circlesmall-close"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-squaresmall-plus"><span class="ui-icon ui-icon-squaresmall-plus"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-squaresmall-minus"><span class="ui-icon ui-icon-squaresmall-minus"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-squaresmall-close"><span class="ui-icon ui-icon-squaresmall-close"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-grip-dotted-vertical"><span class="ui-icon ui-icon-grip-dotted-vertical"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-grip-dotted-horizontal"><span class="ui-icon ui-icon-grip-dotted-horizontal"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-grip-solid-vertical"><span class="ui-icon ui-icon-grip-solid-vertical"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-grip-solid-horizontal"><span class="ui-icon ui-icon-grip-solid-horizontal"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-gripsmall-diagonal-se"><span class="ui-icon ui-icon-gripsmall-diagonal-se"></span></li>
<li class="ui-state-default ui-corner-all" title=".ui-icon-grip-diagonal-se"><span class="ui-icon ui-icon-grip-diagonal-se"></span></li>
</ul>
