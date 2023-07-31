<?php

namespace App\Form\TravelDiary;

use App\Entity\User;
use App\Repository\DiaryKeeperRepository;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\NotNull;

abstract class AbstractLocationType extends AbstractType
{
    const CHOICE_HOME = 'home';
    const CHOICE_OTHER = 'other';

    /**
     * @var array<string>
     */
    private array $choices;

    public function __construct(DiaryKeeperRepository $diaryKeeperRepository, Security $security)
    {
        /** @var User $user */
        $user = $security->getUser();
        $commonLocations = $diaryKeeperRepository->getCommonLocations($user->getDiaryKeeper());
        $this->choices =
            ['journey.locations.choices.home' => self::CHOICE_HOME]
            + array_combine($commonLocations, $commonLocations)
            + ['journey.locations.choices.other' => self::CHOICE_OTHER];
    }

    protected function addLocationFields(
        string $prefix,
        string $locationType,
        FormBuilderInterface|FormInterface $builder,
        array $options,
        array $choiceFieldOptionOverrides = [],
        array $locationFieldOptionOverrides = [],
    ): self
    {
        $choice_options = [
            'journey.locations.choices.home' => [
                'translation_domain' => $options['translation_domain'],
            ],
            'journey.locations.choices.other' => [
                'translation_domain' => $options['translation_domain'],
                'conditional_form_name' => "{$locationType}Location",
            ],
        ];

        foreach($choiceFieldOptionOverrides['choice_options'] ?? [] as $choice => $option_overrides) {
            $choice_options[$choice] = array_merge(
                $choice_options[$choice] ?? [],
                $option_overrides
            );
        }

        unset($choiceFieldOptionOverrides['choice_options']);

        $builder
            ->add("{$locationType}_choice", ChoiceType::class, array_merge([
                'label' => "$prefix.$locationType-choice.label",
                'help' => "$prefix.$locationType-choice.help",
                'label_attr' => ['class' => 'govuk-label--m'],
                'choices' => $this->choices,
                'choice_translation_domain' => false,
                'mapped' => false,
                'choice_options' => $choice_options,
                'constraints' => new NotNull(['groups' => 'wizard.journey.locations', 'message' => "wizard.journey.{$locationType}-choice.not-null"])
            ], $choiceFieldOptionOverrides))
            ->add("{$locationType}Location", InputType::class, array_merge([
                'label' => "$prefix.$locationType-other.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "$prefix.common-other.help",
                'help_html' => 'markdown',
                'attr' => ['class' => 'govuk-input--width-20'],
                'mapped' => false, // TODO: Check whether this breaks things??
            ], $locationFieldOptionOverrides));
        return $this;
    }
}