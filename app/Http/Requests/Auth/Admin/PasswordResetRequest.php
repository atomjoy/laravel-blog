<?php

namespace App\Http\Requests\Auth\Admin;

use Exception;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class PasswordResetRequest extends FormRequest
{
	protected $stopOnFirstFailure = true;

	public function authorize()
	{
		return true; // Allow all
	}

	public function rules()
	{
		$email = 'email:rfc,dns';
		if (env('APP_DEBUG') == true) {
			$email = 'email';
		}

		return [
			'email' => ['required', $email, 'max:191']
		];
	}

	public function failedValidation(Validator $validator)
	{
		throw new ValidationException($validator, response()->json([
			'message' => $validator->errors()->first()
		], 422));
	}

	function prepareForValidation()
	{
		$this->merge(
			collect(request()->json()->all())->only(['email'])->toArray()
		);
	}

	public function testDatabase()
	{
		// Mock request method and throw error in controller if needed from tests
		// Or use putenv('TEST_DATABASE=true') in your tests to throw an error
		if (env('TEST_DATABASE', false) == true) {
			throw new Exception('TEST_DATABASE_PASSWORD', 422);
		}
	}
}
