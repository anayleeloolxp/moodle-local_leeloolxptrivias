<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Leeloolxptrivias - Upgrade script
 *
 * @package    local_leeloolxptrivias
 * @copyright  2020 Leeloo LXP (https://leeloolxp.com)
 * @author     Leeloo LXP <info@leeloolxp.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Function to upgrade local_leeloolxptrivias
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_local_leeloolxptrivias_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2022053001) {
        $table = new xmldb_table('tb_question_diff');
        $dbman->rename_table($table, 'local_leeloolxptrivias_qd', true, true);
        upgrade_plugin_savepoint(true, 2022053001, 'local', 'leeloolxptrivias');
    }

    if ($oldversion < 2022053002) {
        $table = new xmldb_table('local_leeloolxptrivias_qd');
        $field = new xmldb_field('vimeoid'); // You'll have to look up the definition to see.
        $field->set_attributes(XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, null, '', '');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
    }

    return true;
}
