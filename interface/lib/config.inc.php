<?php
/*
Copyright (c) 2008, Till Brehm, Falko Timme, projektfarm Gmbh
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

//** Web-only
if( !empty($_SERVER['DOCUMENT_ROOT']) ) {

	Header("Pragma: no-cache");
	Header("Cache-Control: no-store, no-cache, max-age=0, must-revalidate");
	Header("Content-Type: text/html");

	ini_set('register_globals',0);
}


//** Key paramaters
$conf['app_title']			= 'ISPConfig';
$conf['app_version']		= '3.0.7';
$conf['modules_available']	= 'admin,mail,sites,monitor,client,dns';

define('ISPC_APP_TITLE',	'ISPConfig');
define('ISPC_APP_VERSION',	'3.0.7');
define('ISPC_INTERFACE_MODULES_ENABLED', 'mail,sites,dns,tools');


//** The main ROOT is the parent directory to this file, ie Interface/. NO trailing slashes.
define('ISPC_ROOT_PATH',	realpath(dirname(__FILE__).'/../'));
define('ISPC_LIB_PATH',		ISPC_ROOT_PATH.'/lib');
define('ISPC_CLASS_PATH',	ISPC_ROOT_PATH.'/lib/classes');
define('ISPC_WEB_PATH',		ISPC_ROOT_PATH.'/web');
define('ISPC_THEMES_PATH',	ISPC_ROOT_PATH.'/web/themes');
define('ISPC_WEB_TEMP_PATH', ISPC_WEB_PATH.'/temp'); // Path for downloads, accessible via browser
define('ISPC_CACHE_PATH',	ISPC_ROOT_PATH.'/cache');


//**Server variables
$conf["server_id"] = "{server_id}";


//** Database Settings
$conf["db_type"]			= 'mysql';
$conf["db_host"]			= 'localhost';
$conf["db_database"]		= 'dbispconfig';
$conf["db_user"]			= 'ispconfig';
$conf["db_password"]		= 'e8605b27a4360d135f62129627d8d9ad';

define("DB_TYPE",		$conf["db_type"]);
define("DB_HOST",		$conf["db_host"]);
define("DB_DATABASE",	$conf["db_database"]);
define("DB_USER",		$conf["db_user"]);
define("DB_PASSWORD",	$conf["db_password"]);


//** Path Settings (Do not change!)
$conf["rootpath"]			= substr(dirname(__FILE__),0,-4);
$conf["fs_div"]				= "/"; // File system divider, \\ on windows and / on linux and unix
$conf["classpath"]			= $conf["rootpath"].$conf["fs_div"]."lib".$conf["fs_div"]."classes";
$conf["temppath"]			= $conf["rootpath"].$conf["fs_div"]."temp";

define("DIR_TRENNER",		$conf["fs_div"]);
define("SERVER_ROOT",		$conf["rootpath"]);
define("INCLUDE_ROOT",		SERVER_ROOT.DIR_TRENNER."lib");
define("CLASSES_ROOT",		INCLUDE_ROOT.DIR_TRENNER."classes");


//** Logging
$conf["log_file"]		= '/var/log/ispconfig/ispconfig.log';
$conf["log_priority"]	= 2; // 0 = Debug, 1 = Warning, 2 = Error


//** Allow software package installations
$conf['software_updates_enabled'] = false;


//** Themes
$conf["theme"]					= 'default';
$conf["html_content_encoding"]	= 'text/html; charset=iso-8859-1';
$conf["logo"] 					= 'themes/default/images/mydnsconfig_logo.gif';


//** Default Language
$conf["language"]				= 'en';


//** Misc.
$conf["interface_logout_url"]	= ""; // example: http://www.domain.tld/


//** Auto Load Modules
$conf["start_db"]		= true;
$conf["start_session"]	= true;

//** Constants
define("LOGLEVEL_DEBUG", 0);
define("LOGLEVEL_WARN",  1);
define("LOGLEVEL_ERROR", 2);
?>
