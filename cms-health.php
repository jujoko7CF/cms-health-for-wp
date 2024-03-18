<?php
/**
 * CMS Health
 *
 * @author            jujoko7CF
 *
 * @wordpress-plugin
 * Plugin Name:       CMS Health
 * Plugin URI:        https://github.com/cms-health-project
 * Description:       Client plugin for "CMS Health Project". #CFHack2024
 * Version:           0.0.4.poc
 * Requires PHP:      8.1
 * Author:            jujoko7CF
 * Author URI:        https://jujoko7cf.com
 * Text Domain:       cms-health
 */

use CmsHealth\Definition\CheckResultStatus;
use CmsHealth\Definition\HealthCheckStatus;

require_once 'vendor/autoload.php';

class CMS_Health_Result_Full_Check implements CmsHealth\Definition\HealthCheckInterface, JsonSerializable {
	private HealthCheckStatus $status;
	private string $version;
	private string $serviceId;
	private string $descriptions;
	private array $checks = array();

	public function __construct() {}

	public function getStatus(): HealthCheckStatus { return $this->status; }
	public function getVersion(): string { return $this->version; }
	public function getServiceId(): string { return $this->serviceId; }
	public function getDescription(): string { return $this->description; }
	public function getChecks(): array { return $this->checks; }

	public function setStatus( HealthCheckStatus $status ): void { $this->status = $status; }
	public function setVersion( string $version ): void { $this->version = $version; }
	public function setServiceId( string $serviceId ): void { $this->serviceId = $serviceId; }
	public function setDescription( string $description ): void { $this->description = $description; }
	public function addCheck( CMS_Health_Result_Single_Check $check ): void { $this->checks[ $check->getIdentifier() ] = $check; }

	public function jsonSerialize(): array {
        return [
            'status' => $this->status->value,
            'version' => $this->version,
            'serviceId' => $this->serviceId,
            'description' => $this->description,
            'checks' => $this->checks,
        ];
    }
}

class CMS_Health_Result_Single_Check implements CmsHealth\Definition\CheckInterface, JsonSerializable {
	private string $identifier;
	private array $checkResults = array();

	public function __construct() {}

	public function getIdentifier(): string { return $this->identifier; }
	public function getCheckResults(): array { return $this->checkResults; }

	public function setIdentifier( string $identifier ): void { $this->identifier = $identifier; }
	public function addCheckResult( CMS_Health_Result_Single_Check_Result $check_result ): void { $this->checkResults[] = $check_result; }

	public function jsonSerialize(): array {
        return $this->getCheckResults();
    }
}

class CMS_Health_Result_Single_Check_Result implements CmsHealth\Definition\CheckResultInterface, JsonSerializable {
	private string $componentId;
	private string $componentType;
	private CheckResultStatus $status;
	private string $observedValue;
	private ?string $observedUnit;
	private string $output;
	private DateTime $time;

	public function __construct() {}

	public function getComponentId(): string { return $this->componentId; }
	public function getComponentType(): string { return $this->componentType; }
	public function getStatus(): CheckResultStatus { return $this->status; }
	public function getObservedValue(): string { return $this->observedValue; }
	public function getObservedUnit(): string|null { return $this->observedUnit; }
	public function getOutput(): string { return $this->output; }
	public function getTime(): DateTime { return $this->identifier; }

	public function setComponentId( string $componentId ): void { $this->componentId = $componentId; }
	public function setComponentType( string $componentType ): void { $this->componentType = $componentType; }
	public function setStatus( CheckResultStatus $status ): void { $this->status = $status; }
	public function setObservedValue( string $observedValue ): void { $this->observedValue = $observedValue; }
	public function setObservedUnit( string|null $observedUnit ): void { $this->observedUnit = $observedUnit; }
	public function setOutput( string $output ): void { $this->output = $output; }
	public function setTime( DateTime $time ): void { $this->time = $time; }

	public function jsonSerialize(): array {
        $result = [
            'componentId' => $this->componentId,
            'componentType' => $this->componentType,
            'observedValue' => $this->observedValue,
            'status' => $this->status,
            'time' => $this->time->format('c'),
            'output' => $this->output,
        ];

        if ( isset( $this->observedUnit ) ) {
            $result['observedUnit'] = $this->observedUnit;
        }

        return $result;
    }
}

final class CMS_Health_Options {
	const OPTION_NAME = 'cms-health-options';
	const SITE_HEALTH_TAB_NAME = 'cms-health';

	static function get_all() {
		return get_option( static::OPTION_NAME, array() );
	}

	static function get( $option, $default_value ) {
		$all_options = static::get_all();

		if ( array_key_exists( $option, $all_options ) ) {
			return $all_options[ $option ];
		}

		return $default_value;
	}

