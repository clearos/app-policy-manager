<?php

/**
 * Policy groups view.
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

///////////////////////////////////////////////////////////////////////////////
// Headers
///////////////////////////////////////////////////////////////////////////////

$headers = array(
    lang('groups_policy_name'),
    lang('groups_group'),
);

///////////////////////////////////////////////////////////////////////////////
// Anchors
///////////////////////////////////////////////////////////////////////////////

$anchors = array(anchor_add('/app/' . $basename . '/policy/add'));

///////////////////////////////////////////////////////////////////////////////
// Items
///////////////////////////////////////////////////////////////////////////////

foreach ($groups as $id => $details) {

    if ($mode === 'view') {
        $anchor = '/app/' . $basename . '/policy/view_members/' . $details['core']['group_name'];
        $detail_buttons = array(anchor_custom($anchor, lang('groups_view_members')));
    } else {
        $anchor = '/app/' . $basename . '/policy/edit_members/' . $details['core']['group_name'];
        $detail_buttons = array(anchor_custom($anchor, lang('groups_edit_members')));
    }

    $policy_name = empty($details['core']['description']) ? lang('groups_global_policy') : $details['core']['description'];

    $item['title'] = $details['core']['group_name'];
    $item['action'] = $anchor;
    $item['anchors'] = button_set($detail_buttons);
    $item['details'] = array(
        $policy_name,
        $details['core']['group_name']
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
