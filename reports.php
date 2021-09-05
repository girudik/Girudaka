<?php
require 'config.php';
require KU_ROOTDIR . 'lib/dwoo.php';
require KU_ROOTDIR . 'inc/functions.php';
require KU_ROOTDIR . 'inc/classes/manage.class.php';

	
/* View and delete reports */
function reports() {
	global $tc_db, $tpl_page;
	//$this->ModeratorsOnly();

	$tpl_page .= '<h2>'. _gettext('Reports') . '</h2><br />';
	$query = "SELECT * FROM `" . KU_DBPREFIX . "reports` WHERE `cleared` = 0 ORDER BY id DESC";
	$resultsreport = $tc_db->GetAll($query);
	if (count($resultsreport) > 0) {
		//$tpl_page .= '<table border="1" width="100%"><tr><th>Board</th><th>Post</th><th>File</th><th>Message</th><th>Reason</th><th>Reporter IP</th><th>Action</th></tr>';
		$tpl_page .= '<table border="1" width="100%"><tr><th>Id</th><th>Board</th><th>Post</th><th>File</th><th>Message</th><th>Reason</th><th>Time</th><th>Action</th></tr>';
		foreach ($resultsreport as $linereport) {
			$reportboardid = $tc_db->GetOne("SELECT HIGH_PRIORITY `id` FROM `" . KU_DBPREFIX . "boards` WHERE `name` = " . $tc_db->qstr($linereport['board']) . "");
			$results = $tc_db->GetAll("SELECT * FROM `" . KU_DBPREFIX . "postembeds` WHERE `boardid` = " . $reportboardid . " AND `id` = " . $tc_db->qstr($linereport['postid']) . "");
			foreach ($results as $line) {
				if ($line['IS_DELETED'] == 0) {
					$tpl_page .= '<tr><td>'.$linereport['id'].'</td>';
					$tpl_page .= '<td>/'. $linereport['board'] . '/</td><td><a href="'. KU_BOARDSPATH . '/'. $linereport['board'] . '/res/';
					if ($line['parentid'] == '0') {
						$tpl_page .= $linereport['postid'];
						$post_threadorpost = 'thread';
					} else {
						$tpl_page .= $line['parentid'];
						$post_threadorpost = 'post';
					}
					$tpl_page .= '.html#'. $linereport['postid'] . '">'. $line['id'] . '</a></td><td>';
					if ($line['file'] == 'removed') {
						$tpl_page .= 'removed';
					} elseif ($line['file'] == '') {
						$tpl_page .= 'none';
					} elseif ($line['file_type'] == 'jpg' || $line['file_type'] == 'gif' || $line['file_type'] == 'png') {
						$tpl_page .= '<a href="'. KU_BOARDSPATH . '/'. $linereport['board'] . '/src/'. $line['file'] . '.'. $line['file_type'] . '"><img style="max-width: 100px; max-height: 100px;" loading="lazy" src="'. KU_BOARDSPATH . '/'. $linereport['board'] . '/thumb/'. $line['file'] . 's.'. $line['file_type'] . '" border="0"></a>';
					} elseif ($line['file_type'] == 'you') {
						$tpl_page .= 'youtube: <a href="https://youtu.be/' . $line['file'] . '"><img style="max-width: 100px; max-height: 100px;" loading="lazy" src="'. KU_BOARDSPATH . '/'. $linereport['board'] . '/thumb/you-'. $line['file'] . '-s.jpg" border="0"></a>';
					} else {
						$tpl_page .= '<a href="'. KU_BOARDSPATH . '/'. $linereport['board'] . '/src/'. $line['file'] . '.'. $line['file_type'] . '">File</a>';
					}
					$tpl_page .= '</td><td><div style="max-height: 100px;overflow-y: auto;padding-right: 20px;">';
					if ($line['message'] != '') {
						$tpl_page .= stripslashes($line['message']);
					} else {
						$tpl_page .= '&nbsp;';
					}
					$tpl_page .= '</div></td><td>';
					if ($linereport['reason'] != '') {
						$tpl_page .= htmlspecialchars(stripslashes($linereport['reason']));
					} else {
						$tpl_page .= '&nbsp;';
					}
					$tpl_page .= '<td>';
					$tpl_page .= date(DATE_RFC822, $linereport['when']);

					//$tpl_page .= '</td><td>'. md5_decrypt($linereport['ip'], KU_RANDOMSEED) . '</td><td><a href="?action=reports&clear='. $linereport['id'] . '">Clear</a>&nbsp;&#91;<a href="?action=delposts&boarddir='. $linereport['board'] . '&del'. $post_threadorpost . 'id='. $line['id'] . '" title="Delete" onclick="return confirm(\'Are you sure you want to delete this thread/post?\');">D</a>&nbsp;<a href="'. KU_CGIPATH . '/manage_page.php?action=delposts&boarddir='. $linereport['board'] . '&del'. $post_threadorpost . 'id='. $line['id'] . '&postid='. $line['id'] . '" title="Delete &amp; Ban" onclick="return confirm(\'Are you sure you want to delete and ban this poster?\');">&amp;</a>&nbsp;<a href="?action=bans&banboard='. $linereport['board'] . '&banpost='. $line['id'] . '" title="Ban">B</a>&#93;</td></tr>';
					$tpl_page .= '</td><td><a href="/manage_page.php?action=reports&clear='. $linereport['id'] . '">OK</a>&nbsp;or&nbsp;&#91;<a href="/manage_page.php?action=delposts&boarddir='. $linereport['board'] . '&del'. $post_threadorpost . 'id='. $line['id'] . '" title="Delete" onclick="return confirm(\'Are you sure you want to delete this thread/post?\');">Del</a>&nbsp;<a href="'. KU_CGIPATH . '/manage_page.php?action=delposts&boarddir='. $linereport['board'] . '&del'. $post_threadorpost . 'id='. $line['id'] . '&postid='. $line['id'] . '" title="Delete &amp; Ban" onclick="return confirm(\'Are you sure you want to delete and ban this poster?\');">&amp;</a>&nbsp;<a href="/manage_page.php?action=bans&banboard='. $linereport['board'] . '&banpost='. $line['id'] . '" title="Ban">Ban</a>&#93;</td></tr>';
				} else {
					$tc_db->Execute("UPDATE `" . KU_DBPREFIX . "reports` SET `cleared` = 1 WHERE id = " . $linereport['id'] . "");
				}
			}
		}
		$tpl_page .= '</table>';
	} else {
		$tpl_page .= 'No reports to show.';
	}
}
reports();
echo $tpl_page;
?>
