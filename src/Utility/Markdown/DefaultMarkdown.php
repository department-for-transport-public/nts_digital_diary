<?php


namespace App\Utility\Markdown;


use League\CommonMark\CommonMarkConverter;
use Twig\Extra\Markdown\LeagueMarkdown;
use Twig\Extra\Markdown\MarkdownInterface;

class DefaultMarkdown
{
    protected MarkdownInterface $converter;
    const FORM_CONFIG = [

    ];

    public function __construct()
    {
        $this->converter = new LeagueMarkdown(new CommonMarkConverter());
    }

    public function convert(string $body): string
    {
        return $this->converter->convert($body);
    }
}