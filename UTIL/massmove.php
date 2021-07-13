<?php
echo "init, get config<br/>";
require 'kint.phar';
require '../config.php';
require '../inc/func/posts.php';
require '../inc/func/fetching.php';
$tc_db->debug = true;
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


die;
//$this->AdministratorsOnly();

//$tpl_page .= '<h2>'. _gettext('Move thread') . '</h2><br />';

$board_from = "b";
$board_to = "news";

echo "move threads from '" . $board_from . "' to '" . $board_to . "'<br/>";


echo "get boards<br/>";
$boardsRAW = $tc_db->getAll("SELECT id, name from `kusaba2_boards`;");
$boards = [];
foreach($boardsRAW as $boardRAW) {
	$boards[$boardRAW["name"]] = $boardRAW["id"];
}

echo "get target thread ids<br/>";
$threads = $tc_db->getAll("SELECT id from `" . KU_DBPREFIX . "posts` WHERE boardid = ". $boards[$board_from] ." AND parentid = 0 AND IS_DELETED = 1 and timestamp > 1613261443 ORDER BY id asc;");
//$threads = $tc_db->getAll('SELECT id from `' . KU_DBPREFIX . 'posts` WHERE boardid = '. $boards[$board_from] .' AND parentid = 0 AND IS_DELETED = 1  AND NAME = "" AND tripcode = "" AND id NOT IN (120,5572) ORDER BY id asc;');

foreach($threads as $thread) {
	echo "move " . $thread["id"] . "<br />";
	$thread_from = $thread["id"];
	movethread($board_from, $board_to, $thread_from);
}
echo "done!";


function movethread($board_from, $board_to, $thread_from) {
	global $tc_db, $tpl_page;

	// Get the IDs for the from and to boards
	$board_from_id = $tc_db->GetOne("SELECT HIGH_PRIORITY `id` FROM `" . KU_DBPREFIX . "boards` WHERE `name` = " . $tc_db->qstr($board_from));
	$board_to_id = $tc_db->GetOne("SELECT HIGH_PRIORITY `id` FROM `" . KU_DBPREFIX . "boards` WHERE `name` = " . $tc_db->qstr($board_to));

	$boards_to_regenerate = array($board_from, $board_to);

	// Delete old HTMLs
	$from_html = KU_BOARDSDIR . $board_from . '/res/'. $thread_from . '.html';
	$from_html_50 = KU_BOARDSDIR . $board_from . '/res/'. $thread_from . '+50.html';
	$from_html_100 = KU_BOARDSDIR . $board_from . '/res/'. $thread_from . '-100.html';
	//@unlink($from_html);
	//@unlink($from_html_50);
	//@unlink($from_html_100);

	$tc_db->SetFetchMode(ADODB_FETCH_ASSOC);
	$tc_db->Execute("START TRANSACTION");
	$postembeds = $tc_db->GetAll("SELECT `id`, `file_id`, `file`, `file_type`, `file_size_formatted`
		FROM `" . KU_DBPREFIX . "postembeds`
		WHERE
			`boardid`=$board_from_id
			AND
			(`id`=$thread_from OR `parentid`=$thread_from)
		ORDER BY `parentid` ASC, `id` ASC");

	$posts = group_embeds($postembeds);
	$id_map = array();
	foreach($posts as $post) {
		$id = $post['id'];
		$new_id = $tc_db->GetOne("SELECT COALESCE(MAX(id),0) + 1 FROM `" . KU_DBPREFIX . "posts` WHERE `boardid` = '$board_to_id'");
		if ($id == $thread_from) {
			$thread_to = $new_id;
		}
		$tc_db->Execute("UPDATE `" . KU_DBPREFIX . "posts`
			SET
				`id` = $new_id,
				`boardid` = $board_to_id".
				($id == $thread_from ? ' ' : ", `parentid` = $thread_to ").
			"WHERE
				`boardid` = $board_from_id
				AND
				`id` = $id");
		foreach($post['embeds'] as $embed) {
			if ($embed['file'] != 'removed') {
				$files = GetFileAndThumbs($embed);
				// move file physically
				foreach($files as $f) {
					@rename(KU_BOARDSDIR.$board_from.$f, KU_BOARDSDIR.$board_to.$f);
					@unlink(KU_BOARDSDIR.$board_from.$f);
				}
			}
			$tc_db->Execute("UPDATE `" . KU_DBPREFIX . "files`
				SET
					`post_id` = $new_id,
					`boardid` = $board_to_id
				WHERE
					`file_id` = ".$embed['file_id']);
		}
		$id_map []= array('before'=>$id, 'after'=>$new_id);
	}

	// Replace ref links across the whole site
	$all_refs = $tc_db->GetAll("SELECT `id`, `boardid`, `message` FROM `" . KU_DBPREFIX . "posts` WHERE  `message` LIKE '%\"ref|$board_from|$thread_from%'");
	$board_ids = array();
	foreach($all_refs as &$ref) {
		foreach($id_map as $idm) {
			$ref_boardid = $ref['boardid'];
			//echo "ref_boardid->" . $ref_boardid . "<br/>";
			// Add the board id where reference was found to the array of boards which need regeneration
			if ($ref_boardid != $board_from_id && $ref_boardid != $board_to_id && !in_array($ref_boardid, $board_ids))
				$board_ids []= $ref_boardid;

			$id_before = $idm['before'];
			$id_after = $idm['after'];

			// Replace message contents
			$exp = '/<a href=\\\\"\/(' . $board_from. ')\/res\/' . $thread_from. '\.html#' . $id_before. '\\\\"( onclick=\\\\"return highlight\(\')?(' . $id_before. ')?(\', true\);\\\\")? class=\\\\"ref\|' . $board_from. '\|' . $thread_from. '\|' . $id_before. '(.+?)>(?:(&gt;&gt;)(?:(?:\/' . $board_from. '\/)?' . $id_before. ')|(##(?:OP|ОП)##)?)/';
			$ref['message'] = preg_replace_callback($exp, function($matches) use ($board_from, $thread_from, $id_before, $board_to, $thread_to, $id_after, $ref_boardid, $board_to_id) {
				$res = "<a href=\\\"/$board_to/res/$thread_to.html#$id_after\\\"";
				$res.= $matches[2];
				$res.= $matches[3] ? $id_after : '';
				$res.= $matches[4];
				$res.= " class=\\\"ref|$board_to|$thread_to|$id_after";
				$res.= $matches[5].'>';
				if ($matches[6]) {
					$res.= $matches[6];
					if ($ref_boardid != $board_to_id) // Reference should stay/become external
						$res.= "/$board_to/";
					$res.= $id_after;
				}
				else // In case it's a prooflabel
					$res.= $matches[7];
				return $res;
			}, $ref['message']);
		}
		$tc_db->Execute("UPDATE `" . KU_DBPREFIX . "posts` SET `message` = " . $tc_db->qstr($ref['message']) . " WHERE `boardid` = " . $ref_boardid . " AND `id` = " . $ref['id']);
	}
	$tc_db->Execute("COMMIT");

	//Regenerate pages
	/*
	foreach($board_ids as $b_id) {
		$boards_to_regenerate []= boardid_to_dir($b_id);
	}
	foreach($boards_to_regenerate as $b) {
		$board_class = new Board($b);
		$board_class->RegenerateAll();
		unset($b);
	}
	*/
}

?>