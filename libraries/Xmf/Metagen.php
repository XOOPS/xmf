<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf;

/**
 * Metagen
 *
 * @category  Xmf\Module\Metagen
 * @package   Xmf
 * @author    trabis <lusopoemas@gmail.com>
 * @copyright 2011-2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license   http://www.fsf.org/copyleft/gpl.html GNU public license
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     1.0
 */
class Metagen
{

    /**
     * @var string
     */
    private $_title;

    /**
     * @var string
     */
    private $_original_title;

    /**
     * @var string
     */
    private $_keywords;

    /**
     * @var string
     */
    private $_meta_description;

    /**
     * @var string
     */
    private $_category_path;

    /**
     * @var string
     */
    private $_description;

    /**
     * @var int
     */
    private $_minChar = 5;
    private $_maxKeywords = 20;

public static function assignTitle($title) {}
public static function assignKeywords($keywords) {}
public static function assignDescription($description) {}
public static function generateKeywords($body, $forceKeys = null, $count=20, $minLength=5) {}
public static function generateDescription($body, $wordCount = 100) {}
public static function generateMetaTags($title, $body, $forceKeys = null, $count=20, $minLength=5, $wordCount = 100) {}

    /**
     * Constructor
     *
     * @param string $title         Page title
     * @param string $keywords      List of meta keywords
     * @param string $description   Meta description
     * @param string $category_path category
     */
    public function __construct($title, $keywords = '', $description = '', $category_path = '')
    {
        $this->setCategoryPath($category_path);
        $this->setTitle($title);
        $this->setDescription($description);

        if (empty($keywords)) {
            $keywords = $this->createMetaKeywords();
        }

        $this->setKeywords($keywords);
    }

    /**
     * Return true if the string is length > 0
     *
     * @author psylove
     *
     * @var string $var to test
     * @return boolean
     */
    protected function nonEmptyString($var)
    {
        return (strlen($var) > 0);
    }

    /**
     * Create a title for the short_url field of an article
     *
     * @author psylove
     *
     * @var string $title title of the article
     * @var bool $withExt do we add an html extension or not
     * @return string sort_url for the article
     */
    public function generateSeoTitle($title = '', $withExt = true)
    {
        $title = rawurlencode(strtolower($title));

        $pattern = array(
            "/%09/", "/%20/", "/%21/", "/%22/", "/%23/", "/%25/", "/%26/", "/%27/", "/%28/", "/%29/", "/%2C/", "/%2F/",
            "/%3A/", "/%3B/", "/%3C/", "/%3D/", "/%3E/", "/%3F/", "/%40/", "/%5B/", "/%5C/", "/%5D/", "/%5E/", "/%7B/",
            "/%7C/", "/%7D/", "/%7E/", "/\./"
        );
        $rep_pat = array(
            "-", "-", "-", "-", "-", "-100", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-", "-at-", "-",
            "-", "-", "-", "-", "-", "-", "-", "-"
        );
        $title = preg_replace($pattern, $rep_pat, $title);
        $pattern = array(
            "/%B0/", "/%E8/", "/%E9/", "/%EA/", "/%EB/", "/%E7/", "/%E0/", "/%E2/", "/%E4/", "/%EE/", "/%EF/", "/%F9/",
            "/%FC/", "/%FB/", "/%F4/", "/%F6/"
        );
        $rep_pat = array("-", "e", "e", "e", "e", "c", "a", "a", "a", "i", "i", "u", "u", "u", "o", "o");
        $title = preg_replace($pattern, $rep_pat, $title);

        $tableau = explode("-", $title);
        $tableau = array_filter($tableau, array($this, "nonEmptyString"));
        $title = implode("-", $tableau);

        if (sizeof($title) > 0) {
            if ($withExt) {
                $title .= '.html';
            }

            return $title;
        } else {
            return '';
        }
    }

    /**
     * Sets the title property
     * @param string $title
     *
     */
    public function setTitle($title)
    {
        global $xoopsModule;
        $this->_title = $this->html2text($title);
        $this->_title = $this->purifyText($this->_title);
        $this->_original_title = $this->_title;

        $moduleName = $xoopsModule->getVar('name');

        $titleTag = array();

        if (isset($this->_title) && ($this->_title != '') && (strtoupper($this->_title) != strtoupper($moduleName))) {
            $titleTag['title'] = $this->_title;
        }

        if (isset($this->_category_path) && ($this->_category_path != '')) {
            $titleTag['category'] = $this->_category_path;
        }

        $ret = isset($titleTag['title']) ? $titleTag['title'] : '';

        if (isset($titleTag['category']) && $titleTag['category'] != '') {
            if ($ret != '') {
                $ret .= ' - ';
            }
            $ret .= $titleTag['category'];
        }
        if (isset($titleTag['module']) && $titleTag['module'] != '') {
            if ($ret != '') {
                $ret .= ' - ';
            }
            $ret .= $titleTag['module'];
        }
        $this->_title = $ret;
    }

    /**
     * Sets the keyword property
     *
     * @param string $keywords
     */
    public function setKeywords($keywords)
    {
        $this->_keywords = $keywords;
    }

