<?php

/**
 * Returns whether sudo mode is currently-active.
 *
 * @return bool
 */
function isSudoModeActive() {
	$active = session('sudo_active');
	return !empty($active);
}