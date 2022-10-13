<?php


namespace App\Form\Admin;


use App\Entity\Interviewer;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonGroupType;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Ghost\GovUkFrontendBundle\Form\Type\LinkType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\RouterInterface;

class InterviewerType extends AbstractType
{
    protected string $cancelUrl;

    public function __construct(RouterInterface $router)
    {
        $this->cancelUrl = $router->generate('admin_interviewers_list');
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', InputType::class, [
                'label' => 'interviewer.edit.name.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-15'],
            ])
            ->add('serialId', InputType::class, [
                'label' => 'interviewer.edit.serial-id.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-15'],
            ])
            ->add('email', InputType::class, [
                'property_path' => 'user.username',
                'label' => 'interviewer.edit.email.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-20'],
            ])
            ->add('button_group', ButtonGroupType::class);

        $builder
            ->get('button_group')
            ->add('save', ButtonType::class, [
                'label' => "interviewer.edit.save.label",
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
            'data_class' => Interviewer::class,
            'translation_domain' => 'admin',
            'validation_groups' => 'admin.interviewer',
        ]);
    }
}
