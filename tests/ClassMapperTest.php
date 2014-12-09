<?php

namespace Firehed\Common;

/**
 * @coversDefaultClass Firehed\Common\ClassMapper
 * @covers ::<protected>
 * @covers ::<private>
 */
class ClassMapperTest extends \PHPUnit_Framework_TestCase {

    private function getClassMapper() {
        $map = [
            'user/profile/(?P<id>\d+)' => 'UserProfileController',
            'user/friend/(\d+)' => 'UserFriendController',
            'user/me' => 'UserMeController',
        ];
        return new ClassMapper($map);
    } // getClassMapper

    /**
     * @covers ::__construct
     * @dataProvider sources
     */
    public function testConstruct($source) {
        $this->assertInstanceOf('Firehed\Common\ClassMapper',
            new ClassMapper($source));
    } // testConstruct

    /**
     * @covers ::__construct
     * @dataProvider invalidSources
     * @expectedException InvalidArgumentException
     */
    public function testInvalidConstruct($source) {
        new ClassMapper($source);
    } // testInvalidConstruct


    /**
     * @covers ::search
     */
    public function testSearch() {
        $search_url = 'user/profile/12345';
        list($class, $data) = $this->getClassMapper()->search($search_url);
        $this->assertEquals("UserProfileController", $class, "Class was incorrect");
        $this->assertEquals(["id" => "12345"], $data, "Data was incorrect");
    } // testSearch

    /**
     * @covers ::search
     */
    public function testSearchWithNoDataInMatch() {
        $search_url = 'user/me';
        list($class, $data) = $this->getClassMapper()->search($search_url);
        $this->assertEquals("UserMeController", $class, "Class was incorrect");
        $this->assertEquals([], $data, "Data was incorrect");
    } // testSearchWithNoDataInMatch


    /**
     * @covers ::search
     */
    public function testSearchWithNoNamedData() {
        $search_url = 'user/friend/12345';
        list($class, $data) = $this->getClassMapper()->search($search_url);
        $this->assertEquals("UserFriendController", $class, "Class was incorrect");
        $this->assertEquals([], $data, "Data was incorrect");
    } // testSearchWithNoNamedData

    /**
     * @covers ::search
     */
    public function testSearchNoMatch() {
        $search_url = 'user/metoo';
        list($class, $data) = $this->getClassMapper()->search($search_url);
        $this->assertNull($class, "Class was incorrect");
        $this->assertNull($data, "Data was incorrect");
    } // testSearchNoMatch

    /** @covers ::search */
    public function testRecursiveSearch() {
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


    public function sources() {
        return [
            [['a' => 'b']], // Straight array input
            [__DIR__.'/fixtures/ClassMapper/map.json'], // JSON file
            [__DIR__.'/fixtures/ClassMapper/map.php'], // PHP file
        ];
    } // sources

    public function invalidSources() {
        do {
            $nonexistant_file = \Filesystem::readRandomCharacters(50);
        } while (file_exists($nonexistant_file));
        return [
            [$nonexistant_file],
            [__DIR__.'/fixtures/ClassMapper/map.foo'], // Unknown type
            [__DIR__.'/fixtures/ClassMapper/bad.json'], // Invalid JSON format
        ];
    } // invalidSources

}
