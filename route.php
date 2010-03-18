<?php
/**
 * @package Toupti
 */
class RouteException extends Exception {}
/**
 * @package Toupti
 */
class HighwayToHeaven
{

    private $request = null;

    private static $instance = null;

    private $app_root = null;

    private $routes = array('' => 'index', ':action' => ':action');

    private function __construct()
    {
    }

    public static function destroy()
    {
        HighwayToHeaven::$instance = NULL;
    }

    public static function instance()
    {
        if(is_null(HighwayToHeaven::$instance))
            HighwayToHeaven::$instance = new HighwayToHeaven();
        return HighwayToHeaven::$instance;
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

    public function addNamespace($namespace, Array $params = array())
    {
        return new RouteNamespace($namespace, $params);
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
        $query = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
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
                if(class_exists('Logs'))
                    Logs::debug("matched: " . $rx . " controller: " . $action . " action: " . $method);
                if ( count($matches) > 1 ) {
                    $params = $this->get_route_params($matches, $route);
                    unset($params['controller']);     // don't pollute $params
                }
                break;
            }
        }
        return array($action, $method, $params);
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

}
