<?php
/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

namespace Xmf\Mvc\Lib;

use Xmf\Language;
use Xmf\Loader;

/**
 * Form provides form support using instructions found in model.
 *
 * @category  Xmf\Mvc\Lib\Form
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license   http://www.fsf.org/copyleft/gpl.html GNU public license
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     1.0
 */
class Form extends \Xmf\Mvc\ContextAware
{

    /**
     * class constructor
     */
    public function __construct()
    {
        if (!class_exists('XoopsThemeForm', true)) {
            Loader::loadFile(XOOPS_ROOT_PATH.'/class/xoopsformloader.php');
        }
        Language::load('form', 'xmf');
    }

    /**
     * build a form from a definition
     *
     * @param string $form_attribute name of Request attribute contain definition
     *
     * @return XoopsThemeForm
     */
    protected function buildForm($form_attribute)
    {
        $errors = $this->Request()->getErrors();

        $form_definition=$this->Request()->getAttribute($form_attribute);

        $formdef=empty($form_definition['form'])? array() : $form_definition['form'];
        if (empty($formdef['name'])) {
            $formdef['name'] = 'form';
        }
        if (empty($formdef['title'])) {
            $formdef['title'] = '(Untitled)';
        }
        if (empty($formdef['action'])) {
            $formdef['action'] = '';
        }
        if (empty($formdef['method'])) {
            $formdef['method'] = 'post';
        }
        if (empty($formdef['addtoken'])) {
            $formdef['addtoken'] = true;
        }

        $fields=$form_definition['fields'];
        $elements=array();

        $form = new \XoopsThemeForm(
            $formdef['title'],
            $formdef['name'],
            $formdef['action'],
            $formdef['method'],
            $formdef['addtoken']
        );

        foreach ($fields as $fieldname => $fielddef) {
            $value = $this->Request()->getAttribute($fieldname);
            $size=$fielddef['length'];
            $size=($size>35?30:$size);
            if ($value==null) {
                $value = $this->Request()->getParameter($fieldname, $fielddef['default']);
            }
            $value=htmlentities($value, ENT_QUOTES);
            $caption = $fielddef['description'];
            if (!empty($errors[$fieldname])) {
                $caption .= '<br /> - <span style="color:red;">'.$errors[$fieldname].'</span>';
            }
            switch ($fielddef['input']['form']) {
                case 'text':
                    $form->addElement(
                        new \XoopsFormText($caption, $fieldname, $size, $fielddef['length'], $value),
                        $fielddef['required']
                    );
                    break;
                case 'editor':
                    $form->addElement(
                        new \XoopsFormDhtmlTextArea(
                            $caption,
                            $fieldname,
                            $value,
                            $fielddef['input']['height'],
                            $fielddef['input']['width']
                        ),
                        $fielddef['required']
                    );
                    break;
                case 'textarea':
                    $form->addElement(
                        new \XoopsFormTextArea(
                            $caption,
                            $fieldname,
                            $value,
                            $fielddef['input']['height'],
                            $fielddef['input']['width']
                        ),
                        $fielddef['required']
                    );
                    break;
                case 'password':
                    $form->addElement(
                        new \XoopsFormPassword($caption, $fieldname, $size, $fielddef['length'], $value),
                        $fielddef['required']
                    );
                    break;
                case 'select':
                    $elements[$fieldname] = new \XoopsFormSelect($caption, $fieldname, $value);
                    $elements[$fieldname] -> addOptionArray($fielddef['input']['options']);
                    $form->addElement($elements[$fieldname], $fielddef['required']);
                    break;
                case 'hidden':
                    $form->addElement(new \XoopsFormHidden($fieldname, $value));
                    break;
            }
        }

        $form->addElement(
            new \XoopsFormButton('', 'submit', _FORM_XMF_SUBMIT, 'submit')
        );

        return $form;
    }

    /**
     * render a form
     *
     * @param string $form_attribute name of Request attribute contain definition
     *
     * @return string rendered form
     */
    public function renderForm($form_attribute)
    {
        $form=$this->buildForm($form_attribute);

        return $form->render();
    }

    /**
     * assign form to smarty template
     *
     * @param string $form_attribute name of Request attribute contain definition
     *
     * @return void
     */
    public function assignForm($form_attribute)
    {
        global $xoopsTpl;

        $form=$this->buildForm($form_attribute);
        $form->assign($xoopsTpl);
    }
}
