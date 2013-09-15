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
 * A ModelManager manages the loading, start up and shut down of models.
 *
 * @category  Xmf\Mvc\ModelManager
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/old-licenses/gpl-2.0.html)
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     1.0
 */
class ModelManager extends ContextAware
{
    protected $models;
    protected $modelorder;

    /**
     * class constructor
     */
    public function __construct()
    {
        $this->models=array();
        $this->modelorder=array();
    }

    /**
     * Return a model instance.
     *
     * @param string $name     - A model name.
     * @param string $unitName - A unit name, defaults to current unit
     *
     * @return a Model instance.
     */
    public function &loadModel($name, $unitName = '')
    {

        if (empty($unitName)) {
            $unitName = $this->Controller()->currentUnit;
        }
        if (empty($this->models[$unitName][$name])) {
            $file = $this->Controller()->getComponentName('model', $unitName, $name, '');
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
     * @return void
     */
    public function shutdown()
    {
        foreach ($this->modelorder as $model) {
            $this->models[$model['unit']][$model['name']]->cleanup();
        }

    }
}
