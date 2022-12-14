<?php // $Id$

// vim: expandtab sw=4 ts=4 sts=4:

# ***** BEGIN LICENSE BLOCK *****
# This file is part of HTML Sanitizer.
# Copyright (c) 2005-2009 Frederic Minne <zefredz@gmail.com>.
# All rights reserved.
#
# HTML Sanitizer is free software; you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation; either version 3 of the License, or
# (at your option) any later version.
#
# HTML Sanitizer is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public License
# along with HTML Sanitizer; if not, see <http://www.gnu.org/licenses/>.
#
# ***** END LICENSE BLOCK *****

/**
 * @author  Frederic Minne <zefredz@claroline.net>
 * @copyright Copyright &copy; 2006-2007, Frederic Minne
 * @license http://www.gnu.org/licenses/lgpl.txt GNU Lesser General Public License version 3 or later
 * @version 1.0
 * @package HTML
 */

/**
 * Sanitize HTML body content
 * Remove dangerous tags and attributes that can lead to security issues like
 * XSS or HTTP response splitting
 */
class Phototour_Sanitizer
{
    // Private fields
    var $_allowedTags;
    var $_allowJavascriptEvents;
    var $_allowJavascriptInUrls;
    var $_allowObjects;
    var $_allowScript;
    var $_allowStyle;
    var $_additionalTags;
    var $_params;

    /**
     * Constructor
     */
    function PhototourL_Sanitizer()
    {
        $this->resetAll();
    }

    /**
     * (re)set all options to default value
     */
    function resetAll()
    {
        $this->_allowDOMEvents = false;
        $this->_allowJavascriptInUrls = false;
        $this->_allowStyle = false;
        $this->_allowScript = false;
        $this->_allowObjects = false;
        $this->_allowStyle = false;

        $this->_allowedTags = '<a><br><b><h1><h2><h3><h4><h5><h6>'
            . '<img><li><ol><p><strong><table><tr><td><th><u><ul><thead>'
            . '<tbody><tfoot><em><dd><dt><dl><span><div><del><add><i><hr>'
            . '<pre><br><blockquote><address><code><caption><abbr><acronym>'
            . '<cite><dfn><q><ins><sup><sub><kbd><samp><var><tt><small><big>';

        $this->_additionalTags = '';
    }

    /**
     * Add additional tags to allowed tags
     * @param string
     * @access public
     */
    function addAdditionalTags($tags)
    {
        $this->_additionalTags .= $tags;
    }

    /**
     * Allow object, embed, applet and param tags in html
     * @access public
     */
    function allowObjects()
    {
        $this->_allowObjects = true;
    }

    /**
     * Allow DOM event on DOM elements
     * @access public
     */
    function allowDOMEvents()
    {
        $this->_allowDOMEvents = true;
    }

    /**
     * Allow script tags
     * @access public
     */
    function allowScript()
    {
        $this->_allowScript = true;
    }

    /**
     * Allow the use of javascript: in urls
     * @access public
     */
    function allowJavascriptInUrls()
    {
        $this->_allowJavascriptInUrls = true;
    }

    /**
     * Allow style tags and attributes
     * @access public
     */
    function allowStyle()
    {
        $this->_allowStyle = true;
    }

    /**
     * Helper to allow all javascript related tags and attributes
     * @access public
     */
    function allowAllJavascript()
    {
        $this->allowDOMEvents();
        $this->allowScript();
        $this->allowJavascriptInUrls();
    }

    /**
     * Allow all tags and attributes
     * @access public
     */
    function allowAll()
    {
        $this->allowAllJavascript();
        $this->allowObjects();
        $this->allowStyle();
    }

    /**
     * Filter URLs to avoid HTTP response splitting attacks
     * @access  public
     * @param   string url
     * @return  string filtered url
     */
    function filterHTTPResponseSplitting($url)
    {
        $dangerousCharactersPattern = '~(\r\n|\r|\n|%0a|%0d|%0D|%0A)~';
        return preg_replace($dangerousCharactersPattern, '', $url);
    }

    /**
     * Remove potential javascript in urls
     * @access  public
     * @param   string url
     * @return  string filtered url
     */
    function removeJavascriptURL($str)
    {
        $HTML_Sanitizer_stripJavascriptURL = 'javascript:[^"]+';

        $str = preg_replace("/$HTML_Sanitizer_stripJavascriptURL/i"
            , ''
            , $str);

        return $str;
    }

    /**
     * Remove potential flaws in urls
     * @access  private
     * @param   string url
     * @return  string filtered url
     */
    function sanitizeURL($url)
    {
        if (!$this->_allowJavascriptInUrls) {
            $url = $this->removeJavascriptURL($url);
        }

        $url = $this->filterHTTPResponseSplitting($url);

        return $url;
    }

    /**
     * Callback for PCRE
     * @access private
     * @param matches array
     * @return string
     * @see sanitizeURL
     */
    function _sanitizeURLCallback($matches)
    {
        return 'href="' . $this->sanitizeURL($matches[1]) . '"';
    }

    /**
     * Remove potential flaws in href attributes
     * @access  private
     * @param   string html tag
     * @return  string filtered html tag
     */
    function sanitizeHref($str)
    {
        $HTML_Sanitizer_URL = 'href="([^"]+)"';

        return preg_replace_callback("/$HTML_Sanitizer_URL/i"
            , array(&$this, '_sanitizeURLCallback')
            , $str);
    }

    /**
     * Callback for PCRE
     * @access private
     * @param matches array
     * @return string
     * @see sanitizeURL
     */
    function _sanitizeSrcCallback($matches)
    {
        return 'src="' . $this->sanitizeURL($matches[1]) . '"';
    }

