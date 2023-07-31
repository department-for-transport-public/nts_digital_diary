<?php

namespace App\Tests\Serializer\ChangeSet;

use App\Serializer\ChangeSet\JourneyChangeSetNormalizer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class JourneyNormalizerTest extends KernelTestCase
{
    protected JourneyChangeSetNormalizer $journeyChangeSetNormalizer;

    public function setUp(): void
    {
        parent::setUp();

        static::bootKernel();
        $this->journeyChangeSetNormalizer = static::getContainer()->get(JourneyChangeSetNormalizer::class);
    }

    public function dataFieldChanges(): array
    {
        return [
            // Test unrelated fields
            [
                ['modifiedAt' => [0,1], 'nodifiedAt' => [0,1]],
                [],
            ],

            // Test removals
            [
                ['startLocation' => ['Banana', 'Custard'], 'nodifiedAt' => [0,1]],
                ['startLocation' => ['Banana', 'Custard']],
            ],
            [
                ['endLocation' => ['Banana', 'Custard'], 'nodifiedAt' => [0,1]],
                ['endLocation' => ['Banana', 'Custard']],
            ],
            [
                ['purpose' => ['Banana', 'Custard'], 'nodifiedAt' => [0,1]],
                ['purpose' => ['Banana', 'Custard']],
            ],

            // Test start/end times
            [
                ['nodifiedAt' => [0,1], 'startTime' => [null, new \DateTime('2:03am')]],
                ['startTime' => [null, '02:03']],
            ],
            [
                ['nodifiedAt' => [0,1], 'startTime' => [null, new \DateTime('2:56pm')]],
                ['startTime' => [null, '14:56']],
            ],
            [
                ['nodifiedAt' => [0,1], 'endTime' => [null, new \DateTime('2:03am')]],
                ['endTime' => [null, '02:03']],
            ],
            [
                ['nodifiedAt' => [0,1], 'endTime' => [null, new \DateTime('2:56pm')]],
                ['endTime' => [null, '14:56']],
            ],
            [
                ['zodifiedAt' => [0,1], 'startTime' => [new \DateTime('2:03am'), new \DateTime('4:23am')]],
                ['startTime' => ['02:03', '04:23']],
            ],
            [
                ['zodifiedAt' => [0,1], 'startTime' => [new \DateTime('2:56pm'), new \DateTime('8:56pm')]],
                ['startTime' => ['14:56', '20:56']],
            ],
            [
                ['zodifiedAt' => [0,1], 'endTime' => [new \DateTime('2:03am'), new \DateTime('4:23am')]],
                ['endTime' => ['02:03', '04:23']],
            ],
            [
                ['zodifiedAt' => [0,1], 'endTime' => [new \DateTime('2:56pm'), new \DateTime('8:56pm')]],
                ['endTime' => ['14:56', '20:56']],
            ],

            // Test renames
            [
                ['zodifiedAt' => [0,1], 'isStartHome' => [true, false]],
                ['startIsHome' => [true, false]],
            ],
            [
                ['zodifiedAt' => [0,1], 'isEndHome' => [true, false]],
                ['endIsHome' => [true, false]],
            ],
        ];
    }

    /**
     * @dataProvider dataFieldChanges
     */
    public function testFieldChanges(array $input, array $expectedOutput): void
    {
        $this->assertEqualsCanonicalizing($expectedOutput, $this->journeyChangeSetNormalizer->normalize($input));
    }
}