<?php

namespace Firehed\Common;

final class Bitmask
{

    private int $mask = 0b0;
    private $type = null;

    public function __construct($initial_value = 0)
    {
        if ($initial_value instanceof Enum) {
            $this->setType(get_class($initial_value));
        }
        $this->checkType($initial_value);

        $this->mask = $initial_value;
    }

    private function setType($type): self
    {
        $this->type = $type;
        return $this;
    }

    private function checkType(&$value): bool
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
    }

    public function add($value): self
    {
        $this->checkType($value);
        $this->mask |= $value;
        return $this;
    }

    public function remove($value): self
    {
        $this->checkType($value);
        $this->mask &= ~$value;
        return $this;
    }

    public function has($value): bool
    {
        $this->checkType($value);
        return ($this->mask & $value) === $value;
    }
}
