<?php

namespace App\Form\TravelDiary;

use App\Entity\DiaryKeeper;
use App\Features;
use App\Form\ConfirmActionType;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;

class ApproveDiaryConfirmActionType extends AbstractType
{
    public function __construct(protected Features $features)
    {}

    public function getParent(): string
    {
        return ConfirmActionType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var DiaryKeeper $diaryKeeper */
        $diaryKeeper = $options['diary_keeper'];

        if ($diaryKeeper->hasEmptyDays()) {
            $builder
                ->add('verifyEmptyDays', Gds\CheckboxType::class, [
                    'priority' => 20,
                    'constraints' => [new NotBlank(message: "common.option.required")],
                    'label' => 'diary-state.approve.confirm-empty-journeys',
                    'label_translation_parameters' => ['name' => $diaryKeeper->getName()],
                ])
            ;
        }

        $choices = [
            'diary-state.approve.confirm-return-journeys' => 'confirm-return-journeys',
            'diary-state.approve.split-round-trips' => 'split-round-trips',
            'diary-state.approve.corrected-no-stages' => 'corrected-no-stages',
        ];

        if ($this->features->isEnabled(Features::MILOMETER)) {
            $choices['diary-state.approve.checked-vehicles'] = 'checked-vehicles';
        }

        $builder
            ->add('alsoVerified', Gds\ChoiceType::class, [
                'label' => 'diary-state.approve.also-verify.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'constraints' => [new Count(min: count($choices), minMessage: 'All items must be confirmed')],
                'priority' => 10,
                'multiple' => true,
                'choices' => $choices,
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            'diary_keeper'
        ]);
        $resolver->setDefaults([
            'translation_domain' => 'interviewer',
        ]);
    }
}