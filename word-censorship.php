<?php
/**
 * @package Censorship
 * @version 1.0.0
 */
/*
Plugin Name: Censorship
Description: This plugin censors words in comments.
Author: Aleksandr Haiko
Version: 1.0.0
*/


if(!defined('WPINC')) {
	die;
}


function activation(){
	global $wpdb;
	$table_name = $wpdb->prefix . 'bad_words';

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		word varchar(45) NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY unique_word (word)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta($sql);
}

function deactivation(){
    global $wpdb;
	$table_name = $wpdb->prefix . 'bad_words';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}




register_activation_hook( __FILE__, 'activation');
register_deactivation_hook( __FILE__, 'deactivation');

require_once plugin_dir_path(__FILE__) . 'includes/functions.php';


add_action('plugins_loaded', 'word_censorship_load_plugin_textdamain');

function word_censorship_load_plugin_textdamain()
{
	load_plugin_textdomain('word-censorship', false, basename( __DIR__ ));
}


$word_censorship = new word_censorship();
add_filter('pre_comment_content','word_censorship_filter_comment');

function word_censorship_filter_comment($comment_text) {
	global $word_censorship;
	$comment_text = $word_censorship->filter($comment_text);
	return $comment_text;
}

class word_censorship {

	function filter ($comment_text){
		
		if (!empty(get_bad_words())) {
			
			$bad_words = get_bad_words();

			$string = strtolower(str_replace("\n", " {nl} ", $comment_text));
			$string = preg_replace('/[^ a-zа-яё\d]/ui', '', $string);

			$words_from_comment = explode (" ", $string);

			$result = array_intersect(array_map('strtolower',$bad_words), $words_from_comment);
			if(!empty($result)) {
				foreach ($result as $word) {
					$comment_text = str_replace($word, '<***>', $comment_text);
				}			
			}
		}
				
		return $comment_text;
	}
}

