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
use Throwable;

/**
 * Assignment online-text source provider.
 *
 * @package   local_feedbackinsights
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class assignment_provider extends base_provider {
    /**
     * Method get_key.
     *
     * @return string Return value.
     */
    public function get_key(): string {
        return 'assignment';
    }

    /**
     * Method get_name.
     *
     * @return string Return value.
     */
    public function get_name(): string {
        return get_string('source:assignment', 'local_feedbackinsights');
    }

    /**
     * Method is_available.
     *
     * @return bool Return value.
     */
    public function is_available(): bool {
        return core_component::get_plugin_directory('mod', 'assign') !== null &&
            core_component::get_plugin_directory('assignsubmission', 'onlinetext') !== null;
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
        foreach ($DB->get_records('assign', ['course' => $courseid], 'name ASC', 'id,name') as $assign) {
            try {
                $enabled = $DB->record_exists('assign_plugin_config', [
                    'assignment' => $assign->id,
                    'plugin' => 'onlinetext',
                    'subtype' => 'assignsubmission',
                    'name' => 'enabled',
                    'value' => '1',
                ]);
                if (!$enabled) {
                    continue;
                }

                $cm = $this->get_cm('assign', (int)$assign->id, $courseid);
                $context = context_module::instance($cm->id);
                if (has_capability('mod/assign:grade', $context)) {
                    $instances[(int)$assign->id] = format_string($assign->name, true, ['context' => $context]);
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
        $this->require_access($courseid, $instanceid);
        return ['onlinetext' => get_string('assignment:onlinetext', 'local_feedbackinsights')];
    }

    /**
     * Method require_access.
     *
     * @param int $courseid Parameter courseid.
     * @param int $instanceid Parameter instanceid.
     * @return void Return value.
     */
    public function require_access(int $courseid, int $instanceid): void {
        $cm = $this->get_cm('assign', $instanceid, $courseid);
        require_capability('mod/assign:grade', context_module::instance($cm->id));
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
        int   $courseid,
        int   $instanceid,
        array $questionids,
        int   $datefrom,
        int   $dateuntil
    ): array {
        global $DB;

        $this->require_access($courseid, $instanceid);
        if (!in_array('onlinetext', $questionids, true)) {
            return [];
        }

        $cm = $this->get_cm('assign', $instanceid, $courseid);
        $context = context_module::instance($cm->id);
        $groupuserids = $this->get_group_limited_userids($cm, $context);
        $params = [
            'assignmentid' => $instanceid,
            'submissionassignmentid' => $instanceid,
            'status' => 'submitted',
            'latest' => 1,
            'datefrom' => $datefrom,
            'dateuntil' => $dateuntil,
        ];
        $groupsql = '';
        if ($groupuserids !== null) {
            if (!$groupuserids) {
                return [];
            }
            [$usersql, $userparams] = $DB->get_in_or_equal($groupuserids, SQL_PARAMS_NAMED, 'uid');
            $groupsql = " AND s.userid {$usersql}";
            $params += $userparams;
        }

        $sql = "SELECT ot.id, ot.onlinetext, s.timemodified, s.userid
                  FROM {assignsubmission_onlinetext} ot
                  JOIN {assign_submission} s ON s.id = ot.submission
                 WHERE ot.assignment = :assignmentid
                   AND s.assignment = :submissionassignmentid
                   AND s.status = :status
                   AND s.latest = :latest
                   AND s.timemodified >= :datefrom
                   AND s.timemodified <= :dateuntil
                   {$groupsql}
              ORDER BY s.timemodified ASC, ot.id ASC";

        $responses = [];
        foreach ($DB->get_records_sql($sql, $params) as $record) {
            $responses[] = new response_record(
                (int)$record->id,
                'onlinetext',
                (string)$record->onlinetext,
                (int)$record->timemodified,
                (int)$record->userid,
                false
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
        $cm = $this->get_cm('assign', $instanceid, $courseid);
        $context = context_module::instance($cm->id);
        $groupuserids = $this->get_group_limited_userids($cm, $context);

        $sql = "SELECT ot.id, ot.onlinetext, s.timemodified, s.userid
                  FROM {assignsubmission_onlinetext} ot
                  JOIN {assign_submission} s ON s.id = ot.submission
                 WHERE ot.id = :responseid
                   AND ot.assignment = :assignmentid
                   AND s.assignment = :submissionassignmentid
                   AND s.status = :status
                   AND s.latest = :latest";
        $record = $DB->get_record_sql($sql, [
            'responseid' => $responseid,
            'assignmentid' => $instanceid,
            'submissionassignmentid' => $instanceid,
            'status' => 'submitted',
            'latest' => 1,
        ]);
        if (!$record) {
            return null;
        }
        if ($groupuserids !== null && !in_array((int)$record->userid, $groupuserids, true)) {
            return null;
        }

        return new response_record(
            (int)$record->id,
            'onlinetext',
            (string)$record->onlinetext,
            (int)$record->timemodified,
            (int)$record->userid,
            false
        );
    }
}
