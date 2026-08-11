<?php

namespace App\Tests\Functional\DiaryKeeper\Reflow;

use App\Entity\Journey\Method;
use App\Tests\DataFixtures\Reflow\StageFixtures;
use App\Tests\Functional\AbstractWizardTest;

class AddStageTest extends AbstractWizardTest
{
    public const TEST_USERNAME = 'diary-keeper-adult@example.com';

    public function dataProvider(): array
    {
        return [
            'Other transport method' => [AddStageTestBuilder::otherTests(self::TEST_USERNAME, 1, Method::TYPE_OTHER)],
            'Private transport method #1' => [AddStageTestBuilder::privateTests(self::TEST_USERNAME, 2, Method::TYPE_PRIVATE, 'Red Tesla', true)],
            'Private transport method #2' => [AddStageTestBuilder::privateTests(self::TEST_USERNAME, 4, Method::TYPE_PRIVATE, 'A-Team van', false)],
            'Public transport method' => [AddStageTestBuilder::publicTests(self::TEST_USERNAME, 7, true)],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testAddJourneyWizard(array $testData): void
    {
        $this->initialiseClientAndLoadFixtures([StageFixtures::class]);
        $this->loginUser(self::TEST_USERNAME);

        $this->client->request('GET', '/travel-diary/day-1');
        $this->clickLinkContaining('View');
        $this->clickLinkContaining('Add a stage');
        $basePath = preg_replace('#/add-stage(/.*)?$#', '', $this->getCurrentPath());
        $this->doWizardTest($testData, $basePath);
    }

    // To avoid the default code getting very confused and generating a test name that causes screenshots
    // to not be outputted upon error
    public function toString(): string
    {
        $class = new \ReflectionClass($this);
        return sprintf(
            '%s::%s',
            $class->name,
            $this->getName(false)
        );
    }
}