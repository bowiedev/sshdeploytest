<?php

namespace wpengine\sign_on_plugin;

require_once __DIR__ . '/security-checks.php';
\wpengine\sign_on_plugin\check_security();

class Logger {

	const INSTALL_NAME_ERROR               = 'error_on_invalid_install_name';
	const NONCE_META_DATA_VALIDATION_ERROR = 'error_on_nonce_meta_data_validation';
	const MULTISITE_ENABLED_ERROR          = 'error_multisite_enabled';
	const GENERAL_EXCEPTION_ERROR          = 'error_general_exception';
	const ADD_CREATED_TIME_USER_META_ERROR = 'error_on_add_created_time_user_meta';
	const USER_CREATE_ERROR                = 'error_on_user_create';
	const IMPERSONATED_USER_ERROR          = 'error_on_user_impersonation';

	public static function log( $event, $data ) {
		try {
			$output_data = wp_json_encode( $data );
		} catch ( \Exception $e ) {
			// phpcs:ignore
			$output_data = print_r( $data, true );
		}
		// phpcs:ignore
		error_log( "wpeseamlessloginplugin:event=$event " . $output_data );
	}
}
