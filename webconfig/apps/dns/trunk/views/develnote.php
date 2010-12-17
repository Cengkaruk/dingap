<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2010 ClearFoundation
//
///////////////////////////////////////////////////////////////////////////////
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.  
//  
//////////////////////////////////////////////////////////////////////////////

if ($this->page->data['theme'] == 'clearos6x') {

	echo infobox_highlight("
		<h2>CRUD - Create, Read, Update and Delete</h2> 
		<p>The Local DNS Server is an example of a simple
		CRUD application.  The User Manager, Firewall Tools, and many
		other apps use this type of widget.  Improvements since 5.x:</p>
		<ul>
			<li>Supports hundreds of entries (User Manager, I'm looking at you)</li>
			<li>Sortable</li>
			<li>Searchable</li>
			<li>More theme-able</li>
		</ul>
	");

}

// vim: ts=4 syntax=php