	static function update_all( $value = array() ) {
		if ( empty( $value ) ) {
			return delete_option( static::OPTION_NAME );
		}

		return update_option( static::OPTION_NAME, $value );
	}

	static function update( $option, $value = null ) {
		$all_options = static::get_all();

		if ( ! isset( $value ) ) {
			unset( $all_options[ $option ] );
		} else {
			$all_options[ $option ] = $value;
		}

		static::update_all( $all_options );
	}

	static function init_settings_page() {
		add_filter( 'site_health_navigation_tabs', array( __CLASS__, 'add_site_health_tab' ), PHP_INT_MAX );

		add_action( 'site_health_tab_content', array( __CLASS__, 'site_health_tab_content' ) );

		add_filter( 'cms-health/settings/sections', array( __CLASS__, 'add_default_settings_sections' ), 5 );

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_settings_scripts' ) );
		add_action( 'wp_ajax_cms_health_check_save_options', array( __CLASS__, 'ajax_action' ) );
	}

	static function add_site_health_tab( $tabs ) {
		$tabs[ static::SITE_HEALTH_TAB_NAME ] = __( 'CMS Health', 'cms-health' );
		return $tabs;
	}

	static function site_health_tab_content( $tab ) {
		if ( static::SITE_HEALTH_TAB_NAME !== $tab ) return;

		include_once( plugin_dir_path( __FILE__ ) . '/views/admin/site-health-check/cms-health.php' );
	}

	static function add_default_settings_sections( $sections ) {
		$default_sections = array(
			array(
				'label' => __( 'Security Token', 'cms-health' ),
				'template' => plugin_dir_path( __FILE__ ) . '/views/admin/site-health-check/tabs/security-token.php',
			),
			array(
				'label' => __( 'Checks', 'cms-health' ),
				'template' => plugin_dir_path( __FILE__ ) . '/views/admin/site-health-check/tabs/check-selection.php',
			),
		);

		return array_merge( $sections, $default_sections );
	}

	static function enqueue_settings_scripts() {
		wp_enqueue_script( 'cms-health-ajax', plugin_dir_url( __FILE__ ) . 'js/cms-health-ajax.js', array( 'jquery' ) );
	}

	static function ajax_action() {
		$data = $_POST['form_data'] ?? array();
		$data = wp_list_pluck( $data, 'value', 'name' );

		switch ( $data['form-action'] ?? '' ) {
			case '#regenerate-token':
				if ( ! wp_verify_nonce( $data['_wpnonce'] ?? '', 'cms-health-regenerate-token' ) ) {
					wp_send_json_error();
				}

				$token = wp_generate_password( 24, false );

				static::update( 'security-token', wp_hash_password( $token ) );

				wp_send_json_success( array(
					'token' => $token
				) );
				break;
			case '#save-enabled-checks':
				if ( ! wp_verify_nonce( $data['_wpnonce'] ?? '', 'cms-health-save-enable-checks' ) ) {
					wp_send_json_error();
				}

				$data = http_build_query( $data );
				parse_str( $data, $data );

				$active_checks = array_values($data['cms-health-enabled-checks'] ?? array() );
				$all_checks = array_keys( CMS_Health_Checks::get_all( false ) );

				$inactive_checks = array_diff( $all_checks, $active_checks );

				static::update( 'inactive-checks', $inactive_checks );

				wp_send_json_success();
				break;
			default:
				wp_send_json_error();
		}
	}
}
CMS_Health_Options::init_settings_page();

final class CMS_Health_Rest_API {
	private static $_current_check = false;

