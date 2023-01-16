<?php

namespace App\Tests\Utility;

use App\Utility\XPath;
use PHPUnit\Framework\TestCase;

class XPathTest extends TestCase
{
    public function testButtonWithText()
    {
        $expected = '//button' . $this->predicate(['text' => 'Banana']);
        $actual = XPath::create()->withTag('button')->withText('Banana');
        $this->assertEquals($expected, "".$actual);
    }

    public function testButtonWithTextThatStartsWith()
    {
        $expected = '//button' . $this->predicate(['text-starts-with' => 'Banana']);
        $actual = XPath::create()->withTag('button')->withTextStartsWith('Banana');
        $this->assertEquals($expected, "".$actual);
    }

    public function testClass()
    {
        $expected = '//div' . $this->predicate(['class' => 'onboarding-codes__code-pair']);
        $actual = XPath::create()->withTag('div')->withClass('onboarding-codes__code-pair');
        $this->assertEquals($expected, "".$actual);
    }

    public function testClassAndTextStartsWith()
    {
        $expected = '//label' . $this->predicate(['class' => 'onboarding-codes__code-pair', 'text-starts-with' => 'Toast']);
        $actual = XPath::create()->withTag('label')->withClass('onboarding-codes__code-pair')->withTextStartsWith('Toast');
        $this->assertEquals($expected, "".$actual);
    }

    /**
     * This is the old predicate() function, and it's used here to see whether
     * our new builder output is identical.
     */
    protected function predicate(array $options): string
    {
        $predicates = [];

        if (($text = $options['text'] ?? null) !== null) {
            $predicates[] = "normalize-space(text()) = '{$text}'";
        }

        if (($text = $options['text-starts-with'] ?? null) !== null) {
            $predicates[] = "starts-with(normalize-space(text()), '{$text}')";
        }

        if (($class = $options['class'] ?? null) !== null) {
            $predicates[] = "contains(concat(' ', normalize-space(@class), ' '), ' {$class} ')";
        }

        if (empty($predicates)) {
            return '';
        }

        $predicateStr = join(' and ', $predicates);
        return "[{$predicateStr}]";
    }
}