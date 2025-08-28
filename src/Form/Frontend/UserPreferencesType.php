<?php

namespace App\Form\Frontend;

use App\Entity\UserPreferences;
use App\Utility\DateTimeFormats;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonGroupType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\LinkType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserPreferencesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('timeFormat', ChoiceType::class, [
                'label' => 'user-preferences.time-format.label',
                'help' => 'user-preferences.time-format.help',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'choices' => [
                    'time.12-hour.label' => DateTimeFormats::MODE_12_HOUR,
                    'time.24-hour.label' => DateTimeFormats::MODE_24_HOUR,
                ],
                'choice_options' => [
                    'time.12-hour.label' => ['help' => 'time.12-hour.short-help'],
                    'time.24-hour.label' => ['help' => 'time.24-hour.short-help'],
                ],
            ])
            ;
        if ($options['include_buttons']) {
            $builder->add('buttons', ButtonGroupType::class);
            $builder->get('buttons')
                ->add('submit', ButtonType::class, [
                    'label' => 'actions.save',
                ])
                ->add('cancel', LinkType::class, [
                    'href' => '/',
                    'label' => 'actions.cancel',
                ])
            ;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => UserPreferences::class,
                'include_buttons' => true,
            ]);
    }
}