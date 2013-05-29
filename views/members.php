<?php

/**
 * Policy groups members view.
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

$this->lang->load('groups');
$this->lang->load('users');

///////////////////////////////////////////////////////////////////////////////
// Buttons
///////////////////////////////////////////////////////////////////////////////

$group_name = $group_info['core']['group_name'];
$safe_group_name = strtr($group_name, '$ ', '~:'); // spaces and dollars not allowed, so munge

$base_app = '/app/' . $basename . '/policy';
$form = $basename . '/policy/edit_members/' . $safe_group_name;

if ($mode === 'view') {
    $buttons = array(anchor_cancel($base_app));
    $read_only = TRUE;
} else {
    $buttons = array(anchor_cancel($base_app, 'high'), form_submit_update('submit', 'high'));
    $read_only = FALSE;
}

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('users_username'),
    lang('users_full_name'),
);

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

foreach ($users as $username => $details) {
    // A period is not permitted as key, so translate it into a colon
    $item['title'] = $username;
    $item['name'] = 'users[' . preg_replace('/\./', ':', $username) . ']';
    $item['state'] = (in_array($username, $group_info['core']['members'])) ? TRUE : FALSE;
    $item['details'] = array(
        $username,
        $details['core']['full_name']
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// List table
///////////////////////////////////////////////////////////////////////////////

echo form_open($form);

echo list_table(
    $group_info['core']['description'],
    $buttons,
    $headers,
    $items,
    array('read_only' => $read_only)
);

echo form_close();
