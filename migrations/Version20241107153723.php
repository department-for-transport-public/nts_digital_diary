<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241107153723 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'change to interviewer training module names';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE interviewer_training_record SET module_name = 'manual-onboarding-introduction' WHERE module_name = 'onboarding-introduction'");
        $this->addSql("UPDATE interviewer_training_record SET module_name = 'manual-onboarding-practice' WHERE module_name = 'onboarding-practice'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE interviewer_training_record SET module_name = 'onboarding-introduction' WHERE module_name = 'manual-onboarding-introduction'");
        $this->addSql("UPDATE interviewer_training_record SET module_name = 'onboarding-practice' WHERE module_name = 'manual-onboarding-practice'");
        $this->addSql("DELETE FROM interviewer_training_record WHERE module_name IN ('direct-onboarding-introduction', 'approve-and-submit')");
    }
}
