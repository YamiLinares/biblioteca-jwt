<?php

namespace Database\Seeders;

use App\Models\Book;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        $books = [
            ['Cien años de soledad', 'Gabriel García Márquez', '9780307474728', 'La historia de siete generaciones de la familia Buendía en Macondo.', 'literatura', 1967, 4],
            ['Don Quijote de la Mancha', 'Miguel de Cervantes', '9788420412146', 'Las aventuras del ingenioso hidalgo y su escudero Sancho Panza.', 'literatura', 1605, 3],
            ['Orgullo y prejuicio', 'Jane Austen', '9780141439518', 'Una novela sobre la familia, el carácter y las convenciones sociales.', 'literatura', 1813, 2],
            ['1984', 'George Orwell', '9780451524935', 'Distopía clásica sobre vigilancia, poder y libertad individual.', 'literatura', 1949, 0],
            ['El principito', 'Antoine de Saint-Exupéry', '9780156012195', 'Un aviador conoce a un pequeño viajero proveniente de otro planeta.', 'literatura', 1943, 6],
            ['Breves respuestas a las grandes preguntas', 'Stephen Hawking', '9781984819192', 'Reflexiones accesibles sobre el universo y el futuro de la humanidad.', 'ciencia', 2018, 2],
            ['Cosmos', 'Carl Sagan', '9780345539434', 'Un recorrido por la ciencia, el espacio y la historia del conocimiento.', 'ciencia', 1980, 1],
            ['El gen egoísta', 'Richard Dawkins', '9780198788607', 'Una mirada evolutiva a la selección natural y la biología.', 'ciencia', 1976, 0],
            ['Una breve historia del tiempo', 'Stephen Hawking', '9780553380163', 'Conceptos fundamentales del espacio, el tiempo y los agujeros negros.', 'ciencia', 1988, 5],
            ['Clean Code', 'Robert C. Martin', '9780132350884', 'Principios prácticos para escribir software legible y mantenible.', 'tecnologia', 2008, 3],
            ['The Pragmatic Programmer', 'David Thomas', '9780135957059', 'Consejos y técnicas para desarrollar software con oficio.', 'tecnologia', 2019, 2],
            ['Eloquent JavaScript', 'Marijn Haverbeke', '9781593279509', 'Fundamentos del lenguaje JavaScript y programación en el navegador.', 'tecnologia', 2018, 4],
            ['Sapiens', 'Yuval Noah Harari', '9780062316097', 'Una historia panorámica de la humanidad y sus revoluciones.', 'historia', 2011, 0],
            ['La historia del mundo en 100 objetos', 'Neil MacGregor', '9780143124153', 'Relatos históricos organizados alrededor de objetos cotidianos.', 'historia', 2010, 2],
            ['El diario de Ana Frank', 'Ana Frank', '9780553296983', 'Testimonio escrito durante la ocupación nazi de los Países Bajos.', 'historia', 1947, 1],
            ['Pedagogía del oprimido', 'Paulo Freire', '9781501314131', 'Una propuesta influyente para la educación crítica y emancipadora.', 'educacion', 1968, 3],
            ['Aprender a aprender', 'Barbara Oakley', '9780143132547', 'Estrategias respaldadas por la ciencia para mejorar el aprendizaje.', 'educacion', 2014, 0],
            ['Hábitos atómicos', 'James Clear', '9780735211292', 'Métodos prácticos para construir hábitos sostenibles.', 'desarrollo-personal', 2018, 7],
        ];

        foreach ($books as [$title, $author, $isbn, $description, $category, $year, $stock]) {
            Book::updateOrCreate(['isbn' => $isbn], [
                'title' => $title,
                'author' => $author,
                'description' => $description,
                'category' => $category,
                'publication_year' => $year,
                'stock' => $stock,
            ]);
        }
    }
}
