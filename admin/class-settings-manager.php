<?php
/**
 * CustomKings Product Personalizer - Settings Manager
 *
 * @package CustomKings_Product_Personalizer
 * @subpackage Admin
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * CKPP_Settings_Manager Class.
 *
 * Manages the settings page and its functionalities.
 */
class CKPP_Settings_Manager {
	/**
	 * Manages the settings page and its functionalities for the CustomKings Product Personalizer plugin.
	 *
	 * This class is responsible for registering all plugin settings, defining settings sections and fields,
	 * sanitizing input data, and rendering the HTML for the settings form in the WordPress admin area.
	 *
	 * @since 1.0.0
	 * @access public
	 */

	/**
	 * Constructor for CKPP_Settings_Manager.
	 *
	 * Hooks into `admin_init` to register the plugin's settings.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Registers all plugin settings, sections, and fields with the WordPress Settings API.
	 *
	 * This method defines the structure of the plugin's settings page, including
	 * various input fields for general configuration, caching, security, logging,
	 * database optimization, and UI customization.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function register_settings(): void {
		// Register the main settings group for general plugin options.
		register_setting(
			'ckpp_settings_group', // Option group
			CKPP_Config::OPTION_GENERAL, // Option name
			array( $this, 'sanitize_general_settings' ) // Sanitize callback
		);

		// Add a settings section for general plugin settings.
		add_settings_section(
			'ckpp_general_section', // ID
			__( 'General Settings', 'customkings-product-personalizer' ), // Title
			null, // Callback (no specific callback for the section itself)
			'ckpp_settings_page' // Page slug where this section will appear
		);

		// 1. General Settings
		add_settings_field(
			'ckpp_enable_plugin', // ID
			__( 'Enable Product Personalizer', 'customkings-product-personalizer' ), // Title
			array( $this, 'render_enable_plugin_field' ), // Callback function to render the field
			'ckpp_settings_page', // Page slug
			'ckpp_general_section' // Section ID
		);
		add_settings_field(
			'ckpp_accent_color', // ID
			__( 'Accent Color', 'customkings-product-personalizer' ), // Title
			array( $this, 'render_accent_color_field' ), // Callback
			'ckpp_settings_page', // Page
			'ckpp_general_section' // Section
		);

		// 2. Design Settings
		add_settings_field(
			'ckpp_default_design_width', // ID
			__( 'Default Design Width (px)', 'customkings-product-personalizer' ), // Title
			array( $this, 'render_default_design_width_field' ), // Callback
			'ckpp_settings_page', // Page
			'ckpp_general_section' // Section
		);
		add_settings_field(
			'ckpp_default_design_height', // ID
			__( 'Default Design Height (px)', 'customkings-product-personalizer' ), // Title
			array( $this, 'render_default_design_height_field' ), // Callback
			'ckpp_settings_page', // Page
			'ckpp_general_section' // Section
		);
		add_settings_field(
			'ckpp_design_upload_dir', // ID
			__( 'Design Upload Directory', 'customkings-product-personalizer' ), // Title
			array( $this, 'render_design_upload_dir_field' ), // Callback
			'ckpp_settings_page', // Page
			'ckpp_general_section' // Section
		);

		// 3. Performance & Caching
		add_settings_field(
			'ckpp_enable_caching', // ID
			__( 'Enable Caching', 'customkings-product-personalizer' ), // Title
			array( $this, 'render_enable_caching_field' ), // Callback
			'ckpp_settings_page', // Page
			'ckpp_general_section' // Section
		);
		add_settings_field(
			'ckpp_cache_duration', // ID
			__( 'Cache Duration (hours)', 'customkings-product-personalizer' ), // Title
			array( $this, 'render_cache_duration_field' ), // Callback
			'ckpp_settings_page', // Page
			'ckpp_general_section' // Section
		);

		// 4. Security & Uploads
		add_settings_field(
			'ckpp_enable_security_features', // ID
			__( 'Enable Security Features', 'customkings-product-personalizer' ), // Title
			array( $this, 'render_enable_security_features_field' ), // Callback
			'ckpp_settings_page', // Page
			'ckpp_general_section' // Section
		);
		add_settings_field(
			'ckpp_allowed_file_types', // ID
			__( 'Allowed File Types (comma-separated)', 'customkings-product-personalizer' ), // Title
			array( $this, 'render_allowed_file_types_field' ), // Callback
			'ckpp_settings_page', // Page
			'ckpp_general_section' // Section
		);
		add_settings_field(
			'ckpp_max_upload_size', // ID
			__( 'Max Upload Size (MB)', 'customkings-product-personalizer' ), // Title
			array( $this, 'render_max_upload_size_field' ), // Callback
			'ckpp_settings_page', // Page
			'ckpp_general_section' // Section
		);

		// 5. Logging & Debugging
		add_settings_field(
			'ckpp_enable_logging', // ID
			__( 'Enable Logging', 'customkings-product-personalizer' ), // Title
			array( $this, 'render_enable_logging_field' ), // Callback
			'ckpp_settings_page', // Page
			'ckpp_general_section' // Section
		);
		add_settings_field(
			'ckpp_log_file_path', // ID
			__( 'Log File Path', 'customkings-product-personalizer' ), // Title
			array( $this, 'render_log_file_path_field' ), // Callback
			'ckpp_settings_page', // Page
			'ckpp_general_section' // Section
		);
		add_settings_field(
			'ckpp_debug_mode', // ID
			__( 'Enable Debug Mode', 'customkings-product-personalizer' ), // Title
			array( $this, 'render_debug_mode_field' ), // Callback
			'ckpp_settings_page', // Page
			'ckpp_general_section' // Section
		);

		// 6. Database Optimization
		add_settings_field(
			'ckpp_optimize_tables', // ID
			__( 'Tables to Optimize (comma-separated)', 'customkings-product-personalizer' ), // Title
			array( $this, 'render_optimize_tables_field' ), // Callback
			'ckpp_settings_page', // Page
			'ckpp_general_section' // Section
		);
		add_settings_field(
			'ckpp_optimization_frequency', // ID
			__( 'Optimization Frequency', 'customkings-product-personalizer' ), // Title
			array( $this, 'render_optimization_frequency_field' ), // Callback
			'ckpp_settings_page', // Page
			'ckpp_general_section' // Section
		);
		add_settings_field(
			'ckpp_optimization_time', // ID
			__( 'Optimization Time (HH:MM)', 'customkings-product-personalizer' ), // Title
			array( $this, 'render_optimization_time_field' ), // Callback
			'ckpp_settings_page', // Page
			'ckpp_general_section' // Section
		);
	}

	/**
	 * Sanitizes the general settings array before saving to the database.
	 *
	 * This callback function is registered with `register_setting()` and is
	 * responsible for validating and sanitizing all input fields from the
	 * general settings section.
	 *
	 * @param array $input The raw input array from the settings form.
	 * @return array The sanitized and validated settings array.
	 * @since 1.0.0
	 * @access public
	 */
	public function sanitize_general_settings( array $input ): array {
		$new_input = array();
		$old_options = CKPP_Config::get( 'general' ); // Get current settings for comparison

		// Explicitly handle accent_color first to ensure it's always processed
		$new_input['accent_color'] = isset( $input['accent_color'] ) ? sanitize_hex_color( $input['accent_color'] ) : '#0073aa';

		$security_relevant_settings = [
			'enable_security_features',
			'allowed_file_types',
			'max_upload_size',
			'enable_logging',
			'log_file_path',
			'debug_mode',
			'design_upload_dir', // This can be security relevant if changed to a non-secure location
		];

		foreach ( $input as $key => $value ) {
			// Skip accent_color as it's handled above
			if ( $key === 'accent_color' ) {
				continue;
			}

			$old_value = isset( $old_options[ $key ] ) ? $old_options[ $key ] : null;
			$sanitized_value = null;

			switch ( $key ) {
				case 'enable_plugin':
				case 'enable_caching':
				case 'enable_security_features':
				case 'enable_logging':
				case 'debug_mode':
					$sanitized_value = (bool) $value;
					break;
				case 'default_design_width':
				case 'default_design_height':
				case 'cache_duration':
				case 'max_upload_size':
					$sanitized_value = absint( $value );
					break;
				case 'design_upload_dir':
				case 'allowed_file_types':
				case 'log_file_path':
				case 'optimize_tables':
				case 'optimization_frequency':
				case 'optimization_time':
					$sanitized_value = sanitize_text_field( $value );
					break;
				default:
					$sanitized_value = sanitize_text_field( $value ); // Fallback for any unhandled fields
					break;
			}
			$new_input[ $key ] = $sanitized_value;

			// Log security-relevant changes
			if ( in_array( $key, $security_relevant_settings, true ) && $old_value !== $sanitized_value ) {
				CKPP_Error_Handler::log_security_event(
					'Configuration Change: ' . $key,
					[
						'old_value'  => $old_value,
						'new_value'  => $sanitized_value,
						'user_id'    => get_current_user_id(),
						'ip_address' => $_SERVER['REMOTE_ADDR'],
					]
				);
			}
		}

		// Ensure all expected fields are present, even if not in input (e.g., unchecked checkboxes)
		$new_input['enable_plugin']            = isset( $new_input['enable_plugin'] ) ? $new_input['enable_plugin'] : false;
		$new_input['default_design_width']     = isset( $new_input['default_design_width'] ) ? $new_input['default_design_width'] : 1000;
		$new_input['default_design_height']    = isset( $new_input['default_design_height'] ) ? $new_input['default_design_height'] : 1000;
		$new_input['design_upload_dir']        = isset( $new_input['design_upload_dir'] ) ? $new_input['design_upload_dir'] : '';
		$new_input['enable_caching']           = isset( $new_input['enable_caching'] ) ? $new_input['enable_caching'] : false;
		$new_input['cache_duration']           = isset( $new_input['cache_duration'] ) ? $new_input['cache_duration'] : 24;
		$new_input['enable_security_features'] = isset( $new_input['enable_security_features'] ) ? $new_input['enable_security_features'] : false;
		$new_input['allowed_file_types']       = isset( $new_input['allowed_file_types'] ) ? $new_input['allowed_file_types'] : 'png,jpg,jpeg,gif';
		$new_input['max_upload_size']          = isset( $new_input['max_upload_size'] ) ? $new_input['max_upload_size'] : 5;
		$new_input['enable_logging']           = isset( $new_input['enable_logging'] ) ? $new_input['enable_logging'] : false;
		$new_input['log_file_path']            = isset( $new_input['log_file_path'] ) ? $new_input['log_file_path'] : '';
		$new_input['debug_mode']               = isset( $new_input['debug_mode'] ) ? $new_input['debug_mode'] : false;
		$new_input['optimize_tables']          = isset( $new_input['optimize_tables'] ) ? $new_input['optimize_tables'] : '';
		$new_input['optimization_frequency']   = isset( $new_input['optimization_frequency'] ) ? $new_input['optimization_frequency'] : 'weekly';
		$new_input['optimization_time']        = isset( $new_input['optimization_time'] ) ? $new_input['optimization_time'] : '02:00';
		// The accent_color is already handled above, no need to re-set default here.

		CKPP_Config::clear_cache(); // Clear CKPP_Config's internal cache to ensure fresh data on next load
		// Also clear WordPress's object cache for this option to ensure get_option() retrieves fresh data.
		wp_cache_delete( CKPP_Config::OPTION_GENERAL, 'options' );
		return $new_input;
	}

