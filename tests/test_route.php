<?php

class TestRoute extends UnitTestCase
{
    public function setUp()
    {
        $this->route = new Route(array('routes' => realpath(dirname( __FILE__ ) . '/test_app/routes.php')));
    }

    protected function assertRouteResult($controller, $action, $params)
    {
        $result = $this->route->find_route();
        $this->assertEqual($controller, $result[0]);
        $this->assertEqual($action, $result[1]);
        $this->assertEqual($params, $result[2]);
    }

    public function testAddRouteAndFind()
    {
        $_SERVER['REQUEST_URI'] = '/test';
        $this->route->add('test', array('controller' => 'test'));
        $this->assertRouteResult('test', 'adefault', array());
    }

    public function testAddRouteWithActionAndFind()
    {
        $_SERVER['REQUEST_URI'] = '/test';
        $this->route->add('test', array('controller' => 'test', 'action' => 'foo'));
        $this->assertRouteResult('test', 'foo', array());
    }

    public function testAddAndFindWithQueryString()
    {
        $_SERVER['REQUEST_URI'] = '/test?bar=';
        $this->route->add('test', array('controller' => 'test', 'action' => 'foo'));
        $this->assertRouteResult('test', 'foo', array());
    }

    public function testAddRouteAndNoMatch()
    {
        $_SERVER['REQUEST_URI'] = '/test2';
        $this->route->add('test', array('controller' => 'test', 'action' => 'foo'));
        $this->assertRouteResult('', '', array());
    }

    public function testAddRouteWithParam()
    {
        $_SERVER['REQUEST_URI'] = '/test/norris';
        $this->route->add('test/:chuck', array('controller' => 'test', 'action' => 'foo', ':chuck' => '[a-z]+'));
        $this->assertRouteResult('test', 'foo', array('chuck' => 'norris'));
    }

    public function testAddRouteWithDifferentParam()
    {
        $_SERVER['REQUEST_URI'] = '/test/';
        $this->route->add('test/:chuck', array('controller' => 'test', 'action' => 'foo', ':chuck' => '[a-z]*'));
        $this->assertRouteResult('test', 'foo', array('chuck' => ''));
    }

    public function testRouteWithParamNoMatch()
    {
        $_SERVER['REQUEST_URI'] = '/test/';
        $this->route->add('test/:chuck', array('controller' => 'test', 'action' => 'foo', ':chuck' => '[a-z]+'));
        $this->assertRouteResult('', '', array());
    }

    public function testRouteWith2Params()
    {
        $_SERVER['REQUEST_URI'] = '/test/not/possible';
        $this->route->add('test/:chuck/:norris', array('controller' => 'test',
                                                       'action'     => 'foo',
                                                       ':chuck'     => '[a-z]+',
                                                       ':norris'    => '[a-z]+'));
        $this->assertRouteResult('test', 'foo', array('chuck'  => 'not',
                                                      'norris' => 'possible'));
    }

    public function testRouteParamNotRestrict()
    {
        $_SERVER['REQUEST_URI'] = '/test/not/possible';
        $this->route->add('test/:chuck/:norris', array('controller' => 'test',
                                                       'action'     => 'foo',
                                                       ':chuck'     => '(.*)',
                                                       ':norris'    => '[a-z]+'));
        $this->assertRouteResult('test', 'foo', array('chuck'  => 'not',
                                                      'norris' => 'not'));  // buggy
    }

    public function testAddRouteWithoutController()
    {
        $this->expectException(new Exception('Invalid route for path: /test'));
        $this->route->add('/test', array(''));
    }

    public function testRouteWithParamsNoRegexp()
    {
        $_SERVER['REQUEST_URI'] = '/test/not/possible';
        $this->route->add('test/:chuck/:norris', array('controller' => 'test', 'action'     => 'foo'));
        $this->assertRouteResult('test', 'foo', array('chuck'  => 'not', 'norris' => 'possible'));
    }

    public function testRouteWithParamsNoRegexpButDeclared()
    {
        $_SERVER['REQUEST_URI'] = '/test/not/possible';
        $this->route->add('test/:chuck/:norris', array('controller' => 'test',
                                                       'action'     => 'foo',
                                                       ':chuck', ':norris'));
        $this->assertRouteResult('test', 'foo', array('chuck'  => 'not', 'norris' => 'possible'));
    }
}
