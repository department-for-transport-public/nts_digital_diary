<?php


namespace App\Tests\Functional\Cron;

use App\Tests\Functional\AbstractFunctionalWebTestCase;

class RequestConditionsTest extends AbstractFunctionalWebTestCase
{
    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([]);
        parent::setUp();
    }

    public function cronUrlsProvider(): array
    {
        return [
            ['/cron/test'],
        ];
    }

    /**
     * @dataProvider cronUrlsProvider
     */
    public function testConditionsNotMet($url)
    {
        $this->client->request('GET', "//localhost{$url}");
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', "//localhost{$url}", [], [], [
            'HTTP_X_Appengine_Cron' => 'false',
        ]);
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', "//localhost{$url}", [], [], [
            'HTTP_X_Cloudscheduler' => 'false',
        ]);
        self::assertResponseStatusCodeSame(404);
    }

    /**
     * @dataProvider cronUrlsProvider
     */
    public function testConditionsMet($url)
    {
        $this->client->request('GET', "//localhost{$url}", [], [], [
            'HTTP_X_Appengine_Cron' => 'true',
        ]);
        self::assertResponseStatusCodeSame(200);

        $this->client->request('GET', "//localhost{$url}", [], [], [
            'HTTP_X_Cloudscheduler' => 'true',
        ]);
        self::assertResponseStatusCodeSame(200);

        $this->client->request('GET', "//localhost{$url}", [], [], [
            'HTTP_X_Appengine_Cron' => 'true',
            'HTTP_X_Cloudscheduler' => 'true',
        ]);
        self::assertResponseStatusCodeSame(200);
    }

}