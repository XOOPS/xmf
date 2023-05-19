<?php
namespace Xmf\Test;

use Xmf\Ulid;

class UlidTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    /**
     * @throws \Exception
     */
    public function testGenerate()
    {
        $ulid = Ulid::generate();

        $this->assertRegExp('/^[0-9A-Z]{26}$/', \strtoupper($ulid));
    }
}

