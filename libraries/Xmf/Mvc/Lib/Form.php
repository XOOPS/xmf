<?php

namespace Xmf\Mvc\Lib;

/**
 * Form provides form support using instructions found in model.
 *
 * @author          Richard Griffith
 * @package         Xmf\Mvc
 * @since           1.0
 */

require_once(XOOPS_ROOT_PATH.'/class/xoopsformloader.php');

class Form extends \Xmf\Mvc\ContextAware
{

    public function __construct()
    {
        \Xmf\Language::load('form','xmf');
    }

    private function buildForm($form_attribute)
    {
        $errors = $this->Request()->getErrors();

        $form_definition=$this->Request()->getAttribute($form_attribute);

        $formdef=empty($form_definition['form'])? array() : $form_definition['form'];
        if (empty($formdef['name'])) { $formdef['name']     = 'form'; }
        if (empty($formdef['title'])) { $formdef['title']    = '(Untitled)'; }
        if (empty($formdef['action'])) { $formdef['action']   = ''; }
        if (empty($formdef['method'])) { $formdef['method']   = 'post'; }
        if (empty($formdef['addtoken'])) { $formdef['addtoken'] = true; }

        $fields=$form_definition['fields'];
        $elements=array();

        $form = new \XoopsThemeForm($formdef['title'], $formdef['name'], $formdef['action'], $formdef['method'], $formdef['addtoken']);

        foreach ($fields as $fieldname => $fielddef) {
            $value = $this->Request()->getAttribute ($fieldname);
            $size=$fielddef['length'];
            $size=($size>35?30:$size);
            if($value==null) $value = $this->Request()->getParameter($fieldname, $fielddef['default']);
            $value=htmlentities($value,ENT_QUOTES);
            $caption = $fielddef['description'];
            if (!empty($errors[$fieldname])) {
                $caption .= '<br /> - <span style="color:red;">'.$errors[$fieldname].'</span>';
            }
            switch ($fielddef['input']['form']) {
                case 'text':
                    $form->addElement(new \XoopsFormText($caption, $fieldname, $size, $fielddef['length'], $value), $fielddef['required']);
                    break;
                case 'editor':
                    //$form->addElement(new XoopsFormEditor ($caption, $name, $configs=null, $nohtml=false, $OnFailure= ''), $fielddef['required']);
                    $form->addElement(new \XoopsFormDhtmlTextArea($caption, $fieldname, $value, $fielddef['input']['height'], $fielddef['input']['width']), $fielddef['required']);
                    break;
                case 'textarea':
                    $form->addElement(new \XoopsFormTextArea($caption, $fieldname, $value, $fielddef['input']['height'], $fielddef['input']['width']), $fielddef['required']);
                    break;
                case 'password':
                    $form->addElement(new \XoopsFormPassword($caption, $fieldname, $size, $fielddef['length'], $value), $fielddef['required']);
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

        $form->addElement(new \XoopsFormButton('', 'submit', _FORM_XMF_SUBMIT, 'submit'));

        return $form;
    }

    public function renderForm($form_attribute)
    {
        $form=$this->buildForm($form_attribute);

        return $form->render();
    }

    public function assignForm($form_attribute)
    {
        global $xoopsTpl;

        $form=$this->buildForm($form_attribute);
        $form->assign($xoopsTpl);
    }
}
