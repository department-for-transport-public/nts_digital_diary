<?php

namespace App\Doctrine\ORM\Filter;

use App\Entity\AreaPeriod;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

class TrainingAreaPeriodFilter extends SQLFilter
{
    const FILTER_NAME = "interviewer-training-area-period";

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if ($targetEntity->reflClass->getName() !== AreaPeriod::class) {
            return "";
        }
        return $targetTableAlias.'.training_interviewer_id IS NULL';
    }
}