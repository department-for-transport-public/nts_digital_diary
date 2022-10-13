<?php

namespace App\Form\Admin;

use Ghost\GovUkFrontendBundle\Form\Type\ButtonGroupType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\FileUploadType;
use Ghost\GovUkFrontendBundle\Form\Type\LinkType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\NotNull;

class SampleImportForm extends AbstractType
{
    protected string $cancelUrl;
    private SerializerInterface $serializer;

    public function __construct(RouterInterface $router, SerializerInterface $serializer)
    {
        $this->cancelUrl = $router->generate('admin_dashboard');
        $this->serializer = $serializer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setDataMapper(new SampleImportDataMapper($this->serializer))

            ->add('areas', FileUploadType::class, [
                'label' => 'sample-import.form.areas.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'constraints' => [new NotNull(['groups' => 'area.import'])],
            ])
            ->add('button_group', ButtonGroupType::class);
            ;

        $builder
            ->get('button_group')
            ->add('import', ButtonType::class, [
                'label' => "sample-import.form.import.label",
            ])
            ->add('cancel', LinkType::class, [
                'label' => 'actions.cancel',
                'translation_domain' => 'messages',
                'href' => $this->cancelUrl,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => "area.import",
            'translation_domain' => 'admin',
        ]);
    }
}