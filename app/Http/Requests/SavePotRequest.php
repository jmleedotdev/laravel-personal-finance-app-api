<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SavePotRequest extends FormRequest
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
        $requiredOrSometimes = $this->isMethod('POST')
            ? ['required']
            : ['sometimes', 'required'];

        return [
            'name' => [...$requiredOrSometimes, 'string', 'max:255'],
            'target' => [...$requiredOrSometimes, 'numeric', 'decimal:0,2', 'max:999999.99', 'gt:0'],
            'theme' => [...$requiredOrSometimes, 'string', 'max:7', 'regex:/\A#[0-9a-fA-F]{6}\z/'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The pot name is required.',
            'name.string' => 'The pot name must be a string.',
            'name.max' => 'The pot name must not be greater than 255 characters.',
            'target.required' => 'The target is required.',
            'target.numeric' => 'The target must be a number.',
            'target.decimal' => 'The target must not be greater than 2 decimals.',
            'theme.required' => 'The theme hex code is required.',
            'theme.string' => 'The theme hex code must be a string.',
            'theme.max' => 'The theme hex code must not be greater than 7 characters.',
            'theme.regex' => 'The theme must be a color hex code.',
        ];
    }
}
