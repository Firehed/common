<?php

namespace A\B;

class Foo implements \FooInterface, \CategoryInterface {

    public function getMethod() {
        return 'GET';
    }

    public function getVersion() {
        return '2';
    }

    public function getKey() {
        return __CLASS__;
    }
}
