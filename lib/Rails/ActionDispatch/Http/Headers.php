<?php
namespace Rails\ActionDispatch\Http;

use Rails\ActionDispatch\Exception;

class Headers
{
    protected $headers     = array();
    
    protected $status      = 200;
    
    protected $headersSent = false;
    
    protected $contentType;
    
    public function add($name, $value = null)
    {
        if (!is_string($name)) {
            throw new Exception\InvalidArgumentException(
                sprintf("First argument must be a string, %s passed.", gettype($value))
            );
        } elseif (!is_null($value) && !is_string($value) && !is_int($value)) {
            throw new Exception\InvalidArgumentException(
                sprintf("Second argument must be string, int or null, %s passed.", gettype($value))
            );
        }
        
        if (strpos($name, 'Content-type') === 0) {
            if ($value !== null) {
                $name = $name . $value;
            }
            $this->contentType($name);
        } elseif ($name == 'status') {
            $this->setStatus($value);
        } elseif (strpos($name, 'HTTP/') === 0) {
            $this->setStatus($name);
        } else {
            if ($value === null) {
                if (count(explode(':', $name)) < 2)
                    throw new Exception\InvalidArgumentException(
                        sprintf("%s is not a valid header", $name)
                    );
                $this->headers[] = $name;
            } else {
                $this->headers[$name] = $value;
            }
        }
        return $this;
    }
    
    public function setStatus($status)
    {
        if (ctype_digit((string)$status)) {
            $this->status = (int)$status;
        } elseif (is_string($status)) {
            $this->status = $status;
        } else {
            throw new Exception\InvalidArgumentException(
                sprintf("%s accepts string or int, %s passed.", __METHOD__, gettype($value))
            );
        }
        return $this;
    }
    
    public function status()
    {
        return $this->status;
    }
    
    public function setLocation($url, $status = 302)
    {
        if (!is_string($url)) {
            throw new Exception\InvalidArgumentException(
                sprintf("First argument must be string, %s passed.",, gettype($value))
            );
        }
        
        $this->setStatus($status)->add('Location', $url);
        return $this;
    }
    
    public function send()
    {
        if ($this->headersSent) {
            throw new Exception\LogicException("Headers have already been sent");
        }
        
        header($this->contentType);
        
        foreach ($this->headers as $name => $value) {
            if (!is_int($name)) {
                $value = $name . ': ' . $value;
            }
            header($value);
        }
        
        if (is_int($this->status)) {
            header('HTTP/1.1 ' . $this->status);
        } else {
            header($this->status);
        }
        
        $this->headersSent = true;
    }
    
    public function contentType()
    {
        return $this->contentType;
    }
    
    public function setContentType($contentType)
    {
        if (!is_string($contentType)) {
            throw new Exception\InvalidArgumentException(
                sprintf("Content type must be a string, %s passed", gettype($contentType))
            );
        }
        
        switch ($contentType) {
            case 'html':
                $contentType = 'text/html';
                break;
            
            case 'json':
                $contentType = 'application/json';
                break;
            
            case 'xml':
                $contentType = 'application/xml';
                break;
        }
        
        if (strpos($contentType, 'Content-type:') !== 0) {
            $contentType = 'Content-type: ' . $contentType;
        }
        
        $this->contentType = $contentType;
        
        return $this;
    }
}
