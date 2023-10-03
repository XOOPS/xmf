<?php
namespace Xmf\Test;

use Xmf\Ulid;

class UlidTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
    }

    /**
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
    public function testGeneratesLowercaseIdentifierWhenConfigured(): void
    {
        $ulid = Ulid::generate(false); //generate lower case

        $this->assertMatchesRegularExpression('/[0-9][a-z]/', $ulid);
    }

    public function testGeneratesTwentySixChars(): void
    {
        $this->assertSame(26, strlen(Ulid::generate()));
    }

    public function testRandomnessWhenGeneratedMultipleTimes(): void
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

    public function testGeneratesLexographicallySortableUlids(): void
    {
        $a = Ulid::generate();

        sleep(1);

        $b = Ulid::generate();

        $ulids = [(string) $b, (string) $a];
        usort($ulids, 'strcmp');

        $this->assertSame([(string) $a, (string) $b], $ulids);
    }
}

