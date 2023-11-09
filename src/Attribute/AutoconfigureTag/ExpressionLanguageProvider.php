<?php

namespace App\Attribute\AutoconfigureTag;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;


#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class ExpressionLanguageProvider extends Autoconfigure
{
    public const SECURITY = 'security';
    public const WORKFLOW = 'workflow';
    public const ROUTER = 'router';
    public const VALIDATOR = 'validator';

    protected const VALID_TYPES = [
        self::ROUTER,
        self::SECURITY,
        self::WORKFLOW,
        self::VALIDATOR,
    ];


    public function __construct(string $type)
    {
        if (!in_array($type, self::VALID_TYPES)) {
            throw new \RuntimeException('unexpected expression language type');
        }
        parent::__construct(tags: ["$type.expression_language_provider"]);
    }
}