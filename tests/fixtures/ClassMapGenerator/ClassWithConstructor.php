<?php
class ClassWithConstructor implements FooInterface {

    public function __construct($id) {
    }

    public function getKey() {
        return 'key';
    }
}
