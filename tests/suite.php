<?php

require_once('simpletest/autorun.php');
require_once('simpletest/web_tester.php');

require_once('../toupti.php');
require_once('../testcase.php');
require_once('../request.php');
require_once('../response.php');
require_once('../route.php');
require_once('../controller.php');
require_once('../view.php');
require_once('../view_libs/adaptor.php');
require_once('../view_libs/smarty.php');
require_once('../view_libs/mocktest.php');

class TouptiTestSuite extends TestSuite
{
    public function __construct()
    {
        parent::__construct('Toupti');
        $test_dir = dirname(__FILE__);
        $this->addFile($test_dir .'/test_request.php');
        $this->addFile($test_dir .'/test_response.php');
        $this->addFile($test_dir .'/test_route.php');
        $this->addFile($test_dir .'/test_view.php');
        $this->addFile($test_dir .'/test_toupti.php');
        $this->addFile($test_dir .'/test_touptitestcase.php');
    }
}
