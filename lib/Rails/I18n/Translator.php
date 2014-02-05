<?php
namespace Rails\I18n;

/**
 * Simple translator.
 *
 * Add translations in arrays, like this:
 * $translator->addTranslations([
 *   'en' => [
 *     'errors' => [
 *       'invalid' => 'The value is invalid',
 *       'unique'  => 'The value must be unique'
 *     ]
 *   ],
 *   'es' => [
 *     'errors' => [
 *       'invalid' => 'El valor es inválido,
 *       'unique'  => 'El valor debe ser único'
 *     ]
 *   ]
 * ]);
 *
 * Then you can translate like this:
 * $translator->translate('errors.invalid.unique', 'en'); // The value must be unique
 */
class Translator
{
    const NAMESPACE_SEPARATOR = '.';
    
    protected $translations   = [];
    
    /**
     * Default locale for translations.
     */
    protected $locale;
    
    /**
     * Locale to fallback to if a translation is missing.
     */
    protected $fallback;
    
    public function __construct($locale = null, $fallback = null)
    {
        if ($locale) {
            $this->setLocale($locale);
        }
        if ($fallback) {
            $this->setFallback($fallback);
        }
    }
    
    /**
     * Sets default locale for translations.
     *
     * @var string $locale
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }
    
    public function locale()
    {
        return $this->locale;
    }
    
    public function setFallback($fallback)
    {
        $this->fallback = $fallback;
    }
    
    public function fallback()
    {
        return $this->fallback;
    }
    
    public function translations()
    {
        return $this->translations;
    }
    
    public function addTranslations($translations)
    {
        $this->translations = array_merge_recursive(
            $this->translations,
            $translations
        );
    }
    
    /**
     * If translation isn't found, $key is returned (if an array was
     * passed, it's imploded) or an Exception is thrown.
     */
    public function translate($key, array $params = [], $locale = null, $throwE = false)
    {
        if (null === $locale) {
            $locale = $this->locale;
        }
        if (is_string($key)) {
            $keys = explode(self::NAMESPACE_SEPARATOR, $key);
        } elseif (is_array($key)) {
            $keys = $key;
        } else {
            throw new Exception\InvalidArgumentException(
                sprintf(
                    "First argument must be either string or array, '%s' passed",
                    gettype($key)
                )
            );
        }
        
        $tr = $this->getTranslation($keys, $locale);
        
        if (false === $tr && $locale != $this->fallback && $this->fallback) {
            $tr = $this->getTranslation($keys, $this->fallback);
        }
        
        if (false === $tr) {
            $key = implode(self::NAMESPACE_SEPARATOR, $keys);
            if ($throwE) {
                throw new Exception\TranslationNotFoundException(
                    sprintf(
                        "Couldn't find translation for key '%s'",
                        $key
                    )
                );
            } else {
                return $key;
            }
        }
        
        return $this->replacePlaceholders($tr, $params);
    }
    
    /**
     * Alias of translate().
     */
    public function t($key, array $params = [], $locale = null, $throwE = false)
    {
        return $this->translate($key, $params, $locale, $throwE);
    }
    
    public function replacePlaceholders($text, array $params)
    {
        if (is_int(strpos($text, '%{'))) {
            foreach ($params as $k => $param) {
                $text = str_replace('%{'.$k.'}', $param, $text);
                unset($params[$k]);
            }
        }
        if ($params) {
            $text = call_user_func_array('sprintf', array_merge([$text], $params));
        }
        return $text;
    }
    
    protected function getTranslation(array $keys, $locale)
    {
        if (!isset($this->translations[$locale])) {
            return false;
        }
        
        $tr = $this->translations[$locale];
        
        foreach ($keys as $key) {
            if (isset($tr[$key])) {
                $tr = $tr[$key];
            } else {
                /**
                 * Translation not found.
                 */
                return false;
            }
        }
        
        if (is_array($tr)) {
            /**
             * This may be a translation that got merged with another one, resulting
             * in an array; get the last value.
             */
            $tr = end($tr);
        }
        
        return $tr;
    }
}
