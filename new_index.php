<?php
function check_spambot($email_addr) {
	$spambot = false;
	$result = unserialize(file_get_contents("http://www.stopforumspam.com/api?email={$email_addr}&f=serial"));
	if($result['email']['appears'] == '1'){
		$spambot = true;
	}
	return array('ret'=>$spambot,'sb'=>$result);
}

 function addOrdinalNumberSuffix($num) {
    if (!in_array(($num % 100),array(11,12,13))){
      switch ($num % 10) {
        // Handle 1st, 2nd, 3rd
        case 1:  return $num.'st';
        case 2:  return $num.'nd';
        case 3:  return $num.'rd';
      }
    }
    return $num.'th';
 }
include ('../dbconfig.php');
$db = new mysqli("localhost", $dbuser, $dbpass, "rbnsn_weavers1");
$blank = '';
if($db->connect_errno > 0){
    trigger_error("Unable to connect to database [{$db->connect_error}]", E_USER_ERROR);
}
if (isset($_POST['search'])) {
	$sql = 'SELECT Username, Location, email, comment, web_url, web_title, ind FROM weavers_info_copy WHERE Username LIKE ?';
	$stmt = $db->prepare($sql);
	$search_str = "{$_POST['search']}%";
	$stmt->bind_param('s',$search_str);
	$stmt->execute();
	$stmt->bind_result($username, $location, $email, $comment, $web_url, $web_title, $ind);
	$tmp = array();
	$background = 'lightblue';
	$tmp[] = "<div class='row' style='background-color:$background'>";
	$tmp[] = "<span class='huser'>Name</span>";
	$tmp[] = "<span class='hlocation'>Location</span>";
	$tmp[] = "<span class='hemail'>Email</span>";
	$tmp[] = "<span class='hcomment'>Round Dance Interests</span>";
	$tmp[] = "<span class='hurl'>Web Site</span>";
	$tmp[] = "<div class='clearer'>&nbsp;</div>";
	$tmp[] = "</div>";
	$background = "lightgrey";
	while ($stmt->fetch()) {
		$tmp[] = "<div class='row' style='background-color:$background'>";
		$background = ($background == 'lightgrey')?'white':'lightgrey';
	  $tmp[] = "<span class='user'>&nbsp;$username</span>";
	  $tmp[] = ($location != '')?"<span class='loc'>$location</span>":"<span class='loc'>&nbsp;</span>";
	  if ($email != '') {
	  	$ary = explode(',',$email);
	  	$tmp[] = "<span class='email'>";
	  	$tmpe = array();
	  	foreach ($ary as $n => $addr) {
	  		$tmpe[] = "<button class='see_email' data-ind='$ind' id='see_$n'>See Email Address</button><button class='send_email' data-ind='$ind' id='id_$n'>Send Email</button>";
	  	}
	  	$tmp[] = implode("<br>",$tmpe);
	  	$tmp[] = '</span>';
	  } else {
	  	$tmp[] = "<span class='email'>&nbsp;</span>";
	  }
	  $tmp[] = ($comment != '')?"<span class='comment'>$comment</span>":"<span class='comment'>&nbsp;</span>";
	  if ($web_url != '') {
	  	if (stripos($web_url,'**(bad)**') === FALSE) {
	  		$urls = explode(',',$web_url);
	  		$titles = explode('`',$web_title);
	  		$tmpw = array();
	  		for ($i = 0;$i<count($urls);$i++) {
	  			$tmpw[] = "<a href='{$urls[$i]}' target='_blank'>{$titles[$i]}</a>";
	  		}
	  		$tmp[] = "<span class='url'>";
	  		$tmp[] = implode("<br>",$tmpw);
	  		$tmp[] = '</span>';
	  	} else {
	  		$x = explode('**',$web_url);
	  		$tmp[] = "<span class='bad_url'>Broken URL: {$x[0]}</span>";
	  	}
	  } else {
	  	$tmp[] = "<span class='url'>&nbsp;</span>";
	  }
	  $tmp[] = "<div class='clearer'>&nbsp;</div>";
	  $tmp[] = '</div>';
	}
	exit(json_encode(array('ret'=>'Ok','html'=>implode("\n",$tmp)."\n")));
}
if (isset($_POST['send'])) {
	parse_str($_POST['form_data'],$form_vars);
	$cs = check_spambot($form_vars['your_email']);
	if($cs['ret']) {
		mail('kenrbnsn@rbnsn.com','Bad email entered on Weavers send',"Email: {$form_vars['your_email']}",'From: Weavers Error <error@weavers.rbnsn.com>');
		exit(json_encode(array('ret'=>'Not OK','reason'=>'Bad email address','sp'=>$cs['sb'])));
	}
	exit(json_encode(array('ret'=>'Ok','printr'=>print_r($form_vars,true) . var_export($cs['sb'],true))));
}
if (isset($_POST['changes'])) {
	parse_str($_POST['form_data'],$form_vars);
	$cs = array();
	$cs['ye'] = array();
	$cs['ye'] = check_spambot($form_vars['your_email']);
	$cs['ye']['your_email'] = $form_vars['your_email'];
	if($cs['ye']['ret']) {
		mail('kenrbnsn@rbnsn.com','Bad email entered on Weavers change',"Email: {$form_vars['your_email']}",'From: Weavers Error <error@weavers.rbnsn.com>');
		exit(json_encode(array('ret'=>'Not OK','reason'=>'Bad email address','sp'=>$cs['ye']['sb'])));
	}
	if ($_POST['form_data']['new[email'] != ''){
		$cs['ne'] = check_spambot($form_vars['new']['email']);
		if ($cs['ne']['ret']) {
			mail('kenrbnsn@rbnsn.com','Bad new email entered on Weavers change',"Email: {$form_vars['new']['email']}",'From: Weavers Error <error@weavers.rbnsn.com>');
			exit(json_encode(array('ret'=>'Not OK','reason'=>'Bad email address','sp'=>$cs['ne']['sb'])));
		}
	}
	$tmp = array();
	$tmp[] = "The following changes to the Weavers data have been requested by {$form_vars['your_name']}";
	foreach ($form_vars['reason'] as $which => $dmy) {
		$tmp[] = "New {$which}: {$form_vars['new'][$which]}";
	}
	mail('kenrbnsn@rbnsn.com','Weavers Changes',implode("\n",$tmp)."\n","From: \"{$form_vars['your_name']}\" <{$form_vars['your_email']}>","-f {$form_vars['your_email']}");
	exit(json_encode(array('ret'=>'Ok')));
}
if (isset($_POST['see'])) {
	list($dmy,$which) = explode('_',$_POST['id']);
	$sql = 'SELECT Username, email FROM weavers_info_copy WHERE ind = ?';
	$stmt = $db->prepare($sql);
	if($stmt === false) {
	  trigger_error("Wrong SQL: $sql Error: {$db->error}", E_USER_ERROR);
	}
	$stmt->bind_param('i',$_POST['ind']);
	$stmt->execute();
	$stmt->bind_result($name, $email);
	$stmt->fetch();
	$adrs = explode(',',$email);
	$th = (count($adrs) > 1)?addOrdinalNumberSuffix($which+1):'';
	exit(json_encode(array('ret'=>'Ok','name'=>$name,'emailaddr'=>$adrs[$which],'which'=>$th)));
}
$tmp = array();
$tmp[] = '<div id="tabs" style="display:none">';
$tmp[] = '  <ul>';
$tmp[] = '    <li><a href="#tabs-1">With Personal Data</a></li>';
$tmp[] = '    <li><a href="#tabs-2">Without Personal Data</a></li>';
$tmp[] = '  </ul>';
$tmp[] = '	<div id="tabs-1">';
$background = 'lightblue';
$tmp[] = "<div class='row' style='background-color:$background'>";
$tmp[] = "<span class='huser'>Name</span>";
$tmp[] = "<span class='hlocation'>Location</span>";
$tmp[] = "<span class='hemail'>Email</span>";
$tmp[] = "<span class='hcomment'>Round Dance Interests</span>";
$tmp[] = "<span class='hurl'>Web Site</span>";
$tmp[] = "<div class='clearer'>&nbsp;</div>";
$tmp[] = "</div>";

