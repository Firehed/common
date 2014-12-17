<?php

namespace Firehed\Common;

require_once __DIR__.'/fixtures/ClassMapGenerator/AmbigInterface.php';
require_once __DIR__.'/fixtures/ClassMapGenerator/FooInterface.php';

/**
 * @coversDefaultClass Firehed\Common\ClassMapGenerator
 * @covers ::<protected>
 * @covers ::<private>
 */
class ClassMapGeneratorTest extends \PHPUnit_Framework_TestCase {

    const FIXTURE_DIR = '/fixtures/ClassMapGenerator/';

    // These are used in the file write tests
    private $called;
    private $param1;
    private $param2;

    public function setUp() {
        $this->called = false;
        $this->param1 = null;
        $this->param2 = null;
    } // setUp

    /**
     * @covers ::setMethod
     */
    public function testSetMethod() {
        $generator = new ClassMapGenerator();
        $this->assertSame($generator,
            $generator->setMethod('someMethod'),
            'setMethod should return $this');
    } // setMethod

    /**
     * @covers ::setInterface
     */
    public function testSetInterface() {
        $generator = new ClassMapGenerator();
        $this->assertSame($generator,
            $generator->setInterface('SomeNS\SomeInterface'),
            'setInterface should return $this');
    } // setInterface

    /**
     * @covers ::setNamespace
     */
    public function testSetNamespace() {
        $generator = new ClassMapGenerator();
        $this->assertSame($generator,
            $generator->setNamespace('Some\Name\Space'),
            'setNamespace should return $this');
    } // setNamespace

    /**
     * @covers ::setPath
     */
    public function testSetPath() {
        $generator = new ClassMapGenerator();
        $this->assertSame($generator,
            $generator->setPath('SomeNS\SomePath'),
            'setPath should return $this');
    } // setPath

    /**
     * @covers ::setFormat
     */
    public function testSetFormat() {
        $generator = new ClassMapGenerator();
        $this->assertSame($generator,
            $generator->setFormat(ClassMapGenerator::FORMAT_JSON),
            'setFormat should return $this');
    } // testSetFormat

    /**
     * @covers ::setFormat
     * @expectedException DomainException
     */
    public function testSetInvalidFormat() {
        $generator = new ClassMapGenerator();
        $generator->setFormat('this_is_not_a_format');
    } // testSetInvalidFormat

    /**
     * @covers ::setFormat
     * @expectedException BadMethodCallException
     */
    public function testSetFormatThrowsAfterSetOutputFile() {
        $generator = new ClassMapGenerator();
        $generator->setOutputFile('foo.php')
            ->setFormat(ClassMapGenerator::FORMAT_JSON);
    } // testSetFormatThrowsAfterSetOutputFile

    /**
     * @covers ::setOutputFile
     * @dataProvider outputFiles
     */
    public function testSetOutputFile($file, $is_error, $format = null) {
        $generator = new ClassMapGenerator();
        if ($format) {
            $generator->setFormat($format);
        }
        if ($is_error) {
            $this->setExpectedException('DomainException');
        }
        // When is_error the throw should naturally skip the assert
        $this->assertSame($generator,
            $generator->setOutputFile($file),
            'setOutputFile should return $this');
    } // testSetOutputFile

    /**
     * @covers ::generate
     * @expectedException BadMethodCallException
     */
    public function testGenerateFailsWithNoPath() {
        $generator = new ClassMapGenerator();
        $generator->setMethod('method')
            ->generate();
    } // testGenerateFailsWithNoPath

    /**
     * @covers ::generate
     * @expectedException BadMethodCallException
     */
    public function testGenerateFailsWithNoMethod() {
        $generator = new ClassMapGenerator();
        $generator->setPath(__DIR__)
            ->generate();
    } // testGenerateFailsWithNoMethod

    /**
     * @covers ::generate
     * @covers ::setInterface
     */
    public function testInterfaceFilterWorks() {
        $generator = new ClassMapGenerator();
        $generator->setMethod('getKey')
            ->setInterface('FooInterface')
            ->setPath(__DIR__.self::FIXTURE_DIR);
        $ret = $generator->generate();
        $this->assertArrayHasKey('Foo', $ret);
        $this->assertArrayNotHasKey('FooNoInt', $ret,
            'The FooNoInt class should have been excluded');
        $this->checkGenerated($ret);
    } // testInterfaceFilterWorks

