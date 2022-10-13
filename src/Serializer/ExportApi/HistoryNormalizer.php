<?php

namespace App\Serializer\ExportApi;

use App\Entity\DiaryKeeper;
use App\Repository\PropertyChangeLogRepository;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class HistoryNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    protected PropertyChangeLogRepository $changeLogRepository;
    protected HistoryValueResolver $historyValueResolver;

    use NormalizerAwareTrait;

    public function __construct(PropertyChangeLogRepository $changeLogRepository, HistoryValueResolver $historyValueResolver)
    {
        $this->changeLogRepository = $changeLogRepository;
        $this->historyValueResolver = $historyValueResolver;
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        // N.B. API Version is not relevant as this representation is purely for storing values in the database
        //      (i.e. in the PropertyChangeLog)
        return $data instanceof DiaryKeeper &&
            ($context['history'] ?? null) === true;
    }

    /**
     * @param DiaryKeeper $object
     * @throws ExceptionInterface
     */
    public function normalize($object, string $format = null, array $context = [])
    {
        $changes = $this->changeLogRepository->getLogsForJourneysAndStagesBelongingTo($object);

        $renameMap = [
            'distanceTravelled.unit' => 'distanceUnit',
            'distanceTravelled.value' => 'distance',
        ];

        $changesById = [];

        foreach($changes as $change) {
            $id = $change['entityId'];
            $propertyName = $change['propertyName'];

            if (isset($renameMap[$propertyName])) {
                $propertyName = $renameMap[$propertyName];
            }

            // Turns e.g. "method", 5 -> Corresponding Method object
            $propertyValue = $this->historyValueResolver->resolve($propertyName, $change['propertyValue']);

            $changeContext = ($context['originalContext'] ?? []);

            $changesById[$id] ??= [];
            $changesById[$id][$propertyName] ??= [];
            $changesById[$id][$propertyName][] = [
                'value' => $this->normalizer->normalize($propertyValue, $format, $changeContext),
                'interviewerId' => $change['interviewerSerialId'],
                'timestamp' => $change['timestamp']->getTimestamp(),
            ];
        }

        // Filter the history - we don't need to show history for a property if history length is only 1
        foreach ($changesById as $id => $propertyChanges) {
            foreach ($propertyChanges as $propertyName => $history) {
                if (count($history) === 1) {
                    unset($changesById[$id][$propertyName]);
                }
            }
        }

        return $changesById;
    }
}