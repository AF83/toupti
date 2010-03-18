<?php

/**
 * @package Toupti
 */
class Controller
{
    public $toupti = null;
    public $params = array();

    public function __construct()
    {
        $this->toupti = Toupti::instance();
        $this->params = $this->toupti->get_params();
    }

    /**
     * Quick Alias to get a view.
     * @param tpl a full formated path (relative to the app/view one) to the tpl file to use. If tpl is not provide or
     * null, will use view/controller_name/controller_name.tpl. Without any path indication, file will be search in view/controller_name path/.
     * note that you don't need to provide the file extension.
     * @return View
     */
    public function getView($tpl = null)
    {
        // extract controller_name
        $module = strtolower(substr( get_class($this), 0, - strlen("controller")));
        if(is_null($tpl))
        {
            // at least the file is the controller name.
            $tpl = $module.View::getTplExtension();
        }
        if(strstr($tpl, '/') === FALSE)
        {
            // not path, so it's our controller template dir'
            $tpl = "$module/$tpl";
        }
        if( strrpos($tpl, View::getTplExtension()) != (strlen($tpl) - 4) )
        {
            // missing file extension. note the portnawak test :)
            $tpl .= View::getTplExtension();
        }
        return new View($tpl, $this->params);
    }
    /**
     * quick alias to exit without any layout.
     */
    public function exit_ajax()
    {
        return $this->toupti->request->isXHR();
    }

    /**
     * quick alias to exit without any layout.
     */
    public function request()
    {
        return $this->toupti->request;
    }
    /**
     * Redirect to previous path or exists and outputs in ajax mode
     * @param  string    $ajaxoutput          What to output if we are in ajax mode
     * @param  array     $ajax_headers        Extra headers to output if existing in ajax (probably we will want to put here an x-ajax-referrer)
     * @param  boolean   $redirect_to         if we want to fall back on something in case referrer is not here
     * @return void
     */
    public function redirect_to_referrer_or_exit_ajax($ajaxoutput="", $ajax_headers=array(),$redirect_to="/")
    {
        if($this->exit_ajax())
        {
            foreach($ajax_headers as $ajax_header) { header($ajax_header); }
            echo $ajaxoutput instanceof View ? $ajaxoutput->fetch(): $ajaxoutput;
            exit;
        }
        else
        {
            $this->toupti->redirect_to(isset($_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : $redirect_to);
        }
    }

    /**
     * Is the called action is authorized ?
     * This can be overloaded in each controller to get a particular acl system
     *
     * @return Boolean
     */
    public function isAuthorized($method_name)
    {
        return false;
    }
}
