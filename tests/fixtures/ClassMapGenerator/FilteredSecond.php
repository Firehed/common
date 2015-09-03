<?php

class FilteredSecond implements FilteredInterface {

    public function getKey() {
        return __CLASS__;
    }

    public function filterMethod() {
        return false;
    }

    public function secondFilterMethod()  {
        return true;
    }


}
