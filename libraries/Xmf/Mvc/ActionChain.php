<?php

namespace Xmf\Mvc;

/**
 * This file has its roots as part of the Mojavi package which was
 * Copyright (c) 2003 Sean Kerr. It has been incorporated into this
 * derivative work under the terms of the LGPL V2.1.
 * (license terms)
 *
 * @author          Richard Griffith
 * @author          Sean Kerr
 * @copyright       The XOOPS Project http://sourceforge.net/projects/xoops/
 * @copyright       Portions Copyright (c) 2003 Sean Kerr
 * @license         (license terms)
 * @package         Xmf\Mvc
 * @since           1.0
 */

/**
 * An ActionChain allows execution of multiple actions and retrieving
 * the rendered results from that execution. Potential uses include
 * incoporating information from external Action implementations.
 *
 */
class ActionChain extends ContextAware
{

    /**
     * An associative array of actions.
     *
     * @since  1.0
     * @type   array
     */
    protected $actions;

    /**
     * Whether or not to preserve request parameters while actions are being
     * executed.
     *
     * @since  1.0
     * @type   bool
     */
    protected $preserve;

    /**
     * Create a new ActionChain instance.
     *
     * @since  1.0
     */
    public function __construct ()
    {

        $this->actions  = array();
        $this->preserve = FALSE;

    }

    /**
     * Execute all registered actions.
     *
     * _This method should never be called manually._
     *
     * @since  1.0
     */
    public function execute ()
    {

        $keys  = array_keys($this->actions);
        $count = sizeof($keys);

        // retrieve current render mode
        $renderMode = $this->Controller()->getRenderMode();

        // force all actions at this point to render to variable
        $this->Controller()->setRenderMode(\Xmf\Mvc::RENDER_VAR);

        for ($i = 0; $i < $count; $i++) {

            $action =& $this->actions[$keys[$i]];

            if ($this->preserve && $action['params'] != NULL) {

                // make a copy of the current variables if they exist
                $params   = array();
                $subKeys  = array_keys($action['params']);
                $subCount = sizeof($subKeys);

                for ($x = 0; $x < $subCount; $x++) {

                    if ($this->Request()->hasParameter($subKeys[$x])) {

                        // do not use a reference here
                        $params[$subKeys[$x]] = $this->Request()->getParameter($subKeys[$x]);

                    }

                }

            }

            if ($action['params'] != NULL) {

                // add replacement parameters to the request
                $subKeys  = array_keys($action['params']);
                $subCount = sizeof($subKeys);

                for ($x = 0; $x < $subCount; $x++) {

                    $this->Request()->setParameterByRef($subKeys[$x],
                                                $action['params'][$subKeys[$x]]);

                }

            }

            // execute/forward the action and retrieve rendered result
            $this->Controller()->forward($action['module'], $action['action']);

            // retrieve renderer for action
            $renderer =& $this->Request()->getAttribute('org.mojavi.renderer');

            // did the action render a view?
            if ($renderer !== NULL) {

                // retrieve rendered result
                $action['result'] = $renderer->fetchResult();

                // clear rendered result
                $renderer->clearResult();

                // remove renderer
                $this->Request()->removeAttribute('org.mojavi.renderer');

            }

            if (isset($params)) {

                // put copies of parameters back
                $subKeys  = array_keys($params);
                $subCount = sizeof($subKeys);

                for ($x = 0; $x < $subCount; $x++) {

                    $this->Request()->setParameterByRef($subKeys[$x],
                                                $params[$subKeys[$x]]);

                }

                unset($params);

            }

        }

        // put the old rendermode back
        $this->Controller()->setRenderMode($renderMode);

    }

    /**
     * Fetch the result of an executed action.
     *
     * @param string $regName An action registration name.
     *
     * @return string A rendered view, if the given action exists and did render
     *                a view, otherwise NULL.
     *
     * @since  1.0
     */
    public function & fetchResult ($regName)
    {

        if (isset($this->actions[$regName]['result'])) {
            return $this->actions[$regName]['result'];

        }
        $null = NULL;

        return $null;

    }

    /**
     * Register an action with the chain.
     *
     * @param string $regName An action registration name.
     * @param string $modName A module name.
     * @param string $actName An action name.
     * @param array  $params  An associative array of temporary request parameters.
     *
     * @since  1.0
     */
    public function register ($regName, $modName, $actName, $params = NULL)
    {

        $this->actions[$regName]['action'] = $actName;
        $this->actions[$regName]['module'] = $modName;
        $this->actions[$regName]['params'] = $params;

    }

    /**
     * Set the parameter preservation status.
     *
     * @param bool $preserve A preservation status.
     *
     * @since  1.0
     */
    public function setPreserve ($preserve)
    {

        $this->preserve = $preserve;

    }

}
