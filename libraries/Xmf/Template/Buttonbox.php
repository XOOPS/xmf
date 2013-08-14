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
 * Buttonbox
 *
 * @category  Xmf\Template\Buttonbox
 * @package   Xmf
 * @author    Grégory Mage (Aka Mage)
 * @author    trabis <lusopoemas@gmail.com>
 * @copyright 2011-2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license   http://www.fsf.org/copyleft/gpl.html GNU public license
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     1.0
 */
class Buttonbox extends AbstractTemplate
{
    /**
     * @var array
     */
    private $_items = array();

    /**
     * @var string
     */
    private $_delimiter = "&nbsp;";

    /**
     * @var string
     */
    private $_position = "right";

    /**
     * @return void
     */
    protected function init()
    {
    }

    /**
     * @param  string $position
     * @return void
     */
    public function setPosition($position)
    {
        $this->_position = $position;
    }

    /**
     * @param  string $delimiter
     * @return void
     */
    public function setDelimiter($delimiter)
    {
        $this->_delimiter = $delimiter;
    }

    /**
     * @param  string $title
     * @param  string $link
     * @param  string $icon
     * @param  string $extra
     * @return void
     */
    public function addItem($title, $link, $icon = 'add', $extra = '')
    {
        $item['title'] = $title;
        $item['link'] = $link;
        $item['icon'] = $icon . '.png';
        $item['extra'] = $extra;
        $this->_items[] = $item;
    }

    /**
     * @return void
     */
    protected function render()
    {
        $ret = '';
        $path = XMF_IMAGES_URL . "/icons/32/";
        switch ($this->_position) {
            default:
            case "right":
                $ret = "<div class=\"floatright\">\n";
                break;

            case "left":
                $ret = "<div class=\"floatleft\">\n";
                break;

            case "center":
                $ret = "<div class=\"aligncenter\">\n";
        }
        $ret .= "<div class=\"xo-buttons\">\n";
        foreach ($this->_items as $item) {
            $ret .= "<a class='ui-corner-all tooltip' href='" . $item['link'] . "' title='" . $item['title'] . "'>";
            $ret .= "<img src='" . $path . $item['icon'] . "' title='" . $item['title'] . "' />" . $item['title'] . ' ' . $item['extra'];
            $ret .= "</a>\n";
            $ret .= $this->_delimiter;
        }
        $ret .= "</div>\n</div>\n";
        $this->tpl->assign('dummy_content', $ret);
    }
}
