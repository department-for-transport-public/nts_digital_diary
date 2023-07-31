<?php

namespace App\Tests\Functional\DiaryKeeper;

use App\Entity\DiaryKeeper;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Method;
use App\Tests\DataFixtures\TestSpecific\ShareJourneyTestFixtures;
use App\Tests\Functional\AbstractProceduralWizardTest;
use App\Tests\Functional\Wizard\Form\FormTestCase;
use App\Tests\Functional\Wizard\Action\PathTestAction;
use Facebook\WebDriver\WebDriverBy;

class ShareJourneyTest extends AbstractProceduralWizardTest
{
    const TEST_USERNAME = 'diary-keeper-adult@example.com';

    protected function performTest(string $journeyFixtureRef, bool $isGoingHome): void
    {
        $journeyFixture = $this->getFixtureByReference($journeyFixtureRef);
        $this->assertInstanceOf(Journey::class, $journeyFixture);

        $url = fn(string $pathEnd) => '#^\/travel-diary\/journey\/[0-9A-Z]+' . $pathEnd . '$#';
        $options = [PathTestAction::OPTION_EXPECTED_PATH_REGEX => true];

        $this->formTestAction(
            $url('/share-journey/introduction'),
            'form_button_group_continue',
            [
                new FormTestCase([]),
            ],
            $options
        );

        $sharingDK = $journeyFixture->getDiaryDay()->getDiaryKeeper();
        $allProxiedDKs = $sharingDK->getActingAsProxyFor();
        $allNonProxiedDKs = $sharingDK->getHousehold()->getDiaryKeepers()->filter(fn(DiaryKeeper $dk) => !$sharingDK->isActingAsProxyFor($dk) && $dk->getId() !== $sharingDK->getId());
        $excludeDk = $this->getFixtureByReference('diary-keeper:journey-share:adult');
        $shareToDKs = $allProxiedDKs->filter(fn($dk) => $dk->getId() !== $excludeDk->getId());

        $this->assertCount(3, $allProxiedDKs);
        $this->assertCount(2, $allNonProxiedDKs);
        $this->assertCount(2, $shareToDKs);

        $this->formTestAction(
            $url('/share-journey/who'),
            'share_to_button_group_continue',
            [
                new FormTestCase([], ["#share_to"]),
                // try selecting someone who isn't proxied... should be disabled
                new FormTestCase(['share_to[shareTo][]' => $allNonProxiedDKs->first()->getId()], ['#share_to']),
                // select too many people with respect to maximum allowed by source journey
                new FormTestCase(['share_to[shareTo][]' => $allProxiedDKs->map(fn($dk) => $dk->getId())->toArray()], ['#share_to_shareTo']),
                new FormTestCase(['share_to[shareTo][]' => $shareToDKs->map(fn($dk) => $dk->getId())->toArray()]),
            ],
            $options
        );

//        dump(array_map(fn ($e) => $e->getAttribute('name'), $this->context->getClient()->findElements(WebDriverBy::xpath('//div[@id="purpose_collection"]/div/div/div/input'))));
//        exit(1);

        if ($isGoingHome) {
            $purposeTests = [new FormTestCase([])]; // Purpose is pre-filled if the source journey is "going home"
        } else {
            $purposeTests = [
                new FormTestCase([], ['#purpose_collection_0_purpose', '#purpose_collection_1_purpose']),
                new FormTestCase(['purpose_collection[0][purpose]' => 'shared purpose 0'], ['#purpose_collection_1_purpose']),
                new FormTestCase(['purpose_collection[1][purpose]' => 'shared purpose 1'], ['#purpose_collection_0_purpose']),
                new FormTestCase([
                    'purpose_collection[0][purpose]' => 'shared purpose 0',
                    'purpose_collection[1][purpose]' => 'shared purpose 1',
                ], []),
            ];
        }

        $this->formTestAction(
            $url('/share-journey/purposes'),
            'purpose_collection_button_group_continue',
            $purposeTests,
            $options
        );

        foreach($journeyFixture->getStages() as $stage) {
            switch ($stage->getMethod()->getType()) {
                case Method::TYPE_OTHER :
                    $this->context->getTestCase()->assertPathNotMatches($url('/share-journey/stage-details/'.$stage->getNumber()), true, 'Landed on stage details for other stage type');
                    break;

                case Method::TYPE_PRIVATE :
                    $this->formTestAction(
                        $url('/share-journey/stage-details/'.$stage->getNumber()),
                        "stage_details_collection_button_group_continue",
                        match ($stage->getNumber()) {
                            // source stage is driver
                            3 => [
                                // isDriver values should be ignored, as they are disabled
                                new FormTestCase([
                                    'stage_details_collection[0][isDriver]' => '',
                                    'stage_details_collection[1][isDriver]' => '',
                                    'stage_details_collection[1][parkingCost][hasCost]' => 'true',
                                    'stage_details_collection[1][parkingCost][cost]' => '3.45',
                                ], [
                                    '#stage_details_collection_0_parkingCost_hasCost',
                                ]),
                                new FormTestCase([
                                    'stage_details_collection[0][parkingCost][hasCost]' => 'false',
                                    'stage_details_collection[1][parkingCost][hasCost]' => 'true',
                                    'stage_details_collection[1][parkingCost][cost]' => '3.45',
                                ]),
                            ],
                            4 => [
                                // maximum 1 driver
                                new FormTestCase([
                                    'stage_details_collection[0][isDriver]' => 'true',
                                    'stage_details_collection[0][parkingCost][hasCost]' => 'false',
                                    'stage_details_collection[1][isDriver]' => 'true',
                                    'stage_details_collection[1][parkingCost][hasCost]' => 'false',
                                ], [
                                    '#stage_details_collection_0_isDriver',
                                    '#stage_details_collection_1_isDriver',
                                ]),
                                // zero drivers allowed
                                new FormTestCase([
                                    'stage_details_collection[0][isDriver]' => 'false',
                                    'stage_details_collection[0][parkingCost][hasCost]' => 'true',
                                    'stage_details_collection[0][parkingCost][cost]' => '3.45',
                                    'stage_details_collection[1][isDriver]' => 'false',
                                    'stage_details_collection[1][parkingCost][hasCost]' => 'false',
                                ]),
                            ],
                        },
                        $options
                    );

                    break;

                case Method::TYPE_PUBLIC :
                    $this->formTestAction(
                        $url('/share-journey/stage-details/'.$stage->getNumber()),
                        "stage_details_collection_button_group_continue",
                        [
                            new FormTestCase([
                                'stage_details_collection[0][ticketType]' => '',
                                'stage_details_collection[0][ticketCost][hasCost]' => 'true',
                                'stage_details_collection[0][ticketCost][cost]' => '',
                                'stage_details_collection[1][ticketCost][hasCost]' => 'false',
                            ], [
                                '#stage_details_collection_0_ticketType',
                            ]),
                            new FormTestCase([
                                'stage_details_collection[0][ticketType]' => 'ticket type 0',
                                'stage_details_collection[0][ticketCost][hasCost]' => 'true',
                                'stage_details_collection[0][ticketCost][cost]' => '1.23',
                                'stage_details_collection[1][ticketCost][hasCost]' => 'false',
                            ]),
                        ],
                        $options
                    );
                    break;
            }
        }

        $this->pathTestAction('#^\/travel-diary\/journey\/[A-Z0-9]+$#', [
            PathTestAction::OPTION_EXPECTED_PATH_REGEX => true
        ]);

        $dkNameList = join(', ', $shareToDKs->map(fn ($dk) => $dk->getName())->toArray());
        $tag = $this->context->getClient()->findElement(WebDriverBy::xpath('//main[@id="main-content"]/strong[contains(concat(" ",normalize-space(@class)," ")," govuk-tag ")]'));
        $this->context->getTestCase()->assertEqualsIgnoringCase("journey shared with {$dkNameList}", $tag->getText());

        $this->context->getTestCase()->clickLinkContaining('Delete this journey');

        $this->formTestAction(
            $url('/delete'),
            'confirm_action_button_group_confirm',
            [
                new FormTestCase([]),
            ],
            $options
        );

        $this->pathTestAction('/travel-diary/day-6');
    }

    public function wizardData(): array
    {
        return
            [
                'Day 6 journey to non-home' => ['sharing-journey:1', 0, false],
                'Day 6 journey to home' => ['sharing-journey:2', 1, true],
            ];
    }

    /**
     * @dataProvider wizardData
     */
    public function testShareJourneyWizard(string $journeyFixtureRef, int $journeyIndex, bool $isGoingHome)
    {
        $this->initialiseClientAndLoadFixtures([ShareJourneyTestFixtures::class]);
        $this->loginUser(self::TEST_USERNAME);
        $this->client->request('GET', '/travel-diary/day-6');
        $this->clickLinkContaining('View', $journeyIndex);
        $this->clickLinkContaining('Share this journey');

        $this->context = $this->createContext('');
        $this->performTest($journeyFixtureRef, $isGoingHome);
    }
}