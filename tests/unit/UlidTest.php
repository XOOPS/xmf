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
     * Tests that the `generate()` method generates a unique ULID.
     *
     * @covers Xmf\Ulid::generate
     * @throws \Exception
     */
    public function testGenerate()
    {
        $ulid1 = Ulid::generate();
        usleep(2000);  // Wait for 2 milliseconds to ensure a different timestamp
        $ulid2 = Ulid::generate();

        $this->assertNotEquals($ulid1, $ulid2, 'ULIDs should be unique');
        $this->assertTrue(strcasecmp($ulid1, $ulid2) < 0, 'ULIDs should collate correctly');
    }

    /**
     * Tests that the `generate()` method generates a lowercase ULID when configured to do so.
     *
     * @covers Xmf\Ulid::generate
     */
    public function testGeneratesLowercaseIdentifierWhenConfigured()
    {
        $ulid = Ulid::generate(false); //generate lower case

        if (method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('/[0-9][a-z]/', $ulid);
        } else {
            $this->assertRegExp('/[0-9][a-z]/', $ulid);
        }
    }

    /**
     * Tests that the `generate()` method generates a 26-character ULID.
     *
     * @covers Xmf\Ulid::generate
     */
    public function testGeneratesTwentySixChars()
    {
        $ulid = Ulid::generate();

        $this->assertSame(26, strlen($ulid));
    }

    /**
     * Tests that the `generate()` method generates ULIDs with different random characters when generated multiple times.
     *
     * @covers Xmf\Ulid::generate
     */
    public function testRandomnessWhenGeneratedMultipleTimes()
    {
        $a = Ulid::generate();
        $b = Ulid::generate();

        // The time parts are different.
        $this->assertNotEquals(substr($a, 0, 10), substr($b, 0, 10));

        //the second ULID time part is bigger than the first ULID
        $this->assertGreaterThan(substr($a, 0, 10), substr($b, 0, 10));

        // Only the last time character should be different.
        $this->assertEquals(substr($a, 0, 9), substr($b, 0, 9));

        //the random characters part should be different
        $this->assertNotEquals(substr($a, 10), substr($b, 10));
    }

    /**
     * Tests that the `generate()` method generates lexicographically sortable ULIDs.
     *
     * @covers Xmf\Ulid::generate
     */
    public function testGeneratesLexographicallySortableUlids()
    {
        $a = Ulid::generate();

        sleep(1);

        $b = Ulid::generate();

        $ulids = [(string) $b, (string) $a];
        usort($ulids, 'strcmp');

        $this->assertSame([(string) $a, (string) $b], $ulids);
    }
}

