<?php

namespace App\Tests\Functional\Wizard;

class SummaryTestCase
{
    /** @var array|SummaryRowTestCase[] */
    protected array $cases;

    public function __construct(array $cases)
    {
        $this->cases = $cases;
    }

    public function getCases(): array
    {
        return $this->cases;
    }
}