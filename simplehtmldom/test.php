<?php
include('simple_html_dom.php');
$p_ary = array('name','location','email','link','interests','url');
$html = file_get_html('http://rbnsn.com/weavers/index.php');
$parse = array();
$i = 0;
$table = $html->find('#fixedtable',0);
foreach($table->find('tr') as $row) {
	if ($i > 1) {
		foreach($p_ary as $n => $fld) {
			switch ($fld) {
				case 'name':
				case 'location':
				case 'email':
				case 'interests':
					$parse[$i][$fld] = trim(str_replace(array('</font>','"'),array('',''),$row->find('td',$n)->plaintext));
					break;
				case 'link':
					$a = $row->find('a',0);//->find('a');
					$parse[$i][$fld] = ($a->href)?str_replace('mailto:','',$a->href):'';
					break;
				case 'url':
					$a = $row->find('a',1);//->find('a');
					if (stripos($a->href,'mailto:') !== false) {
						$parse[$i]['link'] .= "\n" . str_replace('mailto:','',$a->href);
						$a = $row->find('a',2);
					}
					$parse[$i][$fld] = ($a->href)?$a->href:'';
					break;
			}
		}
	}
	$i++;
}
print_r($parse);
?>
