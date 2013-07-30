<?php
//============================================================+
// File name   : tcpdf_config.php
// Begin       : 2004-06-11
// Last Update : 2008-06-03
//
// Description : Congiguration file for TCPDF.
//
// Author: Nicola Asuni
//
// (c) Copyright:
//               Nicola Asuni
//               Tecnick.com s.r.l.
//               Via Della Pace, 11
//               09044 Quartucciu (CA)
//               ITALY
//               www.tecnick.com
//               info@tecnick.com
//============================================================+

/**
 * Configuration file for TCPDF.
 * @author Nicola Asuni
 * @copyright 2004-2008 Nicola Asuni - Tecnick.com S.r.l (www.tecnick.com) Via Della Pace, 11 - 09044 - Quartucciu (CA) - ITALY - www.tecnick.com - info@tecnick.com
 * @package com.tecnick.tcpdf
 * @version 3.0.013
 * @link http://tcpdf.sourceforge.net
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 * @since 2004-10-27
 */

// If you define the constant K_TCPDF_EXTERNAL_CONFIG, the following settings will be ignored.

if (!defined("XOOPS_ROOT_PATH")) {
    die("XOOPS root path not defined");
}

if (!defined("K_TCPDF_EXTERNAL_CONFIG")) {

    // DOCUMENT_ROOT fix for IIS Webserver
    if ((!isset($_SERVER['DOCUMENT_ROOT'])) OR (empty($_SERVER['DOCUMENT_ROOT']))) {
        if(isset($_SERVER['SCRIPT_FILENAME'])) {
            $_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0-strlen($_SERVER['PHP_SELF'])));
        } elseif(isset($_SERVER['PATH_TRANSLATED'])) {
            $_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0-strlen($_SERVER['PHP_SELF'])));
        }	else {
            // define here your DOCUMENT_ROOT path if the previous fails
            $_SERVER['DOCUMENT_ROOT'] = "/var/www";
        }
    }

    /**
     * Installation path (/var/www/tcpdf/).
     * By default it is automatically calculated but you can also set it as a fixed string.
     */
    define ("K_PATH_MAIN", realpath(substr(dirname(__FILE__), 0, 0-strlen("config")))."/");

    // Automatic calculation for the following K_PATH_URL constant
    if (isset($_SERVER["HTTP_HOST"]) AND (!empty($_SERVER["HTTP_HOST"]))) {
        if(isset($_SERVER["HTTPS"]) AND (!empty($_SERVER["HTTPS"])) AND strtolower($_SERVER['HTTPS'])!='off') {
            $path_url = "https://";
        } else {
            $path_url = "http://";
        }
        $path_url .= $_SERVER["HTTP_HOST"];
        $path_url .= str_replace( '\\', '/', substr($_SERVER["PHP_SELF"], 0, -24));
    }

    /**
     * URL path to tcpdf installation folder (http://localhost/tcpdf/).
     * By default it is automatically calculated but you can also set it as a fixed string.
     */
    define ("K_PATH_URL", $path_url);

    /**
     * path for PDF fonts
     * use K_PATH_MAIN."fonts/old/" for old non-UTF8 fonts
     */
    define ("K_PATH_FONTS", K_PATH_MAIN."fonts/");

    /**
     * cache directory for temporary files (full path)
     */
    define ("K_PATH_CACHE", K_PATH_MAIN."cache/");

    /**
     * cache directory for temporary files (url path)
     */
    define ("K_PATH_URL_CACHE", K_PATH_URL."cache/");

    /**
     *images directory
     */
    define ("K_PATH_IMAGES", XOOPS_ROOT_PATH ."/images/");

    /**
     * blank image
     */
    define ("K_BLANK_IMAGE", XOOPS_URL . "/uploads/blank.gif");

    /**
     * page format
     */
    define ("PDF_PAGE_FORMAT", "A4");

    /**
     * page orientation (P=portrait, L=landscape)
     */
    define ("PDF_PAGE_ORIENTATION", "P");

    /**
     * document creator
     */
    define ("PDF_CREATOR", "TCPDF");

    /**
     * document author
     */
    define ("PDF_AUTHOR", "TCPDF");

    /**
     * header title
     */
    define ("PDF_HEADER_TITLE", "TCPDF Example");

    /**
     * header description string
     */
    define ("PDF_HEADER_STRING", "by Nicola Asuni - Tecnick.com\nwww.tcpdf.org");

    /**
     * image logo
     */
    define ("PDF_HEADER_LOGO", "logo.gif");

    /**
     * header logo image width [mm]
     */
    define ("PDF_HEADER_LOGO_WIDTH", 30);

    /**
     *  document unit of measure [pt=point, mm=millimeter, cm=centimeter, in=inch]
     */
    define ("PDF_UNIT", "mm");

    /**
     * header margin
     */
    define ("PDF_MARGIN_HEADER", 5);

    /**
     * footer margin
     */
    define ("PDF_MARGIN_FOOTER", 15);

    /**
     * top margin
     */
    define ("PDF_MARGIN_TOP", 27);

    /**
     * bottom margin
     */
    define ("PDF_MARGIN_BOTTOM", 25);

    /**
     * left margin
     */
    define ("PDF_MARGIN_LEFT", 15);

    /**
     * right margin
     */
    define ("PDF_MARGIN_RIGHT", 15);

    /**
     * main font name
     */
    define ("PDF_FONT_NAME_MAIN", "freeserif");

    /**
     * main font size
     */
    define ("PDF_FONT_SIZE_MAIN", 18);

    /**
     * data font name
     */
    define ("PDF_FONT_NAME_DATA", "freeserif");

    /**
     * data font size
     */
    define ("PDF_FONT_SIZE_DATA", 16);

    /**
     *  scale factor for images (number of points in user unit)
     */
    define ("PDF_IMAGE_SCALE_RATIO", 4);

    /**
     * magnification factor for titles
     */
    define("HEAD_MAGNIFICATION", 1.1);

    /**
     * height of cell repect font height
     */
    define("K_CELL_HEIGHT_RATIO", 1.25);

    /**
     * title magnification respect main font size
     */
    define("K_TITLE_MAGNIFICATION", 1.3);

    /**
     * reduction factor for small font
     */
    define("K_SMALL_RATIO", 2/3);

    /**
     * set to true to enable the special procedure used to avoid the overlappind of symbols on Thai language
     */
    define('K_THAI_TOPCHARS', true);

    /**
     * if true allows to call TCPDF methods using HTML syntax
     * IMPORTANT: For security reason, disable this feature if you are printing user HTML content.
     */
    define('K_TCPDF_CALLS_IN_HTML', true);
}

//============================================================+
// END OF FILE
//============================================================+
?>