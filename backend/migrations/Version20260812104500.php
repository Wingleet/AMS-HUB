<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Records the AMS database each account last signed in to, so the hub header
 * can show it. Kept on the user rather than in the JWT so it survives a token
 * refresh without changing the token format.
 */
final class Version20260812104500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ams_server_db to User';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD ams_server_db VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP ams_server_db');
    }
}
