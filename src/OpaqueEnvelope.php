<?php

namespace Firehed\Common;

use BadMethodCallException;
use JsonSerializable;

final class OpaqueEnvelope implements JsonSerializable {

    public function __construct($string) {
        $this->value = $this->mask($string, OpaqueEnvelopeKey::getKey());
    } // __construct

    public function open()/*: string */ {
        return $this->mask($this->value, OpaqueEnvelopeKey::getKey());
    } // open

    private function mask($string, $key)/*: string*/ {
        $out = '';
        $len = strlen($string);
        $keylen = strlen($key);
        for ($i = 0; $i < $len; $i++) {
            $s = $string[$i];
            $k = $key[$i % $keylen];
            $out .= chr(ord($s) ^ ord($k));
        }
        return $out;
    } // mask

    public function __toString() /*:string */ {
        return '<masked string>';
    } // __toString

    // 5.6 magic method to override var_dump
    public function __debugInfo() /*: array*/ {
        return [
            'value' => '<masked string>'
        ];
    } // __debugInfo

    // Prevent serialization
    public function __sleep() {
        throw new BadMethodCallException('You cannot serialize this object.');
    } // __sleep

    /**
     * Hide internal value from JSON encoding
     *
     * Unfortunately, PHP does not allow throwing an exception from this
     * method, so instead a useless value is returned
     */
    public function jsonSerialize() /*:string */ {
        return '<masked string>';
    } // jsonSerialize

}
