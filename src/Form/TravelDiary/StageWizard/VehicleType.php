<?php

namespace App\Form\TravelDiary\StageWizard;

use App\Entity\Journey\Stage;
use App\Entity\Vehicle;
use App\Repository\DiaryKeeperRepository;
use App\Repository\VehicleRepository;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class VehicleType extends AbstractType
{
    const INJECT_TRANSLATION_PARAMETERS = [
        'stage_number' => 'number',
    ];

    protected DiaryKeeperRepository $diaryKeeperRepository;
    protected VehicleRepository $vehicleRepository;

    public function __construct(DiaryKeeperRepository $diaryKeeperRepository, VehicleRepository $vehicleRepository)
    {
        $this->diaryKeeperRepository = $diaryKeeperRepository;
        $this->vehicleRepository = $vehicleRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(PreSetDataEvent $event) use ($options) {
            /** @var Stage $stage */
            $stage = $event->getData();
            $form = $event->getForm();

            $diaryKeeper = $stage->getJourney()->getDiaryDay()->getDiaryKeeper();
            $methodCode = $stage->getMethod()->getCode();

            $relevantHouseholdVehicles = array_filter(
                $diaryKeeper->getHousehold()->getVehicles()->toArray(),
                fn(Vehicle $v) => $v->getMethod()->getCode() === ($methodCode ?? null)
            );

            $otherNamedVehicles = $this->diaryKeeperRepository
                ->findVehiclesNamedByDiaryKeeper($diaryKeeper, $methodCode, 3);

            $householdVehicles = array_merge($relevantHouseholdVehicles, $otherNamedVehicles);
            $getVehicleName = fn($vehicle) => $vehicle instanceof Vehicle ? $vehicle->getFriendlyName() : $vehicle;

            usort($householdVehicles, fn($a, $b) => $getVehicleName($a) <=> $getVehicleName($b));

            $transPrefix = "stage.vehicle";
            $otherLabel = "{$transPrefix}.vehicle.other";

            if (!empty($householdVehicles)) {
                $householdVehicles[VehicleDataMapper::OTHER_KEY] = VehicleDataMapper::OTHER_KEY;

                $form
                    ->add('vehicle', ChoiceType::class, [
                        'label' => "{$transPrefix}.vehicle.label",
                        'label_is_page_heading' => true,
                        'label_attr' => ['class' => 'govuk-label--l'],
                        'help' => "{$transPrefix}.vehicle.help",
                        'choices' => $householdVehicles,
                        'choice_label' => fn($x) => $x === VehicleDataMapper::OTHER_KEY
                            ? $otherLabel
                            : $getVehicleName($x),
                        'choice_options' => [
                            VehicleDataMapper::OTHER_KEY => [
                                'translation_domain' => $options['translation_domain'],
                                'conditional_form_name' => 'vehicleOther',
                            ]
                        ],
                        'choice_translation_domain' => false,
                        'choice_value' => fn($x) => $x === VehicleDataMapper::OTHER_KEY ?
                            VehicleDataMapper::OTHER_KEY : $getVehicleName($x),
                    ]);
            }
            $form
                ->add('vehicleOther', InputType::class, [
                    'label' => "{$transPrefix}.vehicle-other.label",
                    'label_attr' => ['class' => 'govuk-label--s'],
                    'help' => "{$transPrefix}.vehicle-other.help",
                    'constraints' => [
                        new Callback(function(?string $value, ExecutionContextInterface $context) {
                            if ($value === VehicleDataMapper::OTHER_KEY) {
                                $context->buildViolation('common.string.invalid')->addViolation();
                            }
                        }, ['wizard.vehicle'])
                    ],
                ]);
        })->setDataMapper(new VehicleDataMapper());
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Stage::class,
            'translation_domain' => 'travel-diary',
            'validation_groups' => 'wizard.vehicle',
        ]);
    }
}