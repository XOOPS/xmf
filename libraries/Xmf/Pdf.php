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
 * @author          trabis <lusopoemas@gmail.com>
 * @version         $Id: Pdf.php 8065 2011-11-06 02:02:32Z beckmi $
 */

defined('XMF_EXEC') or die('Xmf was not detected');

include_once XMF_LIBRARIES_PATH . '/tcpdf/tcpdf.php';

class Xmf_Pdf extends TCPDF
{
    /**
     * Constructor
     *
     * @param string $orientation
     * @param string $unit
     * @param string $format
     * @param bool $unicode
     * @param string $encoding
     * @param bool $diskcache
     */
    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false)
    {
        error_reporting(0);
        if (is_object($GLOBALS['xoopsLogger'])) {
            $GLOBALS['xoopsLogger']->activated = false;
        }

        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache);

        $filename = XMF_LIBRARIES_PATH . '/tcpdf/config/lang/' . _LANGCODE . '.php';
        if (file_exists($filename)) {
            include_once $filename;
        } else {
            include_once XMF_LIBRARIES_PATH . '/tcpdf/config/lang/en.php';
        }
        $this->setLanguageArray($l); //set language items

        // set font compatible with all characteres
        $this->SetFont('freeserif', '', 14);
    }

    /**
     *  Destructor
     */
    public function __destruct()
    {
        parent::__destruct();
    }
}
