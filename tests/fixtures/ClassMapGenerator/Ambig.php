<?php

class Ambig implements AmbigInterface {

    public function getKey() {
        // Intentionally duplicate Foo
        return 'Foo';
    }
}
