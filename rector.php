<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/bundles',
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php83: true)
    ->withSets([
        \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_COLLECTION_22,
        \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_COMMON_20,
        \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_DBAL_30,
        \Rector\Doctrine\Set\DoctrineSetList::DOCTRINE_ORM_29,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_CODE_QUALITY,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_30,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_31,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_32,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_33,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_34,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_40,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_41,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_42,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_43,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_44,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_50,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_51,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_52,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_53,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_54,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_61,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_62,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_63,
        \Rector\Symfony\Set\SymfonySetList::SYMFONY_64,
    ])
    ->withRules([
    ])
    ->withSkip([
        \Rector\Php80\Rector\Switch_\ChangeSwitchToMatchRector::class => [
            __DIR__ . '/src/Entity/User.php', // Rector incorrectly refactors the switch, ultimately removing the $this->preferences assignment
        ],
        \Rector\Symfony\Symfony61\Rector\Class_\CommandConfigureToAttributeRector::class => [
            __DIR__ . '/src/Command/GcpDatabaseTools/AbstractGcpDatabaseCommand.php', // Rector incorrectly adds the attribute to this *abstract* class
        ],

        \Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector::class,
        \Rector\Php83\Rector\ClassConst\AddTypeToConstRector::class,
    ])
    ->withTypeCoverageLevel(47)
    ->withDeadCodeLevel(44);
