<?php

namespace App\Serializer\ChangeSet;

use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;

abstract class AbstractChangeSetNormalizer implements ContextAwareNormalizerInterface
{
    const CHANGE_SET_ENTITY_KEY = 'property-change-set_entity';

    protected function renameFields(array &$changeSet, array $fromAndTo): void
    {
        foreach($fromAndTo as $from => $to) {
            $this->renameField($changeSet, $from, $to);
        }
    }

    protected function renameField(array &$changeSet, string $from, string $to): void
    {
        if (isset($changeSet[$from])) {
            $changeSet[$to] = $changeSet[$from];
            unset($changeSet[$from]);
        }
    }

    protected function removeFields(array &$changeSet, array $fieldsToRemove): void
    {
        foreach($fieldsToRemove as $fieldToRemove) {
            $this->removeField($changeSet, $fieldToRemove);
        }
    }

    protected function whitelistFields(array &$changeSet, array $fieldsToKeep): void
    {
        foreach($changeSet as $key => $value) {
            if (!in_array($key, $fieldsToKeep)) {
                unset($changeSet[$key]);
            }
        }
    }

    protected function removeField(array &$changeSet, string $name): void
    {
        if (isset($changeSet[$name])) {
            unset($changeSet[$name]);
        }
    }
}