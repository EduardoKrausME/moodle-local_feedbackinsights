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

use moodle_exception;
use Throwable;

/**
 * Source provider registry.
 *
 * @package   local_feedbackinsights
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider_manager {
    /**
     * Return all available providers, including providers registered by plugins.
     *
     * External callback contract:
     * <component>_feedbackinsights_source_providers(): array
     *
     * @return array<string, provider>
     */
    public static function get_providers(): array {
        $providers = [
            new feedback_provider(),
            new assignment_provider(),
        ];

        $callbacks = get_plugins_with_function('feedbackinsights_source_providers', 'lib.php');
        foreach ($callbacks as $pluginfunctions) {
            foreach ($pluginfunctions as $callback) {
                try {
                    $extra = $callback();
                    if (!is_array($extra)) {
                        continue;
                    }
                    foreach ($extra as $candidate) {
                        if (is_string($candidate) && class_exists($candidate)) {
                            $candidate = new $candidate();
                        }
                        if ($candidate instanceof provider) {
                            $providers[] = $candidate;
                        }
                    }
                } catch (Throwable $e) {
                    debugging('Feedback insights source provider registration failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
                }
            }
        }

        $indexed = [];
        foreach ($providers as $provider) {
            if ($provider->is_available()) {
                $indexed[$provider->get_key()] = $provider;
            }
        }
        return $indexed;
    }

    /**
     * Method get.
     *
     * @param string $key Parameter key.
     * @return provider Return value.
     */
    public static function get(string $key): provider {
        $providers = self::get_providers();
        if (!isset($providers[$key])) {
            throw new moodle_exception('error:invalidsource', 'local_feedbackinsights');
        }
        return $providers[$key];
    }

    /**
     * Method get_source_options.
     *
     * @param int $courseid Parameter courseid.
     * @return array Return value.
     */
    public static function get_source_options(int $courseid): array {
        $options = [];
        foreach (self::get_providers() as $key => $provider) {
            foreach ($provider->get_instances($courseid) as $id => $name) {
                $options[$key . '_' . $id] = $provider->get_name() . ': ' . $name;
            }
        }
        return $options;
    }

    /**
     * Method parse_source_value.
     *
     * @param string $value Parameter value.
     * @return array Return value.
     */
    public static function parse_source_value(string $value): array {
        if (!preg_match('/^([a-z][a-z0-9_]*)_(\d+)$/', $value, $matches)) {
            throw new moodle_exception('error:invalidsource', 'local_feedbackinsights');
        }
        return [$matches[1], (int)$matches[2]];
    }
}
