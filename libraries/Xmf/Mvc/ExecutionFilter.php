<?php
/*
 * This file has its roots as part of the Mojavi package which was
 * Copyright (c) 2003 Sean Kerr. It has been incorporated into this
 * derivative work under the terms of the LGPL V2.1.
 * (license terms)
 */

namespace Xmf\Mvc;

/**
 * ExecutionFilter is the main filter that does controls validation,
 * action execution and view rendering.
 *
 * @category  Xmf\Mvc\ExecutionFilter
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @author    Sean Kerr <skerr@mojavi.org>
 * @copyright 2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @copyright 2003 Sean Kerr
 * @license   GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     1.0
 */
class ExecutionFilter extends Filter
{

    /**
     * Create a new ExecutionFilter instance.
     *
     * @since  1.0
     */
    public function __construct ()
    {
        parent::__construct();
    }

    /**
     * Execute this filter.
     *
     *  _This method should never be called manually._
     *
     * @param FilterChain &$filterChain A FilterChain instance.
     *
     * @return void
     * @since  1.0
     */
    public function execute (&$filterChain)
    {
        // retrieve current action instance
        $execChain =& $this->Controller()->getExecutionChain();
        $action    =& $execChain->getAction($execChain->getSize() - 1);
        $actName   =  $this->Controller()->getCurrentAction();
        $unitName  =  $this->Controller()->getCurrentUnit();

        // get current method
        $method = $this->Request()->getMethod();

        // initialize the action
        if ($action->initialize()) {

            // does this action require authentication and authorization?
            if ($action->isSecure()) {

                // get authorization handler and required privilege
                $authHandler =& $this->Controller()->getAuthorizationHandler();

                if ($authHandler === null) {
                    // log invalid security notice
                    trigger_error(
                        'Action requires security but no authorization ' .
                        'handler has been registered',
                        E_USER_NOTICE
                    );
                } elseif (!$authHandler->execute($action)) {
                    // user doesn't have access
                    return;
                }

                // user has access or no authorization handler has been set

            }

            if (($action->getRequestMethods() & $method) != $method) {
                // this action doesn't handle the current request method,
                // use the default view
                $actView = $action->getDefaultView();
            } else {
                // create a ValidatorManager instance
                $validManager = new ValidatorManager;

                // register individual validators
                $action->registerValidators($validManager);

                // check individual validators, and if they succeed,
                // validate entire request
                if (!$validManager->execute()
                    || !$action->validate()
                ) {
                    // one or more individual validators failed or
                    // request validation failed
                    $actView = $action->handleError();
                } else {
                    // execute the action
                    $actView = $action->execute();
                }
            }

            if (is_string($actView) || $actView === null) {
                // use current action for view
                $viewUnit = $unitName;
                $viewAct  = $actName;
                $viewName = $actView;
            } elseif (is_array($actView)) {
                // use another action for view
                $viewUnit = $actView[0];
                $viewAct  = $actView[1];
                $viewName = $actView[2];
            }

            if ($viewName != \Xmf\Mvc::VIEW_NONE) {
                if (!$this->Controller()->viewExists($viewUnit, $viewAct, $viewName)) {
                    $error = 'Unit ' . $viewUnit . ' does not contain view ' .
                             $viewAct . 'View_' . $viewName . ' or the file is ' .
                             'not readable';

                    trigger_error($error, E_USER_ERROR);
                    exit;
                }

                // execute, render and cleanup view
                $view
                    = $this->Controller()->getView($viewUnit, $viewAct, $viewName);
                $test = $view->initialize(); // in 2.0.3b
                $renderer =& $view->execute();

                if ($renderer) {
                    $renderer->execute();
                }
                $view->cleanup();

                // add the renderer to the request
                $this->Request()->setAttributeByRef('org.mojavi.renderer', $renderer);

            }

        }

    }
}
