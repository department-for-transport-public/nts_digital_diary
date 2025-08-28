<?php

declare(strict_types=1);

namespace App\Controller\Admin\Feedback;

use App\Entity\Feedback\ClosedFeedbackNoteView;
use App\Entity\Feedback\Message;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[Route('/feedback/stats', name: 'feedback_stats_')]
class StatsController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected ChartBuilderInterface $chartBuilder,
    ) {}

    #[Route(name: 'index')]
    public function index(): Response
    {
        $periods = $this->getPeriods();
        $supportData = $this->compileStats($periods, 'support');
        $feedbackData = $this->compileStats($periods, 'feedback');

        return $this->render('/admin/feedback/stats/index.html.twig', [
            'support' => [
                'chart' => $this->createChart($periods, $supportData['new'], $supportData['closed']),
                'messagesByState' => $supportData['states'],
            ],
            'feedback' => [
                'chart' => $this->createChart($periods, $feedbackData['new'], $feedbackData['closed']),
                'messagesByState' => $feedbackData['states'],
            ],
        ]);
    }

    protected function createChart(array $periods, array $newStats, array $closedStats): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);

        $chart->setData([
            'labels' => array_map(
                fn($x) => substr("$x", 0, 4) . ' / ' . substr("$x", 4, 2),
                $periods,
            ),
            'datasets' => [
                [
                    'label' => 'New messages',
                    'backgroundColor' => '#f499be',
                    'borderColor' => '#f499be',
                    'data' => array_values($newStats),
                ],
                [
                    'label' => 'Closed messages',
                    'backgroundColor' => '#5694ca',
                    'borderColor' => '#1d70b8',
                    'data' => array_values($closedStats),
                ],
            ],
        ]);

        $chart->setOptions([
            'scales' => [
                'y' => [
                    'title' => ['display' => true, 'text' => 'Number of messages'],
                ],
                'x' => [
                    'title' => ['display' => true, 'text' => 'Year / month']
                ],
            ],
        ]);

        return $chart;
    }

    protected function getPeriods(): array
    {
        $periods = [];
        $period = new DateTime();
        while (count($periods) < 12) {
            $periods[] = $period->format('Ym');
            $period->modify('-1 month');
        }
        return array_reverse($periods);
    }

    protected function compileStats(array $periods, string $category): array
    {

        $indexedNewStats = $indexedClosedStats = array_fill_keys($periods, 0);
        $newMessageStats = $this->getNewMessageStats($periods, $category);
        array_walk($newMessageStats, function (&$stat) use (&$indexedNewStats) {
            $indexedNewStats[$stat['period']] = $stat['cnt'];
        });

        $closedMessageStats = $this->getClosedMessageStats($periods, $category);
        array_walk($closedMessageStats, function (&$stat) use (&$indexedClosedStats) {
            $indexedClosedStats[$stat['period']] = $stat['cnt'];
        });

        $messageStates = [Message::STATE_ASSIGNED, Message::STATE_IN_PROGRESS, Message::STATE_NEW];
        $messagesByState = $this->em->createQueryBuilder()
            ->select('m.state, count(m.id) as cnt')
            ->from(Message::class, 'm')
            ->where('m.state IN (:states)')
            ->setParameter('states', $messageStates)
            ->andWhere('m.category = :category')
            ->setParameter('category', $category)
            ->groupBy('m.state')
            ->getQuery()
            ->execute();
        $messagesByStateIndexed = array_fill_keys($messageStates, 0);
        array_walk($messagesByState, function (&$stat) use (&$messagesByStateIndexed) {
            $messagesByStateIndexed[$stat['state']] = $stat['cnt'];
        });

        return [
            'states' => $messagesByStateIndexed,
            'new' => $indexedNewStats,
            'closed' => $indexedClosedStats
        ];
    }

    protected function getNewMessageStats(array $periods, string $category): array
    {
        return $this->em->createQueryBuilder()
            ->select("date_format(f.sent, '%Y%m') as period, count(f.id) as cnt")
            ->from(Message::class, 'f')
            ->where("date_format(f.sent, '%Y%m') in (:periods)")
            ->setParameter('periods', $periods)
            ->andWhere('f.category = :category')
            ->setParameter('category', $category)
            ->orderBy('period', 'ASC')
            ->groupBy('period')
            ->getQuery()
            ->execute()
        ;
    }

    protected function getClosedMessageStats(array $periods, string $category): array
    {
        return $this->em->createQueryBuilder()
            ->select("date_format(n.createdAt, '%Y%m') as period, count(n.message) as cnt")
            ->from(ClosedFeedbackNoteView::class, 'n')
            ->leftJoin('n.message', 'm')
            ->where("date_format(n.createdAt, '%Y%m') in (:periods)")
            ->setParameter('periods', $periods)
            ->andWhere('m.category = :category')
            ->setParameter('category', $category)
            ->orderBy('period', 'ASC')
            ->groupBy('period')
            ->getQuery()
            ->execute();
    }
}
