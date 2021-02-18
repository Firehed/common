<?php

namespace Firehed\Common;

/**
 * @coversDefaultClass Firehed\Common\Bitmask
 * @covers ::<protected>
 * @covers ::<private>
 */
class BitmaskTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @covers ::__construct
     */
    public function testConstruct()
    {
        $mask = new Bitmask(1);
        $this->assertInstanceOf('Firehed\Common\Bitmask', $mask);
    } // testConstruct

    /**
     * @covers ::__construct
     */
    public function testConstructWithEnum()
    {
        $enum = new EnumForBitmask(EnumForBitmask::THREE);
        $mask = new Bitmask($enum);
        $this->assertInstanceOf('Firehed\Common\Bitmask', $mask);
    } // testConstructWithEnum

    /**
     * @covers ::has
     * @dataProvider goodBits
     */
    public function testHasBit(Bitmask $mask, $bit)
    {
        $this->assertTrue($mask->has($bit));
    }

    /**
     * @covers ::has
     * @dataProvider badBits
     */
    public function testNotHasBit(Bitmask $mask, $bit)
    {
        $this->assertFalse($mask->has($bit));
    }

    /**
     * @covers ::has
     * @dataProvider errorBits
     * @expectedException UnexpectedValueException
     */
    public function testHasBitError(Bitmask $mask, $bit)
    {
        $mask->has($bit);
    } // testHasBitError

    /**
     * @covers ::add
     * @dataProvider addBits
     */
    public function testAddBit(Bitmask $mask, $bit, $already_has, $negative_bit)
    {
        $this->assertFalse(
            $mask->has($negative_bit),
            'Mask should not have negative bit before other is added'
        );
        $this->assertSame($already_has, $mask->has($bit));
        $this->assertSame(
            $mask,
            $mask->add($bit),
            'Bitmask::add should be chainable'
        );
        $this->assertTrue(
            $mask->has($bit),
            'Mask should have bit after it is added'
        );
        $this->assertFalse(
            $mask->has($negative_bit),
            'Mask should still not have negative bit after other is added'
        );
    } // testAddBit

    /**
     * @covers ::remove
     * @dataProvider removeBits
     */
    public function testRemoveBit(Bitmask $mask, $bit, $already_has, $positive_bit)
    {
        $this->assertTrue(
            $mask->has($positive_bit),
            'Mask should have positive bit before other is removed'
        );
        $this->assertSame($already_has, $mask->has($bit));
        $this->assertSame(
            $mask,
            $mask->remove($bit),
            'Bitmask::removeshould be chainable'
        );
        $this->assertFalse(
            $mask->has($bit),
            'Mask should not have bit after it is removed'
        );
        $this->assertTrue(
            $mask->has($positive_bit),
            'Mask should still have positive bit after other is removed'
        );
    } // testRemoveBit

    public function goodBits()
    {
        return [
            [new Bitmask(0b1),  0b1],
            [new Bitmask(0b11), 0b01],
            [new Bitmask(0b11), 0b10],
            [new Bitmask(EnumForBitmask::ONE()), EnumForBitmask::ONE()],
        ];
    } // goodBits

    public function badBits()
    {
        return [
            [new Bitmask(0b01), 0b10],
            [new Bitmask(0b10), 0b11],
            [new Bitmask(0b10), 0b01],
            [new Bitmask(EnumForBitmask::TWO()), EnumForBitmask::ONE()],
        ];
    } // badBits

    public function errorBits()
    {
        return [
            // Incompatible ENUMs
            [new Bitmask(EnumForBitmask::ONE()), DifferentEnum::ONE()],
            // Mask typed to ENUM, test value is int
            [new Bitmask(EnumForBitmask::ONE()), 0b1],
            // Mask untyped, test value is ENUM
            [new Bitmask(1), EnumForBitmask::ONE()],
            // Test values are non-integers
            [new Bitmask(1), 'a'],
            [new Bitmask(1), 1.1],
            [new Bitmask(1), null],
            [new Bitmask(1), true],
            [new Bitmask(1), []],
            // Bonus dumbness
            [new Bitmask(EnumForBitmask::ONE()), 'a'],
        ];
    } // errorBits

    public function addBits()
    {
        return [
            [new Bitmask(0b100), 0b010, false, 0b0001],
            [new Bitmask(0b111), 0b010, true,  0b1001],
            [new Bitmask(0b001), 0b100, false, 0b0010],
            [new Bitmask(EnumForBitmask::ONE()), EnumForBitmask::TWO(), false,
                EnumForBitmask::THREE()],
        ];
    } // addBits

    public function removeBits()
    {
        return [
            [new Bitmask(0b110), 0b010, true, 0b100],
            [new Bitmask(0b101), 0b010, false, 0b100],
            [new Bitmask(0b1001), 0b010, false, 0b1001],
            [new Bitmask(EnumForBitmask::ALL()), EnumForBitmask::TWO(), true,
                EnumForBitmask::THREE()],
        ];
    } // removeBits
}

class EnumForBitmask extends Enum
{

    const ONE = 0b1;
    const TWO = 0b10;
    const THREE = 0b100;

    const ALL = 0b111;
}

class DifferentEnum extends Enum
{

    const ONE = 0b1;
}
