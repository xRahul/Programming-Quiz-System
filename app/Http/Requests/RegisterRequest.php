<?php

namespace QuizSystem\Http\Requests;

use QuizSystem\Http\Requests\Request;

class RegisterRequest extends Request
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
            'first_name' => 'required|string|min:2|max:255',
            'last_name' => 'string|min:2|max:255',
            'email' => 'required|email|min:5|max:255|unique:users,email',
            'password' => 'required|confirmed|min:6',
            'mobile' => 'integer|digits:10|unique:users,mobile',
            'admin_user' => 'boolean'
        ];
    }
}
