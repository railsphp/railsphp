<?php
namespace Rails\ServiceManager;

trait ServiceLocatorAwareTrait
{
    public function services()
    {
        return \Rails::serviceManager();
    }
}
