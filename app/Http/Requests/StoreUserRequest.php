<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest {
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
            'user_name'     => 'required|string|max:50',
            'phone_number'  => 'required|regex:/^\+?[0-9]{10,15}$/|unique:users,phone_number',
            'profile_image' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'work_or_study' => 'nullable|string|max:50',
            'password'      => 'required|string|min:8|confirmed',
        ];
    }
    public function messages(): array {
        return [
            'required'   => 'This field is required.',
            'string'     => 'Please enter text.',
            'unique'     => 'This is already in use.',
            'confirmed'  => 'Does not match.',
            'regex'      => 'Invalid format.',
            'image'      => 'Please upload an image.',
            'mimes'      => 'Please upload an image.',
            'min'        => 'Password must be at least 8 characters',
            'max'        => 'File size is too large.'
        ];
    }
}
