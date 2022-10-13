<?php


namespace Ghost\GovUkFrontendBundle\Form\Type;


use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;
use Symfony\Component\Form\FormInterface;

class ValueUnitDataMapper extends DataMapper
{
    private bool $allowEmpty;

    public function __construct(bool $allowEmpty)
    {
        parent::__construct(null);
        $this->allowEmpty = $allowEmpty;
    }

    /**
     * @param iterable|\Traversable $forms
     * @param mixed $data
     */
    public function mapFormsToData(iterable $forms, &$data): void
    {
        parent::mapFormsToData($forms, $data);

        $forms = iterator_to_array($forms);
        /** @var $forms FormInterface[] */

        // When the ValueUnit is allowed to be empty, but user has provided a unit without value
        if ($this->allowEmpty && !is_null($forms['unit']->getData()) && is_null($forms['value']->getData()))
        {
            $data = null;
        }
    }
}