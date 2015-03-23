<?php
session_start();
$logged_in = (isset($_SESSION['in']))?'true':'false';
include ('../dbconfig.php');
$db = new mysqli("localhost", $dbuser, $dbpass, "rbnsn_weavers1");
if($db->connect_errno > 0){
    trigger_error("Unable to connect to database [{$db->connect_error}]", E_USER_ERROR);
}
if (isset($_POST['geton'])) {
	parse_str($_POST['formdata'],$form_vars);
	if ($form_vars['u'] == 'ksr' && $form_vars['p'] == '$V0nn0Str@nd') {
		$_SESSION['in'] = 'true';
		$logged_in = 'true';
		exit(json_encode(array('ret'=>'Ok')));
	} else {
		exit(json_encode(array('ret'=>'Not OK','u'=>$form_vars['u'],'p'=>$form_vars['p'])));
	}
}

if (isset($_POST['getoff'])) {
	unset($_SESSION['in']);
	$logged_in = 'false';
	exit(json_encode(array('ret'=>'Ok')));
}

if (isset($_POST['op'])) {
	parse_str($_POST['formdata'],$form_vars);
	$ret_array = array('ret'=>'','ind'=>$form_vars['check_ind'],'error'=>'','op' => " {$_POST['op']}d");
	switch($_POST['op']) {
		case 'Delete':
			$sql = 'delete from weavers_info where ind = ?';
			$stmt = $db->prepare($sql);
			if ($stmt === false) {
				$ret_array['error'] = "Wrong SQL: $sql, Error: {$db->error}";
				$ret_array['ret'] = 'Not Ok';
				exit(json_encode($ret_array));
			}
			$stmt->bind_param('i',$form_vars['check_ind']);
			break;
		case 'Update':
			$sql = 'update weavers_info set Username = ?, Location = ?, email = ?, comment = ?, web_url = ?, web_title = ? where ind = ?';
			$stmt = $db->prepare($sql);
			if ($stmt === false) {
				$ret_array['error'] = "Wrong SQL: $sql, Error: {$db->error}";
				$ret_array['ret'] = 'Not Ok';
				exit(json_encode($ret_array));
			}
			$stmt->bind_param('ssssssi',$form_vars['check']['user'],
																 $form_vars['check']['location'],
																 $form_vars['check']['email'],
																 $form_vars['check']['comments'],
																 $form_vars['check']['url'],
																 $form_vars['check']['webtitle'],
																 $form_vars['check_ind']);
			break;
		case 'Add':
			$sql = 'insert into weavers_info (Username, Location, email, comment, web_url, web_title) values (?, ?, ?, ?, ?, ?)';
			$stmt = $db->prepare($sql);
			if ($stmt === false) {
				$ret_array['error'] = "Wrong SQL: $sql, Error: {$db->error}";
				$ret_array['ret'] = 'Not Ok';
				exit(json_encode($ret_array));
			}
			$stmt->bind_param('ssssss',$form_vars['check']['user'],
																 $form_vars['check']['location'],
																 $form_vars['check']['email'],
																 $form_vars['check']['comments'],
																 $form_vars['check']['url'],
																 $form_vars['check']['webtitle']
																 );
			break;
	}
	if (!$stmt->execute()) {
			$ret_array['error'] = "Wrong SQL: $sql, Error: {$db->error}";
			$ret_array['ret'] = 'Not Ok';
	} else {
			$ret_array['ret'] = 'Ok';
	}
	exit(json_encode($ret_array));
}



