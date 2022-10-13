<?php

namespace App\Form\OnBoarding;

use App\Entity\Journey\Method;
use App\Entity\Vehicle;
use Doctrine\ORM\EntityRepository;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonGroupType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\EntityType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Ghost\GovUkFrontendBundle\Form\Type\LinkType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('friendlyName', InputType::class, [
                'label' => "vehicle.form.name.label",
                'help' => "vehicle.form.name.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
            ])
            ->add('method', EntityType::class, [
                'class' => Method::class,
                'choice_label' => fn(Method $x) => "stage.method.choices.{$x->getDescriptionTranslationKey()}",
                'query_builder' => fn(EntityRepository $er) => $er
                    ->createQueryBuilder('m')
                    ->where('m.code IN (:codes)')
                    ->setParameter('codes', Vehicle::VALID_METHOD_CODES),
                'choice_translation_domain' => 'travel-diary',
                'label' => "vehicle.form.method.label",
                'help' => "vehicle.form.method.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('button_group', ButtonGroupType::class);

        $builder
            ->get('button_group')
            ->add('save', ButtonType::class, [
                'label' => "vehicle.form.submit",
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
            'data_class' => Vehicle::class,
            'translation_domain' => 'on-boarding',
            'validation_groups' => ['wizard.on-boarding.vehicle'],
        ]);

        $resolver->setRequired([
            'cancel_link_href',
        ]);
    }
}