<?php

class JtView extends ViewAdaptor 
{

    private $assigned_data = array();

    public static function conf($conf) 
    {
    }

    public static function getTplExtension()
    {
        return '';
    }

    public function __construct($tpl = '', $params = array())
    {
        $this->tpl = $tpl;
    }

    public function assign($key, $value)
    {
        if($value instanceof View)
        {
            $this->notify($value->getNotifs());
            $value = $value->fetch();
        }
        $this->assigned_data[$key] = $value;
    }

    public function display($tpl = null)
    {
        if(!is_null($tpl))
        {
            $this->tpl = $tpl;
        }
        if($this->tpl != "")
            echo render_partial($this->tpl, $this->assigned_data);
    }

    public function fetch($tpl = null)
    {
        if(!is_null($tpl))
        {
            $this->tpl = $tpl;
        }
        if($this->tpl == '')
        {
            return;
        }
        return render_partial($this->tpl, $this->assigned_data);
    }

    public function getData($data)
    {
        if(isset($this->assigned_data[$data])) return $this->assigned_data[$data];
        return NULL;
    }
}

