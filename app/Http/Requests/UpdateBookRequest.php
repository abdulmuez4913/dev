<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $book = $this->route('book');
        $bookId = is_object($book) ? $book->id : $book;

        return [
            'name'        => 'sometimes|string',
            'author'      => 'sometimes|string',
            'ISBN'        => ['sometimes', Rule::unique('books', 'ISBN')->ignore($bookId)],
            'category_id' => 'sometimes|exists:categories,id',
        ];
    }
}
