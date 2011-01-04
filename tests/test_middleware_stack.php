<?php

$libs_path = dirname( __FILE__ ) . '/../../../libs';
require_once realpath($libs_path . "/toupti/middleware.php");

class MockMiddlewareFollow extends Middleware {
 
    /**
     * Doing nothing and follow middleware.
     */
    public function run($req, $res)
    {
        $this->follow($req, $res);
    }

}

class MockMiddlewareNotFollow extends Middleware {
 
    /**
     * Doing nothing and do not call middleware chaining.
     */
    public function run($req, $res)
    {
    }
}

class MockMiddlewareInc extends Middleware {
 
    /**
     * Doing nothing and follow middleware.
     */
    public function run($req, $res)
    {
        $res->value++;
        $this->follow($req, $res);
    }

}

class TestMiddlewareStack extends UnitTestCase
{

    public function testRunningEmptyStack()
    {
        $req = null;
        $res = null;
        $app = new MiddlewareStack();
        try {
            $app->run($res, $res);
            $this->fail("running an empty Stack must trigger an MiddleWareStackException");
        } catch(MiddlewareStackException $exception) {
            $this->pass();
        } catch(Exception $exception) {
            $this->fail("running an empty Stack must trigger an Exception class MiddleWareStackException");
        }


    }
    
    public function testRunningAPassMock()
    {
        $app = new MiddlewareStack();
        $app->add(new MockMiddlewareFollow());

        $res = new StdClass();
        $res->value = 0;
        $req = new StdClass();
        $req->value = 0;

        $app->run($req, $res);

        $this->assertEqual($req->value, 0);
        $this->assertEqual($res->value, 0);
    }

    public function testRunning3Incs()
    {
        $app = new MiddlewareStack();
        $app->add(new MockMiddlewareInc());
        $app->add(new MockMiddlewareInc());
        $app->add(new MockMiddlewareInc());
        
        $res = new StdClass();
        $res->value = 0;
        $req = new StdClass();
        $req->value = 0;
        
        $app->run($req, $res);
        
        $this->assertEqual($req->value, 0);
        $this->assertEqual($res->value, 3);
    }

    public function testRunningNotFollowingMock()
    {
        $app = new MiddlewareStack();
        $app->add(new MockMiddlewareFollow());
        $app->add(new MockMiddlewareInc());
        $app->add(new MockMiddlewareNotFollow);
        $app->add(new MockMiddlewareInc());
        
        $res = new StdClass();
        $res->value = 0;
        $req = new StdClass();
        $req->value = 0;
        
        $app->run($req, $res);

        $this->assertEqual($req->value, 0);
        $this->assertEqual($res->value, 1);
 
    }
}