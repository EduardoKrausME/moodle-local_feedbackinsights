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
 * index.php
 *
 * @package   local_feedbackinsights
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

use local_feedbackinsights\form\analysis_form;
use local_feedbackinsights\form\source_form;
use local_feedbackinsights\service\analysis_repository;
use local_feedbackinsights\service\analysis_service;
use local_feedbackinsights\service\presenter;
use local_feedbackinsights\source\provider_manager;

$courseid = required_param('courseid', PARAM_INT);
$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('local/feedbackinsights:view', $context);

$PAGE->set_url('/local/feedbackinsights/index.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('pluginname', 'local_feedbackinsights'));
$PAGE->set_heading(format_string($course->fullname));

$source = optional_param('source', '', PARAM_ALPHANUMEXT);
$sourceid = optional_param('sourceid', 0, PARAM_INT);
$sourcevalue = optional_param('sourcevalue', '', PARAM_ALPHANUMEXT);

if ($sourcevalue !== '') {
    [$source, $sourceid] = provider_manager::parse_source_value($sourcevalue);
}

$result = null;
$analysisid = 0;
$provider = null;
$runmetadata = null;
$sources = [];

if ($source !== '' && $sourceid > 0) {
    $provider = provider_manager::get($source);
    $provider->require_access($courseid, $sourceid);
    $questions = $provider->get_questions($courseid, $sourceid);

    if (!$questions) {
        throw new moodle_exception('error:notextquestions', 'local_feedbackinsights');
    }

    $form = new analysis_form(null, [
        'courseid' => $courseid,
        'source' => $source,
        'sourceid' => $sourceid,
        'questions' => $questions,
    ]);

    if ($form->is_cancelled()) {
        redirect(new moodle_url('/local/feedbackinsights/index.php', ['courseid' => $courseid]));
    }

    if ($data = $form->get_data()) {
        require_capability('local/feedbackinsights:analyse', $context);

        $questionids = array_values(array_map('strval', (array)$data->questionids));
        foreach ($questionids as $questionid) {
            if (!array_key_exists($questionid, $questions)) {
                throw new moodle_exception('error:invalidquestion', 'local_feedbackinsights');
            }
        }

        $datefrom = (int)$data->datefrom;
        $dateuntil = (int)$data->dateuntil + DAYSECS - 1;
        $service = new analysis_service();
        $run = $service->run(
            $provider,
            $courseid,
            $sourceid,
            $questionids,
            $datefrom,
            $dateuntil
        );
        $result = $run['result'];
        $runmetadata = $run;

        if ((bool)get_config('local_feedbackinsights', 'persistanalyses')) {
            $analysisid = analysis_repository::save([
                'courseid' => $courseid,
                'source' => $source,
                'sourceinstanceid' => $sourceid,
                'questionids' => $questionids,
                'datefrom' => $datefrom,
                'dateuntil' => $dateuntil,
                'emptycount' => $run['emptycount'],
                'invalididcount' => $run['invalididcount'],
            ], $result, $run['responses']);
        }
    }
} else {
    $sources = provider_manager::get_source_options($courseid);
    $form = new source_form(null, ['sources' => $sources, 'courseid' => $courseid]);

    if ($data = $form->get_data()) {
        [$selectedsource, $selectedid] = provider_manager::parse_source_value($data->sourcevalue);
        redirect(new moodle_url('/local/feedbackinsights/index.php', [
            'courseid' => $courseid,
            'source' => $selectedsource,
            'sourceid' => $selectedid,
        ]));
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_feedbackinsights'));
echo html_writer::tag('p', get_string('intro', 'local_feedbackinsights'), ['class' => 'text-muted']);

if ($source === '' || $sourceid <= 0) {
    if (!$sources) {
        echo $OUTPUT->notification(get_string('nosources', 'local_feedbackinsights'), 'notifyinfo');
    } else {
        $form->display();
    }
} else {
    echo html_writer::div(
        html_writer::link(
            new moodle_url('/local/feedbackinsights/index.php', ['courseid' => $courseid]),
            get_string('changesource', 'local_feedbackinsights')
        ),
        'mb-3'
    );
    $form->display();
}

if ($result !== null && $provider !== null) {
    if ($runmetadata && $runmetadata['emptycount'] > 0) {
        echo $OUTPUT->notification(
            get_string('emptyresponsesremoved', 'local_feedbackinsights', $runmetadata['emptycount']),
            'notifyinfo'
        );
    }
    if ($runmetadata && $runmetadata['invalididcount'] > 0) {
        echo $OUTPUT->notification(
            get_string('invalididsdiscarded', 'local_feedbackinsights', $runmetadata['invalididcount']),
            'notifywarning'
        );
    }

    $templatedata = presenter::result(
        $result,
        $courseid,
        $source,
        $sourceid,
        has_capability('local/feedbackinsights:viewresponses', $context)
    );
    echo $OUTPUT->render_from_template('local_feedbackinsights/analysis', $templatedata);

    if ($analysisid) {
        echo $OUTPUT->notification(
            get_string('analysissaved', 'local_feedbackinsights', $analysisid),
            'notifysuccess'
        );
    }
}

$history = [];
foreach (analysis_repository::list_for_course($courseid) as $record) {
    try {
        $historyprovider = provider_manager::get($record->source);
        $historyprovider->require_access($courseid, (int)$record->sourceinstanceid);
        $history[] = $record;
    } catch (Throwable $e) {
        continue;
    }
}

if ($history) {
    echo $OUTPUT->heading(get_string('recentanalyses', 'local_feedbackinsights'), 3);
    $table = new html_table();
    $table->head = [
        get_string('date'),
        get_string('source', 'local_feedbackinsights'),
        get_string('responses', 'local_feedbackinsights'),
        get_string('actions'),
    ];

    foreach ($history as $record) {
        $url = new moodle_url('/local/feedbackinsights/view.php', [
            'courseid' => $courseid,
            'id' => $record->id,
        ]);
        $table->data[] = [
            userdate($record->timecreated),
            s($record->source),
            (int)$record->responsecount,
            html_writer::link($url, get_string('view')),
        ];
    }
    echo html_writer::table($table);
}

if (has_capability('local/feedbackinsights:deleteanalyses', $context) && $history) {
    $clearurl = new moodle_url('/local/feedbackinsights/clear.php', ['courseid' => $courseid]);
    echo $OUTPUT->single_button(
        $clearurl,
        get_string('clearcourseanalyses', 'local_feedbackinsights'),
        'post'
    );
}

echo $OUTPUT->footer();
