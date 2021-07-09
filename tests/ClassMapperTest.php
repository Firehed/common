<?php

namespace Firehed\Common;

use InvalidArgumentException;

/**
 * @coversDefaultClass Firehed\Common\ClassMapper
 * @covers ::<protected>
 * @covers ::<private>
 */
class ClassMapperTest extends \PHPUnit\Framework\TestCase
{

    private function getClassMapper(): ClassMapper
    {
        $map = [
            'user/profile/(?P<id>\d+)' => 'UserProfileController',
            'user/friend/(\d+)' => 'UserFriendController',
            'user/me' => 'UserMeController',
        ];
        return new ClassMapper($map);
    }

    /**
     * @covers ::__construct
     * @dataProvider sources
     */
    public function testConstruct($source): void
    {
        $this->assertInstanceOf(
            'Firehed\Common\ClassMapper',
            new ClassMapper($source)
        );
    }

    /**
     * @covers ::__construct
     * @dataProvider invalidSources
     */
    public function testInvalidConstruct($source): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ClassMapper($source);
    }


    /**
     * @covers ::search
     */
    public function testSearch(): void
    {
        $search_url = 'user/profile/12345';
        list($class, $data) = $this->getClassMapper()->search($search_url);
        $this->assertEquals("UserProfileController", $class, "Class was incorrect");
        $this->assertEquals(["id" => "12345"], $data, "Data was incorrect");
    }

    /**
     * @covers ::filter
     */
    public function testFilter(): void
    {
        $map = $this->getClassMapper();
        $this->assertSame(
            $map,
            $map->filter('some_filter'),
            'filter should be chainable'
        );
    }

    /**
     * @covers ::search
     */
    public function testSearchWithNoDataInMatch(): void
    {
        $search_url = 'user/me';
        list($class, $data) = $this->getClassMapper()->search($search_url);
        $this->assertEquals("UserMeController", $class, "Class was incorrect");
        $this->assertEquals([], $data, "Data was incorrect");
    }


    /**
     * @covers ::search
     */
    public function testSearchWithNoNamedData(): void
    {
        $search_url = 'user/friend/12345';
        list($class, $data) = $this->getClassMapper()->search($search_url);
        $this->assertEquals("UserFriendController", $class, "Class was incorrect");
        $this->assertEquals([], $data, "Data was incorrect");
    }

    /**
     * @covers ::search
     */
    public function testSearchNoMatch(): void
    {
        $search_url = 'user/metoo';
        list($class, $data) = $this->getClassMapper()->search($search_url);
        $this->assertNull($class, "Class was incorrect");
        $this->assertNull($data, "Data was incorrect");
    }

    /** @covers ::search */
    public function testRecursiveSearch(): void
    {
        $map = [
            'user/' => [
                'profile/(?P<id>\d+)' => 'UserProfileController',
                'friend/(\d+)' => 'UserFriendController',
                'me' => 'UserMeController',
            ],
        ];
        $mapper = new ClassMapper($map);

        $search = 'user/profile/12345';
        list($class, $data) = $mapper->search($search);
        $this->assertEquals("UserProfileController", $class, "Class was incorrect");
        $this->assertEquals(["id" => "12345"], $data, "Data was incorrect");
    }

    /** @covers ::search */
    public function testFilteredSearch()
    {
        $map = [
            'GET' => [
                'user/profile/(?P<id>\d+)' => 'UserProfileGetController',
                'user/friend/(\d+)' => 'UserFriendGetController',
                'user/me' => 'UserMeGetController',
            ],
            'POST' => [
                'user/profile/(?P<id>\d+)' => 'UserProfilePostController',
                'user/friend/(\d+)' => 'UserFriendPostController',
                'user/me' => 'UserMePostController',
            ],
        ];
        $mapper = new ClassMapper($map);
        $search = 'user/me';
        list($class, $data) = $mapper->filter('GET')->search($search);
        $this->assertEquals('UserMeGetController', $class);
        $this->assertEquals([], $data);
        // Filtering should not destroy the original=
        list($class, $data) = $mapper->filter('POST')->search($search);
        $this->assertEquals('UserMePostController', $class);
        $this->assertEquals([], $data);
        list($class, $data) = $mapper->filter('PATCH')->search($search);
        $this->assertNull($class, 'Filter with no data should return null on class');
        $this->assertNull($data, 'Filter with no data should return null on data');
    }

    /** @covers ::search */
    public function testMultipleFiltersWithDeepSearch(): void
    {
        $map = [
            'GET' => [
                'application/json' => [
                    'user/' => [
                        '(?P<id>\d+)' => 'UserIdGetJsonController'
                    ],
                ],
            ],
        ];
        $mapper = new ClassMapper($map);
        list($class, $data) = $mapper->filter('GET')
            ->filter('application/json')
            ->search('user/12345');
        $this->assertSame(
            'UserIdGetJsonController',
            $class,
            'The wrong class was returned'
        );
        $this->assertSame(
            ['id' => '12345'],
            $data,
            'The wrong data was returned'
        );
    }


    // -( DataProviders )------------------------------------------------------

    public function sources()
    {
        return [
            [['a' => 'b']], // Straight array input
            [__DIR__ . '/fixtures/ClassMapper/map.json'], // JSON file
            [__DIR__ . '/fixtures/ClassMapper/map.php'], // PHP file
        ];
    }

    public function invalidSources()
    {
        do {
            $nonexistant_file = md5(random_bytes(16));
        } while (file_exists($nonexistant_file));
        return [
            [$nonexistant_file],
            [__DIR__ . '/fixtures/ClassMapper/map.foo'], // Unknown type
            [__DIR__ . '/fixtures/ClassMapper/bad.json'], // Invalid JSON format
        ];
    }
}