    /**
     * Sets the categoryPath property
     *
     * @param string $categoryPath
     */
    public function setCategoryPath($categoryPath)
    {
        $this->_category_path = $this->html2text($categoryPath);
    }

    /**
     * Sets the description property
     * @param string $description
     *
     */
    public function setDescription($description)
    {
        $description = $this->html2text($description);
        $description = $this->purifyText($description);

        $description = preg_replace("/([^\r\n])\r\n([^\r\n])/", "\\1 \\2", $description);
        $description = preg_replace("/[\r\n]*\r\n[\r\n]*/", "\r\n\r\n", $description);
        $description = preg_replace("/[ ]* [ ]*/", ' ', $description);
        $description = StripSlashes($description);

        $this->_description = $description;
        $this->_meta_description = $this->createMetaDescription();
    }

    /**
     * Cleans the provided text
     *
     * @param  string  $text    Text to be cleaned
     * @param  boolean $keyword Whether the provided string is a keyword, or not
     * @return string  The purified text
     */
    public function purifyText($text, $keyword = false)
    {
        return Utilities::purifyText($text, $keyword);
    }

    /**
     *
     * @param $document
     * @return string Converted text
     */
    public function html2text($document)
    {
        return Utilities::html2text($document);
    }

    /**
     * Creates a meta description
     * @param  int    $maxWords Maximum number of words for the description
     * @return string
     */
    public function createMetaDescription($maxWords = 100)
    {
        $words = explode(" ", $this->_description);

        // Only keep $maxWords words
        $newWords = array();
        $i = 0;

        while ($i < $maxWords - 1 && $i < count($words)) {
            $newWords[] = $words[$i];
            $i++;
        }
        $ret = implode(' ', $newWords);

        return $ret;
    }

    /**
     * Generates a list of keywords from the provided text
     * @param  string $text    Text to parse
     * @param  int    $minChar Minimum word length for the keywords
     * @return array  An array of keywords
     */
    public function findMetaKeywords($text, $minChar)
    {
        $keywords = array();

        $text = strtolower($text);
        $text = $this->purifyText($text);
        $text = $this->html2text($text);

        $text = preg_replace("/([^\r\n])\r\n([^\r\n])/", "\\1 \\2", $text);
        $text = preg_replace("/[\r\n]*\r\n[\r\n]*/", "\r\n\r\n", $text);
        $text = preg_replace("/[ ]* [ ]*/", ' ', $text);
        $text = StripSlashes($text);

        $originalKeywords = preg_split('/[^a-zA-Z\'"-]+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($originalKeywords as $originalKeyword) {
            $secondRoundKeywords = explode("'", $originalKeyword);
            foreach ($secondRoundKeywords as $secondRoundKeyword) {
                if (strlen($secondRoundKeyword) >= $minChar) {
                    if (!in_array($secondRoundKeyword, $keywords)) {
                        $key[$secondRoundKeyword] = $secondRoundKeyword;
                        if(empty($keycnt[$secondRoundKeyword])) $keycnt[$secondRoundKeyword] = 0;
                         $keycnt[$secondRoundKeyword] += 1;
                        //$keywords[] = trim($secondRoundKeyword);
                    }
                }
            }
        }
Debug::dump($keycnt);
        array_multisort($keycnt, SORT_DESC, $key, SORT_ASC);
Debug::dump($key);
        $keywords = $key;

        return $keywords;
    }

    /**
     * Creates a string of keywords
     *
     * @return string
     */
    public function createMetaKeywords()
    {
        $keywords = $this->findMetaKeywords($this->_original_title . " " . $this->_description, $this->_minChar);

        $newKeywords = array_slice ($keywords , 0, $this->_maxKeywords );

        $ret = implode(', ', $newKeywords);

        return $ret;
    }

    /**
     * @param $keywords
     * @return void
     */
    public function addMetaKeywords($keywords)
    {
        if (!empty($keywords)) {
            $this->_keywords = array_merge($keywords, explode(",", $keywords));
        }
    }

    /**
     * Generates keywords, description and title, setting the associated properties
     */
    public function buildAutoMetaTags()
    {
        $this->_keywords = $this->createMetaKeywords();
        $this->_meta_description = $this->createMetaDescription();
    }

    /**
     * Assigns the meta tags to the template
     */
    public function createMetaTags()
    {
        global $xoopsTpl, $xoTheme;

        if (is_object($xoTheme)) {
            $xoTheme->addMeta('meta', 'keywords', $this->_keywords);
            $xoTheme->addMeta('meta', 'description', $this->_description);
            $xoTheme->addMeta('meta', 'title', $this->_title);
        } else {
            $xoopsTpl->assign('xoops_meta_keywords', $this->_keywords);
            $xoopsTpl->assign('xoops_meta_description', $this->_description);
        }
        $xoopsTpl->assign('xoops_pagetitle', $this->_title);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->_description;
    }

    /**
     * @return string
     */
    public function getKeywords()
    {
        return $this->_keywords;
    }
}
