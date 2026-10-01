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

namespace local_feedbackinsights\service;

use local_feedbackinsights\model\response_record;

/**
 * Deterministic metrics calculated after AI grouping.
 *
 * @package   local_feedbackinsights
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class metrics {
    /**
     * Method enrich.
     *
     * @param array $payload Parameter payload.
     * @param array $pseudomap Parameter pseudomap.
     * @param int $datefrom Parameter datefrom.
     * @param int $dateuntil Parameter dateuntil.
     * @param int $mintrendresponses Parameter mintrendresponses.
     * @return array Return value.
     */
    public static function enrich(
        array $payload,
        array $pseudomap,
        int $datefrom,
        int $dateuntil,
        int $mintrendresponses
    ): array {
        $total = count($pseudomap);
        foreach ($payload['themes'] as &$theme) {
            $pseudoids = $theme['response_ids'];
            $theme['count'] = count($pseudoids);
            $theme['percentage'] = $total > 0 ? round(($theme['count'] / $total) * 100, 1) : 0.0;
            $theme['source_response_ids'] = self::source_ids($theme['response_ids'], $pseudomap);
            $theme['source_example_ids'] = self::source_ids($theme['example_ids'], $pseudomap);
            unset($theme['response_ids'], $theme['example_ids']);
            $theme['trend'] = self::trend(
                $pseudoids,
                $pseudomap,
                $datefrom,
                $dateuntil,
                $total,
                $mintrendresponses
            );
        }
        unset($theme);

        foreach (['suggestions', 'divergences'] as $section) {
            foreach ($payload[$section] as &$insight) {
                $insight['count'] = count($insight['response_ids']);
                $insight['source_response_ids'] = self::source_ids($insight['response_ids'], $pseudomap);
                unset($insight['response_ids']);
            }
            unset($insight);
        }
        return $payload;
    }

    /**
     * Method source_ids.
     *
     * @param array $ids Parameter ids.
     * @param array $pseudomap Parameter pseudomap.
     * @return array Return value.
     */
    private static function source_ids(array $ids, array $pseudomap): array {
        $result = [];
        foreach ($ids as $id) {
            if (isset($pseudomap[$id])) {
                $result[] = $pseudomap[$id]->id;
            }
        }
        return array_values(array_unique($result));
    }

    /**
     * Method trend.
     *
     * @param array $pseudoids Parameter pseudoids.
     * @param array $pseudomap Parameter pseudomap.
     * @param int $datefrom Parameter datefrom.
     * @param int $dateuntil Parameter dateuntil.
     * @param int $total Parameter total.
     * @param int $mintrendresponses Parameter mintrendresponses.
     * @return array Return value.
     */
    private static function trend(
        array $pseudoids,
        array $pseudomap,
        int $datefrom,
        int $dateuntil,
        int $total,
        int $mintrendresponses
    ): array {
        $span = $dateuntil - $datefrom;
        if ($total < $mintrendresponses || $span < (2 * DAYSECS)) {
            return [];
        }

        $idmap = array_fill_keys($pseudoids, true);
        $bucketspan = max(1, (int)ceil(($span + 1) / 4));
        $buckets = [];
        for ($i = 0; $i < 4; $i++) {
            $start = $datefrom + ($i * $bucketspan);
            $end = min($dateuntil, $start + $bucketspan - 1);
            $buckets[$i] = [
                'label' => userdate($start, get_string('strftimedateshort', 'langconfig')),
                'count' => 0,
                'start' => $start,
                'end' => $end,
            ];
        }

        foreach ($pseudomap as $pseudoid => $response) {
            if (!isset($idmap[$pseudoid])) {
                continue;
            }
            $index = min(3, max(0, intdiv(max(0, $response->timestamp - $datefrom), $bucketspan)));
            $buckets[$index]['count']++;
        }
        return array_values($buckets);
    }
}
