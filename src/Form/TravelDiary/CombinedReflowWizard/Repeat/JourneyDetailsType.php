<?php

namespace App\Form\TravelDiary\CombinedReflowWizard\Repeat;

use App\Form\TravelDiary\CombinedReflowWizard\AbstractJourneyDetailsType;
use App\Form\TravelDiary\CombinedReflowWizard\FormHelperTrait;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;

class JourneyDetailsType extends AbstractJourneyDetailsType
{
    use FormHelperTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $journey = $builder->getData()->getSubject();
        $translationParameters = [
            'methods' => $this->getMethodsList($journey, true),
            'stages' => $journey->getStages()->count(),
        ];
        $builder
            ->add('isSameStages', BooleanChoiceType::class, [
                'label' => 'repeat.journey-details.is-same-stages.label',
                'label_translation_parameters' => $translationParameters,
                'help' => 'repeat.journey-details.is-same-stages.help',
                'help_html' => true,
                'help_translation_parameters' => $translationParameters,
                'label_attr' => ['class' => 'govuk-fieldset__legend--m'],
                'choices' => [
                    'repeat.journey-details.is-same-stages.choices.yes' => "true",
                    'repeat.journey-details.is-same-stages.choices.no' => "false",
                ],
                'choice_translation_domain' => 'travel-diary',
                'choice_options' => [
                    'repeat.journey-details.is-same-stages.choices.yes' => ['label_translation_parameters' => $translationParameters],
                    'repeat.journey-details.is-same-stages.choices.no' => ['label_translation_parameters' => $translationParameters],
                ],
            ]);
    }

    protected function getValidationGroups(FormInterface $form): array
    {
        return array_merge(parent::getValidationGroups($form), ['wizard.repeat-journey.same-stages']);
    }

}