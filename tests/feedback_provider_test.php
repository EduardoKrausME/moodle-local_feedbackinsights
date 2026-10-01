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
use local_feedbackinsights\source\feedback_provider;

/**
 * Tests for the mod_feedback source provider.
 *
 * @package local_feedbackinsights
 * @covers \local_feedbackinsights\source\feedback_provider
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class feedback_provider_test extends advanced_testcase {
    /**
     * Method test_anonymous_feedback_does_not_expose_userid.
     *
     * @return void Return value.
     */
    public function test_anonymous_feedback_does_not_expose_userid(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        $feedback = $this->getDataGenerator()->create_module('feedback', [
            'course' => $course->id,
            'anonymous' => 1,
        ]);
        $cm = get_coursemodule_from_instance('feedback', $feedback->id, $course->id, false, MUST_EXIST);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_feedback');
        $item = $generator->create_question([
            'cmid' => $cm->id,
            'questiontype' => 'textarea',
            'name' => 'Open comment',
        ]);
        $generator->create_response([
            'cmid' => $cm->id,
            'userid' => $student->id,
            'Open comment' => 'The practical examples were useful.',
        ]);

        $this->setUser($teacher);
        $provider = new feedback_provider();
        $responses = $provider->get_responses(
            $course->id,
            $feedback->id,
            [(string)$item->id],
            0,
            time() + DAYSECS
        );

        $this->assertCount(1, $responses);
        $this->assertTrue($responses[0]->anonymous);
        $this->assertNull($responses[0]->userid);
        $this->assertSame('The practical examples were useful.', $responses[0]->text);
    }
}
