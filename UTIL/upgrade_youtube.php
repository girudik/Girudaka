<?php
header('Content-type: text/html; charset=utf-8');

// очистите сломанные ютубные файлы предварительно иначе скрипт сломается!
//SELECT * FROM `kusaba2_files` where file_type = 'you' and file_size_formatted='' AND LENGTH(FILE) != 11;

// после окочания скрипта (возможно его потребуется запустить не один раз в зависимости от отведённого ему времени выполнения) 


echo "init, get config<br/>";
require 'kint.phar';
require '../config.php';
require '../inc/func/posts.php';

$tc_db->debug = true;
/*ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

echo "get embeds<br/>";
$results = $tc_db->getAll("SELECT * FROM `" . KU_DBPREFIX . "files` where file_type = 'you' and file_size_formatted='' AND file_md5 = '';");
//$results = $tc_db->getAll("SELECT * FROM `" . KU_DBPREFIX . "embeds`;");

//d($results);
//SELECT id, name from `kusaba2_boards` WHERE id IN(SELECT boardid FROM `kusaba2_files` where file_type = 'you' and file_size_formatted='' GROUP BY boardid);

echo "get boards<br/>";
$boardsRAW = $tc_db->getAll("SELECT id, name from `kusaba2_boards`;");
$boards = [];
foreach($boardsRAW as $boardRAW) {
	$boards[$boardRAW["id"]] = $boardRAW["name"];
}

//$result = $tc_db->GetOne("SELECT `parentid` FROM `".KU_DBPREFIX."posts` WHERE `id`=? AND `boardid`=(SELECT `id` FROM `boards` WHERE `name`=?)", array($matches[2], $matches[1]));
echo "start processing<br/>";
$i=1;
$len = count($results);
foreach($results as $embed) {
	$boardid = $embed["boardid"];
	$code = $embed["file"];
	$attachment = thumbnailProcess($code, $boardid);;
	//s($attachment);
	$tc_db->Execute("UPDATE `".KU_DBPREFIX."files` SET `file_original`=?, file_size_formatted=?, file_md5=?, image_w=?, image_h=? ,thumb_w=?, thumb_h=? WHERE `file`=?", array($attachment["file_original"], $attachment["file_size_formatted"], $attachment["file_md5"], $attachment["image_w"], $attachment["image_h"], $attachment["thumb_w"], $attachment["thumb_h"], $code));
	usleep(300000); // 300мс
	//s($attachment);
	//SET file_md5='$md5', file_original='$title', file_size_formatted='$time', thumb_w=, thumb_h= where file_type = 'you' and file='ид';
	$i++;
	echo "done " . $i . " / " . $len . "<br />";
}
if ($len > 0) {
	echo "complete!<br/>";
} else {
	echo "nothing needs to be processed<br/>";
}


function thumbnailProcess($code, $boardid) {
	global $boards;
	$thumb_tmpfile = tmpfile();
	$video_data = fetch_video_data("you", $code, KU_VIDEOTHUMBWIDTH, $thumb_tmpfile);
	if ($video_data['error']) {
		echo("error fetch_video_data! " . $video_data['error']); ;
		s($code);
		die;
	}
	$attachment = [];
	$attachment['embedtype'] = 'you';
	$attachment['embed'] = $code;
	
	// semi copypasting from upload.class.php
	$embed_filename = $attachment['embedtype'].'-'.$attachment['embed'].'-';
	$attachment['file_thumb_location'] = KU_BOARDSDIR . $boards[$boardid] . '/thumb/' . str_replace("/", "_", $embed_filename) . 's.jpg';
	$attachment['file_thumb_cat_location'] = KU_BOARDSDIR . $boards[$boardid] . '/thumb/' . str_replace("/", "_", $embed_filename) . 'c.jpg';
	$metaData = stream_get_meta_data($thumb_tmpfile);
	$thumbfile = $metaData['uri'];
	$thumbnailed = [];
	//echo "copy thumbnail<br/>";
	$thumbnailed = copy($thumbfile, $attachment['file_thumb_location']);
	if (!$thumbnailed) {
		echo 'Could not create thumbnail.<br/>';
	}
	// Copy or create catalog thumbnail
	//echo "copy catalog thumbnail<br/>";
	$thumbnailed = copy($thumbfile, $attachment['file_thumb_cat_location']);
	if (!$thumbnailed) {
		echo 'Could not create catalog thumbnail.<br/>';
	}
	fclose($thumb_tmpfile);
	if (!$thumbnailed) {
		die;
	}
	// Fill the rest of the data
	$imageDim_thumb = getimagesize($attachment['file_thumb_location']);
	
	$file_md5 = md5_file($attachment['file_thumb_location']);
	
	$attachment['thumb_w'] = $imageDim_thumb[0];
	$attachment['thumb_h'] = $imageDim_thumb[1];
	$attachment['image_w'] = $video_data['width'];
	$attachment['image_h'] = $video_data['height'];
	if (!is_null($video_data['title']) && $video_data['title']) {
		$attachment['file_original'] = $video_data['title'];
		$attachment['file_size_formatted'] = $video_data['duration'];
	} else {
		$attachment['file_original'] = "";
		$attachment['file_size_formatted'] = "";
	}
	if ($video_data['duration'] == "23:59:59") { // ugly hack
		$attachment['file_size_formatted'] = "";
	}
	$attachment['file_md5'] = $file_md5;


	return $attachment;
}


/*
$posts = $tc_db->getAll("SELECT `id`, `boardid`, `message` FROM `".KU_DBPREFIX."posts` WHERE `message` LIKE '%<summary%'");

foreach($posts as $post) {
  $re = '/<summary[^>]+>([\s\S]+?)<\/summary>(?:<br>)?/m';
  $subst = '<summary class="read-more"><span class="xlink">$1</span></summary>';
  $msg_new = preg_replace($re, $subst, $post['message']);
  $tc_db->Execute("UPDATE `".KU_DBPREFIX."posts` SET `message`=? WHERE `id`=? AND `boardid`=?", array($msg_new, $post['id'], $post['boardid']));
}
*/

?>