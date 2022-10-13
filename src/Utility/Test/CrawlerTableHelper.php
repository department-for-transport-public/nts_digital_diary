<?php

namespace App\Utility\Test;

use Symfony\Component\DomCrawler\Crawler;

class CrawlerTableHelper
{
    protected Crawler $crawler;
    protected array $headers;
    protected string $tableXpath;

    public function __construct(Crawler $crawler, string $tableXpath="//table[contains(concat(' ',normalize-space(@class),' '),' govuk-table ')]")
    {
        $this->crawler = $crawler;
        $this->tableXpath = $tableXpath;
        $this->headers = $crawler->filterXPath($this->tableXpath . "/thead/tr/th")->each(fn(Crawler $node, $i) => $node->text());
    }

    public function getTableHtml(): string
    {
        return $this->crawler->filterXPath($this->tableXpath)->html();
    }

    protected function getRowDataForColumn(array $rowData, string $columnName): ?string
    {
        $key = array_search($columnName, $this->headers);
        return $key === false ? null : $rowData[$key];
    }

    protected function rowMatchesSearchTerms(array $rowData, array $searchTerms): bool
    {
        foreach($searchTerms as $searchKey => $searchValue) {
            if (strval($searchValue) !== $this->getRowDataForColumn($rowData, $searchKey)) {
                return false;
            }
        }

        return true;
    }

    public function getMatchingTableRow(array $searchTerms): ?array {
        foreach($this->crawler->filterXPath($this->tableXpath . "/tbody/tr") as $row) {
            $rowData = (new Crawler($row))->filter('th,td')->each(fn(Crawler $node, $j) => $node->html());

            if ($this->rowMatchesSearchTerms($rowData, $searchTerms)) {
                return $rowData;
            }
        }

        return null;
    }

    /**
     * If exactMatch is true, the target linkText must exactly match the text in the link.
     * If exactMatch is false, the text in the link must merely CONTAIN the target linkText.
     */
    public function getLinkUrl(array $rowData, string $linkText, bool $exactMatch=true): ?string {
        $linksColumn = $this->getRowDataForColumn($rowData, 'Action links');
        $crawler = new Crawler($linksColumn);

        $anchors = $crawler->filter('a');
        foreach($anchors as $anchor) {
            $anchorCrawler = new Crawler($anchor);
            $anchorText = $anchorCrawler->text();

            if (($exactMatch && $anchorText === $linkText) || (!$exactMatch && str_contains($anchorText, $linkText))) {
                return $anchorCrawler->attr('href');
            }
        }

        return null;
    }

    public function getLinkUrlForRowMatching(string $linkText, array $searchTerms, bool $exactMatch=true): ?string
    {
        $rowData = $this->getMatchingTableRow($searchTerms);
        return $rowData === null ? null : $this->getLinkUrl($rowData, $linkText, $exactMatch);
    }
}