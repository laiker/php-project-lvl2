<?php

namespace Differ\Differ;

use Symfony\Component\Yaml\Yaml;

use function Functional\each;

/**
 * genDiff
 *
 * @param  string $pathToFile1
 * @param  string $pathToFile2
 * @param  string $format
 * @return string|false
 */
function genDiff(string $pathToFile1, string $pathToFile2, string $format = '')
{
    $objectFirst = getArrayEntity($pathToFile1);
    $objectSecond = getArrayEntity($pathToFile2);
    $arDiff = diff($objectFirst, $objectSecond);

    if ($format == 'json') {
        return json_encode($arDiff);
    }

    if ($format == 'plain') {
        return formatPlain($arDiff);
    }

    return formatDefault($arDiff);
}

/**
 * getArrayEntity
 *
 * @param  string $pathToFile
 * @return object
 */
function getArrayEntity(string $pathToFile)
{
    $data = '';
    $extension = pathinfo($pathToFile, PATHINFO_EXTENSION);
    $fileRawData = (string)\file_get_contents($pathToFile);

    if ($extension == 'yml' || $extension == 'yaml') {
        return (object)Yaml::parse($fileRawData, Yaml::PARSE_OBJECT_FOR_MAP);
    }

    //default json
    return (object)\json_decode($fileRawData);
}

/**
 * diff
 *
 * @param  object $objectFirst
 * @param  object $objectSecond
 * @return array<mixed>
 */
function diff(object $objectFirst, object $objectSecond): array
{
    $iter = function ($objectFirst, $objectSecond) use (&$iter) {
        $arDiff = [];
        if (\is_object($objectFirst)) {
            $diff1 = each((array)$objectFirst, function ($valueFirst, $keyFirst) use ($objectSecond, $iter, &$arDiff) {
                if (is_object($valueFirst)) {
                    $secondValue = (is_object($objectSecond) && \property_exists($objectSecond, $keyFirst)) ?
                        $objectSecond->{$keyFirst} : '';
                    $oldValue = $iter($valueFirst, $secondValue);
                } else {
                    $oldValue = sanitizeValue($valueFirst);
                }

                if (\property_exists($objectSecond, $keyFirst)) {
                    if (is_object($objectSecond->{$keyFirst})) {  /** @phpstan-ignore-line */
                        $newValue = $iter($valueFirst, $objectSecond->{$keyFirst}); /** @phpstan-ignore-line */
                    } else {
                        $newValue = sanitizeValue($objectSecond->{$keyFirst}); /** @phpstan-ignore-line */
                    }
                }

                if (isset($oldValue)) {
                    $arDiff[$keyFirst]['old'] = $oldValue; /** @phpstan-ignore-line */
                }

                if (isset($newValue)) {
                    $arDiff[$keyFirst]['new'] = $newValue; /** @phpstan-ignore-line */
                }
            });
        }

        if (\is_object($objectSecond)) {
            $objectFirst = is_object($objectFirst) ? $objectFirst : new \stdClass(); /** @phpstan-ignore-line */
            $dif = each((array)$objectSecond, function ($valueSecond, $keySecond) use ($objectFirst, $iter, &$arDiff) {
                if (!\property_exists($objectFirst, $keySecond)) {
                    if (\is_object($valueSecond)) {
                        /** @phpstan-ignore-next-line */
                        $arDiff[$keySecond]['new'] = $iter($objectFirst->{$keySecond}, $valueSecond);
                    } else {
                        $arDiff[$keySecond]['new'] = sanitizeValue($valueSecond); /** @phpstan-ignore-line */
                    }
                }
            });
        }

        $arSortedDiff = sortKeys($arDiff);

        return $arSortedDiff;
    };

    $arData = $iter($objectFirst, $objectSecond);

    return $arData;
}

/**
 * formatDefault
 *
 * @param  array<mixed> $arDiff
 * @return string
 */
