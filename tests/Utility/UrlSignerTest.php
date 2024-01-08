<?php

namespace App\Tests\Utility;

use App\Utility\Security\Url;
use App\Utility\Security\UrlSigner;
use PHPUnit\Framework\TestCase;

class UrlSignerTest extends TestCase
{
    public function dataValidFor(): array
    {
        $url = 'https://example.com/wibble?wobble=12&wubble=toast';

        return [
            'validFor = 1000: Test time equals signature time' => [$url, 1644500418, 1000, 1644500418, true],
            'validFor = 1000: Test time within limits' => [$url, 1644500418, 1000, 1644500555, true],
            'validFor = 1000: Test time at limit' => [$url, 1644500418, 1000, 1644501417, true],
            'validFor = 1000: Test time exceeds limit' => [$url, 1644500418, 1000, 1644501418, false],
            'validFor = 1000: Test time before signature time' => [$url, 1644500418, 1000, 1644500417, true],

            'validFor = 0: Test time equals signature time' => [$url, 1644500418, 0, 1644500418, true],
            'validFor = 0: Test time before signature time' => [$url, 1644500418, 0, 1644400418, true],
            'validFor = 0: Test time after signature time' => [$url, 1644500418, 0, 1644600418, true],
            'validFor = 0: Test time far past' => [$url, 1644500418, 0, 0, true],
            'validFor = 0: Test time far future' => [$url, 1644500418, 0, 9999999999, true],
        ];
    }

    /**
     * @dataProvider dataValidFor
     */
    public function testValidFor(string $url, int $signatureTime, int $validFor, int $testTime, bool $expectedToBeValid): void
    {
        $signer = new UrlSigner('abcd12345');

        $signedUrl = $signer->sign($url, $validFor, $signatureTime);
        $isValid = $signer->isValid($signedUrl, $testTime);

        $this->assertEquals($expectedToBeValid, $isValid);
    }

    public function dataUrlModificationAttempts(): array
    {
        $url = 'https://example.com/wibble?wobble=12&wubble=toast';

        $modifyPair = function(string $key, string $value): callable {
            return fn(string $url) => (new Url($url))->setQueryParam($key, $value)->__toString();
        };

        $removePair = function(string $key): callable {
            return fn(string $url) => (new Url($url))->removeQueryParam($key)->__toString();
        };

        $modifyHost = function($host): callable {
            return fn(string $url) => (new Url($url))->setHost($host)->__toString();
        };

        $modifyScheme = function($protocol): callable {
            return fn(string $url) => (new Url($url))->setScheme($protocol)->__toString();
        };

        $modifyFragment = function($fragment): callable {
            return fn(string $url) => (new Url($url))->setFragment($fragment)->__toString();
        };

        return [
            'Add key/value' => [$url, $modifyPair('wabble', 5), false],
            'Modify existing key/value' => [$url, $modifyPair('wobble', 13), false],
            'Remove key/value' => [$url, $removePair('wobble'), false],
            'Modify _until' => [$url, $modifyPair('_until', 9999999999), false],
            'Remove _until' => [$url, $removePair('_until'), false],
            'Modify _signature' => [$url, $modifyPair('_signature', '18c143d7e91a8c4a6472518c2960dc1d'), false],
            'Remove _signature' => [$url, $removePair('_signature'), false],

            // Things that are allowed to change / aren't part of the signature
            'Modify host' => [$url, $modifyHost('example.net'), true],
            'Modify protocol' => [$url, $modifyScheme('http'), true],
            'Modify fragment' => [$url, $modifyFragment('wibble=wobble=wubble=wabble'), true],
        ];
    }

    /**
     * @dataProvider dataUrlModificationAttempts
     */
    public function testUrlModificationAttempts(string $url, callable $urlModifier, bool $expectedToBeValid): void
    {
        $signer = new UrlSigner('abcd12345');

        $signedUrl = $signer->sign($url, 10000, 1600000000);
        $modifiedUrl = $urlModifier($signedUrl);
        $isValid = $signer->isValid($modifiedUrl, 1600001000);

        $this->assertEquals($expectedToBeValid, $isValid);
    }
}