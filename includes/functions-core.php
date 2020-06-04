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

function qo_get_tags_keyed_by_formatted_keys(): array {
	$manager = WPCF7_ShortcodeManager::get_instance();
	$tags = $manager->get_scanned_tags();
	$webhookTags = [];
	foreach($tags as $tag) {
		if (count($tag['options']) > 0) {
			foreach($tag['options'] as $option) {
				if (substr($option, 0, 8) === 'webhook:') {
					$webhookTags[str_replace(':', '.', substr($option, 8))] = $tag;
					break;
				}
			}
		}
	}
	return $webhookTags;
}

function qo_throw_errors($errors, $message = null) {
	$id = (int) $_POST['_wpcf7'];
	$unit_tag = wpcf7_sanitize_unit_tag( $_POST['_wpcf7_unit_tag'] );

	if ( $contact_form = wpcf7_contact_form( $id ) ) {
		$items = array(
			'into'     => '#' . $unit_tag,
			'status' => 'validation_failed',
			'message' => $contact_form->message( 'validation_error' ),
		);

		if (!is_null($message)) {
			$items['message'] = $message;
		}

		$webhookTags = qo_get_tags_keyed_by_formatted_keys();



		$validator = new WPCF7_Validation();

		foreach($errors as $key => $error) {
			if (isset($webhookTags[$key])) {
				$errorString = implode('\n', $error);
				$validator->invalidate($webhookTags[$key], $errorString);
			}
		}
		$invalidFields = $validator->get_invalid_fields();

		$invalids = array();

		foreach ( $invalidFields as $name => $field ) {
			$invalids[] = array(
				'into' => 'span.wpcf7-form-control-wrap.' . sanitize_html_class( $name ),
				'message' => $field['reason'],
				'idref' => $field['idref'] );
		}

		$items['invalidFields'] = $invalids;

		exit(wp_json_encode( $items ));
	}
}

add_filter( 'wpcf7_validate_password*', 'qo_validate_password_min_length', 20, 2 );
add_filter( 'wpcf7_validate_password', 'qo_validate_password_min_length', 20, 2 );

function qo_validate_password_min_length( $result, $tag ) {
	$minlength = $tag->get_option('minlength');
	if (isset($minlength[0])) {
		$value = ( ! empty( $_POST[ $tag->name ] ) ) ? $_POST[ $tag->name ] : '';
		if (strlen($value) < $minlength[0]) {
			$result->invalidate( $tag, sprintf('Min %s chars.', $minlength[0]));
		}
	}

	return $result;
}

