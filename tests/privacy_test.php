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

namespace local_feedbackinsights;

use advanced_testcase;
use context_course;
use core_privacy\local\request\approved_contextlist;
use local_feedbackinsights\model\response_record;
use local_feedbackinsights\privacy\provider as privacy_provider;
use local_feedbackinsights\service\analysis_repository;

/**
 * Privacy and anonymous-response persistence tests.
 *
 * @package local_feedbackinsights
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class privacy_test extends advanced_testcase {
    /**
     * Method test_anonymous_feedback_never_persists_userid.
     *
     * @return void Return value.
     */
    public function test_anonymous_feedback_never_persists_userid(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();
        $this->setUser($teacher);

        $result = $this->sample_result(100);
        $analysisid = analysis_repository::save([
            'courseid' => $course->id,
            'source' => 'feedback',
            'sourceinstanceid' => 7,
            'questionids' => ['3'],
            'datefrom' => 1,
            'dateuntil' => 2,
            'emptycount' => 0,
            'invalididcount' => 0,
        ], $result, [
            new response_record(100, '3', 'Anonymous response', 2, $student->id, true),
        ]);

        $member = $DB->get_record('local_feedbackinsights_mem', ['analysisid' => $analysisid], '*', MUST_EXIST);
        $this->assertNull($member->userid);
    }

    /**
     * Method test_privacy_deletion_removes_analysis_derived_from_user_response.
     *
     * @return void Return value.
     */
    public function test_privacy_deletion_removes_analysis_derived_from_user_response(): void {
        global $DB;

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();
        $this->setUser($teacher);

        $result = $this->sample_result(101);
        $analysisid = analysis_repository::save([
            'courseid' => $course->id,
            'source' => 'assignment',
            'sourceinstanceid' => 8,
            'questionids' => ['onlinetext'],
            'datefrom' => 1,
            'dateuntil' => 2,
            'emptycount' => 0,
            'invalididcount' => 0,
        ], $result, [
            new response_record(101, 'onlinetext', 'Student response', 2, $student->id, false),
        ]);

        $context = context_course::instance($course->id);
        $approved = new approved_contextlist($student, 'local_feedbackinsights', [$context->id]);
        privacy_provider::delete_data_for_user($approved);

        $this->assertFalse($DB->record_exists('local_feedbackinsights_an', ['id' => $analysisid]));
        $this->assertFalse($DB->record_exists('local_feedbackinsights_mem', ['analysisid' => $analysisid]));
    }

    /**
     * Method sample_result.
     *
     * @param int $responseid Parameter responseid.
     * @return array Return value.
     */
    private function sample_result(int $responseid): array {
        return [
            'themes' => [[
                'key' => 'T1',
                'title' => 'Theme',
                'description' => 'Description',
                'count' => 1,
                'percentage' => 100.0,
                'confidence' => 0.9,
                'source_response_ids' => [$responseid],
                'source_example_ids' => [$responseid],
                'trend' => [],
            ]],
            'suggestions' => [],
            'divergences' => [],
        ];
    }
}
