<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;

class BookController extends Controller
{
    /**
     * List and search books (Task 8: support ?search= and ?category_id=).
     */
    public function index(Request $request)
    {
        $query = Book::query();

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        return BookResource::collection($query->get());
    }

    public function store(StoreBookRequest $request)
    {
        $book = Book::create($request->validated());
        return new BookResource($book);
    }

    public function show(Book $book)
    {
        return new BookResource($book->load('category'));
    }

    public function update(UpdateBookRequest $request, Book $book)
    {
        $book->update($request->validated());
        return new BookResource($book);
    }

    public function destroy(Book $book)
    {
        // Small Fix: check before delete to avoid foreign key failure
        if ($book->borrowings()->exists()) {
            return response()->json([
                'message' => 'Cannot delete book that was borrowed'
            ], 409);
        }

        $book->delete();
        return response()->noContent();
    }
}
