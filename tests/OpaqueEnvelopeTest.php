<?php

namespace Firehed\Common;

use BadMethodCallException;

/**
 * @coversDefaultClass Firehed\Common\OpaqueEnvelope
 * @covers ::<protected>
 * @covers ::<private>
 */
class OpaqueEnvelopeTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @covers ::__construct
     */
    public function testConstruct(): void
    {
        $secret = 'secret';
        $envelope = new OpaqueEnvelope($secret);
        // @phpstan-ignore-next-line
        $this->assertInstanceOf(OpaqueEnvelope::class, $envelope);
    }

    /**
     * @covers ::__toString
     */
    public function testToStringDoesNotRevealValue(): void
    {
        $secret = 'secret';
        $envelope = new OpaqueEnvelope($secret);
        $string = (string)$envelope;
        $this->assertNotEquals(
            $secret,
            $string,
            'Object should not contain the secret when converted to string'
        );
    }

    /**
     * @covers ::__debugInfo
     */
    public function testVarDumpDoesNotRevealValue(): void
    {
        $secret = 'secret';
        $envelope = new OpaqueEnvelope($secret);
        ob_start();
        var_dump($envelope);
        $output = ob_get_clean();
        assert($output !== false);
        $this->assertStringNotContainsString($secret, $output);
    }

    /**
     * @covers ::open
     */
    public function testOpen(): void
    {
        $secret = 'secret';
        $envelope = new OpaqueEnvelope($secret);
        $opened_result = $envelope->open();
        $this->assertSame(
            $secret,
            $opened_result,
            'The original string should have been returned'
        );
    }

    /**
     * @covers ::open
     */
    public function testOpenUnicode(): void
    {
        // Unicode filled star (byte values - from `arc unit`)
        $star = "\xE2\x98\x85";
        $this->assertEquals(
            1,
            mb_strlen($star),
            'The sanity check of multibyte strlen failed'
        );
        $envelope = new OpaqueEnvelope($star);
        $opened_result = $envelope->open();
        $this->assertSame(
            $star,
            $opened_result,
            'The original string should have been returned'
        );
        $this->assertEquals(
            1,
            mb_strlen($opened_result),
            'The opened string was not still a one-character multibyte string'
        );
    }

    /**
     * @covers ::__sleep
     */
    public function testSerializationIsBlocked(): void
    {
        $this->expectException(BadMethodCallException::class);
        serialize(new OpaqueEnvelope('secret'));
    }

    /**
     * @covers ::jsonSerialize
     */
    public function testJsonEncodingIsMasked(): void
    {
        $secret = 'secret';
        $json = json_encode(new OpaqueEnvelope($secret), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString(
            $secret,
            $json,
            'The JSON should not contain the secret'
        );
    }
}
