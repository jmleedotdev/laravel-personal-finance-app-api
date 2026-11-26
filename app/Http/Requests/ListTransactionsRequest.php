<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ListTransactionsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $sort = $this->query('sort');
        $category = $this->query('category');

        if (is_string($sort)) {
            $this->merge([
                'sort' => Str::lower(trim($sort))
            ]);
        }

        if (is_string($category)) {
            $this->merge([
                'category' => Str::title(trim($category))
            ]);
        }
    }

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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255', Rule::exists('categories', 'name')],
            'sort' => ['nullable', 'string', Rule::in([
                'latest', 'oldest', 'a to z', 'z to a', 'highest', 'lowest'
            ])],
        ];
    }
}
