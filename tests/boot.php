<?php
/**
 * Required files.
 */
require __DIR__ . '/files/functions.php';
require __DIR__ . '/../lib/Rails/Rails.php';
require __DIR__ . '/../lib/Rails/Loader/Loader.php';

/**
 * Setup loader.
 */
$loader = new Rails\Loader\Loader([
    __DIR__ . '/../..', # Vendor directory
    __DIR__ . '/../lib',
    __DIR__ . '/fixtures',
    __DIR__
]);
spl_autoload_register([$loader, 'loadClass']);


/**
 * Setup Service Manager.
 */
$sm = new Zend\ServiceManager\ServiceManager();
# Inflector
$sm->setService('inflector', new Rails\ActiveSupport\Inflector\Inflector(
    new Rails\ActiveSupport\Inflector\Inflections\EnglishInflections,
    'en'
));
# Translator
$dirs = [
    __DIR__ . '/files/locales/dir1',
    __DIR__ . '/files/locales/dir2',
];
$loader = new Rails\I18n\Loader();
$loader->addPaths($dirs);
$tr = new Rails\I18n\LoadingTranslator();
$tr->setLocale('en');
$tr->setLoader($loader);
$sm->setService('i18n', $tr);

Rails::setServiceManager($sm);
