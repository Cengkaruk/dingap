<?php

///////////////////////////////////////////////////////////////////////////////
//
// Copyright 2003-2006 Point Clark Networks.
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

/**
 * Web page screen scraper class.
 *
 * @package Gui
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C E S
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Web page screen scraper class.
 *
 * This class is used to screen scrape other web pages.
 *
 * @package Gui
 * @author {@link http://www.pointclark.net/ Point Clark Networks}
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright Copyright 2006, Point Clark Networks
 */

class ScreenScraper
{
	///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

	/**
     * ScreenScraper constructor.
	 *
     */

    public function __construct()
    {}

	/**
	 * Returns the body of a web page.
	 *
	 * This method returns everything between the opening
	 * and closing body tags.  This is handy for wrapping third party web
	 * pages into the web-interface template system. You may have to add 
	 * extra hacking to get this to behave for your particular screen scrape.
	 * You will end up having to fix bad HTML in most cases.
	 *
	 * @param string $url URL
	 * @param string $postfields post fields
	 * @param string $wrapper name of the wrapper page
	 * @return string body of the HTML page
	 */

	function GetBody($url, $postfields, $wrapper)
	{
		try {
			$ch = curl_init();

			if ($postfields) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
				curl_setopt($ch, CURLOPT_POST, 1);
			}

			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_TIMEOUT, 40);
			curl_setopt($ch, CURLOPT_FAILONERROR, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

			$body = curl_exec($ch);
			$error = curl_error($ch);
			$errno = curl_errno($ch);
            curl_close($ch);

			if ($errno != 0)
				throw new Exception ($url . ' - ' . $error, COMMON_ERROR);
		} catch (Exception $e) {
			throw new Exception($e->GetMessage(), COMMON_ERROR);
		}

		// Get rid of header
		$body = preg_replace("/.*<body[^>]*>/si", "", $body);
		$body = preg_replace("/<\/body>.*/si", "", $body);

		// Fix URLs
		$path = preg_replace("/[^\/]*$/", "", $url);
		$body = preg_replace("/href=\"/si", "href=\"$wrapper?target=$path", $body);
		$body = preg_replace("/href=\"[^\"]+http:/i", "href=\"http:", $body);

		// Fix image paths
		$imagepath = preg_replace("/http.*:\/\/[^\/]*/", "", $path);
		$body = preg_replace("/src=\"/si", "src=\"$imagepath/", $body);
		$body = preg_replace("/background=\"/si", "background=\"$imagepath/", $body);

		return $body;
	}
}

// vim: syntax=php ts=4
?>
