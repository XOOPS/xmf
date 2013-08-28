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
 * A Model defines a business process rule set consisting of
 * - business objects (one or more database objects and relating rules)
 * - presentation rules appropriate to input and display
 * - validation rules
 * - methods for specific actions unique to the business process
 * - retrieve rule sets approriate to a specific process step
 *   (i.e entry form, entry validation, etc.)
 * - triggers for announcing completion of specific process actions
 *   (i.e. new object created)
 * - workflow ?
 * - (more)
 *
 * Models are loaded and tracked by the Controller, and thus they are
 * available to any ContextAware object (such as Action and View.)
 *
 * @category  Xmf\Mvc\Model
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @copyright 2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @license   http://www.fsf.org/copyleft/gpl.html GNU public license
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     1.0
 */
abstract class Model extends ContextAware
{
    protected $object;

    /**
     * class constructor
     */
    public function __construct()
    {
        $object=null;
        die('not ready');
    }

    /**
     * initialize the model
     *
     * concrete implementations should establish the process model
     *
     * @param ModelManager &$modelManager controlling ModelManager instance
     *
     * @return bool true if model has initialized, otherwise false
     */
    abstract public function initalize(&$modelManager);

    /**
     * cleanup the model
     *
     * concrete implementations should cleanly close the process model
     *
     * @param ModelManager &$modelManager controlling ModelManager instance
     *
     * @return bool true if model has closed cleanly, otherwise false
     */
    abstract public function cleanup(&$modelManager);
}
