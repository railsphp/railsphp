<?php
namespace Rails\ServiceManager;

trait ServiceLocatorAwareTrait
{
    use \Zend\ServiceManager\ServiceLocatorAwareTrait;
    
    /**
     * Shortcut.
     */
    public function services()
    {
        return $this->getServiceLocator();
    }
}
