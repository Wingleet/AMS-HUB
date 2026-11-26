<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Launcher line-up of HUB card #1 (2026-11-24): iPurchase and iQuality off,
 * iTech and iScan on, and the tiles in a fixed order — iPlanning, iDeck, iTech,
 * iKanban, iScan, iAsset, iCustomer, iSDR, iDismantling.
 *
 * Until now the launcher drew the rows in whatever order the database returned
 * them (in practice insertion order), so the order needs a column of its own:
 * application.sort_order, which the API sorts on.
 *
 * The rows are changed here, not only in App\Catalog\ApplicationCatalog,
 * because a deployment runs the migrations but never app:load-applications:
 * a catalog-only change would not reach the dev, staging or production
 * database. The values below are a frozen copy of the catalog at this version;
 * running app:load-applications afterwards finds every row already up to date.
 */
final class Version20261126120000 extends AbstractMigration
{
    /** Catalog order at this version, 10 apart (ApplicationCatalog::SORT_ORDER_STEP). */
    private const SORT_ORDER = [
        'iPlanning' => 10,
        'iDeck' => 20,
        'iTech' => 30,
        'iKanban' => 40,
        'iScan' => 50,
        'iAsset' => 60,
        'iCustomer' => 70,
        'iSDR' => 80,
        'iDismantling' => 90,
        'iPurchase' => 100,
        'iQuality' => 110,
        'iALB' => 120,
        'iDashboard' => 130,
        'iARC' => 140,
        'iInventory' => 150,
        'iReporting' => 160,
        'iTraining' => 170,
        'iDocumentation' => 180,
        'SSO_App' => 190,
    ];

    private const ITECH_URL = 'https://staging-itech.icare-ams.fr';
    private const ISCAN_URL = 'https://iscan.icare-ams.fr';
    private const ISCAN_DESCRIPTION = 'Store Barcode Scanner (Android) — Bin Moves, Receptions, Issues & Stock Take';
    private const IPURCHASE_URL = 'https://staging-ipurchase.icare-ams.fr';
    private const IQUALITY_URL = 'https://staging-iquality.icare-ams.fr';

    public function getDescription(): string
    {
        return 'Add application.sort_order; launcher: hide iPurchase and iQuality, show iTech and iScan, order the tiles';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE application ADD sort_order INT DEFAULT 0 NOT NULL');

        // iScan is new to the hub; iTech has been in the catalog since
        // September, but a database seeded before that would lack it.
        $this->insertIfMissing('iTech', 'Mechanic Workbench — Job Cards and Time Booking', self::ITECH_URL);
        $this->insertIfMissing('iScan', self::ISCAN_DESCRIPTION, self::ISCAN_URL);

        // On the launcher. A null url is what takes a tile off (the home page
        // filters on it), hence the url is the switch, isActive its companion.
        $this->addSql(
            'UPDATE application SET url = :url, is_active = true, updated_at = NOW() WHERE name = :name',
            ['url' => self::ITECH_URL, 'name' => 'iTech'],
        );
        $this->addSql(
            'UPDATE application SET url = :url, description = :description, is_active = true, updated_at = NOW() WHERE name = :name',
            ['url' => self::ISCAN_URL, 'description' => self::ISCAN_DESCRIPTION, 'name' => 'iScan'],
        );

        // Off the launcher; both stay deployed and reachable at their address.
        $this->addSql(
            "UPDATE application SET url = NULL, is_active = false, updated_at = NOW() WHERE name IN ('iPurchase', 'iQuality')",
        );

        foreach (self::SORT_ORDER as $name => $sortOrder) {
            $this->addSql(
                'UPDATE application SET sort_order = :sortOrder WHERE name = :name',
                ['sortOrder' => $sortOrder, 'name' => $name],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            'UPDATE application SET url = :url, is_active = true, updated_at = NOW() WHERE name = :name',
            ['url' => self::IPURCHASE_URL, 'name' => 'iPurchase'],
        );
        $this->addSql(
            'UPDATE application SET url = :url, is_active = true, updated_at = NOW() WHERE name = :name',
            ['url' => self::IQUALITY_URL, 'name' => 'iQuality'],
        );
        // iTech goes back to hidden. iScan is hidden rather than deleted: a
        // subscription or an SSO code may point at it by now, and deleting the
        // row would fail on, or cascade through, those foreign keys.
        $this->addSql(
            "UPDATE application SET url = NULL, is_active = false, updated_at = NOW() WHERE name IN ('iTech', 'iScan')",
        );

        $this->addSql('ALTER TABLE application DROP sort_order');
    }

    private function insertIfMissing(string $name, string $description, string $url): void
    {
        $this->addSql(
            'INSERT INTO application (name, description, url, icon_url, database_name, is_active, created_at, sort_order)
             SELECT :name, :description, :url, :iconUrl, NULL, true, NOW(), :sortOrder
             WHERE NOT EXISTS (SELECT 1 FROM application WHERE name = :name)',
            [
                'name' => $name,
                'description' => $description,
                'url' => $url,
                'iconUrl' => '/app-logos/icare-ams.png',
                'sortOrder' => self::SORT_ORDER[$name],
            ],
        );
    }
}
