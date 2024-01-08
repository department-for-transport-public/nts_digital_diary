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

class PreferredMethodType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $preferredMethodChoices = [
            'paper',
            'online',
            'phone-app-automatic',
            'phone-app-manual',
            'other',
        ];

        $builder
            ->add('preferredMethod', ChoiceType::class, [
                'label' => 'satisfaction.preferred-method.label',
                'help' => 'satisfaction.preferred-method.help',
                'choices' => array_combine(
                    array_map(fn(string $choice) => "satisfaction.preferred-method.choices.{$choice}", $preferredMethodChoices),
                    $preferredMethodChoices,
                ),
                'choice_options' => [
                    'satisfaction.preferred-method.choices.other' => [
                        'conditional_form_name' => "preferredMethodOther",
                    ],
                ],
                'label_attr' => ['class' => 'govuk-label--m'],
            ])
            ->add("preferredMethodOther", InputType::class, [
                'label' => 'satisfaction.preferred-method-other.label',
                'help' => 'satisfaction.preferred-method-other.help',
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function(PostSubmitEvent $event) {
                /** @var SatisfactionSurvey $data */
                $data = $event->getData();

                $preferredMethod = $data->getPreferredMethod();
                if ($preferredMethod !== 'other') {
                    $data->setPreferredMethodOther(null);
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SatisfactionSurvey::class,
            'translation_domain' => 'travel-diary',
            'validation_groups' => ['wizard.satisfaction-survey.preferred-method'],
        ]);
    }
}