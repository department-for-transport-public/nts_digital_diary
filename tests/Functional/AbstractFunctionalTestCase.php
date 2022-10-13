<?php

namespace App\Tests\Functional;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;

class AbstractFunctionalTestCase extends PantherTestCase
{
    public const WAIT_TIMEOUT = 5;
    protected Client $client;

    public function initialiseClientAndLoadFixtures(array $fixtures): void
    {
        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $databaseTool->loadFixtures($fixtures);

        $this->client = static::createPantherClient();
    }

    public function loginUser(?string $userIdentifier, ?string $password = 'password'): void
    {
        $this->client->request('GET', '/');
        $this->client->clickLink('Sign in');
        $this->client->submitForm('user_login_sign_in', [
            'user_login[group][email]' => $userIdentifier,
            'user_login[group][password]' => $password,
        ]);
    }

    public function loginOtpUser(?string $firstPasscode, ?string $secondPasscode): void
    {
        $this->client->request('GET', '/onboarding');
        $this->client->submitForm('otp_login_sign_in', [
            'otp_login[group][identifier]' => $firstPasscode,
            'otp_login[group][passcode]' => $secondPasscode,
        ]);
    }

    public function assertPathMatches(string $expectedPath, bool $isRegex, string $errorMessage = 'Page path does not match regex'): void
    {
        $this->doAssertPathMatches($expectedPath, $isRegex, false, $errorMessage);
    }

    public function assertPathNotMatches(string $expectedPath, bool $isRegex, string $errorMessage = 'Page path does not match regex'): void
    {
        $this->doAssertPathMatches($expectedPath, $isRegex, true, $errorMessage);
    }

    protected function getUrlPath()
    {
        return parse_url($this->client->getCurrentURL(), PHP_URL_PATH);
    }

    protected function getHeading(): string
    {
        return $this->client->findElement(WebDriverBy::xpath('//*[@id="main-content"]/h1'))->getText();
    }

    protected function getTableData(string $xpathToTableRows = '//*[@id="main-content"]/table/tbody/tr'): array
    {
        return array_map(function (WebDriverElement $tableRow) {
            $subElements = $tableRow->findElements(WebDriverBy::xpath('./*'));
            return array_map(fn(WebDriverElement $x) => $x->getText(), $subElements);
        }, $this->client->findElements(WebDriverBy::xpath($xpathToTableRows)));
    }

    protected function getSummaryListData(string $xpathToSummaryListRows = '//*[@id="main-content"]/dl/div'): array
    {
        return $this->getTableData($xpathToSummaryListRows);
    }

    protected function getTableCaption(string $xpathToCaption = '//*[@id="main-content"]/table/caption'): string
    {
        return $this->client->findElement(WebDriverBy::xpath($xpathToCaption))->getText();
    }

    protected function getCurrentPath(): string
    {
        return parse_url($this->client->getCurrentURL(), PHP_URL_PATH);
    }

    protected function assertDataSize(array $tableData, ?int $rowCount = null, ?int $columnCount = null): void
    {
        if ($rowCount !== null) {
            $this->assertCount($rowCount, $tableData, "Expected {$rowCount} rows");
        }

        if ($columnCount !== null) {
            $this->assertCount($columnCount, $tableData[0], "Expected each row to have {$columnCount} entries");
        }
    }

    protected function clickLinkContaining(string $textContains, int $index = 0, string $xpathBase = '//'): void
    {
        // Doing things this way seems to mean that we don't need to use client->waitFor()
        // (compared with client->findElement->click)
        $textContains = str_replace("'", "\'", $textContains);
        $xpath = $xpathBase.'a[contains(text(), "' . $textContains . '")]';

        $links = $this->client->getCrawler()->filterXPath($xpath)->links();
        $link = $links[$index] ?? null;

        if ($link === null) {
            $this->fail("No such link found (contains: \"$textContains\", index: $index)");
        }

        $this->client->click($link);
    }

    protected function getElementByTextContains(string $elementType, string $textContains, int $elementNumber = 0): WebDriverElement
    {
        $textContains = str_replace("'", "\'", $textContains);
        $elements = $this->client->findElements(WebDriverBy::xpath("//".$elementType."[contains(text(), '" . $textContains . "')]"));

        if (count($elements) <= $elementNumber) {
            $this->fail("Element #{$elementNumber} with text containing '{$textContains}' not found");
        }

        return $elements[$elementNumber];
    }

    protected function doBreadcrumbsTestStartingAt(string $url, int $numberOfLevels): void
    {
        $this->client->request('GET', $url);

        // On the initial dashboard page, there are no breadcrumbs, so this one's already in the array...
        $urls = [$this->getCurrentPath()];

        // Go deeper into the dashboard by clicking the view button on each successive page, and adding the new URL to
        // our list of URLs. For each page, check that these URLs match the URLs of the links on the breadcrumbs.
        //
        // This is repeated twice, since there are a total of three pages involved (dashboard > household > diary keeper)
        for($i = 0; $i < ($numberOfLevels - 1); $i++) {
            $this->clickLinkContaining('View');
            $urls[] = $this->getCurrentPath();

            $breadcrumbs = $this->client->findElements(WebDriverBy::xpath("//ol[@class='govuk-breadcrumbs__list']/li/a[@class='govuk-breadcrumbs__link']"));
            $breadcrumbUrls = array_map(fn(WebDriverElement $e) => $e->getAttribute('href'), $breadcrumbs);

            // The last breadcrumb entry (the current page) is not linked
            $allButTheLastUrl = array_slice($urls, 0, -1);

            foreach($allButTheLastUrl as $idx => $url) {
                $this->assertEquals($url, $breadcrumbUrls[$idx]);
            }
        }
    }


    public function doBackLinksTestStartingAt(string $url, int $numberOfLevels): void
    {
        $this->client->request('GET', $url);

        for($i = 0; $i < ($numberOfLevels - 1); $i++) {
            $url = $this->getCurrentPath();
            $this->clickLinkContaining('View');
            $backLinkUrl = $this->getElementByTextContains('a', 'Back to')->getAttribute('href');
            $this->assertEquals($url, $backLinkUrl);
        }
    }

    protected function assertDataHasKeyValuePair(array $data, string $key, string $value) {
        foreach($data as [$rowKey, $rowValue]) {
            if ($key === $rowKey) {
                $this->assertEquals($value, $rowValue);
                return;
            }
        }

        $this->fail("Missing data in table: '$key' => '$value'");
    }

    protected function assertTimeEquals(\DateTime $expected, ?\DateTime $actual): void
    {
        $this->assertEquals($expected->format('H'), $actual->format('H'));
        $this->assertEquals($expected->format('i'), $actual->format('i'));
    }

    protected function assertDateEquals(\DateTime $expected, ?\DateTime $actual): void
    {
        $this->assertEquals($expected->format('Y'), $actual->format('Y'));
        $this->assertEquals($expected->format('m'), $actual->format('m'));
        $this->assertEquals($expected->format('d'), $actual->format('d'));
    }

    protected function doAssertPathMatches(string $expectedPath, bool $isRegex, bool $negate = false, string $errorMessage = 'Page path does not match regex'): void
    {
        $functionMap = [
            'Regex' => 'assertMatchesRegularExpression',
            'NotRegex' => 'assertDoesNotMatchRegularExpression',
            'Equals' => 'assertEquals',
            'NotEquals' => 'assertNotEquals',
        ];

        $func = $functionMap[($negate ? 'Not' : '') . ($isRegex ? 'Regex' : 'Equals')];
        $this->$func($expectedPath, $this->getCurrentPath(), $errorMessage);
    }
}