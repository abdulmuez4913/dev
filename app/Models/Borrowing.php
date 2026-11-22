<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Borrowing extends Model
{
    protected $table = 'borrowings';

    protected $guarded = [];

    protected $casts = [
        'is_returned'    => 'boolean',
        'borrowing_date' => 'date',
        'return_date'    => 'date',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
