<?php

namespace App\Tests\Functional\Interviewer;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\Functional\AbstractWebTestCase;
use App\Utility\Test\CrawlerTableHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractInterviewerDiaryKeeperTest extends AbstractWebTestCase
{

    public function logInAsInterviewerAndDrillDownToUsersHouseholdPage(string $username): void
    {
        // Log in as an interviewer
        $this->client->request(Request::METHOD_GET, '/');
        $this->submitLoginForm('interviewer@example.com', 'password');
        $this->assertEquals('/interviewer', $this->client->getRequest()->getRequestUri());

        $this->client->clickLink('View archived areas');
        $this->assertEquals('/interviewer/areas-archived', $this->client->getRequest()->getRequestUri());

        // Fetch a particular user
        $entityManager = KernelTestCase::getContainer()->get(EntityManagerInterface::class);
        $userRepository = $entityManager->getRepository(User::class);

        $this->assertInstanceOf(UserRepository::class, $userRepository);
        $user = $userRepository->loadUserByIdentifier($username);

        // Drill down to find this user in areas > households
        $diaryKeeper = $user->getDiaryKeeper();
        $household = $diaryKeeper->getHousehold();
        $areaPeriod = $household->getAreaPeriod();

        $tableHelper = new CrawlerTableHelper($this->client->getCrawler());
        $viewAreaUrl = $tableHelper->getLinkUrlForRowMatching('View', [
            'Area ID' => "" . $areaPeriod->getArea(),
        ], false);
        $this->client->request(Request::METHOD_GET, $viewAreaUrl);

        $tableHelper = new CrawlerTableHelper($this->client->getCrawler());
        $viewHouseholdUrl = $tableHelper->getLinkUrlForRowMatching('View', [
            'Serial' => $areaPeriod->getArea() . ' / ' . str_pad($household->getAddressNumber(), 2, '0', STR_PAD_LEFT) . ' / ' . $household->getHouseholdNumber(),
        ], false);
        $this->client->request(Request::METHOD_GET, $viewHouseholdUrl);
    }

    protected function completePreAuthForm(?string $expectedUrl = null): void
    {
        $url = $expectedUrl ?? $this->client->getRequest()->getRequestUri();
        $this->client->submitForm('form_continue', []);
        $this->assertEquals($url, $this->client->getRequest()->getRequestUri());
    }
}