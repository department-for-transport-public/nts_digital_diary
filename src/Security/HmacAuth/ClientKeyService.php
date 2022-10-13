<?php


namespace App\Security\HmacAuth;


use App\Entity\ApiUser;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Throwable;

class ClientKeyService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

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
        while(
            ($passcode = $this->generateRandomClientKey())
            && $repo->findBy(['key' => $passcode])
        ) {continue;};
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