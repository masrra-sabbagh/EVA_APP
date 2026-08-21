<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest {
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
            'user_name'     => 'sometimes|string|max:50',
            'work_or_study' => 'sometimes|nullable|string|max:50',
            'profile_image' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',
        ];
    }
    public function messages(): array {
        return [
            'string'  => 'Please enter valid text.',
            'max'     => 'This field exceeds the maximum length.',
            'image'   => 'Please upload a valid image.',
            'mimes'   => 'File type not supported.',
        ];
    }
}
