<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProviderRequestRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array {
        return [
            'id_card'            => 'required|image|mimes:jpg,jpeg,png|max:4096',
            'ownership'          => 'required|image|mimes:jpg,jpeg,png|max:4096',
            'has_accepted_terms' => 'required|accepted',
        ];
    }
    public function messages(): array {
        return [
            'required'  => 'This field is required.',
            'image'     => 'Please upload a valid image.',
            'mimes'     => 'File type not supported.',
            'max'       => 'File is too large.',
            'accepted'  => 'You must accept the terms and conditions.',
        ];
    }
}
