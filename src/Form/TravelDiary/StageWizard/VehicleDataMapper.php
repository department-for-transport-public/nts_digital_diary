<?php

namespace App\Form\TravelDiary\StageWizard;

use App\Entity\Journey\Stage;
use App\Entity\Vehicle;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Extension\Core\DataMapper\DataMapper;
use Symfony\Component\Form\FormInterface;
use Traversable;

class VehicleDataMapper extends DataMapper
{
    public const OTHER_KEY = '-_-_-other-_-_-';

    public function mapDataToForms($data, $forms): void
    {
        if (!$data instanceof Stage) {
            throw new UnexpectedTypeException($data, Stage::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */
        if (!isset($forms['vehicleOther'])) {
            // sometimes we end up here with just a "continue" button
            return;
        }

        $vehiclesInForm = isset($forms['vehicle']) ?
            array_map(
                fn(FormInterface $f) => $f->getConfig()->getOption('label'),
                iterator_to_array($forms['vehicle'])
            ) :
            [];

        $vehiclesInForm = array_flip(array_filter($vehiclesInForm, fn(string $x) => $x !== 'stage.vehicle.vehicle.other'));

        if ($data->getVehicle() === null) {
            if ($data->getVehicleOther() !== null) {
                $otherVehicle = $data->getVehicleOther();

                if ($forms['vehicle'] ?? false) {
                    if (($vehiclesInForm[$otherVehicle] ?? null) !== null) {
                        $forms['vehicle']->setData($otherVehicle);
                        $forms['vehicleOther']->setData(null);
                    } else {
                        $forms['vehicle']->setData(self::OTHER_KEY);
                        $forms['vehicleOther']->setData($otherVehicle);
                    }
                } else {
                    $forms['vehicleOther']->setData($otherVehicle);
                }
            }
        } else {
            $forms['vehicle']->setData($data->getVehicle());
            $forms['vehicleOther']->setData(null);
        }
    }

    /**
     * @param Traversable|iterable $forms
     * @param Stage $data
     */
    public function mapFormsToData(iterable $forms, &$data): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */
        if (!isset($forms['vehicleOther'])) {
            // sometimes we end up here with just a "continue" button
            return;
        }

        $vehicle = isset($forms['vehicle']) ? $forms['vehicle']->getData() : self::OTHER_KEY;
        $vehicleOther = $forms['vehicleOther']->getData();

        if ($vehicle === self::OTHER_KEY) {
            $data
                ->setVehicle(null)
                ->setVehicleOther($vehicleOther ?? '');
            // The empty string above is key to being able to tell between:
            //   1) A completely non-filled out form
            //   2) Vehicle: "other" / VehicleOther: <empty>
        } else {
            if ($vehicle instanceof Vehicle) {
                $data
                    ->setVehicle($vehicle)
                    ->setVehicleOther(null);
            } else {
                $data
                    ->setVehicle(null)
                    ->setVehicleOther($vehicle);
            }
        }
    }
}