<?php

namespace App\Form\TravelDiary\JourneyWizard;

use App\Entity\Journey\Journey;
use App\Form\TravelDiary\AbstractLocationType;
use App\Repository\DiaryKeeperRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Security;

class LocationsType extends AbstractLocationType
{
    private LocationsDataMapper $locationsDataMapper;

    public function __construct(DiaryKeeperRepository $diaryKeeperRepository, Security $security, LocationsDataMapper $locationsDataMapper)
    {
        parent::__construct($diaryKeeperRepository, $security);
        $this->locationsDataMapper = $locationsDataMapper;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setDataMapper($this->locationsDataMapper);

        $prefix = 'journey.locations';

        $this
            ->addLocationFields($prefix, 'start', $builder, $options)
            ->addLocationFields($prefix, 'end', $builder, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

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