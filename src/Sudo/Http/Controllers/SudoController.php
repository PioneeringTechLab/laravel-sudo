<?php

namespace CSUNMetaLab\Sudo\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;

class SudoController extends BaseController
{
	/**
	 * Drops the session values to exit sudo mode and then redirects back
	 * to the previous location.
	 *
	 * @return RedirectResponse
	 */
	public function exitSudoMode() {
		// leverage the helper function
		exitSudoMode();

		// redirect back
		return redirect()->back();
	}
}