<?php

namespace Gendiff\Gendiff;

function gendDiff(string $pathToFile1, string $pathToFile2, string $format) :string
{
    $dataFirst = \file_get_contents($pathToFile1);
    $dataSecond = \file_get_contents($pathToFile2);
    if ($format == 'json') {
        return diffJson($dataFirst, $dataSecond);
    }
}

function diffJson($jsonFirst, $jsonSecond)
{
    $arDiff = [];
    $diffString = '{' . PHP_EOL;
    $arJsonFirst = \json_decode($jsonFirst, true);
    $arJsonSecond = \json_decode($jsonSecond, true);
    foreach ($arJsonFirst as $key => $value) {
        $value = \json_encode($value);
        if (!array_key_exists($key, $arJsonSecond)) {
            $arDiff[$key . '_a'] = "  - {$key} : {$value}";
        } else {
            if ($value != \json_encode($arJsonSecond[$key])) {
                $arDiff[$key . '_a'] = "  - {$key} : {$value}";
                $arDiff[$key . '_b'] = "  + {$key} : {$arJsonSecond[$key]}";
            } else {
                $arDiff[$key . '_a'] = "    {$key} : {$value} ";
            }
        }
    }

    foreach ($arJsonSecond as $key => $value) {
        $value = \var_export($value, true);
        if (!array_key_exists($key, $arJsonFirst)) {
            $arDiff[$key . '_b'] = "  + {$key} : {$value}";
        }
    }

    ksort($arDiff);

    $diffString .= implode(PHP_EOL, $arDiff);

    $diffString .= PHP_EOL . '}';

    return $diffString;
}