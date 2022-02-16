<?php

add_action('admin_menu', 'add_link_to_dashboard');

function add_link_to_dashboard()
{
 add_menu_page('Censorship', 'Censorship', 'manage_options', 'word-censorship', 'word_censorship_options', 'dashicons-admin-plugins', 65); 
}


add_action('admin_menu', 'word_censorship_admin_menu');
function word_censorship_admin_menu() {
	add_options_page( 'Censorship', 'Censorship', 'manage_options', 'word-censorship', 'word_censorship_options' );
}


function word_censorship_options() {
?>

	<div>
		<form id="form">
			<h3>Write words in lowercase, separated by commas</h3>
			<h2><b>Enter your words:</h2></p>
			<textarea id='add_bad_words' name='add_bad_words' rows='1' cols='80' type='textarea' placeholder="bad words"></textarea>
			<p></p>
			<input class="submit_bad_words" type="submit" value="Send"/>
		
		</form>
	</div>
	
	<hr>
	<div id="result" style="font-size: 1.4em; color:brown">
		<?php
		foreach(get_bad_words() as $bad_word) {
				echo '<input id="bad_word"name="bad_word" type="button" value="'.$bad_word.'&#10060"/>';
		}
		?>
	</div>

	<div id="save_result"></div>

	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {

			const ajaxurl = "/wp-admin/admin-ajax.php";

			$(".submit_bad_words").on("click", function (event) {
				event.preventDefault();
				$(".submit_bad_words").trigger("reset");

				const bad_words = $('#add_bad_words').val();
				if(bad_words.trim() !== "") {
					submitBadWordsFromAjax(ajaxurl, bad_words);
				} else {
					let message = "<p><h3 style=\"color:red\">The field must be not empty</h3></p>";
					alertPostAction(message);
					$("#add_bad_words").val('');
					hideMessage();
				}
			});


			$("#result #bad_word").on('click',function(event) {
				event.preventDefault();

				let word = $(this).val();
				let length_word = word.length - 1;
				deleted_word = word.substring(0, length_word);

				deleteBadWordFromAjax(ajaxurl, deleted_word);
				$(this).remove();
			});

			function addToList(response) {
				let data = $.parseJSON(response);
					for(let i = 0; i < data.length; ++i){
						$("#result").append('<input type="button" onclick="send('+ data[i] +');" value="'+ data[i] +'&#10060"/>');
					}
			}

			function alertPostAction(message) {
				$('#save_result').html("<div id='save_message'></div>");
				$('#save_message').append(message).show();
			}

			function hideMessage() {
				setTimeout("$('#save_message').hide('slow');", 3000);
			}

			function submitBadWordsFromAjax(url, value){
				$.ajax({
					method: "POST",
					url: ajaxurl,
					data: {
						action: 'submit_bad_words_action',
						bad_words: value.trim()
					},
					success: function(response){
						let message = "<p><h3 style=\"color:green\">Settings saved successfully</h3></p>";
						addToList(response);
						alertPostAction(message);
						$("#add_bad_words").val('');
					},
						timeout: 5000,
				});
				hideMessage();
			}

			function deleteBadWordFromAjax(url, value){
				$.ajax({
					method: "POST",
					url: ajaxurl,
					data: {
						action: 'delete_bad_word_action',
						deleted_word: value
					},
					success: function(response){
						let message = "<p><h3 style=\"color:blue\">" + response + "</h3></p>";
						alertPostAction(message);
					},
					timeout: 5000,
				});
				hideMessage();
			}
		});
	</script>

	<?php
}

add_action( 'wp_ajax_submit_bad_words_action', 'add_bad_words' );
add_action( 'wp_ajax_delete_bad_word_action', 'delete_bad_word' );

function add_bad_words() {

	if (!$_POST) exit('No direct script access allowed');

	if(!empty($_POST) && $_POST['bad_words'] !== '') {

        $bad_words = explode(',', htmlspecialchars($_POST['bad_words']));

		global $wpdb;

		$table_name = $wpdb->prefix . 'bad_words';

		foreach($bad_words as $word) {

			$wpdb->insert($table_name, ['word' => trim($word)]);
		}   

		echo json_encode($bad_words);	

		exit();
	}
}

function delete_bad_word() {

	if(!empty($_POST['deleted_word'])) {

        $deleted_word = $_POST['deleted_word'];

		global $wpdb;

		$table_name = $wpdb->prefix . 'bad_words';

		$result = $wpdb->delete($table_name, ['word' => $deleted_word]);

		if($result == true) {
			echo "\"".$_POST['deleted_word']."\" was deleted successfully";
		} else {
			echo "\"".$_POST['deleted_word'] . "\" failed to delete";
		}

	exit();
	}
}

function get_bad_words() {

    global $wpdb;

    $table_name = $wpdb->prefix . 'bad_words';

	$results = $wpdb->get_results("SELECT word FROM $table_name");
	
	$bad_words = [];
			$i = 0;

	foreach($results as $word) {
		foreach($word as $name) {
			$bad_words[$i] = $name;
		}
		$i++;
	}

    return $bad_words;
}