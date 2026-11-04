<?php

declare(strict_types=1);

namespace Xmf\Test\Security;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Xmf\Security\DecompressionException;
use Xmf\Security\Format;
use Xmf\Security\Serializer;
use Xmf\Test\Fixtures\HookedArrayObject;
use Xmf\Test\Fixtures\HookedProperties;
use Xmf\Test\Fixtures\LongNamedStatus;

#[CoversClass(Serializer::class)]
class SerializerTest extends \PHPUnit\Framework\TestCase
{
    private const MAX_SIZE = 5000000;

    protected function tearDown(): void
    {
        Serializer::enableDebug(false);
        Serializer::setLegacyLogger(null);
    }

    // ---------------------------------------------------------------- JSON

    public function testJsonRoundTrip(): void
    {
        $data = ['name' => 'Ünïcode/path', 'list' => [1, 2.5, true, null]];
        $json = Serializer::toJson($data);

        $this->assertSame('{"name":"Ünïcode/path","list":[1,2.5,true,null]}', $json);
        $this->assertSame($data, Serializer::fromJson($json));
    }

    public function testToJsonKeepsTheCallersJsonErrorState(): void
    {
        json_decode('{');
        try {
            Serializer::toJson([1.5]);
            $this->assertSame(JSON_ERROR_SYNTAX, json_last_error());
        } finally {
            json_decode('[]'); // no stale error for later tests
        }
    }

    public function testToJsonRejectsOversizedOutput(): void
    {
        $this->expectException(\RuntimeException::class);
        Serializer::toJson(str_repeat('a', self::MAX_SIZE));
    }

    public function testToJsonRejectsSharedReferenceExpansionBeforeEncoding(): void
    {
        // each level holds the one below twice through a reference: 2^40 values from ~1.7 KB
        $data = 'x';
        for ($i = 0; $i < 40; $i++) {
            $level = [$data];
            $level[1] = &$level[0];
            $data = $level;
            unset($level);
        }
        $value = Serializer::fromPhp(serialize($data));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('exceeds maximum allowed size');
        Serializer::toJson($value);
    }

    /**
     * Builders, so the large fixtures exist only while their own case runs
     *
     * @return array<string, array{\Closure(): array<mixed>}>
     */
    public static function oversizedJsonProvider(): array
    {
        return [
            // 1000 entries share one 100 KB string: 100 MB of JSON from 100 KB of memory
            'shared string' => [static fn (): array => array_fill(0, 1000, str_repeat('a', 100000))],
            // "[]," per entry: 6 MB of JSON
            'empty arrays' => [static fn (): array => array_fill(0, 2000000, [])],
            // "\"100000\":0," per entry: 6.6 MB of JSON
            'integer object keys' => [static fn (): array => array_fill_keys(range(100000, 699999), 0)],
            // one object with properties named 0..4999 ("{\"0\":0,..."), 200 times: 8.6 MB of JSON
            'numeric object properties' => [static fn (): array => array_fill(0, 200, (object) array_fill(0, 5000, 0))],
            // scalars are charged their encoded length, not one byte: 6.3 MB, 7.5 MB and 6 MB of JSON
            'large integers' => [static fn (): array => array_fill(0, 300000, PHP_INT_MAX)],
            'long floats' => [static fn (): array => array_fill(0, 300000, -1.2345678901234567E-300)],
            'booleans' => [static fn (): array => array_fill(0, 1000000, false)],
            // json_encode() writes the storage of internal iterable objects, which get_object_vars() does not see
            'ArrayObject entries' => [static fn (): array => [new \ArrayObject(array_fill(0, 1000, str_repeat('a', 100000)))]],
            'ArrayIterator entries' => [static fn (): array => [new \ArrayIterator(array_fill(0, 1000, str_repeat('a', 100000)))]],
        ];
    }

