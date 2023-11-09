<?php

namespace App\Form\Admin\Feedback;

use App\Entity\Feedback\Group;
use App\Entity\Feedback\Message;
use App\Security\GoogleIap\AdminRoleResolver;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class AssignmentType extends AbstractType
{
    public function __construct(protected AdminRoleResolver $adminRoleResolver) {}

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
//            ->add('category', ChoiceType::class, [
//                'label' => 'feedback.view.details.category',
//                'choices' => Message::CATEGORIES,
//                'choice_label' => fn($c) => ucfirst($c),
//                'expanded' => false,
//                'attr' => ['class' => 'govuk-input--width-15'],
//                'label_attr' => ['class' => 'govuk-label--s'],
//                'placeholder' => 'feedback.assignment.choice.placeholder',
//                'choice_translation_domain' => false,
//            ])
            ->add('assignedTo', ChoiceType::class, [
                'label' => 'feedback.view.details.assigned-to',
                'choices' => array_map(fn(Group $g) => $g->getName(), $this->adminRoleResolver->getAssignees()),
                'choice_label' => fn($c) => $c,
                'setter' => fn($v) => null,
                'expanded' => false,
                'label_attr' => ['class' => 'govuk-label--s'],
                'placeholder' => 'feedback.assignment.choice.placeholder',
                'constraints' => new NotBlank(groups: ['feedback.assignment']),
                'choice_translation_domain' => false,
            ])
            ->add('submit', ButtonType::class, [
                'type' => 'submit',
                'label' => 'feedback.assignment.submit-button.label',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Message::class,
            'validation_groups' => ["feedback.assignment"],
            'translation_domain' => 'admin',
        ]);
    }
}