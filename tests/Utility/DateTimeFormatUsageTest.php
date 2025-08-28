<?php

namespace App\Tests\Utility;

use RecursiveDirectoryIterator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;
use Twig\Token;

class DateTimeFormatUsageTest extends KernelTestCase
{
    protected Environment $twig;
    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::$kernel->getContainer();

        $twig = $container->get('app.test.twig');

        $this->assertInstanceOf(Environment::class, $twig);
        $this->twig = $twig;
    }

    // This test is designed to find usages of the date filter that don't utilise date_time_format, and error
    // if it finds them, as those dates would then not be switched according to the user's 12/24-hour preferences.
    //
    // Good:
    // - day.date | date(date_time_format.date('with-dow'))
    // Bad:
    // - day.date | date(something)
    // - day.date | date('Y-m-d')
    public function testTemplates(): void
    {
        $loader = $this->twig->getLoader();
        $paths = $this->getPathsFromLoader($loader);

        $errors = [];

        foreach($this->getTemplatesFromPaths($paths, ['travel_diary', 'interviewer']) as $template) {
            $source = $loader->getSourceContext($template);
            $tokens = $this->twig->tokenize($source);

            while(!$tokens->isEOF()) {
                $token = $tokens->next();

                if ($token->getValue() !== '|' || !$token->test(Token::OPERATOR_TYPE)) {
                    continue;
                }

                $token = $tokens->next();
                if ($token->getValue() !== 'date' || !$token->test(Token::NAME_TYPE)) {
                    // Not a date filter
                    continue;
                }

                $token = $tokens->next();

                if ($token->getValue() !== '(' || !$token->test(Token::OPERATOR_TYPE)) {
                    // Not a macro call, somehow?
                    continue;
                }

                $token = $tokens->next();
                if ($token->getValue() === 'date_time_format' && $token->test(Token::NAME_TYPE)) {
                    // Success - this is what we want to see
                    continue;
                }

                $line = $token->getLine();
                $errors[] = "- {$template}, line {$line}";
            }
        }

        $this->assertEmpty($errors, "Bad date filter usage at:\n".join("\n", $errors));
    }



    protected function getTemplatesFromPaths(array $paths, array $subPathsToSearch): \Generator
    {
        foreach($paths as $path) {
            foreach($subPathsToSearch as $subPath) {
                $resolvedPath = "{$path}/{$subPath}/";
                $offset = strlen((string) $path) + 1;

                if (!is_dir($resolvedPath)) {
                    continue;
                }

                $it = new RecursiveDirectoryIterator($resolvedPath);
                foreach(new \RecursiveIteratorIterator($it) as $file) {
                    if ($file->getExtension() === 'twig') {
                        yield substr((string) $file->getPathname(), $offset);
                    }
                }
            }
        }
    }

    protected function getPathsFromLoader(LoaderInterface $loader): array
    {
        $paths = [];

        if ($loader instanceof ChainLoader) {
            foreach($loader->getLoaders() as $loader) {
                $paths = array_merge($paths, $this->getPathsFromLoader($loader));
            }
        } elseif ($loader instanceof FilesystemLoader) {
            $paths = $loader->getPaths();
        }

        return $paths;
    }
}
