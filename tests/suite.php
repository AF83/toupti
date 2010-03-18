<?php

require_once('simpletest/autorun.php');

require_once('../toupti.php');
require_once('../request.class.php');
require_once('../route.php');
require_once('../route_libs/namespace.php');
require_once('../route_libs/resource.php');
require_once('../controller.php');
require_once('../view.php');
require_once('../view_libs/adaptor.php');
require_once('../view_libs/smarty.php');

class TouptiTestSuite extends TestSuite
{
    public function __construct()
    {
        parent::__construct('Toupti');
        $test_dir = dirname(__FILE__);
        $this->addFile($test_dir .'/test_request.php');
        $this->addFile($test_dir .'/test_route.php');
        $this->addFile($test_dir .'/test_view.php');
        $this->addFile($test_dir .'/test_toupti.php');
    }
}
