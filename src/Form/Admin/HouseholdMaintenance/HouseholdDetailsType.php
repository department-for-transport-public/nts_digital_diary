<?php

namespace App\Form\Admin\HouseholdMaintenance;

use App\Entity\Household;
use App\Form\OnBoarding\HouseholdWizard\HouseholdType;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonGroupType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\DateType;
use Ghost\GovUkFrontendBundle\Form\Type\LinkType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HouseholdDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('diaryWeekStartDate', DateType::class, [])
            ->add('isJourneySharingEnabled', BooleanChoiceType::class, [
                'label' => "submit.journey-sharing",
                'label_attr' => ['class' => 'govuk-label--s'],
                'translation_domain' => 'on-boarding',
                'inline' => true,
            ])
            ->add('button_group', ButtonGroupType::class);

        $builder
            ->get('button_group')
            ->add('save', ButtonType::class, [
                'label' => "actions.save",
                'translation_domain' => 'messages',
            ])
            ->add('cancel', LinkType::class, [
                'label' => 'actions.cancel',
                'translation_domain' => 'messages',
                'href' => $options['cancel_link_href'],
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, HouseholdType::preSetDataListener(...));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Household::class,
            'validation_groups' => "admin.maintenance.edit-household",
        ]);
        $resolver->setRequired('cancel_link_href');
    }
}