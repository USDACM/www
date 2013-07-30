<?php
if ((isset($_GET['wpmudev-chat-key'])) && (!empty($_GET['wpmudev-chat-key']))) {

	$wpmudev_chat_key = base64_decode($_GET['wpmudev-chat-key']);
	$chat_session = get_transient($wpmudev_chat_key);
	//echo "chat_session<pre>"; print_r($chat_session); echo "</pre>";
	if ((!empty($chat_session)) && (is_array($chat_session))) {
		global $wpmudev_chat; ?><html>
<head>
	<title><?php
		if (!empty($chat_session['box_title'])) {
			echo sanitize_text_field($chat_session['box_title']) ." &ndash; ";
		} ?></title>
		<?php $wpmudev_chat->wp_enqueue_scripts(); ?>
		<style type="text/css">
			body.wpmudev-chat-pop-out {
				margin: auto;
				padding: 0;
			}
			body.wpmudev-chat-pop-out div#wpmudev-chat-box-<?php echo $chat_session['id'] ?> {
				width: 99%;
				height: 99%;
			  	position:fixed !important;
			  	position:absolute;
			  	top:0;
			  	right:0;
			  	bottom:0;
			  	left:0;
				font-size: 100%;
			}

			body.wpmudev-chat-pop-out div#wpmudev-chat-box-<?php echo $chat_session['id'] ?> div.wpmudev-chat-module-messages-list div.wpmudev-chat-row p.wpmudev-chat-message {
				font-size: 100%;
			}

			body.wpmudev-chat-pop-out div#wpmudev-chat-box-<?php echo $chat_session['id'] ?>  div.wpmudev-chat-module-message-area textarea.wpmudev-chat-send {
			    height: 20%;
			}

			div.wpmudev-chat-box div.wpmudev-chat-module-messages-list div.wpmudev-chat-row ul.wpmudev-chat-row-footer {
			    font-size: 90%;
			}
		</style>
</head>
<body class="wpmudev-chat-pop-out">
<?php echo $wpmudev_chat->process_chat_shortcode($chat_session); ?>
<?php
	$wpmudev_chat->wp_footer();
?>
</body>
</html><?php
}
}