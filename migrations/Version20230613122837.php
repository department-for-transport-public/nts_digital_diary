<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230613122837 extends AbstractMigration
{
    const LEGACY_MAP = [
        'walkthrough' => 'interviewer-dashboard',
        'onboarding' => 'onboarding-practice',
    ];

    public function getDescription(): string
    {
        return 'migrate existing training records to match new module names';
    }

    public function up(Schema $schema): void
    {
        foreach (self::LEGACY_MAP as $oldName => $newName) {
            $this->changeModuleName($newName, $oldName);
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::LEGACY_MAP as $oldName => $newName) {
            $this->changeModuleName($oldName, $newName);
        }
    }

    protected function changeModuleName($to, $from)
    {
        $this->addSql("UPDATE interviewer_training_record SET module_name=? WHERE module_name=?", [$to, $from]);
    }
}
