<?php

/**
 * Policy manager item view.
 *
 * @category   apps
 * @package    policy-manager
 * @subpackage views
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
// Load dependencies
///////////////////////////////////////////////////////////////////////////////

$this->lang->load('base');
$this->lang->load('policy_manager');

///////////////////////////////////////////////////////////////////////////////
// Form handler
///////////////////////////////////////////////////////////////////////////////

if ($form_type === 'edit') {
    $read_only = TRUE;
    $form = $basename . '/policy/edit/' . $policy;
    $buttons = array(
        form_submit_update('submit'),
        anchor_cancel('/app/' . $basename . '/policy')
    );
} else {
    $read_only = FALSE;
    $form = $basename . '/policy/add';
    $buttons = array(
        form_submit_add('submit'),
        anchor_cancel('/app/' . $basename . '/policy')
    );
}

if (count($groups) == 0) {
    echo infobox_warning(
        lang('base_warning'), 
        lang('groups_no_user_defined_groups_warning') . '<br><br>' . 
        anchor_custom('/app/' . $basename . '/policy', lang('base_back')) . ' ' .
        anchor_custom('/app/groups', lang('groups_add_user_defined_group'))
    );
    return;
}

///////////////////////////////////////////////////////////////////////////////
// Form
///////////////////////////////////////////////////////////////////////////////

echo form_open($form);
echo form_header(lang('policy_manager_policy'));

echo field_input('name', $name, lang('policy_manager_policy_name'), $read_only);
echo field_input('description', $description, lang('base_description'));
echo field_simple_dropdown('group', $groups, $group, lang('base_group'));
echo field_button_set($buttons);

echo form_footer();
echo form_close();
