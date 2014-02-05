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
     * Default locale for translations, also serves as fallback if a translation
     * with different locale is requested.
     */
    protected $locale;
    
    public function __construct($locale = null)
    {
        if (!$locale) {
            $this->setLocale($locale);
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
        if (!$locale) {
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
        
        if (false === $tr && $locale != $this->locale) {
            $tr = $this->getTranslation($keys, $this->locale);
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
                break;
            }
        }
        
        if (!is_string($tr)) {
            $tr = false;
        }
        
        return $tr;
    }
}
