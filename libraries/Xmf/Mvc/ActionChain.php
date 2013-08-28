<?php
/*
 * This file has its roots as part of the Mojavi package which was
 * Copyright (c) 2003 Sean Kerr. It has been incorporated into this
 * derivative work under the terms of the LGPL V2.1.
 * (license terms)
 */

namespace Xmf\Mvc;

/**
 * An ActionChain allows execution of multiple actions and retrieving
 * the rendered results from that execution. Potential uses include
 * incoporating information from external Action implementations.
 *
 * @category  Xmf\Mvc\ActionChain
 * @package   Xmf
 * @author    Richard Griffith <richard@geekwright.com>
 * @author    Sean Kerr <skerr@mojavi.org>
 * @copyright 2013 The XOOPS Project http://sourceforge.net/projects/xoops/
 * @copyright 2003 Sean Kerr
 * @license   http://www.fsf.org/copyleft/gpl.html GNU public license
 * @version   Release: 1.0
 * @link      http://xoops.org
 * @since     1.0
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
        $this->preserve = false;

    }

    /**
     * Execute all registered actions.
     *
     * _This method should never be called manually._
     *
     * @return void
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

            if ($this->preserve && $action['params'] != null) {

                // make a copy of the current variables if they exist
                $params   = array();
                $subKeys  = array_keys($action['params']);
                $subCount = sizeof($subKeys);

                for ($x = 0; $x < $subCount; $x++) {

                    if ($this->Request()->hasParameter($subKeys[$x])) {

                        // do not use a reference here
                        $params[$subKeys[$x]]
                            = $this->Request()->getParameter($subKeys[$x]);

                    }

                }

            }

            if ($action['params'] != null) {

                // add replacement parameters to the request
                $subKeys  = array_keys($action['params']);
                $subCount = sizeof($subKeys);

                for ($x = 0; $x < $subCount; $x++) {

                    $this->Request()->setParameterByRef(
                        $subKeys[$x],
                        $action['params'][$subKeys[$x]]
                    );

                }

            }

            // execute/forward the action and retrieve rendered result
            $this->Controller()->forward($action['unit'], $action['action']);

            // retrieve renderer for action
            $renderer =& $this->Request()->getAttribute('org.mojavi.renderer');

            // did the action render a view?
            if ($renderer !== null) {

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

                    $this->Request()->setParameterByRef(
                        $subKeys[$x],
                        $params[$subKeys[$x]]
                    );

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
        $null = null;

        return $null;

    }

    /**
     * Register an action with the chain.
     *
     * @param string $regName  An action registration name.
     * @param string $unitName A unit name.
     * @param string $actName  An action name.
     * @param array  $params   Associative array of temporary request
     *                         parameters.
     *
     * @return void
     */
    public function register ($regName, $unitName, $actName, $params = null)
    {

        $this->actions[$regName]['action'] = $actName;
        $this->actions[$regName]['unit'] = $unitName;
        $this->actions[$regName]['params'] = $params;

    }

    /**
     * Set the parameter preservation status.
     *
     * @param bool $preserve A preservation status.
     *
     * @return void
     */
    public function setPreserve ($preserve)
    {

        $this->preserve = $preserve;

    }
}
