<?php

namespace Ghost\GovUkFrontendBundle\Twig\TokenParser;

use Ghost\GovUkFrontendBundle\Twig\Node\StrictNode;
use Twig\Error\SyntaxError;
use Twig\Node\Node;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

class StrictTokenParser extends AbstractTokenParser
{
    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();
        $stream = $this->parser->getStream();

        $token = $stream->expect(/* Token::NAME_TYPE */ 5);

        $map = ['true' => true, 'false' => false];
        $enabled = $map[strtolower($token->getValue())] ?? null;

        if ($enabled === null) {
            throw new SyntaxError('Strict tag expects a boolean parameter', $stream->getCurrent()->getLine(), $stream->getSourceContext());
        }

        $stream->expect(/* Token::BLOCK_END_TYPE */ 3);

        $body = $this->parser->subparse([$this, 'decideBlockEnd'], true);

        $stream->expect(/* Token::BLOCK_END_TYPE */ 3);

        return new StrictNode($enabled, $body, $lineno, $this->getTag());
    }

    public function decideBlockEnd(Token $token): bool
    {
        return $token->test('endstrict');
    }

    public function getTag(): string
    {
        return 'strict';
    }
}