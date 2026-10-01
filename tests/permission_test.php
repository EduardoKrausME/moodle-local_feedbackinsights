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

/**
 * Plugin capability tests.
 *
 * @package local_feedbackinsights
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class permission_test extends advanced_testcase {
    /**
     * Method test_student_cannot_use_teacher_dashboard.
     *
     * @return void Return value.
     */
    public function test_student_cannot_use_teacher_dashboard(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        $context = context_course::instance($course->id);

        $this->setUser($student);
        $this->assertFalse(has_capability('local/feedbackinsights:view', $context));
        $this->assertFalse(has_capability('local/feedbackinsights:analyse', $context));

        $this->setUser($teacher);
        $this->assertTrue(has_capability('local/feedbackinsights:view', $context));
        $this->assertTrue(has_capability('local/feedbackinsights:analyse', $context));
    }
}
