<?php
namespace Rails\ActiveRecord;

class Base
{
    public function __call($method)
    {
        if ($lowerCase = $this->attributeExists($method, true)) {
            return $this->getAttribute($lowerCase);
        } elseif ($this->associationExists($method)) {
            return $this->getAssociation($method);
        }
    }
}
