<?php


namespace App\Controller\Admin;


use App\Controller\AbstractController;
use App\Form\Admin\SampleImportForm;
use App\Security\OneTimePassword\PasscodeGenerator;
use App\Utility\AreaPeriodHelper;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/sample-import", name="sampleimport_")
 * @Security("user.getUserIdentifier() matches '/@ghostlimited\\.com$/'")
 */
class SampleImportController extends AbstractController
{
    /**
     * @Route(name="index", methods={"GET", "POST"})
     * @Template("admin/sample_import/index.html.twig")
     */
    public function index(Request $request, PasscodeGenerator $passcodeGenerator, EntityManagerInterface $entityManager, AreaPeriodHelper $areaPeriodHelper): array
    {
        $form = $this->getImportForm();
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $areas = $form->getData();
            if ($form->isValid() || empty(iterator_to_array($form->get('areas')->getErrors()))) {
                foreach ($areas as $area) {
                    $entityManager->persist($area);
                }
                $errors = $this->getNormalizedSampleImportFormErrors($form);
                if (empty($errors)) {
                    $entityManager->flush();
                }
                return [
                    'form' => $form->createView(),
                    'areas' => $areas,
                    'normalizedErrors' => $errors,
                    'passcodeGenerator' => $passcodeGenerator,
                ];
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    protected function getNormalizedSampleImportFormErrors(FormInterface $form): array
    {
        $normalizedErrors = [];
        foreach ($form->getErrors(true) as $error) {
            if (!isset($normalizedErrors[$error->getCause()->getPropertyPath()])) {
                $normalizedErrors[$error->getCause()->getPropertyPath()] = [];
            }
            $normalizedErrors[$error->getCause()->getPropertyPath()][] = $error;
        }
        return $normalizedErrors;
    }

    protected function getImportForm(): FormInterface
    {
        return $this
            ->createForm(SampleImportForm::class, null, [])
        ;
    }
}