<?php


namespace App\Security\HmacAuth;


use App\Entity\ApiUser;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Throwable;

class ClientKeyService
{
    public function __construct(protected readonly EntityManagerInterface $entityManager)
    {}

    public function createClientKey(): ApiUser
    {
        $apiUser = (new ApiUser())
            ->setKey($this->generateUniqueClientKey());
        $this->updateNonce($apiUser, false);
        $this->entityManager->persist($apiUser);
        $this->entityManager->flush();
        return $apiUser;
    }

    public function updateNonce(ApiUser $apiUser, bool $flushEntityManager = true): void
    {
        $apiUser->setNonce(time());
        if ($flushEntityManager) {
            $this->entityManager->flush();
        }
    }

    protected function generateUniqueClientKey(): string
    {
        $repo = $this->entityManager->getRepository(ApiUser::class);

        do {
            $passcode = $this->generateRandomClientKey();
        } while($repo->findBy(['key' => $passcode]));

        return $passcode;
    }

    protected function generateRandomClientKey(): string
    {
        try {
            return base64_encode(random_bytes(9));
        } catch (Throwable $e) {
            throw new RuntimeException("failed to create random bytes", 0, $e);
        }
    }
}