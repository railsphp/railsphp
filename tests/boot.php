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
    __DIR__
]);
spl_autoload_register([$loader, 'loadClass']);


/**
 * Setup Service Manager.
 */
$sm = new Zend\ServiceManager\ServiceManager();
$sm->setService('inflector', new Rails\ActiveSupport\Inflector\Inflector(
    new Rails\ActiveSupport\Inflector\Inflections\EnglishInflections,
    'en'
));
Rails::setServiceManager($sm);
