<?php

/*
 * This file is part of the webmozart/assert package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2014 Bernhard Schussek
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of
 * this software and associated documentation files (the "Software"), to deal in
 * the Software without restriction, including without limitation the rights to
 * use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
 * the Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
 * FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace Xmf\Test;

use ArrayIterator;
use ArrayObject;
use Exception;
use Error;
use LogicException;
use RuntimeException;
use stdClass;
use Xmf\Assert;

use PHPUnit\Framework\TestCase;

/**
 * This class allows us to be compatible with multiple PHPUnit versions.
 */
class BaseTestCase extends TestCase
{
    /**
     * The setExpectedException got removed in later phpunit versions,
     * and instead it got split up into three functions.
     *
     * So if the newer functions exist we call those where needed, and otherwise
     * we just use the original function.
     *
     * @param string $exceptionName
     * @param string $exceptionMessage
     * @param int $exceptionCode
     */
    public function setExpectedException($exceptionName, $exceptionMessage = '', $exceptionCode = null)
    {
        if (method_exists($this, 'expectException')) {
            $this->expectException($exceptionName);
            if($exceptionMessage) {
                $this->expectExceptionMessage($exceptionMessage);
            }
            if($exceptionCode) {
                $this->expectExceptionCode($exceptionCode);
            }
            return;
        }
        parent::setExpectedException($exceptionName, $exceptionMessage, $exceptionCode);
    }
}

