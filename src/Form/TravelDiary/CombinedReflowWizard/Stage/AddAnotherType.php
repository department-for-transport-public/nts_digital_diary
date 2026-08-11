<?php

namespace App\Form\TravelDiary\CombinedReflowWizard\Stage;

use App\Entity\Journey\Journey;
use App\Form\TravelDiary\CombinedReflowWizard\FormHelperTrait;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class AddAnotherType extends AbstractType
{
    use FormHelperTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Journey $journey */
        $journey = $options['journey'];
        $translationParameters = [
            'endLocation' => $journey->getEndLocationForDisplay(),
            'methods' => $this->getMethodsList($journey),
            'stages' => $journey->getStages()->count(),
        ];
        $builder
            ->add('is_final_stage', BooleanChoiceType::class, [
                'label_is_page_heading' => true,
                'label' => "add-journey.stage-add-another.label",
                'label_translation_parameters' => $translationParameters,
                'help' => "add-journey.stage-add-another.help",
                'help_translation_parameters' => $translationParameters,
                'help_html' => true,
                'label_attr' => ['class' => 'govuk-label--l'],
                'constraints' => [new NotNull(['message' => 'wizard.stage.add-another.not-null'])],
//                'inline' => true,
                'choices' => [
                    'add-journey.stage-add-another.choices.yes' => "true",
                    'add-journey.stage-add-another.choices.no' => "false",
                ],
                'choice_translation_domain' => 'travel-diary',
            ]);
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['label_translation_parameters'] = $view->vars['translation_parameters'] ?? [];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'translation_domain' => 'travel-diary',
            'journey' => null,
        ]);
    }
}