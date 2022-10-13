<?php

namespace App\DataFixtures;

use App\Entity\AreaPeriod;
use App\Entity\DiaryDay;
use App\Entity\DiaryKeeper;
use App\Entity\Distance;
use App\Entity\Household;
use App\Entity\Interviewer;
use App\Entity\Journey\Journey;
use App\Entity\Journey\Method;
use App\Entity\Journey\Stage;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Features;
use DateInterval;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker;
use RuntimeException;
use function PHPUnit\Framework\assertSame;

class AccessibilityFixtures extends AbstractTestFixtures
{
    public static function getGroups(): array
    {
        return ["accessibility"];
    }

    protected ?string $password = 'dac-test';

    public function load(ObjectManager $manager)
    {
        if (!Features::isEnabled(Features::ACCESSIBILITY_FIXTURES)) {
            echo "Skipping accessibility fixtures - disabled by Features\n";
            return;
        }

        $this->methods = $manager->getRepository(Method::class)->findAll();

        $diaries = [
            [220500, 1, '2022-05-03', 'tom@ghostlimited.com', 'Tom'],
            [220500, 2, '2022-05-03', 'david@ghostlimited.com', 'David'],
            [220500, 3, '2022-05-03', 'nicola@ghostlimited.com', 'Nic'],
            [220500, 4, '2022-05-03', 'mark@ghostlimited.com', 'Mark'],
            [220500, 5, '2022-05-03', 'john@ghostlimited.com', 'John'],
            [220501, 1, '2022-05-03', 'matthew.jenkins@digitalaccessibilitycentre.org', 'Matthew J'],
            [220501, 2, '2022-05-03', 'Matthew.Morgan@digitalaccessibilitycentre.org', 'Matthew M'],
            [220501, 3, '2022-05-03', 'Deborah.Roberts@digitalaccessibilitycentre.org', 'Deborah R'],
            [220501, 4, '2022-05-03', 'mike.jones@digitalaccessibilitycentre.org', 'Mike J'],
            [220501, 5, '2022-05-03', 'Kane.Haywood-Rogers@digitalaccessibilitycentre.org', 'Kane HR'],
            [220501, 6, '2022-05-03', 'Lucy.Jeffreys@digitalaccessibilitycentre.org', 'Lucy J'],
            [220501, 7, '2022-05-03', 'Sian.Box@digitalaccessibilitycentre.org', 'Sian B'],
        ];

        foreach ($diaries as $diary)
            $this->persistAll($manager, $this->createTestDiary(...$diary));

        $manager->persist($this->createInterviewer('Tom', 'dac-test-interviewer@example.com', '1000A', [220500, 220501]));

        $manager->flush();
    }
}