$sql = 'SELECT Username, Location, email, comment, web_url, web_title, ind FROM weavers_info_copy WHERE Username != ? order by Username';
/* Prepare statement */
$stmt = $db->prepare($sql);
if($stmt === false) {
  trigger_error("Wrong SQL: $sql Error: {$db->error}", E_USER_ERROR);
}

/* Bind parameters. TYpes: s = string, i = integer, d = double,  b = blob */
$stmt->bind_param('s',$blank);
$stmt->execute();
$stmt->bind_result($username, $location, $email, $comment, $web_url, $web_title, $ind);
$background = "lightgrey";
while ($stmt->fetch()) {
	$tmp[] = "<div class='row' style='background-color:$background'>";
	$background = ($background == 'lightgrey')?'white':'lightgrey';
  $tmp[] = "<span class='user'>&nbsp;$username</span>";
  $tmp[] = ($location != '')?"<span class='loc'>$location</span>":"<span class='loc'>&nbsp;</span>";
  if ($email != '') {
  	$ary = explode(',',$email);
  	$tmp[] = "<span class='email'>";
  	$tmpe = array();
  	foreach ($ary as $n => $addr) {
  		$tmpe[] = "<button class='see_email' data-ind='$ind' id='see_$n'>See Email Address</button><button class='send_email' data-ind='$ind' id='id_$n'>Send Email</button>";
  	}
  	$tmp[] = implode("<br>",$tmpe);
  	$tmp[] = '</span>';
  } else {
  	$tmp[] = "<span class='email'>&nbsp;</span>";
  }
  $tmp[] = ($comment != '')?"<span class='comment'>$comment</span>":"<span class='comment'>&nbsp;</span>";
  if ($web_url != '') {
  	if (stripos($web_url,'**(bad)**') === FALSE) {
  		$urls = explode(',',$web_url);
  		$titles = explode('`',$web_title);
  		$tmpw = array();
  		for ($i = 0;$i<count($urls);$i++) {
  			$tmpw[] = "<a href='{$urls[$i]}' target='_blank'>{$titles[$i]}</a>";
  		}
  		$tmp[] = "<span class='url'>";
  		$tmp[] = implode("<br>",$tmpw);
  		$tmp[] = '</span>';
  	} else {
  		$x = explode('**',$web_url);
  		$tmp[] = "<span class='bad_url'>Broken URL: {$x[0]}</span>";
  	}
  } else {
  	$tmp[] = "<span class='url'>&nbsp;</span>";
  }
  $tmp[] = "<div class='clearer'>&nbsp;</div>";
  $tmp[] = '</div>';
}
$tmp[] = '</div>';
$tmp[] = '<div id="tabs-2">';
$sql = 'SELECT Username, Location, email, comment, web_url, web_title, ind FROM weavers_info_copy WHERE Username = ? order by email';
/* Prepare statement */
$stmt = $db->prepare($sql);
if($stmt === false) {
  trigger_error("Wrong SQL: $sql Error: {$db->error}", E_USER_ERROR);
}

