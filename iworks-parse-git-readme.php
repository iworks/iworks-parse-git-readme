<?php
/*
Plugin Name: Parse GIT readme
Plugin URI:
Description:
Version: 1.0.0
Author: Marcin Pietrzak
Author URI: http://iworks.pl/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

class iWorks_Parse_GIT_Readme {

	private $period_name = 'iworks_pgr_timestamp';
	private $prefix      = 'iworks_pgr_';
	private $version     = '1.0.0';
	private $debug       = false;

	public function __construct() {
		$this->debug = defined( 'WP_DEBUG' ) && WP_DEBUG;
		/**
		 * WordPress hooks
		 */
		add_shortcode( 'iworks_parse_git_readme', array( $this, 'shortcode_iworks_parse_git_readme' ) );
		add_filter( 'body_class', array( $this, 'filter_add_body_class' ), 10, 2 );
	}

	private function parse_readme_md( $content ) {
		include_once dirname( __FILE__ ) . '/vendor/parsedown.php';
		$parser  = new Emanuil_Rusev_Parsedown();
		$content = wpautop( $parser->text( $content ) );
		return preg_replace( '@<h1>.+</h1>@', '', $content );
	}

	private function get_file( $file, $expiration ) {
		$key     = $this->prefix . crc32( $file );
		$content = get_transient( $key );
		if ( ! empty( $content ) ) {
			return $content;
		}
		$response = wp_remote_get( $file );
		if ( is_wp_error( $response ) ) {
			if ( current_user_can( 'administrator' ) ) {
				return sprintf(
					'<div class="notice notice-error"><p>%s</p></div>',
					$response->get_error_message()
				);
			}
			return __return_empty_string();
		}
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return __return_empty_string();
		}
		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return __return_empty_string();
		}
		return $this->parse_readme_md( $body );
	}

	public function shortcode_iworks_parse_git_readme( $atts ) {
		$atts = shortcode_atts(
			array(
				'file'       => null,
				'expiration' => HOUR_IN_SECONDS,
			),
			$atts,
			'iworks_parse_git_readme'
		);
		if ( empty( $atts['file'] ) ) {
			return __return_empty_string();
		}
		$content = $this->get_file( $atts['file'], $atts['expiration'] );
		return $content;
	}

	public function filter_add_body_class( $classes, $class ) {
		if ( ! is_singular() ) {
			return $classes;
		}
		global $post;
		if ( preg_match( '/iworks_parse_git_readme/', $post->post_content ) ) {
			$classes[] = 'git-readme';
		}
		return $classes;

	}
}

new iWorks_Parse_GIT_Readme;

