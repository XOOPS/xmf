<?php

namespace Xmf;

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license         http://www.fsf.org/copyleft/gpl.html GNU public license
 * @package         Xmf
 * @since           0.1
 * @author          trabis <lusopoemas@gmail.com>
 * @author          The SmartFactory <www.smartfactory.ca>
 * @version         $Id: Highlighter.php 8065 2011-11-06 02:02:32Z beckmi $
 */

defined('XMF_EXEC') or die('Xmf was not detected');

class Highlighter
{
    /**
     * @var string
     */
    private $_preg_keywords = '';

    /**
     * @var string
     */
    private $_keywords = '';

    /**
     * @var bool
     */
    private $_single_words = false;

    /**
     * @var callback|null
     */
    private $_replace_callback = null;

    /**
     * Main constructor
     *
     * This is the main constructor of keyhighlighter class.
     * It is the only public method of the class.
     * @param string   $keywords         the keywords you want to highlight
     * @param boolean  $single_words     specify if it has to highlight also the single words.
     * @param callback $replace_callback a custom callback for keyword highlight.
     */
    public function __construct($keywords, $single_words = false, $replace_callback = null)
    {
        $this->_keywords = $keywords;
        $this->_single_words = $single_words;
        $this->_replace_callback = $replace_callback;
    }

    /**
     * @param  array $replace_matches
     * @return mixed
     */
    private function _replace($replace_matches)
    {
        $patterns = array();
        if ($this->_single_words) {
            $keywords = explode(' ', $this->_preg_keywords);
            foreach ($keywords as $keyword) {
                $patterns[] = '/(?' . '>' . $keyword . '+)/si';
            }
        } else {
            $patterns[] = '/(?' . '>' . $this->_preg_keywords . '+)/si';
        }

        $result = $replace_matches[0];

        foreach ($patterns as $pattern) {
            if (!is_null($this->_replace_callback)) {
                $result = preg_replace_callback($pattern, $this->_replace_callback, $result);
            } else {
                $result = preg_replace($pattern, '<span class="highlightedkey">\\0</span>', $result);
            }
        }

        return $result;
    }

    /**
     * @param  string       $buffer
     * @return mixed|string
     */
    private function _highlight($buffer)
    {
        $buffer = '>' . $buffer . '<';
        $this->_preg_keywords = preg_replace('/[^\w ]/si', '', $this->_keywords);
        $buffer = preg_replace_callback("/(\>(((?" . ">[^><]+)|(?R))*)\<)/is", array(&$this, '_replace'), $buffer);
        $buffer = substr($buffer, 1, -1);

        return $buffer;
    }
}
