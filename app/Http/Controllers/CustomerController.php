<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Staff: List all customers (Task 10).
     */
    public function index()
    {
        return response()->json(Customer::all());
    }

    /**
     * Customer registration: creates customer and returns Sanctum token (Task 2).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'password'     => ['required', 'string', 'min:8'],
            'email'        => ['required', 'string', 'email', 'max:255', 'unique:customers,email'],
            'phone_number' => ['required', 'string', 'max:50', 'unique:customers,phone_number'],
        ]);

        $customer = Customer::create($data);
        $token = $customer->createToken('login')->plainTextToken;

        return response()->json([
            'customer' => $customer,
            'token'    => $token,
        ], 201);
    }

    /**
     * Staff: Show single customer with their borrowings (Task 10).
     */
    public function show(Customer $customer)
    {
        return response()->json($customer->load('borrowings'));
    }
}
