<?php
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
 * @author          Grégory Mage (Aka Mage)
 * @author          trabis <lusopoemas@gmail.com>
 * @version         $Id: Infobox.php 8065 2011-11-06 02:02:32Z beckmi $
 */

defined('XMF_EXEC') or die('Xmf was not detected');

class Xmf_Template_Infobox extends Xmf_Template_Abstract
{
    /**
     * @var string
     */
    protected $_title = '';

    /**
     * @var array
     */
    protected $_items = array();

    /**
     * @return void
     */
    protected function init()
    {
    }

    /**
     * @param $title
     * @return void
     */
    public function setTitle($title)
    {
        $this->_title = $title;
    }

    /**
     * @param string $text
     * @param string $type
     * @param string $value
     * @return Xmf_Template_Infobox
     */
    public function addItem($text = '', $type = 'raw', $value = '')
    {
        $item = "";
        switch ($type) {
            case "changelog":
                $item .= "<div class=\"txtchangelog\">" . $text . "</div>";
                break;
            case "span":
                $color = !empty($value) ? $value : 'inherit';
                $item .= sprintf($text, "<span style='color : " . $color . "; font-weight : bold;'>" . $value . "</span><br />");
                break;
            default:
            case "raw":
                $item .= $text;
                break;
        }
        $this->_items[] = $item;
        return $this; //Allow chain item
    }

    /**
     * @return void
     */
    protected function render()
    {
        if (is_object($GLOBALS['xoTheme'])) {
            $GLOBALS['xoTheme']->addStylesheet(XMF_CSS_URL . '/admin.css');
        }

        $ret = "<fieldset>";
        $ret .= "<legend class=\"label\">" . $this->_title . "</legend>\n";
        foreach ($this->_items as $item) {
            $ret .= $item;
        }
        $ret .= "</fieldset>\n";
        $ret .= "<br/>\n";
        $this->tpl->assign('dummy_content', $ret);
    }
}