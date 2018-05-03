<?php

class Foo implements FooInterface, AmbigInterface {

    public function getKey() {
        return __CLASS__;
    }
}
