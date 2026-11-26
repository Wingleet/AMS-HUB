<?php

namespace App\Tests\Catalog;

use App\Catalog\ApplicationCatalog;
use PHPUnit\Framework\TestCase;

class ApplicationCatalogTest extends TestCase
{
    /**
     * The tiles the launcher draws: the entries with a url (the home page
     * filters on it), in sortOrder order (the API sorts on it).
     *
     * @return list<string>
     */
    private static function launcherNames(): array
    {
        $shown = array_filter(ApplicationCatalog::entries(), static fn (array $entry): bool => $entry['url'] !== null);
        usort($shown, static fn (array $a, array $b): int => $a['sortOrder'] <=> $b['sortOrder']);

        return array_map(static fn (array $entry): string => $entry['name'], $shown);
    }

    public function testLauncherShowsTheApplicationsOfHubCardOneInItsOrder(): void
    {
        $this->assertSame(
            ['iPlanning', 'iDeck', 'iTech', 'iKanban', 'iScan', 'iAsset', 'iCustomer', 'iSDR', 'iDismantling'],
            self::launcherNames(),
        );
    }

    public function testIPurchaseAndIQualityAreOffTheLauncher(): void
    {
        $byName = array_column(ApplicationCatalog::entries(), null, 'name');

        foreach (['iPurchase', 'iQuality'] as $name) {
            $this->assertArrayHasKey($name, $byName, "$name must stay in the catalog: subscriptions may point at its row");
            $this->assertNull($byName[$name]['url']);
            $this->assertFalse($byName[$name]['isActive']);
        }
    }

    public function testSortOrderFollowsThePositionInStepsOfTen(): void
    {
        $sortOrders = array_column(ApplicationCatalog::entries(), 'sortOrder');

        $this->assertSame(
            range(ApplicationCatalog::SORT_ORDER_STEP, count($sortOrders) * ApplicationCatalog::SORT_ORDER_STEP, ApplicationCatalog::SORT_ORDER_STEP),
            $sortOrders,
        );
    }

    public function testNamesAreUnique(): void
    {
        $names = array_column(ApplicationCatalog::APPLICATIONS, 'name');

        $this->assertSame($names, array_values(array_unique($names)), 'app:load-applications matches rows by name');
    }
}
