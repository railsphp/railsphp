<?php
namespace Rails\ActiveSupport\Inflector;

class Word
{
    protected $word;
    
    protected $inflector;
    
    protected $locale;
    
    public function __construct($word, InflectorInterface $inflector, $locale = null)
    {
        $this->inflector = $inflector;
        $this->word      = $word;
        $this->locale    = $locale ?: $inflector->defaultLocale();
    }
    
    public function __toString()
    {
        return $this->word;
    }
    
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }
    
    public function pluralize()
    {
        $this->word = $this->inflector->pluralize($this->word, $this->locale);
        return $this;
    }
    
    public function singularize()
    {
        $this->word = $this->inflector->singularize($this->word, $this->locale);
        return $this;
    }
    
    public function camelize($uppercaseFirstLetter = true)
    {
        $this->word = $this->inflector->camelize($this->word, $uppercaseFirstLetter);
        return $this;
    }
    
    public function underscore()
    {
        $this->word = $this->inflector->underscore($this->word);
        return $this;
    }
    
    public function humanize()
    {
        $this->word = $this->inflector->humanize($this->word);
        return $this;
    }
    
    public function titleize()
    {
        $this->word = $this->inflector->titleize($this->word);
        return $this;
    }
    
    public function tableize()
    {
        $this->word = $this->inflector->tableize($this->word);
        return $this;
    }
    
    public function classify()
    {
        $this->word = $this->inflector->tableize($this->word);
        return $this;
    }
    
    public function ordinal()
    {
        $this->word = $this->inflector->ordinal($this->word);
        return $this;
    }
}