	/**
	 * Renders the checkbox field for enabling/disabling the CustomKings Product Personalizer plugin.
	 *
	 * This field allows administrators to quickly activate or deactivate the entire plugin
	 * functionality from the settings page.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function render_enable_plugin_field(): void {
		$options = CKPP_Config::get( 'general' );
		$checked = isset( $options['enable_plugin'] ) ? checked( 1, $options['enable_plugin'], false ) : '';
		echo '<input type="checkbox" id="ckpp_enable_plugin" name="' . esc_attr( CKPP_Config::OPTION_GENERAL ) . '[enable_plugin]" value="1"' . $checked . ' />';
		echo '<label for="ckpp_enable_plugin">' . esc_html__( 'Check to enable the CustomKings Product Personalizer plugin.', 'customkings-product-personalizer' ) . '</label>';
	}

	/**
	 * Renders the input field for the default design width.
	 *
	 * This field allows administrators to set the default width in pixels for
	 * new product designs created using the personalizer.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function render_default_design_width_field(): void {
		$options = CKPP_Config::get( 'general' );
		$value   = isset( $options['default_design_width'] ) ? absint( $options['default_design_width'] ) : 1000;
		echo '<input type="number" id="ckpp_default_design_width" name="' . esc_attr( CKPP_Config::OPTION_GENERAL ) . '[default_design_width]" value="' . esc_attr( $value ) . '" />';
		echo '<p class="description">' . esc_html__( 'Enter the default width for new designs in pixels.', 'customkings-product-personalizer' ) . '</p>';
	}

	/**
	 * Renders the input field for the default design height.
	 *
	 * This field allows administrators to set the default height in pixels for
	 * new product designs created using the personalizer.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function render_default_design_height_field(): void {
		$options = CKPP_Config::get( 'general' );
		$value   = isset( $options['default_design_height'] ) ? absint( $options['default_design_height'] ) : 1000;
		echo '<input type="number" id="ckpp_default_design_height" name="' . esc_attr( CKPP_Config::OPTION_GENERAL ) . '[default_design_height]" value="' . esc_attr( $value ) . '" />';
		echo '<p class="description">' . esc_html__( 'Enter the default height for new designs in pixels.', 'customkings-product-personalizer' ) . '</p>';
	}

	/**
	 * Renders the input field for the design upload directory.
	 *
	 * This field allows administrators to specify a custom directory path where
	 * product design files will be stored. If left empty, the default WordPress
	 * upload directory will be used.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function render_design_upload_dir_field(): void {
		$options = CKPP_Config::get( 'general' );
		$value   = isset( $options['design_upload_dir'] ) ? esc_attr( $options['design_upload_dir'] ) : '';
		echo '<input type="text" id="ckpp_design_upload_dir" name="' . esc_attr( CKPP_Config::OPTION_GENERAL ) . '[design_upload_dir]" value="' . $value . '" class="regular-text" />';
		echo '<p class="description">' . esc_html__( 'Specify the directory where design files will be uploaded. Leave empty for default WordPress upload directory.', 'customkings-product-personalizer' ) . '</p>';
	}

	/**
	 * Renders the checkbox field for enabling/disabling caching.
	 *
	 * This setting controls whether the plugin utilizes caching mechanisms
	 * to improve performance by storing frequently accessed data.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function render_enable_caching_field(): void {
		$options = CKPP_Config::get( 'general' );
		$checked = isset( $options['enable_caching'] ) ? checked( 1, $options['enable_caching'], false ) : '';
		echo '<input type="checkbox" id="ckpp_enable_caching" name="' . esc_attr( CKPP_Config::OPTION_GENERAL ) . '[enable_caching]" value="1"' . $checked . ' />';
		echo '<label for="ckpp_enable_caching">' . esc_html__( 'Check to enable caching for improved performance.', 'customkings-product-personalizer' ) . '</label>';
	}

	/**
	 * Renders the input field for the cache duration.
	 *
	 * This field allows administrators to specify how long (in hours) cached
	 * data should be considered valid before being refreshed.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function render_cache_duration_field(): void {
		$options = CKPP_Config::get( 'general' );
		$value   = isset( $options['cache_duration'] ) ? absint( $options['cache_duration'] ) : 24;
		echo '<input type="number" id="ckpp_cache_duration" name="' . esc_attr( CKPP_Config::OPTION_GENERAL ) . '[cache_duration]" value="' . esc_attr( $value ) . '" />';
		echo '<p class="description">' . esc_html__( 'Enter the duration in hours for which cached data will be considered valid.', 'customkings-product-personalizer' ) . '</p>';
	}

	/**
	 * Renders the checkbox field for enabling/disabling additional security features.
	 *
	 * This setting controls features like file type validation and maximum upload size limits,
	 * enhancing the security of file uploads within the plugin.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function render_enable_security_features_field(): void {
		$options = CKPP_Config::get( 'general' );
		$checked = isset( $options['enable_security_features'] ) ? checked( 1, $options['enable_security_features'], false ) : '';
		echo '<input type="checkbox" id="ckpp_enable_security_features" name="' . esc_attr( CKPP_Config::OPTION_GENERAL ) . '[enable_security_features]" value="1"' . $checked . ' />';
		echo '<label for="ckpp_enable_security_features">' . esc_html__( 'Check to enable additional security features like file type validation and size limits.', 'customkings-product-personalizer' ) . '</label>';
	}

	/**
	 * Renders the input field for allowed file types.
	 *
	 * This field allows administrators to specify a comma-separated list of file extensions
	 * that are permitted for upload through the product personalizer.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function render_allowed_file_types_field(): void {
		$options = CKPP_Config::get( 'general' );
		$value   = isset( $options['allowed_file_types'] ) ? esc_attr( $options['allowed_file_types'] ) : 'png,jpg,jpeg,gif';
		echo '<input type="text" id="ckpp_allowed_file_types" name="' . esc_attr( CKPP_Config::OPTION_GENERAL ) . '[allowed_file_types]" value="' . $value . '" class="regular-text" />';
		echo '<p class="description">' . esc_html__( 'Enter comma-separated file extensions allowed for upload (e.g., png,jpg,jpeg,gif).', 'customkings-product-personalizer' ) . '</p>';
	}

	/**
	 * Renders the input field for the maximum upload size.
	 *
	 * This field allows administrators to set the maximum file size (in megabytes)
	 * permitted for uploads through the product personalizer.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function render_max_upload_size_field(): void {
		$options = CKPP_Config::get( 'general' );
		$value   = isset( $options['max_upload_size'] ) ? absint( $options['max_upload_size'] ) : 5;
		echo '<input type="number" id="ckpp_max_upload_size" name="' . esc_attr( CKPP_Config::OPTION_GENERAL ) . '[max_upload_size]" value="' . esc_attr( $value ) . '" />';
		echo '<p class="description">' . esc_html__( 'Enter the maximum allowed file upload size in megabytes (MB).', 'customkings-product-personalizer' ) . '</p>';
	}

	/**
	 * Renders the checkbox field for enabling/disabling logging.
	 *
	 * This setting controls whether the plugin generates log entries for debugging
	 * and monitoring purposes.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function render_enable_logging_field(): void {
		$options = CKPP_Config::get( 'general' );
		$checked = isset( $options['enable_logging'] ) ? checked( 1, $options['enable_logging'], false ) : '';
		echo '<input type="checkbox" id="ckpp_enable_logging" name="' . esc_attr( CKPP_Config::OPTION_GENERAL ) . '[enable_logging]" value="1"' . $checked . ' />';
		echo '<label for="ckpp_enable_logging">' . esc_html__( 'Check to enable logging for debugging and monitoring.', 'customkings-product-personalizer' ) . '</label>';
	}

	/**
	 * Renders the input field for the log file path.
	 *
	 * This field allows administrators to specify the full path to the log file
	 * where plugin-related logs will be written. It's crucial to ensure the
	 * specified directory is writable by the web server.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function render_log_file_path_field(): void {
		$options = CKPP_Config::get( 'general' );
		$value   = isset( $options['log_file_path'] ) ? esc_attr( $options['log_file_path'] ) : '';
		echo '<p class="description">' . esc_html__( 'Specify the full path to the log file. Ensure the directory is writable.', 'customkings-product-personalizer' ) . '</p>';
	}

	/**
	 * Renders the checkbox field for enabling/disabling debug mode.
	 *
	 * When enabled, debug mode provides more detailed error reporting and diagnostics,
	 * which is useful during development and troubleshooting.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function render_debug_mode_field(): void {
		$options = CKPP_Config::get( 'general' );
		$checked = isset( $options['debug_mode'] ) ? checked( 1, $options['debug_mode'], false ) : '';
		echo '<input type="checkbox" id="ckpp_debug_mode" name="' . esc_attr( CKPP_Config::OPTION_GENERAL ) . '[debug_mode]" value="1"' . $checked . ' />';
		echo '<label for="ckpp_debug_mode">' . esc_html__( 'Check to enable debug mode for detailed error reporting and diagnostics.', 'customkings-product-personalizer' ) . '</label>';
	}

	/**
	 * Renders the input field for specifying database tables to optimize.
	 *
	 * This field allows administrators to enter a comma-separated list of specific
	 * database table names that should be included in the optimization process.
	 * If left empty, the plugin's default tables will be optimized.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function render_optimize_tables_field(): void {
		$options = CKPP_Config::get( 'general' );
		$value   = isset( $options['optimize_tables'] ) ? esc_attr( $options['optimize_tables'] ) : '';
		echo '<input type="text" id="ckpp_optimize_tables" name="' . esc_attr( CKPP_Config::OPTION_GENERAL ) . '[optimize_tables]" value="' . $value . '" class="regular-text" />';
		echo '<p class="description">' . esc_html__( 'Enter comma-separated table names to optimize (e.g., wp_posts,wp_comments). Leave empty to optimize all tables.', 'customkings-product-personalizer' ) . '</p>';
	}

	/**
	 * Renders the select field for setting the database optimization frequency.
	 *
	 * This field allows administrators to choose how often the database optimization
	 * process should run (e.g., daily, weekly, monthly).
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function render_optimization_frequency_field(): void {
		$options   = CKPP_Config::get( 'general' );
		$value     = isset( $options['optimization_frequency'] ) ? esc_attr( $options['optimization_frequency'] ) : 'weekly';
		$frequencies = array(
			'daily'   => __( 'Daily', 'customkings-product-personalizer' ),
			'weekly'  => __( 'Weekly', 'customkings-product-personalizer' ),
			'monthly' => __( 'Monthly', 'customkings-product-personalizer' ),
		);
		echo '<select id="ckpp_optimization_frequency" name="' . esc_attr( CKPP_Config::OPTION_GENERAL ) . '[optimization_frequency]">';
		foreach ( $frequencies as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '"' . selected( $value, $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Select how often database optimization should run.', 'customkings-product-personalizer' ) . '</p>';
	}

	/**
	 * Renders the input field for setting the database optimization time.
	 *
	 * This field allows administrators to specify the exact time of day (HH:MM format)
	 * when the scheduled database optimization should run.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function render_optimization_time_field(): void {
		$options = CKPP_Config::get( 'general' );
		$value   = isset( $options['optimization_time'] ) ? esc_attr( $options['optimization_time'] ) : '02:00';
		echo '<input type="time" id="ckpp_optimization_time" name="' . esc_attr( CKPP_Config::OPTION_GENERAL ) . '[optimization_time]" value="' . $value . '" />';
		echo '<p class="description">' . esc_html__( 'Set the time of day for the optimization to run (e.g., 02:00 for 2 AM).', 'customkings-product-personalizer' ) . '</p>';
	}

	/**
	 * Renders the color picker field for the plugin's accent color.
	 *
	 * This field allows administrators to customize the primary accent color
	 * used throughout the plugin's user interface to match their website's branding.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function render_accent_color_field(): void {
		$options = CKPP_Config::get( 'general' );
		$value   = isset( $options['accent_color'] ) ? esc_attr( $options['accent_color'] ) : '#0073aa';
		echo '<input type="color" id="ckpp_accent_color" name="' . esc_attr( CKPP_Config::OPTION_GENERAL ) . '[accent_color]" value="' . $value . '" />';
		echo '<p class="description">' . esc_html__( 'Choose the accent color for the plugin UI.', 'customkings-product-personalizer' ) . '</p>';
	}

	/**
	 * Renders the main settings form for the CustomKings Product Personalizer plugin.
	 *
	 * This method outputs the HTML `<form>` element, including necessary WordPress
	 * settings fields (`settings_fields`) and sections (`do_settings_sections`),
	 * and the submit button. This form is used to save all general plugin settings.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function render_settings_form(): void {
		?>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'ckpp_settings_group' );
			do_settings_sections( 'ckpp_settings_page' );
			submit_button();
			?>
		</form>
		<?php
	}
}