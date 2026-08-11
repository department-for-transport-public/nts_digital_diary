<?php

namespace App\ListPage\Admin;

use App\Entity\DiaryKeeper;
use App\Entity\Household;
use App\Repository\Metrics\MetricsLogRepository;
use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkCoreBundle\ListPage\AbstractListPage;
use Ghost\GovUkCoreBundle\ListPage\Field\ChoiceFilter;
use Ghost\GovUkCoreBundle\ListPage\Field\Simple;
use Ghost\GovUkCoreBundle\ListPage\Field\TextFilter;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Routing\RouterInterface;

class MetricsLogListPage extends AbstractListPage
{
    private Household $household;

    public function __construct(
        protected readonly MetricsLogRepository $repository,
        FormFactoryInterface                    $formFactory,
        RouterInterface                         $router,
    ) {
        parent::__construct($formFactory, $router);
    }

    public function setHousehold(Household $household): self
    {
        $this->household = $household;
        return $this;
    }

    protected function getFieldsDefinition(): array
    {
        $events = $this->repository->findDistinctEventsForHousehold($this->household);

        return [
            (new Simple('Time', 'm.createdAt'))->sortable(),
            new ChoiceFilter('Diary serial', 'm.diarySerial', $this->getDiarySerialChoices()),
            new ChoiceFilter('Event', 'm.event', array_combine($events, $events)),
            new TextFilter('User serial', 'm.userSerial'),
            new Simple('Browser'),
            new Simple('Metadata'),
        ];
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        return $this->repository->createHouseholdMetricsQueryBuilder($this->household);
    }

    protected function getDefaultOrder(): array
    {
        return [
            Simple::generateId('Time') => 'ASC',
        ];
    }

    /**
     * @return array<string, string> label => diary serial value
     */
    private function getDiarySerialChoices(): array
    {
        $householdSerial = $this->household->getSerialNumber(padAddressPart: true, spacesBetweenParts: false);

        $choices = ["{$householdSerial} (household)" => $householdSerial];

        foreach ($this->household->getDiaryKeepers() as $diaryKeeper) {
            /** @var DiaryKeeper $diaryKeeper */
            $serial = $diaryKeeper->getSerialNumber(spacesBetweenParts: false);
            $choices["/{$diaryKeeper->getNumber()} – {$diaryKeeper->getName()}"] = $serial;
        }

        return $choices;
    }
}
