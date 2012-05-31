<?php
class ObjectFactory
{
    protected $types = array();

    function register($category, $type, $class)
    {
        $this->types[$category.'_'.$type] = $class;
    }

    function create($category, $type)
    {
        if (!isset($this->types[$category.'_'.$type]))
            die('ObjectFactory does not recognize '.$category.'_'.$type);

        return new $this->types[$category.'_'.$type]();
    }
};
?>
