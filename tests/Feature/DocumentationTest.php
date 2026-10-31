<?php

use App\Models\DocumentationArticle;
use App\Models\User;
use Database\Seeders\DocumentationSeeder;

beforeEach(function () {
    $this->seed(DocumentationSeeder::class);
});

test('can view documentation index with seeded articles', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('panel.docs.index'));

    $response->assertOk();
    $response->assertSee('Pusat Panduan & Dokumentasi Sistem NARA');
    $response->assertSee('Panduan Lengkap Konfigurasi Google Calendar');
    $response->assertSee('Panduan Lengkap Integrasi Telegram Bot');
    $response->assertSee('Troubleshooting Lengkap Integrasi Google Calendar');
});

test('can search and filter documentation articles', function () {
    $user = User::factory()->create();

    // Search query
    $searchResponse = $this->actingAs($user)->get(route('panel.docs.index', ['q' => '403 access_denied']));
    $searchResponse->assertOk();
    $searchResponse->assertSee('Troubleshooting Lengkap Integrasi Google Calendar');

    // Category filter
    $categoryResponse = $this->actingAs($user)->get(route('panel.docs.index', ['category' => 'telegram']));
    $categoryResponse->assertOk();
    $categoryResponse->assertSee('Panduan Lengkap Integrasi Telegram Bot');
});

test('can view single documentation article by slug with rendered markdown', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('panel.docs.show', 'panduan-integrasi-google-calendar-lokal-production'));

    $response->assertOk();
    $response->assertSee('Panduan Lengkap Konfigurasi Google Calendar (Lokal hingga Production)');
    $response->assertSee('Google Calendar API');
    $response->assertSee('Authorized redirect URIs');
});

test('can create a new documentation article', function () {
    $user = User::factory()->create(['name' => 'Imam Admin']);

    $response = $this->actingAs($user)->post(route('panel.docs.store'), [
        'title' => 'Panduan Baru Sistem AI',
        'slug' => 'panduan-baru-sistem-ai',
        'category' => 'custom_guide',
        'category_name' => 'Panduan Tambahan',
        'icon' => '💡',
        'excerpt' => 'Ini adalah ringkasan panduan baru.',
        'content' => "# Panduan Baru\n\nIsi panduan lengkap dengan **markdown**.",
        'sort_order' => 10,
        'is_published' => 1,
    ]);

    $response->assertRedirect(route('panel.docs.show', 'panduan-baru-sistem-ai'));

    $article = DocumentationArticle::where('slug', 'panduan-baru-sistem-ai')->first();
    expect($article)->not->toBeNull();
    expect($article->last_updated_by)->toBe('Imam Admin');
});

test('can update an existing documentation article', function () {
    $user = User::factory()->create(['name' => 'Imam Admin']);
    $article = DocumentationArticle::first();

    $response = $this->actingAs($user)->put(route('panel.docs.update', $article), [
        'title' => 'Judul Telah Diperbarui',
        'slug' => $article->slug,
        'category' => $article->category,
        'category_name' => $article->category_name,
        'icon' => '⚡',
        'excerpt' => 'Ringkasan yang diperbarui.',
        'content' => 'Konten baru yang telah diupdate.',
        'sort_order' => 1,
        'is_published' => 1,
    ]);

    $response->assertRedirect(route('panel.docs.show', $article->slug));

    $article->refresh();
    expect($article->title)->toBe('Judul Telah Diperbarui');
    expect($article->icon)->toBe('⚡');
    expect($article->last_updated_by)->toBe('Imam Admin');
});

test('can delete a documentation article', function () {
    $user = User::factory()->create();
    $article = DocumentationArticle::create([
        'title' => 'Artikel Uji Hapus',
        'slug' => 'artikel-uji-hapus',
        'category' => 'test',
        'category_name' => 'Testing',
        'icon' => '🗑️',
        'excerpt' => 'Akan segera dihapus.',
        'content' => 'Konten testing.',
        'sort_order' => 99,
        'is_published' => true,
    ]);

    $response = $this->actingAs($user)->delete(route('panel.docs.destroy', $article));

    $response->assertRedirect(route('panel.docs.index'));
    expect(DocumentationArticle::where('id', $article->id)->exists())->toBeFalse();
});



test('can link documentation to existing category from table and create new category dynamically', function () {
    $user = User::factory()->create(['name' => 'Imam Admin']);
    $existingCategory = \App\Models\DocumentationCategory::first();

    // 1. Link to existing category
    $response = $this->actingAs($user)->post(route('panel.docs.store'), [
        'title' => 'Panduan Tertaut Kategori Tabel',
        'slug' => 'panduan-tertaut-kategori-tabel',
        'documentation_category_id' => $existingCategory->id,
        'excerpt' => 'Ringkasan panduan tertaut.',
        'content' => 'Konten panduan.',
        'sort_order' => 5,
        'is_published' => 1,
    ]);

    $response->assertRedirect(route('panel.docs.show', 'panduan-tertaut-kategori-tabel'));
    $article = DocumentationArticle::where('slug', 'panduan-tertaut-kategori-tabel')->first();
    expect($article->documentation_category_id)->toBe($existingCategory->id);
    expect($article->category_name)->toBe($existingCategory->name);

    // 2. Create new category on the fly
    $responseNewCat = $this->actingAs($user)->post(route('panel.docs.store'), [
        'title' => 'Panduan dengan Kategori Baru',
        'slug' => 'panduan-kategori-baru',
        'documentation_category_id' => 'new',
        'new_category_name' => 'Integrasi WhatsApp Bot',
        'new_category_icon' => '💬',
        'new_category_desc' => 'Panduan webhook WhatsApp',
        'excerpt' => 'Ringkasan WhatsApp.',
        'content' => 'Konten WhatsApp.',
        'sort_order' => 1,
        'is_published' => 1,
    ]);

    $responseNewCat->assertRedirect(route('panel.docs.show', 'panduan-kategori-baru'));
    $newCategory = \App\Models\DocumentationCategory::where('name', 'Integrasi WhatsApp Bot')->first();
    expect($newCategory)->not->toBeNull();
    expect($newCategory->icon)->toBe('💬');

    $articleNew = DocumentationArticle::where('slug', 'panduan-kategori-baru')->first();
    expect($articleNew->documentation_category_id)->toBe($newCategory->id);
});
