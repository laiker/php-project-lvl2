<?php
namespace Gendiff\Gendiff\Tests;

use PHPUnit\Framework\TestCase;
use function Gendiff\Gendiff\genDiff;

class genDiffTest extends TestCase
{
    public function testJsonDiff()
    {
        $beforePath = 'tests/fixtures/before.json';
        $afterPath = 'tests/fixtures/after.json';
        $resultString = <<<DOC
        {
          - follow: false
            host: hexlet.io
          - proxy: 123.234.53.22
          - timeout: 50
          + timeout: 20
          + verbose: true
        }
        DOC;
        $this->assertSame($resultString, genDiff($beforePath, $afterPath, 'json'));
    }
}