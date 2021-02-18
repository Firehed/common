<?php

namespace Firehed\Common;

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
    public function testConstruct()
    {
        $secret = 'secret';
        $envelope = new OpaqueEnvelope($secret);
        $this->assertInstanceOf('Firehed\Common\OpaqueEnvelope', $envelope);
    } // testConstruct

    /**
     * @covers ::__toString
     */
    public function testToStringDoesNotRevealValue()
    {
        $secret = 'secret';
        $envelope = new OpaqueEnvelope($secret);
        $string = (string)$envelope;
        $this->assertNotEquals(
            $secret,
            $string,
            'Object should not contain the secret when converted to string'
        );
    } // testToStringDoesNotRevealValue

    /**
     * @covers ::__debugInfo
     */
    public function testVarDumpDoesNotRevealValue()
    {
        $secret = 'secret';
        $envelope = new OpaqueEnvelope($secret);
        ob_start();
        var_dump($envelope);
        $output = ob_get_clean();
        $this->assertNotContains($secret, $output);
    } // testVarDumpDoesNotRevealValue

    /**
     * @covers ::open
     */
    public function testOpen()
    {
        $secret = 'secret';
        $envelope = new OpaqueEnvelope($secret);
        $opened_result = $envelope->open();
        $this->assertSame(
            $secret,
            $opened_result,
            'The original string should have been returned'
        );
    } // testOpen

    /**
     * @covers ::open
     */
    public function testOpenUnicode()
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
    } // testOpenUnicode

    /**
     * @covers ::__sleep
     * @expectedException BadMethodCallException
     */
    public function testSerializationIsBlocked()
    {
        serialize(new OpaqueEnvelope('secret'));
    } // testSerializationIsBlocked

    /**
     * @covers ::jsonSerialize
     */
    public function testJsonEncodingIsMasked()
    {
        $secret = 'secret';
        $json = json_encode(new OpaqueEnvelope($secret));
        $this->assertNotContains(
            $secret,
            $json,
            'The JSON should not contain the secret'
        );
    } // testJsonEncodingIsMasked
}
