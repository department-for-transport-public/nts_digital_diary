<?php

namespace App\Form\TravelDiary\CombinedReflowWizard;

use App\Entity\Journey\Journey;
use App\Form\TravelDiary\JourneyWizard\PurposeType;
use App\Form\TravelDiary\JourneyWizard\TimesType;
use App\FormWizard\TravelDiary\CombinedReflowState;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractJourneyDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var CombinedReflowState $data */
        $data = $builder->getData();
        if (!$data->getSubject()->isGoingHome()) {
            $builder->add('purpose', PurposeType::class, [
//                'inherit_data' => true,
                'property_path' => 'subject',
                'label' => false,
                'is_sub_form' => true,
            ]);
        }
        $builder->add('times', TimesType::class, [
//            'inherit_data' => true,
            'property_path' => 'subject',
            'label' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CombinedReflowState::class,
            'validation_groups' => $this->getValidationGroups(...),
            'translation_domain' => 'travel-diary',
            'error_mapping' => [
                'subject.purpose' => 'purpose.purpose',
            ],
        ]);
    }

    protected function getValidationGroups(FormInterface $form): array
    {
        $groups = ['wizard.journey.times'];
        $data = $form->getData();
        if ($data && !$data->getSubject()->isGoingHome()) {
            $groups[] = 'wizard.journey.purpose';
        }
        return $groups;
    }
}