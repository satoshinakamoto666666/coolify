<?php

namespace Tests\Unit;

use App\Livewire\Project\Deployments\Index;
use PHPUnit\Framework\TestCase;

class GlobalDeploymentsPageTest extends TestCase
{
    public function test_hides_server_and_source_filters_when_only_one_exists(): void
    {
        $this->assertFalse(Index::shouldShowServerFilter(0));
        $this->assertFalse(Index::shouldShowServerFilter(1));
        $this->assertTrue(Index::shouldShowServerFilter(2));

        $this->assertFalse(Index::shouldShowSourceFilter(1));
        $this->assertTrue(Index::shouldShowSourceFilter(3));
    }

    public function test_resolves_status_filter_aliases(): void
    {
        $this->assertSame(['queued'], Index::resolveStatusFilterValues('queued'));
        $this->assertSame(['in_progress', 'pending'], Index::resolveStatusFilterValues('pending'));
        $this->assertSame(['finished'], Index::resolveStatusFilterValues('done'));
        $this->assertSame(['failed'], Index::resolveStatusFilterValues('failed'));
    }
}
