<?php
namespace Rails\ActionDispatch\Http;

class UploadedFile
{
    protected $name;
    
    protected $type;
    
    protected $path;
    
    protected $error;
    
    protected $size;
    
    public function __construct(array $data)
    {
        $this->name  = $data['name'];
        $this->type  = $data['type'];
        $this->path  = $data['tmp_name'];
        $this->error = $data['error'];
        $this->size  = $data['size'];
    }
    
    public function name()
    {
        return $this->name;
    }
    
    public function type()
    {
        return $this->type;
    }
    
    public function path()
    {
        return $this->path;
    }
    
    public function size()
    {
        return $this->size;
    }
    
    public function errorCode()
    {
        return $this->error;
    }
    
    public function error()
    {
        return !($this->error == UPLOAD_ERR_OK);
    }
    
    public function move($newName)
    {
        return move_uploaded_file($this->path, $newName);
    }
}
