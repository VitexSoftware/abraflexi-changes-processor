<?php

namespace Test\AbraFlexi\Processor\Plugins;

use AbraFlexi\Processor\Plugins\FakturaVydana;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2022-06-21 at 09:48:25.
 */
class FakturaVydanaTest extends \PHPUnit\Framework\TestCase {

    /**
     * @var FakturaVydana
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void {
        $this->object = new FakturaVydana();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void {
        
    }

    /**
     * @covers AbraFlexi\Processor\Plugins\FakturaVydana::isSettled
     * @todo   Implement testisSettled().
     */
    public function testisSettled() {
        $this->assertEquals('', $this->object->isSettled());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Processor\Plugins\FakturaVydana::isStorned
     * @todo   Implement testisStorned().
     */
    public function testisStorned() {
        $this->assertEquals('', $this->object->isStorned());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Processor\Plugins\FakturaVydana::create
     * @todo   Implement testcreate().
     */
    public function testcreate() {
        $this->assertEquals('', $this->object->create());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Processor\Plugins\FakturaVydana::update
     * @todo   Implement testupdate().
     */
    public function testupdate() {
        $this->assertEquals('', $this->object->update());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Processor\Plugins\FakturaVydana::getMetaState
     * @todo   Implement testgetMetaState().
     */
    public function testgetMetaState() {
        $this->assertEquals('', $this->object->getMetaState());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Processor\Plugins\FakturaVydana::isReminded
     * @todo   Implement testisReminded().
     */
    public function testisReminded() {
        $this->assertEquals('', $this->object->isReminded());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

    /**
     * @covers AbraFlexi\Processor\Plugins\FakturaVydana::getFirmaObject
     * @todo   Implement testgetFirmaObject().
     */
    public function testgetFirmaObject() {
        $this->assertEquals('', $this->object->getFirmaObject());
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete('This test has not been implemented yet.');
    }

}