	static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_route' ) );
	}

	static function register_rest_route() {
		register_rest_route( 'cms-health/v1', '/(?P<check>.+)', array(
			'methods' => 'GET',
			'callback' => array( __CLASS__, 'rest_response' ),
			'permission_callback' => array( __CLASS__, 'permission_callback' ),
		    'args' => array(
				'check' => array(
					'validate_callback' => function( $param, $request, $key ) {
						return
							is_string( $param ) &&
							(
								array_key_exists( $param, CMS_Health_Checks::get_all() ) ||
								'all' === $param
							);
					}
				),
		    ),
		) );
	}

	public static function permission_callback( WP_REST_Request $request ) {
//		$token = $request->get_param( 'token' );
	    $token = $request->get_header( 'Authorization' );

		$hash = CMS_Health_Options::get( 'security-token', false );
		return $hash && wp_check_password( $token, $hash );
	}

	public static function rest_response( WP_REST_Request $request ) {
		switch_to_locale( 'en_US' );

		$url = home_url();
		$parsed_url = parse_url( $url );
		$host = $parsed_url['host'] ?? '';
		$path = $parsed_url['path'] ?? '';
		$domain = "{$host}{$path}";

		$check_id = $request->get_param( 'check' );

		if ( 'all' === $check_id ) {
			$checks = CMS_Health_Checks::get_all();
		} else {
			$checks = array( CMS_Health_Checks::get( $check_id ) );
		}

		$warn = $fail = false;

		$full_report = new CMS_Health_Result_Full_Check();

		foreach ( $checks as $check_id => $check ) {
			try {
				$result = static::_perform( $check_id );

				$single_check = new CMS_Health_Result_Single_Check();
				$single_check->setIdentifier( "{$result['badge']['label']}:{$check_id}" );

				$single_check_result = new CMS_Health_Result_Single_Check_Result();
				$single_check_result->setComponentId( "{$result['badge']['label']}:{$check_id}" );
				$single_check_result->setComponentType( match ( $result['badge']['label'] ) {
					'Performance',
					'Security' => 'system',
					default => 'component'
				} );

				$status = match ( $result['status'] ) {
					'recommended' => CheckResultStatus::Warn,
					'critical' => CheckResultStatus::Fail,
					'good' => CheckResultStatus::Pass,
					default => CheckResultStatus::Info
				};
				$warn = $warn || ( CheckResultStatus::Warn === $status );
				$fail = $fail || ( CheckResultStatus::Fail === $status );

				$single_check_result->setStatus( $status );

				$single_check_result->setObservedValue( '' );
				$single_check_result->setOutput( $result['label'] );
				$single_check_result->setTime( new DateTime() );

				$single_check->addCheckResult( $single_check_result );

				$full_report->addCheck( $single_check );
			} catch ( Exception $e ) {}
		}

		$full_report->setStatus( $fail ? HealthCheckStatus::Fail : ( $warn ? HealthCheckStatus::Warn : HealthCheckStatus::Pass ) );
		$full_report->setVersion( '1' );
		$full_report->setServiceId( $domain );
		$full_report->setDescription( "Health of WordPress website {$domain}" );

		return $full_report;
	}

	private static function _perform( $check_id ) {
		static::$_current_check = $check_id;
		$start_time = microtime( true );

		$check = CMS_Health_Checks::get( $check_id );
		$result = call_user_func( $check['callback'] );

		$end_time = microtime( true );
		$result['duration'] = $end_time - $start_time;
		static::$_current_check = false;

		return $result;
	}

	static function current() {
		return static::$_current_check;
	}
}
CMS_Health_Rest_API::init();

final class CMS_Health_Checks {
	private static $_all;
	private static $_active;

	static function get_all( $only_active = true ) {
		if ( $only_active ) {
			if ( isset( static::$_active ) ) {
				return static::$_active;
			}
		} else {
			if ( isset( static::$_all ) ) {
				return static::$_all;
			}
		}

		static::$_all = array();

//		static::_register_check( 'debug', array(
//			'label' => 'DEBUG',
//			'callback' => function() {
//				return Site_Health_Check_Converter::get_all();
//			},
//		) );
//
//		static::_register_check( 'php-version', array(
//			'label' => 'PHP Version',
//			'callback' => 'phpversion',
//		) );
//
//		static::_register_check( 'is-ssl', array(
//			'label' => 'SSL Verschlüsselung',
//			'callback' => 'is_ssl',
//		) );

		static::_register_wp_site_health_checks();

		do_action( 'cms-health/init' );

		static::$_active = array();

		$inactive = CMS_Health_Options::get( 'inactive-checks', array() );

		foreach ( static::$_all as $check_id => $check ) {
			if ( ! in_array( $check_id, $inactive ) ) {
				static::$_active[ $check_id ] = $check;
			}
		}

		return static::get_all( $only_active );
	}

	private static function _register_wp_site_health_checks() {
		$site_health_checks = WP_Site_Health::get_tests();

		foreach ( $site_health_checks['direct'] as $check_id => &$check ) {
			if ( ! empty( $check['skip_cron'] ?? '' ) ) continue;

			$check['callback'] = array(
				new WP_Site_Health_To_CMS_Health_Converter( $check_id, $check ),
				'direct_callback'
			);
			static::_register( $check_id, $check );
		}
		unset( $check );

		foreach ( $site_health_checks['async'] as $check_id => &$check ) {
			if ( ! empty( $check['skip_cron'] ?? '' ) ) continue;

			$check['callback'] = array(
				new WP_Site_Health_To_CMS_Health_Converter( $check_id, $check ),
				'async_callback'
			);
			static::_register( $check_id, $check );
		}
		unset( $check );
	}

	static function get( $check_id, $only_active = true ) {
		$all = static::get_all( $only_active );

		return array_key_exists( $check_id, $all ) ? $all[ $check_id ] : false;
	}

