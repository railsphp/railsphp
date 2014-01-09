<?php
namespace Rails\ActionView;

use Rails\ActionView\Template\Exception;

class Partial extends Template
{
    public function render_content()
    {
        ob_start();
        $this->_init_render();
        return ob_get_clean();
    }
    
    public function t($name, array $params = [])
    {
        return parent::t($name, $params);
    }
    
    protected function _init_render()
    {
        try {
            return parent::_init_render();
        } catch (Exception\TemplateMissingException $e) {
            throw new Exception\PartialMissingException($e->getMessage(), 0, $e);
        }
    }
}
