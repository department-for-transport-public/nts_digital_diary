<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240108135521 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Reverse the owning side of the DK<->survey relationship';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE diary_keeper ADD satisfaction_survey_id VARCHAR(26) DEFAULT NULL');
        $this->addSql('ALTER TABLE diary_keeper ADD CONSTRAINT FK_3083496ABE181A2B FOREIGN KEY (satisfaction_survey_id) REFERENCES satisfaction_survey (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3083496ABE181A2B ON diary_keeper (satisfaction_survey_id)');

        // select all surveys
        $surveys = $this->connection
            ->executeQuery('select id, diarykeeper_id from satisfaction_survey')
            ->fetchAllAssociative();

        // update the satisfaction_survey_id
        foreach ($surveys as $survey) {
            $this->addSql(
                'update diary_keeper set satisfaction_survey_id = :surveyId where id = :diaryKeeperId',
                ['surveyId' => $survey['id'], 'diaryKeeperId' => $survey['diarykeeper_id']]
            );
        }

        $this->addSql('ALTER TABLE satisfaction_survey DROP FOREIGN KEY FK_F9AA62FB4F3C9F69');
        $this->addSql('DROP INDEX UNIQ_F9AA62FB4F3C9F69 ON satisfaction_survey');
        $this->addSql('ALTER TABLE satisfaction_survey DROP diarykeeper_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE satisfaction_survey ADD diarykeeper_id VARCHAR(26) DEFAULT NULL');
        $this->addSql('ALTER TABLE satisfaction_survey ADD CONSTRAINT FK_F9AA62FB4F3C9F69 FOREIGN KEY (diarykeeper_id) REFERENCES diary_keeper (id) ON UPDATE NO ACTION ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F9AA62FB4F3C9F69 ON satisfaction_survey (diarykeeper_id)');

        // get all DKs with a survey
        $diaries = $this->connection
            ->executeQuery('select id, satisfaction_survey_id from diary_keeper where satisfaction_survey_id is not null')
            ->fetchAllAssociative();

        // update surveys with DK id
        foreach ($diaries as $diary)
        $this->addSql(
            'update satisfaction_survey set diarykeeper_id = :diaryKeeperId where id=:surveyId',
            ['diaryKeeperId' => $diary['id'], 'surveyId' => $diary['satisfaction_survey_id']]
        );

        $this->addSql('ALTER TABLE diary_keeper DROP FOREIGN KEY FK_3083496ABE181A2B');
        $this->addSql('DROP INDEX UNIQ_3083496ABE181A2B ON diary_keeper');
        $this->addSql('ALTER TABLE diary_keeper DROP satisfaction_survey_id');
    }
}
