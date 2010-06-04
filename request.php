<?php

/**
 * @package Toupti
 */
class RequestMapper
{
    public $method;
    public $accept;
    public $original_uri;
    protected $headers;
    //http://www.w3.org/Protocols/rfc2616/rfc2616-sec9.html
    private $isGet = false;
    private $isHead = false;
    private $isPost = false;
    private $isPut = false;
    private $isDelete = false;
    private $isOptions = false;
    private $isTrace = false;
    private $isConnect = false;

    private $possibleRequestMethods = array('GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'TRACE', 'CONNECT');

    public function __construct()
    {
        $this->method       = $_SERVER['REQUEST_METHOD'];
        $this->accept       = $this->parseAcceptHeaders(isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : NULL);
        $this->headers      = $this->getRequestHeaders();
        $this->original_uri = $_SERVER['REQUEST_URI'];
        $this->setRequestMethod();
    }

    protected function getRequestHeaders()
    {
        if (function_exists('apache_request_headers'))
        {
            return $this->getApacheRequestHeaders();
        }
        return $this->getFastCgiRequestHeaders();
    }

    protected function getApacheRequestHeaders()
    {
       return apache_request_headers();
    }

    protected function getFastCgiRequestHeaders()
    {
        $ret = array();
        foreach($_SERVER as $key => $value)
        {
            $matches = array();
            if(preg_match('/^HTTP_(.+)$/', $key, $matches))
            {
                $key = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower($matches[1]))));
                $ret[$key] = $value;
            }
        }
        return $ret;
    }

    public function __get($name)
    {
        if ($this->isRequestMethodName($name))
        {
            return $this->$name;
        }
        return null;
    }

    public function __toString() {
        return
            "url = [{$this->url}]\n".
            "method = [{$this->method}]\n".
            "accept = [{$this->accept}]\n";
    }

    /**
     * Set which method.
     */
    private function setRequestMethod()
    {
        if (in_array($_SERVER['REQUEST_METHOD'], $this->possibleRequestMethods))
        {
            $m = "is".ucfirst(strtolower($_SERVER['REQUEST_METHOD']));
            $this->$m = true;
        }
    }

    private function isRequestMethodName($name)
    {
        foreach($this->possibleRequestMethods as $rm)
        {
            if('is'.ucfirst(strtolower($rm)) == $name)
            {
                return true;
            }
        }
        return false;
    }

    public function getPossibleRequestMethods()
    {
        return $this->possibleRequestMethods;
    }

    public function getRequestMethod()
    {
        foreach($this->possibleRequestMethods as $rm)
        {
            $m = "is".ucfirst(strtolower($rm));
            if($this->$m) return $rm;
        }
    }

    public function getHeader($header)
    {
        return isset($this->headers[$header]) ? $this->headers[$header] : NULL;
    }

    public function isXHR()
    {
        if (array_key_exists('X-Requested-With', $this->headers) && $this->headers['X-Requested-With'] == 'XMLHttpRequest')
            return true;
        // ie bug ?
        elseif (array_key_exists('x-requested-with', $this->headers) && $this->headers['x-requested-with'] == 'XMLHttpRequest')
            return true;
        return false;
    }

    private function parseAcceptHeaders($accept_header, $default="text/html")
    {
        //@fixme: is formats complet ?
        $formats = array(
            'text/html' => 'html',
            'application/xhtml+xml' => 0,
            'application/xml' => 0,
            '*/*' => 'html',
        );

        $accept = array();
        $headers = explode(',', $accept_header);
        foreach ($headers as $header){
            list($mime, $q) =  strpos($header,';q=') ? explode(';q=', $header): array($header,'1'); // As per http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html default q value is 1
            $accept[$mime] = ($q === null)? 1 : $q;
        }

        arsort($accept);
        $accept[] = $default;

        foreach ($accept as $format => $q)
            if (isset($formats[$format]))
                break;

        if ($format && $formats[$format]) 	return array($format, $formats[$format]); //weird ie6 bug sometimes doesn't send headers so fighing notice here

    }
}
