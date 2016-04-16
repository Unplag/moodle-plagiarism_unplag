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
 * @global moodle_database $DB
 *
 * @param int              $oldversion
 *
 * @return bool
 */
function xmldb_plagiarism_unplag_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2016041600) {
        // Define field external_file_id to be added to plagiarism_unplag_files.
        $table = new xmldb_table('plagiarism_unplag_files');

        $field = new xmldb_field('external_file_id', XMLDB_TYPE_INTEGER, '10', true, null, null, null);
        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('reportediturl', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        // Conditionally launch add field.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('similarityscore', XMLDB_TYPE_NUMBER, '5,2', null, null, null, null, null);
        $dbman->change_field_type($table, $field);

        // Unplag savepoint reached.
        upgrade_plugin_savepoint(true, 2016041600, 'plagiarism', 'unplag');
    }

    return true;
}