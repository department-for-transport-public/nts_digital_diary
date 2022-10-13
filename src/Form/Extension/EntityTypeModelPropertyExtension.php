<?php


namespace App\Form\Extension;


use Ghost\GovUkFrontendBundle\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

class EntityTypeModelPropertyExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [
            EntityType::class,
        ];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('model_property', null);
        $resolver->setAllowedTypes('model_property', ['null', 'string']);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['model_property']) {
            $builder->addModelTransformer(new CallbackTransformer(
                function ($data) use ($options) {
                    $pa = PropertyAccess::createPropertyAccessor();
                    $choices = $options['choices'] ?? $options['choice_loader']->loadChoiceList()->getChoices();
                    foreach ($choices as $choice) {
                        if ($pa->getValue($choice, $options['model_property']) === $data) {
                            return $choice;
                        }
                    }
                    return null;
                },
                function ($data) use ($options) {
                    $pa = PropertyAccess::createPropertyAccessor();
                    if ($data) {
                        return $pa->getValue($data, $options['model_property']);
                    }
                    return null;
                }
            ));
        }

    }
}