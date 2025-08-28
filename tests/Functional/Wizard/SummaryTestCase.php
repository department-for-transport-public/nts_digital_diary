<?php

namespace App\Tests\Functional\Wizard;

class SummaryTestCase
{
    public function __construct(
        /** @var array|SummaryRowTestCase[] */
        protected array $cases
    )
    {
    }

    public function getCases(): array
    {
        return $this->cases;
    }
}