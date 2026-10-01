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

use core_text;
use local_feedbackinsights\source\provider_manager;
use moodle_url;

/**
 * Prepare safe arrays for Mustache templates.
 *
 * @package   local_feedbackinsights
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class presenter {
    /**
     * Method result.
     *
     * @param array $result Parameter result.
     * @param int $courseid Parameter courseid.
     * @param string $source Parameter source.
     * @param int $sourceid Parameter sourceid.
     * @param bool $canviewresponses Parameter canviewresponses.
     * @return array Return value.
     */
    public static function result(
        array $result,
        int $courseid,
        string $source,
        int $sourceid,
        bool $canviewresponses
    ): array {
        $themes = [];
        $provider = $canviewresponses ? provider_manager::get($source) : null;

        foreach ($result['themes'] as $theme) {
            $examples = [];
            foreach ($theme['source_example_ids'] as $responseid) {
                if ($canviewresponses && $provider) {
                    $response = $provider->get_response($courseid, $sourceid, (int)$responseid);
                    if (!$response) {
                        continue;
                    }
                    $excerpt = text_normalizer::normalize($response->text);
                    if (core_text::strlen($excerpt) > 220) {
                        $excerpt = core_text::substr($excerpt, 0, 220) . '…';
                    }
                    $examples[] = [
                        'label' => get_string('responseid', 'local_feedbackinsights', $responseid),
                        'text' => $excerpt,
                        'url' => (new moodle_url('/local/feedbackinsights/viewresponse.php', [
                            'courseid' => $courseid,
                            'source' => $source,
                            'sourceid' => $sourceid,
                            'responseid' => $responseid,
                        ]))->out(false),
                    ];
                }
            }

            $trend = [];
            foreach ($theme['trend'] ?? [] as $bucket) {
                $trend[] = [
                    'label' => $bucket['label'],
                    'count' => $bucket['count'],
                ];
            }

            $themes[] = [
                'title' => $theme['title'],
                'description' => $theme['description'],
                'count' => $theme['count'],
                'percentage' => $theme['percentage'],
                'confidence' => round($theme['confidence'] * 100),
                'examples' => $examples,
                'hasexamples' => !empty($examples),
                'trend' => $trend,
                'hastrend' => !empty($trend),
            ];
        }

        return [
            'themes' => $themes,
            'hasthemes' => !empty($themes),
            'suggestions' => array_map(static fn(array $item): array => [
                'title' => $item['title'],
                'description' => $item['description'],
                'count' => $item['count'],
            ], $result['suggestions'] ?? []),
            'hassuggestions' => !empty($result['suggestions']),
            'divergences' => array_map(static fn(array $item): array => [
                'title' => $item['title'],
                'description' => $item['description'],
                'count' => $item['count'],
            ], $result['divergences'] ?? []),
            'hasdivergences' => !empty($result['divergences']),
        ];
    }
}
