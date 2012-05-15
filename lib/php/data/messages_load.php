<?php
//Loads messages

session_start();
require('../auth/session_check.php');
require('../../../db.php');
require('../utilities/names.php');
require('../utilities/convert_times.php');
require('../utilities/thumbnails.php');
require('../utilities/format_text.php');
require('../html/gen_select.php');



function in_string($val,$string)
{
	$val_1 = ',' . $val .',';
	$val_2 = $val . ',';

	if (stristr($string, $val_1))
		{return true;}
	elseif (stristr($string, $val_2))
		{return true;}
	else
		{return false;}
}

function format_name_list($dbh,$list)
{
	$names = explode(',', $list);
	$n = null;
	foreach ($names as $name) {
		$n .= username_to_fullname($dbh,$name) . ", ";
	}
	$n_strip = substr($n, 0,-2);
	return $n_strip;
}

$username = $_SESSION['login'];
$limit = '20';

if (isset($_POST['type']))
	{$type = $_POST['type'];}

if (isset($_POST['start']))
	{$start = $_POST['start'];}

if (isset($_POST['id']))
	{$id = $_POST['id'];}

if (isset($_POST['thread_id']))
	{$thread_id = $_POST['thread_id'];}

if (isset($_POST['new_message']))
	{$new_message = true;}

$replies = false;

switch ($type) {

	case 'inbox':

		$q = $dbh->prepare("SELECT * FROM (SELECT * FROM cm_messages WHERE `archive` NOT LIKE '%,$username,%' AND `archive` NOT LIKE '$username,%' AND `id` = `thread_id`) AS no_archive WHERE (no_archive.to LIKE '%,$username,%' OR no_archive.to LIKE '$username,%' OR no_archive.to LIKE '%,$username' OR no_archive.to = '$username') OR (no_archive.ccs LIKE  '%,$username,%' OR no_archive.ccs LIKE '$username,%'  OR no_archive.ccs LIKE '%,$username' OR no_archive.ccs = '$username') ORDER BY no_archive.time_sent DESC LIMIT $start, $limit");

		$q->execute();

		$msgs = $q->fetchAll(PDO::FETCH_ASSOC);

	break;

	case 'sent':

		$q = $dbh->prepare("SELECT * from cm_messages WHERE `from` LIKE '$username' ORDER BY `time_sent` DESC  LIMIT $start, $limit");

		$q->execute();

		$msgs = $q->fetchAll(PDO::FETCH_ASSOC);

	break;

	case 'archive':

		$q = $dbh->prepare("SELECT * from (SELECT * FROM cm_messages WHERE `id` = `thread_id`) AS no_thread WHERE no_thread.archive LIKE '%,$username,%' OR no_thread.archive LIKE '$username,%' ORDER BY no_thread.time_sent DESC  LIMIT $start, $limit");

		$q->execute();

		$msgs = $q->fetchAll(PDO::FETCH_ASSOC);


	break;

	case 'draft' :


	break;

	case 'starred' :


	break;

	case 'replies' :

		$q = $dbh->prepare("SELECT * FROM cm_messages WHERE thread_id = :thread_id AND id != :thread_id");

		$data = array('thread_id' => $thread_id);

		$q->execute($data);

		$msgs = $q->fetchAll(PDO::FETCH_ASSOC);

		$replies = true;


	break;
}

if (empty($msgs) AND $replies === false AND $new_message === false)
	//i.e, there are no messages to display, we are not loading replies, and
	//this is not a request for the new message html
	{echo "<p>There are no messages in your $type folder</p>";die;}

include('../../../html/templates/interior/messages_display.php');
