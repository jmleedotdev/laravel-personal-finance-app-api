<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveBudgetRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $uniqueCategory = Rule::unique('budgets', 'category')->where('user_id', $user->id);

        if ($this->routeIs('budgets.update')) {
            $budget = $user->budgets()->findOrFail($this->route('budget'));
            $uniqueCategory->ignore($budget);
        }
        $requiredOrSometimes = $this->isMethod('POST')
            ? ['required']
            : ['sometimes', 'required'];

        return [
            'category' => [...$requiredOrSometimes, 'string', 'max:255', $uniqueCategory],
            'maximum' => [...$requiredOrSometimes, 'numeric', 'decimal:0,2', 'max:999999.99', 'gt:0'],
            'theme' => [...$requiredOrSometimes, 'string', 'max:7', 'regex:/\A#[0-9a-fA-F]{6}\z/'],
        ];
    }

    public function messages(): array {
        return [
            'category.required' => 'The category is required.',
            'category.string' => 'The category must be a string',
            'category.max' => 'The category must not be greater than 255 characters.',
            'category.unique' => 'You already have a category for this budget.',
            'maximum.required' => 'The maximum is required.',
            'maximum.numeric' => 'The maximum must be a number.',
            'maximum.decimal' => 'The maximum must not be greater than 2 decimals.',
            'theme.required' => 'The theme hex code is required.',
            'theme.string' => 'The theme hex code must be a string.',
            'theme.max' => 'The theme hex code must not be greater than 7 characters.',
            'theme.regex' => 'The theme must be a color hex code.',
        ];
    }
}
