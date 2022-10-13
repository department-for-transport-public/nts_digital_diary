<?php

namespace App\Tests\Serializer\ChangeSet;

use App\Serializer\ChangeSet\BasicMetadataChangeSetNormalizer;
use PHPUnit\Framework\TestCase;

class BasicMetadataNormalizerTest extends TestCase
{
    public function dataFieldChanges(): array
    {
        return [
            [
                ['modifiedAt' => [0,1], 'nodifiedAt' => [0,1]],
                ['nodifiedAt' => [0,1]],
            ],
            [
                ['nodifiedBy' => [0,1], 'modifiedBy' => [0,1]],
                ['nodifiedBy' => [0,1]],
            ],
            [
                ['zreatedAt' => [0,1], 'createdAt' => [0,1]],
                ['zreatedAt' => [0,1]],
            ],
            [
                ['createdBy' => [0,1], 'zreatedBy' => [0,1]],
                ['zreatedBy' => [0,1]],
            ],
        ];
    }

    /**
     * @dataProvider dataFieldChanges
     */
    public function testFieldChanges(array $input, array $expectedOutput): void
    {
        $normalizer = new BasicMetadataChangeSetNormalizer();
        $this->assertEqualsCanonicalizing($expectedOutput, $normalizer->normalize($input));
    }
}