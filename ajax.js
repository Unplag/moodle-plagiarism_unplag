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
 * Javascript helper function for URKUND plugin
 *
 * @package   plagiarism-unplag
 * @author Mikhail Grinenko <m.grinenko@p1k.co.uk>
 * @copyright Mikhail Grinenko <m.grinenko@p1k.co.uk>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.plagiarism_unplag = {
    interval: null,
    items: []
};

M.plagiarism_unplag.init = function (Y, contextid) {
    var track_progress = function (Y, items, contextid) {

        if (!items[0]){
            clearInterval(M.plagiarism_unplag.interval);
            return false;
        }

        var url = M.cfg.wwwroot + '/plagiarism/unplag/ajax.php';

        var callback = {
            method: 'get',
            context: this,
            sync: false,
            data: {
                'action': 'track_progress',
                'sesskey': M.cfg.sesskey,
                'data': Y.JSON.stringify({
                    ids: items,
                    cid: contextid
                })
            },
            on: {
                success: function (tid, response) {
                    var jsondata = Y.JSON.parse(response.responseText);
                    if (!jsondata){
                        return false;
                    }

                    Y.each(jsondata, function (item, index) {
                        handle_record(item, index);
                    });
                },
                failure: function (tid, response) {
                }
            }
        };

        Y.io(url, callback)
    };

    var handle_record = function (record) {
        var existing = Y.one('.un_progress_val[file_id="' + record.file_id + '"]');
        if (!existing) {
            return;
        }

        existing.setContent(record.progress + '%');

        if (record.progress == 100) {
            existing.addClass('complete');
            var items = M.plagiarism_unplag.items;
            delete items[items.indexOf(record.file_id)];
        }
    };

    var collect_items = function () {
        Y.all('.un_progress').each(function (row) {
            if (!row.hasClass('complete')){
                M.plagiarism_unplag.items.push(row.getAttribute('file_id'));
            }
        });
    };

    var run_plagin = function () {

        collect_items();

        if (M.plagiarism_unplag.items.length) {
            M.plagiarism_unplag.interval = setInterval(function () {
                track_progress(Y, M.plagiarism_unplag.items, contextid)
            }, 5000);
        }
    };

    run_plagin();
};