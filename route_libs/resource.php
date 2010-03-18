<?php

class RouteResource
{
    private $route = null;
    private $name = null;
    private $params = array();
    private $routes = array();
    private $member_tpl = '%s/:%s_id';
    private $collection_tpl = '%ss';
    private $default_controller_scheme = '%ss';
    private $default_members = array('', 'show', 'edit', 'update', 'delete');
    private $default_collections = array('', 'new', 'create');
    private $resource_routes = array(
                                      '%ss'             => array('controller' => '%s'),
                                      '%ss/new'         => array('controller' => '%s', 'action' => 'new'),
                                      '%ss/create'      => array('controller' => '%s', 'action' => 'create'),
                                      '%s/:id'          => array('controller' => '%s', 'action' => 'show',  ':id' => '\d+'),
                                      '%s/:id/edit'     => array('controller' => '%s', 'action' => 'edit',  ':id' => '\d+'),
                                      '%s/:id/update'   => array('controller' => '%s', 'action' => 'update',':id' => '\d+'),
                                      '%s/:id/delete'   => array('controller' => '%s', 'action' => 'delete',':id' => '\d+'),
                                      );
    private $members = array();
    private $collections = array();
    private $linked_namespace = null;
    private $linked_resource = null;

    public function __construct($resource, Array $params = array())
    {
        $this->route = HighwayToHeaven::instance();
        $this->name = $resource;
        if(isset($params['linked_namespace']))
        {
            $this->linkNamespace($params['linked_namespace']);
            unset($params['linked_namespace']);
        }
        $this->params = $params;
        $this->addDefaultCollections();
        $this->addDefaultMembers();
    }

    private function addDefaultMembers()
    {
        $default_members_to_set = $this->default_members;
        if(isset($this->params['only']))
            $default_members_to_set = array_intersect($this->params['only'], $default_members_to_set);
        if(isset($this->params['except']))
            $default_members_to_set = array_diff($default_members_to_set, $this->params['except']);
        foreach($default_members_to_set as $member)
            $this->addMember($member);
    }

    public function addMember($action, Array $params = array())
    {
        $default_scheme = array(
                                'controller' => sprintf($this->default_controller_scheme, $this->name),
                                'action' => empty($action) ? 'show' : $action,
                               );
        $scheme = array_merge($default_scheme, $params);
        $action = empty($action) ? '' : '/'.$action;
        $this->addRoute(sprintf($this->member_tpl.$action, $this->name, $this->name), $scheme);
    }

    private function addDefaultCollections()
    {
        $default_collections_to_set = $this->default_collections;
        if(isset($this->params['only']))
            $default_collections_to_set = array_intersect($this->params['only'], $default_collections_to_set);
        if(isset($this->params['except']))
            $default_collections_to_set = array_diff($default_collections_to_set, $this->params['except']);
        foreach($default_collections_to_set as $collection)
        {
            $params = array();
            if($collection == 'new') $params['action'] = 'anew';
            $this->addCollection($collection, $params);
        }
    }

    public function addCollection($action, Array $params = array())
    {
        $default_scheme['controller'] = sprintf($this->default_controller_scheme, $this->name);
        if(!empty($action))
            $default_scheme['action'] = $action;
        $scheme = array_merge($default_scheme, $params);
        $action = empty($action) ? '' : '/'.$action;
        $this->addRoute(sprintf($this->collection_tpl.$action, $this->name, $this->name), $scheme);
    }

    public function linkNamespace(RouteNamespace $namespace)
    {
        $this->linked_namespace = $namespace;
    }

    public function linkResource(RouteResource $resource)
    {
        $this->linked_resource = $resource;
    }

    private function addRouteToLinkedNamespace($route)
    {
        $this->linked_namespace->add($route, $this->routes[$route]);
    }

    private function addRoute($route, $scheme)
    {
        //we could define route names the following way
        //$route_name = isset($scheme[':id']) ? $this->name.'_'.$scheme['action'] : $this->name.'s';
        $this->routes[$route] = $scheme;
        if(!is_null($this->linked_namespace))
            $this->addRouteToLinkedNamespace($route);
        elseif(!is_null($this->linked_resource))
            $this->addRouteToLinkedResource($route);
        else
            $this->route->add($route, $scheme);
    }
}
