<?php

namespace Firehed\Common;

require_once __DIR__.'/fixtures/ClassMapGenerator/AmbigInterface.php';
require_once __DIR__.'/fixtures/ClassMapGenerator/FilteredInterface.php';
require_once __DIR__.'/fixtures/ClassMapGenerator/FooInterface.php';
require_once __DIR__.'/fixtures/ClassMapGenerator/CategoryInterface.php';

/**
 * @coversDefaultClass Firehed\Common\ClassMapGenerator
 * @covers ::<protected>
 * @covers ::<private>
 */
class ClassMapGeneratorTest extends \PHPUnit\Framework\TestCase {

    const FIXTURE_DIR = '/fixtures/ClassMapGenerator/';
    const EMPTY_DIR = '/fixtures/ClassMapGenerator/empty';

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
     * @covers ::addCategory
     */
    public function testAddCategory() {
        $generator = new ClassMapGenerator();
        $this->assertSame($generator,
            $generator->addCategory('categoryMethod'),
            'addCategory should return $this');
    } // testAddCategory

    /**
     * @covers ::addFilter
     */
    public function testAddFilter() {
        $generator = new ClassMapGenerator();
        $this->assertSame($generator,
            $generator->addFilter('filterMethod', 'return_value'),
            'addFilter should return $this');
    } // testAddFilter

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

    /** @covers ::generate */
    public function testNoFilterApplication() {
        $generator = new ClassMapGenerator();
        $generator->setMethod('getKey')
            ->setInterface('FilteredInterface')
            ->setPath(__DIR__.self::FIXTURE_DIR);
        $ret = $generator->generate();
        $this->assertArrayHasKey('FilteredBoth', $ret,
            'FilteredBoth should be included');
        $this->assertArrayHasKey('FilteredFirst', $ret,
            'FilteredFirst should be included');
        $this->assertArrayHasKey('FilteredSecond', $ret,
            'FilteredSecond should be included');
        $this->assertArrayHasKey('FilteredNone', $ret,
            'FilteredNone should be included');
    }

    /** @covers ::generate */
    public function testSingleFilterApplication() {
        $generator = new ClassMapGenerator();
        $generator->setMethod('getKey')
            ->setInterface('FilteredInterface')
            ->setPath(__DIR__.self::FIXTURE_DIR)
            ->addFilter('filterMethod', true);
        $ret = $generator->generate();
        $this->assertArrayHasKey('FilteredBoth', $ret,
            'FilteredBoth should be included');
        $this->assertArrayHasKey('FilteredFirst', $ret,
            'FilteredFirst should be included');
        $this->assertArrayNotHasKey('FilteredSecond', $ret,
            'FilteredSecond should not be included');
        $this->assertArrayNotHasKey('FilteredNone', $ret,
            'FilteredNone should not be included');
    }

    /** @covers ::generate */
    public function testMultipleFilterApplication() {
        $generator = new ClassMapGenerator();
        $generator->setMethod('getKey')
            ->setInterface('FilteredInterface')
            ->setPath(__DIR__.self::FIXTURE_DIR)
            ->addFilter('filterMethod', true)
            ->addFilter('secondFilterMethod', true);
        $ret = $generator->generate();
        $this->assertArrayHasKey('FilteredBoth', $ret,
            'FilteredBoth should be included');
        $this->assertArrayNotHasKey('FilteredFirst', $ret,
            'FilteredFirst should not be included');
        $this->assertArrayNotHasKey('FilteredSecond', $ret,
            'FilteredSecond should not be included');
        $this->assertArrayNotHasKey('FilteredNone', $ret,
            'FilteredNone should not be included');
    }

    /** @covers ::generate */
    public function testCategoryApplication() {
        $generator = new ClassMapGenerator();
        $generator->setMethod('getKey')
            ->setInterface('CategoryInterface')
            ->setPath(__DIR__.self::FIXTURE_DIR)
            ->addCategory('getMethod');
        $ret = $generator->generate();
        $this->checkGenerated($ret);
        $this->assertArrayHasKey('GET', $ret);
        $this->assertCount(2, $ret['GET'],
            'The two GET classes should be present');
        $this->assertArrayHasKey('POST', $ret);
        $this->assertCount(1, $ret['POST'],
            'The one POST class should be present');
    }

    /** @covers ::generate */
    public function testMultipleCategoryApplication() {
        $generator = new ClassMapGenerator();
        $generator->setMethod('getKey')
            ->setInterface('CategoryInterface')
            ->setPath(__DIR__.self::FIXTURE_DIR)
            ->addCategory('getVersion')
            ->addCategory('getMethod');
        $ret = $generator->generate();
        $this->assertArrayHasKey('2', $ret);
        $this->assertCount(2, $ret['2']);
        $this->assertArrayHasKey('GET', $ret['2']);
        $this->assertCount(2, $ret['2']['GET']);
        $this->assertArrayHasKey('POST', $ret['2']);
        $this->assertCount(1, $ret['2']['POST']);
    }

    /**
     * @covers ::generate
     */
    public function testSearchingEmptyDirectory() {
        $generator = new ClassMapGenerator();
        $generator->setMethod('getKey')
            ->setPath(__DIR__.self::EMPTY_DIR);
        $ret = $generator->generate();
        $this->assertCount(1, $ret,
            'Only the generated tag should be present');
        $this->checkGenerated($ret);
    }

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
        $this->assertArrayNotHasKey('AbstractFoo', $ret,
            'The AbstractFoo class should have been excluded');
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
        $cmd = sprintf("echo %s | php -l", escapeshellarg($this->param2));
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
