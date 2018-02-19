<?php

namespace CSUNMetaLab\Sudo\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Routing\Controller as BaseController;

class SudoController extends BaseController
{
	/**
	 * Drops the session values to exit sudo mode and then redirects back
	 * to the previous location.
	 *
	 * @return RedirectResponse
	 */
	public function exitSudoMode(Request $request) {
		// drop the session values
		$request->session()->forget('sudo_active');
		$request->session()->forget('sudo_last_time');

		// redirect back
		return redirect()->back();
	}
}