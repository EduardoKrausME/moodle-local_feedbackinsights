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

use moodle_exception;

/**
 * Strict parser for bridge output.
 *
 * @package   local_feedbackinsights
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class ai_response_parser {
    /**
     * Method parse.
     *
     * @param string $text Parameter text.
     * @return array Return value.
     */
    public static function parse(string $text): array {
        $text = trim($text);
        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/is', $text, $matches)) {
            $text = trim($matches[1]);
        }
        $decoded = json_decode($text, true);
        if (!is_array($decoded) || !isset($decoded['themes']) || !is_array($decoded['themes'])) {
            throw new moodle_exception('error:malformedairesponse', 'local_feedbackinsights');
        }
        return $decoded;
    }

    /**
     * Method validate_ids.
     *
     * @param array $payload Parameter payload.
     * @param array $validids Parameter validids.
     * @return array Return value.
     */
    public static function validate_ids(array $payload, array $validids): array {
        $validmap = array_fill_keys($validids, true);
        $invalid = 0;
        $themes = [];

        foreach ($payload['themes'] as $index => $theme) {
            if (!is_array($theme)) {
                continue;
            }
            $responseids = self::filter_ids($theme['response_ids'] ?? [], $validmap, $invalid);
            if (!$responseids) {
                continue;
            }
            $exampleids = self::filter_ids($theme['example_ids'] ?? [], $validmap, $invalid);
            $exampleids = array_values(array_intersect($exampleids, $responseids));
            $confidence = isset($theme['confidence']) && is_numeric($theme['confidence'])
                ? max(0.0, min(1.0, (float)$theme['confidence'])) : 0.5;
            $themes[] = [
                'key' => clean_param((string)($theme['key'] ?? 'T' . ($index + 1)), PARAM_ALPHANUMEXT),
                'title' => clean_param((string)($theme['title'] ?? ''), PARAM_TEXT),
                'description' => clean_param((string)($theme['description'] ?? ''), PARAM_TEXT),
                'response_ids' => $responseids,
                'example_ids' => array_slice($exampleids, 0, 3),
                'confidence' => $confidence,
            ];
        }

        $payload['themes'] = $themes;
        $payload['suggestions'] = self::validate_insights($payload['suggestions'] ?? [], $validmap, $invalid);
        $payload['divergences'] = self::validate_insights($payload['divergences'] ?? [], $validmap, $invalid);
        return ['payload' => $payload, 'invalidids' => $invalid];
    }

    /**
     * Method validate_insights.
     *
     * @param mixed $insights Parameter insights.
     * @param array $validmap Parameter validmap.
     * @param int $invalid Parameter invalid.
     * @return array Return value.
     */
    private static function validate_insights(mixed $insights, array $validmap, int &$invalid): array {
        if (!is_array($insights)) {
            return [];
        }
        $result = [];
        foreach ($insights as $insight) {
            if (!is_array($insight)) {
                continue;
            }
            $ids = self::filter_ids($insight['response_ids'] ?? [], $validmap, $invalid);
            if (!$ids) {
                continue;
            }
            $result[] = [
                'title' => clean_param((string)($insight['title'] ?? ''), PARAM_TEXT),
                'description' => clean_param((string)($insight['description'] ?? ''), PARAM_TEXT),
                'response_ids' => $ids,
            ];
        }
        return $result;
    }

    /**
     * Method filter_ids.
     *
     * @param mixed $ids Parameter ids.
     * @param array $validmap Parameter validmap.
     * @param int $invalid Parameter invalid.
     * @return array Return value.
     */
    private static function filter_ids(mixed $ids, array $validmap, int &$invalid): array {
        if (!is_array($ids)) {
            return [];
        }
        $result = [];
        foreach ($ids as $id) {
            $id = (string)$id;
            if (!isset($validmap[$id])) {
                $invalid++;
                continue;
            }
            $result[$id] = $id;
        }
        return array_values($result);
    }
}
