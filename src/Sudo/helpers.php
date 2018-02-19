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

/**
 * Generates and returns an HTML string that represents all of the previous
 * input from the action that triggered the sudo mode re-prompt. The input
 * elements will be rendered as hidden elements.
 *
 * @param array $input The associative array of input
 * @param string $inputKey Optional key to replace the keys of the input
 *
 * @return string
 */
function generatePreviousInputMarkup($input, $inputKey="") {
	$markup = "";
	foreach($input as $key => $value) {
		if(is_array($value)) {
			// go deeper into the array and ensure we render the input elements
			// using PHP array notation
			$markup .= generatePreviousInputMarkup($value, $key."[]");
		}
		else
		{
			// use the key provided by the current array element or the one
			// provided to the function if it is non-empty
			$k = $key;
			if(!empty($inputKey)) {
				$k = $inputKey;
			}
			$markup .= "<input type='hidden' name='$k' value='$value' />\n";
		}
	}

	return $markup;
}