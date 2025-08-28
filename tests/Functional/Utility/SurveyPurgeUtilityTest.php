<?php

namespace App\Tests\Functional\Utility;

class SurveyPurgeUtilityTest extends AbstractPurgeTest
{
    protected const REF_TYPE_ALL = 'all';
    protected const REF_TYPE_HOUSEHOLD_ONLY = 'household-only';
    protected const REF_TYPE_AREA_PERIOD_ONLY = 'area-period-only';

    public function dataPrePurge(): array
    {
        return [
            ['now', self::REF_TYPE_ALL, true],
            ['199-days-ago', self::REF_TYPE_ALL, true],
            ['199-days-ago', self::REF_TYPE_AREA_PERIOD_ONLY, true],
            ['201-days-ago', self::REF_TYPE_ALL, true],
            ['201-days-ago', self::REF_TYPE_AREA_PERIOD_ONLY, true],
            ['300-days-ago', self::REF_TYPE_ALL, true],
            ['300-days-ago:training', self::REF_TYPE_HOUSEHOLD_ONLY, true],
        ];
    }

    /** @dataProvider dataPrePurge */
    public function testPrePurge(string $dateString, string $referenceType, bool $expectedToBePresent): void {
        $this->assertReferencesForDateString($dateString, $referenceType, $expectedToBePresent);
    }

    public function dataPostPurge(): array
    {
        return [
            ['now', self::REF_TYPE_ALL, true],
            ['199-days-ago', self::REF_TYPE_ALL, true],
            ['199-days-ago', self::REF_TYPE_AREA_PERIOD_ONLY, true],
            ['201-days-ago', self::REF_TYPE_ALL, false],
            ['201-days-ago', self::REF_TYPE_AREA_PERIOD_ONLY, false],
            ['300-days-ago', self::REF_TYPE_ALL, false],
            ['300-days-ago:training', self::REF_TYPE_HOUSEHOLD_ONLY, true],
        ];
    }

    /** @dataProvider dataPostPurge */
    public function testPostPurge(string $dateString, string $referenceType, bool $expectedToBePresent): void {
        $this->addressPurgeUtility->purgeOldEmailAddresses();
        $this->surveyPurgeUtility->purgeOldSurveys();
        $this->entityManager->clear();
        $this->assertReferencesForDateString($dateString, $referenceType, $expectedToBePresent);
    }


    protected function assertReferencesForDateString(string $dateString, string $referenceType, bool $expectedToBePresent): void
    {
        $references = [];
        foreach($this->fixtureReferenceRepository->getReferencesByClass() as $classReferences) {
            $references = array_merge($references, $classReferences);
        }

        // A dateString (e.g. "199-days-ago") can be mapped to a whole host of reference names that we created in the
        // fixtures (e.g. "surveys:199-days-ago:journey/1:stage/2").

        // For each of them, we grab the reference's ID, attempt to load it from the database and then check whether its
        // existence (or lack thereof) matches our expectations.
        foreach ($this->getAllReferenceStrings($dateString, $referenceType) as $referenceName) {
            $reference = $references[$referenceName];
            $id = $reference->getId();

            $isPresent = $this->entityManager->find($reference::class, $id) !== null;

            $this->assertEquals($expectedToBePresent, $isPresent, ($expectedToBePresent ? "Missing" : "Unexpected") . " reference: {$referenceName}");
        }
    }

    /**
     * @return array<string>
     */
    protected function getAllReferenceStrings(string $dateString, string $references): array
    {
        return match($references){
            self::REF_TYPE_ALL => [
                "surveys:{$dateString}:diary-keeper",
                "surveys:{$dateString}:user",
                "surveys:{$dateString}:household",
                "surveys:{$dateString}:household-2",
                "surveys:{$dateString}:journey:1",
                "surveys:{$dateString}:journey:1/stage:1",
                "surveys:{$dateString}:journey:2",
                "surveys:{$dateString}:journey:2/stage:1",
                "surveys:{$dateString}:journey:2/stage:2",
            ],
            self::REF_TYPE_AREA_PERIOD_ONLY => [
                "surveys:{$dateString}:area-period",
            ],
            self::REF_TYPE_HOUSEHOLD_ONLY => [
                "surveys:{$dateString}:household",
            ],
};
    }
}