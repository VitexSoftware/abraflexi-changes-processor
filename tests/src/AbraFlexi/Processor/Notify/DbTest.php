<?php

namespace Test\AbraFlexi\Processor\Notify;

use AbraFlexi\Processor\Notify\Db;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2022-06-21 at 09:48:23.
 */
class DbTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var Db
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void {
        $this->object = new Db();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void {
        
    }

    /**
     * @covers AbraFlexi\Processor\Notify\Db::notify
     * @todo   Implement testnotify().
     */
    public function testnotify() {
        $this->assertEquals('', $this->object->notify());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

}
