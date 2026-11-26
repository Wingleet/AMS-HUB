<?php

namespace App\Catalog;

/**
 * Single source of truth for the AMS applications listed by the hub.
 *
 * Consumed by App\Command\LoadApplicationsCommand (prod seeding) and by
 * App\DataFixtures\DevFixtures (dev seeding) so both stay in sync. Both go
 * through entries(), never APPLICATIONS directly, because it adds sortOrder.
 *
 * The order of the entries IS the order of the tiles on the launcher: each
 * entry's sortOrder is derived from its position here (see entries()). The
 * launcher order is iPlanning, iDeck, iTech, iKanban, iScan, iAsset,
 * iCustomer, iSDR, iDismantling (HUB card #1, 2026-11-24).
 *
 * `url` is null for an application that is not hosted anywhere yet; such an
 * entry is seeded with isActive = false so it never renders as a dead link.
 * `databaseName` is informational only (shown in EasyAdmin) and is null for the
 * applications that own no database and read straight from the AMS REST API.
 * `iconUrl` points at a logo served by the hub (frontend/public/app-logos/) or
 * at an absolute URL. The launcher currently ignores it — AppMark shows the
 * one AMS logo on every tile, by design — so it is kept only for EasyAdmin and
 * for the day per-application logos come back.
 */
final class ApplicationCatalog
{
    /**
     * Gap between two consecutive sortOrder values, so an admin can slot an
     * application between two others in EasyAdmin without renumbering.
     */
    public const SORT_ORDER_STEP = 10;

