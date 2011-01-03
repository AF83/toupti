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
* Toupti Route
* @link    http://github.com/af83
* @link    http://dev.af83.com
* @copyright  af83
*
* @package Toupti
* @author AF83 Arnaud Berthommier, François de Metz, Gilles Robit, Luc-Pascal Ceccaldi, Ori Pekleman
*/
/**
 * @package Toupti
 */
class RouteException extends Exception {}
/**
 * @package Toupti
 */
class Route
{

    private $request = null;

    private $app_root = null;

    private $routes = array('' => 'index', ':action' => ':action');

    public function __construct()
    {
    }


    public function setRequest(RequestMapper $request)
    {
        if(!is_null($this->request)) throw New RouteException('Request is already set for this instance');
        $this->request = $request;
    }

    public function add($route, Array $scheme = array())
    {
        /**
         * if nothing defined, controller is the name of the route
         * FIXME, this can be dangerous if you give a strange route, should be checked and throw an exception
         */
        if(empty($scheme))
        {
            $scheme = array('controller' => $route);
        }
        // default HTTP method is GET
        if(!array_key_exists('method', $scheme)) {
            $scheme['method'] = 'GET';
        }
        // default action
        if(!array_key_exists('action', $scheme)) {
            $scheme['action'] = 'adefault';
        }

        $this->add_route($route, $scheme);
    }

    /**
     * Add a new route to the internal dispatcher.
     *
     * @param  String  $path    Route path : a key from the user's routes
     * @param  mixed   $scheme  Which controller to take for this $path
     * @return Void
     */
    private function add_route($path, $scheme)
    {
        $route = array('path'   => $path,
            'rx'     => '',
            'method'     => null,
            'controller' => null,
            'action'=> null);

        if ( empty($scheme['controller']) )
        {
            throw new Exception('Invalid route for path: ' . $path, true);
        }

        // Escape path for rx (XXX use preg_quote ?)
        $rx = str_replace('/', '\/', $path);

        // named path
        if ( strstr($path, ':') )
        {
            $matches = null;

            if ( preg_match_all('/:\w+/', $rx, $matches) )
            {
                foreach ( $matches[0] as $match )
                {
                    $group = isset($scheme[$match]) ? $scheme[$match] : '\w+';
                    $rx = preg_replace('/'.$match.'/', '('.$group.')', $rx);
                }
            }
        }

        // splat path
        if ( strstr($path, '*') )
        {
            $matches = null;

            if ( preg_match_all('/\*/', $rx, $matches) )
            {
                $rx = str_replace('*', '(.*)', $rx);
            }
        }

        $route['rx'] = '\/' . $rx . '\/?';
        $route['controller'] = $scheme['controller'];
        $route['action'] = $scheme['action'];

        // Add new route
        $this->_routes [] = $route;
    }

    /**
     * Try to map browser request to one of the defined routes
     *
     * @return  Array   [0] => 'controller name', [1] => 'action_name', [2] => array( params... )
     */
    public function find_route()
    {
        $action = null;
        $method = null;
        $params = array();

        // Get the query string without the eventual GET parameters passed.
        $query = isset($this->request->resource) ? $this->request->resource : '';
        if ( $offset = strpos($query, '?') )
        {
            $query = substr($query, 0, $offset);
        }

        // Try each route
        foreach ( $this->_routes as $route )
        {
            $rx = '/^' . $route['rx'] . '$/';
            $matches = array();

            // Found a match ?
            if ( preg_match($rx, $query, $matches) ) {

                $params = array();
                $action = $route['controller'];
                $method = $route['action'];
                $path_key = $route['path'];
                // Logs::debug("matched: " . $rx . " controller: " . $action . " action: " . $method);
                if ( count($matches) > 1 ) {
                    $params = $this->get_route_params($matches, $route);
                    unset($params['controller']);     // don't pollute $params
                }
                break;
            }
        }
        return array($action, $method, $params, $path_key);
    }

    /**
     * Extract params from the request with the corresponding path matches
     *
     * @param   Array    $matches    preg_match $match array
     * @param   Array    $route      corresponding route array
     * @return  Array    Hash of request values, with param names as keys.
     */
    private function get_route_params($matches, $route)
    {
        $params      = array();
        $path_parts  = array();
        $param_count = 0;
        $path_array  = explode('/', $route['path']);

        // Handle each route modifier...
        foreach ( $path_array as $key => $param_name )
        {
            // Handle splat parameters (regexps like '.*')
            if ( substr($param_name, 0, 1) == '*' )
            {
                ++$param_count;
                if ( ! isset($params['splat']) ) $params['splat'] = array();
                $params['splat'] []= $matches[$param_count];
                continue;
            }

            // Don't treat non-parameters as parameters
            if ( $param_name[0] != ":")
            {
                continue;
            }

            // Extract param value
            ++$param_count;
            if ( isset($matches[$param_count]) )
            {
                $name = substr($param_name, 1, strlen($param_name));
                $params[$name] = $matches[$param_count];
            }


        }

        if ( !array_key_exists('controller', $params) )
        {
            // This permits the value of a :named_match to be the routed action
            if ( $route['controller'][0] == ':' )
            {
                $key = substr($route['controller'], 1, strlen($route['controller']));

                if ( array_key_exists($key, $params) )
                    $params['controller'] = $params[$key];
            }

            /*
             * Check for an explicit controller-name in route, if
             * no :action parameter was found inside the route rx.
             */
            if ( empty($params['controller']) )
            {
                $params['controller'] = $route['controller'];
            }
        }
        return $params;
    }
    
    public function getComputedRoutes() {
        return $this->_routes;
    }
}
