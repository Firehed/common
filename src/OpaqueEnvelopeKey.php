<?php

namespace Firehed\Common;

final class OpaqueEnvelopeKey
{

    private static $key;

    // Block instanciation
    private function __construct()
    {
    }

    public static function getKey()
    {
        if (!self::$key) {
            for ($ii = 0; $ii < 8; $ii++) {
                self::$key .= md5(mt_rand(), $raw_output = true);
            }
        }
        return self::$key;
    } // getKey
}
