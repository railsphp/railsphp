<?php
namespace RailsTest\I18n;

use Rails;

class LoadingTranslatorTest extends \PHPUnit_Framework_TestCase
{
    protected $basePath;
    
    public function setUp()
    {
        $this->basePath = __DIR__ . '/../..';
    }
    
    public function testLoadFiles()
    {
        $tr = $this->getTranslator([$this->basePath . '/files/locales/dir1']);
        $this->assertSame("This record is invalid.", $tr->t('active_record.errors.invalid'));
        $this->assertSame("This record is invalid.", $tr->t(['active_record', 'errors', 'invalid']));
    }
    
    public function testLocaleSwitch()
    {
        $tr = $this->getTranslator([$this->basePath . '/files/locales/dir1']);
        # English
        $this->assertSame("This record is invalid.", $tr->t('active_record.errors.invalid'));
        # Change default locale.
        $tr->setLocale('es');
        $this->assertSame("Este registro es inválido.", $tr->t('active_record.errors.invalid'));
        # Request translation in english.
        $this->assertSame("This record is invalid.", $tr->t('active_record.errors.invalid', [], 'en'));
    }
    
    public function testLoadFilesMultipleDirs()
    {
        $tr = $this->getTranslator([$this->basePath . '/files/locales/dir1', $this->basePath . '/files/locales/dir2']);
        # Translation found only in dir2
        $this->assertSame("The value is too long.", $tr->t('active_record.errors.length'));
        # Replaced translation from dir1 by translations in dir2.
        $this->assertSame("(2) This record is invalid.", $tr->t('active_record.errors.invalid'));
    }
    
    public function testFallbackLocale()
    {
        $tr = $this->getTranslator([$this->basePath . '/files/locales/dir1', $this->basePath . '/files/locales/dir2']);
        $tr->setFallbacks(['en']);
        $this->assertSame("This message is only in english.", $tr->t('active_record.errors.unique', [], 'es'));
        $tr->setLocale('es');
        $this->assertSame("This message is only in english.", $tr->t('active_record.errors.unique'));
    }
    
    public function testErrors()
    {
        $tr = $this->getTranslator();
        # Return key of unknown translations.
        $this->assertSame(false, $tr->t('active_record.errors.unknownkey'));
        $this->assertSame(false, $tr->t(['active_record', 'errors', 'unknownkey']));
        # Force throwing an exception.
        $this->setExpectedException("Rails\I18n\Exception\TranslationNotFoundException");
        $tr->t('active_record.errors.unknownkey', [], null, true);
        # Pass invalid argument.
        $this->setExpectedException("Rails\I18n\Exception\InvalidArgumentException");
        $tr->t(1);
    }
    
    protected function getTranslator(array $dirs = [])
    {
        $loader = new Rails\I18n\Loader();
        $loader->addPaths($dirs);
        $tr = new Rails\I18n\LoadingTranslator();
        $tr->setLocale('en');
        $tr->setLoader($loader);
        return $tr;
    }
}

