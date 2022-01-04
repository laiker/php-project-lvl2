<?php

namespace Gendiff\Gendiff\Tests;

use PHPUnit\Framework\TestCase;

use function Differ\Differ\genDiff;

class GenDiffTest extends TestCase
{
    public function testJsonDiff(): void
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
        $this->assertSame($resultString, genDiff($beforePath, $afterPath));
    }

    public function testYamlDiff(): void
    {
        $beforePath = 'tests/fixtures/before.yml';
        $afterPath = 'tests/fixtures/after.yml';
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
        $this->assertSame($resultString, genDiff($beforePath, $afterPath));
    }

    public function testDifferentFormatsDiff(): void
    {
        $beforePath = 'tests/fixtures/before.json';
        $afterPath = 'tests/fixtures/after.yaml';
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
        $this->assertSame($resultString, genDiff($beforePath, $afterPath));
    }

    public function testRecursiveDiff(): void
    {

        $beforePath = 'tests/fixtures/beforeRec.json';
        $afterPath = 'tests/fixtures/afterRec.json';
        $resultString = <<<DOC
        {
            common: {
              + follow: false
                setting1: Value 1
              - setting2: 200
              - setting3: true
              + setting3: null
              + setting4: blah blah
              + setting5: {
                    key5: value5
                }
                setting6: {
                    doge: {
                      - wow: 
                      + wow: so much
                    }
                    key: value
                  + ops: vops
                }
            }
            group1: {
              - baz: bas
              + baz: bars
                foo: bar
              - nest: {
                    key: value
                }
              + nest: str
            }
          - group2: {
                abc: 12345
                deep: {
                    id: 45
                }
            }
          + group3: {
                deep: {
                    id: {
                        number: 45
                    }
                }
                fee: 100500
            }
        }
        DOC;
        $this->assertSame($resultString, genDiff($beforePath, $afterPath));
    }

    public function testRecursiveDiff2(): void
    {
        $beforePath = 'tests/fixtures/before2.json';
        $afterPath = 'tests/fixtures/after2.json';
        $resultString =
        <<<DOC
        {
            common: {
              + follow: false
                setting1: Value 1
              - setting2: 200
              - setting3: true
              + setting3: {
                    key: value
                }
              + setting4: blah blah
              + setting5: {
                    key5: value5
                }
                setting6: {
                    doge: {
                      - wow: too much
                      + wow: so much
                    }
                    key: value
                  + ops: vops
                }
            }
            group1: {
              - baz: bas
              + baz: bars
                foo: bar
              - nest: {
                    key: value
                }
              + nest: str
            }
          - group2: {
                abc: 12345
                deep: {
                    id: 45
                }
            }
          + group3: {
                deep: {
                    id: {
                        number: 45
                    }
                }
                fee: 100500
            }
            group4: {
              - default: null
              + default: 
              - foo: 0
              + foo: null
              - isNested: false
              + isNested: none
              + key: false
                nest: {
                  - bar: 
                  + bar: 0
                  - isNested: true
                }
              + someKey: true
              - type: bas
              + type: bar
            }
        }
        DOC;
        $this->assertSame($resultString, genDiff($beforePath, $afterPath));
    }

    public function testRecursiveDiffYaml(): void
    {

        $beforePath = 'tests/fixtures/beforeRec.yaml';
        $afterPath = 'tests/fixtures/afterRec.yaml';
        $resultString = <<<DOC
        {
            common: {
              + follow: false
                setting1: Value 1
              - setting2: 200
              - setting3: true
              + setting3: null
              + setting4: blah blah
              + setting5: {
                    key5: value5
                }
                setting6: {
                    doge: {
                      - wow: 
                      + wow: so much
                    }
                    key: value
                  + ops: vops
                }
            }
            group1: {
              - baz: bas
              + baz: bars
                foo: bar
              - nest: {
                    key: value
                }
              + nest: str
            }
          - group2: {
                abc: 12345
                deep: {
                    id: 45
                }
            }
          + group3: {
                deep: {
                    id: {
                        number: 45
                    }
                }
                fee: 100500
            }
        }
        DOC;
        $this->assertSame($resultString, genDiff($beforePath, $afterPath));
    }

    public function testRecursiveDiffPlain(): void
    {

        $beforePath = 'tests/fixtures/beforeRec.json';
        $afterPath = 'tests/fixtures/afterRec.json';
        $resultString = <<<DOC
        Property 'common.follow' was added with value: false
        Property 'common.setting2' was removed
        Property 'common.setting3' was updated. From true to null
        Property 'common.setting4' was added with value: 'blah blah'
        Property 'common.setting5' was added with value: [complex value]
        Property 'common.setting6.doge.wow' was updated. From '' to 'so much'
        Property 'common.setting6.ops' was added with value: 'vops'
        Property 'group1.baz' was updated. From 'bas' to 'bars'
        Property 'group1.nest' was updated. From [complex value] to 'str'
        Property 'group2' was removed
        Property 'group3' was added with value: [complex value]
        DOC;
        $this->assertSame($resultString, genDiff($beforePath, $afterPath, 'plain'));
    }

    public function testRecursiveJson(): void
    {

        $beforePath = 'tests/fixtures/beforeRec.json';
        $afterPath = 'tests/fixtures/afterRec.json';
        $resultString = '{"common":{"old":{"follow":{"new":"false"},"setting1":{"old":"Value 1","new":"Value 1"},"setting2":{"old":"200"},"setting3":{"old":"true","new":"null"},"setting4":{"new":"blah blah"},"setting5":{"new":{"key5":{"old":"value5","new":"value5"}}},"setting6":{"old":{"doge":{"old":{"wow":{"old":"","new":"so much"}},"new":{"wow":{"old":"","new":"so much"}}},"key":{"old":"value","new":"value"},"ops":{"new":"vops"}},"new":{"doge":{"old":{"wow":{"old":"","new":"so much"}},"new":{"wow":{"old":"","new":"so much"}}},"key":{"old":"value","new":"value"},"ops":{"new":"vops"}}}},"new":{"follow":{"new":"false"},"setting1":{"old":"Value 1","new":"Value 1"},"setting2":{"old":"200"},"setting3":{"old":"true","new":"null"},"setting4":{"new":"blah blah"},"setting5":{"new":{"key5":{"old":"value5","new":"value5"}}},"setting6":{"old":{"doge":{"old":{"wow":{"old":"","new":"so much"}},"new":{"wow":{"old":"","new":"so much"}}},"key":{"old":"value","new":"value"},"ops":{"new":"vops"}},"new":{"doge":{"old":{"wow":{"old":"","new":"so much"}},"new":{"wow":{"old":"","new":"so much"}}},"key":{"old":"value","new":"value"},"ops":{"new":"vops"}}}}},"group1":{"old":{"baz":{"old":"bas","new":"bars"},"foo":{"old":"bar","new":"bar"},"nest":{"old":{"key":{"old":"value"}},"new":"str"}},"new":{"baz":{"old":"bas","new":"bars"},"foo":{"old":"bar","new":"bar"},"nest":{"old":{"key":{"old":"value"}},"new":"str"}}},"group2":{"old":{"abc":{"old":"12345"},"deep":{"old":{"id":{"old":"45"}}}}},"group3":{"new":{"deep":{"old":{"id":{"old":{"number":{"old":"45","new":"45"}},"new":{"number":{"old":"45","new":"45"}}}},"new":{"id":{"old":{"number":{"old":"45","new":"45"}},"new":{"number":{"old":"45","new":"45"}}}}},"fee":{"old":"100500","new":"100500"}}}}';// phpcs:ignore
        $this->assertSame($resultString, genDiff($beforePath, $afterPath, 'json'));
    }

    public function testRecursiveDiffPlain2(): void
    {
        $beforePath = 'tests/fixtures/before2.json';
        $afterPath = 'tests/fixtures/after2.json';
        $resultString = <<<DOC
        Property 'common.follow' was added with value: false
        Property 'common.setting2' was removed
        Property 'common.setting3' was updated. From true to [complex value]
        Property 'common.setting4' was added with value: 'blah blah'
        Property 'common.setting5' was added with value: [complex value]
        Property 'common.setting6.doge.wow' was updated. From 'too much' to 'so much'
        Property 'common.setting6.ops' was added with value: 'vops'
        Property 'group1.baz' was updated. From 'bas' to 'bars'
        Property 'group1.nest' was updated. From [complex value] to 'str'
        Property 'group2' was removed
        Property 'group3' was added with value: [complex value]
        Property 'group4.default' was updated. From null to ''
        Property 'group4.foo' was updated. From 0 to null
        Property 'group4.isNested' was updated. From false to 'none'
        Property 'group4.key' was added with value: false
        Property 'group4.nest.bar' was updated. From '' to 0
        Property 'group4.nest.isNested' was removed
        Property 'group4.someKey' was added with value: true
        Property 'group4.type' was updated. From 'bas' to 'bar'
        DOC;
        $this->assertSame($resultString, genDiff($beforePath, $afterPath, 'plain'));
    }
}
