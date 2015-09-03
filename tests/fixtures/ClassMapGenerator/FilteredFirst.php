<?php

class FilteredFirst implements FilteredInterface {

    public function getKey() {
        return __CLASS__;
    }

    public function filterMethod() {
        return true;
    }

    public function secondFilterMethod()  {
        return false;
    }


}
