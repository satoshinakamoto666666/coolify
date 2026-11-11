<div wire:poll.5000ms class="flex flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight dark:text-white">Deployments</h1>
            <p class="text-sm text-neutral-500 dark:text-neutral-400">
                Real-time and historical deployments across your projects and servers.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <x-forms.button wire:click="$refresh">Refresh</x-forms.button>
            @if ($projectFilter || $serverFilter || $sourceFilter || $statusFilter || $search)
                <x-forms.button wire:click="clearFilters">Clear Filters</x-forms.button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-3 md:grid-cols-5">
        <x-forms.input wire:model.live.debounce.300ms="search" placeholder="Search app, commit, or UUID..." />

        <x-forms.select wire:model.live="projectFilter">
            <option value="">All Projects</option>
            @foreach ($projects as $project)
                <option value="{{ $project->uuid }}">{{ $project->name }}</option>
            @endforeach
        </x-forms.select>

        @if ($showServerFilter)
            <x-forms.select wire:model.live="serverFilter">
                <option value="">All Servers</option>
                @foreach ($servers as $server)
                    <option value="{{ $server->id }}">{{ $server->name }}</option>
                @endforeach
            </x-forms.select>
        @endif

        @if ($showSourceFilter)
            <x-forms.select wire:model.live="sourceFilter">
                <option value="">All Sources</option>
                @foreach ($sources as $source)
                    <option value="{{ $source['key'] }}">{{ $source['name'] }}</option>
                @endforeach
            </x-forms.select>
        @endif

        <x-forms.select wire:model.live="statusFilter">
            <option value="">All Statuses</option>
            <option value="queued">Queued</option>
            <option value="pending">Pending / In Progress</option>
            <option value="done">Done (Finished)</option>
            <option value="failed">Failed</option>
            <option value="cancelled-by-user">Cancelled</option>
        </x-forms.select>
    </div>

    <div class="overflow-hidden rounded-lg border border-neutral-200 dark:border-coolgray-300">
        <table class="min-w-full divide-y divide-neutral-200 dark:divide-coolgray-300">
            <thead class="bg-neutral-50 dark:bg-coolgray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Application</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Server</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Commit</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Started</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-200 dark:divide-coolgray-300">
                @forelse ($deployments as $deployment)
                    <tr class="hover:bg-neutral-100/60 dark:hover:bg-coolgray-200/50">
                        <td class="px-4 py-3 font-medium dark:text-white">
                            <a href="{{ $deployment->deployment_url }}" class="hover:underline">
                                {{ $deployment->application_name ?? 'Application #' . $deployment->application_id }}
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-semibold">
                                {{ $deployment->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-neutral-600 dark:text-neutral-300">
                            {{ $deployment->server_name ?? ('Server #' . $deployment->server_id) }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-neutral-500">
                            {{ $deployment->commit ? substr($deployment->commit, 0, 7) : 'HEAD' }}
                        </td>
                        <td class="px-4 py-3 text-xs text-neutral-500">
                            {{ optional($deployment->created_at)->diffForHumans() }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-neutral-500">
                            No deployments match the selected filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $deployments->links() }}
    </div>
</div>
