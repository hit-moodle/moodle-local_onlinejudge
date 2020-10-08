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

namespace SphereEngine\Api\Mock;

use SphereEngine\Api\Model\HttpApiResponse;

trait ApiClientTrait {
    private function isAccessTokenCorrect() {
        return $this->accessToken == "correctAccessToken";
    }

    /**
     * Returns value under key in associative array
     * @param array $array associative array
     * @param string $key key to be looked in array
     * @param boolean $null_if_not_exists function returns null if value doesn't exists, otherwise exception is thrown
     * @throws \Exception if $null_if_not_exists is false and the value doesn't exist
     * @return mixed
     */
    private function getParam($array, $key, $null_if_not_exists = false) {
        if (isset($array[$key])) {
            return $array[$key];
        } else {
            if ($null_if_not_exists) {
                return null;
            } else {
                throw new \Exception('Lack of ' . $key . ' parameter');
            }
        }
    }

    /**
     * Gets data from JSON mock file.
     * @param   string $routingJsonPath path to routing json
     * @throws \Exception on nonexisting JSON file
     * @throws \Exception on nonexisting data in JSON
     * @return \SphereEngine\Api\Model\HttpApiResponse
     */
    private function getMockData($routingJsonPath) {
        $version = isset($this->version) ? $this->version : '';
        $mockDataJsonFile = './client-commons/mockData' . $version . '.json';
        if (file_exists($mockDataJsonFile)) {
            $dataJsonPath = $this->getDataPath($routingJsonPath);
            $mockData = json_decode(file_get_contents($mockDataJsonFile), true);
            $pathArray = explode('/', $dataJsonPath);

            foreach ($pathArray as $p) {
                if (isset($mockData[$p])) {
                    $mockData = $mockData[$p];
                } else {
                    throw new \Exception('There is no ' . $dataJsonPath . ' path in ' . $mockDataJsonFile . ' file');
                }
            }

            $httpCode = (isset($mockData['httpCode'])) ? $mockData['httpCode'] : 0;
            $httpBody = (isset($mockData['httpBody'])) ? $mockData['httpBody'] : '';
            $connErrno = (isset($mockData['connErrno'])) ? $mockData['connErrno'] : 0;
            $connError = (isset($mockData['connError'])) ? $mockData['connError'] : null;

            if (is_array($httpBody)) {
                $httpBody = json_encode($httpBody);
            }

            return new HttpApiResponse($httpCode, 'application/json', $httpBody, $connError);
        } else {
            throw new \Exception('There is no ' . $mockDataJsonFile . ' file. Please pull data from client-commons submodule.');
        }
    }

    /**
     * Gets json path to data from json path from routing
     * @param   string $routingJsonPath path to routing json
     * @throws \Exception on nonexisting JSON file
     * @throws \Exception on nonexisting data in JSON
     * @return string
     */
    private function getDataPath($routingJsonPath) {
        $version = isset($this->version) ? $this->version : '';
        $mockRoutingJsonFile = './client-commons/mockRouting' . $version . '.json';
        if (file_exists($mockRoutingJsonFile)) {
            $mockRouting = json_decode(file_get_contents($mockRoutingJsonFile), true);
            $pathArray = explode('/', $routingJsonPath);

            foreach ($pathArray as $p) {
                if (isset($mockRouting[$p])) {
                    $mockRouting = $mockRouting[$p];
                } else {
                    throw new \Exception('There is no ' . $routingJsonPath . ' path in ' . $mockRoutingJsonFile . ' file');
                }
            }

            return $mockRouting;
        } else {
            throw new \Exception('There is no ' . $mockRoutingJsonFile . ' file. Please pull data from client-commons submodule.');
        }
    }
}