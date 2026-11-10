<?php

namespace App\Http\Controllers\Panel;

use App\Domain\Capture\InboxService;
use App\Http\Controllers\Controller;
use App\Models\InboxItem;
use App\Support\Enums\ActivitySource;
use App\Support\Enums\InboxStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InboxController extends Controller
{
    public function __construct(private readonly InboxService $inbox) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $items = InboxItem::where('user_id', $user->id)
            ->where('status', InboxStatus::Pending)
            ->orderByDesc('created_at')
            ->paginate(15);

        $projects = $user->projects()->where('status', '!=', 'archived')->orderBy('name')->get(['id', 'name']);
        $domains = $user->lifeDomains()->orderBy('sort_order')->get(['id', 'name']);

        return view('panel.inbox.index', compact('items', 'projects', 'domains'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
            'hint' => ['nullable', 'string', 'in:task,idea'],
        ]);

        $this->inbox->capture(
            $request->user(),
            $data['content'],
            ActivitySource::Web,
            $data['hint'] ?? null,
        );

        return redirect()->route('panel.inbox.index')->with('toast', 'Catatan berhasil ditambahkan ke Inbox.');
    }

    public function triage(Request $request, InboxItem $inboxItem): RedirectResponse
    {
        abort_unless($inboxItem->user_id === $request->user()->id, 403);

        $target = $request->input('target'); // task, project, someday, goal, discard

        match ($target) {
            'task' => $this->handleTriageTask($request, $inboxItem),
            'project' => $this->inbox->triageToProject($inboxItem, $request->user(), [
                'name' => $request->input('name', $inboxItem->content),
            ]),
            'someday' => $this->inbox->triageToSomeday($inboxItem, $request->user()),
            'goal' => $this->handleTriageGoal($request, $inboxItem),
            'discard' => $this->inbox->discard($inboxItem),
            default => abort(400, 'Target triase tidak dikenal'),
        };

        return redirect()->route('panel.inbox.index')->with('toast', 'Item Inbox berhasil diproses.');
    }

    private function handleTriageTask(Request $request, InboxItem $item): void
    {
        $projectId = $request->integer('project_id');
        $project = $request->user()->projects()->findOrFail($projectId);

        $this->inbox->triageToTask($item, $project, [
            'title' => $request->input('title', $item->content),
            'estimated_minutes' => $request->input('estimated_minutes') ?: null,
            'due_date' => $request->input('due_date') ?: null,
        ]);
    }

    private function handleTriageGoal(Request $request, InboxItem $item): void
    {
        $domainId = $request->integer('life_domain_id');
        $domain = $request->user()->lifeDomains()->findOrFail($domainId);

        $this->inbox->triageToGoal($item, $domain, [
            'title' => $request->input('title', $item->content),
        ]);
    }
}

