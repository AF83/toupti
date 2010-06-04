<?php

class TestAppController extends Controller
{
    public function isAuthorized()
    {
        return true;
    }

    public function adefault()
    {
        $view = $this->getView();
        $view->assign('test', 'plop');
        $view->assign('param', $this->params['param']);
        return $view;
    }

    public function index()
    {
        $view = $this->getView();
        $view->assign('param', $this->params['param']);
        $view->assign('param2', $this->params['param2']);
        return $view;
    }

    public function post()
    {
        $view = $this->getView();
        $view->assign('email', $this->params['email']);
        return $view;
    }

    public function upload()
    {
        $view = $this->getView();
        $view->assign('foo', 'bar');
        return $view;
    }

    public function error_500()
    {
        $this->toupti->response->set_status(500);
        $view = $this->getView();
        $view->assign('foo', 'bar');
        return $view;
    }

    public function multiple_template()
    {
        $view = $this->getView('test.tpl');
        $view->assign('foo', 'bar');
        $view2 = $this->getView('chuck.tpl');
        $view2->assign('param', 2);
        $view2->assign('view', $view);
        return $view2;
    }
}
