<?php

namespace Ghost\GovUkFrontendBundle\Twig\Node;

use Twig\Compiler;
use Twig\Node\Node;

class StrictNode extends Node
{
    public function __construct($enabled, Node $body, int $lineno, string $tag = 'strict')
    {
        parent::__construct(['body' => $body], ['enabled' => $enabled], $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $compiler->write("\$_strictVars = \$this->env->isStrictVariables();\n");

        if ($this->getAttribute('enabled')) {
            $compiler->write("\$this->env->enableStrictVariables();\n");
        } else {
            $compiler->write("\$this->env->disableStrictVariables();\n");
        }

        $compiler->subcompile($this->getNode('body'));

        $compiler->write("if (\$_strictVars) {\n");
        $compiler->write("  \$this->env->enableStrictVariables();\n");
        $compiler->write("} else {\n");
        $compiler->write("  \$this->env->disableStrictVariables();\n");
        $compiler->write("}");
    }
}