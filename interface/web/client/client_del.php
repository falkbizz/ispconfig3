<?php

/*
Copyright (c) 2005, Till Brehm, projektfarm Gmbh
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

/******************************************
* Begin Form configuration
******************************************/

$list_def_file = "list/client.list.php";
$tform_def_file = "form/client.tform.php";

/******************************************
* End Form configuration
******************************************/

require_once('../../lib/config.inc.php');
require_once('../../lib/app.inc.php');

//* Check permissions for module
$app->auth->check_module_permissions('client');
if($conf['demo_mode'] == true) $app->error('This function is disabled in demo mode.');

$app->uses('tpl,tform');
$app->load('tform_actions');

class page_action extends tform_actions {
	
	function onDelete() {
		global $app, $conf,$list_def_file,$tform_def_file;
		
		// Loading tform framework
        if(!is_object($app->tform)) $app->uses('tform');
		
		if($_POST["confirm"] == 'yes') {
			parent::onDelete();
		} else {
		
		$app->uses('tpl');
		$app->tpl->newTemplate("form.tpl.htm");
		$app->tpl->setInclude('content_tpl', 'templates/client_del.htm');
		
		include_once($list_def_file);
		
		// Load table definition from file
        $app->tform->loadFormDef($tform_def_file);
		
		$this->id = intval($_REQUEST["id"]);
		
		$this->dataRecord = $app->tform->getDataRecord($this->id);
		$client_id = intval($this->dataRecord['client_id']);

        
		//$parent_client_id = intval($this->dataRecord['parent_client_id']);
		//$parent_user = $app->db->queryOneRecord("SELECT userid FROM sys_user WHERE client_id = $parent_client_id");
		$client_group = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = $client_id");
		
		// Get all records (sub-clients, mail, web, etc....)  of this client.
		$tables = 'cron,client,dns_rr,dns_soa,dns_slave,ftp_user,mail_access,mail_content_filter,mail_domain,mail_forwarding,mail_get,mail_user,mail_user_filter,shell_user,spamfilter_users,support_message,web_database,web_domain,web_traffic';
		$tables_array = explode(',',$tables);
		$client_group_id = intval($client_group['groupid']);
		
		$table_list = array();
		if($client_group_id > 1) {
			foreach($tables_array as $table) {
				if($table != '') {
					$records = $app->db->queryAllRecords("SELECT * FROM $table WHERE sys_groupid = ".$client_group_id);
					$number = count($records);
					if($number > 0) $table_list[] = array('table' => $table."(".$number.")");
				}
			}
		}
		
		$app->tpl->setVar('id',$this->id);
		$app->tpl->setLoop('records', $table_list);
		
		//* load language file 
		$lng_file = 'lib/lang/'.$_SESSION['s']['language'].'_client_del.lng';
		include($lng_file);
		$app->tpl->setVar($wb);
		
		$app->tpl_defaults();
		$app->tpl->pparse();
		}
	}
	
	
	
	
	function onAfterDelete() {
		global $app, $conf;
		
		$client_id = intval($this->dataRecord['client_id']);
		
		if($client_id > 0) {			
			// remove the group of the client from the resellers group
			$parent_client_id = intval($this->dataRecord['parent_client_id']);
			$parent_user = $app->db->queryOneRecord("SELECT userid FROM sys_user WHERE client_id = $parent_client_id");
			$client_group = $app->db->queryOneRecord("SELECT groupid FROM sys_group WHERE client_id = $client_id");
			$app->auth->remove_group_from_user($parent_user['userid'],$client_group['groupid']);
			
			// delete the group of the client
			$app->db->query("DELETE FROM sys_group WHERE client_id = $client_id");
			
			// delete the sys user(s) of the client
			$app->db->query("DELETE FROM sys_user WHERE client_id = $client_id");
			
			// Delete all records (sub-clients, mail, web, etc....)  of this client.
			$tables = 'client,dns_rr,dns_soa,dns_slave,ftp_user,mail_access,mail_content_filter,mail_domain,mail_forwarding,mail_get,mail_user,mail_user_filter,shell_user,spamfilter_users,support_message,web_database,web_domain,web_traffic,web_folder,web_folder_user';
			$tables_array = explode(',',$tables);
			$client_group_id = intval($client_group['groupid']);
			if($client_group_id > 1) {
				foreach($tables_array as $table) {
					if($table != '') {
						$records = $app->db->queryAllRecords("SELECT * FROM $table WHERE sys_groupid = ".$client_group_id);
						// find the primary ID of the table
						$table_info = $app->db->tableInfo($table);
						$index_field = '';
						foreach($table_info as $tmp) {
							if($tmp['option'] == 'primary') $index_field = $tmp['name'];
						}
						// Delete the records
						if($index_field != '') {
							if(is_array($records)) {
								foreach($records as $rec) {
									$app->db->datalogDelete($table, $index_field, $rec[$index_field]);
								}
							}
						}
						
					}
				}
			}
			
			
			
		}
		
	}
}

$page = new page_action;
$page->onDelete()

?>
