<?php

/**
 * PHP 8.1 declarations that Scrutinizer's analyzer does not know.
 *
 * Read only by Scrutinizer (dependency_paths); PHPStan excludes this file and
 * nothing loads it at runtime, where PHP provides the real ones.
 */

interface UnitEnum
{
    /** @return static[] */
    public static function cases(): array;
}

/**
 * @property-read int|string $value
 */
interface BackedEnum extends UnitEnum
{
    public static function from(int|string $value): static;

    public static function tryFrom(int|string $value): ?static;
}

function array_is_list(array $array): bool
{
}
