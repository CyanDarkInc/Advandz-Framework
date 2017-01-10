<?php
/**
 * Allows the creation of views.
 *
 * @package Advandz
 * @subpackage Advandz.lib
 * @copyright Copyright (c) 2010-2013 Phillips Data, Inc. All Rights Reserved.
 * @license https://opensource.org/licenses/MIT The MIT License (MIT)
 * @author Cody Phillips <therealclphillips.woop@gmail.com>>
 */
class View extends Knife {
    /**
     * @var array Holds all the variables we will send to the view
     */
    protected $vars;
    
    /**
     * @var string The file used in the view
     */
    public $file;
    
    /**
     * @var string The file extension used in the view
     */
    public $view_ext = ".pdt";
    
    /**
     * @var string The location of this view's files within the view path
     */
    public $view;
    
    /**
     * @var string This view's relative path (useful in template to reference view related files [e.g. images,
     *     javascript, css])
     */
    public $view_dir;
    
    /**
     * @var string This aasets relative path (useful in template to reference view related files [e.g. images,
     *     javascript, css])
     */
    public $assets_dir;
    
    /**
     * @var string This view's partial path relative to the public web directory
     */
    public $view_path;
    
    /**
     * @var string The default view path
     */
    public $default_view_path = APPDIR;
    
    /**
     * @param string $file The file name you want to load.
     * @param string $view The view directory to use (plugins use PluginName.directory).
     */
    public function __construct($file = null, $view = null) {
        $this->view     = Configure::get("System.default_view");
        $this->view_ext = Configure::get("System.view_ext");
        
        $this->setView($file, $view);
    }
    
    /**
     * Copy constructor used to clone the View with all properties (e.g.
     * helpers) retained, while clearing all variables to be set in the view
     * file.
     */
    public function __clone() {
        $this->vars = null;
    }
    
    /**
     * Overwrites the default view path
     *
     * @param string $path The path to set as the default view path in this view
     */
    public final function setDefaultView($path) {
        $this->default_view_path = $path;
        $this->view_path         = $path;
    }
    
    /**
     * Sets the view file and view to be used for this View
     *
     * @param string $file The file name to load
     * @param string $view The view directory to use (plugins use PluginName.directory)
     */
    public final function setView($file = null, $view = null) {
        // Overwrite the view file if given
        if ($file !== null) {
            $this->file = $file;
        }
        
        // Overwrite the view directory if given
        list($view_path, $view) = $this->getViewPath($view);
        $this->view       = $view;
        $this->view_path  = $view_path;
        $this->view_dir   = Router::makeURI(str_replace("index.php/", "", WEBDIR) . $view_path . "views" . DS . $view . DS);
        $this->assets_dir = Router::makeURI(str_replace("index.php/", "", WEBDIR) . "assets" . DS);
    }
    
    /**
     * Sets a variable in this view with the given name and value
     *
     * @param mixed $name Name of the variable to set in the view, or an array of key/value pairs where each key is the
     *     variable and each value is the value to set.
     * @param mixed $value Value of the variable to set in the view.
     */
    public final function set($name, $value = null) {
        if (is_array($name)) {
            foreach ($name as $var_name => $value) {
                $this->vars[$var_name] = (is_object($value) && $value instanceof self ? $value->fetch() : $value);
            }
        } else {
            $this->vars[$name] = (is_object($value) && $value instanceof self ? $value->fetch() : $value);
        }
    }
    
    /**
     * Returns the output of the view
     *
     * @param string $file The file used as our view
     * @param string $view The view directory to use
     * @return string HTML generated by the view
     * @throws Exception When the file does not exist
     */
    public final function fetch($file = null, $view = null) {
        $this->setView($file, $view);
        
        $file = $this->view_path . "views" . DS . $this->view . DS . $this->file . $this->view_ext;
        
        if (is_array($this->vars)) {
            extract($this->vars);
        } // Extract the vars to local namespace
        
        if (!file_exists($file)) {
            throw new Exception("Files does not exist: " . $file);
        }
        
        ob_start(); // Start output buffering
        
        include $this->compile($file); // Include the file
        
        $contents = ob_get_clean(); // Get the contents of the buffer and close buffer.
        
        return $contents; // Return the contents
    }
    
    /**
     * Fetches the view path and view file from the given string which may be
     * of the format PluginName.view_path/view_dir
     *
     * @param string $view The string to parse into a view path and view
     * @return array An array where the 1st index is the view path and the 2nd is the view
     */
    private function getViewPath($view = null) {
        $view_path = ($this->view_path ? $this->view_path : $this->default_view_path);
        
        $view = ($view == null ? $this->view : $view);
        
        $view_parts = explode(".", $view);
        
        if (count($view_parts) == 2) {
            $view_path = str_replace(ROOTWEBDIR, "", PLUGINDIR) . Loader::fromCamelCase($view_parts[0]) . DS;
            $view      = $view_parts[1];
        }
        
        return [$view_path, $view];
    }
}