<?php
namespace Repeka\Domain\Utils;

use Countable;

final class ArrayUtils {
    private function __construct() {
    }

    /**
     * Maps values to keys and groups matching items, preserving keys.
     * Eg. array_group_by([-1,2,1], Math.abs) === [ 1 => [0=>1, 2=>-1], 2 => [1=>2] ]
     * @param array $values
     * @param callable $mapper
     * @return array
     */
    public static function groupBy(array $values, callable $mapper) {
        $groups = [];
        foreach ($values as $key => $value) {
            $groupingKey = $mapper($value);
            if (!array_key_exists($groupingKey, $groups)) {
                $groups[$groupingKey] = [];
            }
            $groups[$groupingKey][$key] = $value;
        }
        return $groups;
    }

    /**
     * Filters an array with Countable values (eg. array) leaving only equal or longer than $minLength.
     * @param Countable[]|array[] $values
     * @param int $minLengthInclusive
     * @return array
     */
    public static function removeValuesShorterThan(array $values, int $minLengthInclusive) {
        return array_filter(
            $values,
            function ($valueArray) use ($minLengthInclusive) {
                return count($valueArray) >= $minLengthInclusive;
            }
        );
    }

    /**
     * Returns true if all values after applying function are equal.
     * @param array $values
     * @param callable $mapper
     * @return bool
     */
    public static function allEqual(array $values, callable $mapper): bool {
        $uniqueValues = array_unique(array_map($mapper, $values));
        return count($uniqueValues) <= 1;
    }
}
