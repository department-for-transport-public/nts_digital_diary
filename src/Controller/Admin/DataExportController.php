<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AbstractJobController;
use App\Messenger\Job\FileDownloadJob;
use App\Messenger\Job\Job;
use App\Messenger\Job\Providers\AdminDataExport\FeedbackMetadataProvider;
use App\Messenger\Job\Providers\AdminDataExport\SatisfactionSurveyDataProvider;
use App\Messenger\Job\Providers\AdminDataExport\SurveyParadataProvider;
use App\Messenger\Job\State;
use App\Utility\Admin\DataExport\SurveyParadataDataExport;
use DateTime;
use Google\Cloud\Storage\StorageClient;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/data-export', name: 'data_export_')]
class DataExportController extends AbstractJobController
{

    public function __construct(
        #[Autowire(service: 'cache.ghost.job.status')] CacheItemPoolInterface $statusCache,
        #[Autowire(service: 'cache.ghost.job.result')] CacheItemPoolInterface $resultCache,
        MessageBusInterface $messageBus,
        protected string $tempBucketName,
    ) {
        parent::__construct($statusCache, $resultCache, $messageBus);
    }

    #[Route(name: 'index')]
    public function index(): Response
    {
        return $this->render('/admin/data-export/index.html.twig', []);
    }

    #[Route('/feedback-metadata', name: 'feedback_metadata')]
    public function exportFeedbackMetadata(): Response
    {
        $job = new FileDownloadJob(
            FeedbackMetadataProvider::class,
            'Feedback and support metadata',
            $this->getFilename('nts-feedback-metadata')
        );
        $this->messageBus->dispatch($job);

        return new RedirectResponse($this->generateUrl('admin_data_export_pending', ['jobId' => $job->getId()]));
    }

    #[Route('/satisfaction-survey', name: 'satisfaction_survey')]
    public function exportSatisfactionSurvey(): Response
    {
        $job = new FileDownloadJob(
            SatisfactionSurveyDataProvider::class,
            'Satisfaction survey data',
            $this->getFilename('nts-satisfaction-survey-data')
        );
        $this->messageBus->dispatch($job);

        return new RedirectResponse($this->generateUrl('admin_data_export_pending', ['jobId' => $job->getId()]));
    }

    #[Route('/paradata', name: 'paradata')]
    public function exportUsageMetadata(SurveyParadataDataExport $dataExport): Response
    {
        $job = new FileDownloadJob(
            SurveyParadataProvider::class,
            'Survey paradata',
            $this->getFilename('nts-paradata')
        );
        $this->messageBus->dispatch($job);

        return new RedirectResponse($this->generateUrl('admin_data_export_pending', ['jobId' => $job->getId()]));
    }

    #[Route(path: '/pending/{jobId}', name: 'pending')]
    public function pending(Request $request, string $jobId): Response
    {
        $jobStatus = $this->getJobStatus($jobId);

        $downloadUrl = null;
        $redirectUrl = null;
        if ($jobStatus && $jobStatus->getState() === State::COMPLETED) {
            $jobResult = $this->getJobResultFromCache($jobStatus);

            $downloadUrl = $this->generateUrl('admin_data_export_download', ['jobId' => $jobId]);
            $redirectUrl = $jobResult === true
                ? null
                : $this->generateUrl('admin_data_export_index');
            // Allow the completed export's GCS file to be framed on this page.
            // Set + rendered in this request, so a request attribute is enough;
            // intentionally not a session flash (see SecureHeadersSubscriber).
            $cspAdditions = $request->attributes->get('csp:add', []);
            $cspAdditions[] = ['frame-src', 'storage.googleapis.com'];
            $request->attributes->set('csp:add', $cspAdditions);
        }

        return $this->render('admin/data-export/queue.html.twig', [
            'jobStatus' => $jobStatus,
            'downloadUrl' => $downloadUrl,
            'redirectUrl' => $redirectUrl,
        ]);
    }

    #[Route(path: '/download/{jobId}', name: 'download')]
    public function download(string $jobId): Response
    {
        $jobStatus = $this->getJobStatus($jobId);
        /** @var FileDownloadJob $job */
        $job = $jobStatus->getJob();
        $jobResult = $this->getJobResultFromCache($jobStatus);

        if ($jobResult === true) {
            return new RedirectResponse(
                (new StorageClient())->bucket($this->tempBucketName)->object($job->getFilename())->signedUrl(time() + 30)
            );
        }

        return new Response(
            $jobResult['data'],
            200,
            $this->getResponseHeaders($jobStatus->getJob())
        );
    }

    /**
     * @param FileDownloadJob $job
     */
    protected function getResponseHeaders(Job $job): array
    {
        return [
            'Content-Type' => 'application/octet-stream',
            'Transfer-Encoding' => 'chunked',
            'Cache-Control' => 'no-cache, must-revalidate',
            'Content-Description' => $job->getDescription(),
            'Content-Disposition' => 'attachment; filename="' . $job->getFilename() . '"',
            'Content-Transfer-Encoding' => 'binary',
            'Expires' => '0',
        ];
    }

    protected function getFilename(string $prefix): string
    {
        return $prefix . '-' . (new DateTime())->format('Ymd-His') . '.csv';
    }
}
