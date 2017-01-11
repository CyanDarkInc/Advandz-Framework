<?php
/**
 * Provides helper methods for dealing with HTML content.
 *
 * @package Advandz
 * @subpackage Advandz.helpers.html
 * @copyright Copyright (c) 2012-2017 CyanDark, Inc. All Rights Reserved.
 * @license https://opensource.org/licenses/MIT The MIT License (MIT)
 * @author The Advandz Team <team@advandz.com>
 */

namespace Advandz\Helper;

use Type;

class Html
{
    /**
     * @var bool True if requiring XHTML standards, false for traditional HTML
     */
    public $xhtml = true;

    /**
     * Outputs or returns the given string in HTML safe format, if it exists.
     *
     * @param string $str The string to print, if it exists
     * @param bool $return True to return the result as a string, else echo the result
     * @param bool $preserve_tags True to preserve tags
     * @return string The result (if $return is set to true)
     */
    public function _(&$str, $return = false, $preserve_tags = false)
    {
        $result = (isset($str) ? $this->safe($str, $preserve_tags) : '');
        if ($return) {
            return $result;
        }
        print $result;
    }

    /**
     * Makes a given string HTML safe.
     *
     * @param string $str The string to make HTML safe
     * @param bool $preserve_tags True to preserve tags
     * @return string The string in HTML safe format
     */
    public function safe($str, $preserve_tags = false)
    {
        if (!$this->isUtf8($str)) {
            $str = utf8_encode($str);
        }

        $str = htmlentities($str, ENT_QUOTES, 'UTF-8');

        if ($preserve_tags) {
            $str = str_replace(['&lt;', '&gt;', '&quot;', '&#039;'], ['<', '>', '"', "'"], $str);
        }

        return $str;
    }

    /**
     * Tests whether the given string is in UTF8 format.
     *
     * @param string $str The string to test
     * @return bool True if it is UTF8, false otherwise
     */
    public function isUtf8($str)
    {
        $c    = Type::_int();
        $b    = Type::_int();
        $bits = Type::_int();
        $len  = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            $c = ord($str[$i]);
            if ($c > 128) {
                if (($c >= 254)) {
                    return false;
                } elseif ($c >= 252) {
                    $bits = 6;
                } elseif ($c >= 248) {
                    $bits = 5;
                } elseif ($c >= 240) {
                    $bits = 4;
                } elseif ($c >= 224) {
                    $bits = 3;
                } elseif ($c >= 192) {
                    $bits = 2;
                } else {
                    return false;
                }
                if (($i + $bits) > $len) {
                    return false;
                }
                while ($bits > 1) {
                    $i++;
                    $b = ord($str[$i]);
                    if ($b < 128 || $b > 191) {
                        return false;
                    }
                    $bits--;
                }
            }
        }

        return true;
    }

    /**
     * Returns the given string as-is, if it exists. This is similar
     * to Html::_(), except it does not make the string HTML safe, and an
     * alternative value may be returned if the given $str does not exist. It's
     * useful for passing into methods that expect raw text.
     *
     * @param string $str The string to print, if it exists
     * @param string $alt The alternate string to use if $str is not set
     * @return string The result
     * @see Html::_()
     */
    public function ifSet(&$str, $alt = '')
    {
        return isset($str) ? $str : $alt;
    }

    /**
     * Concatenate multiple strings together, with an optional separator.
     *
     * @param mixed $separator A string to be placed between each element or an array, containing 'start', 'before',
     *     'between', after', and 'end' separators -- all optional
     * @param string [optional] Pieces to concatenate [$param1, $param2, ..., $paramN]
     * @return string The concatenated string
     */
    public function concat($separator = null)
    {
        $params = func_get_args();
        array_shift($params); // Shift the separator off of the list

        $result     = null;
        $num_params = count($params);
        for ($i = 0, $j = 0; $i < $num_params; $i++) {
            if ($params[$i] == '') {
                continue;
            }

            if (is_array($separator)) {

                // Start cap to begin this concatenation
                if ($j == 0 && isset($separator['start'])) {
                    $result .= $separator['start'];
                }

                $result .= ($j > 0 && isset($separator['between']) ? $separator['between'] : '');

                if (isset($separator['before'])) {
                    $result .= $separator['before'];
                }

                $result .= $params[$i];

                if (isset($separator['after'])) {
                    $result .= $separator['after'];
                }
                // End cap to finish this concatenation
                if ($i == $num_params - 1 && isset($separator['end'])) {
                    $result .= $separator['end'];
                }
            } else {
                $result .= ($j > 0 ? $separator : '').$params[$i];
            }
            $j++;
        }

        return $result;
    }

    /**
     * Builds attributes for the current tag. An attribute may be either a string or
     * an array. In the case of arrays, the elements of the arrays will be concatenated together
     * using the $glue parameter.
     *
     * @param array $attributes The attribute keys and values to build
     * @pararm string $glue The string to use to concatenate an array of attribute values together
     * @return string The key=value attributes
     */
    public function buildAttributes(array $attributes = null, $glue = ' ')
    {
        $html = Type::_string();

        if (!empty($attributes)) {
            foreach ($attributes as $key => $value) {
                if (is_array($value)) {
                    $value = implode($glue, $value);
                }
                $html .= ' '.$this->_($key, true).'="'.$this->_($value, true).'"';
            }
        }

        return $html;
    }

    /**
     * Add conditional operation around a block of HTML.
     *
     * @param string $html The HTML to wrap an expression around
     * @param string $expression The expression to use
     * @param bool $hidden True to hide the $html from all browsers by default, false to display by default
     * @return string The HTML block with the condition wrapped around it
     */
    public function addCondition($html, $expression, $hidden = true)
    {
        if ($expression == null) {
            return $html;
        }

        return '<!--[if '.$expression.']>'.(!$hidden ? '<!-->'.$html.'<!--' : $html).'<![endif]-->';
    }

    /**
     * Converts hyperlinks found into HTML.
     *
     * @param string $content Content containing hyperlinks
     * @return string The content with hyperlinks as HTML
     */
    public function hyperlink($content)
    {
        $pattern     = Type::_array();
        $replacement = Type::_array();

        // Convert email addresses to links
        $pattern[]     = "/[a-zA-Z0-9!#$%\*\/?\|^\{\}`~&'\+=_.-]+@[a-zA-Z0-9.-]+\.[a-zA-Z0-9]{2,10}/";
        $replacement[] = '<a href="mailto:\\0">\\0</a>';

        // Convert links where http is specified, into links
        $pattern[]     = "/(https?:\/\/)(w{0,3}[\.]{0,1}[a-zA-Z0-9.-]+\.[a-zA-Z0-9]{2,10})(.*)([!-\/:-@]+\s|[!-\/:-@]+$|\s|$)/iU";
        $replacement[] = '<a href="\\1\\2\\3" target="_blank">\\1\\2\\3</a>\\4';

        // Convert links where http is not specified, into links
        $pattern[]     = "/([^http:\/\/]|[^https:\/\/]|^)(www.[a-zA-Z0-9.-]+\.[a-zA-Z0-9]{2,10})(.*)([!-\/:-@]+\s|[!-\/:-@]+$|\s|$)/iU";
        $replacement[] = '\\1<a href="http://\\2\\3" target="_blank">\\2\\3</a>\\4';

        return preg_replace($pattern, $replacement, $content);
    }
}