if (isset($_POST['check'])) {
	if ($logged_in) {
		parse_str($_POST['formdata'],$form_vars);
		$sql = 'select Username, Location, email, comment, web_url, web_title, ind from weavers_info where';
		$sql .= ($_POST['check_which'] == 'Check Name')?' Username = ?':' email = ?';
		$stmt = $db->prepare($sql);
		if($stmt === false) {
		  exit(json_encode(array('ret'=>'Not Ok','error'=>"Wrong SQL: $sql Error: {$db->error}")));
		}
		$check_var = ($_POST['check_which'] == 'Check Name')?'name_to_check':'email_to_check';
		$stmt->bind_param('s',$form_vars[$check_var]);
		$stmt->execute();
		$stmt->bind_result($username, $location, $email, $comment, $web_url, $web_title, $ind);
		if ($stmt->fetch()) {
			exit(json_encode(array('ret'=>'Ok','sql'=>$sql,'username'=>$username,'location'=>$location,'email'=>$email,
														'comments'=>$comment,'url'=>$web_url,'web_title'=>$web_title,'ind'=>$ind)));
		} else {
			exit(json_encode(array('ret'=>'Not OK','error'=>"Wrong SQL: $sql Error: {$db->error}")));
		}
	} else {
		exit(json_encode(array('ret'=>'Not OK')));
	}
}

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Weavers Admin</title>
		<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css">
		<style>
			.row {
				display: block;
				width: 100%;
				clear: both;
				float: left;
			}
			label {
				display: block;
				clear: both;
				float: left;
				width: 25%;
				font-weight: bold;
			}

			.inpt {
				display: block;
				float: left;
				width: 65%;
			}

			.hdn {
				display: none;
				clear: both;
			}

			.sb {
				clear: both;
			}
		</style>
		<script src="//code.jquery.com/jquery-1.10.2.min.js"></script>
		<script src="//code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
		<script src="./validVal/src/js/jquery.validVal.js"></script>
		<script>
			$(document).ready(function() {
				var logged_in = '<?php echo $logged_in ?>';
				if (logged_in == 'false') {
					$('#lgout').hide();
					$('#logged_in_buttons').hide();
				} else {
					$('#lgout').show();
					$('#logged_in_buttons').show();
				}
				$('#check2').hide();
				$("#lg").validVal();
				$('#check').validVal();
				$('#check2').validVal();
				$('#lg').dialog({
					autoOpen: false,
					width: 500,
				});
				$('#check').dialog({
					autoOpen: false,
					width: 500,
				});
				$('#check2').dialog({
					autoOpen: false,
					width: 700,
					close: function( event, ui ) {
						$('#check').dialog('open');
					},
				});
				if (logged_in == 'false') {
					$('#lg').dialog('open');
				} else {
					$('#logged_in_buttons').show();
//					$('#check').dialog('open');
				}
				$('#do_update').click(function() {
					$('#check2').dialog('close');
					$('#check').dialog('open');
				});
				$('#do_add').click(function() {
					$('#add_record').show();
					$('#change_record').hide();
					$('#check').dialog('close');
					$('#check2').dialog('open');
				});

				$('#lg input:submit').click(function(event) {
					event.preventDefault();
					var form_data2 = $('#lg').serialize();
					var form_data = $("#lg").triggerHandler( "submitForm" );
					$.post('<?php echo $_SERVER["PHP_SELF"]?>',{geton: 1, formdata: form_data2}, function(data) {
						if (data.ret == 'Ok') {
							logged_in = 'true';
							$('#lg').trigger( "resetForm" );
							$('#lg').dialog('close');
							$('#check').dialog('open');
							$('#lgout').show();
						}
					},'json');
				});
				$('#check2 input:submit').click(function(event) {
					event.preventDefault();
					var which_button = event.currentTarget.value;
					alert(which_button);
					var form_data2 = $('#check2').serialize();
					var form_data = $("#check2").triggerHandler( "submitForm" );
					$.post('<?php echo $_SERVER["PHP_SELF"]?>',{op: which_button, formdata: form_data2}, function(data) {
						if (data.ret == 'Ok') {
							$('#check2').dialog('close');
							alert('Record number ' + data.ind + data.op + ' Ok');
							$('#name_to_check').val('');
							$('#email_to_check').val('');
							$.each(['name','email','comments','location','url','webtitle'],function(key, value) {
								$('#check_' + value).val('');
							});
							$('#check').dialog('open');
						} else {
							alert('Couldn\'t ' + data.op + ' record number ' + data.ind + ', error: ' + data.error);
						}
					},'json');
				});
				$('#check input:submit').click(function(event) {
					event.preventDefault();
					var which_button = event.currentTarget.value;
					var form_data2 = $('#check').serialize();
					var form_data = $("#check").triggerHandler( "submitForm" );
					$.post('<?php echo $_SERVER["PHP_SELF"]?>',{check: 1, formdata: form_data2, check_which: which_button}, function(data) {
						if (data.ret == 'Ok') {
							$('#check').dialog('close');
							$('#check_user').val(data.username);
							$('#check_email').val(data.email);
							$('#check_location').val(data.location);
							$('#check_comments').val(data.comments);
							$('#check_url').val(data.url);
							$('#check_webtitle').val(data.web_title);
							$('#check_ind').val(data.ind);
							$('#add_record').hide();
							$('#change_record').show();
							$('#check2').dialog('open');
						} else {
							alert('Error:' + data.error);
						}
					},'json');
				});

				$('#lgout').click(function() {
					$.post('<?php echo $_SERVER["PHP_SELF"]?>',{getoff: 1}, function(data) {
						if (data.ret == 'Ok') {
							logged_in = 'false';
							$('#lgout').hide();
							$('#check').dialog('close');
							$('#check2').dialog('close');
							$('#lg').dialog('open');
						}
					},'json');
				});
			});
		</script>
	</head>
	<body>
	<form id='lg' action='#' method='post'>
		<div class='row'>
		<label for='u'>Username:</label>
		<input type='text' class='inpt' required id='u' name='u'>
		<label for='p'>Password:</label>
		<input type='password' class='inpt' required id='p' name='p'>
		</div>
		<input class='hdn' name='hdn'>
		<input type='submit' value='Log In' class='sb' name='geton'>
	</form>
	<form id='check' action='#' method='post'>
		<div class='row'>
			<label for='name_to_check'>Name to check</label>
			<input type='text' name='name_to_check' id='name_to_check' class='inpt'>
		</div>
		<div class='row'>
			<label for='email_to_check'>Email to check</label>
			<input type='email' name='email_to_check' id='name_to_check' class='inpt'>
		</div>
		<input class='hdn' name='hdn'>
		<input type='submit' value='Check Name' id='checkit_name' name='checkit[name]'><input type='submit' value='Check Email' id='checkit_email' name='checkit[email]'>
	</form>
	<form action="#" method="post" id='check2'>
		<p>
			<div class='row'>
				<label for='check_user'>User: </label><input type='text' class='inpt' id='check_user' name='check[user]'>
			</div>
			<div class='row'>
				<label for='check_email'>Email: </label><input type='email' class='inpt' id='check_email' name='check[email]'>
			</div>
			<div class='row'>
				<label for='check_location'>Location: </label><input type='text' class='inpt' id='check_location' name='check[location]'>
			</div>
			<div>
				<label for='check_comments'>Comments: </label><input type='text' class='inpt' id='check_comments' name='check[comments]'>
			</div>
			<div class='row'>
				<label for='check_url'>URL: </label><input type='url' class='inpt' id='check_url' name='check[url]'>
			</div>
			<div class='row'>
				<label for='check_webtitle'>Page Title: </label><input type='text' class='inpt' id='check_webtitle' name='check[webtitle]'>
			</div>
			<input type='hidden' id='check_ind' name='check_ind'>
			<div class='row'>
				<input type='submit' id='delete_record' name='submit[delete]' value='Delete'>
				<input type='submit' id='change_record' name='submit[change]' value='Update'>
				<input type='submit' id='add_record' name='submit[add'] value='Add'>
			</div>
		</p>
	</form>
	<button id='lgout' value='Log Out'>Log Out</button>
	<div id='logged_in_buttons'>
		<button id='do_update' value='Update Member'>Update Member</button>
		<button id='do_add' value='Add Member'>Add New Member</button>
	</div>
	</body>
</html>