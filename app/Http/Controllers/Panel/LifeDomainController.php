<?php

namespace App\Http\Controllers\Panel;

use App\Domain\Goals\GoalService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Panel\LifeDomainRequest;
use App\Models\LifeDomain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LifeDomainController extends Controller
{
    public function __construct(private readonly GoalService $goals) {}

    public function index(Request $request): View
    {
        $domains = $request->user()
            ->lifeDomains()
            ->withCount('goals')
            ->get();

        return view('panel.life-domains.index', compact('domains'));
    }

    public function create(): View
    {
        return view('panel.life-domains.form', ['domain' => new LifeDomain]);
    }

    public function store(LifeDomainRequest $request): RedirectResponse
    {
        $this->authorize('create', LifeDomain::class);
        $this->goals->createDomain($request->user(), $request->validated());

        return redirect()
            ->route('panel.life-domains.index')
            ->with('toast', 'Life Domain ditambahkan.');
    }

    public function show(LifeDomain $lifeDomain): View
    {
        $this->authorize('view', $lifeDomain);
        $lifeDomain->load(['goals.projects']);

        return view('panel.life-domains.show', ['domain' => $lifeDomain]);
    }

    public function edit(LifeDomain $lifeDomain): View
    {
        $this->authorize('update', $lifeDomain);

        return view('panel.life-domains.form', ['domain' => $lifeDomain]);
    }

    public function update(LifeDomainRequest $request, LifeDomain $lifeDomain): RedirectResponse
    {
        $this->authorize('update', $lifeDomain);
        $this->goals->updateDomain($lifeDomain, $request->validated());

        return redirect()
            ->route('panel.life-domains.index')
            ->with('toast', 'Life Domain diperbarui.');
    }

    public function destroy(LifeDomain $lifeDomain): RedirectResponse
    {
        $this->authorize('delete', $lifeDomain);
        $this->goals->deleteDomain($lifeDomain);

        return redirect()
            ->route('panel.life-domains.index')
            ->with('toast', 'Life Domain dihapus.');
    }
}

