<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('isbn')) {
            $this->merge(['isbn' => preg_replace('/[\s-]+/', '', (string) $this->input('isbn'))]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('PUT') ? 'required' : 'sometimes';
        $book = $this->route('book');
        $bookId = $book instanceof Book ? $book->id : $book;

        return [
            'title' => [$required, 'string', 'max:180'],
            'author' => [$required, 'string', 'max:120'],
            'isbn' => [$required, 'regex:/^(?:\d{10}|\d{13})$/', Rule::unique('books', 'isbn')->ignore($bookId)],
            'description' => [$required, 'string', 'max:5000'],
            'category' => [$required, 'string', Rule::in(Book::CATEGORIES)],
            'publication_year' => [$required, 'integer', 'min:1450', 'max:'.(now()->year + 1)],
            'stock' => [$required, 'integer', 'min:0', 'max:1000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El título es obligatorio.',
            'author.required' => 'El autor es obligatorio.',
            'isbn.required' => 'El ISBN es obligatorio.',
            'isbn.regex' => 'El ISBN debe contener 10 o 13 dígitos.',
            'isbn.unique' => 'El ISBN ya está registrado.',
            'description.required' => 'La descripción es obligatoria.',
            'category.required' => 'La categoría es obligatoria.',
            'category.in' => 'La categoría seleccionada no es válida.',
            'publication_year.required' => 'El año de publicación es obligatorio.',
            'publication_year.min' => 'El año de publicación debe ser 1450 o posterior.',
            'publication_year.max' => 'El año de publicación no puede estar en el futuro.',
            'stock.required' => 'La cantidad de ejemplares es obligatoria.',
            'stock.integer' => 'Las existencias deben ser un número entero.',
            'stock.min' => 'Las existencias no pueden ser negativas.',
        ];
    }
}
