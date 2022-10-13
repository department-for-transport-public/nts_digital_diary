<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20220222111120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update transport method ordering';
    }

    public function getMapping(): array
    {
        $addParameterNames = fn(array $array): array => [
            'descriptionTranslationKey' => $array[0],
            'currentSort' => $array[1],
            'targetSort' => $array[2],
        ];

        return [
            $addParameterNames(['walk',             1, 1]),
            $addParameterNames(['car',              6, 2]),
            $addParameterNames(['bicycle',          2, 3]),
            $addParameterNames(['motorcycle',       7, 4]),
            $addParameterNames(['van-or-lorry',     8, 5]),
            $addParameterNames(['e-bike',           3, 6]),
            $addParameterNames(['e-scooter',        4, 7]),
            $addParameterNames(['mobility-scooter', 5, 8]),
        ];
    }

    public function up(Schema $schema): void
    {
        foreach($this->getMapping() as $parameters) {
            $this->addSql('UPDATE method SET sort=:targetSort WHERE description_translation_key=:descriptionTranslationKey AND sort=:currentSort', $parameters);
        }
    }

    public function down(Schema $schema): void
    {
        foreach($this->getMapping() as $parameters) {
            $this->addSql('UPDATE method SET sort=:currentSort WHERE description_translation_key=:descriptionTranslationKey AND sort=:targetSort', $parameters);
        }
    }
}
