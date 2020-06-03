<?php

function ctz_log( $message, $file = 'errors.log' ) {
    error_log( $message . "\n", 3, CFTZ_PLUGIN_PATH . '/' . $file );
}


function qo_split_key_and_set_value_to_pointer(&$arrayPtr, $keysImploded, $value) {
	$keys = explode(':', $keysImploded);
	$lastKey = array_pop($keys);

	foreach($keys as $arrKey) {
		if (!array_key_exists($arrKey, $arrayPtr)) {
			$arrayPtr[$arrKey] = [];
		}
		$arrayPtr = &$arrayPtr[$arrKey];
	}

	$arrayPtr[$lastKey] = $value;
}

function qo_format_nested_values($data) {
	if (!is_array($data)) {
		return $data;
	}

	$formattedData = [];
	foreach($data as $keyImploded => $value) {
		qo_split_key_and_set_value_to_pointer($formattedData, $keyImploded, $value);
	}
	return $formattedData;
}


function qo_format_tag_value($tag, $value) {
	$numeric = $tag->get_option('numeric');
	if (isset($numeric[0]) && $numeric[0] === 'true') {
		return intval($value);
	}
	return $value;
}