    /**
     * @param \Closure(): array<mixed> $build
     */
    #[DataProvider('oversizedJsonProvider')]
    public function testToJsonRejectsOversizedDataBeforeEncoding(\Closure $build): void
    {
        $data = $build();
        memory_reset_peak_usage();
        $before = memory_get_peak_usage();
        try {
            Serializer::toJson($data);
            $this->fail('expected the size guard to reject the data');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('exceeds maximum allowed size', $e->getMessage());
        }
        $this->assertLessThan($before + 1000000, memory_get_peak_usage(), 'JSON was built before the guard ran');
    }

    public function testToJsonCountsOnlyThePropertiesJsonEncodeWrites(): void
    {
        $object = new class {
            public string $shown = 'a';
            // never encoded, so it must not count against the budget
            protected string $hidden;

            public function __construct()
            {
                $this->hidden = str_repeat('h', 6000000);
            }
        };

        $this->assertSame('{"shown":"a"}', Serializer::toJson($object));
        $this->assertSame('{"k":"v"}', Serializer::toJson(new \ArrayObject(['k' => 'v'])));
    }

    public function testToJsonCountsEachPropertyOfAnInternalSubclassOnce(): void
    {
        // a property both get_object_vars() and an array cast see must not count twice
        $object = new class (['k' => 'v'], \ArrayObject::STD_PROP_LIST) extends \ArrayObject {
            public string $text = '';
        };
        $object->text = str_repeat('a', 3000000);

        $this->assertSame(3000011, \strlen(Serializer::toJson($object)));
    }

    #[RequiresPhp('>= 8.4')]
    public function testToJsonCountsValuesReturnedByPropertyGetHooks(): void
    {
        // json_encode() calls get hooks; a virtual property is absent from an array cast
        $this->assertSame('{"virtual":"vv","backed":"RAW"}', Serializer::toJson(new HookedProperties(2)));

        $this->assertSame('{"virtual":"vv"}', Serializer::toJson(new HookedArrayObject(2)));
    }

    /**
     * @return array<string, array{\Closure(): object}>
     */
    public static function largeGetHookProvider(): array
    {
        return [
            'user class' => [static fn (): object => new HookedProperties(100000)],
            'ArrayObject with STD_PROP_LIST' => [static fn (): object => new HookedArrayObject(100000)],
        ];
    }

    /**
     * @param \Closure(): object $build
     */
    #[RequiresPhp('>= 8.4')]
    #[DataProvider('largeGetHookProvider')]
    public function testToJsonRejectsLargePropertyGetHooksBeforeEncoding(\Closure $build): void
    {
        $data = array_fill(0, 1000, $build());
        memory_reset_peak_usage();
        $before = memory_get_peak_usage();
        try {
            Serializer::toJson($data);
            $this->fail('expected the size guard to reject the data');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('exceeds maximum allowed size', $e->getMessage());
        }
        // the guard holds one hooked value at a time; json_encode() builds 100 MB
        $this->assertLessThan($before + 1000000, memory_get_peak_usage(), 'JSON was built before the guard ran');
    }

    public function testToJsonCountsBackedEnumsAsTheirValue(): void
    {
        // counted by name and value this is over 5 MB; json_encode() writes "[1,1,...]"
        $json = Serializer::toJson(array_fill(0, 150000, LongNamedStatus::PROCESSING_INSTRUCTION_COMPLETE));
        $this->assertSame(300001, \strlen($json));
    }

    public function testToJsonCountsStringsReturnedByJsonSerialize(): void
    {
        $item = new class implements \JsonSerializable {
            public function jsonSerialize(): mixed
            {
                return str_repeat('a', 100000);
            }
        };
        $data = array_fill(0, 1000, $item);
        memory_reset_peak_usage();
        $before = memory_get_peak_usage();
        try {
            Serializer::toJson($data);
            $this->fail('expected the size guard to reject the data');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('exceeds maximum allowed size', $e->getMessage());
        }
        // the guard holds one returned string at a time; json_encode() builds 100 MB
        $this->assertLessThan($before + 1000000, memory_get_peak_usage(), 'JSON was built before the guard ran');
    }

