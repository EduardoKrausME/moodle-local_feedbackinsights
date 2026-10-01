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
use local_feedbackinsights\service\ai_response_parser;
use moodle_exception;

/**
 * Tests strict AI response parsing and ID validation.
 *
 * @package local_feedbackinsights
 * @covers \local_feedbackinsights\service\ai_response_parser
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class ai_response_parser_test extends advanced_testcase {
    /**
     * Method test_malformed_response_is_rejected.
     *
     * @return void Return value.
     */
    public function test_malformed_response_is_rejected(): void {
        $this->resetAfterTest();

        $this->expectException(moodle_exception::class);
        ai_response_parser::parse('not-json');
    }

    /**
     * Method test_invalid_ids_are_discarded.
     *
     * @return void Return value.
     */
    public function test_invalid_ids_are_discarded(): void {
        $this->resetAfterTest();

        $payload = [
            'themes' => [[
                'key' => 'T1',
                'title' => 'Theme',
                'description' => 'Description',
                'response_ids' => ['R000001', 'R999999'],
                'example_ids' => ['R999999', 'R000001'],
                'confidence' => 0.8,
            ]],
            'suggestions' => [[
                'title' => 'Suggestion',
                'description' => 'Description',
                'response_ids' => ['R000002', 'R999999'],
            ]],
            'divergences' => [],
        ];

        $validated = ai_response_parser::validate_ids($payload, ['R000001', 'R000002']);
        $this->assertSame(['R000001'], $validated['payload']['themes'][0]['response_ids']);
        $this->assertSame(['R000001'], $validated['payload']['themes'][0]['example_ids']);
        $this->assertSame(['R000002'], $validated['payload']['suggestions'][0]['response_ids']);
        $this->assertSame(3, $validated['invalidids']);
    }
}
