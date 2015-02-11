<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Social Count Plus Facebook Counter.
 *
 * @package  Social_Count_Plus/Facebook_Counter
 * @category Counter
 * @author   Claudio Sanches
 */
class Social_Count_Plus_Facebook_Counter extends Social_Count_Plus_Counter {

	/**
	 * Counter ID.
	 *
	 * @var string
	 */
	public $id = 'facebook';

	/**
	 * API URL.
	 *
	 * @var string
	 */
	protected $api_url = 'https://graph.facebook.com/';
	protected $api_url_token = 'https://graph.facebook.com/v2.2/';
	protected $oauth = 'oauth/access_token';
	protected $tail_oauth = '&grant_type=client_credentials';
	protected $api_url_token_tail = '&format=json&method=get&pretty=0&suppress_http_code=1';

	/**
	 * Test the counter is available.
	 *
	 * @param  array $settings Plugin settings.
	 *
	 * @return bool
	 */
	public function is_available( $settings ) {
		return ( isset( $settings['facebook_active'] ) && isset( $settings['facebook_id'] ) && ! empty( $settings['facebook_id'] ) );
	}

	/**
	 * Get the total.
	 *
	 * @param  array $settings Plugin settings.
	 * @param  array $cache    Counter cache.
	 *
	 * @return int
	 */
	public function get_total( $settings, $cache ) {
		if ( $this->is_available( $settings ) ) {
			$params = array(
				'sslverify' => false,
				'timeout'   => 60
			);

			if (!empty($settings['facebook_app_id'])) {
				// Fetch fan count using the ACCESS TOKEN
				$token_request = $this->api_url_token.$this->oauth.'?client_id='.$settings['facebook_app_id'].'&client_secret='.$settings['facebook_app_secret'].$this->tail_oauth;
				$token_response = file_get_contents($token_request);

				if (!empty($token_response)) {
					$token_params = null;
	    			parse_str($token_response, $token_params);

	    			$requestURL = $this->api_url_token.$settings['facebook_id'].'?access_token='.$token_params['access_token'].$this->api_url_token_tail;
	    			$this->connection = wp_remote_get( $requestURL, $params );
				} else {
					$this->total = ( isset( $cache[ $this->id ] ) ) ? $cache[ $this->id ] : 0;
				}
			} else {
				// Fetch fan count old fashion way with simple URI request (only page id as parameter)
				$this->connection = wp_remote_get( $this->api_url . $settings['facebook_id'], $params );
			}

			if ( is_wp_error( $this->connection ) ) {
				$this->total = ( isset( $cache[ $this->id ] ) ) ? $cache[ $this->id ] : 0;
			} else {
				$_data = json_decode( $this->connection['body'], true );

				if ( isset( $_data['likes'] ) ) {
					$count = intval( $_data['likes'] );

					$this->total = $count;
				} else {
					$this->total = ( isset( $cache[ $this->id ] ) ) ? $cache[ $this->id ] : 0;
				}
			}
		}

		return $this->total;
	}
}
