<?php

namespace Differ\Differ;

use stdClass;
use Symfony\Component\Yaml\Yaml;

function genDiff(string $pathToFile1, string $pathToFile2, string $format = ''): string
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

function getArrayEntity(string $pathToFile)
{
    $extension = pathinfo($pathToFile, PATHINFO_EXTENSION);
    $fileRawData = (string)\file_get_contents($pathToFile);
    if ($extension == 'json') {
        return \json_decode($fileRawData);
    }

    if ($extension == 'yml' || $extension == 'yaml') {
        return Yaml::parse($fileRawData, Yaml::PARSE_OBJECT_FOR_MAP);
    }
}

function diff(object $objectFirst, object $objectSecond)
{
    $iter = function ($objectFirst, $objectSecond) use (&$iter) {

        $arDiff = [];
        if (\is_object($objectFirst)) {
            foreach ((array)$objectFirst as $keyFirst => $valueFirst) {
                if (is_object($valueFirst)) {
                    $secondValue = (is_object($objectSecond) && \property_exists($objectSecond, $keyFirst)) ?
                        $objectSecond->{$keyFirst} : '';
                    $arDiff[$keyFirst]['old'] = $iter($valueFirst, $secondValue);
                } else {
                    $arDiff[$keyFirst]['old'] = sanitizeValue($valueFirst);
                }

                if (\property_exists($objectSecond, $keyFirst)) {
                    if (is_object($objectSecond->{$keyFirst})) {
                        $arDiff[$keyFirst]['new'] = $iter($valueFirst, $objectSecond->{$keyFirst});
                    } else {
                        $arDiff[$keyFirst]['new'] = sanitizeValue($objectSecond->{$keyFirst});
                    }
                }
            }
        }

        if (\is_object($objectSecond)) {
            $objectFirst = is_object($objectFirst) ? $objectFirst : new \stdClass();
            foreach ((array)$objectSecond as $keySecond => $valueSecond) {
                if (!\property_exists($objectFirst, $keySecond)) {
                    if (\is_object($valueSecond)) {
                        $arDiff[$keySecond]['new'] = $iter($objectSecond->{$keySecond}, $valueSecond);
                    } else {
                        $arDiff[$keySecond]['new'] = sanitizeValue($valueSecond);
                    }
                }
            }
        }

        ksort($arDiff);

        return $arDiff;
    };

    $arData = $iter($objectFirst, $objectSecond);

    return $arData;
}

function formatDefault(array $arDiff)
{
    $iter = function ($arDiff, $level, $diffParent = false) use (&$iter) {

        $diffString = '{' . PHP_EOL;

        $arFormatDiff = [];

        foreach ($arDiff as $key => $value) {
            $noDiffValue = false;

            $hasOldValue = \array_key_exists('old', $value);
            $hasNewValue = \array_key_exists('new', $value);

            if ($hasOldValue) {
                $noDiffValue = \is_array($value['old']) && (!$hasNewValue || !\is_array($value['new']));
                $valueOld = \is_array($value['old']) ? $iter($value['old'], $level + 2, $noDiffValue) : $value['old'];
            }

            if ($hasNewValue) {
                $noDiffValue = (!$hasOldValue || !\is_array($value['old'])) && \is_array($value['new']);
                $valueNew = \is_array($value['new']) ? $iter($value['new'], $level + 2, $noDiffValue) : $value['new'];
            }

            if ($diffParent) {
                $diff = ' ';
            } else {
                $diff = '-';
            }

            if ($hasOldValue && $hasNewValue) {
                if ($valueOld != $valueNew) {
                    $arFormatDiff[] = str_repeat('  ', $level) .  $diff . ' ' . $key . ': ' . $valueOld;
                    $arFormatDiff[] = str_repeat('  ', $level) .  '+' . ' ' . $key . ': ' . $valueNew;
                } else {
                    $arFormatDiff[] = str_repeat('  ', $level) .  ' ' . ' ' . $key . ': ' . $valueNew;
                }
            } else {
                if ($hasOldValue) {
                    $arFormatDiff[] = str_repeat('  ', $level) .  $diff . ' ' . $key . ': ' . $valueOld;
                } else {
                    if ($diffParent) {
                        $diffPositive = ' ';
                    } else {
                        $diffPositive = '+';
                    }
                    $arFormatDiff[] = str_repeat('  ', $level) .  $diffPositive . ' ' . $key . ': ' . $valueNew;
                }
            }
        }

        $diffString .= implode(PHP_EOL, $arFormatDiff);

        if ($level > 1) {
            $diffString .= PHP_EOL . str_repeat('  ', $level - 1)  . '}';
        } else {
            $diffString .= PHP_EOL . '}';
        }


        return $diffString;
    };

    return $iter($arDiff, 1, false);
}

function formatPlain(array $arDiff)
{

    $iter = function ($arDiff, &$arFormatDiff = [], $level = 1, $currentLevel = '') use (&$iter) {

        foreach ($arDiff as $key => $value) {
            $hasOldValue = \array_key_exists('old', $value);
            $hasNewValue = \array_key_exists('new', $value);

            $tempLevel = ($level == 1) ? $key : $currentLevel . '.' . $key;

            if ($hasOldValue) {
                if (\is_array($value['old']) && $hasNewValue && \is_array($value['new'])) {
                    $valueOld = $iter($value['old'], $arFormatDiff, $level + 1, $tempLevel);
                } else {
                    $valueOld = isset($value['old']) ? sanitizeValuePlain($value['old']) : '';
                }
            }

            if ($hasNewValue) {
                if (\is_array($value['new']) && $hasOldValue && \is_array($value['old'])) {
                    $valueNew = $iter($value['new'], $arFormatDiff, $level + 1, $tempLevel);
                } else {
                    $valueNew = isset($value['new']) ? sanitizeValuePlain($value['new']) : '';
                }
            }

            $formatString = '';
            if ($hasOldValue && $hasNewValue) {
                if ($valueOld != $valueNew) {
                    $formatString = "Property '" . $tempLevel . "' was updated. From " . $valueOld . " to " . $valueNew;
                }
            } else {
                if (isset($value['old'])) {
                    $formatString = "Property '" . $tempLevel . "' was removed";
                } else {
                    $formatString = "Property '" . $tempLevel . "' was added with value: " . $valueNew;
                }
            }

            if ($formatString && !in_array($formatString, $arFormatDiff)) {
                $arFormatDiff[] = $formatString;
            }
        }

        return $arFormatDiff;
    };

    $arFormatDiff = $iter($arDiff);
    return implode(PHP_EOL, $arFormatDiff);
}

function sanitizeValue($value)
{
    if (is_object($value)) {
        return (array) $value;
    }

    return \str_replace('"', '', \json_encode($value));
}

function sanitizeValuePlain($value)
{
    if (is_object($value) || is_array($value)) {
        return '[complex value]';
    }

    $value = \str_replace('"', '', $value);

    if (in_array(trim($value), ['true', 'false', 'null']) || is_numeric($value)) {
        return $value;
    }

    return "'{$value}'";
}
