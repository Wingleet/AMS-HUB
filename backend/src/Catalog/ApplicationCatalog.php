<?php

namespace App\Catalog;

/**
 * Single source of truth for the AMS applications listed by the hub.
 *
 * Consumed by App\Command\LoadApplicationsCommand (prod seeding) and by
 * App\DataFixtures\DevFixtures (dev seeding) so both stay in sync.
 *
 * `url` is null for an application that is not hosted anywhere yet; such an
 * entry is seeded with isActive = false so it never renders as a dead link.
 * `databaseName` is informational only (shown in EasyAdmin) and is null for the
 * applications that own no database and read straight from the AMS REST API.
 * `iconUrl` points at a logo served by the hub (frontend/public/app-logos/) or
 * at an absolute URL; null falls back to the generated mark in AppMark.tsx.
 *
 * The logos are each application's own asset, copied from its repository and
 * downscaled to 128px. Only three are actually distinct — iSDR (magnifier),
 * iCustomer (customer figure) and iDismantling (crane and stripped fuselage);
 * iDeck, iPlanning, iQuality, iKanban, iALB, iTech and iAsset all ship the plain
 * shared iCare AMS artwork, so their tiles are identical by design, not by
 * oversight. Drop a dedicated file in app-logos/ and point the entry at it to
 * fix that.
 */
final class ApplicationCatalog
{
    public const APPLICATIONS = [
        // ---- Hosted on the AMC server (icare-ams.fr) ------------------------
        [
            'name' => 'iSDR',
            'description' => 'Aircraft Maintenance & SDR Management Platform',
            'url' => 'https://staging-isdr.icare-ams.fr',
            'iconUrl' => '/app-logos/isdr.png',
            'databaseName' => 'app_isdr_prod',
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
            'name' => 'iPlanning',
            'description' => 'HR & Skills Planning Tool',
            'url' => 'https://staging-iplanning.icare-ams.fr',
            'iconUrl' => '/app-logos/icare-ams.png',
            'databaseName' => null,
            'isActive' => true,
        ],
        [
            'name' => 'iQuality',
            'description' => 'Quality Control & Compliance Platform',
            'url' => 'https://quality.wingleet.tech',
            'iconUrl' => '/app-logos/icare-ams.png',
            'databaseName' => 'app_iquality_prod',
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
            'name' => 'iAsset',
            'description' => 'Asset, Tooling & GSE Inventory',
            'url' => 'https://staging-iasset.icare-ams.fr',
            'iconUrl' => '/app-logos/icare-ams.png',
            'databaseName' => null,
            'isActive' => true,
        ],

        // ---- Also on the AMC server (icare-ams.fr) --------------------------
        [
            'name' => 'iDismantling',
            'description' => 'Dismantling Management Platform',
            'url' => 'https://staging-idismantling.icare-ams.fr',
            'iconUrl' => '/app-logos/idismantling.png',
            'databaseName' => 'app_idismantling_prod',
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
            'name' => 'iPurchase',
            'description' => 'Purchasing & Supplier Order Management',
            'url' => 'https://staging-ipurchase.icare-ams.fr',
            'iconUrl' => '/app-logos/icare-ams.png',
            'databaseName' => null,
            'isActive' => true,
        ],

        // ---- Deployed, but deliberately hidden from the launcher ------------
        // These two run on the VPS and answer over HTTPS, but their tile is not
        // wanted on the home page yet. The home page filters on `url`, not on
        // `isActive`: an inactive entry still renders a tile carrying an
        // "Inactive" badge, so only a null url actually removes it. The live
        // address is kept here so restoring a tile is a one-line change.
        [
            'name' => 'iALB',
            'description' => 'Aircraft Log Book — Legs, Crews and Tech Situation',
            'url' => null, // live on https://ialb.wingleet.tech (VPS port 8085)
            'iconUrl' => '/app-logos/icare-ams.png',
            'databaseName' => null,
            'isActive' => false,
        ],
        [
            'name' => 'iTech',
            'description' => 'Mechanic Workbench — Job Cards and Time Booking',
            'url' => null, // live on https://itech.wingleet.tech (VPS port 8091)
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
}
