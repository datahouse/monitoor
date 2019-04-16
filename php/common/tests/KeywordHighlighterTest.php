<?php

namespace Datahouse\MON\Common\Tests;

use Datahouse\MON\Common\KeywordHighlighter;

class KeywordHighlighterTest extends \PHPUnit_Framework_TestCase
{
    public function testEmptyString()
    {
        $section_mpos = [];
        list ($matching_keywords, $result)
            = KeywordHighlighter::mark($section_mpos, 0, "");
        $this->assertEquals("", $result);
        $this->assertEmpty($matching_keywords);
    }

    public function testNoMatches()
    {
        $input = "some uninteresting text";
        $section_mpos = [];
        list ($matching_keywords, $result)
            = KeywordHighlighter::mark($section_mpos, 0, $input);
        $this->assertEquals($input, $result);
        $this->assertEmpty($matching_keywords);
    }

    public function testSingleKeywordMatch()
    {
        $input = "a keyword";
        $section_mpos = [
            'key' => [0 => [2]]
        ];
        list ($matching_keywords, $result)
            = KeywordHighlighter::mark($section_mpos, 0, $input);
        $this->assertEquals("a <mark>key</mark>word", $result);
        $this->assertEquals([[2, 'key']], $matching_keywords);
    }

    public function testMultipleKeywordMatches()
    {
        $input = "a keyword or two or three";
        $section_mpos = [
            'key' => [0 => [2]],
            'or' => [0 => [6, 10, 17]],
            'three' => [0 => [20]]
        ];
        list ($matching_keywords, $result)
            = KeywordHighlighter::mark($section_mpos, 0, $input);
        $this->assertEquals(
            "a <mark>key</mark>w<mark>or</mark>d <mark>or</mark> two " .
            "<mark>or</mark> <mark>three</mark>",
            $result
        );

        $expSortedMatches = [
            [2, 'key'],
            [6, 'or'],
            [10, 'or'],
            [17, 'or'],
            [20, 'three']
        ];
        sort($matching_keywords);
        $this->assertEquals($expSortedMatches, $matching_keywords);
    }

    public function testOverlappingKeywordMatches()
    {
        $input = "a keyword or two or three";
        $section_mpos = [
            'key' => [0 => [2]],
            'or' => [0 => [6, 10, 17]],
            'word' => [0 => [5]]
        ];
        list ($matching_keywords, $result)
            = KeywordHighlighter::mark($section_mpos, 0, $input);
        $this->assertEquals(
            "a <mark>keyword</mark> <mark>or</mark> two " .
            "<mark>or</mark> three",
            $result
        );

        $expSortedMatches = [
            [2, 'key'],
            [5, 'word'],
            [6, 'or'],
            [10, 'or'],
            [17, 'or'],
        ];
        sort($matching_keywords);
        $this->assertEquals($expSortedMatches, $matching_keywords);
    }
}