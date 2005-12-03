<?php

/*
Copyright (c) 2005, Till Brehm, Falko Timme, projektfarm Gmbh
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

/**
* DNS validation
*
* @author Falko Timme <ft@falkotimme.com>
* @copyright Copyright &copy; 2005, Falko Timme
*/

class validate_dns {

function validate_field($field, $area, $zoneid, $wildcard_allowed = 1){
  //$desc: Name, Data, RP mbox, RP txtref, SRV target, Zone origin, Name server, Admin email
  global $app, $conf;

  switch ($area) {
  case "Name":
    $desc = $app->tform->wordbook['name_txt'];
    break;
  case "Data":
    $desc = $app->tform->wordbook['data_txt'];
    break;
  case "RP mbox":
    $desc = $app->tform->wordbook['rp_mbox_txt'];
    break;
  case "RP txtref":
    $desc = $app->tform->wordbook['rp_txtref_txt'];
    break;
  case "SRV target":
    $desc = $app->tform->wordbook['srv_target_txt'];
    break;
  case "Zone origin":
    $desc = $app->tform->wordbook['zone_origin_txt'];
    break;
  case "Name server":
    $desc = $app->tform->wordbook['ns_txt'];
    break;
  case "Admin email":
    $desc = $app->tform->wordbook['mbox_txt'];
    break;
  }

  $error = '';

  $valid_characters = "*ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890-_";

  if(strlen($field) > 255) $error .= $desc." ".$app->tform->wordbook['error_255_characters']."<br>\r\n";

  $parts = explode(".", $field);
  $i = 0;
  foreach ($parts as $part){
    $i++;

    if(strlen($part) > 63) $error .= $desc." ".$app->tform->wordbook['error_63_characters']."<br>\r\n";

    if(strspn($part, $valid_characters) != strlen($part)) $error .= $desc." ".$app->tform->wordbook['error_invalid_characters']."<br>\r\n";

    if(substr($part, 0, 1) == '-') $error .= $desc." ".$app->tform->wordbook['error_hyphen_begin']."<br>\r\n";
    if(substr($part, -1) == '-') $error .= $desc." ".$app->tform->wordbook['error_hyphen_end']."<br>\r\n";

    if(strstr($part, "*")){
      if($wildcard_allowed){
        if($i != 1) $error .= $desc." ".$app->tform->wordbook['error_wildcard_non_initial_part']."<br>\r\n";

        if($part != "*") $error .= $desc." ".$app->tform->wordbook['error_wildcard_mix']."<br>\r\n";
      } else {
        $error .= $desc." ".$app->tform->wordbook['error_no_wildcard_allowed']."<br>\r\n";
      }
    }
  }

  if(substr($field, -1) == '.' && $area == 'Name'){
    $soa = $app->db->queryOneRecord("SELECT * FROM soa WHERE id = ".$zoneid);
    if(substr($field, (strlen($field) - strlen($soa['origin']))) != $soa['origin']) $error .= $desc." ".$app->tform->wordbook['error_out_of_zone']."<br>\r\n";
  }

  return $error;
}

function validate_rp_data(&$data, $zoneid){
  global $app, $conf;
  $error = '';
  $fields = explode(" ", trim($data));
  if(count($fields) != 2) return $error .= $app->tform->wordbook['data_txt']." ".$app->tform->wordbook['error_invalid_rp']."<br>\r\n";
  $mbox = $fields[0];
  $txtref = $fields[1];

  $error .= $this->validate_field($mbox, 'RP mbox', $zoneid, 0);
  $error .= $this->validate_field($txtref, 'RP txtref', $zoneid, 0);

  $data = $mbox." ".$txtref;
  return $error;
}

function validate_srv_data(&$data, $zoneid){
  global $app, $conf;
  $error = '';

  $fields = explode(" ", trim($data));
  if(count($fields) != 3) return $error .= $app->tform->wordbook['data_txt']." ".$app->tform->wordbook['error_invalid_srv']."<br>\r\n";

  $weight = $fields[0];
  $port = $fields[1];
  $target = $fields[2];
  if($weight < 0 || $weight > 65535) $error .= $app->tform->wordbook['weight_txt']." (\"<i>" . htmlentities($weight)."</i>\") ".$app->tform->wordbook['error_srv_out_of_range']."<br>\r\n";
  if ($port < 0 || $port > 65535) $error .= $app->tform->wordbook['port_txt']." (\"<i>".htmlentities($port)."</i>\") ".$app->tform->wordbook['error_srv_out_of_range']."<br>\r\n";

  $error .= $this->validate_field($target, "SRV target", $zoneid, 0);

  $data = (int)$weight." ".(int)$port." ".$target;
  return $error;
}

function is_integer($value, $fieldname, $zero_allowed = 0){
  global $app, $conf;

  $error = '';

  if(intval($value) != $value || !is_numeric($value)) $error .= $fieldname." ".$app->tform->wordbook['error_must_be_integer']."<br>\r\n";
  if($value > 2147483647) $error .= $fieldname." ".$app->tform->wordbook['error_must_not_be_greater_than_2147483647']."<br>\r\n";
  if(!$zero_allowed){
    if($value <= 0) $error .= $fieldname." ".$app->tform->wordbook['error_must_be_positive']."<br>\r\n";
  } else {
    if($value < 0) $error .= $fieldname." ".$app->tform->wordbook['error_must_not_be_negative']."<br>\r\n";
  }

  return $error;
}

function validate_rr(&$rr){
  global $app, $conf;

  $error = '';

  $tmp_rr = $rr;
  foreach($tmp_rr as $key => $val){
    $rr[$key] = trim($val);
  }
  unset($tmp_rr);

  $error .= $this->validate_field($rr['name'], 'Name', $rr['zone'], 1);

  switch ($rr['type']) {
  case "A":
    $ip_parts = explode(".", $rr['data']);
    if(count($ip_parts) != 4){
      $error .= $app->tform->wordbook['data_txt']." ".$app->tform->wordbook['error_a']."<br>\r\n";
    } else {
      for($n = 0; $n < 4; $n++){
        $q = $ip_parts[$n];
        if(!is_numeric($q) || (int)$q < 0 || (int)$q > 255) $error .= $app->tform->wordbook['data_txt']." ".$app->tform->wordbook['error_a']."<br>\r\n";
      }
    }
    $rr['data'] = (int)$ip_parts[0].".".(int)$ip_parts[1].".".(int)$ip_parts[2].".".(int)$ip_parts[3];
    break;
  case "AAAA":
    $valid_chars = "ABCDEFabcdef1234567890:";

    if(strspn($rr['data'], $valid_chars) != strlen($rr['data'])) $error .= $app->tform->wordbook['data_txt']." ".$app->tform->wordbook['error_aaaa']."<br>\r\n";
    break;
  case "ALIAS":
    $error .= $this->validate_field($rr['data'], 'Data', $rr['zone'], 0);
    break;
  case "CNAME":
    $error .= $this->validate_field($rr['data'], 'Data', $rr['zone'], 0);
    break;
  case "HINFO":
    if(!strchr($rr['data'], ' ')) $error .= $app->tform->wordbook['data_txt']." ".$app->tform->wordbook['error_hinfo']."<br>\r\n";
    break;
  case "MX":
    $error .= $this->validate_field($rr['data'], 'Data', $rr['zone'], 0);
    break;
  case "NS":
    $error .= $this->validate_field($rr['data'], 'Data', $rr['zone'], 0);
    break;
  case "PTR":
    $error .= $this->validate_field($rr['data'], 'Data', $rr['zone'], 0);
    if(substr($rr['data'], -1) != '.') $error .= $app->tform->wordbook['data_txt']." ".$app->tform->wordbook['error_ptr']."<br>\r\n";
    break;
  case "RP":
    $error .= $this->validate_rp_data($rr['data'], $rr['zone']);
    break;
  case "SRV":
    $error .= $this->validate_srv_data($rr['data'], $rr['zone']);
    break;
  case "TXT":
    break;
  }

  $error .= $this->is_integer($rr['aux'], $app->tform->wordbook['aux_txt'], 1);

  $error .= $this->is_integer($rr['ttl'], $app->tform->wordbook['ttl_txt']);


  return $error;
}

function validate_soa(&$soa){
  global $app, $conf;

  $error = '';

  $tmp_soa = $soa;
  foreach($tmp_soa as $key => $val){
    if($key != 'active') $soa[$key] = trim($val);
  }
  unset($tmp_soa);

  if($soa['origin'] == '') $error .= $app->tform->wordbook['origin_txt']." ".$app->tform->wordbook['error_empty']."<br>\r\n";
  if(substr($soa['origin'], -1) != '.') $error .= $app->tform->wordbook['origin_txt']." ".$app->tform->wordbook['error_dot']."<br>\r\n";
  $error .= $this->validate_field($soa['origin'], "Zone origin", $soa['id'], 0);

  $error .= $this->is_integer($soa['ttl'], $app->tform->wordbook['ttl_txt']);

  if($soa['ns'] == '') $error .= $app->tform->wordbook['ns_txt']." ".$app->tform->wordbook['error_empty']."<br>\r\n";
  $error .= $this->validate_field($soa['ns'], "Name server", $soa['id'], 0);

  if($soa['mbox'] == '') $error .= $app->tform->wordbook['mbox_txt']." ".$app->tform->wordbook['error_empty']."<br>\r\n";
  $error .= $this->validate_field($soa['mbox'], "Admin email", $soa['id'], 0);

  $error .= $this->is_integer($soa['refresh'], $app->tform->wordbook['refresh_txt']);

  $error .= $this->is_integer($soa['retry'], $app->tform->wordbook['retry_txt']);

  $error .= $this->is_integer($soa['expire'], $app->tform->wordbook['expire_txt']);

  $error .= $this->is_integer($soa['minimum'], $app->tform->wordbook['minimum_txt']);

  return $error;
}

}