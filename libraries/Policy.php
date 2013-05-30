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
use \clearos\apps\openldap_directory\OpenLDAP as OpenLDAP;

clearos_load_library('base/Engine');
clearos_load_library('groups/Group_Manager_Factory');
clearos_load_library('openldap/LDAP_Driver');
clearos_load_library('openldap_directory/OpenLDAP');

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

class Policy extends Engine
{
    ///////////////////////////////////////////////////////////////////////////////
    // C O N S T A N T S
    ///////////////////////////////////////////////////////////////////////////////

    const DEFAULT_PRIORITY = 100;
    const DEFAULT_STATE = 1;

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
     * Adds a policy.
     *
     * @param string $app         app name
     * @param string $name        policy name
     * @param string $group       system group
     * @param string $description policy description
     * @param string $priority    policy priority
     *
     * @return void
     * @throws Engine_Exception
     */

    public function add($app, $name, $group, $description = '', $priority = '')
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        Validation_Exception::is_valid($this->validate_app($app));
        Validation_Exception::is_valid($this->validate_name($name));
        Validation_Exception::is_valid($this->validate_group($group));
        Validation_Exception::is_valid($this->validate_description($description));
        Validation_Exception::is_valid($this->validate_priority($priority));

        // Add top-level policies container
        //---------------------------------

        if ($this->ldaph === NULL)
            $this->_get_ldap_handle();

        $policy_dn = $this->_get_policies_container();

        $policy_container['ou'] = 'Policies';
        $policy_container['objectClass'] = array(
            'top',
            'organizationalUnit'
        );

        if (! $this->ldaph->exists($policy_dn)) {
            clearos_log('policy_manager', 'adding policy manager container');
            $this->ldaph->add($policy_dn, $policy_container);
        }

        // Add app policies container
        //---------------------------

        $app_dn = 'ou=' . $app . ',' . $policy_dn;

        $app_container['ou'] = $app;
        $app_container['objectClass'] = array(
            'top',
            'organizationalUnit'
        );

        if (! $this->ldaph->exists($app_dn)) {
            clearos_log('policy_manager', 'adding app policy container: ' . $app);
            $this->ldaph->add($app_dn, $app_container);
        }

        // Add policy
        //-----------

        $policy_dn = 'clearPolicyName=' . $name . ',' . $app_dn;

        $ldap_object['objectClass'] = array(
            'top',
            'clearPolicy'
        );

        $ldap_object['clearPolicyName'] = $name;
        $ldap_object['clearPolicyApp'] = $app;
        $ldap_object['clearPolicyState'] = self::DEFAULT_STATE;
        $ldap_object['clearPolicyPriority'] = empty($priority) ? self::DEFAULT_PRIORITY : $priority;
        $ldap_object['clearPolicyDescription'] = $description;
        $ldap_object['clearPolicyGroup'] = $group;

