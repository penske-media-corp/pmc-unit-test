<?php
/**
 * Common bootstrap file use for all theme & pmc plugin unit test
 *
 * @package pmc-unit-test
 */

// Note: We're disabling these rules in this file specific for unit test bootstrap.
// phpcs:disable WordPressVIPMinimum.Files.IncludingFile.UsingCustomFunction
// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_error_reporting
// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
// phpcs:disable WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__HTTP_USER_AGENT__
// phpcs:disable WordPressVIPMinimum.Constants.RestrictedConstants.DefiningRestrictedConstant

namespace PMC\Unit_Test;

use PMC\Unit_Test\Interfaces\Mocker as MockerInterface;
use PMC\Unit_Test\Mocks\Factory as MockerFactory;

if ( ! defined( 'IS_UNIT_TEST' ) ) {
	define( 'IS_UNIT_TEST', true );
}
if ( ! defined( 'IS_UNIT_TESTING' ) ) {
	define( 'IS_UNIT_TESTING', true );
}
if ( ! defined( 'VIP_2FA_TIME_GATE' ) ) {
	define( 'VIP_2FA_TIME_GATE', strtotime( '+1 year' ) );
}
if ( ! defined( 'VIP_FILESYSTEM_USE_STREAM_WRAPPER' ) ) {
	// Disable VIP GO CDN, @ref https://github.com/Automattic/vip-go-mu-plugins/blob/master/a8c-files.php#L71.
	define( 'VIP_FILESYSTEM_USE_STREAM_WRAPPER', false );
}

/**
 * PHPunit Bootstrap test class.
 *
 * @codeCoverageIgnore No one tests a phpunit bootstrap file.
 */
class Bootstrap {
	const DEFAULT_PRIORITY = 10;
	const EARLY_PRIORITY   = 5;
	const HIGH_PRIORITY    = 0;
	const LOW_PRIORITY     = 99999;

	/**
	 * Store the current Bootstrap instance object.
	 *
	 * @var Bootstrap
	 */
	private static $_instance = null;

	/**
	 * Store the WP unit test path.
	 *
	 * @var array|false|string
	 */
	private $_phpunit_dir = null;

	/**
	 * Store the current theme to activate.
	 *
	 * @var string|null
	 */
	private $_theme = null;

	/**
	 * Store the alternative test namespaces to be registered for auto loading.
	 *
	 * @var array
	 */
	private $_namespaces = [];

	/**
	 * Store the location of the tests path.
	 *
	 * @var string|null
	 */
	private $_tests_path = null;

	/**
	 * Store the list mock folders.
	 *
	 * @var array
	 */
	private $_mock_folders = [];

	/**
	 * The list of plugin to activate by default.
	 *
	 * @var string[]
	 */
	private $_active_plugins = [
		// Disable for now until we need to activate these for all pipelines.
		// 'jetpack/jetpack.php'.
		'amp/amp.php',
	];

	/**
	 * Return single instance of a class
	 */
	public static function get_instance() {
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new static();
		}

