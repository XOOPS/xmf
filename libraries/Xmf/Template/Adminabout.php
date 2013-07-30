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
 * @author          trabis <lusopoemas@gmail.com>
 * @author          The SmartFactory <www.smartfactory.ca>
 * @version         $Id: Adminabout.php 8065 2011-11-06 02:02:32Z beckmi $
 */

defined('XMF_EXEC') or die('Xmf was not detected');

class Xmf_Template_Adminabout extends Xmf_Template_Abstract
{
    /**
     * @var XoopsModule
     */
    private $_module;

    /**
     * @var string
     */
    private $_paypal;

    /**
     * @var string
     */
    private $_logoImageUrl;

    /**
     * @var string
     */
    private $_logoLinkUrl;

    var $_lang_aboutTitle;
    var $_lang_author_info;
    var $_lang_developer_lead;
    var $_lang_developer_contributor;
    var $_lang_developer_website;
    var $_lang_developer_email;
    var $_lang_developer_credits;
    var $_lang_module_info;
    var $_lang_module_status;
    var $_lang_module_release_date;
    var $_lang_module_demo;
    var $_lang_module_support;
    var $_lang_module_bug;
    var $_lang_module_submit_bug;
    var $_lang_module_feature;
    var $_lang_module_submit_feature;
    var $_lang_module_disclaimer;
    var $_lang_author_word;
    var $_lang_version_history;
    var $_lang_by;

    /**
     * @return void
     */
    protected function init()
    {
        $this->setTemplate(XOOPS_ROOT_PATH . '/modules/xmf/templates/xmf_adminabout.html');
        $this->_module = $GLOBALS['xoopsModule'];
        $this->_paypal = '6KJ7RW5DR3VTJ'; //Xoops Foundation used by default
        $this->_logoLinkUrl = 'http://wwww.xoops.org';
        $this->_logoImageUrl = XMF_IMAGES_URL . '/icons/32/xoopsmicrobutton.gif';
    }

    /**
     * @param XoopsModule $module
     * @return void
     */
    public function setModule(XoopsModule $module)
    {
        $this->_module = $module;
    }

    /**
     * @param string $value Paypal key
     * @return void
     */
    public function setPaypal($value)
    {
        $this->_paypal = $value;
    }

    /**
     * @param string $value Url for the logo image
     * @return void
     */
    public function setLogoImageUrl($value)
    {
        $this->_logoImageUrl = $value;
    }

    /**
     * @param string $value Link to use when logo is clicked
     * @return void
     */
    public function setLogoLinkUrl($value)
    {
        $this->_logoLinkUrl = $value;
    }

    /**
     * @return void
     */
    protected function render()
    {
        Xmf_Language::load('about', 'xmf');
        Xmf_Language::load('modinfo', $this->_module->getVar('dirname'));
        if (is_object($GLOBALS['xoTheme'])) {
            $GLOBALS['xoTheme']->addStylesheet(XMF_CSS_URL . '/admin.css');
        }

        $this->tpl->assign('module_paypal', $this->_paypal);

        $this->tpl->assign('module_url', XOOPS_URL . "/modules/" . $this->_module->getVar('dirname') . "/");
        $this->tpl->assign('module_image', $this->_module->getInfo('image'));
        $this->tpl->assign('module_name', $this->_module->getInfo('name'));
        $this->tpl->assign('module_version', $this->_module->getInfo('version'));
        $this->tpl->assign('module_description', $this->_module->getInfo('description'));

        // Left headings...
        if ($this->_module->getInfo('author_realname') != '') {
            $author_name = $this->_module->getInfo('author') . " (" . $this->_module->getInfo('author_realname') . ")";
        } else {
            $author_name = $this->_module->getInfo('author');
        }

        $this->tpl->assign('module_author_name', $author_name);
        $this->tpl->assign('module_license', $this->_module->getInfo('license'));
        $this->tpl->assign('module_license_url', $this->_module->getInfo('license_url'));
        $this->tpl->assign('module_credits', $this->_module->getInfo('credits'));

        // Developers Information
        $this->tpl->assign('module_developer_lead', $this->_module->getInfo('developer_lead'));
        $this->tpl->assign('module_developer_contributor', $this->_module->getInfo('developer_contributor'));
        $this->tpl->assign('module_developer_website_url', $this->_module->getInfo('developer_website_url'));
        $this->tpl->assign('module_developer_website_name', $this->_module->getInfo('developer_website_name'));
        $this->tpl->assign('module_developer_email', $this->_module->getInfo('developer_email'));

        $people = $this->_module->getInfo('people');
        if ($people) {
            $this->tpl->assign('module_people_developers', isset($people['developers']) ? array_map(
                    array($this, '_sanitize'), $people['developers']) : false);
            $this->tpl->assign('module_people_testers', isset($people['testers']) ? array_map(
                    array($this, '_sanitize'), $people['testers']) : false);
            $this->tpl->assign('module_people_translators', isset($people['translators']) ? array_map(
                    array($this, '_sanitize'), $people['translators']) : false);
            $this->tpl->assign('module_people_documenters', isset($people['documenters']) ? array_map(
                    array($this, '_sanitize'), $people['documenters']) : false);
            $this->tpl->assign('module_people_other', isset($people['other']) ? array_map(
                    array($this, '_sanitize'), $people['other']) : false);
        }
        //$this->tpl->assign('module_developers', $this->module->getInfo('developer_email'));

        // Module Development information
        $this->tpl->assign('module_release_date', $this->_module->getInfo('release_date'));
        $this->tpl->assign('module_status', $this->_module->getInfo('module_status'));
        $this->tpl->assign('module_demo_site_url', $this->_module->getInfo('demo_site_url'));
        $this->tpl->assign('module_demo_site_name', $this->_module->getInfo('demo_site_name'));
        $this->tpl->assign('module_support_site_url', $this->_module->getInfo('support_site_url'));
        $this->tpl->assign('module_support_site_name', $this->_module->getInfo('support_site_name'));
        $this->tpl->assign('module_submit_bug', $this->_module->getInfo('submit_bug'));
        $this->tpl->assign('module_submit_feature', $this->_module->getInfo('submit_feature'));

        // Warning
        $this->tpl->assign('module_warning', $this->_sanitize($this->_module->getInfo('warning')));

        // Author's note
        $this->tpl->assign('module_author_word', $this->_module->getInfo('author_word'));

        // For changelog thanks to 3Dev
        $filename = XOOPS_ROOT_PATH . '/modules/' . $this->_module->getVar('dirname') . '/docs/changelog.txt';
        if (is_file($filename)) {
            $filesize = filesize($filename);
            $handle = fopen($filename, 'r');
            $this->tpl->assign('module_version_history', $this->_sanitize(fread($handle, $filesize)));
            fclose($handle);
        }

        if ($this->_logoImageUrl && $this->_logoLinkUrl) {
            $this->tpl->assign('logo_image_url', $this->_logoImageUrl);
            $this->tpl->assign('logo_link_url', $this->_logoLinkUrl);

        }
    }

    /**
     * @param string $value
     * @return string
     */
    private function _sanitize($value)
    {
        return MyTextSanitizer::getInstance()->displayTarea($value, 1);
    }

}