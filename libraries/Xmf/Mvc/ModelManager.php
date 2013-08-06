<?php

namespace Xmf\Mvc;

/*
 You may not change or alter any portion of this comment or credits
 of supporting developers from this source code or any supporting source code
 which is considered copyrighted (c) material of the original comment or credit authors.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 */

/**
 * Xmf\Mvc\ModelManager abstract model interface
 *
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license         http://www.fsf.org/copyleft/gpl.html GNU private license
 * @package         Xmf\Mvc
 * @since           1.0
 * @author          Richard Griffith
 */

/**
 * A ModelManager manages the loading, start up and shut down of models.
 *
 */
class ModelManager extends ContextAware
{
    protected $models;
    protected $modelorder;

    public function __construct()
    {
        $this->models=array();
        $this->modelorder=array();
    }

    /**
     * Return a model instance.
     *
     * @param string $name    - A model name.
     * @param string $modName - A unit (module) name, defaults to current unit
     *
     * @return a Model instance.
     */
    public function &loadModel ($name, $unitName='')
    {

        if (empty($unitName)) { $unitName = $this->Controller()->currentModule; }
        if (empty($this->models[$unitName][$name])) {
            $file = $this->Controller()->getComponentName ('model', $unitName, $name, '');
            $this->Controller()->loadRequired($file);

            $model =  $name; // no suffix
            // fix for same name views
            $unitModel = $unitName . '_' . $model;
            if (class_exists($unitModel)) {
                $model =& $unitModel;
            }

            $this->models[$unitName][$name]=new $model;

            $this->modelorder[]=array('unit'=>$unitName,'name'>$name);

            $this->models[$unitName][$name]->initialize();

        }

        return $this->models[$unitName][$name];

    }

    /**
     * Shutdown the ModelManager
     *
     */
    public function shutdown()
    {
        foreach ($this->modelorder as $model) {
            $this->models[$model['unit']][$model['name']]->cleanup();
        }

    }

}
