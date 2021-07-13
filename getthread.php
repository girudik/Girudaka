<?php
// On-demand thread rendering
require 'config.php';
require KU_ROOTDIR . 'inc/functions.php';
require KU_ROOTDIR . 'inc/classes/board-post.class.php';
if (KU_NEWCACHE_LOGIC) {
	$board = $_GET['board'];
	$thread = (int)$_GET['thread'];
	if (!isset($board)) {
		http_response_code(404); exit;
	}
	$board_class = new Board($board);
	$content = "";
	if (!isset($board_class->board['name'])) {
		http_response_code(404); exit;
	}

	if (!isset($thread) && $thread <= 0) {
		http_response_code(404); exit;
	}
	if (file_exists(KU_ROOTDIR . $board . '/res/' . $thread . '.html')) {
		do_redirect(KU_BOARDSPATH . '/' . $board . '/res/' . $thread . '.html', true); exit;
	}
	$result = $tc_db->GetOne("SELECT `id` FROM `".KU_DBPREFIX."posts` WHERE `id`=? AND parentid=0 AND `boardid`=(SELECT `id` FROM `".KU_DBPREFIX."boards` WHERE `name`=?)", array($thread, $board));
	if (!$result) {
		http_response_code(404); exit;
	}
	$content = $board_class->RegenerateThreads($thread, $return_output=true);
	if ($content) {
		echo $content;
	} else {
		http_response_code(404); exit;
	}
} else { // simalation old logic
	exit; // no need
	if (!isset($_GET['board']))
		http_response_code(404);
	if (!isset($_GET['thread']))
		http_response_code(404);
	$thread = (int)$_GET['thread'];
	if ($thread <= 0)
		http_response_code(404);
	if (file_exists(KU_ROOTDIR . $_GET['board'] . '/res/' . $thread . '.html'))
		do_redirect(KU_BOARDSPATH . '/' . $_GET['board'] . '/res/' . $thread . '.html', true);
	$board_class = new Board($_GET['board']);
	if (!isset($board_class->board['name']))
		http_response_code(404);
	if ($board_class->RegenerateThreads($thread))
		do_redirect(KU_BOARDSPATH . '/' . $_GET['board'] . '/res/' . $thread . '.html', true);
	else
		http_response_code(404);
}