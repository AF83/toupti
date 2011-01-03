<?php

class MyController extends Controller
{
    public function isAuthorized($method_name)
    {
        if ($method_name != 'action_403')
            return true;
        return false;
    }

    public function adefault()
    {
        return 'plop';
    }

    public function action_403()
    {
    }
}

class TestToupti extends UnitTestCase
{
    public function setUp()
    {
    }
    
    public function testExceptionWithoutRouteConf()
    {
        $this->expectException(new TouptiException('No route defined. Please create '. realpath(dirname(__FILE__) . '/..')  .'/conf/routes.php'));
        $toupti = new Toupti(array());
    }

    public function testExceptionWithRouteConf()
    {
        $this->expectException(new TouptiException('No route defined. Please create '. dirname(__FILE__) . '/data/route_error.php'));
        $toupti = Toupti::instance(array('route_path' => dirname(__FILE__) .'/data/route_error.php'));
    }

    public function testRouteSuccess()
    {
        $toupti = Toupti::instance(array('route_path' => dirname(__FILE__).'/data/routes.php'));
        $_SERVER['REQUEST_URI'] = '/';
        $this->assertEqual('plop', $toupti->run());
    }

    public function testRouteWith404()
    {
        $_SERVER['REQUEST_URI'] = '/404';
        $toupti = Toupti::instance(array('route_path' => dirname(__FILE__).'/data/routes.php'));
        $this->expectException(new TouptiException('Error 404 /404', 404));
        $toupti->run();
    }

    public function testRouteWith403()
    {
        $_SERVER['REQUEST_URI'] = '/403';
        $toupti = Toupti::instance(array('route_path' => dirname(__FILE__).'/data/routes.php'));
        $this->expectException(new TouptiException('access_not_allowed', 403));
        $toupti->run();
    }

    public function testRouteControllerError()
    {
        $_SERVER['REQUEST_URI'] = '/unknow';
        $toupti = Toupti::instance(array('route_path' => dirname(__FILE__).'/data/routes.php'));
        $this->expectException(new TouptiException('Route error. Controller UnknowController not found for /unknow.', 404));
        $toupti->run();
    }

    public function testRouteActionError()
    {
        $_SERVER['REQUEST_URI'] = '/unknow_action';
        $toupti = Toupti::instance(array('route_path' => dirname(__FILE__).'/data/routes.php'));
        $this->expectException(new TouptiException('Route error. Action plop not exist in MyController for /unknow_action.', 404));
        $toupti->run();
    }
}
