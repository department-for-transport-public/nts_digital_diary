<?php

namespace App\Form\Admin;

use App\Entity\AreaPeriod;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonGroupType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Ghost\GovUkFrontendBundle\Form\Type\LinkType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AllocateAreaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('area', InputType::class, [
                'attr' => ['class' => 'govuk-input--width-5'],
                'label' => "interviewer.allocate.form.area.label",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('button_group', ButtonGroupType::class);

        $builder
            ->get('button_group')
            ->add('subscribe', ButtonType::class, [
                'label' => "interviewer.allocate.form.allocate",
            ])
            ->add('cancel', LinkType::class, [
                'label' => 'actions.cancel',
                'translation_domain' => 'messages',
                'href' => $options['cancel_link_href'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AreaPeriod::class,
            'validation_groups' => ['interviewer.allocate-area'],
            'translation_domain' => 'admin',
        ]);

        $resolver->setRequired([
            'cancel_link_href',
        ]);
    }
}