<?php

namespace Firehed\Common;

final class Bitmask
{

    private $mask = 0b0;
    private $type = null;

    public function __construct($initial_value = 0)
    {
        if ($initial_value instanceof Enum) {
            $this->setType(get_class($initial_value));
        }
        $this->checkType($initial_value);

        $this->mask = $initial_value;
    } // __construct

    private function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    private function checkType(&$value)
    {
        if ($value instanceof Enum && $this->type) {
            if (!$value instanceof $this->type) {
                throw new \UnexpectedValueException(
                    "An invalid value type was provided"
                );
            }
            $value = $value->getValue();
        } elseif (!is_int($value) || $this->type) {
            throw new \UnexpectedValueException(
                "A non-integer, non-enum value was provided"
            );
        }
        return true;
    } // checkType

    public function add($value)
    {
        $this->checkType($value);
        $this->mask |= $value;
        return $this;
    } // add

    public function remove($value)
    {
        $this->checkType($value);
        $this->mask &= ~$value;
        return $this;
    }

    public function has($value)
    {
        $this->checkType($value);
        return ($this->mask & $value) === $value;
    } // has
}
