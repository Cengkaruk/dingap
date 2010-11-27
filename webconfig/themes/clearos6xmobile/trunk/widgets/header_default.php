<?php

///////////////////////////////////////////////////////////////////////////////
// 
// Copyright 2009, 2010 ClearFoundation
//
//////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Lesser General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////
	
$bootstrap = isset($_ENV['CLEAROS_BOOTSTRAP']) ? $_ENV['CLEAROS_BOOTSTRAP'] : '/usr/clearos/framework/shared';
require_once($bootstrap . '/bootstrap.php');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('base');

require_once('menu.php');

///////////////////////////////////////////////////////////////////////////////
// H E A D E R
///////////////////////////////////////////////////////////////////////////////

echo "
<!-- Body -->
<body>

<div data-role='page' data-theme='b'> 
	<div data-role='header'>
		<h1>$title</h1>
		<a href='/app/dashboard/menu' data-icon='gear' class='ui-btn-right'>" . lang('base_menu') . "</a>
	</div>
	<div data-role='content'>
";

?>
