<?php

namespace Firehed\Common;

use BadMethodCallException;
use JsonSerializable;

final class OpaqueEnvelope implements JsonSerializable
{

    private string $value;

    public function __construct(string $string)
    {
        $this->value = $this->mask($string, OpaqueEnvelopeKey::getKey());
    }

    public function open(): string
    {
        return $this->mask($this->value, OpaqueEnvelopeKey::getKey());
    }

    private function mask(string $string, string $key): string
    {
        $out = '';
        $len = strlen($string);
        $keylen = strlen($key);
        for ($i = 0; $i < $len; $i++) {
            $s = $string[$i];
            $k = $key[$i % $keylen];
            $out .= chr(ord($s) ^ ord($k));
        }
        return $out;
    }

    public function __toString(): string
    {
        return '<masked string>';
    }

    /**
     * Override for var_dump()
     *
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return [
            'value' => '<masked string>'
        ];
    }

    // Prevent serialization
    public function __sleep()
    {
        throw new BadMethodCallException('You cannot serialize this object.');
    }

    /**
     * Hide internal value from JSON encoding
     *
     * Unfortunately, PHP does not allow throwing an exception from this
     * method, so instead a useless value is returned
     */
    public function jsonSerialize(): string
    {
        return '<masked string>';
    }
}
