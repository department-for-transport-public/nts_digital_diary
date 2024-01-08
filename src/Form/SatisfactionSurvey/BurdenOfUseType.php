<?php

namespace App\Form\SatisfactionSurvey;

use App\Entity\SatisfactionSurvey;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PostSubmitEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BurdenOfUseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $burdenOfUseChoices = [
            '1-not-at-all-burdensome',
            '2-a-little-burdensome',
            '3-moderately-burdensome',
            '4-very-burdensome',
            '5-extremely-burdensome',
        ];

        $burdenReasonChoices = [
            'technical-problems',
            'task-length',
            'completing-multiple-diaries',
            'vocabulary-was-difficult',
            'did-not-understand-intent',
            'distracted',
            'other-activities-at-same-time',
            'other',
        ];

        $builder
            ->add('burdenRating', ChoiceType::class, [
                'label' => 'satisfaction.burden-of-use.label',
                'help' => 'satisfaction.burden-of-use.help',
                'choices' => array_combine(
                    array_map(fn(string $choice) => "satisfaction.burden-of-use.choices.{$choice}", $burdenOfUseChoices),
                    $burdenOfUseChoices,
                ),
                'choice_options' => [
                    'satisfaction.burden-of-use.choices.1-not-at-all-burdensome' => [
                        'conditional_hide_form_names' => [
                            'burdenReason',
                        ],
                    ],
                ],
                'label_attr' => ['class' => 'govuk-label--m'],
            ])
            ->add('burdenReason', ChoiceType::class, [
                'label' => 'satisfaction.burden-reason.label',
                'help' => 'satisfaction.burden-reason.help',
                'choices' => array_combine(
                    array_map(fn(string $choice) => "satisfaction.burden-reason.choices.{$choice}", $burdenReasonChoices),
                    $burdenReasonChoices,
                ),
                'choice_options' => [
                    'satisfaction.burden-reason.choices.other' => [
                        'conditional_form_name' => "burdenReasonOther",
                    ],
                ],
                'label_attr' => ['class' => 'govuk-label--m'],
                'multiple' => true,
                'fieldset_attr' => ['data-hidden-by-default' => true, 'class' => 'govuk-!-margin-top-8'],
            ])
            ->add("burdenReasonOther", InputType::class, [
                'label' => 'satisfaction.burden-reason-other.label',
                'help' => 'satisfaction.burden-reason-other.help',
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function(PostSubmitEvent $event) {
                /** @var SatisfactionSurvey $data */
                $data = $event->getData();

                $burdenRating = $data->getBurdenRating();
                if (!$burdenRating || $burdenRating === '1-not-at-all-burdensome') {
                    $data
                        ->setBurdenReason([])
                        ->setBurdenReasonOther(null);
                } else {
                    $burdenReason = $data->getBurdenReason();
                    if (!in_array('other', $burdenReason)) {
                        $data->setBurdenReasonOther(null);
                    }
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SatisfactionSurvey::class,
            'translation_domain' => 'travel-diary',
            'validation_groups' => ['wizard.satisfaction-survey.burden'],
        ]);
    }
}