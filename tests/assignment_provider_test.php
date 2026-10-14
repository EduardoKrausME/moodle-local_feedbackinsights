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
use local_feedbackinsights\source\assignment_provider;
use local_feedbackinsights\service\analysis_repository;

/**
 * Regression tests for blind-marked Assignment response identities.
 *
 * @coversNothing
 * @package local_feedbackinsights
 */
final class assignment_provider_test extends advanced_testcase {
    /**
     * Hidden respondent identities must never be returned or persisted.
     */
    public function test_blind_marking_hides_identities_until_revealed(): void {
        global $DB;

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $teacher = $generator->create_user();
        $student = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');
        $generator->enrol_user($student->id, $course->id, 'student');

        $assign = $generator->create_module('assign', ['course' => $course->id]);
        $DB->set_field('assign', 'blindmarking', 1, ['id' => $assign->id]);
        $DB->set_field('assign', 'revealidentities', 0, ['id' => $assign->id]);

        $now = time();
        $submissionid = $DB->insert_record('assign_submission', (object)[
            'assignment' => $assign->id,
            'userid' => $student->id,
            'groupid' => 0,
            'attemptnumber' => 0,
            'latest' => 1,
            'status' => 'submitted',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $responseid = $DB->insert_record('assignsubmission_onlinetext', (object)[
            'assignment' => $assign->id,
            'submission' => $submissionid,
            'onlinetext' => 'A submission under blind marking',
            'onlineformat' => FORMAT_HTML,
        ]);

        $this->setUser($teacher);
        $provider = new assignment_provider();
        $this->assertFalse($provider->can_view_respondent_identities($course->id, $assign->id));

        $response = $provider->get_response($course->id, $assign->id, $responseid);
        $this->assertNotNull($response);
        $this->assertTrue($response->anonymous);
        $this->assertNull($response->userid);

        $responses = $provider->get_responses($course->id, $assign->id, ['onlinetext'], 0, $now + 60);
        $this->assertCount(1, $responses);
        $this->assertNull($responses[0]->userid);
        $this->assertTrue($responses[0]->anonymous);

        // When identities have been revealed, authorised graders may see them.
        $DB->set_field('assign', 'revealidentities', 1, ['id' => $assign->id]);
        $this->assertTrue($provider->can_view_respondent_identities($course->id, $assign->id));
        $visible = $provider->get_response($course->id, $assign->id, $responseid);
        $this->assertSame((int)$student->id, $visible->userid);
        $this->assertFalse($visible->anonymous);

        // Re-hide identities between extraction and storage: the repository
        // must recheck current permissions and discard the stale user ID.
        $DB->set_field('assign', 'revealidentities', 0, ['id' => $assign->id]);
        $analysisid = analysis_repository::save([
            'courseid' => $course->id,
            'source' => 'assignment',
            'sourceinstanceid' => $assign->id,
            'questionids' => ['onlinetext'],
            'datefrom' => 0,
            'dateuntil' => $now + 60,
            'emptycount' => 0,
            'invalididcount' => 0,
        ], ['themes' => [], 'suggestions' => [], 'divergences' => []], [$visible]);

        $member = $DB->get_record('local_feedbackinsights_mem', ['analysisid' => $analysisid], '*', MUST_EXIST);
        $this->assertNull($member->userid);
    }
}
