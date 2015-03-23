<?php
include ('../dbconfig.php');
$db = new mysqli("localhost", $dbuser, $dbpass, "rbnsn_weavers1");
$blank = '';
if($db->connect_errno > 0){
    trigger_error("Unable to connect to database [{$db->connect_error}]", E_USER_ERROR);
}
$tmp = array();
$sql = 'select email, Username, ind FROM weavers_info_copy';
$stmt = $db->prepare($sql);
$stmt->execute();
$stmt->bind_result($email, $username, $ind);
while ($stmt->fetch()) {
	if (!array_key_exists("{$email}/{$username}", $tmp)) {
		$tmp["{$email}/{$username}"] = array();
	} else {
		$tmp["{$email}/{$username}"][] = $ind;
	}
}
ksort($tmp);
//$del_sql = array();
$i = 1;
foreach ($tmp as $k => $ary) {
	if (!empty($ary)) {
		$del_sql = "delete from weavers_info_copy where ind in (" . implode(', ',$ary) . ")";
		$stmt = $db->prepare($del_sql);
		$stmt->execute();
		++$i;
	}
}
?>
<!doctype html>
<html>
	<head>
		<title>De-duplicate</title>
	</head>
	<body>
		<pre><?php echo "Number groups deleted: $i"; ?></pre>
	</body>
</html>