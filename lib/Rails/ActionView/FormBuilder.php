<?php
namespace Rails\ActionView;

class FormBuilder
{
    protected $helper;
    
    protected $model;
    
    protected $inputNamespace;
    
    public function __construct($helper, $model)
    {
        $this->helper = $helper;
        $this->model  = $model;
        $this->inputNamespace = \Rails::services()->get('inflector')->underscore(get_class($model));
    }
    
    public function textField($property, array $attrs = array())
    {
        $this->helper->setDefaultModel($this->model);
        return $this->helper->textField($this->inputNamespace, $property, $attrs);
    }
    
    public function hiddenField($property, array $attrs = array())
    {
        $this->helper->setDefaultModel($this->model);
        return $this->helper->hiddenField($this->inputNamespace, $property, $attrs);
    }
    
    public function passwordField($property, array $attrs = array())
    {
        $this->helper->setDefaultModel($this->model);
        return $this->helper->passwordField($this->inputNamespace, $property, $attrs);
    }
    
    public function checkBox($property, array $attrs = array(), $checked_value = '1', $unchecked_value = '0')
    {
        $this->helper->setDefaultModel($this->model);
        return $this->helper->checkBox($this->inputNamespace, $property, $attrs, $checked_value, $unchecked_value);
    }
    
    public function textArea($property, array $attrs = array())
    {
        $this->helper->setDefaultModel($this->model);
        return $this->helper->textArea($this->inputNamespace, $property, $attrs);
    }
    
    public function select($property, $options, array $attrs = array())
    {
        $this->helper->setDefaultModel($this->model);
        return $this->helper->select($this->inputNamespace, $property, $options, $attrs);
    }
    
    public function radioButton($property, $tag_value, array $attrs = array())
    {
        $this->helper->setDefaultModel($this->model);
        return $this->helper->radioButton($this->inputNamespace, $property, $tag_value, $attrs);
    }
    
    public function label($property, $text = '', $options = [])
    {
        if (!$text) {
            $text = \Rails::services()->get('inflector')->humanize($property);
        }
        return $this->helper->contentTag('label', $text, ['for' => $this->inputNamespace . '_' . $property]);
    }
    
    public function submit($value = null, array $options = [])
    {
        if (!$value) {
            $inflector = \Rails::services()->get('inflector');
            
            $prettyClassName = $inflector->humanize(
                $inflector->underscore(get_class($this->model))
            );
            
            if ($this->model->isNewRecord()) {
                $value = 'Create ' . $prettyClassName;
            } else {
                $value = 'Update ' . $prettyClassName;
            }
        }
        
        return $this->helper->tag('input', array_merge($options, ['value' => $value, 'type' => 'submit']));
    }
    
    public function field($type, $property, array $attrs = array())
    {
        $this->helper->setDefaultModel($this->model);
        return $this->helper->formField($type, $this->inputNamespace, $property, $attrs);
    }
    
    public function object()
    {
        return $this->model;
    }
}