    public function testToJsonGuardsJsonSerializableOutput(): void
    {
        $data = 'x';
        for ($i = 0; $i < 40; $i++) {
            $level = [$data];
            $level[1] = &$level[0];
            $data = $level;
            unset($level);
        }
        $object = new class ($data) implements \JsonSerializable {
            public function __construct(private array $data)
            {
            }

            public function jsonSerialize(): mixed
            {
                return $this->data;
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('exceeds maximum allowed size');
        Serializer::toJson($object);
    }

    public function testToJsonEncodesJsonSerializableReturningItself(): void
    {
        $object = new class implements \JsonSerializable {
            public function jsonSerialize(): mixed
            {
                return $this;
            }
        };

        // json_encode() encodes the properties of an object returned as itself
        $this->assertSame('{}', Serializer::toJson($object));
    }

    public function testToJsonGuardsPropertiesOfJsonSerializableReturningItself(): void
    {
        $data = 'x';
        for ($i = 0; $i < 40; $i++) {
            $level = [$data];
            $level[1] = &$level[0];
            $data = $level;
            unset($level);
        }
        $object = new class ($data) implements \JsonSerializable {
            public function __construct(public array $data)
            {
            }

            public function jsonSerialize(): mixed
            {
                return $this;
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('exceeds maximum allowed size');
        Serializer::toJson($object);
    }

    public function testToJsonRejectsUnencodableData(): void
    {
        $this->expectException(\JsonException::class);
        Serializer::toJson("\xB1\x31");
    }

    public function testFromJsonRejectsEmptyString(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        Serializer::fromJson('');
    }

    public function testFromJsonRejectsOversizedPayload(): void
    {
        $this->expectException(\RuntimeException::class);
        Serializer::fromJson(str_repeat(' ', self::MAX_SIZE + 1));
    }

    public function testFromJsonRejectsMalformedJson(): void
    {
        $this->expectException(\JsonException::class);
        Serializer::fromJson('{"a":');
    }

    public function testJsonOnly(): void
    {
        $this->assertSame(['a' => 1], Serializer::jsonOnly('{"a":1}'));
        $this->assertNull(Serializer::jsonOnly(''));
        $this->assertNull(Serializer::jsonOnly('   '));
        $this->assertNull(Serializer::jsonOnly('{bad'));
        $this->assertNull(Serializer::jsonOnly('a:1:{i:0;i:1;}'));
    }

    public function testJsonOnlyRejectsOversizedPayload(): void
    {
        // assertTrue keeps a failure from printing the 5 MB value
        $this->assertTrue(null === Serializer::jsonOnly('"' . str_repeat('a', self::MAX_SIZE) . '"'));
    }

    // ----------------------------------------------------------------- PHP

    public static function phpValues(): array
    {
        return [
            'null'   => [null],
            'false'  => [false],
            'true'   => [true],
            'int'    => [-42],
            'float'  => [1.5],
            'string' => ['hello'],
            'array'  => [['a' => [1, 2], 'b' => 'c']],
        ];
    }

    #[DataProvider('phpValues')]
    public function testPhpRoundTrip($value): void
    {
        $this->assertSame($value, Serializer::fromPhp(Serializer::toPhp($value)));
    }

    public function testToPhpRejectsResource(): void
    {
        $handle = fopen('php://memory', 'r');
        try {
            $this->expectException(\InvalidArgumentException::class);
            Serializer::toPhp($handle);
        } finally {
            fclose($handle);
        }
    }

    public function testToPhpRejectsClosure(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Serializer::toPhp(static function () {
            // body irrelevant: closures cannot be serialized
        });
    }

    public function testToPhpRejectsNestedClosure(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Serializer::toPhp(['callback' => static function () {
            // body irrelevant: closures cannot be serialized
        }]);
    }

    public function testToPhpRejectsOversizedOutput(): void
    {
        $this->expectException(\RuntimeException::class);
        Serializer::toPhp(str_repeat('a', self::MAX_SIZE));
    }

    public function testFromPhpRejectsEmptyString(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        Serializer::fromPhp('');
    }

    public function testFromPhpRejectsOversizedPayload(): void
    {
        $this->expectException(\RuntimeException::class);
        Serializer::fromPhp(str_repeat('a', self::MAX_SIZE + 1));
    }

    public function testFromPhpRejectsMalformedPayload(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        Serializer::fromPhp('a:1:{i:0;');
    }

    public function testFromPhpKeepsNulBytesInStrings(): void
    {
        // binary data such as random_bytes() round-trips; only object tokens are refused
        $value = ['token' => "a\0b", "k\0" => ["\0"]];

        $this->assertSame($value, Serializer::fromPhp(serialize($value)));
    }

    public function testFromPhpRejectsObjectWithPrivateProperty(): void
    {
        $this->expectExceptionMessage('Objects and enums are not allowed in this payload');
        Serializer::fromPhp(serialize(['e' => new \Exception('x')]));
    }

    /**
     * @return array<string, array{string}> payloads holding an object, class or enum token
     */
    public static function objectPayloads(): array
    {
        return [
            'object'        => [serialize(new \ArrayObject([1]))],
            'custom class'  => ['C:11:"ArrayObject":21:{x:i:0;a:0:{};m:a:0:{}}'],
            'enum'          => ['E:23:"Xmf\Test\NoSuchEnum:One";'],
            'nested enum'   => ['a:2:{i:0;s:1:"x";s:1:"k";a:1:{i:0;E:23:"Xmf\Test\NoSuchEnum:One";}}'],
            'nested object' => ['a:1:{i:0;O:8:"stdClass":0:{}}'],
        ];
    }

    /**
     * Run $read with an autoload spy and return the classes it tried to load.
     *
     * @return array{0: mixed, 1: array<int, string>} the result and the autoloaded classes
     */
    private static function readWithAutoloadSpy(callable $read): array
    {
        $loaded = [];
        $spy = static function (string $class) use (&$loaded): void {
            $loaded[] = $class;
        };
        spl_autoload_register($spy);
        try {
            $result = $read();
        } catch (\RuntimeException $e) {
            $result = $e->getMessage();
        } finally {
            spl_autoload_unregister($spy);
        }
        return [$result, $loaded];
    }

    #[DataProvider('objectPayloads')]
    public function testFromPhpRejectsObjectTokensBeforeAutoloading(string $payload): void
    {
        // allowed_classes=false still autoloads and builds enum cases, so the
        // payload must be refused before unserialize() resolves any class.
        [$result, $loaded] = self::readWithAutoloadSpy(static fn() => Serializer::fromPhp($payload));

        $this->assertSame('Objects and enums are not allowed in this payload', $result);
        $this->assertSame([], $loaded);
    }

    public function testFromPhpRejectsRealEnumCase(): void
    {
        $this->expectException(\RuntimeException::class);
        Serializer::fromPhp(serialize(['s' => LongNamedStatus::PROCESSING_INSTRUCTION_COMPLETE]));
    }

    public function testFromLegacyRejectsEncodedEnumBeforeAutoloading(): void
    {
        $payload = base64_encode('E:23:"Xmf\Test\NoSuchEnum:One";');
        [$result, $loaded] = self::readWithAutoloadSpy(static fn() => Serializer::tryFrom($payload, 'd', Format::LEGACY));

        $this->assertSame('d', $result);
        $this->assertSame([], $loaded);
    }

    public function testAllowListRestoresListedClassesAndEnums(): void
    {
        $value = ['o' => new \ArrayObject([1]), 'e' => LongNamedStatus::PROCESSING_INSTRUCTION_COMPLETE];

        $restored = Serializer::fromPhp(serialize($value), [\ArrayObject::class, LongNamedStatus::class]);

        $this->assertInstanceOf(\ArrayObject::class, $restored['o']);
        $this->assertSame([1], $restored['o']->getArrayCopy());
        $this->assertSame(LongNamedStatus::PROCESSING_INSTRUCTION_COMPLETE, $restored['e']);
    }

    public function testAllowListRejectsUnlistedEnumBeforeAutoloading(): void
    {
        // an allow-list does not stop unserialize() from autoloading an unlisted enum
        $payload = 'a:2:{i:0;O:11:"ArrayObject":4:{i:0;i:0;i:1;a:0:{}i:2;a:0:{}i:3;N;}i:1;E:23:"Xmf\Test\NoSuchEnum:One";}';
        [$result, $loaded] = self::readWithAutoloadSpy(static fn() => Serializer::fromPhp($payload, [\ArrayObject::class]));

        $this->assertSame('Objects and enums are not allowed in this payload', $result);
        $this->assertSame([], $loaded);
    }

    /**
     * @return array<string, array{string}> tokens whose declared length runs past the payload
     */
    public static function overlongPayloads(): array
    {
        return [
            'string'     => ['s:99999999999999999999:"x";'],
            'enum name'  => ['E:99999999999999999999:"A:B";'],
            'class data' => ['C:11:"ArrayObject":99999999999999999999:{}'],
        ];
    }

    #[DataProvider('overlongPayloads')]
    public function testFromPhpRejectsLengthsPastThePayload(string $payload): void
    {
        $this->expectException(\UnexpectedValueException::class);
        Serializer::fromPhp($payload, [\ArrayObject::class, 'A']);
    }

    public function testAllowListRejectsUnlistedObject(): void
    {
        $this->expectExceptionMessage('Objects and enums are not allowed in this payload');
        Serializer::fromPhp(serialize([new \ArrayObject([1])]), [\SplStack::class]);
    }

    public function testAllowListRejectsUnlistedEnumInsideClassDataBeforeAutoloading(): void
    {
        // ArrayObject decodes its own C: body, where allowed_classes does not stop an enum autoloading
        $body = 'x:i:0;a:1:{i:0;E:23:"Xmf\Test\NoSuchEnum:One";};m:a:0:{}';
        $payload = 'C:11:"ArrayObject":' . \strlen($body) . ':{' . $body . '}';
        [$result, $loaded] = self::readWithAutoloadSpy(static fn() => Serializer::fromPhp($payload, [\ArrayObject::class]));

        $this->assertSame('Objects and enums are not allowed in this payload', $result);
        $this->assertSame([], $loaded);
    }

    public function testAllowListRejectsUnlistedObjectInsideClassData(): void
    {
        // unserialize() would hand back an ArrayObject holding a __PHP_Incomplete_Class
        $body = 'x:i:0;a:1:{i:0;O:8:"stdClass":1:{s:1:"x";i:1;}};m:a:0:{}';
        $payload = 'C:11:"ArrayObject":' . \strlen($body) . ':{' . $body . '}';

        $this->expectExceptionMessage('Objects and enums are not allowed in this payload');
        Serializer::fromPhp($payload, [\ArrayObject::class]);
    }

    public function testAllowListRefusesEnumLikeTextInsideClassDataStrings(): void
    {
        // the C: body is the class's own format, so its strings are not skipped: fail closed
        $body = 'x:i:0;a:1:{i:0;s:31:"E:23:"Xmf\Test\NoSuchEnum:One";";};m:a:0:{}';
        $payload = 'C:11:"ArrayObject":' . \strlen($body) . ':{' . $body . '}';

        $this->expectExceptionMessage('Objects and enums are not allowed in this payload');
        Serializer::fromPhp($payload, [\ArrayObject::class]);
    }

    public function testFromPhpKeepsTokenLikeTextInsideStrings(): void
    {
        $value = ['O:8:"stdClass":0:{}' => 'E:23:"Xmf\Test\NoSuchEnum:One";', 'n' => [1.5e-7, -INF, true, null, 'C:']];

        $this->assertSame(serialize($value), serialize(Serializer::fromPhp(serialize($value))));
    }

    public function testFromPhpIgnoresTrailingBytesLikeUnserialize(): void
    {
        $this->assertSame(['1.2.3.4' => 5], Serializer::fromPhp(serialize(['1.2.3.4' => 5]) . "\n"));
    }

    public function testFromPhpRejectsUnknownTokens(): void
    {
        // serialize() never writes the escaped-string form S:, so it stays outside the grammar
        $this->expectException(\UnexpectedValueException::class);
        Serializer::fromPhp('S:1:"\61";');
    }

    public function testFromPhpRestoresAllowedClasses(): void
    {
        $object = new \stdClass();
        $object->name = 'x';

        $result = Serializer::fromPhp(serialize($object), [\stdClass::class]);

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame('x', $result->name);
    }

    // -------------------------------------------------------------- legacy

    public function testLegacyRoundTrip(): void
    {
        $data = ['legacy' => true, 'items' => [1, 2, 3]];

        $this->assertSame($data, Serializer::fromLegacy(Serializer::toLegacy($data)));
    }

    public function testFromLegacyAcceptsRawSerializedPayloadAndLogsIt(): void
    {
        $calls = [];
        Serializer::setLegacyLogger(static function (string $file, int $line, string $preview) use (&$calls): void {
            $calls[] = [$file, $line, $preview];
        });

        $this->assertSame([1, 2], Serializer::fromLegacy(serialize([1, 2])));
        $this->assertCount(1, $calls);
        // the logger reports the first caller outside Serializer
        $this->assertStringNotContainsString('Serializer.php', $calls[0][0]);
        $this->assertGreaterThan(0, $calls[0][1]);
        $this->assertSame(serialize([1, 2]), $calls[0][2]);
    }

    public function testLegacyLoggerReportsTheCallSite(): void
    {
        $location = null;
        Serializer::setLegacyLogger(static function (string $file, int $line) use (&$location): void {
            $location = [$file, $line];
        });

        $line = __LINE__ + 1;
        Serializer::fromLegacy(serialize([1, 2]));

        $this->assertSame([__FILE__, $line], $location); // NOSONAR expected value is first
    }

    public function testLegacyLoggerTruncatesLongPreviews(): void
    {
        $preview = null;
        Serializer::setLegacyLogger(static function (string $file, int $line, string $text) use (&$preview): void {
            $preview = $text;
        });

        Serializer::fromLegacy(Serializer::toLegacy(str_repeat('x', 200)));

        $this->assertSame(53, \strlen((string) $preview));
        $this->assertStringEndsWith('...', (string) $preview);
    }

    public function testFromLegacyRejectsNonBase64Garbage(): void
    {
        $this->expectException(\RuntimeException::class);
        Serializer::fromLegacy('not a serialized or base64 value');
    }

    public function testFromLegacyReportsClassRejectionForPlainPayload(): void
    {
        // the plain path reports the same rejection the base64 path does
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Objects and enums are not allowed in this payload');
        Serializer::fromLegacy(serialize(new \ArrayObject([1])), [\SplStack::class]);
    }

    public function testFromLegacyKeepsNulBytesInStrings(): void
    {
        $value = ['k' => "abc\0def-padding"];

        $this->assertSame($value, Serializer::fromLegacy(base64_encode(serialize($value))));
    }

    #[RequiresPhpExtension('zlib')]
    public function testFromLegacyDecodesGzipPayload(): void
    {
        $data = ['compressed' => str_repeat('abc', 100)];

        $this->assertSame($data, Serializer::fromLegacy(base64_encode(gzencode(serialize($data)))));
    }

    #[RequiresPhpExtension('zlib')]
    public function testFromLegacyRejectsTruncatedGzip(): void
    {
        $gzip = gzencode(serialize(['compressed' => str_repeat('abc', 100)]));

        $this->expectException(DecompressionException::class);
        Serializer::fromLegacy(base64_encode(substr($gzip, 0, 40)));
    }

    #[RequiresPhpExtension('zlib')]
    public function testFromLegacyRejectsGzipBomb(): void
    {
        $bomb = gzencode(serialize(str_repeat('a', self::MAX_SIZE + 1)), 9);

        $this->expectException(DecompressionException::class);
        $this->expectExceptionMessage('Decompressed payload exceeds');
        Serializer::fromLegacy(base64_encode($bomb));
    }

    public function testFromLegacyAcceptsSerializedNull(): void
    {
        $this->assertNull(Serializer::fromLegacy('N;'));
    }

    public function testFromLegacyAcceptsShortToLegacyOutput(): void
    {
        $this->assertFalse(Serializer::fromLegacy(Serializer::toLegacy(false)));
    }

    // ------------------------------------------------------ detect / from

    public static function detectCases(): array
    {
        return [
            'empty'          => ['', Format::AUTO],
            'json object'    => ['{"a":1}', Format::JSON],
            'json array'     => ['  [1,2]', Format::JSON],
            'broken json'    => ['{"a":', Format::AUTO],
            'php array'      => [serialize([1, 2]), Format::PHP],
            'php null'       => ['N;', Format::PHP],
            'php double'     => [serialize(1.5), Format::PHP],
            'php object'     => [serialize(new \stdClass()), Format::PHP],
            'legacy'         => [base64_encode(serialize(['a' => 'b'])), Format::LEGACY],
            'plain text'     => ['hello world', Format::AUTO],
            'base64 text'    => [base64_encode('just some plain text'), Format::AUTO],
        ];
    }

    #[DataProvider('detectCases')]
    public function testDetect(string $payload, string $expected): void
    {
        $this->assertSame($expected, Serializer::detect($payload));
    }

    public function testFromDispatchesOnDetectedFormat(): void
    {
        $data = ['k' => 'v'];

        $this->assertSame($data, Serializer::from(Serializer::toJson($data)));
        $this->assertSame($data, Serializer::from(Serializer::toPhp($data)));
        $this->assertSame($data, Serializer::from(Serializer::toLegacy($data)));
        $this->assertNull(Serializer::from('N;'));
    }

    public function testFromFallsBackToJsonForScalars(): void
    {
        $this->assertSame(42, Serializer::from('42'));
        $this->assertNull(Serializer::from('null'));
    }

    public function testFromRejectsGarbage(): void
    {
        $this->expectException(\RuntimeException::class);
        Serializer::from('definitely not serialized');
    }

    // --------------------------------------------- typed helpers / tryFrom

    public function testToArrayForEachFormat(): void
    {
        $data = ['x' => 1];

        $this->assertSame($data, Serializer::toArray(Serializer::toJson($data), Format::JSON));
        $this->assertSame($data, Serializer::toArray(Serializer::toPhp($data), Format::PHP));
        $this->assertSame($data, Serializer::toArray(Serializer::toLegacy($data), Format::LEGACY));
        $this->assertSame($data, Serializer::toArray(Serializer::toJson($data)));
    }

    public function testToArrayRejectsNonArray(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        Serializer::toArray(Serializer::toPhp('scalar'), Format::PHP);
    }

    public function testToArrayRejectsUnknownFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Serializer::toArray('{}', 'xml');
    }

    public function testToObjectRestoresRequestedClass(): void
    {
        $object = new \ArrayObject([1, 2]);

        $this->assertEquals($object, Serializer::toObject(serialize($object), \ArrayObject::class));
        $this->assertEquals(
            $object,
            Serializer::toObject(base64_encode(serialize($object)), \ArrayObject::class, Format::LEGACY)
        );
    }

    public function testToObjectRejectsUnknownClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Serializer::toObject(serialize(new \stdClass()), 'No\\Such\\ClassName');
    }

    public function testToObjectRejectsJsonFormat(): void
    {
        $this->expectException(\RuntimeException::class);
        Serializer::toObject('{}', \stdClass::class, Format::JSON);
    }

    public function testToObjectRejectsOtherClasses(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        Serializer::toObject(serialize(new \ArrayObject()), \stdClass::class);
    }

    public function testTryFromReturnsDefaultOnFailure(): void
    {
        $this->assertSame(['a' => 1], Serializer::tryFrom('{"a":1}', 'd', Format::JSON));
        $this->assertSame([1], Serializer::tryFrom(serialize([1]), 'd', Format::PHP));
        $this->assertSame([1], Serializer::tryFrom(base64_encode(serialize([1])), 'd', Format::LEGACY));
        $this->assertSame([1], Serializer::tryFrom('[1]', 'd'));
        $this->assertSame('d', Serializer::tryFrom('{bad', 'd', Format::JSON));
        $this->assertSame('d', Serializer::tryFrom('anything', 'd', 'xml'));
    }

    public function testScalarsOnly(): void
    {
        $this->assertSame('x', Serializer::scalarsOnly(Serializer::toJson('x'), Format::JSON));
        $this->assertSame(3, Serializer::scalarsOnly(Serializer::toPhp(3), Format::PHP));
        $this->assertSame(
            'a longer legacy value',
            Serializer::scalarsOnly(Serializer::toLegacy('a longer legacy value'), Format::LEGACY)
        );
        $this->assertNull(Serializer::scalarsOnly('null'));
    }

    public function testScalarsOnlyRejectsArrays(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        Serializer::scalarsOnly('[1]', Format::JSON);
    }

    public function testScalarsOnlyRejectsUnknownFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Serializer::scalarsOnly('1', 'xml');
    }

    // --------------------------------------------------------------- debug

    public function testDebugStatsAreEmptyWhenDisabled(): void
    {
        $this->assertSame([], Serializer::getDebugStats());
    }

    public function testDebugStatsRecordOperationsAndErrors(): void
    {
        Serializer::enableDebug();
        Serializer::fromPhp(serialize([1]));
        try {
            Serializer::fromPhp('a:1:{');
        } catch (\UnexpectedValueException $e) {
            // expected
        }

        $stats = Serializer::getDebugStats();

        $this->assertSame(2, $stats['total_operations']);
        $this->assertSame([Format::PHP => 2], $stats['formats_detected']);
        $this->assertCount(1, $stats['errors']);
        $this->assertSame('fromPhp', $stats['errors'][0]['operation']);
        $this->assertIsArray($stats['errors'][0]['trace']);

        Serializer::enableDebug(false);
        $this->assertSame([], Serializer::getDebugStats());
    }

    // ------------------------------------------------- issue #191 regressions

    public function testLegacyRoundTripNearSizeLimit(): void
    {
        // serialized form fits MAX_SIZE, base64 form is a third larger
        $data = str_repeat('a', 4000000);

        $this->assertSame($data, Serializer::fromLegacy(Serializer::toLegacy($data)));
    }

    public function testFromLegacyRejectsOversizedPlainPayload(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Payload exceeds maximum size of 5000000 bytes');

        Serializer::fromLegacy(serialize(str_repeat('a', self::MAX_SIZE)));
    }

    public function testFromLegacyRejectsPayloadOverEncodedLimit(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Payload exceeds maximum size of 6666668 bytes');

        Serializer::fromLegacy(str_repeat('QUFB', 1666668));
    }

    #[RequiresPhpExtension('zlib')]
    public function testDetectRecognizesGzipLegacyPayload(): void
    {
        $payload = base64_encode(gzencode(serialize(['a' => 1])));

        $this->assertSame(Format::LEGACY, Serializer::detect($payload));
        $this->assertSame(['a' => 1], Serializer::from($payload));
    }

    public function testDetectSkipsPayloadsTooLargeToDecode(): void
    {
        $this->assertSame(Format::AUTO, Serializer::detect(str_repeat('QUFB', 1666668)));
    }

    public function testLegacyLoggerReportsTheCallSiteThroughFrom(): void
    {
        $location = null;
        Serializer::setLegacyLogger(static function (string $file, int $line) use (&$location): void {
            $location = [$file, $line];
        });

        $line = __LINE__ + 1;
        Serializer::tryFrom(Serializer::toLegacy([1, 2]));

        $this->assertSame([__FILE__, $line], $location); // NOSONAR expected value is first
    }

    #[RequiresPhpExtension('zlib')]
    public function testFromLegacyRejectsDataAfterGzipStream(): void
    {
        $this->expectException(DecompressionException::class);

        Serializer::fromLegacy(base64_encode(gzencode('i:1;') . 'corrupt-trailer'));
    }

    #[RequiresPhpExtension('zlib')]
    public function testFromLegacyRejectsSecondGzipMember(): void
    {
        $this->expectException(DecompressionException::class);

        Serializer::fromLegacy(base64_encode(gzencode('i:1;') . gzencode('i:2;')));
    }
}
