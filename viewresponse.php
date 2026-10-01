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
 * viewresponse.php
 *
 * @package   local_feedbackinsights
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_feedbackinsights\source\provider_manager;

$courseid = required_param('courseid', PARAM_INT);
$source = required_param('source', PARAM_ALPHANUMEXT);
$sourceid = required_param('sourceid', PARAM_INT);
$responseid = required_param('responseid', PARAM_INT);

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/feedbackinsights:viewresponses', $context);

$provider = provider_manager::get($source);
$provider->require_access($courseid, $sourceid);
$response = $provider->get_response($courseid, $sourceid, $responseid);

if (!$response) {
    throw new moodle_exception('error:responsenotfound', 'local_feedbackinsights');
}

$PAGE->set_url('/local/feedbackinsights/viewresponse.php', [
    'courseid' => $courseid,
    'source' => $source,
    'sourceid' => $sourceid,
    'responseid' => $responseid,
]);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('originalresponse', 'local_feedbackinsights'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('originalresponse', 'local_feedbackinsights'));

$metadata = [
    get_string('responseid', 'local_feedbackinsights', $response->id),
    userdate($response->timestamp),
];

if ($response->anonymous) {
    $metadata[] = get_string('anonymous', 'local_feedbackinsights');
} else if ($response->userid) {
    $user = core_user::get_user($response->userid, '*', IGNORE_MISSING);
    if ($user) {
        $metadata[] = fullname($user);
    }
}

echo html_writer::tag('p', implode(' · ', array_map('s', $metadata)), ['class' => 'text-muted']);
echo html_writer::div(
    format_text($response->text, FORMAT_HTML, ['context' => $context]),
    'card card-body'
);
echo $OUTPUT->footer();
