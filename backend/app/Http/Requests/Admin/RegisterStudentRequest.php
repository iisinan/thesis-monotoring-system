<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RegisterStudentRequest extends FormRequest
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
        return [
            'type' => 'required|in:single,bulk',
            'name' => 'required_if:type,single|nullable|string|max:255',
            'email' => 'required_if:type,single|nullable|email|unique:users,email',
            'matrix_number' => 'required_if:type,single|nullable|string|unique:student_profiles,student_id_number',
            'program_id' => 'required_if:type,single|nullable|exists:programs,id',
            'csv_file' => 'required_if:type,bulk|nullable|file|mimes:csv,txt',
        ];
    }
}
