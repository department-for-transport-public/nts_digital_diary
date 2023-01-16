<?php

namespace App\Tests\Functional\DiaryKeeper;

use App\Tests\DataFixtures\StageFixtures;
use App\Tests\Functional\Wizard\Action\CallbackAction;
use App\Tests\Functional\Wizard\Action\Context;
use App\Tests\Functional\Wizard\Form\CallbackFormTestCase;
use App\Tests\Functional\Wizard\Form\CallbackIncludingErrorsFormTestCase;
use App\Tests\Functional\Wizard\Form\FormTestCase;
use App\Tests\Functional\Wizard\Action\PathTestAction;
use App\Tests\Functional\Wizard\Action\FormTestAction;
use Facebook\WebDriver\WebDriverBy;

class ShareJourneyTest extends AbstractJourneyTest
{
    const TEST_USERNAME = 'diary-keeper-adult@example.com';

    protected function generateTest(bool $overwriteStageDetails, int $numberOfStages): array
    {
        $url = fn(string $pathEnd) => '#^\/travel-diary\/journey\/[0-9A-Z]+' . $pathEnd . '$#';
        $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

        $tests[] = new FormTestAction(
            $url('/share-journey/introduction'),
            'form_button_group_continue',
            [
                new FormTestCase([]),
            ],
            $options
        );

        $chooseNthShareButton = function(int $n): \Closure {
            return function(Context $context) use ($n): array {
                $input = $context->getClient()->findElement(WebDriverBy::xpath('//div[@id="share_to_shareTo"]/div['.($n).']/input'));
                return ['share_to[shareTo][]' => $input->getAttribute('value')];
            };
        };

        $tests[] = new FormTestAction(
            $url('/share-journey/who'),
            'share_to_button_group_continue',
            [
                new FormTestCase([], ["#share_to"]),
                // Although it won't be possible to choose this button, since it is disabled, this will serve as a
                // good test that it *is* actually disabled
                new CallbackFormTestCase($chooseNthShareButton(1), ['#share_to']),
                new CallbackFormTestCase($chooseNthShareButton(2))
            ],
            $options
        );

        $setNthPurposeTo = function(int $n, string $purpose): \Closure {
            return function(Context $context) use ($n, $purpose): array {
                $input = $context->getClient()->findElement(WebDriverBy::xpath('//div[@id="purposes"]/div['.($n).']/input'));
                return [$input->getAttribute('name') => $purpose];
            };
        };

        $expectedErrorForNthPurpose = function(int $n): \Closure {
            return function (Context $context) use ($n): array {
                $input = $context->getClient()->findElement(WebDriverBy::xpath('//div[@id="purposes"]/div['.($n).']/input'));
                return ['#'.$input->getAttribute('id')];
            };
        };

        $tests[] = new FormTestAction(
            $url('/share-journey/purposes'),
            'purposes_button_group_continue',
            [
                new CallbackIncludingErrorsFormTestCase($setNthPurposeTo(1, ''), $expectedErrorForNthPurpose(1)),
                new CallbackFormTestCase($setNthPurposeTo(1, 'shared purpose')),
            ],
            $options
        );

        for($i=1; $i<=$numberOfStages; $i++) {
            $tests[] = new FormTestAction(
                $url('/share-journey/stage-details/'.$i),
                "stage_details_button_group_continue",
                [
                    new FormTestCase([]),
                ],
                $options
            );
        }

        $tests[] = new PathTestAction('#^\/travel-diary\/journey\/[A-Z0-9]+$#', [
            PathTestAction::OPTION_EXPECTED_PATH_REGEX => true
        ]);

        $tests[] = new CallbackAction(function(Context $context){
            $tag = $context->getClient()->findElement(WebDriverBy::xpath('//main[@id="main-content"]/strong[contains(concat(" ",normalize-space(@class)," ")," govuk-tag ")]'));
            $context->getTestCase()->assertEqualsIgnoringCase("journey shared with test diary keeper (proxied)", $tag->getText());
        });

        $tests[] = new CallbackAction(function(Context $context) {
            $context->getTestCase()->clickLinkContaining('Delete this journey');
        });

        $tests[] = new FormTestAction(
            $url('/delete'),
            'confirm_action_button_group_confirm',
            [
                new FormTestCase([]),
            ],
            $options
        );

        $tests[] = new PathTestAction('/travel-diary/day-7');

        return [$tests];
    }

    public function wizardData(): array
    {
        return
            [
                'Share' => $this->generateTest(false, 1),
            ];
    }

    /**
     * @dataProvider wizardData
     */
    public function testShareJourneyWizard(array $wizardData)
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->loginUser(self::TEST_USERNAME);
        $this->client->request('GET', '/travel-diary/day-7');
        $this->clickLinkContaining('View');
        $this->clickLinkContaining('Share this journey');

        $this->doWizardTest($wizardData);
    }
}