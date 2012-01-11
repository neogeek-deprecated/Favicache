<?php

/*

 Plugin Name: Favicache
 Plugin URI: http://neo-geek.net/favicache/
 Description: Retrieves and stores favicons in a local cache. This makes for faster page load times and lessens the strain on the server in which the favicon was originally retrieved from.
 Version: 2.0
 Compatibility: 3.0, 3.1, 3.1.3, 3.2, 3.2.1, 3.3, 3.3.1
 Author: Neo Geek
 Author URI: http://neo-geek.net/
 
 Copyright (c) 2011 Neo Geek
 Dual-licensed under both MIT and BSD licenses.
 
 Permission is hereby granted, free of charge, to any person obtaining a copy
 of this software and associated documentation files (the "Software"), to deal
 in the Software without restriction, including without limitation the rights
 to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 copies of the Software, and to permit persons to whom the Software is
 furnished to do so, subject to the following conditions:
 
 The above copyright notice and this permission notice shall be included in
 all copies or substantial portions of the Software.
 
 THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 THE SOFTWARE.

*/

function fetch_favicon($url) {
	
	$ch = curl_init($url);
	
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	$output = curl_exec($ch);
	
	if (!curl_errno($ch) && $info = curl_getinfo($ch)) {
		
		curl_close($ch);
		
		if ($info['http_code'] == 200) { return $output; }
		
	}
	
	return false;
	
}

function fetch_page_info($url) {
	
	$ch = curl_init($url);
	
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	$output = curl_exec($ch);
	
	curl_close($ch);
	
	preg_match_all('/<link (.+)\/?>/Ui', $output, $links);
	
	foreach ($links[1] as $link) {
		if (preg_match('/rel=(?:"|\').*icon.*(?:"|\')/', $link) && preg_match('/href=(?:"|\')(.+)(?:"|\')/Ui', $link, $matches)) { return $matches[1]; }
	}
	
}

function favicache($url = '') {
	
	global $wpdb;
	
	$url = parse_url($_POST['link_url']);
	
	if (!is_dir(constant('ABSPATH') . 'wp-content/plugins/favicache/')) {
		mkdir(constant('ABSPATH') . 'wp-content/plugins/favicache/');
	}
	
	if ($favicon = fetch_favicon('http://' . $url['host'] . '/favicon.ico')) {
		
		$filename = 'wp-content/plugins/favicache/' . md5($url['host']) . '.ico';
		file_put_contents(constant('ABSPATH') . $filename, $favicon);
		
	} else if ($favicon = fetch_favicon('http://www.' . $url['host'] . '/favicon.ico')) {

		$filename = 'wp-content/plugins/favicache/' . md5($url['host']) . '.ico';
		file_put_contents(constant('ABSPATH') . $filename, $favicon);
		
	} else if ($favicon = fetch_favicon(fetch_page_info('http://' . $url['host']))) {
		
		$filename = 'wp-content/plugins/favicache/' . md5($url['host']) . '.ico';
		file_put_contents(constant('ABSPATH') . $filename, $favicon);
		
	} else { $filename = 'wp-content/plugins/favicache/default.ico'; }
	
	if (isset($_POST['link_id'])) {
		$wpdb->query('UPDATE `' . $wpdb->links . '` SET `link_image` = "' . wp_guess_url() . '/' . $filename . '" WHERE `link_id` = ' . (int)$_POST['link_id']);
	} else {
		$wpdb->query('UPDATE `' . $wpdb->links . '` SET `link_image` = "' . wp_guess_url() . '/' . $filename . '" ORDER BY `link_id` DESC LIMIT 1');
	}
	
}

if (function_exists('add_action') && isset($_POST['link_url'])) {
	add_action('add_link', 'favicache', $_POST['link_url']);
	add_action('edit_link', 'favicache', $_POST['link_url']);
}

?>