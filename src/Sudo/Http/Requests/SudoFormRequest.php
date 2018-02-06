<?php

namespace CSUNMetaLab\Sudo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SudoFormRequest extends FormRequest
{
	public function authorize() {
		return true;
	}

	public function rules() {
		return [
			'password' => 'required|string',
		];
	}

	public function messages() {
		return [
			'password.required' => trans('sudo.errors.v.password.required'),
		];
	}
}