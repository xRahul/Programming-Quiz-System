<?php

namespace QuizSystem\Http\Requests;

use QuizSystem\Http\Requests\Request;

class LoginRequest extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'required|email|max:255',
            'password' => 'required||min:6',
            'remember' => 'boolean'
        ];
    }

    public function messages()
    {
        return [
            'email.required' => 'Email is required!',
            'email.email' => 'Enter an Email Address',
            'password.required' => 'Enter the password',
            'password.min' => 'Min 6 characters password'
        ];
    }
}
