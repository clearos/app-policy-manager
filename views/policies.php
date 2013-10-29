<?php

/**
 * Policy overview.
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

$this->lang->load('policy_manager');

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('policy_manager_policy_name'),
    lang('policy_manager_group'),
);

///////////////////////////////////////////////////////////////////////////////
// Anchors
///////////////////////////////////////////////////////////////////////////////

$anchors = array(anchor_add('/app/' . $basename . '/policy/add'));

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

foreach ($policies as $name => $details) {

    $detail_buttons = array(anchor_custom('/app/' . $basename . '/policy/configure/' . $name, lang('base_configure_policy')));

    if ($name !== 'global') {
        $detail_buttons[] = anchor_edit('/app/' . $basename . '/policy/edit/' . $name);
        $detail_buttons[] = anchor_delete('/app/' . $basename . '/policy/delete/' . $name);
    }

    $item['title'] = $name;
    $item['action'] = $anchor;
    $item['anchors'] = button_set($detail_buttons);
    $item['details'] = array(
        $details['description'],
        $details['group']
    );

    $items[] = $item;
}

///////////////////////////////////////////////////////////////////////////////
// Summary table
///////////////////////////////////////////////////////////////////////////////

$options =  array('sort' => FALSE);

echo summary_table(
    lang('groups_app_policies'),
    $anchors,
    $headers,
    $items,
    $options
);
