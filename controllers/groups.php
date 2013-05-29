<?php

/**
 * Policy groups controller.
 *
 * @category   apps
 * @package    policy-manager
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/policy_manager/
 */

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
///////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////////////////////////
// D E P E N D E N C I E S
///////////////////////////////////////////////////////////////////////////////

use \Exception as Exception;
use \clearos\apps\accounts\Accounts_Engine as Accounts_Engine;
use \clearos\apps\groups\Group_Engine as Group;

///////////////////////////////////////////////////////////////////////////////
// C L A S S
///////////////////////////////////////////////////////////////////////////////

/**
 * Policy groups controller.
 *
 * @category   apps
 * @package    policy-manager
 * @subpackage controllers
 * @author     ClearFoundation <developer@clearfoundation.com>
 * @copyright  2013 ClearFoundation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License version 3 or later
 * @link       http://www.clearfoundation.com/docs/developer/apps/policy_manager/
 */

class Groups extends ClearOS_Controller
{
    protected $app_name = NULL;
    protected $group_list = NULL;

    /**
     * Group policy membership constructor.
     *
     * @param string  $app_name   app that manages the group
     * @param string  $group_list group name
     *
     * @return view
     */

    function __construct($app_name, $group_list)
    {
        $this->app_name = $app_name;
        $this->group_list = $group_list;
    }

    /**
     * Groups server overview.
     *
     * @return view
     */

