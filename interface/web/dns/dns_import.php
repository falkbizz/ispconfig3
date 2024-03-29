<?php

/*
Copyright (c) 2008, Till Brehm, projektfarm Gmbh
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('dns');


// Loading the template
$app->uses('tpl,validate_dns');
$app->tpl->newTemplate("form.tpl.htm");
$app->tpl->setInclude('content_tpl','templates/dns_import.htm');
$app->load_language_file('/web/dns/lib/lang/'.$_SESSION['s']['language'].'_dns_wizard.lng');

// import variables
$template_id = (isset($_POST['template_id']))?intval($_POST['template_id']):0;
$sys_groupid = (isset($_POST['client_group_id']))?intval($_POST['client_group_id']):0;
$domain = (isset($_POST['domain'])&&!empty($_POST['domain']))?$_POST['domain']:NULL;

// get the correct server_id
if($_SESSION['s']['user']['typ'] == 'admin') {
	$server_id = (isset($_POST['server_id']))?intval($_POST['server_id']):1;
} else {
	$client_group_id = $_SESSION["s"]["user"]["default_group"];
	$client = $app->db->queryOneRecord("SELECT default_dnsserver FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");
	$server_id = $client["default_dnsserver"];
}


// Load the templates
$records = $app->db->queryAllRecords("SELECT * FROM dns_template WHERE visible = 'Y'");
$template_id_option = '';
$n = 0;
foreach($records as $rec){
	$checked = ($rec['template_id'] == $template_id)?' SELECTED':'';
	$template_id_option .= '<option value="'.$rec['template_id'].'"'.$checked.'>'.$rec['name'].'</option>';
	if($n == 0 && $template_id == 0) $template_id = $rec['template_id'];
	$n++;
}
unset($n);
$app->tpl->setVar("template_id_option",$template_id_option);

// If the user is administrator
if($_SESSION['s']['user']['typ'] == 'admin') {
	
	// Load the list of servers
	$records = $app->db->queryAllRecords("SELECT server_id, server_name FROM server WHERE mirror_server_id = 0 AND dns_server = 1 ORDER BY server_name");
	$server_id_option = '';
	foreach($records as $rec){
		$checked = ($rec['server_id'] == $server_id)?' SELECTED':'';
		$server_id_option .= '<option value="'.$rec['server_id'].'"'.$checked.'>'.$rec['server_name'].'</option>';
	}
	$app->tpl->setVar("server_id",$server_id_option);
	
	// load the list of clients
	$sql = "SELECT groupid, name FROM sys_group WHERE client_id > 0 ORDER BY name";
	$clients = $app->db->queryAllRecords($sql);
	$client_select = '';
	if($_SESSION["s"]["user"]["typ"] == 'admin') $client_select .= "<option value='0'></option>";
	if(is_array($clients)) {
		foreach( $clients as $client) {
			$selected = ($client["groupid"] == $sys_groupid)?'SELECTED':'';
			$client_select .= "<option value='$client[groupid]' $selected>$client[name]</option>\r\n";
		}
	}

	$app->tpl->setVar("client_group_id",$client_select);
}

if ($_SESSION["s"]["user"]["typ"] != 'admin' && $app->auth->has_clients($_SESSION['s']['user']['userid'])) {
	
	// Get the limits of the client
	$client_group_id = $_SESSION["s"]["user"]["default_group"];
	$client = $app->db->queryOneRecord("SELECT client.client_id, contact_name FROM sys_group, client WHERE sys_group.client_id = client.client_id and sys_group.groupid = $client_group_id");

	
	// load the list of clients
	$sql = "SELECT groupid, name FROM sys_group, client WHERE sys_group.client_id = client.client_id AND client.parent_client_id = ".$client['client_id'];
	$clients = $app->db->queryAllRecords($sql);
	$tmp = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = ".$client['client_id']);
	$client_select = '<option value="'.$tmp['groupid'].'">'.$client['contact_name'].'</option>';
	if(is_array($clients)) {
		foreach( $clients as $client) {
			$selected = ($client["groupid"] == $sys_groupid)?'SELECTED':'';
			$client_select .= "<option value='$client[groupid]' $selected>$client[name]</option>\r\n";
		}
	}

	$app->tpl->setVar("client_group_id",$client_select);
}

// Import the zone-file
//if(1=="1")
if(isset($_FILES['file']['name']) && is_uploaded_file($_FILES['file']['tmp_name']))
{
	$sql = "SELECT server_name FROM `server` WHERE server_id=".intval($server_id)." OR mirror_server_id=".intval($server_id)." ORDER BY server_name ASC";
	$servers = $app->db->queryAllRecords($sql);
	for ($i=0;$i<count($servers);$i++)
	{
		if (substr($servers[$i]['server_name'],strlen($servers[$i]['server_name'])-1) != ".")
		{
			$servers[$i]['server_name'] .= ".";
		}
	}
	$lines = file($_FILES['file']['tmp_name']);
	//$lines = file("apriqot.se.txt");
	$name = str_replace("txt","",$_FILES['file']['name']);
	$name = str_replace("zone","",$name);
	if ($domain !== NULL)
	{
		$name = $domain;
		if (substr($name,strlen($name)-1) != ".")
		{
			$name .= ".";
		}
	}
	$i = 0;
	$r = 0;
	$s = 0;
	$dns_rr = array();
	foreach ($lines as $line)
	{
		if (substr($line,0,1)!=';'&&substr($line,0,1)!='$')
		{
			$line = str_replace("\n",NULL,$line);
			$i++;

			// TODO - Find a better way to parse the SOA record. Lazy checking.
			if ($i <= 7)
			{
				if ($i > 1)
				{
					$s++;
					$line = str_replace("\t",NULL,$line);
					if (!empty($line))
					{
						print(strpos(";",$line));
						$line = substr($line,0,strpos($line,";"));
						if ($s == 1)
							$soa['serial'] = $line;
						else if ($s == 2)
							$soa['refresh'] = $line;
						else if ($s == 3)
							$soa['retry'] = $line;
						else if ($s == 4)
							$soa['expire'] = $line;
						else if ($s == 5)
							$soa['ttl'] = $line;

					}
				}
				else
				{
					$line = str_replace("\t",",",$line);
					$recs = explode(",",$line);

					foreach ($recs as $key => $rec)
					{
						$rec = trim($rec);
						//name	type	data	aux	ttl	active
						if ($key == 0)
						{
							if ($rec == '@' || $rec == "")
							{
								$rec = $name;
							}

							$soa['name'] = $rec;
						}

						if ($key != 0 && strtolower($rec) == 'soa')
						{
							$typekeys[$s] = $key;
						}
						else if ($key > $typekey[$r])
						{
							if ($rec != ""&&$rec != "(")
							{
								$rec = explode(" ",$rec);

								$soa['ns'] = $servers[0]['server_name'];
								$soa['mbox'] = $rec[1];
							}
						}
					}
				}
			}
			else
			{
				$line = str_replace("\n","",trim($line));

				if (!empty($line))
				{

					preg_match_all('/(.*?)\s*IN\s*(A|CNAME|MX|TXT|NS|AAAA)\s*(.*)/',$line, $recs);

					if ($recs[1][0] == '@' || trim($recs[1][0]) == "")
					{
						$recs[1][0] = $name;
					}
					$dns_rr[$r]['name'] = $recs[1][0];
					$dns_rr[$r]['type'] = $recs[2][0];
					if (strtolower($dns_rr[$r]['type'])=='mx')
					{
						$recs[3][0] = str_replace(" ","\t",$recs[3][0]);
						$mx[$r] = explode("\t",$recs[3][0]);
						for ($m=1;$m<count($mx[$r]);$m++)
						{
							if (!empty($mx[$r][$m]))
								$dns_rr[$r]['data'] = $mx[$r][$m];
						}
						
						$dns_rr[$r]['aux'] = $mx[$r][0];
					}
					else if (strtolower($dns_rr[$r]['type'])=='txt')
					{
						$dns_rr[$r]['data'] = substr($recs[3][0],1,(strlen($recs[3][0])-2));
					}
					else
					{
						$dns_rr[$r]['data'] = $recs[3][0];
					}

					if (strtolower($dns_rr[$r]['type'])=='ns' && strtolower($dns_rr[$r]['name'])==$name)
					{
						unset($dns_rr[$r]);
					}

					$r++;
				}
			}

		}
	}

	foreach ($servers as $server)
	{
		$dns_rr[$r]['name']=$name;
		$dns_rr[$r]['type']='NS';
		$dns_rr[$r]['data'] = $server['server_name'];
		$dns_rr[$r]['aux'] = 0;

		$r++;
	}
					print('<pre>');
					print_r($dns_rr);
					print('</pre>');
	// Insert the soa record
	$sys_userid = $_SESSION['s']['user']['userid'];
	$origin = $app->db->quote($soa['name']);
	$ns = $app->db->quote($soa['ns']);
	$mbox = $app->db->quote($soa['mbox']);
	$refresh = $app->db->quote($soa['refresh']);
	$retry = $app->db->quote($soa['retry']);
	$expire = $app->db->quote($soa['expire']);
	$minimum = $app->db->quote($soa['ttl']);
	$ttl = $app->db->quote($soa['ttl']);
	$xfer = $app->db->quote('');
	$serial = $app->db->quote(intval($soa['serial'])+1);
	
	$insert_data = "(`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `origin`, `ns`, `mbox`, `serial`, `refresh`, `retry`, `expire`, `minimum`, `ttl`, `active`, `xfer`) VALUES
	('$sys_userid', '$sys_groupid', 'riud', 'riud', '', '$server_id', '$origin', '$ns', '$mbox', '$serial', '$refresh', '$retry', '$expire', '$minimum', '$ttl', 'Y', '$xfer')";
	$dns_soa_id = $app->db->datalogInsert('dns_soa', $insert_data, 'id');
	
	// Insert the dns_rr records
	if(is_array($dns_rr) && $dns_soa_id > 0)
	{
		foreach($dns_rr as $rr)
		{
			$insert_data = "(`sys_userid`, `sys_groupid`, `sys_perm_user`, `sys_perm_group`, `sys_perm_other`, `server_id`, `zone`, `name`, `type`, `data`, `aux`, `ttl`, `active`) VALUES
			('$sys_userid', '$sys_groupid', 'riud', 'riud', '', '$server_id', '$dns_soa_id', '$rr[name]', '$rr[type]', '$rr[data]', '$rr[aux]', '$rr[ttl]', 'Y')";
			$dns_rr_id = $app->db->datalogInsert('dns_rr', $insert_data, 'id');
		}
	}
	
	header('Location: /dns/dns_soa_edit.php?id='.$dns_soa_id);
}

$app->tpl->setVar('msg',$msg);
$app->tpl->setVar('error',$error);

$app->tpl->setVar("title",'Import Zone Files');

$lng_file = 'lib/lang/'.$_SESSION['s']['language'].'_dns_wizard.lng';
include($lng_file);
$app->tpl->setVar($wb);

$app->tpl_defaults();
$app->tpl->pparse();


?>