<?php

namespace App\Tests\Messenger;

use App\Entity\PropertyChangeLog;
use App\Messenger\PropertyChange\MessageHandler;
use PHPUnit\Framework\TestCase;

class PropertyChangeRetentionTest extends TestCase
{
    public function dataKeeps(): array
    {
        $toCl = function(array $intSerialIdValues): array  {
            $count = 0;

            return array_map(function(?int $interviewerSerialId) use (&$count) {
                return (new PropertyChangeLog())
                    ->setEntityId($count++)
                    ->setInterviewerSerialId($interviewerSerialId);
            }, $intSerialIdValues);
        };

        // N.B. Arrays are in newest to oldest order
        return [
            // All DK: Keep most recent
            [
                $toCl([null, null, null, null]),
                [0],
            ],
            // All INT: Keep most recent
            [
                $toCl([1, 1, 1, 1]),
                [0],
            ],
            // INT + other: Keep most recent INT, DK
            [
                $toCl([1, null, null, null]),
                [0, 1],
            ],
            [
                $toCl([1, null, null, 1]),
                [0, 1],
            ],
            [
                $toCl([1, 1, null, 1]),
                [0, 2],
            ],
            [
                $toCl([1, null, 1, 1]),
                [0, 1],
            ],
            // DK + other: Keep most recent DK, INT, DK
            [
                $toCl([null, 1, 1, 1]),
                [0, 1],
            ],
            [
                $toCl([null, 1, 1, null]),
                [0, 1, 3],
            ],
            [
                $toCl([null, null, 1, null]),
                [0, 2, 3],
            ],
            [
                $toCl([null, null, 1, 1, null]),
                [0, 2, 4],
            ],
        ];
    }

    /**
     * @dataProvider dataKeeps
     */
    public function testKeeps(array $changeLogs, array $expectedKeepEntityIds): void
    {
        $keeps = MessageHandler::getKeepsForChangeLogs($changeLogs);
        $keepEntityIds = array_map(fn(PropertyChangeLog $cl) => $cl->getEntityId(), $keeps);
        $this->assertEquals(array_map(fn($x) => strval($x), $expectedKeepEntityIds), $keepEntityIds);
    }
}