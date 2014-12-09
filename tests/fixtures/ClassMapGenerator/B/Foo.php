<?php

namespace B;

class Foo implements \FooInterface {

    public function getKey() {
        return __CLASS__;
    }
}
