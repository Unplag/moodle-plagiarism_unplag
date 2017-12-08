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
 * upgrade.php
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylec <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use plagiarism_unplag\classes\unplag_core;

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once(dirname(__FILE__) . '/../constants.php');
require_once(dirname(__FILE__) . '/../autoloader.php');

/**
 * Plugin upgrades
 *
 * @package     plagiarism_unplag
 * @subpackage  plagiarism
 * @author      Aleksandr Kostylec <a.kostylev@p1k.co.uk>
 * @copyright   UKU Group, LTD, https://www.unicheck.com
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @param int $oldversion
 *
 * @return bool
 * @throws ddl_exception
 * @throws ddl_field_missing_exception
 * @throws ddl_table_missing_exception
 * @throws downgrade_exception
 * @throws upgrade_exception
 */
function xmldb_plagiarism_unplag_upgrade($oldversion) {
    global $DB;

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

        // Unicheck savepoint reached.
        upgrade_plugin_savepoint(true, 2016041600, 'plagiarism', 'unplag');
    }

    if ($oldversion < 2016100500) {
        // Define field external_file_id to be added to plagiarism_unplag_files.
        $table = new xmldb_table('plagiarism_unplag_files');

        $field = new xmldb_field('parent_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'id');

        // Conditionally launch add field parent_id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('type', XMLDB_TYPE_CHAR, '63', null, XMLDB_NOTNULL, null, 'document', 'filename');

        // Conditionally launch add field type.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Unicheck savepoint reached.
        upgrade_plugin_savepoint(true, 2016100500, 'plagiarism', 'unplag');
    }

    if ($oldversion < 2016112200) {
        if ($dbman->table_exists('plagiarism_unplag_user_data')) {
            $dbman->drop_table('plagiarism_unplag_user_data');
        }

        $table = new xmldb_table('plagiarism_unplag_user_data');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->add_field('user_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->add_field('external_user_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null);
        $table->add_field('external_token', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id'], null, null);
        $table->add_key('user_id', XMLDB_KEY_FOREIGN, ['user_id'], 'user', 'id');

        $dbman->create_table($table);
        upgrade_plugin_savepoint(true, 2016112200, 'plagiarism', 'unplag');
    }

    if ($oldversion < 2017012300) {
        unplag_core::migrate_users_access();
        upgrade_plugin_savepoint(true, 2017012300, 'plagiarism', 'unplag');
    }

    if ($oldversion < 2017120100) {

        $table = new xmldb_table('plagiarism_unplag_files');

        $field = new xmldb_field('state', XMLDB_TYPE_CHAR, '63', null, XMLDB_NOTNULL, null, 'CREATED', 'reportediturl');
        // Conditionally launch add field type.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);

            $files = $DB->get_recordset('plagiarism_unplag_files', null, 'id asc', '*');
            foreach ($files as $file) {
                switch ($file->statuscode) {
                    case 200:
                        $file->state = 'CHECKED';
                        break;
                    case 202:
                        if ($file->check_id) {
                            $file->state = 'CHECKING';

                            break;
                        }

                        $file->state = 'UPLOADED';
                        break;
                    case 'pending':
                        $file->state = 'CREATED';
                        break;
                    default:
                        $file->state = 'HAS_ERROR';
                        break;
                }
                $DB->update_record('plagiarism_unplag_files', $file);
            }
            $files->close(); // Don't forget to close the recordset!
        }

        $field = new xmldb_field('external_file_uuid', XMLDB_TYPE_CHAR, '63', null, null, null, null, 'state');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Unicheck savepoint reached.
        upgrade_plugin_savepoint(true, 2017120100, 'plagiarism', 'unplag');
    }

    return true;
}