    public const APPLICATIONS = [
        // ---- On the launcher, in this order --------------------------------
        [
            'name' => 'iPlanning',
            'description' => 'HR & Skills Planning Tool',
            'url' => 'https://staging-iplanning.icare-ams.fr',
            'iconUrl' => '/app-logos/icare-ams.png',
            'databaseName' => null,
            'isActive' => true,
        ],
        [
            'name' => 'iDeck',
            'description' => 'Work Package Scheduling & Hangar Deck Planning',
            'url' => 'https://staging-ideck.icare-ams.fr',
            'iconUrl' => '/app-logos/icare-ams.png',
            'databaseName' => null,
            'isActive' => true,
        ],
        [
            'name' => 'iTech',
            'description' => 'Mechanic Workbench — Job Cards and Time Booking',
            'url' => 'https://staging-itech.icare-ams.fr',
            'iconUrl' => '/app-logos/icare-ams.png',
            'databaseName' => null,
            'isActive' => true,
        ],
        [
            'name' => 'iKanban',
            'description' => 'Visual Task Tracking and Workflow Management Platform',
            'url' => 'https://staging-ikanban.icare-ams.fr',
            'iconUrl' => '/app-logos/icare-ams.png',
            'databaseName' => null,
            'isActive' => true,
        ],
        [
            // An Android app for the store's barcode-scanner handhelds: the
            // tile opens its download page (release APK + install steps), the
            // only web address iScan has.
            'name' => 'iScan',
            'description' => 'Store Barcode Scanner (Android) — Bin Moves, Receptions, Issues & Stock Take',
            'url' => 'https://iscan.icare-ams.fr',
            'iconUrl' => '/app-logos/icare-ams.png',
            'databaseName' => null,
            'isActive' => true,
        ],
        [
            'name' => 'iAsset',
            'description' => 'Asset, Tooling & GSE Inventory',
            'url' => 'https://staging-iasset.icare-ams.fr',
            'iconUrl' => '/app-logos/icare-ams.png',
            'databaseName' => null,
            'isActive' => true,
        ],
        [
            'name' => 'iCustomer',
            'description' => 'Customer Portal & MRO Request Tracking',
            'url' => 'https://staging-icustomer.icare-ams.fr',
            'iconUrl' => '/app-logos/icustomer.png',
            'databaseName' => null,
            'isActive' => true,
        ],
        [
            'name' => 'iSDR',
            'description' => 'Aircraft Maintenance & SDR Management Platform',
            'url' => 'https://staging-isdr.icare-ams.fr',
            'iconUrl' => '/app-logos/isdr.png',
            'databaseName' => 'app_isdr_prod',
            'isActive' => true,
        ],
        [
            'name' => 'iDismantling',
            'description' => 'Dismantling Management Platform',
            'url' => 'https://staging-idismantling.icare-ams.fr',
            'iconUrl' => '/app-logos/idismantling.png',
            'databaseName' => 'app_idismantling_prod',
            'isActive' => true,
        ],

        // ---- Deployed, but deliberately hidden from the launcher ------------
        // All three run on the AMC server and answer over HTTPS at the
        // addresses in the comments below; their tile is simply not wanted on
        // the home page. The home page filters on `url`, not on `isActive`: an
        // inactive entry still renders a tile carrying an "Inactive" badge, so
        // only a null url actually removes it. The live address is kept here so
        // restoring a tile is a one-line change. iPurchase and iQuality were
        // taken off on HUB card #1 (2026-11-24).
        [
            'name' => 'iPurchase',
            'description' => 'Purchasing & Supplier Order Management',
            'url' => null, // live on https://staging-ipurchase.icare-ams.fr
            'iconUrl' => '/app-logos/icare-ams.png',
            'databaseName' => null,
            'isActive' => false,
        ],
        [
            'name' => 'iQuality',
            'description' => 'Quality Control & Compliance Platform',
            'url' => null, // live on https://staging-iquality.icare-ams.fr
            'iconUrl' => '/app-logos/icare-ams.png',
            'databaseName' => 'app_iquality_prod',
            'isActive' => false,
        ],
        [
            'name' => 'iALB',
            'description' => 'Aircraft Log Book — Legs, Crews and Tech Situation',
            'url' => null, // live on https://staging-ialb.icare-ams.fr
            'iconUrl' => '/app-logos/icare-ams.png',
            'databaseName' => null,
            'isActive' => false,
        ],

        // ---- Built, not hosted yet ------------------------------------------
        [
            'name' => 'iDashboard',
            'description' => 'AMS Operational Dashboard & Live Indicators',
            'url' => null,
            'iconUrl' => null,
            'databaseName' => null,
            'isActive' => false,
        ],

        // ---- Roadmap placeholders, no code yet ------------------------------
        [
            'name' => 'iARC',
            'description' => 'ARC Compliance & Certification Platform',
            'url' => null,
            'iconUrl' => null,
            'databaseName' => null,
            'isActive' => false,
        ],
        [
            'name' => 'iInventory',
            'description' => 'Parts & Inventory Management System',
            'url' => null,
            'iconUrl' => null,
            'databaseName' => null,
            'isActive' => false,
        ],
        [
            'name' => 'iReporting',
            'description' => 'Advanced Analytics & Reporting Suite',
            'url' => null,
            'iconUrl' => null,
            'databaseName' => null,
            'isActive' => false,
        ],
        [
            'name' => 'iTraining',
            'description' => 'Training & Certification Management',
            'url' => null,
            'iconUrl' => null,
            'databaseName' => null,
            'isActive' => false,
        ],
        [
            'name' => 'iDocumentation',
            'description' => 'Document Management & Control System',
            'url' => null,
            'iconUrl' => null,
            'databaseName' => null,
            'isActive' => false,
        ],

        // ---- The hub itself --------------------------------------------------
        // Kept as a row so subscriptions and SSO settings have something to
        // point at, but deliberately without a URL: this *is* the hub, and a
        // launcher tile linking back to the page you are already on is noise.
        // The home page lists only entries that have somewhere to go.
        [
            'name' => 'SSO_App',
            'description' => 'Central Single Sign-On Authentication Application',
            'url' => null,
            'iconUrl' => null,
            'databaseName' => null,
            'isActive' => true,
        ],
    ];

    /**
     * The catalog with each entry's sortOrder, derived from its position:
     * 10 for the first entry, 20 for the second, and so on.
     *
     * @return list<array{name: string, description: string, url: ?string, iconUrl: ?string, databaseName: ?string, isActive: bool, sortOrder: int}>
     */
    public static function entries(): array
    {
        $entries = [];
        foreach (array_values(self::APPLICATIONS) as $index => $entry) {
            $entries[] = $entry + ['sortOrder' => ($index + 1) * self::SORT_ORDER_STEP];
        }

        return $entries;
    }
}
