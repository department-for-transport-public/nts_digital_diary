<?php

namespace App\Serializer\ExportApi;

use App\Entity\Journey\Method;
use Doctrine\ORM\EntityManagerInterface;

class HistoryValueResolver
{
    protected EntityManagerInterface $entityManager;
    protected array $entities;

    // Service is lazy to avoid early database calls which were upsetting cloud build deployment
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;

        $map = [
            'method' => Method::class,
        ];

        foreach($map as $key => $entityClass) {
            $this->entities[$key] = $this->entityManager
                ->getRepository($entityClass)
                ->createQueryBuilder('e', 'e.id')
                ->getQuery()
                ->execute();
        }
    }

    public function resolve(string $propertyName, ?string $value) {
        $booleanProperties = ['isDriver', 'isEndHome', 'isStartHome', 'isTicketAdult', 'isTicketConcessionary', 'isTicketReturn'];

        if (in_array($propertyName, $booleanProperties) && $value !== null) {
            return boolval($value);
        }

        if (isset($this->entities[$propertyName])) {
            return $this->entities[$propertyName][$value] ?? null;
        }

        return $value;
    }
}