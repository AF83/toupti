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

    private $named_routes = array();

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
        if(isset($scheme['route_name']))
            $this->nameRouteAfter($path, $scheme['route_name']);
    }

    public function __call($name, $arguments)
    {
        $matches = array();
        if(preg_match('/(?P<name>\w+)_(?P<type>url|path)$/', $name, $matches))
            return $this->buildNamedRouteFor($matches, $arguments);
        throw new Exception(sprintf('The method %s does not exist in %s', $name, get_class($this)));
    }

    private function nameRouteAfter($path, $route_name)
    {
        if(!isset($this->named_routes[$route_name]))
            $this->named_routes[$route_name] = $path;
        else
            throw new TouptiException(sprintf('The route name %s is already defined and leads to %s', $route_name, $this->named_routes[$route_name]));
    }

    private function prepareNamedRouteParams($params)
    {
        $ret = array();
        foreach($params as $param)
        {
            if(is_object($param))
            {
                if(!($param instanceof Resourceable)) throw new TouptiException('The parameters given to build a route should be Resourcable Objects');
                $ret[sprintf(':%s_id', $param->getResourceName())] = $param->getResourceIdentifier();
            }
            if(is_array($param))
            {
                foreach($param as $key => $val)
                {
                    if($key[0] == ':')
                        $ret[$key] = $val;
                }
            }
        }
        $this->named_route_params = $ret;
        return $ret;
    }

    private function replace_callbck($matches)
    {
        if(!isset($matches['identifier']))
            $matches['identifier'] = $matches[1];
        if(!isset($this->named_route_params[$matches['identifier']]))
            throw new TouptiException(sprintf('%s url parameter required but was not found', $matches["identifier"]));
        $this->found_matches[$matches['identifier']] = $this->named_route_params[$matches['identifier']];
        return $this->named_route_params[$matches['identifier']].'/';
    }

    private function buildNamedRouteFor($route_name_type, Array $params)
    {
        $this->prepareNamedRouteParams($params);
        extract($route_name_type);//initiate $name and $type vars
        if(!isset($this->named_routes[$name]))
            throw new TouptiException(sprintf('The route %s does not exist', $name));
        $this->found_matches = array();
        $path = '/'.preg_replace_callback('/(?P<identifier>:[^\/]+)\/?/', 
                                       array($this, 'replace_callbck'),
                                       $this->named_routes[$name]);
        $query_string = $this->buildNamedRouteQueryString(array_diff($this->named_route_params, $this->found_matches));
        $path .= $query_string;
        unset($this->found_matches);
        unset($this->named_route_params);
        if($type == 'path')
            return $path;
        if($type == 'url')
            return $_SERVER['HTTP_HOST'].$path;//FIXME should use toupti request object
    }

    private function buildNamedRouteQueryString($params)
    {
        $to_implode = array();
        foreach($params as $key => $value)
        {
            $key = $key[0] == ':' ? substr($key, 1) : $key;
            $to_implode []= sprintf('%s=%s', $key, $value);
        }
        if(!empty($to_implode))
            return sprintf('?%s', implode('&', $to_implode));
        return '';
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

    public function urlFor($resource_name, Array $params = array())
    {
        if(isset($params['object']) && !($params['object'] instanceof Resourceable)) throw new TouptiException('The object should be Resourceable');

        $to_implode = array();
        if(isset($params['namespace']) && !empty($params['namespace']))
            $to_implode []= $params['namespace'];
        if(isset($params['action']))
            $to_implode []= $params['action'];
        $r = $resource_name;
        $r .= !isset($params['object']) ? 's' : '';
        $to_implode []= $r;
        $to_implode []= 'path';
        $path_method = implode('_', $to_implode);
        $other_params = array();
        foreach($params as $key => $value)
        {
            if($key[0] == ':')
                $other_params[$key] = $value;
        }
        if(!isset($params['object']) && empty($other_params))
            return call_user_func(array($this, $path_method)); 
        else
        {
            if(isset($params['object']))
                return call_user_func_array(array($this, $path_method), array($params['object'], $other_params)); 
            else
                return call_user_func_array(array($this, $path_method), array($other_params)); 
        }
    }

}
