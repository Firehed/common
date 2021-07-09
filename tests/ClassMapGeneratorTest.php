<?php

namespace Firehed\Common;

require_once __DIR__ . '/fixtures/ClassMapGenerator/AmbigInterface.php';
require_once __DIR__ . '/fixtures/ClassMapGenerator/FilteredInterface.php';
require_once __DIR__ . '/fixtures/ClassMapGenerator/FooInterface.php';
require_once __DIR__ . '/fixtures/ClassMapGenerator/CategoryInterface.php';

use BadMethodCallException;
use DomainException;
use Exception;

/**
 * @coversDefaultClass Firehed\Common\ClassMapGenerator
 * @covers ::<protected>
 * @covers ::<private>
 */
class ClassMapGeneratorTest extends \PHPUnit\Framework\TestCase
{

    private const FIXTURE_DIR = '/fixtures/ClassMapGenerator/';
    private const EMPTY_DIR = '/fixtures/ClassMapGenerator/empty';

    // These are used in the file write tests
    private $called;
    private $param1;
    private $param2;

    public function setUp(): void
    {
        $this->called = false;
        $this->param1 = null;
        $this->param2 = null;
    }

    /**
     * @covers ::addCategory
     */
    public function testAddCategory(): void
    {
        $generator = new ClassMapGenerator();
        $this->assertSame(
            $generator,
            $generator->addCategory('categoryMethod'),
            'addCategory should return $this'
        );
    }

    /**
     * @covers ::addFilter
     */
    public function testAddFilter(): void
    {
        $generator = new ClassMapGenerator();
        $this->assertSame(
            $generator,
            $generator->addFilter('filterMethod', 'return_value'),
            'addFilter should return $this'
        );
    }

    /**
     * @covers ::setMethod
     */
    public function testSetMethod(): void
    {
        $generator = new ClassMapGenerator();
        $this->assertSame(
            $generator,
            $generator->setMethod('someMethod'),
            'setMethod should return $this'
        );
    }

    /**
     * @covers ::setInterface
     */
    public function testSetInterface(): void
    {
        $generator = new ClassMapGenerator();
        $this->assertSame(
            $generator,
            $generator->setInterface('SomeNS\SomeInterface'),
            'setInterface should return $this'
        );
    }

    /**
     * @covers ::setNamespace
     */
    public function testSetNamespace(): void
    {
        $generator = new ClassMapGenerator();
        $this->assertSame(
            $generator,
            $generator->setNamespace('Some\Name\Space'),
            'setNamespace should return $this'
        );
    }

    /**
     * @covers ::setPath
     */
    public function testSetPath(): void
    {
        $generator = new ClassMapGenerator();
        $this->assertSame(
            $generator,
            $generator->setPath('SomeNS\SomePath'),
            'setPath should return $this'
        );
    }

    /**
     * @covers ::setFormat
     */
    public function testSetFormat(): void
    {
        $generator = new ClassMapGenerator();
        $this->assertSame(
            $generator,
            $generator->setFormat(ClassMapGenerator::FORMAT_JSON),
            'setFormat should return $this'
        );
    }

    /**
     * @covers ::setFormat
     */
    public function testSetInvalidFormat(): void
    {
        $generator = new ClassMapGenerator();
        $this->expectException(DomainException::class);
        $generator->setFormat('this_is_not_a_format');
    }

    /**
     * @covers ::setFormat
     */
    public function testSetFormatThrowsAfterSetOutputFile(): void
    {
        $generator = new ClassMapGenerator();
        $this->expectException(BadMethodCallException::class);
        $generator->setOutputFile('foo.php')
            ->setFormat(ClassMapGenerator::FORMAT_JSON);
    }

    /**
     * @covers ::setOutputFile
     * @dataProvider outputFiles
     */
    public function testSetOutputFile($file, $is_error, $format = null): void
    {
        $generator = new ClassMapGenerator();
        if ($format) {
            $generator->setFormat($format);
        }
        if ($is_error) {
            $this->expectException(DomainException::class);
        }
        // When is_error the throw should naturally skip the assert
        $this->assertSame(
            $generator,
            $generator->setOutputFile($file),
            'setOutputFile should return $this'
        );
    }

    /**
     * @covers ::generate
     */
    public function testGenerateFailsWithNoPath(): void
    {
        $generator = new ClassMapGenerator();
        $this->expectException(BadMethodCallException::class);
        $generator->setMethod('method')
            ->generate();
    }

    /**
     * @covers ::generate
     */
    public function testGenerateFailsWithNoMethod(): void
    {
        $generator = new ClassMapGenerator();
        $this->expectException(BadMethodCallException::class);
        $generator->setPath(__DIR__)
            ->generate();
    }