/**
 * @since  1.0
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class AssertTest extends BaseTestCase
{
    private static $resource;

    public static function getResource()
    {
        if (!static::$resource) {
            static::$resource = fopen(__FILE__, 'r');
        }

        return static::$resource;
    }

    public static function tearDownAfterClass()
    {
        @fclose(self::$resource);
    }

    public function getTests()
    {
        $resource = self::getResource();

        return [
            ['string', ['value'], true],
            ['string', [''], true],
            ['string', [1234], false],
            ['stringNotEmpty', ['value'], true],
            ['stringNotEmpty', ['0'], true],
            ['stringNotEmpty', [''], false],
            ['stringNotEmpty', [1234], false],
            ['integer', [123], true],
            ['integer', ['123'], false],
            ['integer', [1.0], false],
            ['integer', [1.23], false],
            ['integerish', [1.0], true],
            ['integerish', [1.23], false],
            ['integerish', [123], true],
            ['integerish', ['123'], true],
            ['float', [1.0], true],
            ['float', [1.23], true],
            ['float', [123], false],
            ['float', ['123'], false],
            ['numeric', [1.0], true],
            ['numeric', [1.23], true],
            ['numeric', [123], true],
            ['numeric', ['123'], true],
            ['numeric', ['foo'], false],
            ['natural', [0], true],
            ['natural', [1], true],
            ['natural', [-1], false],
            ['natural', ['1'], false],
            ['natural', [1.0], false],
            ['natural', [1.23], false],
            ['boolean', [true], true],
            ['boolean', [false], true],
            ['boolean', [1], false],
            ['boolean', ['1'], false],
            ['scalar', ['1'], true],
            ['scalar', [123], true],
            ['scalar', [true], true],
            ['scalar', [null], false],
            ['scalar', [[]], false],
            ['scalar', [new stdClass()], false],
            ['object', [new stdClass()], true],
            ['object', [new RuntimeException()], true],
            ['object', [null], false],
            ['object', [true], false],
            ['object', [1], false],
            ['object', [[]], false],
            ['resource', [$resource], true],
            ['resource', [$resource, 'stream'], true],
            ['resource', [$resource, 'other'], false],
            ['resource', [1], false],
            ['isCallable', ['strlen'], true],
            ['isCallable', [[$this, 'getTests']], true],
            ['isCallable', [function () {}], true],
            ['isCallable', [1234], false],
            ['isCallable', ['foobar'], false],
            ['isArray', [[]], true],
            ['isArray', [[1, 2, 3]], true],
            ['isArray', [new ArrayIterator([])], false],
            ['isArray', [123], false],
            ['isArray', [new stdClass()], false],
            ['isTraversable', [[]], true],
            ['isTraversable', [[1, 2, 3]], true],
            ['isTraversable', [new ArrayIterator([])], true],
            ['isTraversable', [123], false],
            ['isTraversable', [new stdClass()], false],
            ['isArrayAccessible', [[]], true],
            ['isArrayAccessible', [[1, 2, 3]], true],
            ['isArrayAccessible', [new ArrayObject([])], true],
            ['isArrayAccessible', [123], false],
            ['isArrayAccessible', [new stdClass()], false],
            ['isCountable', [[]], true],
            ['isCountable', [[1, 2]], true],
            ['isCountable', [new ArrayIterator([])], true],
            ['isCountable', [new stdClass()], false],
            ['isCountable', [new \SimpleXMLElement('<foo>bar</foo>')], true],
            ['isCountable', ['abcd'], false],
            ['isCountable', [123], false],
            ['isIterable', [[]], true],
            ['isIterable', [[1, 2, 3]], true],
            ['isIterable', [new ArrayIterator([])], true],
            ['isIterable', [123], false],
            ['isIterable', [new stdClass()], false],
            ['isInstanceOf', [new stdClass(), 'stdClass'], true],
            ['isInstanceOf', [new Exception(), 'stdClass'], false],
            ['isInstanceOf', [123, 'stdClass'], false],
            ['isInstanceOf', [[], 'stdClass'], false],
            ['isInstanceOf', [null, 'stdClass'], false],
            ['notInstanceOf', [new stdClass(), 'stdClass'], false],
            ['notInstanceOf', [new Exception(), 'stdClass'], true],
            ['notInstanceOf', [123, 'stdClass'], true],
            ['notInstanceOf', [[], 'stdClass'], true],
            ['isInstanceOfAny', [new ArrayIterator(), ['Iterator', 'ArrayAccess']], true],
            ['isInstanceOfAny', [new Exception(), ['Exception', 'Countable']], true],
            ['isInstanceOfAny', [new Exception(), ['ArrayAccess', 'Countable']], false],
            ['isInstanceOfAny', [123, ['stdClass']], false],
            ['isInstanceOfAny', [[], ['stdClass']], false],
            ['isAOf', ['stdClass', 'stdClass'], true],
            ['isAOf', ['stdClass', 123], false],
            ['isAOf', ['Iterator', 'ArrayIterator'], false],
            ['isAOf', [123, 'Iterator'], false],
            ['isAOf', [[], 'Iterator'], false],
            ['isAnyOf', ['ArrayIterator', ['Iterator', 'ArrayAccess']], true],
            ['isAnyOf', ['ArrayIterator', [123]], false],
            ['isAnyOf', ['Exception', ['Exception', 'Countable']], true],
            ['isAnyOf', ['Exception', ['ArrayAccess', 'Countable']], false],
            ['isAnyOf', [123, ['stdClass']], false],
            ['isAnyOf', [[], ['stdClass']], false],
            ['isNotA', ['stdClass', 'stdClass'], false],
            ['isNotA', ['stdClass', 123], false],
            ['isNotA', ['Iterator', 'ArrayIterator'], true],
            ['isNotA', [123, 'Iterator'], true],
            ['isNotA', [[], 'Iterator'], true],
            ['true', [true], true],
            ['true', [false], false],
            ['true', [1], false],
            ['true', [null], false],
            ['false', [false], true],
            ['false', [true], false],
            ['false', [1], false],
            ['false', [0], false],
            ['false', [null], false],
            ['notFalse', [false], false],
            ['notFalse', [true], true],
            ['notFalse', [1], true],
            ['notFalse', [0], true],
            ['notFalse', [null], true],
            ['null', [null], true],
            ['null', [false], false],
            ['null', [0], false],
            ['notNull', [false], true],
            ['notNull', [0], true],
            ['notNull', [null], false],
            ['isEmpty', [null], true],
            ['isEmpty', [false], true],
            ['isEmpty', [0], true],
            ['isEmpty', [''], true],
            ['isEmpty', [1], false],
            ['isEmpty', ['a'], false],
            ['notEmpty', [1], true],
            ['notEmpty', ['a'], true],
            ['notEmpty', [null], false],
            ['notEmpty', [false], false],
            ['notEmpty', [0], false],
            ['notEmpty', [''], false],
            ['eq', [1, 1], true],
            ['eq', [1, '1'], true],
            ['eq', [1, true], true],
            ['eq', [1, 0], false],
            ['notEq', [1, 0], true],
            ['notEq', [1, 1], false],
            ['notEq', [1, '1'], false],
            ['notEq', [1, true], false],
            ['same', [1, 1], true],
            ['same', [1, '1'], false],
            ['same', [1, true], false],
            ['same', [1, 0], false],
            ['notSame', [1, 0], true],
            ['notSame', [1, 1], false],
            ['notSame', [1, '1'], true],
            ['notSame', [1, true], true],
            ['greaterThan', [1, 0], true],
            ['greaterThan', [0, 0], false],
            ['greaterThanEq', [2, 1], true],
            ['greaterThanEq', [1, 1], true],
            ['greaterThanEq', [0, 1], false],
            ['lessThan', [0, 1], true],
            ['lessThan', [1, 1], false],
            ['lessThanEq', [0, 1], true],
            ['lessThanEq', [1, 1], true],
            ['lessThanEq', [2, 1], false],
            ['range', [1, 1, 2], true],
            ['range', [2, 1, 2], true],
            ['range', [0, 1, 2], false],
            ['range', [3, 1, 2], false],
            ['oneOf', [1, [1, 2, 3]], true],
            ['oneOf', [1, ['1', '2', '3']], false],
            ['inArray', [1, [1, 2, 3]], true],
            ['inArray', [1, ['1', '2', '3']], false],
            ['contains', ['abcd', 'ab'], true],
            ['contains', ['abcd', 'bc'], true],
            ['contains', ['abcd', 'cd'], true],
            ['contains', ['abcd', 'de'], false],
            ['contains', ['', 'de'], false],
            ['contains', ['äþçð', 'äþ'], true],
            ['contains', ['äþçð', 'þç'], true],
            ['contains', ['äþçð', 'çð'], true],
            ['contains', ['äþçð', 'ðé'], false],
            ['contains', ['', 'ðé'], false],
            ['contains', ['あいうえ', 'あい'], true],
            ['contains', ['あいうえ', 'いう'], true],
            ['contains', ['あいうえ', 'うえ'], true],
            ['contains', ['あいうえ', 'えお'], false],
            ['contains', ['', 'えお'], false],
            ['contains', ['😄😑☹️', '😄'], true],
            ['contains', ['😄😑☹️', '😑'], true],
            ['contains', ['😄😑☹️', '☹️'], true],
            ['contains', ['😄😑☹️', '😄☹️'], false],
            ['contains', ['', '😑'], false],
            ['notContains', ['abcd', 'ab'], false],
            ['notContains', ['abcd', 'bc'], false],
            ['notContains', ['abcd', 'cd'], false],
            ['notContains', ['abcd', 'de'], true],
            ['notContains', ['', 'de'], true],
            ['notContains', ['äþçð', 'äþ'], false],
            ['notContains', ['äþçð', 'þç'], false],
            ['notContains', ['äþçð', 'çð'], false],
            ['notContains', ['äþçð', 'ðé'], true],
            ['notContains', ['', 'ðé'], true],
            ['notContains', ['あいうえ', 'あい'], false],
            ['notContains', ['あいうえ', 'いう'], false],
            ['notContains', ['あいうえ', 'うえ'], false],
            ['notContains', ['あいうえ', 'えお'], true],
            ['notContains', ['', 'えお'], true],
            ['notContains', ['😄😑☹️', '😄'], false],
            ['notContains', ['😄😑☹️', '😑'], false],
            ['notContains', ['😄😑☹️', '☹️'], false],
            ['notContains', ['😄😑☹️', '😄☹️'], true],
            ['notContains', ['', '😑'], true],
            ['notWhitespaceOnly', ['abc'], true],
            ['notWhitespaceOnly', ['123'], true],
            ['notWhitespaceOnly', [' abc '], true],
            ['notWhitespaceOnly', ['a b c'], true],
            ['notWhitespaceOnly', [''], false],
            ['notWhitespaceOnly', [' '], false],
            ['notWhitespaceOnly', ["\t"], false],
            ['notWhitespaceOnly', ["\n"], false],
            ['notWhitespaceOnly', ["\r"], false],
            ['notWhitespaceOnly', ["\r\n\t "], false],
            ['startsWith', ['abcd', 'ab'], true],
            ['startsWith', ['abcd', 'bc'], false],
            ['startsWith', ['', 'bc'], false],
            ['startsWith', ['äþçð', 'äþ'], true],
            ['startsWith', ['äþçð', 'þç'], false],
            ['startsWith', ['', 'þç'], false],
            ['startsWith', ['あいうえ', 'あい'], true],
            ['startsWith', ['あいうえ', 'いう'], false],
            ['startsWith', ['', 'いう'], false],
            ['startsWith', ['😄😑☹️', '😄'], true],
            ['startsWith', ['😄😑☹️', '😑'], false],
            ['startsWith', ['', '😑'], false],
            ['startsWithLetter', ['abcd'], true],
            ['startsWithLetter', [[66]], false],
            ['startsWithLetter', ['a'], true],
            ['startsWithLetter', ['a1'], true],
            ['startsWithLetter', ['1abcd'], false],
            ['startsWithLetter', ['1'], false],
            ['startsWithLetter', [''], false],
            ['startsWithLetter', [null], false],
            ['startsWithLetter', [66], false],
            ['notStartsWith', ['abcd', 'ab'], false],
            ['notStartsWith', ['abcd', 'bc'], true],
            ['notStartsWith', ['', 'bc'], true],
            ['notStartsWith', ['äþçð', 'äþ'], false],
            ['notStartsWith', ['äþçð', 'þç'], true],
            ['notStartsWith', ['', 'þç'], true],
            ['notStartsWith', ['あいうえ', 'あい'], false],
            ['notStartsWith', ['あいうえ', 'いう'], true],
            ['notStartsWith', ['', 'いう'], true],
            ['notStartsWith', ['😄😑☹️', '😄'], false],
            ['notStartsWith', ['😄😑☹️', '😑'], true],
            ['notStartsWith', ['', '😑'], true],
            ['endsWith', ['abcd', 'cd'], true],
            ['endsWith', ['abcd', 'bc'], false],
            ['endsWith', ['', 'bc'], false],
            ['endsWith', ['äþçð', 'çð'], true],
            ['endsWith', ['äþçð', 'þç'], false],
            ['endsWith', ['', 'þç'], false],
            ['endsWith', ['あいうえ', 'うえ'], true],
            ['endsWith', ['あいうえ', 'いう'], false],
            ['endsWith', ['', 'いう'], false],
            ['endsWith', ['😄😑☹️', '☹️'], true],
            ['endsWith', ['😄😑☹️', '😑'], false],
            ['endsWith', ['', '😑'], false],
            ['notEndsWith', ['abcd', 'cd'], false],
            ['notEndsWith', ['abcd', 'bc'], true],
            ['notEndsWith', ['', 'bc'], true],
            ['notEndsWith', ['äþçð', 'çð'], false],
            ['notEndsWith', ['äþçð', 'þç'], true],
            ['notEndsWith', ['', 'þç'], true],
            ['notEndsWith', ['あいうえ', 'うえ'], false],
            ['notEndsWith', ['あいうえ', 'いう'], true],
            ['notEndsWith', ['', 'いう'], true],
            ['notEndsWith', ['😄😑☹️', '☹️'], false],
            ['notEndsWith', ['😄😑☹️', '😑'], true],
            ['notEndsWith', ['', '😑'], true],
            ['regex', ['abcd', '~^ab~'], true],
            ['regex', ['abcd', '~^bc~'], false],
            ['regex', ['', '~^bc~'], false],
            ['notRegex', ['abcd', '{^ab}'], false],
            ['notRegex', ['abcd', '{^bc}'], true],
            ['notRegex', ['', '{^bc}'], true],
            ['unicodeLetters', ['abcd'], true],
            ['unicodeLetters', ['ᴁڅਘธブ乶'], true],
            ['unicodeLetters', ['ȁȄ'], true],
            ['unicodeLetters', ['ȁ1Ȅ'], false],
            ['unicodeLetters', ['©'], false],
            ['unicodeLetters', ['🙁'], false],
            ['unicodeLetters', [''], false],
            ['alpha', ['abcd'], true],
            ['alpha', ['ab1cd'], false],
            ['alpha', [''], false],
            ['alpha', [66], false],
            ['alpha', [[]], false],
            ['digits', ['1234'], true],
            ['digits', ['12a34'], false],
            ['digits', [''], false],
            ['alnum', ['ab12'], true],
            ['alnum', ['ab12$'], false],
            ['alnum', [''], false],
            ['lower', ['abcd'], true],
            ['lower', ['abCd'], false],
            ['lower', ['ab_d'], false],
            ['lower', [''], false],
            ['upper', ['ABCD'], true],
            ['upper', ['ABcD'], false],
            ['upper', ['AB_D'], false],
            ['upper', [''], false],
            ['length', ['abcd', 4], true],
            ['length', ['abc', 4], false],
            ['length', ['abcde', 4], false],
            ['length', ['äbcd', 4], true, true],
            ['length', ['äbc', 4], false, true],
            ['length', ['äbcde', 4], false, true],
            ['length', ['あbcd', 4], true, true], // 'HIRAGANA LETTER A' (U+3042)
            ['length', ['あbc', 4], false, true],
            ['length', ['あbcde', 4], false, true],
            ['minLength', ['abcd', 4], true],
            ['minLength', ['abcde', 4], true],
            ['minLength', ['abc', 4], false],
            ['minLength', ['äbcd', 4], true, true],
            ['minLength', ['äbcde', 4], true, true],
            ['minLength', ['äbc', 4], false, true],
            ['minLength', ['あbcd', 4], true, true],
            ['minLength', ['あbcde', 4], true, true],
            ['minLength', ['あbc', 4], false, true],
            ['maxLength', ['abcd', 4], true],
            ['maxLength', ['abc', 4], true],
            ['maxLength', ['abcde', 4], false],
            ['maxLength', ['äbcd', 4], true, true],
            ['maxLength', ['äbc', 4], true, true],
            ['maxLength', ['äbcde', 4], false, true],
            ['maxLength', ['あbcd', 4], true, true],
            ['maxLength', ['あbc', 4], true, true],
            ['maxLength', ['あbcde', 4], false, true],
            ['lengthBetween', ['abcd', 3, 5], true],
            ['lengthBetween', ['abc', 3, 5], true],
            ['lengthBetween', ['abcde', 3, 5], true],
            ['lengthBetween', ['ab', 3, 5], false],
            ['lengthBetween', ['abcdef', 3, 5], false],
            ['lengthBetween', ['äbcd', 3, 5], true, true],
            ['lengthBetween', ['äbc', 3, 5], true, true],
            ['lengthBetween', ['äbcde', 3, 5], true, true],
            ['lengthBetween', ['äb', 3, 5], false, true],
            ['lengthBetween', ['äbcdef', 3, 5], false, true],
            ['lengthBetween', ['あbcd', 3, 5], true, true],
            ['lengthBetween', ['あbc', 3, 5], true, true],
            ['lengthBetween', ['あbcde', 3, 5], true, true],
            ['lengthBetween', ['あb', 3, 5], false, true],
            ['lengthBetween', ['あbcdef', 3, 5], false, true],
            ['fileExists', [__FILE__], true],
            ['fileExists', [__DIR__], true],
            ['fileExists', [__DIR__.'/foobar'], false],
            ['file', [__FILE__], true],
            ['file', [__DIR__], false],
            ['file', [__DIR__.'/foobar'], false],
            ['directory', [__DIR__], true],
            ['directory', [__FILE__], false],
            ['directory', [__DIR__.'/foobar'], false],
            // no tests for readable()/writable() for now
            ['classExists', [__CLASS__], true],
            ['classExists', [__NAMESPACE__.'\Foobar'], false],
            ['subclassOf', [__CLASS__, __NAMESPACE__.'\BaseTestCase'], true],
            ['subclassOf', [__CLASS__, 'stdClass'], false],
            ['interfaceExists', ['\Countable'], true],
            ['interfaceExists', [__CLASS__], false],
            ['implementsInterface', ['ArrayIterator', 'Traversable'], true],
            ['implementsInterface', [__CLASS__, 'Traversable'], false],
            ['propertyExists', [(object) ['property' => 0], 'property'], true],
            ['propertyExists', [(object) ['property' => null], 'property'], true],
            ['propertyExists', [(object) ['property' => null], 'foo'], false],
            ['propertyNotExists', [(object) ['property' => 0], 'property'], false],
            ['propertyNotExists', [(object) ['property' => null], 'property'], false],
            ['propertyNotExists', [(object) ['property' => null], 'foo'], true],
            ['methodExists', ['RuntimeException', 'getMessage'], true],
            ['methodExists', [new RuntimeException(), 'getMessage'], true],
            ['methodExists', ['stdClass', 'getMessage'], false],
            ['methodExists', [new stdClass(), 'getMessage'], false],
            ['methodExists', [null, 'getMessage'], false],
            ['methodExists', [true, 'getMessage'], false],
            ['methodExists', [1, 'getMessage'], false],
            ['methodNotExists', ['RuntimeException', 'getMessage'], false],
            ['methodNotExists', [new RuntimeException(), 'getMessage'], false],
            ['methodNotExists', ['stdClass', 'getMessage'], true],
            ['methodNotExists', [new stdClass(), 'getMessage'], true],
            ['methodNotExists', [null, 'getMessage'], true],
            ['methodNotExists', [true, 'getMessage'], true],
            ['methodNotExists', [1, 'getMessage'], true],
            ['keyExists', [['key' => 0], 'key'], true],
            ['keyExists', [['key' => null], 'key'], true],
            ['keyExists', [['key' => null], 'foo'], false],
            ['keyNotExists', [['key' => 0], 'key'], false],
            ['keyNotExists', [['key' => null], 'key'], false],
            ['keyNotExists', [['key' => null], 'foo'], true],
            ['validArrayKey', ['abcd'], true],
            ['validArrayKey', [1], true],
            ['validArrayKey', [false], false],
            ['validArrayKey', [true], false],
            ['validArrayKey', [new stdClass()], false],
            ['validArrayKey', [new ToStringClass('testString')], false],
            ['validArrayKey', [self::getResource()], false],
            ['count', [[0, 1, 2], 3], true],
            ['count', [[0, 1, 2], 2], false],
            ['minCount', [[0], 2], false],
            ['minCount', [[0, 1], 2], true],
            ['minCount', [[0, 1, 2], 2], true],
            ['maxCount', [[0, 1, 2], 2], false],
            ['maxCount', [[0, 1], 2], true],
            ['maxCount', [[0], 2], true],
            ['countBetween', [[0, 1, 2], 4, 5], false],
            ['countBetween', [[0, 1, 2], 3, 5], true],
            ['countBetween', [[0, 1, 2], 1, 2], false],
            ['countBetween', [[0, 1, 2], 2, 5], true],
            ['countBetween', [[0, 1, 2], 2, 3], true],
            ['isList', [[1, 2, 3]], true],
            ['isList', [[]], true],
            ['isList', [[0 => 1, 2 => 3]], false],
            ['isList', [['key' => 1, 'foo' => 2]], false],
            ['isList', [true], false],
            ['isList', [false], false],
            ['isList', [[true]], true],
            ['isList', [[false]], true],
            ['isList', [[[1], [2]]], true],
            ['isList', [[['foo' => 'bar'], ['baz' => 'tab']]], true],
            ['isNonEmptyList', [[1, 2, 3]], true],
            ['isNonEmptyList', [[]], false],
            ['isNonEmptyList', [[0 => 1, 2 => 3]], false],
            ['isNonEmptyList', [['key' => 1, 'foo' => 2]], false],
            ['isNonEmptyList', [true], false],
            ['isNonEmptyList', [false], false],
            ['isNonEmptyList', [[true]], true],
            ['isNonEmptyList', [[false]], true],
            ['isNonEmptyList', [[[1], [2]]], true],
            ['isNonEmptyList', [[['foo' => 'bar'], ['baz' => 'tab']]], true],
            ['isMap', [['key' => 1, 'foo' => 2]], true],
            ['isMap', [[]], true],
            ['isMap', [[1, 2, 3]], false],
            ['isMap', [[0 => 1, 2 => 3]], false],
            ['isNonEmptyMap', [['key' => 1, 'foo' => 2]], true],
            ['isNonEmptyMap', [[]], false],
            ['isNonEmptyMap', [[1, 2, 3]], false],
            ['isNonEmptyMap', [[0 => 1, 2 => 3]], false],
            ['uuid', ['00000000-0000-0000-0000-000000000000'], true],
            ['uuid', ['urn:ff6f8cb0-c57d-21e1-9b21-0800200c9a66'], true],
            ['uuid', ['uuid:{ff6f8cb0-c57d-21e1-9b21-0800200c9a66}'], true],
            ['uuid', ['ff6f8cb0-c57d-21e1-9b21-0800200c9a66'], true],
            ['uuid', ['ff6f8cb0-c57d-11e1-9b21-0800200c9a66'], true],
            ['uuid', ['ff6f8cb0-c57d-31e1-9b21-0800200c9a66'], true],
            ['uuid', ['ff6f8cb0-c57d-41e1-9b21-0800200c9a66'], true],
            ['uuid', ['ff6f8cb0-c57d-51e1-9b21-0800200c9a66'], true],
            ['uuid', ['FF6F8CB0-C57D-11E1-9B21-0800200C9A66'], true],
            ['uuid', ['zf6f8cb0-c57d-11e1-9b21-0800200c9a66'], false],
            ['uuid', ['af6f8cb0c57d11e19b210800200c9a66'], false],
            ['uuid', ['ff6f8cb0-c57da-51e1-9b21-0800200c9a66'], false],
            ['uuid', ['af6f8cb-c57d-11e1-9b21-0800200c9a66'], false],
            ['uuid', ['3f6f8cb0-c57d-11e1-9b21-0800200c9a6'], false],
            ['throws', [function () { throw new LogicException('test'); }, 'LogicException'], true],
            ['throws', [function () { throw new LogicException('test'); }, 'IllogicException'], false],
            ['throws', [function () { throw new Exception('test'); }], true],
            ['throws', [function () { trigger_error('test'); }, 'Throwable'], true, false, 70000],
            ['throws', [function () { trigger_error('test'); }, 'Unthrowable'], false, false, 70000],
            ['throws', [function () { throw new Error(); }, 'Throwable'], true, true, 70000],
            ['ip', ['192.168.0.1'], true],
            ['ip', [new ToStringClass('192.168.0.1')], true],
            ['ip', ['255.255.255.255'], true],
            ['ip', ['0.0.0.0'], true],
            ['ip', ['2001:0db8:0000:0042:0000:8a2e:0370:7334'], true],
            ['ip', ['::ffff:192.0.2.1'], true],
            ['ip', ['::1'], true],
            ['ip', ['::'], true],
            ['ip', ['foo'], false],
            ['ip', [123], false],
            ['ip', [[]], false],
            ['ip', [null], false],
            ['ip', [false], false],
            ['ipv4', ['192.168.0.1'], true],
            ['ipv4', [new ToStringClass('192.168.0.1')], true],
            ['ipv4', ['255.255.255.255'], true],
            ['ipv4', ['0.0.0.0'], true],
            ['ipv4', ['2001:0db8:0000:0042:0000:8a2e:0370:7334'], false],
            ['ipv4', ['::ffff:192.0.2.1'], false],
            ['ipv4', ['::1'], false],
            ['ipv4', ['::'], false],
            ['ipv4', ['foo'], false],
            ['ipv4', [123], false],
            ['ipv4', [[]], false],
            ['ipv4', [null], false],
            ['ipv4', [false], false],
            ['ipv6', ['192.168.0.1'], false],
            ['ipv6', ['255.255.255.255'], false],
            ['ipv6', ['0.0.0.0'], false],
            ['ipv6', ['2001:0db8:0000:0042:0000:8a2e:0370:7334'], true],
            ['ipv6', [new ToStringClass('2001:0db8:0000:0042:0000:8a2e:0370:7334')], true],
            ['ipv6', ['::ffff:192.0.2.1'], true],
            ['ipv6', ['::1'], true],
            ['ipv6', ['::'], true],
            ['ipv6', ['foo'],  false],
            ['ipv6', [123], false],
            ['ipv6', [[]], false],
            ['ipv6', [null], false],
            ['ipv6', [false], false],
            ['email', ['foo'], false],
            ['email', [123], false],
            ['email', ['foo.com'], false],
            ['email', ['foo@bar.com'], true],
            ['email', [new ToStringClass('foo@bar.com')], true],
            ['uniqueValues', [['qwerty', 'qwerty']], false],
            ['uniqueValues', [['asdfg', 'qwerty']], true],
            ['uniqueValues', [[123, '123']], false],
        ];
    }

    public function getMethods()
    {
        $methods = [];

        foreach ($this->getTests() as $params) {
            $methods[$params[0]] = [$params[0]];
        }

        return array_values($methods);
    }

    /**
     * @dataProvider getTests
     */
    public function testAssert($method, $args, $success, $multibyte = false, $minVersion = null)
    {
        if ($minVersion && PHP_VERSION_ID < $minVersion) {
            $this->markTestSkipped(sprintf('This test requires php %s or upper.', $minVersion));

            return;
        }
        if ($multibyte && !function_exists('mb_strlen')) {
            $this->markTestSkipped('The function mb_strlen() is not available');
        }

        if (!$success) {
            $this->setExpectedException('\InvalidArgumentException');
        }

        call_user_func_array(['Webmozart\Assert\Assert', $method], $args);
        $this->addToAssertionCount(1);
    }

    /**
     * @dataProvider getTests
     */
    public function testNullOr($method, $args, $success, $multibyte = false, $minVersion = null)
    {
        if ($minVersion && PHP_VERSION_ID < $minVersion) {
            $this->markTestSkipped(sprintf('This test requires php %s or upper.', $minVersion));

            return;
        }
        if ($multibyte && !function_exists('mb_strlen')) {
            $this->markTestSkipped('The function mb_strlen() is not available');
        }

        if (!$success && null !== reset($args)) {
            $this->setExpectedException('\InvalidArgumentException');
        }

        call_user_func_array(['Webmozart\Assert\Assert', 'nullOr'.ucfirst($method)], $args);
        $this->addToAssertionCount(1);
    }

    /**
     * @dataProvider getMethods
     */
    public function testNullOrAcceptsNull($method)
    {
        call_user_func(['Webmozart\Assert\Assert', 'nullOr'.ucfirst($method)], null);
        $this->addToAssertionCount(1);
    }

    /**
     * @dataProvider getTests
     */
    public function testAllArray($method, $args, $success, $multibyte = false, $minVersion = null)
    {
        if ($minVersion && PHP_VERSION_ID < $minVersion) {
            $this->markTestSkipped(sprintf('This test requires php %s or upper.', $minVersion));

            return;
        }
        if ($multibyte && !function_exists('mb_strlen')) {
            $this->markTestSkipped('The function mb_strlen() is not available');
        }

        if (!$success) {
            $this->setExpectedException('\InvalidArgumentException');
        }

        $arg = array_shift($args);
        array_unshift($args, [$arg]);

        call_user_func_array(['Webmozart\Assert\Assert', 'all'.ucfirst($method)], $args);
        $this->addToAssertionCount(1);
    }

    /**
     * @dataProvider getTests
     */
    public function testAllTraversable($method, $args, $success, $multibyte = false, $minVersion = null)
    {
        if ($minVersion && PHP_VERSION_ID < $minVersion) {
            $this->markTestSkipped(sprintf('This test requires php %s or upper.', $minVersion));

            return;
        }
        if ($multibyte && !function_exists('mb_strlen')) {
            $this->markTestSkipped('The function mb_strlen() is not available');
        }

        if (!$success) {
            $this->setExpectedException('\InvalidArgumentException');
        }

        $arg = array_shift($args);
        array_unshift($args, new ArrayIterator([$arg]));

        call_user_func_array(['Webmozart\Assert\Assert', 'all'.ucfirst($method)], $args);
        $this->addToAssertionCount(1);
    }

    public function getStringConversions()
    {
        return [
            ['integer', ['foobar'], 'Expected an integer. Got: string'],
            ['string', [1], 'Expected a string. Got: integer'],
            ['string', [true], 'Expected a string. Got: boolean'],
            ['string', [null], 'Expected a string. Got: NULL'],
            ['string', [[]], 'Expected a string. Got: array'],
            ['string', [new stdClass()], 'Expected a string. Got: stdClass'],
            ['string', [self::getResource()], 'Expected a string. Got: resource'],

            ['eq', ['1', '2'], 'Expected a value equal to "2". Got: "1"'],
            ['eq', [new ToStringClass("XXX"), new ToStringClass("YYY")], 'Expected a value equal to '. __NAMESPACE__ . '\ToStringClass: "YYY". Got: '. __NAMESPACE__ . '\ToStringClass: "XXX"'],
            ['eq', [1, 2], 'Expected a value equal to 2. Got: 1'],
            ['eq', [true, false], 'Expected a value equal to false. Got: true'],
            ['eq', [true, null], 'Expected a value equal to null. Got: true'],
            ['eq', [null, true], 'Expected a value equal to true. Got: null'],
            ['eq', [[1], [2]], 'Expected a value equal to array. Got: array'],
            ['eq', [new ArrayIterator([]), new stdClass()], 'Expected a value equal to stdClass. Got: ArrayIterator'],
            ['eq', [1, self::getResource()], 'Expected a value equal to resource. Got: 1'],

            ['lessThan', [new \DateTime('2020-01-01 00:00:00'), new \DateTime('1999-01-01 00:00:00')], 'Expected a value less than DateTime: "1999-01-01T00:00:00+00:00". Got: DateTime: "2020-01-01T00:00:00+00:00"'],
        ];
    }

    /**
     * @dataProvider getStringConversions
     */
    public function testConvertValuesToStrings($method, $args, $exceptionMessage)
    {
        $this->setExpectedException('\InvalidArgumentException', $exceptionMessage);

        call_user_func_array(['Webmozart\Assert\Assert', $method], $args);
    }

    public function testAnUnknownMethodThrowsABadMethodCall()
    {
        $this->setExpectedException('\BadMethodCallException');

        Assert::nonExistentMethod();
    }
}

/**
 * @ignore
 */
class ToStringClass
{
    /**
     * @var string
     */
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function __toString()
    {
        return $this->value;
    }
}
