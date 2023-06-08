<?php

namespace App\Tests\Functional\Utility;

class SurveyPurgeUtilityTest extends AbstractPurgeTest
{
    public function dataPrePurge(): array
    {
        return [
            ['now', true],
            ['199-days-ago', true],
            ['201-days-ago', true],
            ['300-days-ago', true],
        ];
    }

    /** @dataProvider dataPrePurge */
    public function testPrePurge(string $dateString, bool $expectedToBePresent): void {
        $this->assertReferencesForDateString($dateString, $expectedToBePresent);
    }

    public function dataPostPurge(): array
    {
        return [
            ['now', true],
            ['199-days-ago', true],
            ['201-days-ago', false],
            ['300-days-ago', false],
        ];
    }

    /** @dataProvider dataPostPurge */
    public function testPostPurge(string $dateString, bool $expectedToBePresent): void {
        $this->surveyPurgeUtility->purgeOldSurveys();
        $this->assertReferencesForDateString($dateString, $expectedToBePresent);
    }


    protected function assertReferencesForDateString(string $dateString, bool $expectedToBePresent): void
    {
        $references = $this->fixtureReferenceRepository->getReferences();

        // A dateString (e.g. "199-days-ago") can be mapped to a whole host of reference names that we created in the
        // fixtures (e.g. "surveys:199-days-ago:journey/1:stage/2").

        // For each of them, we grab the reference's ID, attempt to load it from the database and then check whether its
        // existence (or lack thereof) matches our expectations.
        foreach ($this->getAllReferenceStrings($dateString) as $referenceName) {
            $reference = $references[$referenceName];
            $id = $reference->getId();

            $isPresent = $this->entityManager->find(get_class($reference), $id) !== null;

            $this->assertEquals($expectedToBePresent, $isPresent, ($expectedToBePresent ? "Missing" : "Unexpected") . " reference: {$referenceName}");
        }
    }

    /**
     * @return array<string>
     */
    protected function getAllReferenceStrings(string $dateString): array
    {
        return [
            "surveys:{$dateString}:diary-keeper",
            "surveys:{$dateString}:user",
            "surveys:{$dateString}:household",
            "surveys:{$dateString}:journey:1",
            "surveys:{$dateString}:journey:1/stage:1",
            "surveys:{$dateString}:journey:2",
            "surveys:{$dateString}:journey:2/stage:1",
            "surveys:{$dateString}:journey:2/stage:2",
        ];
    }
}