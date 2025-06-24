<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PageRequest extends FormRequest
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
            'title' => 'required', // Page title is required
            'content' => 'required', // Page content is required
            // 'event_id' => 'required|integer', // Event ID is required and must be an integer
            'author_id' => 'uuid|required', // User ID is required and must be a UUID
            'slug' => 'required|alpha_dash:ascii|unique:pages,slug,'.$this->slug.',slug', // Slug is required and must contain only ASCII characters and underscores/dashes
        ];
    }
}
