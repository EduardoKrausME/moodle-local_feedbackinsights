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
 * clear.php
 *
 * @package   local_feedbackinsights
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_feedbackinsights\service\analysis_repository;

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/feedbackinsights:deleteanalyses', $context);

$url = new moodle_url('/local/feedbackinsights/clear.php', ['courseid' => $courseid]);
$returnurl = new moodle_url('/local/feedbackinsights/index.php', ['courseid' => $courseid]);

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('clearcourseanalyses', 'local_feedbackinsights'));
$PAGE->set_heading(format_string($course->fullname));

if (optional_param('confirm', 0, PARAM_BOOL)) {
    require_sesskey();
    analysis_repository::delete_course($courseid);
    redirect($returnurl, get_string('analysescleared', 'local_feedbackinsights'));
}

echo $OUTPUT->header();
echo $OUTPUT->confirm(
    get_string('clearconfirm', 'local_feedbackinsights'),
    new moodle_url($url, ['confirm' => 1, 'sesskey' => sesskey()]),
    $returnurl
);
echo $OUTPUT->footer();
