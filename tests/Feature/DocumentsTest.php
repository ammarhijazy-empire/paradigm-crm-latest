<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DocumentsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function documents_are_private_and_downloaded_only_through_the_policy(): void
    {
        Storage::fake('private');
        $ops = $this->user('ops');
        $f = $this->fileFor(stage: 3);
        $this->actingAs($ops)->post("/files/{$f->number}/documents", ['type' => 'commercial_invoice', 'files' => [UploadedFile::fake()->create('ci.pdf', 100, 'application/pdf')]])->assertSessionHasNoErrors();
        $doc = $f->documents()->firstOrFail();
        Storage::disk('private')->assertExists($doc->path);
        $this->assertFileDoesNotExist(public_path($doc->path));

        $this->actingAs($ops)->get("/documents/{$doc->id}")->assertOk();
        $this->actingAs($f->sales)->get("/documents/{$doc->id}")->assertOk();
        $this->actingAs($this->user('sales'))->get("/documents/{$doc->id}")->assertStatus(403);
        auth()->logout();
        $this->get("/documents/{$doc->id}")->assertRedirect(route('login'));
    }

    #[Test]
    public function uploads_are_checked_by_type_and_size(): void
    {
        Storage::fake('private');
        $f = $this->fileFor(stage: 3);
        $ops = $this->user('ops');
        $this->actingAs($ops)->post("/files/{$f->number}/documents", ['type' => 'other', 'files' => [UploadedFile::fake()->create('evil.php', 1, 'application/x-php')]])->assertSessionHasErrors('files.0');
        $this->actingAs($ops)->post("/files/{$f->number}/documents", ['type' => 'other', 'files' => [UploadedFile::fake()->create('big.pdf', 30000, 'application/pdf')]])->assertSessionHasErrors('files.0');
        $this->actingAs($this->user('pricing'))->post("/files/{$f->number}/documents", ['type' => 'other', 'files' => [UploadedFile::fake()->create('a.pdf', 1, 'application/pdf')]])->assertStatus(403);
    }
}
