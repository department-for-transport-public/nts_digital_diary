<?php

namespace App\Form\Admin;

use App\Entity\AreaPeriod;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Serializer\SerializerInterface;

class SampleImportDataMapper implements DataMapperInterface
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function mapDataToForms($viewData, \Traversable $forms)
    {
    }

    public function mapFormsToData(\Traversable $forms, &$viewData)
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */
        if ($forms['areas']->getData()) {
            $viewData = $this->serializer->deserialize(
                file_get_contents($forms['areas']->getData()->getRealpath()),
                AreaPeriod::class . '[]',
                "csv",
                [
                    'as_collection' => true,
                    'sampleImport' => true,
                ]
            );
        }
    }
}