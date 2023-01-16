<?php

namespace App\Tests\Functional\Api;

use App\Entity\AreaPeriod;
use App\Entity\User;
use App\Tests\DataFixtures\ApiFixtures;
use App\Tests\DataFixtures\ApiUserFixtures;
use Doctrine\ORM\EntityNotFoundException;

class InterviewersTest extends AbstractApiWebTestCase
{
    protected function setUp(): void
    {
        $this->initialiseClientAndLoadFixtures([ApiUserFixtures::class, ApiFixtures::class]);
        parent::setUp();
    }

    protected function assertMatchesUserInterviewer(User $user, array $data, array $options = []): void
    {
        $interviewer = $user->getInterviewer();

        if ($options['check_id'] ?? true) {
            self::assertEquals($interviewer->getId(), $data['id']);
        }

        self::assertEquals($interviewer->getName(), $data['name']);
        self::assertEquals($interviewer->getSerialId(), $data['serialId']);
        self::assertEquals($user->getUserIdentifier(), $data['email']);

        if ($options['check_area_periods'] ?? false) {
            $areaPeriods = array_map(fn(AreaPeriod $a) => $a->getId(), $interviewer->getAreaPeriods()->toArray());
            self::assertEqualsCanonicalizing($areaPeriods, $data['area_periods']);
        }
    }

    protected function getInterviewerUser(): User
    {
        $user = $this->getFixtureByReference('user:interviewer');
        self::assertInstanceOf(User::class, $user);
        self::assertNotNull($user->getInterviewer());

        return $user;
    }

    protected function getInterviewerUserBySerialId(int $serialId): User
    {
        $user = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->leftJoin('u.interviewer', 'i')
            ->where('i.serialId = :serialId')
            ->setParameter('serialId', $serialId)
            ->getQuery()
            ->getOneOrNullResult();

        self::assertInstanceOf(User::class, $user);
        return $user;
    }

    public function testCollectionGet()
    {
        $response = $this->makeSignedRequestAndGetResponse("/api/v1/interviewers");

        self::assertCount(1, $response);

        $user = $this->getInterviewerUser();
        $this->assertMatchesUserInterviewer($user, $response[0]);
    }

    public function dataCollectionGetFilters(): array
    {
        return [
            "Known email" => [['email' => 'interviewer@example.com'], true],
            "Known partial email" => [['email' => 'viewer'], true],
            "Unknown email" => [['email' => 'silly@example.com'], false],
            "Unknown partial email" => [['email' => 'silly'], false],
            "Serial ID" => [['serialId' => '101'], true],
            "Partial serial ID" => [['serialId' => '10'], false],
            "Area ID" => [fn(User $interviewerUser) => [
                'areaPeriods' => $interviewerUser
                    ->getInterviewer()
                    ->getAreaPeriods()
                    ->first()
                    ->getId(),
            ], true],
            "Partial Area ID" => [function(User $interviewerUser) {
                $id = $interviewerUser
                    ->getInterviewer()
                    ->getAreaPeriods()
                    ->first()
                    ->getId();
                return [
                    'areaPeriods' => substr($id, 0, strlen($id) - 1),
                ];
            }, false],
            "Known name" => [['name' => 'Test interviewer'], true],
            "Known partial name" => [['name' => 'view'], true],
            "Unknown name name" => [['name' => 'banana'], false],
        ];
    }

    /**
     * @dataProvider dataCollectionGetFilters
     */
    public function testCollectionGetFilters(array|\Closure $queryParams, bool $expectSuccess)
    {
        if ($queryParams instanceof \Closure) {
            $queryParams = $queryParams($this->getInterviewerUser());
        }

        $response = $this->makeSignedRequestAndGetResponse("/api/v1/interviewers", $queryParams);

        if ($expectSuccess) {
            self::assertCount(1, $response);

            $user = $this->getInterviewerUser();
            $this->assertMatchesUserInterviewer($user, $response[0]);
        } else {
            self::assertCount(0, $response);
        }
    }

    public function testGetSuccess()
    {
        $user = $this->getInterviewerUser();
        $interviewerId = $user->getInterviewer()->getId();

        $response = $this->makeSignedRequestAndGetResponse("/api/v1/interviewers/{$interviewerId}");
        $this->assertMatchesUserInterviewer($user, $response, ['check_area_periods' => true]);
    }

    public function testGetFail()
    {
        $interviewerId = $this->garbleId($this->getInterviewerUser()->getInterviewer()->getId());
        $this->makeSignedRequestAndGetResponse("/api/v1/interviewers/{$interviewerId}", [], ['expectedResponseCode' => 404]);
    }

    public function testDeleteSuccess()
    {
        $interviewerId = $this->getInterviewerUser()->getInterviewer()->getId();
        $this->makeSignedRequestAndGetResponse("/api/v1/interviewers/{$interviewerId}", [], ['method' => 'DELETE', 'expectedResponseCode' => 204]);

        // Check it's actually been deleted...
        $this->expectException(EntityNotFoundException::class);
        $this->getInterviewerUser();
    }

    public function testDeleteFail()
    {
        $interviewerId = $this->garbleId($this->getInterviewerUser()->getInterviewer()->getId());
        $this->makeSignedRequestAndGetResponse("/api/v1/interviewers/{$interviewerId}", [], ['method' => 'DELETE', 'expectedResponseCode' => 404]);
    }

    public function testPostSuccess()
    {
        $data = [
            'name' => 'Mark',
            'serialId' => 120,
            'email' => 'mark@example.com',
        ];

        $this->makeSignedRequestAndGetResponse("/api/v1/interviewers", [], ['method' => 'POST', 'expectedResponseCode' => 201], $data);

        // Check it's actually been created...
        $user = $this->getInterviewerUserBySerialId(120);
        $this->assertMatchesUserInterviewer($user, $data, ['check_id' => false]);
    }

    public function dataPostFail(): array
    {
        return [
            // Omitting fields
            [422, ['name' => 'Mark', 'serialId' => 120]],
            [422, ['name' => 'Mark', 'email' => 'mark@example.com']],
            [422, ['serialId' => 120, 'email' => 'mark@example.com']],

            // Invalid email
            [422, ['name' => 'Mark', 'serialId' => 120, 'email' => 'mark']],

            // Empty fields
            [422, ['name' => '', 'serialId' => 120, 'email' => 'mark@example.com']],
            [422, ['name' => 'Mark', 'serialId' => '', 'email' => 'mark@example.com']],
            [422, ['name' => 'Mark', 'serialId' => 120, 'email' => '']],
        ];
    }

    /**
     * @dataProvider dataPostFail
     */
    public function testPostFail(int $expectedResponseCode, array $data)
    {
        $this->makeSignedRequestAndGetResponse("/api/v1/interviewers", [], ['method' => 'POST', 'expectedResponseCode' => $expectedResponseCode], $data);
    }
}