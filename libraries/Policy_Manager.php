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

// Classes
//--------

use \clearos\apps\base\Engine as Engine;
use \clearos\apps\groups\Group_Manager_Factory as Group_Manager_Factory;
use \clearos\apps\openldap\LDAP_Driver as LDAP_Driver;
use \clearos\apps\policy_manager\Policy as Policy;

clearos_load_library('base/Engine');
clearos_load_library('groups/Group_Manager_Factory');
clearos_load_library('openldap/LDAP_Driver');
clearos_load_library('policy_manager/Policy');

// Exceptions
//-----------

use \clearos\apps\base\Validation_Exception as Validation_Exception;

clearos_load_library('base/Validation_Exception');

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
    // V A R I A B L E S
    ///////////////////////////////////////////////////////////////////////////////

    protected $ldaph = NULL;

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
     * Returns possible systems groups.
     *
     * @param string $app app name
     *
     * @return array list of possible system groups
     */

    public function get_available_groups($app)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_app($app));

        $group_manager = Group_Manager_Factory::create();

        $possible_groups = array();
        $configured_groups = array();

        $all_groups = $group_manager->get_list();
        $policies = $this->get_policies($app);

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
     * @param string $app app name
     *
     * @return array policy information.
     * @throws Engine_Exception
     */

    public function get_policies($app)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_app($app));

        // Get LDAP handle
        //----------------

        if ($this->ldaph === NULL)
            $this->_get_ldap_handle();

        // Search for policies in LDAP
        //----------------------------

        $policies = array();
        $priorities = array();

        $policy_dn = 'ou=' . $app . ',' . $this->_get_policies_container();

        if (! $this->ldaph->exists($policy_dn))
            return array();

        $result = $this->ldaph->search(
            "(objectclass=clearPolicy)",
            $policy_dn
        );

        $entry = $this->ldaph->get_first_entry($result);

        while ($entry) {
            $attributes = $this->ldaph->get_attributes($entry);

            $name = $attributes['clearPolicyName'][0];

            $details = array();
            $details['group'] = $attributes['clearPolicyGroup'][0];
            $details['description'] = $attributes['clearPolicyDescription'][0];
            $details['priority'] = empty($attributes['clearPolicyPriority'][0]) ? Policy::DEFAULT_PRIORITY : $attributes['clearPolicyPriority'][0];

            $policies[$name] = $details;
            $priorities[$details['priority']][] = $name;

            $entry = $this->ldaph->next_entry($entry);
        }

        // Add impled global group if not defined in LDAP
        //-----------------------------------------------

        /*
        if (empty($policies['global'])) {
            $policies['global']['group'] = $app . '_plugin';
            $policies['global']['description'] = 'Global'; // FIXME review, translate
            $policies['global']['priority'] = 0;

            $priorities[0][] = 'global';
        }
        */

        // Sort by priority
        //-----------------

        $sorted_policies = array();

        ksort($priorities);

        foreach ($priorities as $priority => $list) {
            sort($list);  // Alphabetic if same policy priority
            foreach ($list as $name)
                $sorted_policies[$name] = $policies[$name];
        }

        return $sorted_policies;
    }

    ///////////////////////////////////////////////////////////////////////////
    // V A L I D A T I O N
    ///////////////////////////////////////////////////////////////////////////

    /**
     * Validates app name.
     *
     * @param string $app app name
     *
     * @return string error message if app is invalid
     */

    public function validate_app($app)
    {
        clearos_profile(__METHOD__, __LINE__);

        // return lang('policy_manager_app_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Creates an LDAP handle.
     *
     * @access private
     *
     * @return void
     * @throws Engine_Exception
     */

    protected function _get_ldap_handle()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ldap = new LDAP_Driver();
        $this->ldaph = $ldap->get_ldap_handle();
    }

    /**
     * Returns policies container.
     *
     * @return string policies container
     * @throws Engine_Exception
     */

    protected function _get_policies_container()
    {
        clearos_profile(__METHOD__, __LINE__);

        $ldap = new LDAP_Driver();
        $base_dn = $ldap->get_base_dn();

        return 'ou=Policies,' . $base_dn;
    }
}
