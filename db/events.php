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

        
$observers = array (

/*
 * Event Handlers
 */
    array (
        'eventname'      => '\assignsubmission_file\event\assessable_uploaded',
        'includefile'      => '/plagiarism/unplag/lib.php',
        'callback'  => 'plagiarism_plugin_unplag::unplag_event_file_uploaded',
    ),
    array (
        'eventname'      => '\mod_workshop\event\assessable_uploaded',
        'includefile'      => '/plagiarism/unplag/lib.php',
        'callback'  => 'plagiarism_plugin_unplag::unplag_event_file_uploaded',

    ),
    array (
        'eventname'      => 'mod_forum\event\assessable_uploaded',
        'includefile'      => '/plagiarism/unplag/lib.php',
        'callback'  => 'plagiarism_plugin_unplag::unplag_event_file_uploaded',

    ),

    array (
        'eventname'      => '\assignsubmission_onlinetext\event\assessable_uploaded',
        'includefile'      => '/plagiarism/unplag/lib.php',
        'callback'  => 'plagiarism_plugin_unplag::unplag_event_content_uploaded',

    ),
  

    array (
        'eventname'      => '\mod_assign\event\assessable_submitted',
        'includefile'      => '/plagiarism/unplag/lib.php',
        'callback'  => 'plagiarism_plugin_unplag::unplag_event_assessable_submitted',
   
    ),

);
