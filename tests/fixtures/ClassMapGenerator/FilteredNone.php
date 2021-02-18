<?php

class FilteredNone implements FilteredInterface
{

    public function getKey()
    {
        return __CLASS__;
    }

    public function filterMethod()
    {
        return false;
    }

    public function secondFilterMethod()
    {
        return false;
    }
}
