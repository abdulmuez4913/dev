<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * The database has 3 staff, 2 customers, 2 categories, 2 books, and 1 open borrowing.
     */
    public function run(): void
    {
        // 1. Staff (3 staff members)
        $staff1 = Staff::create([
            'name'     => 'ali',
            'email'    => 'ali2@gmail.com',
            'password' => '12345678',
        ]);

        $staff2 = Staff::create([
            'name'     => 'sarah',
            'email'    => 'sarah@gmail.com',
            'password' => '12345678',
        ]);

        $staff3 = Staff::create([
            'name'     => 'john',
            'email'    => 'john@gmail.com',
            'password' => '12345678',
        ]);

        // 2. Customers (2 customers)
        $customer1 = Customer::create([
            'name'         => 'omar',
            'email'        => 'omar@gmail.com',
            'phone_number' => '0910000001',
            'password'     => '12345678',
        ]);

        $customer2 = Customer::create([
            'name'         => 'fatima',
            'email'        => 'fatima@gmail.com',
            'phone_number' => '0920000002',
            'password'     => '12345678',
        ]);

        // 3. Categories (2 categories)
        $cat1 = Category::create([
            'name'        => 'Fiction',
            'description' => 'Novels and storytelling',
        ]);

        $cat2 = Category::create([
            'name'        => 'Science',
            'description' => 'Physics, Chemistry, and Computer Science',
        ]);

        // 4. Books (2 books)
        $book1 = Book::create([
            'name'        => 'Clean Code',
            'author'      => 'Robert C. Martin',
            'ISBN'        => '9780132350884',
            'category_id' => $cat2->id,
        ]);

        $book2 = Book::create([
            'name'        => 'Harry Potter',
            'author'      => 'J.K. Rowling',
            'ISBN'        => '9780747532699',
            'category_id' => $cat1->id,
        ]);

        // 5. Open Borrowing (1 open borrowing)
        $customer1->borrowings()->attach($book1->id, [
            'borrowing_date' => Carbon::now()->toDateString(),
            'return_date'    => Carbon::now()->addDays(7)->toDateString(),
            'is_returned'    => false,
        ]);
    }
}