    /** @covers ::generate */
    public function testNoFilterApplication(): void
    {
        $generator = new ClassMapGenerator();
        $generator->setMethod('getKey')
            ->setInterface('FilteredInterface')
            ->setPath(__DIR__ . self::FIXTURE_DIR);
        $ret = $generator->generate();
        $this->assertArrayHasKey(
            'FilteredBoth',
            $ret,
            'FilteredBoth should be included'
        );
        $this->assertArrayHasKey(
            'FilteredFirst',
            $ret,
            'FilteredFirst should be included'
        );
        $this->assertArrayHasKey(
            'FilteredSecond',
            $ret,
            'FilteredSecond should be included'
        );
        $this->assertArrayHasKey(
            'FilteredNone',
            $ret,
            'FilteredNone should be included'
        );
    }

    /** @covers ::generate */
    public function testSingleFilterApplication(): void
    {
        $generator = new ClassMapGenerator();
        $generator->setMethod('getKey')
            ->setInterface('FilteredInterface')
            ->setPath(__DIR__ . self::FIXTURE_DIR)
            ->addFilter('filterMethod', true);
        $ret = $generator->generate();
        $this->assertArrayHasKey(
            'FilteredBoth',
            $ret,
            'FilteredBoth should be included'
        );
        $this->assertArrayHasKey(
            'FilteredFirst',
            $ret,
            'FilteredFirst should be included'
        );
        $this->assertArrayNotHasKey(
            'FilteredSecond',
            $ret,
            'FilteredSecond should not be included'
        );
        $this->assertArrayNotHasKey(
            'FilteredNone',
            $ret,
            'FilteredNone should not be included'
        );
    }

    /** @covers ::generate */
    public function testMultipleFilterApplication(): void
    {
        $generator = new ClassMapGenerator();
        $generator->setMethod('getKey')
            ->setInterface('FilteredInterface')
            ->setPath(__DIR__ . self::FIXTURE_DIR)
            ->addFilter('filterMethod', true)
            ->addFilter('secondFilterMethod', true);
        $ret = $generator->generate();
        $this->assertArrayHasKey(
            'FilteredBoth',
            $ret,
            'FilteredBoth should be included'
        );
        $this->assertArrayNotHasKey(
            'FilteredFirst',
            $ret,
            'FilteredFirst should not be included'
        );
        $this->assertArrayNotHasKey(
            'FilteredSecond',
            $ret,
            'FilteredSecond should not be included'
        );
        $this->assertArrayNotHasKey(
            'FilteredNone',
            $ret,
            'FilteredNone should not be included'
        );
    }

    /** @covers ::generate */
    public function testCategoryApplication(): void
    {
        $generator = new ClassMapGenerator();
        $generator->setMethod('getKey')
            ->setInterface('CategoryInterface')
            ->setPath(__DIR__ . self::FIXTURE_DIR)
            ->addCategory('getMethod');
        $ret = $generator->generate();
        $this->checkGenerated($ret);
        $this->assertArrayHasKey('GET', $ret);
        $this->assertCount(
            2,
            $ret['GET'],
            'The two GET classes should be present'
        );
        $this->assertArrayHasKey('POST', $ret);
        $this->assertCount(
            1,
            $ret['POST'],
            'The one POST class should be present'
        );
    }

