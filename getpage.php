<?php
// On-demand page rendering
require 'config.php';
require KU_ROOTDIR . 'inc/functions.php';
require KU_ROOTDIR . 'inc/classes/board-post.class.php';

if (KU_NEWCACHE_LOGIC) {
	$board = $_GET['board'];
	
	$page = (int)$_GET['page'];
	$catalog = (int)$_GET['catalog'];
	$format = $_GET['format'];
	if (!isset($board)) {
		http_response_code(404); exit;
	}
	$board_class = new Board($board);
	$content = "";
	if ((!isset($board_class->board['name']) && $board != I0_OVERBOARD_DIR)) {
		http_response_code(404); exit;
	}

	if (isset($catalog) && $catalog) {
		if (!isset($format) || ($format != "html" && $format != "json")) {
			http_response_code(404); exit;
		}
		if ($board_class->board['enablecatalog'] == 1) {
			switch ($format) {
				case "html":
					if (file_exists(KU_ROOTDIR . $board . '/catalog.html')) {
						do_redirect(KU_BOARDSPATH . '/' . $board . '/catalog.html', true); exit;
					}
					$content = $board_class->RegeneratePages($from=$page, $to=$page, $singles=array(), $on_demand=false, $return_output=true, $return_catalog=true, $return_catalog_format="html");
					break;
				case "json":
					if (file_exists(KU_ROOTDIR . $board . '/catalog.json')) {
						do_redirect(KU_BOARDSPATH . '/' . $board . '/catalog.json', true); exit;
					}
					header('Content-type: application/json; charset=utf-8');
					$content = $board_class->RegeneratePages($from=$page, $to=$page, $singles=array(), $on_demand=false, $return_output=true, $return_catalog=true, $return_catalog_format="json");
					break;
			}
		}
	} else {
		if (!isset($page) && $page < 0) {
			http_response_code(404); exit;
		}
		if ($page == 0) {
			if (file_exists(KU_ROOTDIR . $board . '/index.html')) {
				do_redirect(KU_BOARDSPATH . '/' . $board . '/', true); exit;
			}
		} else {
			if (file_exists(KU_ROOTDIR . $board . '/' . $page . '.html')) {
				do_redirect(KU_BOARDSPATH . '/' . $board . '/' . $page . '.html', true); exit;
			}
		}
		if (I0_OVERBOARD_ENABLED && $board == I0_OVERBOARD_DIR) {
			$content = RegenerateOverboard($boardlist=$board_class->board['boardlist'], $return_page=$page, $return_output=true);
		} else {
			$content = $board_class->RegeneratePages($from=$page, $to=$page, $singles=array(), $on_demand=true, $return_output=true);
		}
	}
	if ($content) {
		echo $content;
	} else {
		http_response_code(404); exit;
	}
} else { // old logic
	if (!isset($_GET['board']))
		http_response_code(404);
	if (!isset($_GET['page']))
		http_response_code(404);
	$page = (int)$_GET['page'];
	if ($page <= 0)
		http_response_code(404);
	if (file_exists(KU_ROOTDIR . $_GET['board'] . '/' . $page . '.html'))
		do_redirect(KU_BOARDSPATH . '/' . $_GET['board'] . '/' . $page . '.html', true);
	$board_class = new Board($_GET['board']);
	if (!isset($board_class->board['name']))
		http_response_code(404);
	if ($board_class->RegeneratePages($page, $page, array(), true))
		do_redirect(KU_BOARDSPATH . '/' . $_GET['board'] . '/' . $page . '.html', true);
	else
		http_response_code(404);
}