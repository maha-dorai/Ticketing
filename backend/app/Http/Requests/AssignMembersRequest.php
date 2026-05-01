<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignMembersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // L'autorisation est gérée par les middlewares
    }

    public function rules(): array
    {
        return [
            'user_ids' => 'required|array',
            'user_ids.*' => [
                'required',
                Rule::exists('users', 'id')->where(function ($query) {
                    return $query->where('statut', 'actif')
                                 ->whereIn('role', ['developpeur', 'testeur']);
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_ids.required' => 'La liste des utilisateurs est obligatoire.',
            'user_ids.array' => 'La liste des utilisateurs doit être un tableau.',
            'user_ids.*.exists' => 'Un ou plusieurs utilisateurs sont invalides, ne sont pas actifs, ou n\'ont pas le rôle adéquat (développeur/testeur).',
        ];
    }
}
