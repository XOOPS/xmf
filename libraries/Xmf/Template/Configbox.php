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
 * @package         Xmf
 * @since           0.1
 * @author          Grégory Mage (Aka Mage)
 * @author          trabis <lusopoemas@gmail.com>
 * @version         $Id: Configbox.php 8065 2011-11-06 02:02:32Z beckmi $
 */

defined('XMF_EXEC') or die('Xmf was not detected');

class Xmf_Template_Configbox extends Xmf_Template_Abstract
{
    /**
     * @var string
     */
    private $_title;

    /**
     * @var array
     */
    private $_items;

    protected function init()
    {
        Xmf_Language::load('main', 'xmf');
    }

    /**
     * @param string $title
     * @return void
     */
    public function setTitle($title)
    {
        $this->_title = $title;
    }

    /**
     * @param string $value
     * @param string $type type of config:   1- "default": Just a line with value.
     *                                       2- "folder": check if this is an folder.
     *                                       3- "chmod": check if this is the good chmod.
     *                                          For this type ("chmod"), the value is an array: array(path, chmod)
     * @return bool
     */
    public function addItem($value, $type = 'default')
    {
        $item = "";
        $path = XMF_IMAGES_URL . "/icons/16/";
        switch ($type)
        {
            default:
            case "default":
                $item .= "<span>" . $value . "</span>";
                break;

            case "folder":
                if (!is_dir($value)){
                    $item .= "<span style='color : red; font-weight : bold;'>";
                    $item .= "<img src='" . $path . "off.png' >";
                    $item .= sprintf(_AM_XMF_CONFIG_FOLDERKO, $value);
                    $item .= "</span>\n";
                }else{
                    $item .= "<span style='color : green;'>";
                    $item .= "<img src='" . $path . "on.png' >";
                    $item .= sprintf(_AM_XMF_CONFIG_FOLDEROK, $value);
                    $item .= "</span>\n";
                }
                break;

            case "chmod":
                if (is_dir($value[0])){
                    if (substr(decoct(fileperms($value[0])),2) != $value[1]) {
                        $item .= "<span style='color : red; font-weight : bold;'>";
                        $item .= "<img src='" . $path . "off.png' >";
                        $item .= sprintf(_AM_XMF_CONFIG_CHMOD, $value[0], $value[1], substr(decoct(fileperms($value[0])),2));
                        $item .= "</span>\n";
                    }else{
                        $item .= "<span style='color : green;'>";
                        $item .= "<img src='" . $path . "on.png' >";
                        $item .= sprintf(_AM_XMF_CONFIG_CHMOD, $value[0], $value[1], substr(decoct(fileperms($value[0])),2));
                        $item .= "</span>\n";
                    }
                }
                break;
        }
        $this->_items[] = $item;
        return true;
    }

    protected function render()
    {
        $ret = "";
        foreach ($this->_items as $item) {
            $ret .= $item;
            $ret .= "<br />";
        }
        $this->tpl->assign('dummy_content', $ret);
    }
}