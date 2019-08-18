<?php
namespace nerdmann\react\phenocean;

use InvalidArgumentException;

class Event
{

    private $value;

    public function __construct(string $type)
    {
        $this->type = $type;
        $this->value = array();
    }

    public function getType()
    {
        return $this->type;
    }

    public function setProperty($key, $value)
    {
        $this->value[$key] = $value;
    }

    public function getProperty($key)
    {
        if (isset($key))
            return $this->value[$key];
        else
            throw new InvalidArgumentException("Property " . $key . " not found");
    }

    public function __toString()
    {
        $string = json_encode($this->value);
    }
}

