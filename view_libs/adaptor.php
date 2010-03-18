<?php
/**
 * @package Toupti
 */
abstract class ViewAdaptor
{
    private $notifs = array();

    abstract public static function conf($conf); 
    
    abstract public static function getTplExtension(); 

    abstract public function __construct($tpl = '', $params = array());

    abstract public function assign($key, $value);

    abstract public function display($tpl = null);

    abstract public function fetch($tpl = null);

    /**
     * @return Array notifs accumulator.
     */
    public function getNotifs()
    {
        return $this->notifs;
    }

    /**
     * @param Mixed $notify notification or an array of them.
     */
    public function notify($notify)
    {
        if(is_array($notify))
        {
            foreach($notify as $key => $value)
                $this->addNotify($value);
        }
        else
        {
            $this->addNotify($notify);
        }
    }

    private function addNotify($notify)
    {
        if (!is_null($notify))
            $this->notifs []= $notify;
    }
}
