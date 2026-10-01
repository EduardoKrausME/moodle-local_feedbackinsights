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

namespace local_feedbackinsights\source;

use context_module;
use core_component;
use local_feedbackinsights\model\response_record;
use moodle_exception;
use Throwable;

/**
 * mod_feedback source provider.
 *
 * @package   local_feedbackinsights
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class feedback_provider extends base_provider {
    /**
     * Method get_key.
     *
     * @return string Return value.
     */
    public function get_key(): string {
        return 'feedback';
    }

    /**
     * Method get_name.
     *
     * @return string Return value.
     */
    public function get_name(): string {
        return get_string('source:feedback', 'local_feedbackinsights');
    }

    /**
     * Method is_available.
     *
     * @return bool Return value.
     */
    public function is_available(): bool {
        return core_component::get_plugin_directory('mod', 'feedback') !== null;
    }

    /**
     * Method get_instances.
     *
     * @param int $courseid Parameter courseid.
     * @return array Return value.
     */
    public function get_instances(int $courseid): array {
        global $DB;

        if (!$this->is_available()) {
            return [];
        }

        $instances = [];
        foreach ($DB->get_records('feedback', ['course' => $courseid], 'name ASC', 'id,name') as $feedback) {
            try {
                $cm = $this->get_cm('feedback', (int)$feedback->id, $courseid);
                $context = context_module::instance($cm->id);
                if (has_capability('mod/feedback:viewreports', $context)) {
                    $instances[(int)$feedback->id] = format_string($feedback->name, true, ['context' => $context]);
                }
            } catch (Throwable $e) {
                continue;
            }
        }
        return $instances;
    }

    /**
     * Method get_questions.
     *
     * @param int $courseid Parameter courseid.
     * @param int $instanceid Parameter instanceid.
     * @return array Return value.
     */
    public function get_questions(int $courseid, int $instanceid): array {
        global $DB;

        $this->require_access($courseid, $instanceid);
        $items = $DB->get_records_select(
            'feedback_item',
            'feedback = :feedback AND typ IN (:textarea, :textfield)',
            ['feedback' => $instanceid, 'textarea' => 'textarea', 'textfield' => 'textfield'],
            'position ASC, id ASC',
            'id,name,typ'
        );

        $questions = [];
        foreach ($items as $item) {
            $name = trim(format_string($item->name));
            if ($name === '') {
                $name = get_string('questionnumber', 'local_feedbackinsights', $item->id);
            }
            $questions[(string)$item->id] = $name;
        }
        return $questions;
    }

    /**
     * Method require_access.
     *
     * @param int $courseid Parameter courseid.
     * @param int $instanceid Parameter instanceid.
     * @return void Return value.
     */
    public function require_access(int $courseid, int $instanceid): void {
        $cm = $this->get_cm('feedback', $instanceid, $courseid);
        require_capability('mod/feedback:viewreports', context_module::instance($cm->id));
    }

    /**
     * Method get_responses.
     *
     * @param int $courseid Parameter courseid.
     * @param int $instanceid Parameter instanceid.
     * @param array $questionids Parameter questionids.
     * @param int $datefrom Parameter datefrom.
     * @param int $dateuntil Parameter dateuntil.
     * @return array Return value.
     */
    public function get_responses(
        int $courseid,
        int $instanceid,
        array $questionids,
        int $datefrom,
        int $dateuntil
    ): array {
        global $DB;

        $this->require_access($courseid, $instanceid);
        $feedback = $DB->get_record('feedback', ['id' => $instanceid, 'course' => $courseid], '*', MUST_EXIST);
        $cm = $this->get_cm('feedback', $instanceid, $courseid);
        $context = context_module::instance($cm->id);
        $anonymous = ((int)$feedback->anonymous === 1);
        $groupuserids = $this->get_group_limited_userids($cm, $context);

        if ($anonymous && $groupuserids !== null) {
            throw new moodle_exception('error:anonymousseparategroups', 'local_feedbackinsights');
        }

        $questionids = array_values(array_filter(array_map('intval', $questionids), static fn(int $id): bool => $id > 0));
        if (!$questionids) {
            return [];
        }

        [$questionsql, $questionparams] = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED, 'qid');
        $params = $questionparams + [
                'feedbackid' => $instanceid,
                'datefrom' => $datefrom,
                'dateuntil' => $dateuntil,
            ];
        $groupsql = '';
        if ($groupuserids !== null) {
            if (!$groupuserids) {
                return [];
            }
            [$usersql, $userparams] = $DB->get_in_or_equal($groupuserids, SQL_PARAMS_NAMED, 'uid');
            $groupsql = " AND c.userid {$usersql}";
            $params += $userparams;
        }

        $sql = "SELECT v.id, v.item AS questionid, v.value, c.timemodified, c.userid
                  FROM {feedback_value} v
                  JOIN {feedback_item} i ON i.id = v.item
                  JOIN {feedback_completed} c ON c.id = v.completed
                 WHERE i.feedback = :feedbackid
                   AND v.item {$questionsql}
                   AND c.timemodified >= :datefrom
                   AND c.timemodified <= :dateuntil
                   {$groupsql}
              ORDER BY c.timemodified ASC, v.id ASC";

        $responses = [];
        foreach ($DB->get_records_sql($sql, $params) as $record) {
            $responses[] = new response_record(
                (int)$record->id,
                (string)$record->questionid,
                (string)$record->value,
                (int)$record->timemodified,
                $anonymous ? null : ((int)$record->userid ?: null),
                $anonymous
            );
        }
        return $responses;
    }

    /**
     * Method get_response.
     *
     * @param int $courseid Parameter courseid.
     * @param int $instanceid Parameter instanceid.
     * @param int $responseid Parameter responseid.
     * @return ?response_record Return value.
     */
    public function get_response(int $courseid, int $instanceid, int $responseid): ?response_record {
        global $DB;

        $this->require_access($courseid, $instanceid);
        $feedback = $DB->get_record('feedback', ['id' => $instanceid, 'course' => $courseid], '*', MUST_EXIST);
        $cm = $this->get_cm('feedback', $instanceid, $courseid);
        $context = context_module::instance($cm->id);
        $anonymous = ((int)$feedback->anonymous === 1);
        $groupuserids = $this->get_group_limited_userids($cm, $context);

        if ($anonymous && $groupuserids !== null) {
            throw new moodle_exception('error:anonymousseparategroups', 'local_feedbackinsights');
        }

        $sql = "SELECT v.id, v.item AS questionid, v.value, c.timemodified, c.userid
                  FROM {feedback_value} v
                  JOIN {feedback_item} i ON i.id = v.item
                  JOIN {feedback_completed} c ON c.id = v.completed
                 WHERE v.id = :responseid
                   AND i.feedback = :feedbackid
                   AND i.typ IN (:textarea, :textfield)";
        $record = $DB->get_record_sql($sql, [
            'responseid' => $responseid,
            'feedbackid' => $instanceid,
            'textarea' => 'textarea',
            'textfield' => 'textfield',
        ]);
        if (!$record) {
            return null;
        }
        if ($groupuserids !== null && !in_array((int)$record->userid, $groupuserids, true)) {
            return null;
        }

        return new response_record(
            (int)$record->id,
            (string)$record->questionid,
            (string)$record->value,
            (int)$record->timemodified,
            $anonymous ? null : ((int)$record->userid ?: null),
            $anonymous
        );
    }
}
