<?php

/**
 * @package Toupti
 */
class View
{
    protected static $view_class = NULL;

    protected $view_object = NULL;

    public static $conf = array(
    );

    public $_js = array();

    public static function conf($conf)
    {
        self::$conf = $conf;
    }

    public function reset()
    {
        self::$view_class = NULL;
        self::$conf = NULL;
    }

    public static function useLib($lib)
    {
        $class_name = ucfirst(strtolower($lib)).'View';
        if (!class_exists($class_name))
        {
            throw new TouptiException(sprintf("The %s view adaptor could not be loaded, class name %s", $lib, $class_name));
        }
        self::$view_class = $class_name;
    }

    public function __construct($tpl = '', $params = array())
    {
        $view_class = self::$view_class;
        if (is_null($view_class))
        {
            throw new TouptiException("no adaptor set");
        }
        call_user_func_array(array($view_class, 'conf'), array(self::$conf));
        $this->view_object = new $view_class($tpl, $params);
    }

    public function __call($name, $arguments)
    {
        if (is_null($this->view_object) || !method_exists($this->view_object, $name))
        {
            throw new TouptiException(sprintf("Could not call %s either on View nor on a Lib", $name));
        }
        return call_user_func_array(array($this->view_object, $name), $arguments);
    }

    /**
     * Required javascript file.
     * @param $files String  needed javascript file.
     */
    public function js()
    {
        $args = (func_num_args() == 1 && is_array(func_get_arg(0))) ? func_get_arg(0) : func_get_args();
        $this->_js = array_merge($this->_js, $args);
    }
}
