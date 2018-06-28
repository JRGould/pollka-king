<?php
/*
Plugin Name: Pollka King
Description: Live-updating polls for your WordPress website
Version: 0.1
Author: Jeffrey Gould
Author URI: https://jrgould.com
*/


if( ! class_exists( 'PollkaKing' ) ) {
	class PollkaKing {
		
		private $shortcode_name = 'pollka';
	
		public function __construct() {
			add_shortcode( $this->shortcode_name, [$this, 'shortcode'] );
			add_action( 'wp_enqueue_scripts', [$this, 'scripts'] );
			add_action( 'wp_ajax_nopriv_pk_submit_poll', [$this, 'submit_poll'] );
			add_action( 'wp_ajax_nopriv_pk_get_poll_data', [$this, 'get_poll_data'] );
		}

		public function shortcode( $atts ) {
			$answers= [];
			foreach ( $atts as $key => $val ) {
				if( strstr( $key, 'answer-' ) ) {
					$answers[ str_replace( 'answer-', '', $key ) ] = $val;
				}
			} 
			$vue_atts = esc_attr( json_encode( [
				'id'       => sanitize_title_with_dashes( $atts['id'], '', 'save' ), 
				'question' => $atts['question'],
				'answers'  => $answers,
			] ) );

			return "<div data-pk-atts='{$vue_atts}'>loading poll...</div>";
		}

		// Only enqueue scripts if we're displaying a post that contains the shortcode
		public function scripts() {
			global $post;
			if( has_shortcode( $post->post_content, $this->shortcode_name ) ) {
				wp_enqueue_script( 'vue', 'https://cdnjs.cloudflare.com/ajax/libs/vue/2.5.16/vue.js', [], '2.5.16' );
				wp_enqueue_script( 'pollka-king', plugin_dir_url( __FILE__ ) . 'js/pollka-king.js', ['vue'], '0.1', true );
				wp_add_inline_script( 'pollka-king', 'window.ajaxurl = "' . admin_url( 'admin-ajax.php' ) . '"');
				wp_enqueue_style( 'pollka-king', plugin_dir_url( __FILE__ ) . 'css/pollka-king.css', [], '0.1' );
			}
		}

		public function submit_poll(){
			$id = sanitize_title_with_dashes( $_GET['id'], '', 'save' );
			$answer = sanitize_text_field( $_GET['answer'] );
			$option_name = 'pollka-poll_' . $id;
			$option_value = get_option( $option_name, [] );
			$answer_count = isset( $option_value[ $answer ] ) ? $option_value[ $answer ] : 0;
			$option_value[ $answer ] = $answer_count + 1; 
			update_option( $option_name, $option_value );
			exit( 'success' );
		}

		public function get_poll_data() {
			$id = sanitize_title_with_dashes( $_GET['id'], '', 'save' );
			$option_name = 'pollka-poll_' . $id;
			$option_value = get_option( $option_name, [] );
			exit( json_encode( $option_value ) );
		}

	}
	new PollkaKing();
}
