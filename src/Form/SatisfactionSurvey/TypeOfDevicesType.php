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

class TypeOfDevicesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $typeOfDevicesChoices = [
            'desktop-or-laptop',
            'tablet',
            'smartphone',
            'other',
        ];

        $builder
            ->add('typeOfDevices', ChoiceType::class, [
                'label' => 'satisfaction.type-of-devices.label',
                'help' => 'satisfaction.type-of-devices.help',
                'choices' => array_combine(
                    array_map(fn(string $choice) => "satisfaction.type-of-devices.choices.{$choice}", $typeOfDevicesChoices),
                    $typeOfDevicesChoices,
                ),
                'choice_options' => [
                    'satisfaction.type-of-devices.choices.other' => [
                        'conditional_form_name' => "typeOfDevicesOther",
                    ],
                ],
                'label_attr' => ['class' => 'govuk-label--m'],
                'multiple' => true,
            ])
            ->add("typeOfDevicesOther", InputType::class, [
                'label' => 'satisfaction.type-of-devices-other.label',
                'help' => 'satisfaction.type-of-devices-other.help',
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->addEventListener(FormEvents::POST_SUBMIT, function(PostSubmitEvent $event) {
                /** @var SatisfactionSurvey $data */
                $data = $event->getData();

                $typeOfDevices = $data->getTypeOfDevices();
                if (!in_array('other', $typeOfDevices)) {
                    $data->setTypeOfDevicesOther(null);
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SatisfactionSurvey::class,
            'translation_domain' => 'travel-diary',
            'validation_groups' => ['wizard.satisfaction-survey.type-of-devices'],
        ]);
    }
}