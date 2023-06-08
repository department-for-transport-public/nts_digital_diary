<?php

namespace App\Tests\Functional\Utility;

use App\Tests\DataFixtures\SurveysFixtures;
use App\Tests\Functional\AbstractFunctionalWebTestCase;
use App\Utility\Cleanup\EmailAddressPurgeUtility;
use App\Utility\Cleanup\SurveyPurgeUtility;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractPurgeTest extends AbstractFunctionalWebTestCase
{
    protected ?EmailAddressPurgeUtility $addressPurgeUtility;
    protected ?EntityManagerInterface $entityManager;
    protected ?SurveyPurgeUtility $surveyPurgeUtility;

    public function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([
            SurveysFixtures::class
        ]);

        $container = static::getContainer();
        $this->addressPurgeUtility = $container->get(EmailAddressPurgeUtility::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->surveyPurgeUtility = $container->get(SurveyPurgeUtility::class);
    }
}