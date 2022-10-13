<?php

namespace App\Form\TravelDiary\JourneyWizard;

use App\Entity\Journey\Journey;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PurposeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('purpose', InputType::class, [
                'label_is_page_heading' => true,
                'label' => $options['purpose_label'],
                'label_attr' => ['class' => 'govuk-label--l'],
                'help' => "journey.purpose.help",
                'help_html' => 'markdown',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'purpose_label' => 'journey.purpose.label',
            'data_class' => Journey::class,
            'translation_domain' => 'travel-diary',
            'validation_groups' => 'wizard.journey.purpose',
        ]);
    }
}