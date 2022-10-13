<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Ghost\GovUkFrontendBundle\Form\DataTransformer\TimeStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TimeType extends AbstractType
{
    const AM = 'am';
    const PM = 'pm';

    public function getBlockPrefix(): string
    {
        return 'gds_time';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $subFieldOptions = [
            'error_bubbling' => true,
            'translation_domain' => 'messages',
        ];

        $amOrPmOptions = [
            'label' => 'time.meridiem.label',
            'choices' => [
                'time.meridiem.am.label' => self::AM,
                'time.meridiem.pm.label' => self::PM,
            ],
            'expanded' => $options['expanded'],
        ];

        if ($options['expanded'] === false) {
            $amOrPmOptions['placeholder'] = '';
        }

        $builder
            ->add('hour', TextType::class, array_merge($subFieldOptions, [
                'label' => 'time.hour.label',
            ]))
            ->add('minute', TextType::class, array_merge($subFieldOptions, [
                'label' => 'time.minute.label',
            ]))
            ->add('am_or_pm', ChoiceType::class, array_merge($subFieldOptions, $amOrPmOptions))
            ->addModelTransformer(new TimeStringTransformer())
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'help' => 'time.help',
            'error_bubbling' => false,
            'expanded' => true,
        ]);
    }
}
