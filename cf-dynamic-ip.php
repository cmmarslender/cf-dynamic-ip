<?php

if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	die( "Please run `composer install` first\n" );
}

require_once __DIR__  . '/vendor/autoload.php';

/* Supported Config Variables */
$zone = isset( $_ENV['ZONE'] ) ? $_ENV['ZONE'] : false;
$record = isset( $_ENV['RECORD'] ) ? $_ENV['RECORD'] : false;
$email = isset( $_ENV['CF_EMAIL'] ) ? $_ENV['CF_EMAIL'] : false;
$api_key = isset( $_ENV['CF_API_KEY'] ) ? $_ENV['CF_API_KEY'] : false;
$ip_service = isset( $_ENV['IP_SERVICE'] ) ? $_ENV['IP_SERVICE'] : false;
$interval = isset( $_ENV['INTERVAL'] ) ? $_ENV['INTERVAL'] : 300;

if ( ! $zone || ! $record || ! $email || ! $api_key || ! $ip_service ) {
	die( "You must set `ZONE`, `RECORD`, `CF_EMAIL`, `CF_API_KEY`, and `IP_SERVICE` environment variables to use this \n" );
}

do {
	echo "Checking if IP is current \n";

	// Check for cache
	$cache_json = false;
	$cache_file = '.cf-dns-cache.json';
	if ( file_exists( $cache_file ) ) {
		$cache_json = json_decode( file_get_contents( $cache_file ), true );
	}

	$current_ip = trim( file_get_contents( $ip_service ) );

	echo " - Current IP: {$current_ip} \n";

	if ( ! $cache_json || $cache_json['ip'] != $current_ip ) {
		// Update record in CF
		$cf_api_base = 'https://api.cloudflare.com/client/v4/';
		$base_headers = array(
			'X-Auth-Email' => $email,
			'X-Auth-Key' => $api_key,
			'Content-Type' => 'application/json',
		);

		// First, we have to get the proper zone
		$zones_req = Requests::get($cf_api_base . 'zones?name=' . $zone, $base_headers );
		$zone_req = json_decode( $zones_req->body, true );
		$zone_id = $zone_req['result'][0]['id'];

		echo " - CF Zone ID: {$zone_id} \n";

		// Get DNS Record ID
		// zones/:zone_identifier/dns_records
		$records_req = Requests::get( $cf_api_base . "zones/{$zone_id}/dns_records?type=A&name={$record}", $base_headers );
		$record_req = json_decode( $records_req->body, true );
		$record_id = $record_req['result'][0]['id'];

		echo " - CF Record ID: {$record_id} \n";

		// Now, update the record with the proper IP
		// PUT zones/:zone_identifier/dns_records/:identifier
		$update = Requests::put( $cf_api_base . "zones/{$zone_id}/dns_records/{$record_id}", $base_headers, json_encode( [
			'type' => 'A',
			'name' => $record,
			'content' => $current_ip,
		]) );

		if ( $update->status_code == 200 ) {
			echo " - SUCCESSFULLY UPDATED RECORD IN CF \n";

			// Update cache file
			$cache_info = array(
				'ip' => $current_ip,
				'timestamp' => time(),
			);

			file_put_contents( $cache_file, json_encode( $cache_info ) );

			echo " - Wrote cache file \n";

		} else {
			echo " - ERROR: Update request failed \n";
		}


	} else {
		echo " - Cached IP matches current IP. Skipping... \n";
	}

	echo " - DONE \n\n";
	echo "Sleeping for {$interval} seconds... \n\n";

	sleep( $interval );

} while( true );
