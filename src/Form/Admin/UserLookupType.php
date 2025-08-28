<?php

namespace App\Form\Admin;

use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserLookupType extends AbstractType
{
    public function __construct(protected RouterInterface $router)
    {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', Gds\EmailType::class, [
                'label' => 'Email address',
                'label_attr' => [
                    'class' => 'govuk-label--s'
                ],
                'constraints' => [
                    new NotBlank(),
                ],
                'attr' => [
                    'class' => 'govuk-input--width-20',
                ],
            ])
            ->add('buttonGroup', Gds\ButtonGroupType::class);

        $builder
            ->get('buttonGroup')
            ->add('submit', Gds\ButtonType::class, [
                'type' => 'submit',
                'label' => 'Lookup',
            ])
            ->add('cancel', Gds\LinkType::class, [
                'href' => $this->router->generate('admin_household_maintenance_list'),
                'label' => 'Back to dashboard',
            ]);
    }
}
