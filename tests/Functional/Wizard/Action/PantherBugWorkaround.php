<?php

namespace App\Tests\Functional\Wizard\Action;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverElement;
use Facebook\WebDriver\WebDriverSelect;
use Facebook\WebDriver\WebDriverSelectInterface;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\DomCrawler\Crawler;
use Symfony\Component\Panther\WebDriver\WebDriverCheckbox;

class PantherBugWorkaround
{
    /**
     * Used to work around the bug in Panther whereby it is unable to clear checkbox data, even when explicitly
     * mentioned in the data of a submitForm call.
     *
     * Explicitly deselect everything for checkboxes mentioned in the formSubmissionData
     */
    public static function clearMentionedMultiSelections(Crawler $submitButtonNode, array $formSubmissionData, Client $client): array
    {
        $form = $submitButtonNode->form(null);
        $formElement = $form->getElement();

        foreach($formSubmissionData as $k => $data) {
            $element = $formElement->findElement(WebDriverBy::name($k));

            if (
                strtolower($element->getTagName()) !== 'input' ||
                strtolower($element->getAttribute('type')) !== 'checkbox'
            ){
                continue;
            }

            $webDriverElement = self::getWebDriverSelect($element);

            if ($webDriverElement) {
                $webDriverElement->deselectAll();

                if ($data === '' || $data === false || $data === null) {
                    // Actually unset the data if we intended for it to be deselected...
                    unset($formSubmissionData[$k]);
                }
            }
        }

        return $formSubmissionData;
    }

    private static function getWebDriverSelect(WebDriverElement $element): ?WebDriverSelectInterface
    {
        $type = $element->getAttribute('type');

        $tagName = $element->getTagName();
        $select = 'select' === $tagName;

        if (!$select && ('input' !== $tagName || ('radio' !== $type && 'checkbox' !== $type))) {
            return null;
        }

        return $select ? new WebDriverSelect($element) : new WebDriverCheckbox($element);
    }
}