/* Bind parameters. TYpes: s = string, i = integer, d = double,  b = blob */
$stmt->bind_param('s',$blank);
$stmt->execute();
$stmt->bind_result($username, $location, $email, $comment, $web_url, $web_title, $ind);
while ($stmt->fetch()) {
	$tmp[] = "<div class='row' style='background-color:$background'>";
	$background = ($background == 'lightgrey')?'white':'lightgrey';
	list($tmpu,$dmy) = explode('@',$email, 2);
	$tmpu .= '@...';
  $tmp[] = "<span class='user'>&nbsp;$tmpu</span>";
  $tmp[] = ($location != '')?"<span class='loc'>$location</span>":"<span class='loc'>&nbsp;</span>";
  if ($email != '') {
  	$ary = explode(',',$email);
  	$tmp[] = "<span class='email'>";
  	$tmpe = array();
  	foreach ($ary as $n => $addr) {
  		$tmpe[] = "<button class='see_email' data-ind='$ind' id='see_$n'>See Email Address</button><button class='send_email' data-ind='$ind' id='id_$n'>Send Email</button>";
  	}
  	$tmp[] = implode("<br>",$tmpe);
  	$tmp[] = '</span>';
  } else {
  	$tmp[] = "<span class='email'>&nbsp;</span>";
  }
  $tmp[] = ($comment != '')?"<span class='comment'>$comment</span>":"<span class='comment'>&nbsp;</span>";
  if ($web_url != '') {
  	if (stripos($web_url,'**(bad)**') === FALSE) {
  		$urls = explode(',',$web_url);
  		$titles = explode('`',$web_title);
  		$tmpw = array();
  		for ($i = 0;$i<count($urls);$i++) {
  			$tmpw[] = "<a href='{$urls[$i]}' target='_blank'>{$titles[$i]}</a>";
  		}
  		$tmp[] = "<span class='url'>";
  		$tmp[] = implode("<br>",$tmpw);
  		$tmp[] = '</span>';
  	} else {
  		$x = explode('**',$web_url);
  		$tmp[] = "<span class='bad_url'>Broken URL: {$x[0]}</span>";
  	}
  } else {
  	$tmp[] = "<span class='url'></span>";
  }
  $tmp[] = "<div class='clearer'>&nbsp;</div>";
  $tmp[] = '</div>';
}
$tmp[] = '</div>';
$tmp[] = '</div>';
?>
<!DOCTYPE html>
<html>
	<head>
		<title>New Weavers</title>
		<META name="keywords" content="round dance, cue, cues, cue sheet, cue sheets, quick cues, head cues, weaver, weavers, Weaver, Weavers, cuers, cuer, ballroom">
		<META name="description" content="The Weavers Round Dance page. An International listing of cuers and dancers.">
		<META name="author" content="Ken Robinson, Widgets on the Web, Hillsborough, NJ">
		<META NAME="RATING" CONTENT="General">
		<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css">
		<style>
			body {
				height:100%;
			}

			.row {
				display: block;
				width: 95%;
				margin-right: auto;
				margin-left: auto;
				clear: both;
				padding-top: 0.25em;
				padding-bottom: 0.25em;
			}

			.user, .comment,.email  {
				display: block;
				width: 20%;
				float: left;
			}

			.loc {
				display: block;
				width: 15%;
				float: left;
			}

			.url {
				display: block;
				width: 25%;
				float: left;
			}

			.huser, .hcomment,.hemail  {
				display: block;
				width: 20%;
				float: left;
				font-weight: bold;
				text-align: center;
			}

			.hlocation {
				display: block;
				width: 15%;
				float: left;
				font-weight: bold;
				text-align: center;
			}

			.hurl {
				display: block;
				width: 25%;
				float: left;
				font-weight: bold;
				text-align: center;
			}
			.bad_url {
				display: block;
				width: 25%;
				float: left;
				color: red;
			}

			.clearer {
				clear: both;
				line-height: 0.01em;
			}

			#show_email_addr {
				display: block;
				width: 50%;
			}

			label {
				font-weight: bold;
				display: block;
				clear: both;
				float: left;
				width: 25%;
			}

			.inpt {
				display: block;
				float: left;
				width: 70%;
			}

			textarea {
				display: block;
				float: left;
				width: 70%;
			}

			.rdo, .fl {
				float: left;
			}

			.ui-widget {
				font-size: 75%;
			}

			.new_field {
				display: block;
				width: 100%;
				clear: both;
				float: left;
			}

		</style>
		<script src="//code.jquery.com/jquery-1.10.2.min.js"></script>
		<script src="//code.jquery.com/ui/1.10.3/jquery-ui.js"></script>
		<script src="./validVal/src/js/jquery.validVal.js"></script>
		<script>
			$(document).ready(function() {
				$('.new_field').hide();
			  $( "#tabs" ).tabs();
			  $("#tabs").show();
				$("#send_mail_form").validVal();
				$('#changes_updates_form').validVal();
				$('#show_email_addr').dialog({
					modal: true,
					position: 'center',
					autoOpen: false,
					width: 500,
				});
				$("#search_results").dialog({
					model: true,
					autoOpen: false,
					width: 900,
				});

				$('#send_email').dialog({
					modal: true,
					autoOpen: false,
					width: 700,
				});
				$('#changes_updates').dialog({
					modal: true,
					autoOpen: false,
					width: 700,
				});
				$('.see_email').on('click',function() {
					$.post("<?php echo $_SERVER['PHP_SELF']?>",{see: 1, id:$(this).attr('id'), ind: $(this).data('ind')},
						function(data) {
							if (data.ret == 'Ok') {
								str = (data.name) != '' ? ' for ' + data.name + ' is ' : ' is ';
								$('#show_email_addr').text('The ' + data.which + ' email address ' + str + data.emailaddr);
								$('#show_email_addr').dialog('open');
//								alert('Email address: ' + data.emailaddr);
							}
						},'json');
					});
				$('.reason_check_boxes').click(function() {
					id = $(this).attr('id');
					if ($('#' + id).is(':checked')) {
						id = id.replace('reason','new');
						$('#enter_' + id).prop('placeholder','required');
						$('#enter_' + id).prop('required',true);
						$('#' + id).show();
					} else {
						id = id.replace('reason','new');
						$('#' + id).hide();
						$('#enter_'+id).val('');
						$('#enter_'+id).prop('placeholder','');
						$('#enter_'+id).prop('required',false);

					}
				});
				$('#show_changes_form').click(function() {
					$.each(['name','email','interests','location','url'],function(key, value) {
						$('#reason_' + value).prop('checked',false);
						$('#new_' + value).hide();
						$('#enter_new_' + value).val('');
						$('#enter_new_' + value).prop('placeholder','');
						$('#enter_new_' + value).prop('required',false);
					});
					$('#changes_updates').dialog('open');
				});
				$('.send_email').click(function() {
					$.post("<?php echo $_SERVER['PHP_SELF']?>",{see: 1, id:$(this).attr('id'), ind: $(this).data('ind')},
						function(data) {
							if (data.ret == 'Ok') {
								toStr = (data.name != '') ? data.name + ' (' + data.emailaddr + ')' : data.emailaddr;
								$('#send_email').dialog( "option", "title", "Send Email to " + toStr );
								$('#send_email').dialog('open');
							}
						},'json');
				});
				$('#changes_updates_form input:submit').click(function(event) {
					event.preventDefault();
					var form_data2 = $('#changes_updates_form').serialize();
					var form_data = $( "#changes_updates_form" ).triggerHandler( "submitForm" );
					if (form_data) {
						$.post("<?php echo $_SERVER['PHP_SELF'] ?>",{changes: 1, form_data: form_data2}, function(data) {
							if (data.ret == 'Ok') {
								alert('Your changes have been sent. We will notify you when they are done');
								$("#changes_updates_form").trigger( "resetForm" );
								$('#changes_updates').dialog('close');
							} else {
								alert('Oops ... ' + data.reason);
							}
						},'json');
					}
				});
				$('#send_mail_form input:submit').click(function(event) {
					event.preventDefault();
					var form_data2 = $('#send_mail_form').serialize();
					var form_data = $( "#send_mail_form" ).triggerHandler( "submitForm" );
					if (form_data) {
						$.post("<?php echo $_SERVER['PHP_SELF'] ?>",{send: 1,form_data: form_data2}, function(data) {
							if (data.ret == 'Ok') {
								alert('Back from submit ' + data.printr);
								$("#send_mail_form").trigger( "resetForm" );
								$('#send_email').dialog('close');
							} else {
								alert('Oops ... ' + data.reason);
							}
						},'json');
					}
				});
			  $('#search_for_name').click(function() {
			  	$.post("<?php echo $_SERVER['PHP_SELF'] ?>",{search: $('#name_search').val()},function(data) {
			  		$('#search_results').html(data.html);
			  		$('#search_results').on('click','.see_email',function() {
							$.post("<?php echo $_SERVER['PHP_SELF']?>",{see: 1, id:$(this).attr('id'), ind: $(this).data('ind')},
								function(data) {
									if (data.ret == 'Ok') {
										str = (data.name) != '' ? ' for ' + data.name + ' is ' : ' is ';
										$('#show_email_addr').text('The ' + data.which + ' email address ' + str + data.emailaddr);
										$('#show_email_addr').dialog('open');
									}
								},'json');
							});
						$('#search_results').on('click','.send_email',function() {
							$.post("<?php echo $_SERVER['PHP_SELF']?>",{see: 1, id:$(this).attr('id'), ind: $(this).data('ind')},
								function(data) {
									if (data.ret == 'Ok') {
										toStr = (data.name != '') ? data.name + ' (' + data.emailaddr + ')' : data.emailaddr;
										$('#send_email').dialog( "option", "title", "Send Email to " + toStr );
										$('#send_email').dialog('open');
									}
								},'json');
							});

			  		$('#search_results').dialog('open');
			  	},'json');
			  });
			});
		</script>
		<?php
			include('../ga.inc.php');
		?>
	</head>
	<body>
			<div style="text-align:center;font-size:130px; font-family:Dance Craze BV; color:red;font-style:italic">the Weavers</div>
			<div style="font-family:Salina; font-size:75px; font-weight:bold; color:black;text-align:center">Membership<br>Directory</div>
			<div class="search"><input type="textfield" name="name_search" id="name_search"><button id="search_for_name">Search for Name that begins with...</button></div>
			<button id="show_changes_form">Send Changes/Updates</button>
		<?php
			echo implode("\n",$tmp) . "\n";
		?>
		<div id="show_email_addr"></div>
		<div id="send_email" style="display:none">
			<form id="send_mail_form" method="post">
				<label for="your_name">Your Name:</label>
				<input type="text" class="inpt" name="your_name" id="your_name" required placeholder="required">
				<label for="your_email">Your Email:</label>
				<input type="email" class="inpt" name="your_email" id="your_email" required placeholder="required">
				<label for="subject">Subject:</label>
				<input type="text" class="inpt" name="subject" id="subject" required placeholder="required">
				<label for="message">Message:</label>
				<textarea rows="10" cols="60" name="message" id="message"></textarea>
				<label for="copy_youremail_yes">Copy to yourself:</label>
				<div class="fl">
				<input type="radio" name="copy_youremail" id="copy_youremail_yes" value="yes" checked>yes<br>
				<input class="rdo" type="radio" name="copy_youremail" id="copy_youremail_no" value="no">no
				</div>
				<input style="display:none" name="my_spam_bot" value="">
				<input class="fl" style="clear:both" type="submit" id="submit_button" name="submit_button" value="Send Email Message">
			</form>
		</div>
		<div id="changes_updates" style="display:none">
			<form id="changes_updates_form" method="post">
				<label for="your_name">Your Name:</label>
				<input type="text" class="inpt" name="your_name" id="your_name" required placeholder="required">
				<label for="your_email">Your Email:</label>
				<input type="email" class="inpt" name="your_email" id="your_email" required placeholder="required">
				<label for="reason_name">Reason for change:</label>
				<div class="fl">
					<input type="checkbox" name="reason[name]" class="reason_check_boxes" id="reason_name" value="yes"> Name Change<br>
					<input type="checkbox" name="reason[email]" class="reason_check_boxes" id="reason_email" value="yes"> Email Change<br>
					<input type="checkbox" name="reason[location]" class="reason_check_boxes" id="reason_location" value="yes"> Location Change<br>
					<input type="checkbox" name="reason[interests]" class="reason_check_boxes" id="reason_interests" value="yes"> Interests Change<br>
					<input type="checkbox" name="reason[url]" class="reason_check_boxes" id="reason_url" value="yes"> URL Change<br>
				</div>
				<div class="new_field" id="new_name">
					<label for="enter_new_name">Enter New Name:</label>
					<input class="inpt" type="text" id="enter_new_name" name="new[name]">
				</div>
				<div class="new_field" id="new_email">
					<label for="enter_new_email">Enter New Email:</label>
					<input class="inpt" type="email" id="enter_new_email" name="new[email]">
				</div>
				<div class="new_field" id="new_location">
					<label for="enter_new_location">Enter New Location:</label>
					<input class="inpt" type="text" id="enter_new_location" name="new[location]">
				</div>
				<div class="new_field" id="new_interests">
					<label for="enter_new_interests">Enter New Interests:</label>
					<input class="inpt" type="text" id="enter_new_interests" name="new[interests]">
				</div>
				<div class="new_field" id="new_url">
					<label for="enter_new_url">Enter New URL:</label>
					<input class="inpt" type="url" id="enter_new_url" name="new[url]">
				</div>
				<input style="display:none" name="my_spam_bot" value="">
				<input class="fl" style="clear:both" type="submit" id="changes_submit_button" name="changes_submit_button" value="Send Changes to Ken Robinson">
			</form>
		</div>
		<div id="search_results"></div>
	</body>
</html>
