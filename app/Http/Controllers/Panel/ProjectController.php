<?php

namespace App\Http\Controllers\Panel;

use App\Domain\Projects\ProjectService;
use App\Domain\Projects\WipGuard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\ProjectRequest;
use App\Models\Project;
use App\Support\Enums\ProjectStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    private const SORTABLE = ['name', 'status', 'wip_slot', 'progress_percent', 'due_date', 'last_activity_at'];

    public function __construct(
        private readonly ProjectService $projects,
        private readonly WipGuard $wipGuard,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $sort = in_array($request->string('sort')->toString(), self::SORTABLE, true)
            ? $request->string('sort')->toString()
            : 'name';
        $dir = $request->string('dir')->toString() === 'desc' ? 'desc' : 'asc';

        $query = $user->projects()
            ->with('goal.lifeDomain')
            ->withCount([
                'tasks as open_tasks_count' => fn ($q) => $q->whereIn('status', ['todo', 'in_progress', 'waiting']),
                'tasks as done_tasks_count' => fn ($q) => $q->where('status', 'done'),
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        } elseif (! $request->boolean('with_archived')) {
            // Arsip tidak tampil di tampilan default (Bagian 7.3).
            $query->where('status', '!=', ProjectStatus::Archived);
        }

        if ($request->filled('wip_slot')) {
            $query->where('wip_slot', $request->string('wip_slot'));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }
        if ($request->filled('q')) {
            $query->where('name', 'like', '%'.$request->string('q')->toString().'%');
        }

        $projects = $query->orderBy($sort, $dir)->paginate(15)->withQueryString();
        $wipUsage = $this->wipGuard->usage($user);

        return view('panel.projects.index', compact('projects', 'wipUsage', 'sort', 'dir'));
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $goals = $user->goals()->with('lifeDomain')->orderBy('title')->get();
        $project = new Project([
            'goal_id' => $request->integer('goal_id') ?: null,
            'status' => ProjectStatus::Idea,
        ]);
        $wipUsage = $this->wipGuard->usage($user);

        return view('panel.projects.form', compact('project', 'goals', 'wipUsage'));
    }

    public function store(ProjectRequest $request): RedirectResponse
    {
        $this->authorize('create', Project::class);
        $project = $this->projects->create($request->user(), $request->validated());

        return redirect()
            ->route('panel.projects.show', $project)
            ->with('toast', 'Project dibuat.');
    }

    public function show(Project $project): View
    {
        $this->authorize('view', $project);
        $project->load([
            'goal.lifeDomain',
            'tasks' => fn ($q) => $q->withCount('subtasks')->orderBy('sort_order')->orderBy('id'),
        ]);

        return view('panel.projects.show', compact('project'));
    }

    public function edit(Request $request, Project $project): View
    {
        $this->authorize('update', $project);
        $user = $request->user();
        $goals = $user->goals()->with('lifeDomain')->orderBy('title')->get();
        $wipUsage = $this->wipGuard->usage($user);

        return view('panel.projects.form', compact('project', 'goals', 'wipUsage'));
    }

    public function update(ProjectRequest $request, Project $project): RedirectResponse
    {
        $this->authorize('update', $project);
        $this->projects->update($project, $request->validated());

        return redirect()
            ->route('panel.projects.show', $project)
            ->with('toast', 'Project diperbarui.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $this->authorize('delete', $project);
        $this->projects->delete($project);

        return redirect()
            ->route('panel.projects.index')
            ->with('toast', 'Project diarsipkan (soft delete).');
    }
}

