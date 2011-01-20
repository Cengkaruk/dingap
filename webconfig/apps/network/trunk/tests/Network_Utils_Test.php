<?php

/**
 * Network utility test class.
 *
 * @category   Apps
 * @package    Base
 * @subpackage Tests
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/dns/
 */

///////////////////////////////////////////////////////////////////////////////
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

use \clearos\apps\network\Network_Utils as Network_Utils;
require_once '../libraries/Network_Utils.php';

/**
 * Network utility test class.
 *
 * @category   Apps
 * @package    Base
 * @subpackage Tests
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2003-2011 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/dns/
 */

class Network_Utils_Test extends PHPUnit_Framework_TestCase
{
    /**
     * @var Network_Utils
     */
    protected $network;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->network = new Network_Utils();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    ///////////////////////////////////////////////////////////////////////////////
    // get_broadcast_address
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Tests broadcast address calculation.
     */

    public function test_get_broadcast_address()
    {
        $result = $this->network->get_broadcast_address('192.168.22.22', '255.255.255.0');
        $this->assertEquals('192.168.22.255', $result);
    }

    /**
     * Tests broadcast address calculation with an empty IP.
     */

    public function test_get_broadcast_address_empty_ip()
    {
        $this->setExpectedException('clearos\apps\base\Validation_Exception');
        $result = $this->network->get_broadcast_address('', '255.255.255.0');
    }

    /**
     * Tests broadcast address calculation with an empty netmask.
     */

    public function test_get_broadcast_address_empty_netmask()
    {
        $this->setExpectedException('clearos\apps\base\Validation_Exception');
        $result = $this->network->get_broadcast_address('192.168.22.22', '');
    }

    /**
     * Tests broadcast address calculation with an invalid IP
     */

    public function test_get_broadcast_address_invalid_ip()
    {
        $this->setExpectedException('clearos\apps\base\Validation_Exception');
        $result = $this->network->get_broadcast_address('not an IP', '255.255.255.0');
    }

    /**
     * Tests broadcast address calculation with an invalid netmask
     */

