<?php

class RouteNamespace
{
    private $route = null;
    private $name = null;
    private $params = array();
    private $current_route = null;
    private $routes = array();

    public function __construct($namespace, Array $params = array())
    {
        $this->route = HighwayToHeaven::instance();
        $this->name = $namespace;
        $this->params = $params;
    }

    public function add($route, Array $scheme = array())
    {
        $this->current_route = array('route' => $route, 'scheme' => $scheme);
        $this->prepareRoute();
        $this->addRoute($this->current_route['route'], $this->current_route['scheme']);
        $this->current_route = null;
    }

    public function addResource($resource, Array $params = array())
    {
        $params['linked_namespace'] = $this;
        return new RouteResource($resource, $params);
    }

    private function addRoute($route, $scheme)
    {
        //we could define route names the following way
        //$route_name = isset($scheme[':id']) ? $this->name.'_'.$scheme['action'] : $this->name.'s';
        $this->routes[$route] = $scheme;
        $this->route->add($route, $scheme);
    }

    private function prepareRoute()
    {
        $this->prepareControllerPrefix();
        $this->current_route['route'] = implode('/', array($this->name, $this->current_route['route']));
    }

    private function prepareControllerPrefix()
    {
        $prefix = isset($this->params['controller_prefix']) ? $this->params['controller_prefix'] : ucfirst(strtolower($this->name));
        $controller = !isset($this->current_route['scheme']['controller']) ? $this->current_route['route'] : $this->current_route['scheme']['controller'];
        $this->current_route['scheme']['controller'] = empty($prefix) ? $controller : $prefix.ucfirst($controller);
    }
}
