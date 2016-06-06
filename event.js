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
 * Javascript helper function for plugin
 *
 * @package   plagiarism_unplag
 * @author    Aleksandr Kostylev <a.kostylev@p1k.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.plagiarism_unplag_event = {
    interval: null,
    items: []
};

M.plagiarism_unplag_event.init = function (Y) {

    var dialog = function (message, success, failure) {
        var open_time = new Date();
        var result = window.confirm(message);
        var close_time = new Date();

        if (close_time - open_time < 10) {
            failure();
        } else {
            success(result);
        }
    };

    var start_check_by_url = function (url) {
        if(url){
            location.href = url;
        }
    };

    var $unplag_check_link = Y.one('.unplag-check');

    if($unplag_check_link.length){
        Y.one('.unplag-check').on('click', function(e) {
            e.preventDefault();

            var url = this.get('href');
            dialog(M.util.get_string('check_confirm', 'plagiarism_unplag'), function(result) {
                if(result.toString() == 'true'){
                    start_check_by_url(url);
                }
            }, function() {
                start_check_by_url(url);
            });

        });
    }
};