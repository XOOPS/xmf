<?php declare(strict_types=1);

namespace Xmf\Test;

use Xmf\Ulid;

/**
 *
 */
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
     * It tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
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
        $ulid = Ulid::generate();

        // Assert that the ULID string is valid.
        $this->assertTrue(Ulid::isValid($ulid));

        // Assert that the ULID string is always in uppercase.
        $this->assertEquals($ulid, \strtoupper($ulid));

        // Assert that the ULID string is unique.
        $this->assertNotEquals($ulid, Ulid::generate());

        $ulid1 = Ulid::generate();
        $this->assertTrue(Ulid::isValid($ulid1));
        \usleep(2000);  // Wait for 2 milliseconds to ensure a different timestamp
        $ulid2 = Ulid::generate();

        $this->assertNotEquals($ulid1, $ulid2, 'ULIDs should be unique');
        $this->assertTrue(\strcasecmp($ulid1, $ulid2) < 0, 'ULIDs should collate correctly');
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testGenerateUlidUpperCase()
    {
        $ulid = Ulid::generate(true);
        $this->assertTrue(Ulid::isValid($ulid));
        $this->assertEquals(\strtoupper($ulid), $ulid);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testGenerateUlidLowerCase()
    {
        $ulid = Ulid::generate(false);
        $this->assertTrue(Ulid::isValid($ulid));
        $this->assertEquals(\strtolower($ulid), $ulid);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testDecode()
    {
        $ulid = Ulid::generate();

        // Decode the ULID string.
        $components = Ulid::decode($ulid);

        // Assert that the decoded time and randomness components are valid.
        $this->assertGreaterThan(0, $components['time']);
        $this->assertGreaterThan(0, $components['rand']);

        // Assert that the decoded time and randomness components are within the valid range.
        $this->assertLessThanOrEqual(PHP_INT_MAX, $components['time']);
        $this->assertLessThanOrEqual(PHP_INT_MAX, $components['rand']);
    }

    /**
     * Tests that the `generate()` method generates a lowercase ULID when configured to do so.
     *
     * @covers Xmf\Ulid::generate
     * @throws \Exception
     */
    public function testGeneratesLowercaseIdentifierWhenConfigured()
    {
        $ulid = Ulid::generate(false); //generate lower case

        if (\method_exists($this, 'assertMatchesRegularExpression')) {
            $this->assertMatchesRegularExpression('/[0-9][a-z]/', $ulid);
        } else {
            $this->assertRegExp('/[0-9][a-z]/', $ulid);
        }
    }

    /**
     * Tests that the `generate()` method generates a 26-character ULID.
     *
     * @covers Xmf\Ulid::generate
     * @throws \Exception
     */
    public function testGeneratesTwentySixChars()
    {
        $ulid = Ulid::generate();

        $this->assertSame(26, \strlen($ulid));
    }

    /**
     * Tests that the `generate()` method generates ULIDs with different random characters when generated multiple times.
     *
     * @covers Xmf\Ulid::generate
     * @throws \Exception
     * @throws \Exception
     */
    public function testRandomnessWhenGeneratedMultipleTimes()
    {
        $a = Ulid::generate();
        \usleep(100);  // Wait for 100 microseconds to ensure a different timestamp
        $b = Ulid::generate();
        $this->assertLessThan($b, $a);

        // Using strcmp for lexicographical comparison
        $this->assertTrue(\strcmp($a, $b) < 0);

        // The time parts are different.
        $this->assertNotEquals(\substr($a, 0, 10), \substr($b, 0, 10));

        //the second ULID time part is bigger than the first ULID
        $this->assertGreaterThan(\substr($a, 0, 10), \substr($b, 0, 10));

        // The first 5-6 characters should be the same
        $this->assertEquals(\substr($a, 0, 5), \substr($b, 0, 5));

        //the random characters part should be different
        $this->assertNotEquals(\substr($a, 10), \substr($b, 10));
    }

    /**
     * Tests that the `generate()` method generates lexicographically sortable ULIDs.
     *
     * @covers Xmf\Ulid::generate
     * @throws \Exception
     * @throws \Exception
     */
    public function testGeneratesLexographicallySortableUlids()
    {
        $a = Ulid::generate();

        \usleep(1000);  // Wait for 1 millisecond to ensure a different timestamp

        $b = Ulid::generate();

        $ulids = [$b, $a];
        \usort($ulids, 'strcmp');

        $this->assertSame([$a, $b], $ulids);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testIsValid()
    {
        $ulid = Ulid::generate();

        // Assert that the isValid() method returns true for valid ULID strings.
        $this->assertTrue(Ulid::isValid($ulid));

        // Assert that the isValid() method returns false for invalid ULID strings.
        $invalidUlid = 'invalid-ulid';
        $this->assertFalse(Ulid::isValid($invalidUlid));
    }

    /**
     * @return void
     */
    public function testEncodeTime()
    {
        $time = 572826470852228;
        $timeChars = Ulid::encodeTime($time);  // Assumes encodeTime is public

        $this->assertEquals('G8ZE7509M4', $timeChars);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testDecodeTime()
    {
        $timeChars = 'G8ZE7509M4SNQGYN4H6GSNQGYN';
        $time = Ulid::decodeTime($timeChars);
        $this->assertEquals(572826470852228, $time);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testEncodeRandomness()
    {
        $randChars = Ulid::encodeRandomness();
        //Checking the length of randomness characters
        $this->assertEquals(16, \strlen($randChars));
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testDecodeRandomness()
    {
        $randChars = Ulid::generate();

        $rand = Ulid::decodeRandomness($randChars);
        // Assert that the randomness value is within the valid range.
        $this->assertGreaterThanOrEqual(0, $rand);
        $this->assertLessThanOrEqual(PHP_INT_MAX, $rand);

        // Assert that the randomness value is different from a known value.
        $this->assertNotEquals(1234567890, $rand);
    }


    /**
     * @return void
     * @throws \Exception
     */
    public function testGenerateUnique()
    {
        $ulid1 = Ulid::generate();
        $ulid2 = Ulid::generate();

        $this->assertNotEquals($ulid1, $ulid2);
    }

    /**
     * @return void
     */
    public function testDecodeException()
    {

        $invalidUlid = 'invalid-ulid';

        $this->expectException(\InvalidArgumentException::class);
        Ulid::decode($invalidUlid);
    }

    /**
     * @return void
     */
    public function testIsValidException()
    {
        $invalidUlid = 'invalid-ulid';

        $isValid = Ulid::isValid($invalidUlid);

        $this->assertFalse($isValid);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testDecodeUlid()
    {
        $ulid = Ulid::generate();
        $components = Ulid::decode($ulid);

        $this->assertTrue($components['rand'] >= 0 && $components['rand'] < (32 ** 16));
    }

    /**
     * @return void
     */
    public function testDecodeInvalidUlid()
    {
        $invalidUlid = 'invalid-ulid';

        // Assert that the decode() method throws an exception of the correct type for invalid ULID strings.
        $this->expectException(\InvalidArgumentException::class);
        Ulid::decode($invalidUlid);
    }

    // Validate ULID

    /**
     * @return void
     * @throws \Exception
     */
    public function testValidateUlid()
    {
        $ulid = Ulid::generate();

        $this->assertTrue(Ulid::isValid($ulid));
    }

    /**
     * @return void
     */
    public function testValidateInvalidUlid()
    {
        //INVALID
        $this->assertFalse(Ulid::isValid('invalid-ulid-string'));
    }

    // Test ULID Generation:

    /**
     * @return void
     * @throws \Exception
     */
    public function testUlidGeneration()
    {
        $ulid = Ulid::generate();
        $this->assertNotEmpty($ulid);
        $this->assertSame(26, \strlen($ulid));
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testUlidUniqueness()
    {
        $ulid1 = Ulid::generate();
        \usleep(1000);  // Wait for 1 millisecond to ensure a different timestamp
        $ulid2 = Ulid::generate();
        $this->assertNotEquals($ulid1, $ulid2);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testCaseSensitivity()
    {
        $ulidUpperCase = Ulid::generate(true);
        $ulidLowerCase = Ulid::generate(false);

        // Assert that the two ULID strings are different.
        $this->assertNotEquals($ulidUpperCase, $ulidLowerCase);

        echo "ulidUpperCase: $ulidUpperCase\n";
        echo "ulidLowerCase: $ulidLowerCase\n";
    }

    // Test ULID Decoding:

    /**
     * @return void
     * @throws \Exception
     */
    public function testUlidDecoding()
    {
        $ulid = Ulid::generate();
        $components = Ulid::decode($ulid);
        $this->assertArrayHasKey('time', $components);
        $this->assertArrayHasKey('rand', $components);
    }

    /**
     * @return void
     */
    public function testInvalidUlidDecoding()
    {

        $this->expectException(\InvalidArgumentException::class);
        Ulid::decode('invalidulid');
    }

    // Test ULID Encoding:
    // (assuming you have a method to encode time and randomness separately)
    /**
     * @return void
     */
    public function testTimeEncoding()
    {
        $time = Ulid::microtimeToUlidTime(\microtime(true));
        $encodedTime = Ulid::encodeTime($time);  // Assumes encodeTime is public
        $this->assertNotEmpty($encodedTime);
    }

    // Test ULID Validity Checking:

    /**
     * @return void
     * @throws \Exception
     */
    public function testValidUlid()
    {
        $ulid = Ulid::generate();
        $this->assertTrue(Ulid::isValid($ulid));
    }

    /**
     * @return void
     */
    public function testInvalidUlid()
    {
        $this->assertFalse(Ulid::isValid('invalidulid'));
    }

    // Test Lexicographic Order:

    /**
     * @return void
     * @throws \Exception
     */
    public function testLexicographicOrder()
    {
        $ulid1 = Ulid::generate();
        \usleep(1000);
        $ulid2 = Ulid::generate();
        $this->assertTrue(\strcmp($ulid1, $ulid2) < 0);
    }

    // Test Microtime Conversion:

    /**
     * @return void
     */
    public function testMicrotimeConversion()
    {
        $microtime = \microtime(true);
        $ulidTime = Ulid::microtimeToUlidTime($microtime);

        $this->assertIsInt($ulidTime);
        // Check if the time is within a reasonable range (e.g., since the year 2000)
        $this->assertGreaterThanOrEqual($ulidTime, 946684800000000);
    }

    // Test the decoding of time from a given ULID

    /**
     * @return void
     * @throws \Exception
     */
    public function testDecodeTimeInt()
    {
        $ulid = Ulid::generate();
        $decodedTime = Ulid::decodeTime($ulid); // Assumes decodeTime is public
        $this->assertIsInt($decodedTime);
    }

    // Test the decoding of randomness from a given ULID

    /**
     * @return void
     * @throws \Exception
     */
    public function testDecodeRandomnessInt()
    {
        $ulid = Ulid::generate();
        $decodedRandomness = Ulid::decodeRandomness($ulid); // Assumes decodeRandomness is public

        $this->assertIsInt($decodedRandomness);
    }

    // Test the encoding of randomness

    /**
     * @return void
     * @throws \Exception
     */
    public function testEncodeRandomnessNotEmpty()
    {
        $encodedRandomness = Ulid::encodeRandomness(); // Assumes encodeRandomness is public
        $this->assertNotEmpty($encodedRandomness);
    }

    // Test for valid random value range

    /**
     * @return void
     * @throws \Exception
     */
    public function testRandomValueRange()
    {
        $ulid = Ulid::generate();
        $components = Ulid::decode($ulid);
        $this->assertGreaterThanOrEqual(0, $components['rand']);
        // Assuming a maximum value for the random component:
        $this->assertLessThanOrEqual(PHP_INT_MAX, $components['rand']);
    }

    // Test encoding and decoding consistency

    /**
     * @return void
     * @throws \Exception
     */
    public function testEncodingDecodingConsistency()
    {
        $ulid = Ulid::generate();
        $components = Ulid::decode($ulid);
        $encodedTime = Ulid::encodeTime($components['time']); // Assumes encodeTime is public
        $this->assertEquals(\substr($ulid, 0, 10), $encodedTime);
    }

    // Test if the microtimeToUlidTime function is working as expected

    /**
     * @return void
     */
    public function testMicrotimeToUlidTimeFunction()
    {
        $microtime = \microtime(true);
        $ulidTime = Ulid::microtimeToUlidTime($microtime);

        $this->assertIsInt($ulidTime);

        // Check if the time is within a reasonable range (e.g., since the year 2000)
        $this->assertGreaterThanOrEqual($ulidTime, 946684800000000);
    }

    /**
     * Test for valid ULID string format
     * @throws \Exception
     */
    public function testValidUlidString()
    {
        $ulid = Ulid::generate();

        $this->assertTrue(Ulid::isValid($ulid));
    }

    /**
     * Test for invalid ULID string format
     */
    public function testInvalidUlidString()
    {
        $invalidUlid = 'INVALID_ULID_STRING';

        $this->assertFalse(Ulid::isValid($invalidUlid));
    }

    /**
     * Test for case insensitivity in ULID validation
     * @throws \Exception
     */
    public function testCaseInsensitivity()
    {
        $ulid = Ulid::generate(false);  // generate lowercase ULID
        $this->assertTrue(Ulid::isValid(\strtoupper($ulid)));
    }

    /**
     * Test exception handling for invalid ULID string in decode method
     */
    public function testDecodeExceptionHandling()
    {

        $this->expectException(\InvalidArgumentException::class);
        $invalidUlid = 'INVALID_ULID_STRING';
        Ulid::decode($invalidUlid);
    }

    /**
     * Test exception handling for invalid ULID string in decodeTime method
     */
    public function testDecodeTimeExceptionHandling()
    {
        $invalidUlid = 'INVALID_ULID_STRING';

        $this->expectException(\InvalidArgumentException::class);
        Ulid::decodeTime($invalidUlid);  // Assumes decodeTime is public
    }

    /**
     * Test exception handling for invalid ULID string in decodeRandomness method
     */
    public function testDecodeRandomnessExceptionHandling()
    {
        $invalidUlid = 'INVALID_ULID_STRING';

        $this->expectException(\InvalidArgumentException::class);
        Ulid::decodeRandomness($invalidUlid);  // Assumes decodeRandomness is public
    }
}
