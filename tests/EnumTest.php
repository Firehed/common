<?php

namespace Firehed\Common;

/**
 * @coversDefaultClass Firehed\Common\Enum
 * @covers ::<protected>
 * @covers ::<private>
 */
class EnumTest extends \PHPUnit\Framework\TestCase
{


    /**
     * @covers ::__construct
     * @expectedException UnexpectedValueException
     * @expectedExceptionMessage Value not a const in enum Firehed\Common\EnumFixture
     */
    public function testBadValueInConstructThrows(): void
    {
        new EnumFixture(13);
    }

    /**
     * @covers ::__construct
     */
    public function testGoodValueInConstruct(): void
    {
        $this->assertInstanceOf('Firehed\Common\Enum', new EnumFixture(EnumFixture::May));
    }

    /**
     * @covers ::__construct
     */
    public function testEmptyConstructUsesDefault(): void
    {
        $this->assertInstanceOf('Firehed\Common\Enum', new EnumFixture());
    }

    /**
     * @covers ::__construct
     * @expectedException UnexpectedValueException
     */
    public function testNoDefaultEnumThrowsOnConstruct(): void
    {
        new NoDefaultEnumFixture();
    }

    /**
     * @covers ::is
     */
    public function testIsMatchScalar(): void
    {
        $f = new EnumFixture(EnumFixture::January);
        $this->assertTrue($f->is(EnumFixture::January));
    }

    /** @covers ::is */
    public function testIsMatchEnum(): void
    {
        $f = new EnumFixture(EnumFixture::January);
        $test = new EnumFixture(EnumFixture::January);
        $this->assertTrue($f->is($test));
    }

    /**
     * @covers ::is
     */
    public function testIsNoMatchScalar(): void
    {
        $f = new EnumFixture(EnumFixture::February);
        $this->assertFalse($f->is(EnumFixture::January));
    }

    /** @covers ::is */
    public function testNoMatchSameClassDifferentValue(): void
    {
        $f = new EnumFixture(EnumFixture::January);
        $test = new EnumFixture(EnumFixture::February);
        $this->assertFalse($f->is($test));
    }

    /** @covers ::is */
    public function testNoMatchDifferentClassSameValue(): void
    {
        $f = new EnumFixture(EnumFixture::January);
        $test = new EnumFixture2(EnumFixture2::January);
        $this->assertFalse($f->is($test));
    }

    /** @covers ::is */
    public function testNoMatchDifferentClassDifferentValue(): void
    {
        $f = new EnumFixture(EnumFixture::January);
        $test = new EnumFixture2(EnumFixture2::February);
        $this->assertFalse($f->is($test));
    }


    /**
     * @covers ::getConstList
     */
    public function testGetConstListWithDefault(): void
    {
        $exp =
            [ '__default' => 1
            , 'January' => 1
            , 'February' => 2
            , 'March' => 3
            , 'April' => 4
            , 'May' => 5
            , 'June' => 6
            , 'July' => 7
            , 'August' => 8
            , 'September' => 9
            , 'October' => 10
            , 'November' => 11
            , 'December' => 12
            ];
        $month = new EnumFixture();
        $this->assertEquals($exp, $month->getConstList(true));
    }

    /**
     * @covers ::getConstList
     */
    public function testGetConstListWithoutDefault(): void
    {
        $exp =
            [ 'January' => 1
            , 'February' => 2
            , 'March' => 3
            , 'April' => 4
            , 'May' => 5
            , 'June' => 6
            , 'July' => 7
            , 'August' => 8
            , 'September' => 9
            , 'October' => 10
            , 'November' => 11
            , 'December' => 12
            ];
        $month = new EnumFixture();
        $this->assertEquals($exp, $month->getConstList(false));
    }

    /**
     * @covers ::getValue
     */
    public function testGetValueWithDefault(): void
    {
        $m = new EnumFixture();
        $this->assertEquals(EnumFixture::__default, $m->getValue());
    }

    /**
     * @covers ::getValue
     */
    public function testGetValueSpecifiedInConstruct(): void
    {
        $m = new EnumFixture(EnumFixture::May);
        $this->assertEquals(EnumFixture::May, $m->getValue());
    }

    /**
     * @covers ::__invoke
     */
    public function testInvokeReturnsValue(): void
    {
        $m = new EnumFixture(EnumFixture::May);
        $this->assertEquals(EnumFixture::May, $m());
    }

    /**
     * @covers ::__callStatic
     */
    public function testStaticInvocationReturnsEnum(): void
    {
        $exp = new EnumFixture(EnumFixture::May);
        $this->assertEquals($exp, EnumFixture::May(), 'Calling constant as function should return the enum');
    }

    /**
     * @covers ::__callStatic
     * @expectedException UnexpectedValueException
     * @expectedExceptionMessage Value 'Firehed\Common\EnumFixture::NonDefinedConstant' not a const in enum Firehed\Common\EnumFixture
     */
    public function testStaticInvocationOfUndefinedValueThrows(): void
    {
        EnumFixture::NonDefinedConstant();
    }
}

class EnumFixture extends Enum
{

    const __default = self::January;

    const January = 1;
    const February = 2;
    const March = 3;
    const April = 4;
    const May = 5;
    const June = 6;
    const July = 7;
    const August = 8;
    const September = 9;
    const October = 10;
    const November = 11;
    const December = 12;
}

class EnumFixture2 extends Enum
{

    const January = 1;
    const February = 2;
}

class NoDefaultEnumFixture extends Enum
{

    const ONE = 1;
    const TWO = 2;
}
