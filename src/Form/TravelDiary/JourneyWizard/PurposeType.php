<?php

namespace App\Form\TravelDiary\JourneyWizard;

use App\Entity\Journey\Journey;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PurposeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isSubForm = $options['is_sub_form'];
        $builder
            ->add('purpose', InputType::class, [
                'label_is_page_heading' => !$isSubForm,
                'label' => $options['purpose_label'],
                'label_attr' => ['class' => $isSubForm ? 'govuk-label--m' : 'govuk-label--l'],
                'help' => "journey.purpose.help",
                'help_html' => 'markdown',
                'attr' => [
                    'autocomplete' => 'on',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'purpose_label' => 'journey.purpose.label',
            'data_class' => Journey::class,
            'translation_domain' => 'travel-diary',
            'validation_groups' => 'wizard.journey.purpose',
            'is_sub_form' => false,
        ]);
    }
}