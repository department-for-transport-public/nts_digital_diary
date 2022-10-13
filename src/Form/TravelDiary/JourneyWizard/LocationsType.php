<?php

namespace App\Form\TravelDiary\JourneyWizard;

use App\Entity\Journey\Journey;
use App\Entity\User;
use App\Repository\DiaryKeeperRepository;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraints\NotNull;

class LocationsType extends AbstractType
{
    const CHOICE_HOME = 'home';
    const CHOICE_OTHER = 'other';

    /**
     * @var array|string[]
     */
    private array $choices;

    private LocationsDataMapper $locationsDataMapper;

    public function __construct(DiaryKeeperRepository $diaryKeeperRepository, LocationsDataMapper $locationsDataMapper, Security $security)
    {
        $this->locationsDataMapper = $locationsDataMapper;

        /** @var User $user */
        $user = $security->getUser();
        $commonLocations = $diaryKeeperRepository->getCommonLocations($user->getDiaryKeeper());
        $this->choices =
            ['journey.locations.choices.home' => self::CHOICE_HOME]
            + array_combine($commonLocations, $commonLocations)
            + ['journey.locations.choices.other' => self::CHOICE_OTHER];

    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setDataMapper($this->locationsDataMapper);

        $this
            ->addLocationFields('start', $builder, $options)
            ->addLocationFields('end', $builder, $options);
    }

    protected function addLocationFields($locationType, FormBuilderInterface $builder, array $options): self
    {
        $prefix = 'journey.locations';

        $builder
            ->add("{$locationType}_choice", ChoiceType::class, [
                'label' => "$prefix.$locationType-choice.label",
                'help' => "$prefix.$locationType-choice.help",
                'label_attr' => ['class' => 'govuk-label--m'],
                'choices' => $this->choices,
                'choice_translation_domain' => false,
                'mapped' => false,
                'choice_options' => [
                    'journey.locations.choices.home' => ['translation_domain' => $options['translation_domain']],
                    'journey.locations.choices.other' => [
                        'translation_domain' => $options['translation_domain'],
                        'conditional_form_name' => "{$locationType}Location",
                    ],
                ],
                'constraints' => new NotNull(['groups' => 'wizard.journey.locations', 'message' => "wizard.journey.{$locationType}-choice.not-null"])
            ])
            ->add("{$locationType}Location", InputType::class, [
                'label' => "$prefix.$locationType-other.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "$prefix.common-other.help",
                'help_html' => 'markdown',
                'attr' => ['class' => 'govuk-input--width-20'],
            ]);
        return $this;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'help' => 'journey.locations.help',
            'data_class' => Journey::class,
            'translation_domain' => 'travel-diary',
            'validation_groups' => function(FormInterface $form) {
                $vg = ['wizard.journey.locations'];

                foreach(['start', 'end'] as $startOrEnd) {
                    if ($form->get("{$startOrEnd}_choice")->getData() === self::CHOICE_OTHER) {
                        $vg[] = "wizard.journey.locations.{$startOrEnd}-general";

                        $name = $form->get("{$startOrEnd}Location")->getData();
                        if ($name === null || strtolower($name) !== 'home') {
                            $vg[] = "wizard.journey.locations.{$startOrEnd}-other";
                        }
                    }
                }

                return $vg;
            }
        ]);
    }
}