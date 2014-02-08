<?php
class NullActiveModel
{
    public function i18nScope()
    {
        return 'activemodel';
    }
    
    public function getAttribute($attribute)
    {
        return $attribute . '-value';
    }
}