		return self::$_instance;
	}

	/**
	 * Constructor
	 *
	 * @throws \Exception If functions.php not found.
	 */
	public function __construct() {
		$_SERVER['HTTP_USER_AGENT'] = 'pmc-unit-test';

		$this->_phpunit_dir = getenv( 'WP_TESTS_DIR' );
		if ( ! file_exists( $this->_phpunit_dir ) ) {
			$this->_phpunit_dir = '/var/www/html/wp-tests/tests/phpunit';
		}

		if ( ! file_exists( $this->_phpunit_dir . '/includes/functions.php' ) ) {
			throw new \Exception( sprintf( 'Error, file not found: %s/includes/functions.php', $this->_phpunit_dir ) );
		}

		require_once realpath( $this->_phpunit_dir . '/includes/functions.php' );

		// Using unit test bootstrap function to add filter, the wp core has not loaded yet at this point.
		tests_add_filter( 'muplugins_loaded', [ $this, 'muplugins_loaded_late_bind' ], self::LOW_PRIORITY );
		tests_add_filter( 'muplugins_loaded', [ $this, 'muplugins_loaded_early_bind' ], self::HIGH_PRIORITY );
		tests_add_filter( 'setup_theme', [ $this, 'load_pmc_required_plugins' ], self::HIGH_PRIORITY );
		tests_add_filter( 'after_setup_theme', [ $this, 'after_setup_theme_early_bind' ], self::HIGH_PRIORITY );
		tests_add_filter( 'after_setup_theme', [ $this, 'after_setup_theme_late_bind' ], self::LOW_PRIORITY );
		tests_add_filter( 'pmc_do_not_load_plugin', [ $this, 'pmc_do_not_load_plugin' ], self::DEFAULT_PRIORITY, 4 );

		// Disable 2fa enforcement.
		tests_add_filter( 'wpcom_vip_is_two_factor_forced', '__return_false', self::LOW_PRIORITY );
		tests_add_filter( 'jetpack_sso_require_two_step', '__return_false', self::LOW_PRIORITY );

		// Preventing some deprecated triggers from firing.
		tests_add_filter( 'doing_it_wrong_trigger_error', '__return_false', self::LOW_PRIORITY );
		tests_add_filter( 'deprecated_constructor_trigger_error', '__return_false', self::LOW_PRIORITY );
		tests_add_filter( 'deprecated_function_trigger_error', '__return_false', self::LOW_PRIORITY );
		tests_add_filter( 'deprecated_hook_trigger_error', '__return_false', self::LOW_PRIORITY );
		tests_add_filter( 'pmc_deprecated_function', [ $this, 'pmc_deprecated_function' ] );
		tests_add_filter( 'pmc_doing_it_wrong', [ $this, 'pmc_doing_it_wrong' ] );

		// Adding this filter to allow workaround unit test loading plugin properly where wp options have not been set.
		tests_add_filter( 'pmc_unit_test_bootstrap_activation', '__return_true' );

		tests_add_filter( 'pre_http_request', [ 'PMC\Unit_Test\Utility', 'filter_pre_http_request' ], 10, 3 );

		$this->register_mock_folders( __DIR__ . '/../mocks' );

		$this->handle_deprecated();

	}

	/**
	 * We should not need to do this if all unittest reference the pmc unit test base class.
	 * Until then, we need to add this function to capture the deprecated warnings and remove if they are allowed.
	 *
	 * @param bool $caught Deprecated function that is caught.
	 */
	function handle_deprecated( $caught = false ) {

		if ( ! function_exists( 'apply_filters' ) ) {
			tests_add_filter( 'deprecated_function_run', [ $this, 'handle_deprecated' ], self::LOW_PRIORITY );
			tests_add_filter( 'deprecated_hook_run', [ $this, 'handle_deprecated' ], self::LOW_PRIORITY );
			tests_add_filter( 'deprecated_argument_run', [ $this, 'handle_deprecated' ], self::LOW_PRIORITY );
		} elseif ( ! apply_filters( 'pmc_deprecated_function', $caught ) ) {
			foreach ( [ 'deprecated_function_run', 'deprecated_hook_run', 'deprecated_argument_run' ] as $hook ) {
				if ( ! isset( $GLOBALS['wp_filter']['deprecated_function_run']->callbacks[10] ) ) {
					continue;
				}
				if ( empty( $GLOBALS['wp_filter'][ $hook ]->callbacks[10] ) ) {
					continue;
				}
				$function = reset( $GLOBALS['wp_filter'][ $hook ]->callbacks[10] )['function'];
				if ( ! is_array( $function ) || ! is_object( $function[0] ) ) {
					continue;
				}
				if ( is_a( $function[0], \WP_UnitTestCase_Base::class ) ) {
					$caught_deprecated = array_diff( Utility::get_hidden_property( $function[0], 'caught_deprecated' ), [ $caught ] );
					Utility::set_and_get_hidden_property( $function[0], 'caught_deprecated', $caught_deprecated );
					break;
				}
			}
		}
	}

	/**
	 * Filter to ignore deprecated function warnings/errors
	 *
	 * @param string $function Deprecated function that is caught.
	 *
	 * @return bool|string
	 */
	function pmc_deprecated_function( $function ) {

		// Allow list of the deprecates function to prevent unit test errors.
		$allowed_list = [
			'_vip_admin_gallery_css_extras',
			'_wpcom_vip_allow_more_html_in_comments',
			'category_link',
			'delete_user_attribute',
			'disable_autosave',
			'disable_right_now_comment_count',
			'do_sitemap_pings',
			'get_blog_lang_code',
			'get_top_posts',
			'get_user_attribute',
			'make_tags_local',
			'news_sitemap_uri',
			'sitemap_cache_key',
			'sitemap_content_type',
			'sitemap_discovery',
			'sitemap_endpoints',
			'sitemap_handle_update',
			'sitemap_uri',
			'Theme without footer.php',
			'Theme without header.php',
			'update_user_attribute',
			'vary_cache_on_function',
			'vary_cache_on_function',
			'vip_admin_gallery_css_extras',
			'vip_allow_title_orphans',
			'vip_disable_tag_suggest',
			'vip_doubleclick_dartiframe_redirect',
			'vip_multiple_moderators',
			'vip_remove_enhanced_feed_images',
			'vip_remove_enhanced_feed_images',
			'vip_wp_file_get_content',
			'w3cdate_from_mysql',
			'wpcom_disable_mobile_app_promotion',
			'wpcom_initiate_flush_rewrite_rules',
			'wpcom_invite_force_matching_email_address',
			'wpcom_is_vip',
			'wpcom_print_news_sitemap',
			'wpcom_print_sitemap',
			'wpcom_print_sitemap_item',
			'wpcom_print_xml_tag',
			'wpcom_sitemap_array_to_simplexml',
			'wpcom_sitemap_initstr',
			'wpcom_sitemap_n_to_news_namespace',
			'wpcom_sitemap_namespaces',
			'wpcom_uncached_get_post_by_meta',
			'wpcom_uncached_get_post_meta',
			'wpcom_vip_allow_full_size_images_for_real',
			'wpcom_vip_allow_more_html_in_comments',
			'wpcom_vip_audio_player_colors',
			'wpcom_vip_check_site_url',
			'wpcom_vip_crop_small_thumbnail',
			'wpcom_vip_debug',
			'wpcom_vip_disable_custom_customizer',
			'wpcom_vip_disable_default_subscribe_to_comments',
			'wpcom_vip_disable_devicepx_js',
			'wpcom_vip_disable_enhanced_feeds',
			'wpcom_vip_disable_geolocation_output',
			'wpcom_vip_disable_global_terms',
			'wpcom_vip_disable_hovercards',
			'wpcom_vip_disable_instapost',
			'wpcom_vip_disable_postpost',
			'wpcom_vip_disable_smilies',
			'wpcom_vip_disable_youtube_comment_embeds',
			'wpcom_vip_disable_zemanta_for_all_users',
			'wpcom_vip_enable_opengraph',
			'wpcom_vip_enable_term_order_functionality',
			'wpcom_vip_flaptor_related_posts',
			'wpcom_vip_get_flaptor_related_posts',
			'wpcom_vip_get_home_host',
			'wpcom_vip_get_meta_desc',
			'wpcom_vip_get_most_shared_posts',
			'wpcom_vip_get_resized_remote_image_url',
			'wpcom_vip_get_stats_array',
			'wpcom_vip_get_stats_csv',
			'wpcom_vip_get_stats_xml',
			'wpcom_vip_home_template_uri',
			'wpcom_vip_load_custom_cdn',
			'wpcom_vip_load_custom_cdn',
			'wpcom_vip_load_geolocation_styles_only_when_needed',
			'wpcom_vip_load_helper',
			'wpcom_vip_load_helper_stats',
			'wpcom_vip_load_helper_wpcom',
			'wpcom_vip_load_plugin',
			'wpcom_vip_meta_desc',
			'wpcom_vip_notify_on_new_user_added_to_site',
			'wpcom_vip_plugins_ui_disable_activation',
			'wpcom_vip_plugins_url',
			'wpcom_vip_remove_feed_tracking_bug',
			'wpcom_vip_remove_feed_tracking_bug',
			'wpcom_vip_remove_mediacontent_from_rss2_feed',
			'wpcom_vip_remove_opensearch',
			'wpcom_vip_remove_playlist_styles',
			'wpcom_vip_remove_polldaddy_rating',
			'wpcom_vip_require_lib',
			'wpcom_vip_stats_roles',
			'wpcom_vip_theme_dir',
			'wpcom_vip_theme_url',
			'wpcom_vip_top_post_title',
		];

		if ( in_array( $function, (array) $allowed_list, true ) ) {
			return false;  // Ignore the deprecated warning/error messages.
		}

		$allowed_patterns = [
			'File Theme without .*\.php',
		];

		foreach ( $allowed_patterns as $pattern ) {
			if ( preg_match( '/' . $pattern . '/', $function ) ) {
				return false; // Ignore the deprecated warning/error messages.
			}
		}

		return $function;

	}

	/**
	 * Passes PMC code to the list of `_doing_it_wrong()` calls.
	 *
	 * @param string $function The function to add.
	 *
	 * @return string $function The function to add.
	 */
	public function pmc_doing_it_wrong( $function ) {

		// Allowed list of the deprecated functions to prevent unit test errors.
		$allowed_list = [
			'amp_is_available', // amp 2.0.
			'is_attachment',
			'is_author',
			'is_front_page',
			'is_home',
			'is_page',
			'is_post_type_archive',
			'is_single',
			'is_singular',
			'is_tax',
			'register_rest_route',
			// TODO: 2021-06-16 - SK pipeline is failing for unexplained reasons, ethitter needs to discuss with Hau when he's back from PTO.
			'vip_safe_wp_remote_request',
			'wpcom_vip_load_plugin',
			// VIP throws this warning if 'plugins_loaded' event has not been fired.
			'WP_Scripts::localize',
			// WP 5.7.
		];

		if ( in_array( $function, (array) $allowed_list, true ) ) {
			return false;  // Ignore the deprecated warning/error message.
		}

		$allowed_patterns = [
			'was called too early and so it will not work properly',
			'Conditional query tags do not work before the query is run',
			'wpcom_vip_load_plugin',
		];

		foreach ( $allowed_patterns as $pattern ) {
			if ( preg_match( '/' . $pattern . '/', $function ) ) {
				return false; // Ignore the deprecated warning/error messages.
			}
		}

		return $function;
	}

	/**
	 * Use the wp core init event to remove all sso & login related wp core events
	 */
	public function init() {
		remove_all_actions( 'clear_auth_cookie' );
		remove_all_actions( 'jetpack_sso_handle_login' );

		/*
		 * We should not send any headers during unit tests, so disable all wp core header sent
		 *  to avoid unit test errors header already sent.
		 */
		remove_all_actions( 'send_headers' );

		// We need to prevent shutdown events from triggering unit test errors during shutdown.
		remove_all_actions( 'shutdown' );

		/*
		Workaround fix where do_action( 'init' ) trigger jetpack to reload causing
		fatal errors due to its use of require instead of require_once, @see jetpack
		function jetpack_content_options_init for details.
		*/
		if ( function_exists( 'jetpack_content_options_customize_register' ) ) {
			remove_action( 'init', 'jetpack_content_options_init' );
		}
	}

	/**
	 * We need to trigger a few events during the mu-plugins_loaded event with the highest priority
	 * The pmc-vip-client-mu-plugins require the theme to activated first.
	 */
	public function muplugins_loaded_early_bind() {
		global $wpdb;

		// Detect and setup VIP Go constants before theme is loaded.
		if ( function_exists( 'wpcom_vip_load_plugin' ) ) {
			// VIP Go Site.
			// We need these constants defined to work properly for pmc-global-functions plugin.
			if ( ! defined( 'PMC_IS_VIP_GO_SITE' ) ) {
				define( 'PMC_IS_VIP_GO_SITE', true );
			}
			if ( ! defined( 'VIP_GO_ENV' ) ) {
				define( 'VIP_GO_ENV', 'dev' );
			}
			if ( ! defined( 'VIP_GO_APP_ENVIRONMENT' ) ) {
				define( 'VIP_GO_APP_ENVIRONMENT', 'dev' );
			}
		}

		// We want the default wpdb object to display any SQL errors.
		$wpdb->suppress_errors( false );

		// This case always true during unit test, but in case dev env might not setup that way.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			/*
			* We want custom error handler to suppress php native deprecated warning messages.
			*/
			set_error_handler(
				function ( $errno, $errstr, $errfile = false ) {

					if ( 0 === error_reporting() ) {
						return false;
					}

					$suppress_messages = [
						'Array and string offset access syntax with curly braces is deprecated',
						'Function create_function() is deprecated',
					];

					if ( in_array( $errstr, (array) $suppress_messages, true ) ) {
						return true;
					}

					if ( preg_match( '/Trying to get property .* of non-object/', $errstr ) ) {
						if ( preg_match( '/co-authors/', $errfile ) ) {
							// Suppress the php notice from co authors plugin.
							// Note: We probably can suppress the message from all non pmc plugins.
							return true;
						}
					}

					$exclude_patterns = [
						'Cannot modify header information',
						'Constant .* already defined',
						'Code coverage needs to be enabled in php.ini',
					];

					foreach ( $exclude_patterns as $pattern ) {
						if ( preg_match( "/$pattern/", $errstr ) ) {
							return true;
						}
					}

					return false;

				},
				E_DEPRECATED | E_USER_DEPRECATED | E_NOTICE | E_USER_NOTICE | E_ERROR | E_WARNING | E_USER_WARNING
			);

		}

		// Workaround fatal errors where $wp_rewrite object have not been initialized.
		remove_action( 'switch_theme', 'rri_wpcom_action_switch_theme' );

		// Allow project to manually override the default theme load a custom theme for unit test.
		if ( ! empty( $this->_theme ) ) {
			switch_theme( $this->_theme );
		}

	}

	/**
	 * We need to trigger a few events during the muplugins_loaded event
	 */
	public function muplugins_loaded_late_bind() {

		// We want to hook into wp core init function and run with a very low priority so we can override some wp core events and remove filter/actions.
		add_action( 'init', [ $this, 'init' ], self::LOW_PRIORITY );

		if ( ! defined( 'JETPACK_DEV_DEBUG' ) ) {
			define( 'JETPACK_DEV_DEBUG', true );
		}

		// Setup all active plugins before we switch theme.
		// Notes: We can't use activate_plugin because function only available in wp-admin.
		if ( ! empty( $this->_active_plugins ) ) {
			$active_plugins = get_option( 'active_plugins' );
			if ( empty( $active_plugins ) ) {
				$active_plugins = [];
			}
			foreach ( $this->_active_plugins as $plugin ) {
				if ( 'php' === pathinfo( $plugin, PATHINFO_EXTENSION ) ) {
					$files_to_check = [
						$plugin,
					];
				} else {
					$files_to_check = [
						sprintf( '%s/%s.php', $plugin, $plugin ),
						sprintf( '%s/plugin.php', $plugin ),
					];
				}

				foreach ( $files_to_check as $file ) {
					if ( file_exists( sprintf( '%s/%s', WP_PLUGIN_DIR, $file ) ) ) {
						$active_plugins[] = $file;
						break;
					}
				}
			}

			if ( ! empty( $active_plugins ) ) {
				$active_plugins = array_unique( (array) $active_plugins );
				sort( $active_plugins );
				update_option( 'active_plugins', $active_plugins );
			}

		}

		add_filter(
			'jetpack_tools_to_include',
			function ( $tools ) {
				$tools = array_merge(
					$tools,
					[
						'shortcodes/youtube.php',
					]
				);

				return $tools;
			}
		);

		add_filter(
			'jetpack_active_modules',
			function ( $modules ) {
				$modules = array_merge(
					$modules,
					[
						'shortcodes',
					]
				);

				return $modules;
			}
		);

	}

	/**
	 * Helper function to load the required plugins to start unit test
	 */
	public function load_pmc_required_plugins(): void {
		static $ran = false;
		if ( $ran ) {
			return;
		}
		$ran = true;

		/*
		IMPORTANT: We need to make sure the unit test is compatible with vip go environment.
		Testing vip-init got loaded vip mu-plugins on vip go environment.
		If this function doesn't exist by now, it is safe to assume we're on classic site.
		 */
		if ( ! function_exists( 'wpcom_vip_load_plugin' ) ) {
			// VIP Classic site.
			if ( file_exists( WP_CONTENT_DIR . '/themes/vip/plugins/vip-init.php' ) ) {
				require_once( WP_CONTENT_DIR . '/themes/vip/plugins/vip-init.php' );
			}
		}

	}

	/**
	 * We would load majority of the common plugins from this wp events
	 */
	public function after_setup_theme_early_bind() {

		// Suppress warning and only reports errors, wp 5.6 is more strict and throw more warnings.
		error_reporting( E_CORE_ERROR | E_COMPILE_ERROR | E_ERROR | E_PARSE | E_USER_ERROR | E_RECOVERABLE_ERROR );

		$this->load_pmc_required_plugins();

		// Backward compatible until we get to the chance to do code clean up.
		if ( ! trait_exists( 'PMC\Global_Functions\Traits\Singleton', false ) ) {
			class_alias( 'PMC\Unit_Test\Traits\Singleton', 'PMC\Global_Functions\Traits\Singleton' );
		}

		// Remove these action to prevent header already send errors.
		remove_all_actions( 'clear_auth_cookie' );
		remove_all_actions( 'jetpack_sso_handle_login' );
		remove_all_actions( 'send_headers' );
		remove_all_actions( 'shutdown' );

		/**
		 * To fix pipeline fail deprecated notice.
		 *
		 * 'wpcom_vip_load_custom_cdn' is deprecated by VIP for VIP Go sites.
		 * ref: https://github.com/Automattic/vip-go-mu-plugins/blob/master/vip-helpers/vip-deprecated.php#L1299
		 */
		remove_action( 'parse_query', 'PMC::load_custom_cdn' );

		if ( ! empty( $this->_namespaces ) && ! empty( $this->_tests_path ) ) {
			foreach ( $this->_namespaces as $namespace ) {
				Autoloader::register( $namespace . '\Tests', $this->_tests_path );
			}
		}

	}

	/**
	 * Late bind action after theme setup
	 */
	public function after_setup_theme_late_bind() {
		$this->register_mockers();
		remove_all_filters( 'pmc_unit_test_bootstrap_activation' );

		// Set default permalink structure if empty.
		$structure = get_option( 'permalink_structure' );
		if ( empty( $structure ) ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
			// @TODO: If pmc-vertical active, set to  /%year%/%monthnum%/%vertical%/%category%/%postname%-%post_id%/.
			// else /%year%/%monthnum%/%category%/%postname%-%post_id%/.
		}

		// Amp 2.0. Not disabling autoloading.
		if ( class_exists( \AMP_Options_Manager::class )
			&& class_exists( \AMP_Theme_Support::class )
			&& interface_exists( \AmpProject\AmpWP\Option::class )
			&& class_exists( \AmpProject\AmpWP\Admin\ReaderThemes::class )
		) {
			\AMP_Options_Manager::update_option( \AmpProject\AmpWP\Option::THEME_SUPPORT, \AMP_Theme_Support::READER_MODE_SLUG );
			\AMP_Options_Manager::update_option( \AmpProject\AmpWP\Option::READER_THEME, \AmpProject\AmpWP\Admin\ReaderThemes::DEFAULT_READER_THEME );
		}

	}

	/**
	 * Filter to allow us to control and disable plugin we can't be load in unit test
	 *
	 * @param bool   $status  Whether or not to load the plugin.
	 * @param string $plugin  Plugin name.
	 * @param string $folder  Either plugins or pmc-plugins.
	 * @param string $version Plugin version.
	 *
	 * @return bool|mixed
	 */
	public function pmc_do_not_load_plugin( $status, $plugin, $folder, $version ) {
		$excludes = [
			'pmc-ndn',
			'jetpack-force-2fa',
			'new-device-notification',
			'vip-go-elasticsearch',
			'wpcom-elasticsearch',
			'fastly',
		];

		if ( in_array( $plugin, (array) $excludes, true ) ) {
			return true;  // Do not load this plugin.
		}

		// Plugin is being load, let's try to load the plugin's mockers.
		if ( 'pmc-plugins' === $folder ) {
			if ( ! empty( $version ) ) {
				$plugin .= '-' . $version;
			}

			$path_to_check = dirname( PMC_GLOBAL_FUNCTIONS_PATH );

			$folder = $path_to_check . '/' . $plugin . '/mocks';
			if ( file_exists( $folder ) ) {
				$this->_mock_folders[] = realpath( $folder );
			}

			$folder = $path_to_check . '/' . $plugin . '/tests/mocks';
			if ( file_exists( $folder ) ) {
				$this->_mock_folders[] = realpath( $folder );
			}
		}

		return $status;
	}

	/**
	 * Activate the given plugins
	 *
	 * @param array $plugins The array of plugin to activate, eg, [ 'amp', 'some-plugin' ].
	 *
	 * @return $this
	 */
	public function activate_plugins( array $plugins ): Bootstrap {
		$this->_active_plugins = array_merge( $this->_active_plugins, $plugins );

		return $this;
	}

	/**
	 * Register the folders to autoload the custom mocker interface
	 *
	 * @param string|array $folders Mock folders.
	 *
	 * @return $this
	 */
	public function register_mock_folders( $folders ) {
		foreach ( (array) $folders as $folder ) {
			$folder = realpath( $folder );
			if ( ! empty( $folder ) ) {
				$this->_mock_folders[] = $folder;
			}

		}

		return $this;
	}

	/**
	 * Register all the mocker interface from the registered mock folders
	 *
	 * @return void
	 */
	public function register_mockers() {

		$before_classes = get_declared_classes();

		foreach ( $this->_mock_folders as $folder ) {
			if ( file_exists( $folder ) ) {
				foreach ( glob( $folder . '/*.php' ) as $file ) {
					require_once realpath( $file );
				}
			}
		}

		$after_classes = get_declared_classes();

		$classes = array_diff( $after_classes, $before_classes );
		foreach ( $classes as $class ) {
			if ( preg_match( '/\\\Mocks\\\/', $class ) ) {
				if ( in_array( MockerInterface::class, (array) class_implements( $class ), true ) ) {
					MockerFactory::get_instance()->register( $class );
				}
			}
		}

		$this->_mock_folders = [];

	}

	/**
	 * The bootstrap entry point
	 *
	 * @param array $options Bootstrap test options.
	 */
	public function start( $options = [] ) {

		// Custom theme should be loaded during unit test.
		if ( ! empty( $options['theme'] ) ) {
			$this->_theme = $options['theme'];
		}

		if ( ! empty( $options['namespace'] ) ) {
			$this->_namespaces = (array) $options['namespace'];
		}

		if ( ! empty( $options['tests_path'] ) ) {
			$this->_tests_path = $options['tests_path'];
		}

		// Ignore because it's a test file.
		// WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace.
		$trace        = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 1 );
		$calling_path = dirname( $trace[0]['file'] );

		if ( empty( $this->_theme ) ) {
			if ( false === strpos( $calling_path, 'pmc-plugins' ) ) {
				$this->_theme = basename( preg_replace( '@/tests.*$@', '', $calling_path ) );
			}
		}

		if ( empty( $this->_tests_path ) ) {
			$this->_tests_path = $calling_path;
		}

		if ( empty( $this->_namespaces ) ) {
			if ( strpos( $calling_path, 'pmc-plugins' ) ) {
				$name                = basename( str_replace( '/tests', '', $calling_path ) );
				$name                = ucwords( str_replace( [ 'pmc-', '-' ], [ '', ' ' ], $name ) );
				$this->_namespaces[] = 'PMC\\' . str_replace( ' ', '_', $name );
			} else {
				$name                = basename( str_replace( '/tests', '', $calling_path ) );
				$name                = preg_replace( '/-\d+/', '', $name );
				$name1               = ucwords( str_replace( [ 'pmc-', '-' ], [ '', ' ' ], $name ) );
				$name2               = ucwords( str_replace( [ 'pmc-', '-' ], [ 'PMC ', ' ' ], $name ) );
				$this->_namespaces[] = str_replace( ' ', '_', $name1 );
				if ( $name1 !== $name2 ) {
					$this->_namespaces[] = str_replace( ' ', '_', $name2 );
				}
			}
		}

		/*
		Add support to auto-detect whether the theme is a vip/pmc-theme or just plain pmc-theme.
		This is needed for the transition from themes/vip/pmc-theme to themes/pmc-theme structure for VIPGO sites.
		*/
		if ( ! empty( $this->_theme )
			&& false === strpos( $this->_theme, 'vip/' )
			&& false !== strpos( $calling_path, '/vip/' ) ) {
			$this->_theme = 'vip/' . $this->_theme;
		}

		// If no default theme is defined,define one if a theme is given.
		if ( ! defined( 'WP_DEFAULT_THEME' ) && ! empty( $this->_theme ) ) {
			define( 'WP_DEFAULT_THEME', $this->_theme );
		}

		if ( ! empty( $options['activate_plugins'] ) && is_array( $options['activate_plugins'] ) ) {
			$this->activate_plugins( $options['activate_plugins'] );
		}

		// Start the unit test.
		require realpath( $this->_phpunit_dir . '/includes/bootstrap.php' );
	}

}
