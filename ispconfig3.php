<?php
/*
Copyright (c) 2009, Scott Barr <gsbarr@gmail.com>
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

$PLUGIN_DIR='ispconfig3';
define('SM_PATH','../../');

require_once(SM_PATH . 'include/validate.php');
require_once(SM_PATH . 'functions/page_header.php');
require_once(SM_PATH . 'include/load_prefs.php');
require_once(SM_PATH . 'functions/i18n.php'); 
require_once(SM_PATH . 'functions/identity.php'); 

require_once('config.php');
require_once('functions.php');
require_once('ispc_remote.class.php');

// Re-write modules array and map to page ids
define('GENERAL_PAGE', 1);

$enabled_modules = array();
$module_count = 2;

foreach ($ispc_config['enable_modules'] as $module)
{
	define(strtoupper($module).'_PAGE', $module_count);
	$module_count++;
}

// Set up locale, for the error messages.
$prev = sq_change_text_domain('ispconfig3', SM_PATH . 'plugins/ispconfig3/locale');
textdomain ('ispconfig3');

sqgetGlobalVar('page', $page, SQ_GET);
$page = (int)$page;

if (empty($page)) 
{
	displayPageHeader($color, 'None'); //Header
	ispc_displayPage('index', array('url' => SM_PATH . 'plugins/ispconfig3/ispconfig3.php?page='.GENERAL_PAGE));
}
else
{
	switch ($page)
	{
		case PASSWORD_PAGE:
			if (!empty($_POST)) {
				ispc_savePassword();
			}
			
			ispc_getPasswordPage();
			break;
			
		case FETCHMAIL_PAGE:
			if (!empty($_POST)) {
				ispc_saveFetchmail();
			}
			
			sqgetGlobalVar('action', $action, SQ_GET);
			if (!empty($action) && $action == 'delete') {
				ispc_deleteFetchmail();
			}
			
			ispc_getFetchmailPage();
			break;
			
		case FORWARDING_PAGE:
			if (!empty($_POST)) {
				ispc_saveForwarding();
			}
			
			sqgetGlobalVar('action', $action, SQ_GET);
			if (!empty($action) && $action == 'delete') {
				ispc_deleteForwarding();
			}
			
			ispc_getForwardingPage();
			break;
			
		case AUTOREPLY_PAGE:
			if (!empty($_POST)) {
				ispc_saveAutoreply();
			}
			
			ispc_getAutoreplyPage(); 
			break;
			
		case MAILFILTER_PAGE:
			if (!empty($_POST)) {
				ispc_saveMailFilter();
			}
			
			sqgetGlobalVar('action', $action, SQ_GET);
			if (!empty($action) && $action == 'delete') {
				ispc_deleteMailFilter();
			}
			
			ispc_getMailFilterPage(); 
			break;
				
		case POLICY_PAGE:
			if (!empty($_POST)) {
				ispc_savePolicy();
			}
			
			ispc_getPolicyPage();
			break;
	
		case WBLIST_PAGE:
			if (!empty($_POST)) {
				ispc_saveWBList();
			}
			
			sqgetGlobalVar('action', $action, SQ_GET);
			if (!empty($action) && $action == 'delete') {
				ispc_deleteWBList();
			}
			
			ispc_getWBListPage();
			break;
			
		default:
			ispc_getGeneralPage();
	}
}
?>