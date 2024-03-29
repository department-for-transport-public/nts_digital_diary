<?php

namespace App\Controller\Api;

use App\Repository\HouseholdRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SurveyDataController extends AbstractController
{
    /**
     * @Route("/survey-data")
     * @throws ExceptionInterface
     */
    public function index(Request $request, HouseholdRepository $householdRepository, NormalizerInterface $normalizer, int $version): Response
    {
        $startTime = $this->getQueryTimestampAsDate($request, 'startTime');
        $endTime = $this->getQueryTimestampAsDate($request, 'endTime');

        $serials = $this->getQueryHouseholdSerials($request);

        if ($serials && !($startTime || $endTime)) {
            $maxHouseholds = 10;
            if (count($serials) > $maxHouseholds) {
                throw new BadRequestHttpException("No more than $maxHouseholds households can be requested in a single call");
            }

            $households = $householdRepository->findForExportByHouseholdSerials($serials);
        } elseif (!$serials && $startTime && $endTime) {
            // Limit end time to 7 days after start time to prevent DOS
            $sevenDays = 7 * 24 * 60 * 60;
            if ($endTime->getTimestamp() > ($startTime->getTimestamp() + $sevenDays)) {
                throw new BadRequestHttpException("endTime cannot be more than 7 days / $sevenDays seconds after startTime");
            }

            $households = $householdRepository->findForExportByTimestamps($startTime, $endTime);
        } else {
            throw new BadRequestHttpException('Invalid request parameters');
        }

        $data = $normalizer->normalize($households, null, [
            'apiVersion' => $version,
            DateTimeNormalizer::FORMAT_KEY => 'c',
        ]);

        return new JsonResponse($data);
    }

    protected function getQueryHouseholdSerials(Request $request): ?array
    {
        $queryParamValue = $request->query->get('householdSerials', null);
        return $queryParamValue ? explode(',', $queryParamValue) : null;
    }

    protected function getQueryTimestampAsDate(Request $request, $queryParamKey): ?DateTime
    {
        $queryParamValue = $request->query->get($queryParamKey, null);
        if (is_null($queryParamValue)) {
            return null;
        }
        $queryParamValueInt = intval($queryParamValue);
        if ($queryParamValue !== "$queryParamValueInt") {
            throw new BadRequestException("'$queryParamKey' was badly formatted ('$queryParamValue')");
        }
        return (new DateTime())->setTimestamp($queryParamValue);
    }
}
