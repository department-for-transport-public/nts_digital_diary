<?php

namespace App\Tests\Functional\DiaryKeeper;

use App\Entity\Journey\Journey;
use App\Tests\DataFixtures\StageFixtures;
use App\Tests\Functional\AbstractFunctionalWebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class StageReorderTest extends AbstractFunctionalWebTestCase
{
    private const string TEST_USERNAME = 'diary-keeper-adult@example.com';

    /**
     * Journey:2 in the fixtures has two stages numbered 1 (bus-or-coach) and
     * 2 (walk). This test uses the reorder-stages page with mapping=2,1 to
     * swap them and confirms the change is persisted.
     */
    public function testCanReorderStages(): void
    {
        $journey = $this->setUpAndGetJourney();
        $url = "/travel-diary/journey/{$journey->getId()}/reorder-stages?mapping=2,1";

        // GET the preview page (which carries the CSRF-bearing confirm form)
        $this->client->request(Request::METHOD_GET, $url);
        $this->assertResponseIsSuccessful();

        // Submit the confirm form — its action is the current URL, so the
        // mapping query param is re-applied on POST and then flushed
        $this->client->submitForm('confirm_action_button_group_confirm');

        // Journey view redirect indicates the reorder was accepted
        $this->assertResponseRedirects("/travel-diary/journey/{$journey->getId()}");

        $this->assertStageOrder($journey->getId(), [
            'walk' => 1,
            'bus-or-coach' => 2,
        ]);
    }

    /**
     * ReorderUtils::isMappingValid rejects mappings with out-of-range values.
     * A bogus mapping should be a safe no-op: original order preserved.
     *
     * @dataProvider invalidMappings
     */
    public function testInvalidMappingLeavesOrderUnchanged(string $mapping): void
    {
        $journey = $this->setUpAndGetJourney();
        $url = "/travel-diary/journey/{$journey->getId()}/reorder-stages?mapping={$mapping}";

        $this->client->request(Request::METHOD_GET, $url);
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('confirm_action_button_group_confirm');
        $this->assertResponseRedirects("/travel-diary/journey/{$journey->getId()}");

        $this->assertStageOrder($journey->getId(), [
            'bus-or-coach' => 1,
            'walk' => 2,
        ]);
    }

    public static function invalidMappings(): array
    {
        return [
            'out of range' => ['9,9'],
            'duplicate' => ['1,1'],
            'wrong length' => ['1,2,3'],
            'negative' => ['-1,2'],
            'garbage' => ['not-a-mapping'],
            'empty' => [''],
        ];
    }

    private function setUpAndGetJourney(): Journey
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->submitLoginForm(self::TEST_USERNAME, 'password');

        $journey = $this->fixtureReferenceRepository->getReference('journey:2', Journey::class);
        // Confirm starting state
        $this->assertCount(2, $journey->getStages());
        return $journey;
    }

    /**
     * @param array<string, int> $expected map of method description key → expected stage number
     */
    private function assertStageOrder(string $journeyId, array $expected): void
    {
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        /** @var Journey $fresh */
        $fresh = $em->find(Journey::class, $journeyId);

        $actual = [];
        foreach ($fresh->getStages() as $stage) {
            $actual[$stage->getMethod()->getDescriptionTranslationKey()] = $stage->getNumber();
        }
        $this->assertEquals($expected, $actual);
    }
}
