<?php

namespace App\Http\Controllers\Panel;

use App\Domain\Goals\GoalService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\GoalRequest;
use App\Models\Goal;
use App\Models\LifeDomain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GoalController extends Controller
{
    public function __construct(private readonly GoalService $goals) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $domains = $user->lifeDomains()->get(['id', 'name', 'color']);

        $goals = Goal::query()
            ->whereIn('life_domain_id', $domains->pluck('id'))
            ->with('lifeDomain')
            ->withCount('projects')
            ->when($request->filled('life_domain_id'), fn ($q) => $q->where('life_domain_id', $request->integer('life_domain_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('target_date')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        return view('panel.goals.index', compact('goals', 'domains'));
    }

    public function create(Request $request): View
    {
        $domains = $request->user()->lifeDomains()->get(['id', 'name']);
        $goal = new Goal(['life_domain_id' => $request->integer('life_domain_id') ?: $domains->first()?->id]);

        return view('panel.goals.form', compact('goal', 'domains'));
    }

    public function store(GoalRequest $request): RedirectResponse
    {
        $this->authorize('create', Goal::class);
        $data = $request->validated();
        /** @var LifeDomain $domain */
        $domain = $request->user()->lifeDomains()->findOrFail($data['life_domain_id']);

        $goal = $this->goals->createGoal($domain, $data);

        return redirect()
            ->route('panel.goals.show', $goal)
            ->with('toast', 'Goal dibuat.');
    }

    public function show(Goal $goal): View
    {
        $this->authorize('view', $goal);
        $goal->load(['lifeDomain', 'projects' => fn ($q) => $q->withCount('tasks')->orderBy('status')->orderBy('name')]);

        return view('panel.goals.show', compact('goal'));
    }

    public function edit(Request $request, Goal $goal): View
    {
        $this->authorize('update', $goal);
        $domains = $request->user()->lifeDomains()->get(['id', 'name']);

        return view('panel.goals.form', compact('goal', 'domains'));
    }

    public function update(GoalRequest $request, Goal $goal): RedirectResponse
    {
        $this->authorize('update', $goal);
        $this->goals->updateGoal($goal, $request->validated());

        return redirect()
            ->route('panel.goals.show', $goal)
            ->with('toast', 'Goal diperbarui.');
    }

    public function destroy(Goal $goal): RedirectResponse
    {
        $this->authorize('delete', $goal);
        $this->goals->deleteGoal($goal);

        return redirect()
            ->route('panel.goals.index')
            ->with('toast', 'Goal dihapus.');
    }
}

