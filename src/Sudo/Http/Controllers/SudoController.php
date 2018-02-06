<?php

namespace CSUNMetaLab\Sudo\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

use CSUNMetaLab\Sudo\Http\Requests\SudoFormRequest;

class SudoController extends BaseController
{
	/**
	 * Displays the form to accept a user password and enable sudo mode.
	 *
	 * @return View
	 */
	public function create() {
		
	}

	/**
	 * Accepts the sudo mode submission and allows for the original request to
	 * proceed.
	 *
	 * @return RedirectResponse
	 */
	public function store(SudoFormRequest $request) {
		
	}
}