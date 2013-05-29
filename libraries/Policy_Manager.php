<?php

/**
 * Policy manager class.
 *
 * @category   apps
 * @package    policy-manager
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/policy_manager/
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

///////////////////////////////////////////////////////////////////////////////
// N A M E S P A C E
///////////////////////////////////////////////////////////////////////////////

namespace clearos\apps\policy_manager;

///////////////////////////////////////////////////////////////////////////////
// B O O T S T R A P
///////////////////////////////////////////////////////////////////////////////

$bootstrap = getenv('CLEAROS_BOOTSTRAP') ? getenv('CLEAROS_BOOTSTRAP') : '/usr/clearos/framework/shared';
require_once $bootstrap . '/bootstrap.php';

///////////////////////////////////////////////////////////////////////////////
// T R A N S L A T I O N S
///////////////////////////////////////////////////////////////////////////////

clearos_load_language('policy_manager');

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\groups\Group_Manager_Factory as Group_Manager_Factory;
use \clearos\apps\ldap\LDAP_Factory as LDAP_Factory;

clearos_load_library('base/Engine');
clearos_load_library('groups/Group_Manager_Factory');
clearos_load_library('ldap/LDAP_Factory');

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Policy manager class.
 *
 * @category   apps
 * @package    policy-manager
 * @subpackage libraries
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/lgpl.html GNU Lesser General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/policy_manager/
 */

class Policy_Manager extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Policy manager constructor.
     */

    public function __construct()
    {
        clearos_profile(__METHOD__, __LINE__);
    }

    /**
     * Adds a policy.
     *
     * @return void
     * @throws Engine_Exception
     */

    public function add($policy, $group)
    {
        clearos_profile(__METHOD__, __LINE__);

        $ldap = LDAP_Factory::create();
        $ldaph = $ldap->get_ldap_handle();
echo "hello - $policy - $group";
die();
    }

    /**
     * Returns possible systems groups.
     *
     * @param string $add_group add group to possible list
     *
     * @return array list of possible system groups
     */

    public function get_available_groups()
    {
        clearos_profile(__METHOD__, __LINE__);

        $group_manager = Group_Manager_Factory::create();

        $possible_groups = array();
        $configured_groups = array();

        $all_groups = $group_manager->get_list();
        $policies = $this->get_policies();

        foreach ($policies as $policy)
            $configured_groups[] = $policy['group'];

        foreach ($all_groups as $group) {
            if (! in_array($group, $configured_groups))
                $possible_groups[] = $group;
        }

        return $possible_groups;
    }

    /**
     * Return an array of all policies.
     *
     * @return array policy information.
     * @throws Engine_Exception
     */

    public function get_policies()
    {
        clearos_profile(__METHOD__, __LINE__);

        $policies = array();

        // FIXME
        // $policies['custom1']['group'] = 'testgroup';

        return $policies;
    }

    ///////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Validates system group.
     *
     * @param string $group system group name
     *
     * @return string error message if system group name is invalid
     */

    public function validate_group($group)
    {
        clearos_profile(__METHOD__, __LINE__);

        $group_manager = Group_Manager_Factory::create();
        $all_groups = $group_manager->get_list();

        if (! in_array($group, $all_groups))
            return lang('groups_group_name_invalid');
    }

    /**
     * Validates policy name.
     *
     * @param string $name policy name
     *
     * @return string error message if policy name is invalid
     */

    public function validate_policy_name($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match('/^[a-z0-9_\-]+$/', $name))
            return lang('groups_policy_name_invalid');
    }
}
