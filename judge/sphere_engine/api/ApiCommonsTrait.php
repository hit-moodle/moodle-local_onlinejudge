<?php
// This file is part of Moodle - https://moodle.org
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

/**
 * ApiCommonsTrait
 *
 * PHP version 5
 *
 * Common function for all Sphere Engine modules.
 *
 */

namespace SphereEngine\Api;

trait ApiCommonsTrait {
    /**
     * extra POST data only for the next request
     *
     * @param array $data
     */
    public function addExtraPost($data) {
        $this->apiClient->addExtraPost($data);
    }

    /**
     * Create endpoint link
     *
     * @param string $module Sphere Engine module (problems, compilers)
     * @param string $endpoint Sphere Engine endpoint
     * @param boolean $strictEndpoint strict endpoint (false if you need use another endpoint than sphere-engine.com)
     * @throws \RuntimeException
     */
    protected function createEndpointLink($module, $endpoint, $strictEndpoint = true) {

        if (strpos($endpoint, '.') === false) {
            if ($strictEndpoint && preg_match('/^[a-z0-9]{8,16}$/', $endpoint) == false) {
                throw new \RuntimeException('A valid key must consist of at least 8 to 16 characters consisting of lowercase letters and numbers');
            }
            return $endpoint . '.' . $this->module . '.sphere-engine.com/api/' . $this->version;
        } else {
            if ($strictEndpoint && preg_match('/^[a-z0-9]{8,16}(?:\.api)?\.' . $module . '\.sphere\-engine\.com$/', $endpoint) == false) {
                throw new \RuntimeException('Correct endpoint should be in format {customerID}.api.' . $module . '.sphere-engine.com');
            }
            return $endpoint . '/api/' . $this->version;
        }
    }

}
