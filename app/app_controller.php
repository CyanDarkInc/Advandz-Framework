<?php
/**
 * The parent controller for the application.
 *
 * @package Advandz
 * @subpackage Advandz.app
 * @copyright Copyright (c) 2012-2017 CyanDark, Inc. All Rights Reserved.
 * @license https://opensource.org/licenses/MIT The MIT License (MIT)
 * @author The Advandz Team <team@advandz.com>
 */

namespace Advandz\App\Controller;

class AppController extends \Controller
{
    /**
     * Pre-Action, This method called before the index method, or controller specified action.
     */
    public function preAction()
    {
        // Load the necessary helpers
        $this->helpers(['Cdnjs']);

        // Load the necessary libraries from CDNJS
        $libs = $this->Cdnjs->loadLibraries(['jquery']);

        // Set the structure variables
        $this->structure->set('libs', $libs);
    }

    //
    // TODO: Define any methods that you would like to use in any of your other
    // controller that extend this class.
    //
}