    /**
     * Remove potential flaws in href attributes
     * @access  private
     * @param   string html tag
     * @return  string filtered html tag
     */
    function sanitizeSrc($str)
    {
        $HTML_Sanitizer_URL = 'src="([^"]+)"';

        return preg_replace_callback("/$HTML_Sanitizer_URL/i"
            , array(&$this, '_sanitizeSrcCallback')
            , $str);
    }

    /**
     * Remove dangerous attributes from html tags
     * @access  private
     * @param   string html tag
     * @return  string filtered html tag
     */
    function removeEvilAttributes($str)
    {
        if (!isset($this->_allowDOMEvents)) {
            $str = preg_replace_callback('/<(.*?)>/i'
                , array(&$this, '_removeDOMEventsCallback')
                , $str);
        }

        if (!$this->_allowStyle) {
            $str = preg_replace_callback('/<(.*?)>/i'
                , array(&$this, '_removeStyleCallback')
                , $str);
        }

        return $str;
    }

    /**
     * Remove DOM events attributes from html tags
     * @access  private
     * @param   string html tag
     * @return  string filtered html tag
     */
    function removeDOMEvents($str)
    {
        $str = preg_replace('/\s*=\s*/', '=', $str);

        $HTML_Sanitizer_stripAttrib = '(onclick|ondblclick|onmousedown|'
            . 'onmouseup|onmouseover|onmousemove|onmouseout|onkeypress|onkeydown|'
            . 'onkeyup|onfocus|onblur|onabort|onerror|onload)';

        $str = stripslashes(preg_replace("/$HTML_Sanitizer_stripAttrib/i"
            , 'forbidden'
            , $str));

        return $str;
    }

    /**
     * Callback for PCRE
     * @access private
     * @param matches array
     * @return string
     * @see removeDOMEvents
     */
    function _removeDOMEventsCallback($matches)
    {
        return '<' . $this->removeDOMEvents($matches[1]) . '>';
    }

    /**
     * Remove style attributes from html tags
     * @access  private
     * @param   string html tag
     * @return  string filtered html tag
     */
    function removeStyle($str)
    {
        $str = preg_replace('/\s*=\s*/', '=', $str);

        $HTML_Sanitizer_stripAttrib = '(style)';

        $str = stripslashes(preg_replace("/$HTML_Sanitizer_stripAttrib/i"
            , 'forbidden'
            , $str));

        return $str;
    }

    /**
     * Callback for PCRE
     * @access private
     * @param matches array
     * @return string
     * @see removeStyle
     */
    function _removeStyleCallback($matches)
    {
        return '<' . $this->removeStyle($matches[1]) . '>';
    }

    /**
     * Remove dangerous HTML tags
     * @access  private
     * @param   string html code
     * @return  string filtered url
     */
    function removeEvilTags($str)
    {
        $allowedTags = $this->_allowedTags;

        if ($this->_allowScript) {
            $allowedTags .= '<script>';
        }

        if ($this->_allowStyle) {
            $allowedTags .= '<style>';
        }

        if ($this->_allowObjects) {
            $allowedTags .= '<object><embed><applet><param>';
        }

        $allowedTags .= $this->_additionalTags;

        $str = strip_tags($str, $allowedTags);

        return $str;
    }

    /**
     * Sanitize HTML
     *  remove dangerous tags and attributes
     *  clean urls
     * @access  public
     * @param   string html code
     * @return  string sanitized html code
     */
    function sanitize($html)
    {
        $html = $this->removeEvilTags($html);

        $html = $this->removeEvilAttributes($html);

        $html = $this->sanitizeHref($html);

        $html = $this->sanitizeSrc($html);

        return $html;
    }

    function int($html, $check = 0)
    {
        if ($check == 0) {
            $html = preg_replace('/\D/', '', $html);
            return (int)$html;
        } else {

            if (!is_numeric($html)) {
                throw new Zend_Exception('Not an integer');

            } else
                return $html;
        }
    }

    function float($html, $check = 0)
    {
        if ($check == 0) {
            //$html=preg_replace('/\D./', '', $html);
            return (float)$this->sanitize($html);
        } else {
            if (floatval($html)) {
                return $html;
            } else {
                throw new Zend_Exception('Not a float');
            }
        }
    }

    public function get($id, $type = "sanitize", $check = 0)
    {
        $p = $this->_params;
        if (!empty($p["$id"]))
            return $this->$type($p["$id"], $check);
        else
            if ($type == "sanitize")
                return "";
            else
                return 0;
    }


    public function getParam($id, $type = "int", $check = 0)
    {
        $p = $this->_params;
        return (empty($p["$id"])) ? 0 : $this->$type($p["$id"], $check);
    }


    public function string($html)
    {
        return $this->sanitize($html);
    }

    public function checkParams($params)
    {
        $data = $this->_params;
        $form_data = array();
        foreach ($params as $param) {
            if (array_key_exists($param, $data) == FALSE) {
                if (Zend_Controller_Action_HelperBroker::hasHelper('redirector')) {
                    $redirect = Zend_Controller_Action_HelperBroker::getExistingHelper('redirector');

                } else
                    $redirect = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
                $redirect->gotoSimple('param', 'error', 'tmapi', array('param' => $param));
                Phototour_Logger::log("$param FAIL");

            } else {
                $form_data[$param] = $data[$param];
            }
        }

        return $form_data;
    }


}

function html_sanitize($str)
{
    static $san = null;

    if (empty($san)) {
        $san = new HTML_Sanitizer;
    }

    return $san->sanitize($str);
}


?>
