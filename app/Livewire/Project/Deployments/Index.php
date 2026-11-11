<?php

namespace App\Livewire\Project\Deployments;

use App\Models\ApplicationDeploymentQueue;
use App\Models\GithubApp;
use App\Models\GitlabApp;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url(as: 'project')]
    public string $projectFilter = '';

    #[Url(as: 'server')]
    public string $serverFilter = '';

    #[Url(as: 'source')]
    public string $sourceFilter = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'search')]
    public string $search = '';

    public int $perPage = 25;

    public function updatingProjectFilter(): void
    {
        $this->resetPage();
    }

    public function updatingServerFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSourceFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->projectFilter = '';
        $this->serverFilter = '';
        $this->sourceFilter = '';
        $this->statusFilter = '';
        $this->search = '';
        $this->resetPage();
    }

    /**
     * Determines whether the server filter dropdown should be shown.
     * Hidden automatically when the team only has 1 server.
     */
    public static function shouldShowServerFilter(int $serverCount): bool
    {
        return $serverCount > 1;
    }

    /**
     * Determines whether the source filter dropdown should be shown.
     * Hidden automatically when the team only has 1 Git source.
     */
    public static function shouldShowSourceFilter(int $sourceCount): bool
    {
        return $sourceCount > 1;
    }

    /**
     * Normalizes user-facing status filter names ("done", "pending", "queued") to internal queue statuses.
     *
     * @return array<int, string>
     */
    public static function resolveStatusFilterValues(string $status): array
    {
        return match (strtolower(trim($status))) {
            'queued' => ['queued'],
            'pending', 'in_progress' => ['in_progress', 'pending'],
            'done', 'finished' => ['finished'],
            'failed' => ['failed'],
            'cancelled', 'cancelled-by-user' => ['cancelled-by-user'],
            default => $status !== '' ? [$status] : [],
        };
    }

    public function render(): View
    {
        $teamId = currentTeam()->id;
        $projects = Project::where('team_id', $teamId)->orderBy('name')->get();
        $servers = Server::where('team_id', $teamId)->orderBy('name')->get();

        $githubSources = GithubApp::where('team_id', $teamId)->get()->map(fn ($s) => [
            'key' => 'github:' . $s->id,
            'name' => $s->name ?? ('GitHub #' . $s->id),
        ]);
        $gitlabSources = GitlabApp::where('team_id', $teamId)->get()->map(fn ($s) => [
            'key' => 'gitlab:' . $s->id,
            'name' => $s->name ?? ('GitLab #' . $s->id),
        ]);
        $sources = $githubSources->concat($gitlabSources)->values();

        $serverIds = $servers->pluck('id');
        $query = ApplicationDeploymentQueue::whereIn('server_id', $serverIds)
            ->orderByDesc('created_at');

        if ($this->serverFilter !== '') {
            $query->where('server_id', (int) $this->serverFilter);
        }

        if ($this->statusFilter !== '') {
            $statuses = self::resolveStatusFilterValues($this->statusFilter);
            if (! empty($statuses)) {
                $query->whereIn('status', $statuses);
            }
        }

        if ($this->projectFilter !== '') {
            $project = $projects->firstWhere('uuid', $this->projectFilter)
                ?? $projects->firstWhere('id', (int) $this->projectFilter);
            if ($project) {
                $appIds = $project->applications()->pluck('applications.id');
                $query->whereIn('application_id', $appIds);
            }
        }

        if ($this->sourceFilter !== '' && str_contains($this->sourceFilter, ':')) {
            [$sourceType, $sourceId] = explode(':', $this->sourceFilter, 2);
            $morphClass = $sourceType === 'gitlab' ? GitlabApp::class : GithubApp::class;
            $query->whereHas('application', function ($q) use ($morphClass, $sourceId) {
                $q->where('source_type', $morphClass)->where('source_id', (int) $sourceId);
            });
        }

        if ($this->search !== '') {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('application_name', 'like', $term)
                    ->orWhere('commit', 'like', $term)
                    ->orWhere('deployment_uuid', 'like', $term);
            });
        }

        return view('livewire.project.deployments.index', [
            'deployments' => $query->paginate($this->perPage),
            'projects' => $projects,
            'servers' => $servers,
            'sources' => $sources,
            'showServerFilter' => self::shouldShowServerFilter($servers->count()),
            'showSourceFilter' => self::shouldShowSourceFilter($sources->count()),
        ]);
    }
}
