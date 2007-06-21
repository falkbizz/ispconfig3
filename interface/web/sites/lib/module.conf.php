<?php

$module["name"] 		= "sites";
$module["title"] 		= "Sites";
$module["template"] 	= "module.tpl.htm";
$module["startpage"] 	= "sites/web_domain_list.php";
$module["tab_width"]    = '';

/*
	Websites menu
*/

$items[] = array( 'title' 	=> "Domain",
				  'target' 	=> 'content',
				  'link'	=> 'sites/web_domain_list.php');


$items[] = array( 'title' 	=> "Subdomain",
				  'target' 	=> 'content',
				  'link'	=> 'sites/web_subdomain_list.php');


$items[] = array( 'title' 	=> "Aliasdomain",
				  'target' 	=> 'content',
				  'link'	=> 'sites/web_aliasdomain_list.php');

$module["nav"][] = array(	'title'	=> 'Websites',
							'open' 	=> 1,
							'items'	=> $items);

// aufr�umen
unset($items);

/*
	FTP User menu
*/

$items[] = array( 'title' 	=> "FTP-User",
				  'target' 	=> 'content',
				  'link'	=> 'sites/ftp_user_list.php');


$module["nav"][] = array(	'title'	=> 'FTP',
							'open' 	=> 1,
							'items'	=> $items);

// aufr�umen
unset($items);


?>