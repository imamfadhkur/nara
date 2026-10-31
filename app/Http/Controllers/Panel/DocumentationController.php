<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\DocumentationArticle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Controller Dokumentasi & Basis Pengetahuan Sistem NARA (Fase 2).
 *
 * Mengelola panduan lengkap API, konfigurasi, alur kerja fitur,
 * dan troubleshooting yang tersimpan dinamis di database.
 */
class DocumentationController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $category = trim((string) $request->query('category', ''));

        $query = DocumentationArticle::query()
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($category !== '') {
            $query->where('category', $category);
        }

        $articles = $query->get();

        $allCategories = DocumentationArticle::query()
            ->select('category', 'category_name')
            ->distinct()
            ->get();

        $groupedArticles = $articles->groupBy('category_name');

        return view('panel.docs.index', [
            'articles' => $articles,
            'groupedArticles' => $groupedArticles,
            'allCategories' => $allCategories,
            'search' => $search,
            'selectedCategory' => $category,
            'totalCount' => DocumentationArticle::count(),
        ]);
    }

    public function show(DocumentationArticle $article): View
    {
        $allArticles = DocumentationArticle::query()
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('category_name');

        return view('panel.docs.show', [
            'article' => $article,
            'allArticles' => $allArticles,
        ]);
    }

    public function create(): View
    {
        $existingCategories = DocumentationArticle::query()
            ->select('category', 'category_name')
            ->distinct()
            ->get();

        return view('panel.docs.form', [
            'article' => new DocumentationArticle(['is_published' => true, 'icon' => '📖', 'sort_order' => 10]),
            'isEdit' => false,
            'existingCategories' => $existingCategories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:documentation_articles,slug',
            'category' => 'required|string|max:64',
            'category_name' => 'required|string|max:128',
            'icon' => 'nullable|string|max:16',
            'excerpt' => 'required|string|max:500',
            'content' => 'required|string',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_published' => 'nullable|boolean',
        ]);

        $slug = ! empty($data['slug']) ? Str::slug($data['slug']) : Str::slug($data['title']);

        // Pastikan slug unik
        $baseSlug = $slug;
        $counter = 1;
        while (DocumentationArticle::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $article = DocumentationArticle::create([
            'title' => $data['title'],
            'slug' => $slug,
            'category' => Str::slug($data['category'], '_'),
            'category_name' => $data['category_name'],
            'icon' => $data['icon'] ?: '📖',
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
        $existingCategories = DocumentationArticle::query()
            ->select('category', 'category_name')
            ->distinct()
            ->get();

        return view('panel.docs.form', [
            'article' => $article,
            'isEdit' => true,
            'existingCategories' => $existingCategories,
        ]);
    }

    public function update(Request $request, DocumentationArticle $article): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => ['required', 'string', 'max:255', Rule::unique('documentation_articles', 'slug')->ignore($article->id)],
            'category' => 'required|string|max:64',
            'category_name' => 'required|string|max:128',
            'icon' => 'nullable|string|max:16',
            'excerpt' => 'required|string|max:500',
            'content' => 'required|string',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_published' => 'nullable|boolean',
        ]);

        $article->update([
            'title' => $data['title'],
            'slug' => Str::slug($data['slug']),
            'category' => Str::slug($data['category'], '_'),
            'category_name' => $data['category_name'],
            'icon' => $data['icon'] ?: '📖',
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
}
