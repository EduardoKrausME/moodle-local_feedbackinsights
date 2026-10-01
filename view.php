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
 * view.php
 *
 * @package   local_feedbackinsights
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_feedbackinsights\service\analysis_repository;
use local_feedbackinsights\service\presenter;
use local_feedbackinsights\source\provider_manager;

$courseid = required_param('courseid', PARAM_INT);
$id = required_param('id', PARAM_INT);

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/feedbackinsights:view', $context);

$analysis = analysis_repository::get($id, $courseid);
$provider = provider_manager::get($analysis->source);
$provider->require_access($courseid, (int)$analysis->sourceinstanceid);

$result = json_decode($analysis->resultsjson, true);
if (!is_array($result)) {
    throw new moodle_exception('error:storedanalysis', 'local_feedbackinsights');
}

$PAGE->set_url('/local/feedbackinsights/view.php', ['courseid' => $courseid, 'id' => $id]);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('analysis', 'local_feedbackinsights'));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('analysis', 'local_feedbackinsights') . ' #' . $analysis->id);

$a = (object)[
    'date' => userdate($analysis->timecreated),
    'responses' => (int)$analysis->responsecount,
    'empty' => (int)$analysis->emptycount,
];
echo html_writer::tag('p', get_string('analysiscreated', 'local_feedbackinsights', $a), ['class' => 'text-muted']);

if ((int)$analysis->invalididcount > 0) {
    echo $OUTPUT->notification(
        get_string('invalididsdiscarded', 'local_feedbackinsights', (int)$analysis->invalididcount),
        'notifywarning'
    );
}

echo $OUTPUT->render_from_template(
    'local_feedbackinsights/analysis',
    presenter::result(
        $result,
        $courseid,
        $analysis->source,
        (int)$analysis->sourceinstanceid,
        has_capability('local/feedbackinsights:viewresponses', $context)
    )
);

echo html_writer::div(
    html_writer::link(
        new moodle_url('/local/feedbackinsights/index.php', ['courseid' => $courseid]),
        get_string('backtoanalyses', 'local_feedbackinsights')
    ),
    'mt-3'
);

echo $OUTPUT->footer();
