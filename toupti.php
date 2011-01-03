<?php
/* Copyright (c) 2009, Arnaud Berthomier
 * Copyright (c) 2009-2010, AF83
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the University of California, Berkeley nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHORS AND CONTRIBUTORS ``AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE AUTHORS AND CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * @package Toupti
 */
class TouptiException extends Exception {}

/**
 * Toupti: yet another micro-framework made out of PHP.
 * @package Toupti
 * @author  Arnaud Berthomier <oz@cyprio.net>
 */
class Toupti
{
    /**
     * Parameters from _GET, _POST, and defined routes.
     */
    protected $params = array();

    /**
     * Request info
     */
    public $request = null;

    /**
     * Response info
     */
    public $response = null;

    /**
     *
     */
    public $controller = null;

    /**
     * The action we'll want to run...
     */
    public $action = null;

    /**
     * route key who solve the request.
     */
    public $path_key = null;
    
    /**
     * Routing setup
     */
    private $route = null;

    private static $_instance = null;

    /**
     * Toupti constructor
     */
    public function __construct()
    {
    }

    public function get_params()
    {
        return $this->params;
    }

    /**
     * Dispatch browser query to the appropriate action.
     * This our "main" entry point.
     * @todo move $route params to the constructor.
     * @return void
     */
    public function run($route, $req, $res)
    {
        $this->route = $route;
        $this->request = $req;
        $this->response = $res;
        $this->route->setRequest($this->request);

        // Find an action for the query, and set params accordingly.
        list($controller, $action, $params, $path_key) = $this->route->find_route();

        // Update ourself
        $this->controller = $controller;
        $this->action = $action;
        $this->path_key = $path_key;

        // Merge route params with POST/GET values
        $params = array_merge($params, $_POST, $_GET); // FIXME Possible CSRF attacks here
        $this->request->params = $params;

        // Dispatch the routed action !
        if (isset($controller) && isset($action)) {
            $controller_class = ucfirst($controller)."Controller";
            $this->call_action($controller_class, $action);
        } else {
            throw new TouptiException('Error 404 '. $this->request->original_uri, 404);
        }

    }

    /**
     * Call a user action
     *
     * @param  string  $controller_name Name of the controller to call
     * @param  string  $method_name
     * @param  Array   $params Request parameters
     * @todo, moving this to middleware would be great.
     */
    private function call_action($controller_name, $method_name)
    {
        if($controller_name != 'Controller' && class_exists($controller_name, true))
        {
            Controller::setToupti($this);
            Logs::debug('XXX: '.Controller::$req);
            $controller = new $controller_name();
            if(method_exists($controller, $method_name))
            {
                if($controller->isAuthorized($method_name))
                {
                    $this->response->body = $controller->$method_name();
                    // \o/ good job, can exit now
                    return;
                }
                else
                {
                    throw new TouptiException('access_not_allowed', 403);
                }
            } else {
                throw new TouptiException('Route error. Action '. $method_name  .' not exist in '. $controller_name . ' for '. $this->request->original_uri . '.', 404);
            }
        }
        throw new TouptiException('Route error. Controller '. $controller_name . ' not found for '. $this->request->original_uri . '.', 404);
    }

}
