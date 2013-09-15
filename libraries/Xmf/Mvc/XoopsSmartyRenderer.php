<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf\Mvc;

/**
 * The XoopsSmartyRenderer is a XOOPS specific renderer that uses XOOPS
 * Smarty templates and the standard $xoopsTpl mechanisms for page
 * rendering. Renderer attributes become Smarty assigned variables,
 * and the actual display is handled by the normal XOOPS cycle.
 *
 * @category  Xmf\Mvc\XoopsSmartyRenderer
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     1.0
 */
class XoopsSmartyRenderer extends Renderer
{

    /** signal that we used a default template, just dump attributes */
    private $_dumpmode;

    /**
     * Create a new Renderer instance.
     *
     * @since  1.0
     */
    public function __construct ()
    {
        parent::__construct();
        $this->_dumpmode   = false;
    }

    /**
     * Render the view.
     *
     * We actually just
     * - make sure that a template is set
     * - assign attributes to smarty variables
     *
     * @return void
     * @since  1.0
     */
    public function execute ()
    {
        global $xoopsTpl, $xoopsOption;
        if ($this->template == null) {
            if (empty($xoopsOption['template_main'])) {
                $this->template = 'db:system_dummy.html';
                $this->_dumpmode   = true;
            } else {
                $this->template = $xoopsOption['template_main'];
            }
        }

        // make it easier to access data directly in the template
        $mojavi   =& $this->controller()->getMojavi();
        $template =& $this->attributes;
        if ($this->_dumpmode) {
            $template['dummy_content']
                ='<pre>' . print_r($this->attributes, true) . '</pre>';
        } else {
            $template =& $this->attributes;
        }

        if ($this->mode == \Xmf\Mvc::RENDER_VAR
            || $this->Controller()->getRenderMode() == \Xmf\Mvc::RENDER_VAR
        ) {
            $varRender = new XoopsTplRender;
            $varRender->setXTemplate($this->template);
            foreach ($template as $k => $v) {
                $varRender->setAttribute($k, $v);
            }
            $varRender->setAttribute('xmfmvc', $mojavi);
            $this->result=$varRender->fetch();
            // echo $this->result;

        } else {
            $GLOBALS['xoopsOption']['template_main'] = $this->template;
            // the following is to make footer.php quit complaining
            if (false === strpos($xoopsOption['template_main'], ':')) {
                $GLOBALS['xoTheme']->contentTemplate
                    = 'db:' . $xoopsOption['template_main'];
            } else {
                $GLOBALS['xoTheme']->contentTemplate = $xoopsOption['template_main'];
            }

            foreach ($template as $k => $v) {
                $xoopsTpl->assign($k, $v);
            }
            $xoopsTpl->assign('xmfmvc', $mojavi);
            // templates and values are assigned, XOOPS will handle the rest
        }

    }

    // These following are unique to XoopsSmartyRenderer

    /**
     * Add Stylesheet
     *
     * @param string $stylesheet URL of CSS stylesheet
     *
     * @return void
     * @since  1.0
     */
    public function addStylesheet($stylesheet)
    {
        if (is_object($GLOBALS['xoTheme'])) {
            $GLOBALS['xoTheme']->addStylesheet($stylesheet);
        }
    }

    /**
     * Add Script
     *
     * @param string $script URL to javascript file
     *
     * @return void
     * @since  1.0
     */
    public function addScript($script)
    {
        if (is_object($GLOBALS['xoTheme'])) {
            $GLOBALS['xoTheme']->addScript($script);
        }
    }

    /**
     * Add Page Title
     *
     * @param string $pagetitle page title
     *
     * @return void
     * @since  1.0
     */
    public function addPageTitle($pagetitle)
    {
        assign('xoops_pagetitle', htmlspecialchars($pagetitle));
    }

    /**
     * Add meta tag for keywords
     *
     * @param mixed $keywords meta keywords to include
     *
     * @return void
     * @since  1.0
     */
    public function addMetaKeywords($keywords)
    {
        if (is_array($keywords)) {
            $keywords=implode(',', $keywords);
        }
        if (is_object($GLOBALS['xoTheme'])) {
            $GLOBALS['xoTheme']->addMeta(
                'meta',
                'keywords',
                htmlspecialchars($keywords, ENT_QUOTES, null, false)
            );
        }
    }

    /**
     * Add meta tag for description
     *
     * @param string $description meta description
     *
     * @return void
     * @since  1.0
     */
    public function addMetaDescription($description)
    {
        if (is_object($GLOBALS['xoTheme'])) {
            $GLOBALS['xoTheme']->addMeta(
                'meta',
                'description',
                htmlspecialchars($pageX['meta_description'], ENT_QUOTES, null, false)
            );
        }
    }
}
