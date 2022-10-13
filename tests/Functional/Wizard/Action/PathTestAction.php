<?php

namespace App\Tests\Functional\Wizard\Action;

class PathTestAction extends AbstractAction
{
    const OPTION_EXPECTED_PATH_REGEX = 'path-is-regex';

    protected string $expectedPath;
    protected array $options;

    public function __construct(string $expectedPath, array $options = [])
    {
        $this->expectedPath = $expectedPath;
        $this->options = $options;
    }

    public function getExpectedPath(): string
    {
        return $this->expectedPath;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function perform(Context $context): void
    {
        $this->outputDebugHeader($context);
        $expectedPath = $this->getResolvedExpectedPath($context);

        $isExpectedPathRegex = $this->isExpectedPathRegex();
        $this->outputPathDebug($context, $expectedPath, $isExpectedPathRegex);

        $context->getTestCase()->assertPathMatches(
            $expectedPath,
            $isExpectedPathRegex
        );
    }

    protected function isExpectedPathRegex(): bool
    {
        return boolval($this->getOptions()[PathTestAction::OPTION_EXPECTED_PATH_REGEX] ?? false);
    }

    protected function getResolvedExpectedPath(Context $context): string
    {
        return $context->getConfig('basePath') . $this->getExpectedPath();
    }

    protected function outputPathDebug(Context $context, string $expectedPath, bool $isExpectedPathRegex, string $prefix='--'): void
    {
        if ($this->isAtLeastDebugLevel($context, 2)) {
            $paddedPrefix = str_pad($prefix, 2);
            $regexFlag = $isExpectedPathRegex ? ' <info>(REGEX)</info>':'';
            $context->getOutput()->writeln("  {$paddedPrefix} <comment>Check path matches:</comment> {$expectedPath}{$regexFlag}\n");
        }
    }
}