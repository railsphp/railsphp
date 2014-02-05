<?php
namespace Rails\I18n;

/**
 * This translator uses Loader to load translation from files.
 */
class LoadingTranslator extends AbstractTranslator
{
    protected $loader;
    
    public function setLoader(Loader $loader)
    {
        $this->loader = $loader;
    }
    
    public function loader()
    {
        return $this->loader;
    }
    
    public function availableLocales()
    {
        return $this->loader->availableLocales();
    }
    
    public function translate($key, array $params = [], $locale = null, $throwE = false)
    {
        $this->loadTranslations($locale ?: $this->locale);
        return parent::translate($key, $params, $locale, $throwE);
    }
    
    public function t($key, array $params = [], $locale = null, $throwE = false)
    {
        return $this->translate($key, $params, $locale, $throwE);
    }
    
    protected function loadTranslations($locale)
    {
        if ($this->loader) {
            $tr = $this->loader->loadTranslations($locale);
            if (is_array($tr)) {
                $this->addTranslations($tr);
            }
        }
    }
}
