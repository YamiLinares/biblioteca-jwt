<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class BookApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_book_endpoint_rejects_requests_without_a_jwt(): void
    {
        $book = Book::factory()->create();
        $payload = $this->bookPayload();

        $this->getJson('/api/books')->assertUnauthorized();
        $this->getJson("/api/books/{$book->id}")->assertUnauthorized();
        $this->postJson('/api/books', $payload)->assertUnauthorized();
        $this->putJson("/api/books/{$book->id}", $payload)->assertUnauthorized();
        $this->patchJson("/api/books/{$book->id}", ['stock' => 2])->assertUnauthorized();
        $this->deleteJson("/api/books/{$book->id}")->assertUnauthorized();
    }

    public function test_authenticated_user_lists_books_with_search_and_category_filters(): void
    {
        $this->authenticate();
        Book::factory()->create(['title' => 'La ciencia del cosmos', 'author' => 'Ana Ruiz', 'category' => 'ciencia']);
        Book::factory()->create(['title' => 'Novela de verano', 'category' => 'literatura']);

        $this->getJson('/api/books?per_page=1')->assertOk()->assertJsonStructure(['data', 'links', 'meta']);
        $this->getJson('/api/books?search=cosmos')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'La ciencia del cosmos');
        $this->getJson('/api/books?category=ciencia')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.category', 'ciencia');
    }

    public function test_authenticated_user_creates_book_with_derived_availability_and_safe_attributes(): void
    {
        $this->authenticate();
        $response = $this->postJson('/api/books', $this->bookPayload() + ['is_admin' => true]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'El archivo de las palabras')
            ->assertJsonPath('data.is_available', true)
            ->assertJsonMissingPath('data.is_admin');
        $this->assertDatabaseHas('books', ['isbn' => '9781234567897', 'stock' => 3]);
        $this->assertDatabaseMissing('books', ['isbn' => '9781234567897', 'is_admin' => true]);
    }

    public function test_creation_rejects_negative_stock_invalid_isbn_category_and_duplicate_isbn(): void
    {
        $this->authenticate();
        $payload = $this->bookPayload();

        $this->postJson('/api/books', $this->bookPayload(['stock' => -1]))->assertUnprocessable()->assertJsonValidationErrors('stock');
        $this->postJson('/api/books', $this->bookPayload(['isbn' => 'not-an-isbn']))->assertUnprocessable()->assertJsonValidationErrors('isbn');
        $this->postJson('/api/books', $this->bookPayload(['category' => 'otra']))->assertUnprocessable()->assertJsonValidationErrors('category');
        Book::factory()->create(['isbn' => '9781234567897']);
        $this->postJson('/api/books', $payload)->assertUnprocessable()->assertJsonValidationErrors('isbn');
    }

    public function test_authenticated_user_reads_and_gets_json_404_for_missing_book(): void
    {
        $this->authenticate();
        $book = Book::factory()->create();

        $this->getJson("/api/books/{$book->id}")->assertOk()->assertJsonPath('data.id', $book->id);
        $this->getJson('/api/books/999999')->assertNotFound()->assertJsonPath('message', 'El recurso solicitado no existe.');
    }

    public function test_put_replaces_all_book_fields_and_rejects_incomplete_or_invalid_data(): void
    {
        $this->authenticate();
        $book = Book::factory()->create();

        $this->putJson("/api/books/{$book->id}", $this->bookPayload(['title' => 'Edición corregida']))
            ->assertOk()->assertJsonPath('data.title', 'Edición corregida');
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => 'Edición corregida']);
        $this->putJson("/api/books/{$book->id}", ['title' => 'Incompleto'])->assertUnprocessable()
            ->assertJsonValidationErrors(['author', 'isbn', 'category', 'stock']);
        $this->putJson("/api/books/{$book->id}", $this->bookPayload(['stock' => -2]))->assertUnprocessable()->assertJsonValidationErrors('stock');
        $this->putJson('/api/books/999999', $this->bookPayload())->assertNotFound();
    }

    public function test_patch_changes_only_submitted_fields_and_uses_isbn_uniqueness(): void
    {
        $this->authenticate();
        $book = Book::factory()->create(['title' => 'Conservar título', 'stock' => 5]);
        $other = Book::factory()->create();

        $this->patchJson("/api/books/{$book->id}", ['stock' => 0])->assertOk()
            ->assertJsonPath('data.stock', 0)->assertJsonPath('data.is_available', false)
            ->assertJsonPath('data.title', 'Conservar título');
        $this->patchJson("/api/books/{$book->id}", ['stock' => 4])->assertOk()->assertJsonPath('data.is_available', true);
        $this->patchJson("/api/books/{$book->id}", ['isbn' => $other->isbn])->assertUnprocessable()->assertJsonValidationErrors('isbn');
        $this->patchJson("/api/books/{$book->id}", ['stock' => -1])->assertUnprocessable()->assertJsonValidationErrors('stock');
    }

    public function test_delete_removes_book_and_returns_204(): void
    {
        $this->authenticate();
        $book = Book::factory()->create();

        $this->deleteJson("/api/books/{$book->id}")->assertNoContent();
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->deleteJson('/api/books/999999')->assertNotFound();
    }

    public function test_wrong_method_on_api_returns_json_405(): void
    {
        $this->putJson('/api/books', [])->assertStatus(405)->assertHeader('content-type', 'application/json');
    }

    private function authenticate(): User
    {
        $user = User::factory()->create();
        $token = Auth::guard('api')->login($user);
        $this->withHeader('Authorization', 'Bearer '.$token);

        return $user;
    }

    private function bookPayload(array $overrides = []): array
    {
        return array_replace([
            'title' => 'El archivo de las palabras',
            'author' => 'Lucía Méndez',
            'isbn' => '9781234567897',
            'description' => 'Una novela ficticia sobre memoria y lectura.',
            'category' => 'literatura',
            'publication_year' => 2021,
            'stock' => 3,
        ], $overrides);
    }
}