function formatDefault($arDiff): string
{
    $iter = function ($arDiff, $level, $diffParent = false) use (&$iter) {

        $diffString = '{' . PHP_EOL;
        $arFormatDiff = [];
        $diffResult = each($arDiff, function ($value, $key) use ($iter, $level, $diffParent, &$arFormatDiff) {

            $noDiffValue = false;
            $valueNew = '';
            $valueOld = '';

            $hasOldValue = \array_key_exists('old', $value);
            $hasNewValue = \array_key_exists('new', $value);

            if ($hasOldValue) {
                /** @phpstan-ignore-next-line */
                $noDiffValue = \is_array($value['old']) && (!$hasNewValue || !\is_array($value['new']));
                /** @phpstan-ignore-next-line */
                $valueOld = \is_array($value['old']) ? $iter($value['old'], $level + 2, $noDiffValue) : $value['old'];
            }

            if ($hasNewValue) {
                /** @phpstan-ignore-next-line */
                $noDiffValue = (!$hasOldValue || !\is_array($value['old'])) && \is_array($value['new']);
                /** @phpstan-ignore-next-line */
                $valueNew = \is_array($value['new']) ? $iter($value['new'], $level + 2, $noDiffValue) : $value['new'];
            }

            if ($diffParent) {
                $diff = ' ';
            } else {
                $diff = '-';
            }

            if ($hasOldValue && $hasNewValue) {
                if ($valueOld != $valueNew) {
                    /** @phpstan-ignore-next-line */
                    $arFormatDiff[] = str_repeat('  ', $level) .  $diff . ' ' . $key . ': ' . $valueOld;
                    /** @phpstan-ignore-next-line */
                    $arFormatDiff[] = str_repeat('  ', $level) .  '+' . ' ' . $key . ': ' . $valueNew;
                } else {
                    /** @phpstan-ignore-next-line */
                    $arFormatDiff[] = str_repeat('  ', $level) .  ' ' . ' ' . $key . ': ' . $valueNew;
                }
            } else {
                if ($hasOldValue) {
                    /** @phpstan-ignore-next-line */
                    $arFormatDiff[] = str_repeat('  ', $level) .  $diff . ' ' . $key . ': ' . $valueOld;
                } else {
                    if ($diffParent) {
                        $diffPositive = ' ';
                    } else {
                        $diffPositive = '+';
                    }
                    /** @phpstan-ignore-next-line */
                    $arFormatDiff[] = str_repeat('  ', $level) .  $diffPositive . ' ' . $key . ': ' . $valueNew;
                }
            }
        });

        $diffString .= implode(PHP_EOL, $arFormatDiff); /** @phpstan-ignore-line */

        if ($level > 1) {
            $diffString .= PHP_EOL . str_repeat('  ', $level - 1)  . '}'; /** @phpstan-ignore-line */
        } else {
            $diffString .= PHP_EOL . '}'; /** @phpstan-ignore-line */
        }


        return $diffString;
    };

    return $iter($arDiff, 1, false);
}

/**
 * formatPlain
 *
 * @param  array<mixed> $arDiff
 * @return string
 */
function formatPlain(array $arDiff): string
{

    $iter = function ($arDiff, &$arFormatDiff = [], $level = 1, $currentLevel = '') use (&$iter) {

        $diffResult = each($arDiff, function ($value, $key) use ($iter, $currentLevel, $level, &$arFormatDiff) {

            $valueNew = '';
            $valueOld = '';
            $hasOldValue = \array_key_exists('old', $value);
            $hasNewValue = \array_key_exists('new', $value);

            $tempLevel = ($level == 1) ? $key : $currentLevel . '.' . $key;

            if ($hasOldValue) {
                if (\is_array($value['old']) && $hasNewValue && \is_array($value['new'])) {
                    /** @phpstan-ignore-next-line */
                    $valueOld = $iter($value['old'], $arFormatDiff, $level + 1, $tempLevel);
                } else {
                    /** @phpstan-ignore-next-line */
                    $valueOld = sanitizeValuePlain($value['old']);
                }
            }

            if ($hasNewValue) {
                if (\is_array($value['new']) && $hasOldValue && \is_array($value['old'])) {
                    /** @phpstan-ignore-next-line */
                    $valueNew = $iter($value['new'], $arFormatDiff, $level + 1, $tempLevel);
                } else {
                     /** @phpstan-ignore-next-line */
                     $valueNew = sanitizeValuePlain($value['new']);
                }
            }

            $formatString = '';
            if ($hasOldValue && $hasNewValue) {
                if ($valueOld != $valueNew) {
                    /** @phpstan-ignore-next-line */
                    $formatString = "Property '" . $tempLevel . "' was updated. From " . $valueOld . " to " . $valueNew;
                }
            } else {
                if (isset($value['old'])) {
                    $formatString = "Property '" . $tempLevel . "' was removed"; /** @phpstan-ignore-line */
                } else {
                    /** @phpstan-ignore-next-line */
                    $formatString = "Property '" . $tempLevel . "' was added with value: " . $valueNew;
                }
            }

            if ($formatString !== '' && !in_array($formatString, $arFormatDiff, true)) {
                $arFormatDiff[] = $formatString; /** @phpstan-ignore-line */
            }
        });

        return $arFormatDiff;
    };

    $arFormatDiff = $iter($arDiff);
    return implode(PHP_EOL, $arFormatDiff);
}

/**
 * sanitizeValue
 *
 * @param  mixed|string $value
 * @return mixed|null
 */
function sanitizeValue($value)
{
    if (is_object($value)) {
        return (array) $value;
    }

    if ($value === null) {
        return 'null';
    }

    return trim(var_export($value, true), '\'');
}

/**
 * sanitizeValuePlain
 *
 * @param  mixed $value
 * @return string
 */
function sanitizeValuePlain($value): string
{
    if (is_object($value) || is_array($value)) {
        return '[complex value]';
    }

    $value = trim(var_export($value, true), '\''); /** @phpstan-ignore-line */

    if (in_array(trim($value), ['true', 'false', 'null'], true) || is_numeric($value)) {
        return $value;
    }

    return "'{$value}'";
}


function sortKeys(array $arDiff): array
{
    $sort = ksort($arDiff); /** @phpstan-ignore-line */
    return $arDiff;
}
