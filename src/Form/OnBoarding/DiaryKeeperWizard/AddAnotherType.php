<?php

namespace App\Form\OnBoarding\DiaryKeeperWizard;

use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class AddAnotherType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('add_another', BooleanChoiceType::class, [
                'label' => "diary-keeper.add-another.label",
                'help' => "diary-keeper.add-another.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'constraints' => [new NotNull(['message' => 'wizard.diary-keeper.add-another.not-null'])],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => null,
            'translation_domain' => 'on-boarding',
        ]);
    }
}