        if (! $this->ldaph->exists($policy_dn))
            $this->ldaph->add($policy_dn, $ldap_object);
    }

    /**
     * Deletes a policy.
     *
     * @param string $app  app name
     * @param string $name policy name
     *
     * @return void
     * @throws Engine_Exception
     */

    public function delete($app, $name)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_app($app));
        Validation_Exception::is_valid($this->validate_name($name));

        if ($this->ldaph === NULL)
            $this->_get_ldap_handle();

        $ldap_object['objectClass'] = array(
            'top',
            'clearPolicy'
        );

        $dn = 'clearPolicyName=' . $name . ',ou=' . $app . ',' . $this->_get_policies_container();

        $this->ldaph->delete($dn);
    }

    /**
     * Returns policy information.
     *
     * @param string $app  app name
     * @param string $name policy name
     *
     * @return array policy information.
     * @throws Engine_Exception
     */

    public function get($app, $name)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_app($app));
        Validation_Exception::is_valid($this->validate_name($name));

        // Get LDAP handle
        //----------------

        if ($this->ldaph === NULL)
            $this->_get_ldap_handle();

        // Search for policies in LDAP
        //----------------------------

        $result = $this->ldaph->search(
            "(&(objectclass=clearPolicy)(clearPolicyName=$name))",
            'ou=' . $app . ',' . $this->_get_policies_container()
        );

        $entry = $this->ldaph->get_first_entry($result);

        if (empty($entry) && ($name === 'global')) {
            $policy['state'] = TRUE;
            $policy['priority'] = 1;
            $policy['description'] = '';
            $policy['group'] = $app . '_plugin';
            $policy['settings'] = array();
        } else {
            $attributes = $this->ldaph->get_attributes($entry);

            $policy['state'] = $attributes['clearPolicyState'][0];
            $policy['priority'] = $attributes['clearPolicyPriority'][0];
            $policy['description'] = $attributes['clearPolicyDescription'][0];
            $policy['group'] = $attributes['clearPolicyGroup'][0];
            $policy['settings'] = unserialize($attributes['clearPolicySettings'][0]);
        }

        return $policy;
    }

    /**
     * Stores policy settings.
     *
     * @param string $app      app name
     * @param string $name     policy name
     * @param string $settings policy settings
     *
     * @return void
     * @throws Engine_Exception
     */

    public function store_settings($app, $name, $settings)
    {
        clearos_profile(__METHOD__, __LINE__);

        Validation_Exception::is_valid($this->validate_app($app));
        Validation_Exception::is_valid($this->validate_name($name));

        if ($this->ldaph === NULL)
            $this->_get_ldap_handle();

        $ldap_object['objectClass'] = array(
            'top',
            'clearPolicy'
        );

        $dn = 'clearPolicyName=' . $name . ',ou=' . $app . ',' . $this->_get_policies_container();

        $ldap_object['clearPolicySettings'] = serialize($settings);

        // Add global policy if not in LDAP
        /*
        if (!$this->ldaph->exists($dn) && ($name === 'global'))
            $this->add($app, 'global', $app . '_plugin', 'Global', 1); // FIXME: language?
        */

        $this->ldaph->modify($dn, $ldap_object);
    }

    /**
     * Updates a policy.
     *
     * @param string $app         app name
     * @param string $name        policy name
     * @param string $group       system group
     * @param string $description policy description
     *
     * @return void
     * @throws Engine_Exception
     */

    public function update($app, $name, $group, $description = '')
    {
        clearos_profile(__METHOD__, __LINE__);

        // Validate
        //---------

        Validation_Exception::is_valid($this->validate_app($app));
        Validation_Exception::is_valid($this->validate_name($name));
        Validation_Exception::is_valid($this->validate_group($group));
        Validation_Exception::is_valid($this->validate_description($description));

        // Perform LDAP update
        //--------------------

        if ($this->ldaph === NULL)
            $this->_get_ldap_handle();

        $ldap_object['objectClass'] = array(
            'top',
            'clearPolicy'
        );

        $dn = 'clearPolicyName=' . $name . ',ou=' . $app . ',' . $this->_get_policies_container();

        $ldap_object['clearPolicyName'] = $name;
        $ldap_object['clearPolicyGroup'] = $group;
        $ldap_object['clearPolicyDescription'] = $description;

        // Add global policy if not in LDAP
        /*
        if (!$this->ldaph->exists($dn) && ($name === 'global'))
            $this->add($app, 'global', $app . '_plugin', 'Global', 1); // FIXME: language?
        */

        $this->ldaph->modify($dn, $ldap_object);
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

    /**
     * Validates policy description.
     *
     * @param string $description policy description
     *
     * @return string error message if policy description is invalid
     */

    public function validate_description($description)
    {
        clearos_profile(__METHOD__, __LINE__);

        // return lang('policy_manager_description_invalid');
    }

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
            return lang('policy_manager_group_invalid');
    }

    /**
     * Validates policy name.
     *
     * @param string $name policy name
     *
     * @return string error message if policy name is invalid
     */

    public function validate_name($name)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (!preg_match('/^[a-z0-9_\-]+$/', $name))
            return lang('policy_manager_policy_name_invalid');
    }

    /**
     * Validates priority.
     *
     * @param int $priority priority
     *
     * @return string error message if priority is invalid
     */

    public function validate_priority($priority)
    {
        clearos_profile(__METHOD__, __LINE__);

        if (empty($priority))
            return;

        if (!preg_match('/^[0-9]$/', $priority))
            return lang('policy_manager_priority_invalid');
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E   M E T H O D S
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Creates an LDAP handle.
     *
     * @access private
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
     * @access private
     * @return string policies container
     * @throws Engine_Exception
     */

    protected function _get_policies_container()
    {
        clearos_profile(__METHOD__, __LINE__);

        $base_dn = OpenLDAP::get_base_dn();

        return 'ou=Policies,' . $base_dn;
    }
}
