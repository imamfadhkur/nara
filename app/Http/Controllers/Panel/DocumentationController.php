<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\DocumentationArticle;
use App\Models\DocumentationCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Controller Dokumentasi & Basis Pengetahuan Sistem NARA (Fase 2).
 *
 * Mengelola panduan lengkap API, konfigurasi, alur kerja fitur,
 * dan troubleshooting yang tersimpan dinamis di database dengan relasi kategori terstruktur.
 */
class DocumentationController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $categoryParam = trim((string) $request->query('category', ''));

        $query = DocumentationArticle::query()
            ->with('documentationCategory')
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($categoryParam !== '') {
            $query->where(function ($q) use ($categoryParam) {
                $q->where('category', $categoryParam)
                  ->orWhereHas('documentationCategory', fn ($cq) => $cq->where('slug', $categoryParam)->orWhere('id', $categoryParam));
            });
        }

        $articles = $query->get();

        $allCategories = DocumentationCategory::query()
            ->withCount(['publishedArticles as articles_count'])
            ->orderBy('sort_order')
            ->get();

        $groupedArticles = $articles->groupBy(fn ($item) => $item->category_name);

        return view('panel.docs.index', [
            'articles' => $articles,
            'groupedArticles' => $groupedArticles,
            'allCategories' => $allCategories,
            'search' => $search,
            'selectedCategory' => $categoryParam,
            'totalCount' => DocumentationArticle::count(),
        ]);
    }

    public function show(DocumentationArticle $article): View
    {
        $article->loadMissing('documentationCategory');

        $allArticles = DocumentationArticle::query()
            ->with('documentationCategory')
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($item) => $item->category_name);

        return view('panel.docs.show', [
            'article' => $article,
            'allArticles' => $allArticles,
        ]);
    }

    public function create(): View
    {
        $categories = DocumentationCategory::orderBy('sort_order')->get();

        return view('panel.docs.form', [
            'article' => new DocumentationArticle(['is_published' => true, 'icon' => '📖', 'sort_order' => 10]),
            'isEdit' => false,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:documentation_articles,slug',
            'documentation_category_id' => 'nullable',
            'new_category_name' => 'nullable|string|max:128',
            'new_category_icon' => 'nullable|string|max:16',
            'new_category_desc' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:64',
            'category_name' => 'nullable|string|max:128',
            'icon' => 'nullable|string|max:16',
            'excerpt' => 'required|string|max:500',
            'content' => 'required|string',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_published' => 'nullable|boolean',
        ]);

        $category = $this->resolveCategory($request);

        $slug = ! empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($data['title']);

        // Pastikan slug unik
        $baseSlug = $slug;
        $counter = 1;
        while (DocumentationArticle::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $article = DocumentationArticle::create([
            'documentation_category_id' => $category?->id,
            'category' => $category?->slug ?? Str::slug($data['category'] ?? 'general', '_'),
            'category_name' => $category?->name ?? ($data['category_name'] ?? 'Umum'),
            'title' => $data['title'],
            'slug' => $slug,
            'icon' => (! empty($data['icon']) ? $data['icon'] : ($category?->icon ?: '📖')),
            'excerpt' => $data['excerpt'],
            'content' => $data['content'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_published' => $request->boolean('is_published'),
            'last_updated_by' => $request->user()->name,
        ]);

        return redirect()->route('panel.docs.show', $article->slug)
            ->with('status', 'Artikel dokumentasi berhasil ditambahkan.');
    }

    public function edit(DocumentationArticle $article): View
    {
        $categories = DocumentationCategory::orderBy('sort_order')->get();

        return view('panel.docs.form', [
            'article' => $article->loadMissing('documentationCategory'),
            'isEdit' => true,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, DocumentationArticle $article): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('documentation_articles', 'slug')->ignore($article->id)],
            'documentation_category_id' => 'nullable',
            'new_category_name' => 'nullable|string|max:128',
            'new_category_icon' => 'nullable|string|max:16',
            'new_category_desc' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:64',
            'category_name' => 'nullable|string|max:128',
            'icon' => 'nullable|string|max:16',
            'excerpt' => 'required|string|max:500',
            'content' => 'required|string',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_published' => 'nullable|boolean',
        ]);

        $category = $this->resolveCategory($request);

        $article->update([
            'documentation_category_id' => $category?->id ?? $article->documentation_category_id,
            'category' => $category?->slug ?? $article->category,
            'category_name' => $category?->name ?? $article->category_name,
            'title' => $data['title'],
            'slug' => Str::slug($data['slug']),
            'icon' => (! empty($data['icon']) ? $data['icon'] : ($category?->icon ?: '📖')),
            'excerpt' => $data['excerpt'],
            'content' => $data['content'],
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_published' => $request->boolean('is_published'),
            'last_updated_by' => $request->user()->name,
        ]);

        return redirect()->route('panel.docs.show', $article->slug)
            ->with('status', 'Artikel dokumentasi berhasil diperbarui.');
    }

    public function destroy(DocumentationArticle $article): RedirectResponse
    {
        $title = $article->title;
        $article->delete();

        return redirect()->route('panel.docs.index')
            ->with('status', "Artikel \"{$title}\" berhasil dihapus.");
    }

    /**
     * Resolusi kategori: memilih dari kategori yang ada atau membuat kategori baru jika diminta.
     */
    private function resolveCategory(Request $request): ?DocumentationCategory
    {
        $categoryId = $request->input('documentation_category_id');
        $newCatName = trim((string) $request->input('new_category_name', ''));
        $newCatIcon = trim((string) $request->input('new_category_icon', ''));
        $newCatDesc = trim((string) $request->input('new_category_desc', ''));

        // Jika user memilih buat kategori baru ATAU mengetik nama kategori baru
        if ($categoryId === 'new' || $newCatName !== '') {
            if ($newCatName === '') {
                $newCatName = 'Kategori Baru';
            }
            $slug = Str::slug($newCatName);

            return DocumentationCategory::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $newCatName,
                    'icon' => $newCatIcon ?: '📁',
                    'description' => $newCatDesc ?: null,
                    'sort_order' => (DocumentationCategory::max('sort_order') ?? 0) + 1,
                ]
            );
        }

        // Jika user memilih kategori yang sudah ada dari dropdown
        if (is_numeric($categoryId) && (int) $categoryId > 0) {
            return DocumentationCategory::find((int) $categoryId);
        }

        // Fallback untuk backward-compatibility via category_name
        if ($request->filled('category_name')) {
            $catName = trim((string) $request->input('category_name'));
            $slug = Str::slug($catName);

            return DocumentationCategory::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $catName,
                    'icon' => $request->input('icon', '📁') ?: '📁',
                    'sort_order' => (DocumentationCategory::max('sort_order') ?? 0) + 1,
                ]
            );
        }

        return null;
    }
}
