<?php
	/**
	 * Plugin Name: Restrict Page By Role
	 * Plugin URI:
	 * Description: This WordPress plugin allows you to restrict access to the content of a page or post to which only certain group of users can access.
	 * Author: Leo Baiano
	 * Author URI: http://leobaiano.com.br
	 * Version: 1.0.0
	 * License: GPLv2 or later
	 * Text Domain: lb-rpbr
 	 * Domain Path: /languages/
	 */

	if ( ! defined( 'ABSPATH' ) )
		exit; // Exit if accessed directly.

	/**
	 * Restrict_Page_By_Role
	 *
	 * @author   Leo Baiano <ljunior2005@gmail.com>
	 */
	class Restrict_Page_By_Role {
		/**
		 * Instance of this class.
		 *
		 * @var object
		 *
		 * @since 1.0.0
		 */
		protected static $instance = null;

		/**
		 * Slug.
		 *
		 * @var string
		 *
		 * @since 1.0.0
		 */
		protected static $text_domain = 'lb-rpbr';

		/**
		 * Initialize the plugin
		 *
		 * @since 1.0.0
		 */
		private function __construct() {
			// Load plugin text domain
			add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

			// Load styles and script
			add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_styles_and_scripts' ) );

			// Load Helpers
			add_action( 'init', array( $this, 'load_helper' ) );

			// Add field in meta box submitdiv
			if ( is_admin() ) {
				add_action( 'post_submitbox_misc_actions', array( $this, 'post_submitbox_misc_actions' ) );
			}

			// Save post
			add_action( 'save_post', array( $this, 'register_restrict_role' ) );

			// Change content for login form if page is restrict
			add_filter( 'the_content', array( $this, 'restrict_content_page' ) );

			// Change the title for content restrict
			add_filter( 'the_title', array( $this, 'restrict_title_page' ) );
		}

		/**
		 * Return an instance of this class.
		 *
		 *
		 * @since 1.0.0
		 *
		 * @return object A single instance of this class.
		 */
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		/**
		 * Load the plugin text domain for translation.
		 *
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( self::$text_domain, false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		/**
		 * Load styles and scripts
		 *
		 * @since 1.0.0
		 *
		 */
		public function load_admin_styles_and_scripts(){
			wp_enqueue_style( self::$text_domain . '_css_main', plugins_url( '/assets/css/main.css', __FILE__ ), array(), null, 'all' );
			$params = array(
						'ajax_url'	=> admin_url( 'admin-ajax.php' )
					);
			wp_enqueue_script( self::$text_domain . '_js_main', plugins_url( '/assets/js/main.js', __FILE__ ), array( 'jquery' ), null, true );
			wp_localize_script( self::$text_domain . '_js_main', 'data_baianada', $params );
		}

		/**
		 * Load auxiliary and third classes are in the class directory
		 *
		 * @since 1.0.0
		 */
		public function load_helper() {
			$class_dir = plugin_dir_path( __FILE__ ) . "/helper/";
			foreach ( glob( $class_dir . "*.php" ) as $filename ){
				include $filename;
			}
		}

		/**
		 * Add custom field in meta box submitdiv.
		 *
		 * @since 1.0.0
		 */
		public function post_submitbox_misc_actions() {
			$post = get_post();

			echo '<div class="misc-pub-section public-post-preview">';
				$this->get_select_html( $post );
			echo '</div>';

		}

		/**
		 * Print the select with roles for define restrict page.
		 *
		 * @since 1.0.0
		 *
		 * @param WP_Post $post The post object.
		 */
		private function get_select_html( $post ) {

			// Check if empty $post and define $post
			if ( empty( $post ) ) {
				$post = get_post();
			}

			$restrict_access = get_post_meta( $post->ID, self::$text_domain . '_restrict_access', true );
			$select_role = get_post_meta( $post->ID, self::$text_domain . '_select_role', true );

			// Field nonce for submit control
			wp_nonce_field( self::$text_domain . '_select_role', self::$text_domain . '_select_role_wpnonce' );

			// Create html with select for restrict access by role
			echo '<p><strong>' . __( 'Restrict access by role?', 'lb-rpbr' ) . '</strong></p>';
			echo '<input type="checkbox" name="' . self::$text_domain . '_restrict_access" value="1" class="' . self::$text_domain . '_restrict_access"' . checked( 1, $restrict_access, false ) . '> Sim?';
			echo '<div class="' . self::$text_domain . '_box-select-role">';
				echo '<p><strong>' . __( 'Select a role', 'lb-rpbr' ) . '</strong></p>';
				echo '<label class="screen-reader-text" for="' . self::$text_domain . '_select_role">' . __( 'Select role', 'lb-rpbr' ) . '</label>';
				echo '<select name="' . self::$text_domain . '_select_role" id="' . self::$text_domain . '_select_role" class="' . self::$text_domain . '_select_role">';
					wp_dropdown_roles( $select_role );
				echo '</select>';
			echo '</div>';
		}

		/**
		 * Save select role for restrict access for page.
		 *
		 *
		 * @since 1.0.0
		 *
		 * @param int $post_id The post id.
		 * @param object $post The post object.
		 * @return bool false or true
		 */
		public function register_restrict_role( $post_id ) {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				echo $post_id;
				return false;
			}

			if ( empty( $_POST[self::$text_domain . '_select_role_wpnonce'] ) || ! wp_verify_nonce( $_POST[self::$text_domain . '_select_role_wpnonce'], self::$text_domain . '_select_role' ) ) {
				return false;
			}

			if ( isset ( $_POST[self::$text_domain . '_restrict_access'] ) ) {
				$restrict_access = esc_attr( $_POST[self::$text_domain . '_restrict_access'] );
				update_post_meta( $post_id, self::$text_domain . '_restrict_access', $restrict_access );
			} else {
				update_post_meta( $post_id, self::$text_domain . '_restrict_access', 0 );
			}

			$select_role = esc_attr( $_POST[self::$text_domain . '_select_role'] );
			update_post_meta( $post_id, self::$text_domain . '_select_role', $select_role );

			return true;
		}

		/**
		 * Restrict content page
		 *
		 * @since 1.0.0
		 *
		 * @param string $content The content page
		 * @return string $content
		 */
		public function restrict_content_page( $content ) {
			$restrict_access = get_post_meta( get_the_ID(), self::$text_domain . '_restrict_access', true );
			$select_role = get_post_meta( get_the_ID(), self::$text_domain . '_select_role', true );

			if ( $restrict_access && $select_role ) {
				if ( current_user_can( $select_role ) || current_user_can( 'administrator') || current_user_can( 'super-admin') ) {
					return $content;
				} else {
					if ( is_user_logged_in() ) {
						echo '<p>' . __( 'You are logged but not part of the group that has access to this content, sorry. You can access other site content, access the menu and continue browsing.', 'lb-rpbr' ) . '</p>';
					} else {
						$args = array(
							'echo'           => true,
							'remember'       => true,
							'redirect'       => ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
							'form_id'        => 'loginform',
							'id_username'    => 'user_login',
							'id_password'    => 'user_pass',
							'id_remember'    => 'rememberme',
							'id_submit'      => 'wp-submit',
							'label_username' => __( 'Username', 'lb-rpbr' ),
							'label_password' => __( 'Password', 'lb-rpbr' ),
							'label_remember' => __( 'Remember Me', 'lb-rpbr' ),
							'label_log_in'   => __( 'Log In', 'lb-rpbr' ),
							'value_username' => '',
							'value_remember' => false
						);
						wp_login_form( $args );
					}
					return;
				}
			}
			return $content;
		}

		/**
		 * Restrict content page
		 *
		 * @since 1.0.0
		 *
		 * @param string $content The content page
		 * @return string $content
		 */
		public function restrict_title_page( $title ) {
			$restrict_access = get_post_meta( get_the_ID(), self::$text_domain . '_restrict_access', true );
			$select_role = get_post_meta( get_the_ID(), self::$text_domain . '_select_role', true );

			if ( $restrict_access && $select_role ) {
				if ( current_user_can( $select_role ) || current_user_can( 'administrator') || current_user_can( 'super-admin') ) {
					return $title;
				} else {
					return '<h2>' . __( 'Restrict Content', 'lb-rpbr' ) . '</h2>';
				}
			}
			return $title;
		}

	} // end class Baianada();
	add_action( 'plugins_loaded', array( 'Restrict_Page_By_Role', 'get_instance' ), 0 );