	static function register( $check_id, $check = array() ) {
		if ( ! did_action( 'cms-health/init' ) ) {
			return new WP_Error(
				__FUNCTION__,
				sprintf(
					/* translators: %s: cms-health/init */
					__( 'CMS health checks must be registered on the %s action.', 'cms-health' ),
					'<code>cms-health/init</code>'
				),
			);
		}

		if ( ! is_string( $check_id ) ) {
			return new WP_Error(
				__FUNCTION__,
				sprintf(
					/* translators: 1: $args, 2: The REST API route being registered. */
					__( 'Health check identifier %1$s should be a string. Non-string value detected for %1$s.', 'cms-health' ),
					'<code>$check_id</code>',
				)
			);
		}

		return static::_register( $check_id, $check );
	}

	private static function _register( $check_id, $check = array() ) {
		$check = apply_filters( 'cms-health/register_check_args', $check, $check_id );
		$check = apply_filters( "cms-health/register_{$check_id}_check_args", $check, $check_id );

		if ( false === $check ) {
			return false;
		}

		if ( ! is_array( $check ) ) {
			return new WP_Error(
				__FUNCTION__,
				sprintf(
					/* translators: 1: $args, 2: The REST API route being registered. */
					__( 'Health check %1$s should be an array. Non-array value detected for %2$s.', 'cms-health' ),
					'<code>$args</code>',
					'<code>' . $check_id . '</code>'
				)
			);
		}

		$default_args = array(
			'label' => $check_id,
			'callback' => '__return_true',
		);

		$check = wp_parse_args( $check, $default_args );

		if ( ! is_callable( $check['callback'] ) ) {
			return new WP_Error(
				__FUNCTION__,
				sprintf(
					/* translators: 1: $args, 2: The REST API route being registered. */
					__( 'Health check %1$s should be a callable. Non-callable value detected for %2$s.', 'cms-health' ),
					'<code>$args["callback"]</code>',
					'<code>' . $check_id . '</code>'
				)
			);
		}

		static::$_all[ $check_id ] = $check;

		return true;
	}
}

final class WP_Site_Health_To_CMS_Health_Converter {
	function __construct(
		public $check_id,
		public $check
	) {}

	static function _perform_test( $callback ) {
		return apply_filters( 'site_status_test_result', call_user_func( $callback ) );
	}

	function direct_callback() {
		$wp_site_health = WP_Site_Health::get_instance();

		require_once trailingslashit( ABSPATH ) . 'wp-admin/includes/admin.php';
		// load all necessary files.
//		switch ( $this->check_id ) {
//			case 'plugin_version':
//				if ( ! function_exists( 'get_plugin_updates' ) )
//					require_once( ABSPATH . 'wp-admin/includes/update.php' );
//				break;
//			case 'wordpress_version':
//				if ( ! function_exists( 'get_core_updates' ) )
//					require_once( ABSPATH . 'wp-admin/includes/update.php' );
//				break;
//			case 'php_version':
//				if ( ! function_exists( 'wp_check_php_version' ) )
//					require_once( ABSPATH . 'wp-admin/includes/misc.php' );
//				break;
//			default:
//				break;
//		}

		if ( is_string( $this->check['test'] ) ) {
			$test_function = sprintf(
				'get_test_%s',
				$this->check['test']
			);

			if ( method_exists( $wp_site_health, $test_function ) && is_callable( array( $wp_site_health, $test_function ) ) ) {
				return static::_perform_test( array( $wp_site_health, $test_function ) );
			}
		}

		if ( is_callable( $this->check['test'] ) ) {
			return static::_perform_test( $this->check['test'] );
		}
	}

	function async_callback() {
		if ( isset( $this->check['async_direct_test'] ) && is_callable( $this->check['async_direct_test'] ) ) {
			return static::_perform_test( $this->check['async_direct_test'] );
		}

		if ( is_string( $this->check['test'] ) ) {
			if ( isset( $this->check['has_rest'] ) && $this->check['has_rest'] ) {
				$url = add_query_arg( array( '_locale' => 'user' ), $this->check['test'] );

				$request = new WP_REST_Request( 'GET', $url );
				$request->set_body_params( array_merge(
					$request->get_body_params(),
					array( '_wpnonce' => wp_create_nonce( 'wp_rest' ) )
				) );

				$response = rest_do_request( $request );
				return $response->get_data();
			} else {
				return 'AJAX';
				return wp_remote_post(
					admin_url( 'admin-ajax.php' ),
					array(
						'body' => array(
							'action'   => $this->check['test'],
							'_wpnonce' => wp_create_nonce( 'health-check-site-status' ),
						),
					)
				);
			}
		}
	}
}