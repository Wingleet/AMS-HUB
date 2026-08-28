<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Sign-in now goes through AMS, which owns the credentials and answers with a
 * token rather than a profile. An account created on first sign-in therefore
 * has no local password and no name yet, so the three columns that carried them
 * must accept null — otherwise the very first AMS sign-in fails on a not-null
 * violation.
 */
final class Version20260812073000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Allow null password, firstname and lastname for AMS-provisioned users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ALTER COLUMN password DROP NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN firstname DROP NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN lastname DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Rows created from AMS legitimately hold null in these columns, so the
        // constraint cannot simply be restored: fill them first, or the ALTER
        // fails. An empty string is the reversible placeholder.
        $this->addSql('UPDATE "user" SET password = \'\' WHERE password IS NULL');
        $this->addSql('UPDATE "user" SET firstname = \'\' WHERE firstname IS NULL');
        $this->addSql('UPDATE "user" SET lastname = \'\' WHERE lastname IS NULL');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN password SET NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN firstname SET NOT NULL');
        $this->addSql('ALTER TABLE "user" ALTER COLUMN lastname SET NOT NULL');
    }
}
