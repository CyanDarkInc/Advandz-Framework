<?php
/**
 * CDNJS is one of the most famous free and public web front-end CDN services
 * that host any production version of the most popular JavaScript and CSS
 * libraries.
 *
 * @package Advandz
 * @subpackage Advandz.helpers.cdnjs
 * @copyright Copyright (c) 2016-2017 Advandz, LLC. All Rights Reserved.
 * @license https://opensource.org/licenses/MIT The MIT License (MIT)
 * @author The Advandz Team <team@advandz.com>
 */

namespace Advandz\Helper;

use Exception;
use Advandz\Component\Http;

class Cdnjs extends Http
{
    /**
     * @var array An array containing the loaded libraries
     */
    private $libraries = [];

    /**
     * Load a library.
     *
     * @param  array $libs The libraries to load
     * @return array An array with all the files of the library
     */
    public function loadLibraries($libs = [])
    {
        $loaded_libs = [];

        if (is_array($libs)) {
            foreach ($libs as $key => $value) {
                // Fetch library
                if (is_numeric($key)) {
                    $lib = $value;
                } else {
                    $lib = $key;
                }

                // Get all available library versions
                $library = $this->getLibrary($lib);

                // Search the library if not exists
                if ($library == false) {
                    $search = $this->searchLibrary($lib);

                    if ($search != false) {
                        $library = $this->getLibrary($search[0]->name);
                    }
                }

                // Get library
                if ($library != false) {
                    // Fetch version
                    if (is_numeric($key)) {
                        $version = $library->version;
                    } else {
                        $version = $value;
                    }

                    // Get library version file
                    foreach ($library->assets as $asset) {
                        if ($asset->version == $version) {
                            $loaded_libs[$library->name] = 'https://cdnjs.cloudflare.com/ajax/libs/' . $library->name
                                . '/' . $asset->version . '/' . $library->filename;
                            break;
                        }
                    }

                    // Load the latest version if the provided version is invalid
                    if (empty($loaded_libs[$library->name]) || !@file_get_contents($loaded_libs[$library->name])) {
                        $loaded_libs[$library->name] = 'https://cdnjs.cloudflare.com/ajax/libs/' . $library->name
                            . '/' . $library->version . '/' . $library->filename;
                    }
                }
            }

            // Set loaded libraries in the "libraries" object
            $this->libraries = $loaded_libs;

            return $loaded_libs;
        }
    }

    /**
     * Search a library.
     *
     * @param  string    $lib The library name
     * @throws Exception When the library not exists
     * @return mixed     An array with the results or false if not return results
     */
    public function searchLibrary($lib)
    {
        // Search the library in CDNJS
        if (isset($lib)) {
            $search = $this->server('api.cdnjs.com')
                ->uri('libraries')
                ->useSsl()
                ->method('GET')
                ->execute(['search' => $lib]);
            $search = json_decode($search);

            if (json_last_error() === JSON_ERROR_NONE && !empty($search->results)) {
                return $search->results;
            }

            return false;
        } else {
            throw new Exception('Undefined library name');
        }
    }

    /**
     * Get an specific library.
     *
     * @param  string    $lib The library name
     * @throws Exception When the library not exists
     * @return mixed     An array with the results or false if not return results
     */
    public function getLibrary($lib)
    {
        // Get the library from CDNJS
        if (isset($lib)) {
            $library = $this->server('api.cdnjs.com')
                ->uri('libraries/' . $lib)
                ->useSsl()
                ->method('GET')
                ->execute();
            $library = json_decode($library);

            if (json_last_error() === JSON_ERROR_NONE && isset($library->name)) {
                return $library;
            }

            return false;
        } else {
            throw new Exception('Undefined library name');
        }
    }

    /**
     * Get all the loaded libraries.
     *
     * @return mixed An array containing the loaded libraries
     */
    public function getLibraries()
    {
        return $this->libraries;
    }
}
