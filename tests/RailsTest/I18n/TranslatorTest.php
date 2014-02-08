<?php
namespace RailsTest\I18n;

use Rails;

class TranslatorTest extends \PHPUnit_Framework_TestCase
{
    protected $translator;
    
    protected function setUp()
    {
        $this->translator = new Rails\I18n\Translator();
        
        /**
         * First keys must be the locale of the translations.
         * E.g. [ 'en' => [ ... ] ]
         */
        $this->translator->addTranslations([
            'en' => [
                'electronics' => [
                    'microwave' => 'microwave',
                    'blender'   => 'blender',
                    'fridge'    => 'fridge'
                ],
                'sentences' => [
                    'my_hobby'  => 'My favorite hobby is %{hobby}!',
                    'time'      => 'The time is %{time}',
                    'multi'     => "Here's value 1 %{value1} and value 2 %{value2}"
                ]
            ],
            'es' => [
                'electronics' => [
                    'microwave' => 'microondas',
                ],
                'sentences' => [
                    'time'      => 'La hora es %{time}',
                ]
            ],
            'fr' => [
                'electronics' => [
                    'microwave' => 'micro-onde',
                    'blender'   => 'mixer'
                ]
            ]
        ]);
    }
    
    public function testTranslation()
    {
        $this->assertSame('microondas', $this->translator->translate('electronics.microwave', [], 'es'));
        $this->assertSame('micro-onde', $this->translator->translate(['electronics', 'microwave'], [], 'fr'));
    }
    
    public function testDefaultLocale()
    {
        $this->translator->setLocale('es');
        $this->assertSame('microondas', $this->translator->translate('electronics.microwave'));
        $this->translator->setLocale('fr');
        $this->assertSame('micro-onde', $this->translator->translate(['electronics', 'microwave']));
    }
    
    public function testFallbacks()
    {
        $this->translator->setFallbacks(['fr', 'en']);
        $this->translator->setLocale('es');
        
        $this->assertSame('mixer', $this->translator->translate('electronics.blender', [], 'es'));
        $this->assertSame('fridge', $this->translator->translate('electronics.fridge', [], 'es'));
    }
    
    public function testInterpolation()
    {
        $hobby = 'to read';
        $this->assertSame(
            'My favorite hobby is to read!',
            $this->translator->translate('sentences.my_hobby', ['hobby' => $hobby], 'en')
        );
    }
    
    public function testDefault()
    {
        $default = [
            'unknown.foo2',
            'sentences.time'
        ];
        $time = date('H:i:s');
        
        $options = [
            'default' => $default,
            'time'    => $time
        ];
        
        $this->translator->setFallbacks(['en']);
        
        $this->assertSame(
            'The time is ' . $time,
            $this->translator->translate('unknown.foo', $options, 'fr')
        );
    }
    
    public function testTranslationMissing()
    {
        $this->translator->setLocale('en');
        $missingKey = 'unknown.foo';
        $this->assertSame(
            false,
            $this->translator->translate($missingKey)
        );
    }
    
    public function testMultipleInterpolation()
    {
        $key = 'sentences.multi';
        $options = [
            'value1' => 'one',
            'value2' => 'two'
        ];
        $this->assertSame(
            "Here's value 1 one and value 2 two",
            $this->translator->translate($key, $options, 'en')
        );
    }
    
    /**
     * @expectedException Rails\I18n\Exception\TranslationMissingException
     */
    public function testTranslationMissingException()
    {
        $this->translator->setLocale('en');
        $missingKey = 'unknown.foo';
        $this->translator->translate($missingKey, ['exception' => true]);
    }
}