    /**
     * @covers ::generate
     */
    public function testNamespacesAreHandledWhenSet() {
        $generator = new ClassMapGenerator();
        $generator->setMethod('getKey')
            ->setNamespace('A')
            ->setPath(__DIR__.self::FIXTURE_DIR.'A/');
        $ret = $generator->generate();

        $this->assertArrayNotHasKey('Foo', $ret);
        $this->assertArrayHasKey('A\Foo', $ret);
        $this->assertArrayHasKey('A\B\Foo', $ret);
        $this->assertArrayNotHasKey('B\Foo', $ret);
        $this->checkGenerated($ret);
    } // testNamespacesAreHandledWhenSet

    /**
     * @covers ::generate
     */
    public function testNamespacesAreSkippedWhenNot() {
        $generator = new ClassMapGenerator();
        $generator->setMethod('getKey')
            ->setPath(__DIR__.self::FIXTURE_DIR.'A/');
        $ret = $generator->generate();
        $this->assertArrayNotHasKey('Foo', $ret);
        $this->assertArrayNotHasKey('A\Foo', $ret);
        $this->assertArrayNotHasKey('A\B\Foo', $ret);
        $this->assertArrayNotHasKey('B\Foo', $ret);
        $this->checkGenerated($ret);
    } // testNamespacesAreSkippedWhenNot

    /**
     * @covers ::generate
     * @expectedException Exception
     */
    public function testAmbiguityIsRejected() {
        $generator = new ClassMapGenerator();
        $generator->setMethod('getKey')
            ->setInterface('AmbigInterface')
            ->setPath(__DIR__.self::FIXTURE_DIR);
        $generator->generate();
    } // testAmbiguityIsRejected

    /**
     * @covers ::generate
     */
    public function testJSONGeneration() {
        $generator = new ClassMapGenerator();
        $path = 'some_path.json';

        $generator->setMethod('getKey')
            ->setFormat(ClassMapGenerator::FORMAT_JSON)
            ->setOutputFile($path, [$this, 'mockWriter'])
            ->setNamespace('A\B')
            ->setPath(__DIR__.self::FIXTURE_DIR.'A/B/');
        $ret = $generator->generate();

        $this->assertTrue($this->called, "Writer was not called");
        $this->assertEquals($path,
            $this->param1,
            "First param to writer was not the path");

        $decoded_write = json_decode($this->param2, true);
        $this->assertSame(JSON_ERROR_NONE,
            json_last_error(),
            "Output should have been valid JSON");
        $this->assertEquals($ret,
            $decoded_write,
            "Returned JSON should have been the genereted output");

        $this->assertInternalType('array', $ret, 'Generate not an array');
        $this->checkGenerated($ret);
    } // testJSONGeneration

    /**
     * @covers ::generate
     */
    public function testPHPGeneration() {
        $generator = new ClassMapGenerator();
        $path = 'some_path.php';

        $generator->setMethod('getKey')
            ->setFormat(ClassMapGenerator::FORMAT_PHP)
            ->setOutputFile($path, [$this, 'mockWriter'])
            ->setNamespace('A\B')
            ->setPath(__DIR__.self::FIXTURE_DIR.'A/B/');
        $ret = $generator->generate();

        $this->assertTrue($this->called, "Writer function was not called");
        $this->assertEquals($path,
            $this->param1,
            "First param to writer was not the path");

        // Perform a syntax check on the generated output (rather than blindly
        // checking with eval() or something which would be tragically insecure
        $cmd = csprintf("echo %s | php -l", $this->param2)->getUnmaskedString();
        $exec_output = null;
        $return_code = null;
        exec($cmd, $exec_output, $return_code);
        $this->assertSame(0,
            $return_code,
            'Generated output should have been syntactically valid PHP');

        $this->assertInternalType('array', $ret, 'Generate not an array');
        $this->checkGenerated($ret);
    } // testPHPGeneration

    private function checkGenerated(array $ret) {
        $this->assertArrayHasKey('@gener'.'ated', $ret,
            'Return value should contain a generated hint');
    } // checkGenerated

    public function mockWriter($param1, $param2) {
        $this->called = true;
        $this->param1 = $param1;
        $this->param2 = $param2;
        return true;
    } // mockWriter

    public function outputFiles() {
        $dir = __DIR__.DIRECTORY_SEPARATOR;
        return [
            // Output path, should throw, optional setFormat param
            [$dir.'test.json', false],
            [$dir.'test.php', false],
            [$dir.'test.json', false, ClassMapGenerator::FORMAT_JSON],
            [$dir.'test.php', false, ClassMapGenerator::FORMAT_PHP],

            [$dir.'test.exe', true],
            [$dir.'test.json', true, ClassMapGenerator::FORMAT_PHP],
            [$dir.'test.php', true, ClassMapGenerator::FORMAT_JSON],
        ];
    } // outputFiles

}