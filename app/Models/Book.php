<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    public const CATEGORIES = ['literatura', 'ciencia', 'tecnologia', 'historia', 'educacion', 'desarrollo-personal'];

    protected $fillable = ['title', 'author', 'isbn', 'description', 'category', 'publication_year', 'stock'];

    protected function casts(): array
    {
        return ['publication_year' => 'integer', 'stock' => 'integer'];
    }

    protected function isAvailable(): Attribute
    {
        return Attribute::get(fn (): bool => $this->stock > 0);
    }
}
