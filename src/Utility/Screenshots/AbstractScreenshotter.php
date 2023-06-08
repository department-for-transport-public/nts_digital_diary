<?php

namespace App\Utility\Screenshots;

use App\Utility\XPath;
use Nesk\Puphpeteer\Resources\Browser;
use Nesk\Puphpeteer\Resources\ElementHandle;
use Nesk\Puphpeteer\Resources\Page;
use Nesk\Rialto\Data\JsFunction;

abstract class AbstractScreenshotter
{
    protected Browser $browser;
    protected string $screenshotsBaseDir;
    protected string $hostname;
    protected Page $page;

    public function __construct(Browser $browser, string $screenshotsBaseDir, string $hostname)
    {
        $this->browser = $browser;
        $this->screenshotsBaseDir = $screenshotsBaseDir;
        $this->hostname = $hostname;
        $this->page = $this->getNewPage();
    }

    protected function getNewPage(): Page
    {
        $page = $this->browser->newPage();
        $page->setCacheEnabled(false);
        $page->setViewport(["width" => 1003, "height" => 200]);
        return $page;
    }

    public function getWaitForOptions(): array
    {
        return [
            'timeout' => 8000,
            'waitUntil' => "networkidle0",
        ];
    }

    protected function goto(string $urlPath): void
    {
        $this->page->goto("{$this->hostname}{$urlPath}", []);
    }

    /**
     * @throws ScreenshotsException
     */
    protected function screenshot(string $filename): void
    {
        self::takeScreenshot($this->page, "{$this->screenshotsBaseDir}{$filename}");
    }

    /**
     * @throws ScreenshotsException
     */
    public static function takeScreenshot(Page $page, string $fullPath): void
    {
        $fullDir = dirname($fullPath);

        if (!is_dir($fullDir) && !mkdir($fullDir, 0777, true)) {
            throw new ScreenshotsException("Failed to create screenshots directory: '{$fullDir}'");
        }

        $page->screenshot(["path" => $fullPath, 'fullPage' => true]);
    }

    protected function clickAndWait(string $selector): void
    {
        $this->page->click($selector, []);
        $this->page->waitForNavigation($this->getWaitForOptions());
    }

    // -----

    /**
     * @throws ScreenshotsException
     */
    protected function clickButtonWithText(string $text, int $index = 0, bool $waitForNavigation=true): void
    {
        $xpath = XPath::create()->withTag('button')->withText($text);
        $this->clickLink($xpath, $index, $waitForNavigation);
    }

    /**
     * @throws ScreenshotsException
     */
    protected function clickButtonWithTextThatStartsWith(string $text, int $index = 0, bool $waitForNavigation=true): void
    {
        $xpath = XPath::create()->withTag('button')->withTextStartsWith($text);
        $this->clickLink($xpath, $index, $waitForNavigation);
    }

    /**
     * @throws ScreenshotsException
     */
    protected function clickLinkWithText(string $text, int $index = 0, bool $waitForNavigation=true): void
    {
        $xpath = XPath::create()->withTag('a')->withText($text);
        $this->clickLink($xpath, $index, $waitForNavigation);
    }

    /**
     * @throws ScreenshotsException
     */
    protected function clickLinkWithTextThatStartsWith(string $text, int $index = 0, bool $waitForNavigation=true): void
    {
        $xpath = XPath::create()->withTag('a')->withTextStartsWith($text);
        $this->clickLink($xpath, $index, $waitForNavigation);
    }

    /**
     * @throws ScreenshotsException
     */
    protected function clickLink(string $expression, int $index = 0, bool $waitForNavigation = true): void
    {
        $this->findElement($expression, $index)->click([]);
        if ($waitForNavigation) {
            $this->page->waitForNavigation($this->getWaitForOptions());
        }
    }

    /**
     * @throws ScreenshotsException
     */
    protected function findElement(string $xpathExpression, int $index = 0): ElementHandle
    {
        $elements = $this->page->querySelectorXPath($xpathExpression);

        if (!isset($elements[$index])) {
            throw new ScreenshotsException("Element not found for {$xpathExpression}[{$index}]", $this->page);
        }

        return $elements[$index];
    }

    /**
     * @throws ScreenshotsException
     */
    protected function submit(array $fields, string $buttonTextStartsWith, bool $overwriteFields = false): void
    {
        $this->fillForm($fields, '//form', $overwriteFields);
        $this->clickButtonWithTextThatStartsWith($buttonTextStartsWith);
    }

    /**
     * @throws ScreenshotsException
     */
    protected function fillForm(array $fields, string $xpathPrefix = '//form', bool $overwriteFields = false)
    {
        foreach($fields as $labelOrId => $value) {
            if (is_array($value)) {
                $xpath = str_starts_with($labelOrId, '#')
                    ? '//*[@id="' . substr($labelOrId, 1) . '"]'
                    : (new XPath())->withPrefix($xpathPrefix)->withTag('legend')->withTextStartsWith($labelOrId)->withSuffix('/..');
                $this->fillForm($value, $xpath);
                continue;
            }

            if (str_starts_with($labelOrId, '#')) {
                $targetId = $labelOrId;
            } else {
                $xpath = (new XPath())->withPrefix($xpathPrefix)->withTag('label')->withTextStartsWith($labelOrId);
                $labelOrId = $this->findElement($xpath);
                $targetId = $labelOrId->evaluate(JsFunction::createWithParameters(['node'])->body('return node.getAttribute("for");'));

                if (!$targetId) {
                    throw new ScreenshotsException("Could not find referenced (for) element for label element '{$labelOrId}'", $this->page);
                }

                $targetId = "#{$targetId}";
            }

            if (is_bool($value)) {
                if ($value === true) {
                    $this->page->click($targetId, []);
                } else {
                    // Makes no logical sense - we click, or we don't
                    throw new ScreenshotsException("Value cannot be 'false' for label {$labelOrId}", $this->page);
                }
            } else {
                if ($overwriteFields) {
                    // See: https://stackoverflow.com/a/52633235/865429
                    $this->page->click($targetId, ['clickCount' => 4]);
                }

                $this->page->type($targetId, $value, []);
            }
        }
    }

    protected function getFormElementXPath(string $xpathPrefix, string $tag, string $idOrTextStartsWith): string
    {
        return str_starts_with($idOrTextStartsWith, '#')
            ? '//*[@id="' . substr($idOrTextStartsWith, 1) . '"]'
            : (new XPath())->withPrefix($xpathPrefix)->withTag($tag)->withTextStartsWith($label)->$xpath->withSuffix('/..');
    }
}