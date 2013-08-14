<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf\Template;

/**
 * Feed implements a basic rss feed
 *
 * @category  Xmf\Template\Feed
 * @package   Xmf
 * @author    trabis <lusopoemas@gmail.com>
 * @author    The SmartFactory <www.smartfactory.ca>
 * @copyright 2011-2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license   http://www.fsf.org/copyleft/gpl.html GNU public license
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     1.0
 */
class Feed extends AbstractTemplate
{
    /**
     * @var string
     */
    private $_title = '';

    /**
     * @var string
     */
    private $_url = '';

    /**
     * @var string
     */
    private $_description = '';

    /**
     * @var string
     */
    private $_language = '';

    /**
     * @var string
     */
    private $_charset = '';

    /**
     * @var string
     */
    private $_category = '';

    /**
     * @var string
     */
    private $_pubdate = '';

    /**
     * @var string
     */
    private $_webmaster = '';

    /**
     * @var string
     */
    private $_generator = '';

    /**
     * @var string
     */
    private $_copyright = '';

    /**
     * @var string
     */
    private $_lastbuild = '';

    /**
     * @var string
     */
    private $_editor = '';

    /**
     * @var int
     */
    private $_ttl = 60;

    /**
     * @var string
     */
    private $_image_title = '';

    /**
     * @var string
     */
    private $_image_url = '';

    /**
     * @var string
     */
    private $_image_link = '';

    /**
     * @var int
     */
    private $_image_width = 200;

    /**
     * @var int
     */
    private $_image_height = 50;

    /**
     * @var array
     */
    private $_items = array();

    /**
     * @return void
     */
    protected function init()
    {
        $this->setTemplate(XMF_ROOT_PATH . '/templates/xmf_feed.html');
        $this->disableLogger();

        global $xoopsConfig;
        $this->_title = $xoopsConfig['sitename'];
        $this->_url = XOOPS_URL;
        $this->_description = $xoopsConfig['slogan'];
        $this->_language = _LANGCODE;
        $this->_charset = _CHARSET;
        $this->_pubdate = date(_DATESTRING, time());
        $this->_lastbuild = formatTimestamp(time(), 'D, d M Y H:i:s');
        $this->_webmaster = $xoopsConfig['adminmail'];
        $this->_editor = $xoopsConfig['adminmail'];
        $this->_generator = XOOPS_VERSION;
        $this->_copyright = 'Copyright ' . formatTimestamp(time(), 'Y') . ' ' . $xoopsConfig['sitename'];
        $this->_image_title = $this->_title;
        $this->_image_url = XOOPS_URL . '/images/logo.gif';
        $this->_image_link = $this->_url;
    }

    /**
     * Render the feed and display it directly
     *
     * @return void
     */
    protected function render()
    {
        $this->tpl->assign('channel_charset', $this->_charset);
        $this->tpl->assign('channel_title', $this->_title);
        $this->tpl->assign('channel_link', $this->_url);
        $this->tpl->assign('channel_desc', $this->_description);
        $this->tpl->assign('channel_webmaster', $this->_webmaster);
        $this->tpl->assign('channel_editor', $this->_editor);
        $this->tpl->assign('channel_category', $this->_category);
        $this->tpl->assign('channel_generator', $this->_generator);
        $this->tpl->assign('channel_language', $this->_language);
        $this->tpl->assign('channel_lastbuild', $this->_lastbuild);
        $this->tpl->assign('channel_copyright', $this->_copyright);
        $this->tpl->assign('channel_ttl', $this->_ttl);
        $this->tpl->assign('channel_image_url', $this->_image_url);
        $this->tpl->assign('channel_image_title', $this->_image_title);
        $this->tpl->assign('channel_image_url', $this->_image_url);
        $this->tpl->assign('channel_image_link', $this->_image_link);
        $this->tpl->assign('channel_image_width', $this->_image_width);
        $this->tpl->assign('channel_image_height', $this->_image_height);
        $this->tpl->assign('channel_items', $this->_items);
    }

    /**
     * @param string $category
     */
    public function setCategory($category)
    {
        $this->_category = $category;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->_category;
    }

    /**
     * @param string $charset
     */
    public function setCharset($charset)
    {
        $this->_charset = $charset;
    }

    /**
     * @return string
     */
    public function getCharset()
    {
        return $this->_charset;
    }

    /**
     * @param string $copyright
     */
    public function setCopyright($copyright)
    {
        $this->_copyright = $copyright;
    }

    /**
     * @return string
     */
    public function getCopyright()
    {
        return $this->_copyright;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->_description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->_description;
    }

    /**
     * @param string $editor
     */
    public function setEditor($editor)
    {
        $this->_editor = $editor;
    }

    /**
     * @return string
     */
    public function getEditor()
    {
        return $this->_editor;
    }

    /**
     * @param string $generator
     */
    public function setGenerator($generator)
    {
        $this->_generator = $generator;
    }

    /**
     * @return string
     */
    public function getGenerator()
    {
        return $this->_generator;
    }

    /**
     * @param int $image_height
     */
    public function setImageHeight($image_height)
    {
        $this->_image_height = $image_height;
    }

    /**
     * @return int
     */
    public function getImageHeight()
    {
        return $this->_image_height;
    }

    /**
     * @param string $image_link
     */
    public function setImageLink($image_link)
    {
        $this->_image_link = $image_link;
    }

    /**
     * @return string
     */
    public function getImageLink()
    {
        return $this->_image_link;
    }

    /**
     * @param string $image_title
     */
    public function setImageTitle($image_title)
    {
        $this->_image_title = $image_title;
    }

    /**
     * @return string
     */
    public function getImageTitle()
    {
        return $this->_image_title;
    }

    /**
     * @param string $image_url
     */
    public function setImageUrl($image_url)
    {
        $this->_image_url = $image_url;
    }

    /**
     * @return string
     */
    public function getImageUrl()
    {
        return $this->_image_url;
    }

    /**
     * @param int $image_width
     */
    public function setImageWidth($image_width)
    {
        $this->_image_width = $image_width;
    }

    /**
     * @return int
     */
    public function getImageWidth()
    {
        return $this->_image_width;
    }

    /**
     * @param array $items
     */
    public function setItems($items)
    {
        $this->_items = $items;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->_items;
    }

    /**
     * @param string $language
     */
    public function setLanguage($language)
    {
        $this->_language = $language;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->_language;
    }

    /**
     * @param string $lastbuild
     */
    public function setLastbuild($lastbuild)
    {
        $this->_lastbuild = $lastbuild;
    }

    /**
     * @return string
     */
    public function getLastbuild()
    {
        return $this->_lastbuild;
    }

    /**
     * @param string $pubdate
     */
    public function setPubdate($pubdate)
    {
        $this->_pubdate = $pubdate;
    }

    /**
     * @return string
     */
    public function getPubdate()
    {
        return $this->_pubdate;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->_title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->_title;
    }

    /**
     * @param int $ttl
     */
    public function setTtl($ttl)
    {
        $this->_ttl = $ttl;
    }

    /**
     * @return int
     */
    public function getTtl()
    {
        return $this->_ttl;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->_url = $url;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * @param string $webmaster
     */
    public function setWebmaster($webmaster)
    {
        $this->_webmaster = $webmaster;
    }

    /**
     * @return string
     */
    public function getWebmaster()
    {
        return $this->_webmaster;
    }
}
