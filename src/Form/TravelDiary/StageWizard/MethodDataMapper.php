<?php

namespace App\Form\TravelDiary\StageWizard;

use App\Entity\Journey\Stage;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;
use Symfony\Component\Form\FormInterface;
use Traversable;

class MethodDataMapper extends DataMapper
{
    public function mapDataToForms($data, $forms): void
    {
        if (!$data instanceof Stage) {
            throw new UnexpectedTypeException($data, Stage::class);
        }

        parent::mapDataToForms($data, $forms);

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */
        if (!isset($forms['method'])) {
            // sometimes we end up here with just a "continue" button
            return;
        }

        if ($data->getMethod() && $data->getMethod()->isOtherRequired()) {
            $otherFormName = "other-{$data->getMethod()->getDescriptionTranslationKey()}";
            $forms[$otherFormName]->setData($data->getMethodOther());
        }
    }

    /**
     * @param Traversable|iterable $forms
     * @param Stage $data
     */
    public function mapFormsToData(iterable $forms, &$data): void
    {
        parent::mapFormsToData($forms, $data);

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */
        if (!isset($forms['method'])) {
            // sometimes we end up here with just a "continue" button
            return;
        }

        $data->setMethodOther(
            ($data->getMethod() && $data->getMethod()->isOtherRequired())
                ? $forms["other-{$data->getMethod()->getDescriptionTranslationKey()}"]->getData()
                : null
        );
    }
}