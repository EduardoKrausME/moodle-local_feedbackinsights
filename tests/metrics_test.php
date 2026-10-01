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
use local_feedbackinsights\model\response_record;
use local_feedbackinsights\service\metrics;

/**
 * Deterministic metric tests.
 *
 * @package local_feedbackinsights
 * @covers \local_feedbackinsights\service\metrics
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class metrics_test extends advanced_testcase {
    /**
     * Method test_counts_are_calculated_from_membership_ids.
     *
     * @return void Return value.
     */
    public function test_counts_are_calculated_from_membership_ids(): void {
        $this->resetAfterTest();

        $start = strtotime('2026-09-01 00:00:00');
        $end = strtotime('2026-09-30 23:59:59');
        $map = [
            'R000001' => new response_record(10, '1', 'A', $start + DAYSECS, 2),
            'R000002' => new response_record(11, '1', 'B', $start + (10 * DAYSECS), 3),
            'R000003' => new response_record(12, '1', 'C', $start + (20 * DAYSECS), 4),
        ];
        $payload = [
            'themes' => [[
                'key' => 'T1',
                'title' => 'Theme',
                'description' => 'Description',
                'response_ids' => ['R000001', 'R000003'],
                'example_ids' => ['R000001'],
                'confidence' => 0.9,
            ]],
            'suggestions' => [],
            'divergences' => [],
        ];

        $result = metrics::enrich($payload, $map, $start, $end, 2);
        $theme = $result['themes'][0];

        $this->assertSame(2, $theme['count']);
        $this->assertSame(66.7, $theme['percentage']);
        $this->assertSame([10, 12], $theme['source_response_ids']);
        $this->assertSame([10], $theme['source_example_ids']);
        $this->assertCount(4, $theme['trend']);
        $this->assertSame(2, array_sum(array_column($theme['trend'], 'count')));
    }
}