    function index()
    {
        // Show account status widget if we're not in a happy state
        //---------------------------------------------------------

        $this->load->module('accounts/status');

        if ($this->status->unhappy())
            return;

        // Show cache widget if using remote accounts (e.g. AD)
        //-----------------------------------------------------

        $this->load->module('accounts/cache');

        if ($this->cache->needs_reset()) {
            $app_name = empty($this->app_name) ? 'groups' :  $this->app_name;
            $this->cache->widget($app_name);
            return;
        }

        // Load libraries
        //---------------

        $this->lang->load('groups');
        $this->load->factory('groups/Group_Manager_Factory');
        $this->load->factory('accounts/Accounts_Factory');

        // Load view data
        //---------------

        try {
            $data['basename'] = $this->app_name;
            $data['groups'] = array();

            $all_groups = $this->group_manager->get_details(Group::FILTER_PLUGIN);

            foreach ($this->group_list as $group) {
                if (array_key_exists($group, $all_groups))
                    $data['groups'][] = $all_groups[$group];
            }

            if ($this->accounts->get_capability() === Accounts_Engine::CAPABILITY_READ_WRITE)
                $data['mode'] = 'edit';
            else
                $data['mode'] = 'view';
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        if (empty($data['groups']))
            return;

        // Load views
        //-----------

        $options['javascript'] = array(clearos_app_htdocs('accounts') . '/cache.js.php');

        $this->page->view_form('policy_manager/policies', $data, lang('groups_group_manager'), $options);
    }

    /**
     * Add view.
     *
     * @return view
     */

    function add()
    {
        $this->_handle_item('add', $group_name);
    }

    /**
     * Group delete view.
     *
     * @param string $group_name group name
     *
     * @return view
     */

    function delete($group_name)
    {
        $show_group_name = strtr($group_name, '~:', '$ '); // spaces and dollars not allowed, so munge

        $confirm_uri = '/app/groups/destroy/' . $group_name;
        $cancel_uri = '/app/groups';
        $items = array($show_group_name);

        $this->page->view_confirm_delete($confirm_uri, $cancel_uri, $items);
    }

    /**
     * Destroys group.
     *
     * @param string $group_name group name
     *
     * @return view
     */

    function destroy($group_name)
    {
        $group_name = strtr($group_name, '~:', '$ '); // spaces and dollars not allowed, so munge

        // Load libraries
        //---------------

        $this->load->factory('groups/Group_Factory', $group_name);

        // Handle form submit
        //-------------------

        try {
            $this->group->delete();
            $this->page->set_status_deleted();
            redirect('/groups');
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }
    }

    /**
     * Group edit view.
     *
     * @param string $group_name group name
     *
     * @return view
     */

    function edit($group_name)
    {
        $group_name = strtr($group_name, '~:', '$ '); // spaces and dollars not allowed, so munge

        $this->_handle_item('edit', $group_name);
    }

    /**
     * Group edit members view.
     *
     * @param string $group_name group name
     *
     * @return view
     */

    function edit_members($group_name)
    {
        $group_name = strtr($group_name, '~:', '$ '); // spaces and dollars not allowed, so munge

        $this->_handle_members('edit', $group_name);
    }

    /**
     * User view.
     *
     * @param string $group_name group_name
     *
     * @return view
     */

    function view($group_name)
    {
        $group_name = strtr($group_name, '~:', '$ '); // spaces and dollars not allowed, so munge

        $this->_handle_item('view', $group_name);
    }

    /**
     * Group members view.
     *
     * @param string $group_name group name
     *
     * @return view
     */

    function view_members($group_name)
    {
        $group_name = strtr($group_name, '~:', '$ '); // spaces and dollars not allowed, so munge

        $this->_handle_members('view', $group_name);
    }

    ///////////////////////////////////////////////////////////////////////////////
    // P R I V A T E
    ///////////////////////////////////////////////////////////////////////////////

    /**
     * Group common view/edit members form handler.
     *
     * @param string  $form_type   form type (add, edit or view)
     * @param string  $group_name  group_name
     * @param boolean $account_app see account_plugin_members
     *
     * @return view
     */

    function _handle_members($form_type, $group_name, $account_app = FALSE)
    {
        // Load libraries
        //---------------

        $this->lang->load('groups');
        $this->load->factory('users/User_Manager_Factory');
        $this->load->factory('groups/Group_Factory', $group_name);

        // Check group policy
        //-------------------

        if (! empty($this->group_list) && (!in_array($group_name, $this->group_list))) {
            throw new Exception('not allowed');
            $this->page->view_exception($e);
            return;
        }

        // Handle form submit
        //-------------------

        if ($this->input->post('submit')) {
            try {
                $users = array();
                // A period is not permitted as key, so translate it into a colon
                
                foreach ($this->input->post('users') as $user => $state)
                    $users[] = preg_replace('/:/', '.', $user);
                
                $this->group->set_members($users);

                $this->page->set_status_updated();

                if ($account_app)
                    redirect('/accounts/plugins');
                if (empty($this->app_name))
                    redirect('/groups');
                else
                    redirect($this->app_name);

            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load the view data 
        //------------------- 

        try {
            $data['mode'] = $form_type;
            $data['basename'] = empty($this->app_name) ? '' : $this->app_name;
            $data['group_info'] = $this->group->get_info();
            $data['users'] = $this->user_manager->get_details();
            $data['account_app'] = $account_app;
        } catch (Group_Not_Found_Exception $e) {
            $this->page->view_form('groups/nomembers', NULL, lang('groups_members'));
            return;
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load the views
        //---------------

        $this->page->view_form('policy_manager/members', $data, lang('groups_members'));
    }

    /**
     * Group common add/edit form handler.
     *
     * @param string $form_type  form type (add, edit or view)
     * @param string $group_name group_name
     *
     * @return view
     */

    function _handle_item($form_type, $group_name)
    {
        // Load libraries
        //---------------

        $this->load->library('policy_manager/Policy_Manager');
        $this->load->factory('groups/Group_Manager_Factory');

        // Set validation rules
        //---------------------

        $this->form_validation->set_policy('group', 'policy_manager/Policy_Manager', 'validate_group', TRUE);
        $this->form_validation->set_policy('policy_name', 'policy_manager/Policy_Manager', 'validate_policy_name', TRUE);
        $form_ok = $this->form_validation->run();

        // Handle form submit
        //-------------------

        if ($this->input->post('submit') && $form_ok) {
            try {
                if ($form_type === 'edit') {
                    $this->policy_manager->set_policy(
                        $policy,
                        $this->input->post('policy_name'),
                        $this->input->post('group')
                    );
                    $this->page->set_status_updated();
                } else {
                    $this->policy_manager->add(
                        $this->input->post('policy_name'),
                        $this->input->post('group')
                    );
                    $this->page->set_status_added();
                }

                redirect($this->app_name . '/policy');
            } catch (Exception $e) {
                $this->page->view_exception($e);
                return;
            }
        }

        // Load view data
        //---------------

        try {
            $data['policy'] = $policy;
            $data['form_type'] = $form_type;
            $data['basename'] = $this->app_name;

            /*
            if ($form_type === 'edit') {
                $data['policy_name'] = $this->policy_manager->get_policy_name($policy);
                $data['group'] = $this->policy_manager->get_policy_system_group($policy);
            }
            */

            $data['groups'] = $this->policy_manager->get_available_groups();
        } catch (Exception $e) {
            $this->page->view_exception($e);
            return;
        }

        // Load views
        //-----------

        $this->page->view_form('policy_manager/item', $data, lang('groups_policy'));
    }
}
