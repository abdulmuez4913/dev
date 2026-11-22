<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LibraryApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_staff_login_successful(): void
    {
        $response = $this->postJson('/api/staff/login', [
            'email'    => 'ali2@gmail.com',
            'password' => '12345678',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'token']);
    }

    public function test_staff_login_invalid_credentials(): void
    {
        $response = $this->postJson('/api/staff/login', [
            'email'    => 'ali2@gmail.com',
            'password' => 'wrongpass',
        ]);

        $response->assertStatus(401);
    }

    public function test_customer_login_successful(): void
    {
        $response = $this->postJson('/api/customer/login', [
            'phone_number' => '0910000001',
            'password'     => '12345678',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);
    }

    public function test_customer_login_wrong_credentials_returns_401(): void
    {
        $response = $this->postJson('/api/customer/login', [
            'phone_number' => '0910000001',
            'password'     => 'wrongpass',
        ]);

        $response->assertStatus(401);
    }

    public function test_task_2_customer_signup_returns_token_and_201(): void
    {
        $response = $this->postJson('/api/customer', [
            'name'         => 'New Customer',
            'email'        => 'newcust@example.com',
            'phone_number' => '0999999999',
            'password'     => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['customer', 'token']);
    }

    public function test_task_1_staff_mark_as_returned(): void
    {
        $staff = Staff::first();
        $customer = Customer::first();
        $book = Book::first();

        // Put request with staff token
        $response = $this->actingAs($staff, 'staff')->putJson('/api/borrowings/return', [
            'customer_id' => $customer->id,
            'book_id'     => $book->id,
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Book marked as returned']);

        // Second time should return 404 because borrowing is no longer open
        $response2 = $this->actingAs($staff, 'staff')->putJson('/api/borrowings/return', [
            'customer_id' => $customer->id,
            'book_id'     => $book->id,
        ]);

        $response2->assertStatus(404);
    }

    public function test_task_3_validate_borrowing_dates(): void
    {
        $customer = Customer::find(2);
        $book = Book::find(2);

        $response = $this->actingAs($customer, 'customer')->postJson('/api/borrowings', [
            'book_id'        => $book->id,
            'borrowing_date' => Carbon::now()->addDays(5)->toDateString(),
            'return_date'    => Carbon::now()->addDays(2)->toDateString(), // return date before borrowing date
        ]);

        $response->assertStatus(422);
    }

    public function test_task_4_overlapping_borrowings_prevented(): void
    {
        $customer2 = Customer::find(2);
        $book1 = Book::find(1); // Book 1 is already borrowed by customer 1 from today to +7 days

        $response = $this->actingAs($customer2, 'customer')->postJson('/api/borrowings', [
            'book_id'        => $book1->id,
            'borrowing_date' => Carbon::now()->addDays(2)->toDateString(),
            'return_date'    => Carbon::now()->addDays(5)->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'This book is already borrowed on these dates']);
    }

    public function test_task_5_max_3_open_borrowings_per_customer(): void
    {
        $customer2 = Customer::find(2);
        $category = Category::first();

        // Create 3 books for customer 2
        $b1 = Book::create(['name' => 'B1', 'author' => 'A1', 'ISBN' => 'ISBN1', 'category_id' => $category->id]);
        $b2 = Book::create(['name' => 'B2', 'author' => 'A2', 'ISBN' => 'ISBN2', 'category_id' => $category->id]);
        $b3 = Book::create(['name' => 'B3', 'author' => 'A3', 'ISBN' => 'ISBN3', 'category_id' => $category->id]);
        $b4 = Book::create(['name' => 'B4', 'author' => 'A4', 'ISBN' => 'ISBN4', 'category_id' => $category->id]);

        $dates = [
            'borrowing_date' => Carbon::now()->addDays(10)->toDateString(),
            'return_date'    => Carbon::now()->addDays(15)->toDateString(),
        ];

        $this->actingAs($customer2, 'customer')->postJson('/api/borrowings', ['book_id' => $b1->id] + $dates)->assertStatus(200);
        $this->actingAs($customer2, 'customer')->postJson('/api/borrowings', ['book_id' => $b2->id] + $dates)->assertStatus(200);
        $this->actingAs($customer2, 'customer')->postJson('/api/borrowings', ['book_id' => $b3->id] + $dates)->assertStatus(200);

        // 4th open borrowing fails with 422
        $response = $this->actingAs($customer2, 'customer')->postJson('/api/borrowings', ['book_id' => $b4->id] + $dates);
        $response->assertStatus(422);
    }

    public function test_task_6_and_7_staff_see_all_and_overdue_borrowings(): void
    {
        $staff = Staff::first();

        // Staff sees all
        $responseAll = $this->actingAs($staff, 'staff')->getJson('/api/borrowings/all');
        $responseAll->assertStatus(200);

        // Staff sees overdue
        $responseOverdue = $this->actingAs($staff, 'staff')->getJson('/api/borrowings/overdue');
        $responseOverdue->assertStatus(200);
    }

    public function test_task_8_search_books(): void
    {
        $staff = Staff::first();

        $response = $this->actingAs($staff, 'sanctum')->getJson('/api/book?search=Clean');
        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Clean Code']);
    }

    public function test_task_9_book_resource_is_available(): void
    {
        $staff = Staff::first();

        $response = $this->actingAs($staff, 'sanctum')->getJson('/api/book/1');
        $response->assertStatus(200)
            ->assertJson(['data' => ['is_available' => false]]); // Currently borrowed

        $response2 = $this->actingAs($staff, 'sanctum')->getJson('/api/book/2');
        $response2->assertStatus(200)
            ->assertJson(['data' => ['is_available' => true]]); // Not borrowed
    }

    public function test_task_10_staff_list_and_show_customers(): void
    {
        $staff = Staff::first();

        $responseList = $this->actingAs($staff, 'staff')->getJson('/api/customer');
        $responseList->assertStatus(200);

        $responseShow = $this->actingAs($staff, 'staff')->getJson('/api/customer/1');
        $responseShow->assertStatus(200)
            ->assertJsonStructure(['id', 'name', 'borrowings']);
    }

    public function test_small_fixes_409_conflict_on_delete_category_or_book(): void
    {
        $staff = Staff::first();

        // Deleting category that has books gives 409
        $responseCat = $this->actingAs($staff, 'staff')->deleteJson('/api/category/1');
        $responseCat->assertStatus(409);

        // Deleting book that has borrowings gives 409
        $responseBook = $this->actingAs($staff, 'staff')->deleteJson('/api/book/1');
        $responseBook->assertStatus(409);
    }
}