    /** @covers ::generate */
    public function testMultipleCategoryApplication(): void
    {
        $generator = new ClassMapGenerator();
        $generator->setMethod('getKey')
            ->setInterface('CategoryInterface')
            ->setPath(__DIR__ . self::FIXTURE_DIR)
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
    public function testSearchingEmptyDirectory(): void
    {
        $generator = new ClassMapGenerator();
        $generator->setMethod('getKey')
            ->setPath(__DIR__ . self::EMPTY_DIR);
        $ret = $generator->generate();
        $this->assertCount(
            1,
            $ret,
            'Only the generated tag should be present'
        );
        $this->checkGenerated($ret);
    }

    /**
     * @covers ::generate
     * @covers ::setInterface
     */
    public function testInterfaceFilterWorks(): void
    {
        $generator = new ClassMapGenerator();
        $generator->setMethod('getKey')
            ->setInterface('FooInterface')
            ->setPath(__DIR__ . self::FIXTURE_DIR);
        $ret = $generator->generate();
        $this->assertArrayHasKey('Foo', $ret);
        $this->assertArrayNotHasKey(
            'FooNoInt',
            $ret,
            'The FooNoInt class should have been excluded'
        );
        $this->assertArrayNotHasKey(
            'AbstractFoo',
            $ret,
            'The AbstractFoo class should have been excluded'
        );
        $this->checkGenerated($ret);
    }

    /**
     * @covers ::generate
     */
    public function testNamespacesAreHandledWhenSet(): void
    {
        $generator = new ClassMapGenerator();
        $generator->setMethod('getKey')
            ->setNamespace('A')
            ->setPath(__DIR__ . self::FIXTURE_DIR . 'A/');
        $ret = $generator->generate();

        $this->assertArrayNotHasKey('Foo', $ret);
        $this->assertArrayHasKey('A\Foo', $ret);
        $this->assertArrayHasKey('A\B\Foo', $ret);
        $this->assertArrayNotHasKey('B\Foo', $ret);
        $this->checkGenerated($ret);
    }

    /**
     * @covers ::generate
     */
    public function testNamespacesAreSkippedWhenNot(): void
    {
        $generator = new ClassMapGenerator();
        $generator->setMethod('getKey')
            ->setPath(__DIR__ . self::FIXTURE_DIR . 'A/');
        $ret = $generator->generate();
        $this->assertArrayNotHasKey('Foo', $ret);
        $this->assertArrayNotHasKey('A\Foo', $ret);
        $this->assertArrayNotHasKey('A\B\Foo', $ret);
        $this->assertArrayNotHasKey('B\Foo', $ret);
        $this->checkGenerated($ret);
    }

    /**
     * @covers ::generate
     */
    public function testAmbiguityIsRejected(): void
    {
        $generator = new ClassMapGenerator();
        $generator->setMethod('getKey')
            ->setInterface('AmbigInterface')
            ->setPath(__DIR__ . self::FIXTURE_DIR);
        $this->expectException(Exception::class);
        $generator->generate();
    }

    /**
     * @covers ::generate
     */
    public function testJSONGeneration(): void
    {
        $generator = new ClassMapGenerator();
        $path = 'some_path.json';

        $generator->setMethod('getKey')
            ->setFormat(ClassMapGenerator::FORMAT_JSON)
            ->setOutputFile($path, [$this, 'mockWriter'])
            ->setNamespace('A\B')
            ->setPath(__DIR__ . self::FIXTURE_DIR . 'A/B/');
        $ret = $generator->generate();

        $this->assertTrue($this->called, "Writer was not called");
        $this->assertEquals(
            $path,
            $this->param1,
            "First param to writer was not the path"
        );

        $decoded_write = json_decode($this->param2, true);
        $this->assertSame(
            JSON_ERROR_NONE,
            json_last_error(),
            "Output should have been valid JSON"
        );
        $this->assertEquals(
            $ret,
            $decoded_write,
            "Returned JSON should have been the genereted output"
        );

        $this->assertIsArray($ret, 'Generate not an array');
        $this->checkGenerated($ret);
    }

    /**
     * @covers ::generate
     */
    public function testPHPGeneration(): void
    {
        $generator = new ClassMapGenerator();
        $path = 'some_path.php';

        $generator->setMethod('getKey')
            ->setFormat(ClassMapGenerator::FORMAT_PHP)
            ->setOutputFile($path, [$this, 'mockWriter'])
            ->setNamespace('A\B')
            ->setPath(__DIR__ . self::FIXTURE_DIR . 'A/B/');
        $ret = $generator->generate();

        $this->assertTrue($this->called, "Writer function was not called");
        $this->assertEquals(
            $path,
            $this->param1,
            "First param to writer was not the path"
        );

        // Perform a syntax check on the generated output (rather than blindly
        // checking with eval() or something which would be tragically insecure
        $cmd = sprintf("echo %s | php -l", escapeshellarg($this->param2));
        $exec_output = null;
        $return_code = null;
        exec($cmd, $exec_output, $return_code);
        $this->assertSame(
            0,
            $return_code,
            'Generated output should have been syntactically valid PHP'
        );

        $this->assertIsArray($ret, 'Generate not an array');
        $this->checkGenerated($ret);
    }

    private function checkGenerated(array $ret)
    {
        $this->assertArrayHasKey(
            '@gener' . 'ated',
            $ret,
            'Return value should contain a generated hint'
        );
    }

    public function mockWriter($param1, $param2)
    {
        $this->called = true;
        $this->param1 = $param1;
        $this->param2 = $param2;
        return true;
    }

    public function outputFiles()
    {
        $dir = __DIR__ . DIRECTORY_SEPARATOR;
        return [
            // Output path, should throw, optional setFormat param
            [$dir . 'test.json', false],
            [$dir . 'test.php', false],
            [$dir . 'test.json', false, ClassMapGenerator::FORMAT_JSON],
            [$dir . 'test.php', false, ClassMapGenerator::FORMAT_PHP],

            [$dir . 'test.exe', true],
            [$dir . 'test.json', true, ClassMapGenerator::FORMAT_PHP],
            [$dir . 'test.php', true, ClassMapGenerator::FORMAT_JSON],
        ];
    }
}
