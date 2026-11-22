<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Customer;
use Illuminate\Http\Request;

class BorrowingsController extends Controller
{
    /**
     * Customer: List authenticated customer's borrowings.
     */
    public function borrowings()
    {
        $borrowings = auth()->user()->borrowings;
        return response()->json($borrowings);
    }

    /**
     * Customer: Borrow a book with date validations, overlap check, and limit check.
     * Implements Task 3, Task 4, and Task 5.
     */
    public function borrow(Request $request)
    {
        $data = $request->validate([
            'book_id'        => 'required|exists:books,id',
            'borrowing_date' => ['required', 'date', 'after_or_equal:today'],
            'return_date'    => ['required', 'date', 'after:borrowing_date'],
        ]);

        // Task 5: Maximum 3 open borrowings per customer
        $openCount = auth()->user()->borrowings()
            ->wherePivot('is_returned', false)
            ->count();

        if ($openCount >= 3) {
            return response()->json([
                'message' => 'Maximum 3 open borrowings per customer reached'
            ], 422);
        }

        // Task 4: No two customers on the same book at the same time
        $book = Book::findOrFail($data['book_id']);
        $isTaken = $book->borrowings()
            ->wherePivot('is_returned', false)
            ->wherePivot('borrowing_date', '<=', $data['return_date'])
            ->wherePivot('return_date', '>=', $data['borrowing_date'])
            ->exists();

        if ($isTaken) {
            return response()->json([
                'message' => 'This book is already borrowed on these dates'
            ], 422);
        }

        // Attach borrowing record
        auth()->user()->borrowings()->attach([
            $data['book_id'] => [
                'borrowing_date' => $data['borrowing_date'],
                'return_date'    => $data['return_date'],
                'is_returned'    => false,
            ]
        ]);

        return response()->json([
            'message' => 'borrowings created sucessfully'
        ]);
    }

    /**
     * Customer: Detach/remove a borrowing.
     */
    public function delete($id)
    {
        auth()->user()->borrowings()->detach($id);
        return response()->json([
            'message' => 'removed'
        ]);
    }

    /**
     * Staff: Mark a book as returned.
     * Implements Task 1.
     */
    public function markAsReturned(Request $request)
    {
        $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'book_id'     => ['required', 'exists:books,id'],
        ]);

        $customer = Customer::findOrFail($request->customer_id);

        $borrowing = $customer->borrowings()
            ->wherePivot('book_id', $request->book_id)
            ->wherePivot('is_returned', false)
            ->first();

        if (!$borrowing) {
            return response()->json([
                'message' => 'This customer has no open borrowing for this book'
            ], 404);
        }

        $customer->borrowings()
            ->wherePivot('book_id', $request->book_id)
            ->wherePivot('is_returned', false)
            ->updateExistingPivot($request->book_id, [
                'is_returned' => true,
            ]);

        return response()->json([
            'message'        => 'Book marked as returned',
            'customer_id'    => $customer->id,
            'book_id'        => (int) $request->book_id,
            'borrowing_date' => $borrowing->pivot->borrowing_date,
        ]);
    }

    /**
     * Staff: View all borrowings across all customers and books.
     * Implements Task 6 (with optional ?is_returned= filter).
     */
    public function all(Request $request)
    {
        $query = Borrowing::with(['customer', 'book']);

        if ($request->has('is_returned')) {
            $isReturned = filter_var($request->is_returned, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_returned', $isReturned);
        }

        return response()->json($query->get());
    }

    /**
     * Staff: View overdue unreturned books.
     * Implements Task 7.
     */
    public function overdue()
    {
        $late = Borrowing::with(['customer', 'book'])
            ->where('is_returned', false)
            ->where('return_date', '<', now()->toDateString())
            ->get();

        return response()->json($late);
    }
}