    public function test_get_broadcast_address_invalid_netmask()
    {
        $this->setExpectedException('clearos\apps\base\Validation_Exception');
        $result = $this->network->get_broadcast_address('192.168.22.22', '255.255.255.33');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // get_netmask
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Tests for valid netmask.
     */

    public function test_get_netmask()
    {
        $result = $this->network->get_netmask('24');
        $this->assertEquals('255.255.255.0', $result);
    }

    /**
     * Tests for empty netmask.
     */

    public function test_get_netmask_empty()
    {
        $this->setExpectedException('clearos\apps\base\Validation_Exception');
        $result = $this->network->get_netmask('');
    }

    /**
     * Tests for non-sensical netmask.
     */

    public function test_get_netmask_nonsense()
    {
        $this->setExpectedException('clearos\apps\base\Validation_Exception');
        $result = $this->network->get_netmask('not a netmask');
    }

    /**
     * Tests for out of range netmask.
     */

    public function test_get_netmask_out_of_range()
    {
        $this->setExpectedException('clearos\apps\base\Validation_Exception');
        $result = $this->network->get_netmask('33');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // get_network_address
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Tests network address calculation.
     */

    public function test_get_network_address()
    {
        $result = $this->network->get_network_address('192.168.22.22', '255.255.255.0');
        $this->assertEquals('192.168.22.0', $result);
    }

    /**
     * Tests network address calculation with an empty IP.
     */

    public function test_get_network_address_empty_ip()
    {
        $this->setExpectedException('clearos\apps\base\Validation_Exception');
        $result = $this->network->get_network_address('', '255.255.255.0');
    }

    /**
     * Tests network address calculation with an empty netmask.
     */

    public function test_get_network_address_empty_netmask()
    {
        $this->setExpectedException('clearos\apps\base\Validation_Exception');
        $result = $this->network->get_network_address('192.168.22.22', '');
    }

    /**
     * Tests network address calculation with an invalid IP
     */

    public function test_get_network_address_invalid_ip()
    {
        $this->setExpectedException('clearos\apps\base\Validation_Exception');
        $result = $this->network->get_network_address('not an IP', '255.255.255.0');
    }

    /**
     * Tests network address calculation with an invalid netmask
     */

    public function test_get_network_address_invalid_netmask()
    {
        $this->setExpectedException('clearos\apps\base\Validation_Exception');
        $result = $this->network->get_network_address('192.168.22.22', '255.255.255.33');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // get_prefix
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Tests for valid prefix.
     */

    public function test_get_prefix()
    {
        $result = $this->network->get_prefix('255.255.255.0');
        $this->assertEquals('24', $result);
    }

    /**
     * Tests for empty prefix.
     */

    public function test_get_prefix_empty()
    {
        $this->setExpectedException('clearos\apps\base\Validation_Exception');
        $result = $this->network->get_prefix('');
    }

    /**
     * Tests for non-sensical prefix.
     */

    public function test_get_prefix_nonsense()
    {
        $this->setExpectedException('clearos\apps\base\Validation_Exception');
        $result = $this->network->get_prefix('not a prefix');
    }

    /**
     * Tests for out of range prefix.
     */

    public function test_get_prefix_out_of_range()
    {
        $this->setExpectedException('clearos\apps\base\Validation_Exception');
        $result = $this->network->get_prefix('255.255.255.256');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // is_private_ip
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Tests for private IP detection.
     */

    public function test_is_private_ip()
    {
        $result = $this->network->is_private_ip('192.168.1.100');
        $this->assertTrue($result);
    }

    /**
     * Tests for private IP detection with public IP.
     */

    public function test_is_private_ip_public()
    {
        $result = $this->network->is_private_ip('1.2.3.4');
        $this->assertFalse($result);
    }

    /**
     * Tests for private IP detection with public IP 172.15.255.255.
     */

    public function test_is_private_ip_public_172_15()
    {
        $result = $this->network->is_private_ip('172.15.255.255');
        $this->assertFalse($result);
    }

    /**
     * Tests for private IP detection with empty IP.
     */

    public function test_is_private_ip_empty()
    {
        $this->setExpectedException('clearos\apps\base\Validation_Exception');
        $result = $this->network->is_private_ip('');
    }

    /**
     * Tests for private IP detection with non-sensical IP.
     */

    public function test_is_private_ip_nonsense()
    {
        $this->setExpectedException('clearos\apps\base\Validation_Exception');
        $result = $this->network->is_private_ip('not a prefix');
    }

    /**
     * Tests for private IP detection with out of range IP.
     */

    public function test_is_private_ip_out_of_range()
    {
        $this->setExpectedException('clearos\apps\base\Validation_Exception');
        $result = $this->network->is_private_ip('255.255.255.256');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // validate_hostname_alias
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * @todo Implement test_validate_hostname_alias().
     */
    public function test_validate_hostname_alias()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement test_validate_hostname().
     */
    public function test_validate_hostname()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement test_validate_domain().
     */
    public function test_validate_domain()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * Tests for valid IP.
     */

    public function test_validate_ip()
    {
        $result = $this->network->validate_ip('1.2.3.4');
        $this->assertEmpty($result, $result);
    }

    /**
     * Tests validation for non-sensical IP address.
     */

    public function test_validate_ip_nonsense()
    {
        $result = $this->network->validate_ip('not an IP');
        $this->assertStringMatchesFormat('%s', $result);
    }

    /**
     * Tests validation for out of range IP address.
     */

    public function test_validate_ip_out_of_range()
    {
        $result = $this->network->validate_ip('1.2.3.256');
        $this->assertStringMatchesFormat('%s', $result);
    }

    /**
     * @todo Implement test_validate_ip_on_network().
     */
    public function test_validate_ip_on_network()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement test_validate_mac().
     */
    public function test_validate_mac()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement test_validate_ip_range().
     */
    public function test_validate_ip_range()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement test_validate_netmask().
     */
    public function test_validate_netmask()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement test_validate_network().
     */
    public function test_validate_network()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement test_validate_port().
     */
    public function test_validate_port()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement test_validate_port_range().
     */
    public function test_validate_port_range()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement test_validate_prefix().
     */
    public function test_validate_prefix()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement test_validate_protocol().
     */
    public function test_validate_protocol()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement test_validate_local_ip().
     */
    public function test_validate_local_ip()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
}
?>
