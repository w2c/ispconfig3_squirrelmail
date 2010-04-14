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

function section_error($tMsg) {
	return sqsession_register($tMsg, 'section_err');
}

function section_message($tMsg) {
	return sqsession_register($tMsg, 'section_msg');
}

function ispc_displayPage($tName, $tVars=array()) 
{ 
      global $color, $theme_css, $username, $imapConnection;
		
      $base_url = SM_PATH . 'plugins/ispconfig3/ispconfig3.php';
      
      if (file_exists("templates/$tName.phtml"))
      {
      	if (count($tVars)) {
      		extract($tVars);
	    }
      	include_once("templates/$tName.phtml");
      }
}

function ispc_getGeneralPage()
{
	global $username, $imapServerAddress;
	
	$_ispc_remote = new ispc_remote();
	$_result = $_ispc_remote->grud_record('get','alias', array('destination' => $username, 'active' => 'y'));
		
	$aliases = array();
	if (is_array($_result)) {
		foreach ($_result as $_res) {
			$aliases[] = $_res['destination'];
		}
	}
	
	$prev = sq_change_text_domain('squirrelmail');
	$_mail = _("E-mail");
	
	sq_change_text_domain('ispconfig3', SM_PATH . 'plugins/ispconfig3/locale');
	
	$ident = array_shift(get_identities());
	
	ispc_displayPage('general', array('mail' => $_mail, 
									  'ident' => $ident, 
									  'server_address' => $imapServerAddress,
									  'aliases' => $aliases));
}

function ispc_getPasswordPage()
{
	global $rcmail_config;
	
	$prev = sq_change_text_domain('squirrelmail');
	$_save = _("Save");
	
	sq_change_text_domain('ispconfig3', SM_PATH . 'plugins/ispconfig3/locale');
	
	$pwl = (isset($rcmail_config['password_min_length']) && is_numeric($rcmail_config['password_min_length'])) ? $rcmail_config['password_min_length'] : 999;
	$confirm = (isset($rcmail_config['password_confirm_current']) && is_bool($rcmail_config['password_confirm_current'])) ? $rcmail_config['password_confirm_current'] : true;
	
	ispc_displayPage('password', array('save' => $_save, 'pwl' => $pwl, 'confirm' => $confirm));
}

function ispc_savePassword()
{
	global $username, $rcmail_config;
	
	sqgetGlobalVar('_newpasswd', $new_password, SQ_POST);
	if (empty($new_password)) {
		section_error(_("nopassword"));
		return false;
	}
	
	sqgetGlobalVar('_confpasswd', $confirm_password, SQ_POST);
	if (empty($confirm_password)) {
		section_error(_("nopassword"));
		return false;
	}
	
	if ($confirm_password != $new_password) {
		section_error(_("passwordinconsistency"));
		return false;
	}
	
	$pwl = (isset($rcmail_config['password_min_length']) && is_numeric($rcmail_config['password_min_length'])) ? $rcmail_config['password_min_length'] : 999;
	if (strlen($new_password) < $pwl) {
		section_error(sprintf(_('passwordminlength'), $pwl));
		return false;
	}
	
	$confirm = (isset($rcmail_config['password_confirm_current']) && is_bool($rcmail_config['password_confirm_current'])) ? $rcmail_config['password_confirm_current'] : true;
	if ($confirm)
	{
		sqgetGlobalVar('_curpasswd', $cur_password, SQ_POST);
		if (empty($cur_password)) {
			section_error(_("nocurpassword"));
			return false;
		}
		
		$current_pass = sqauth_read_password();
		if ($current_pass != $cur_password) {
			section_error(_("passwordincorrect"));
			return false;
		}
		
		if ($current_pass == $new_password) {
			return false;
		}
	}
	
	$_ispc_remote = new ispc_remote();
	$res = $_ispc_remote->grud_record('get','user', array('email' => $username), true);
	$params = array_shift($res);
	
	$params['id'] = $params['mailuser_id'];
	unset($params['mailuser_id']);
	
	$params['password'] = $new_password;
	$_ispc_remote->grud_record('update','user', $params);
	
	sqauth_save_password($new_password);
	section_message(_("Successfully Saved Options"));	
}

function ispc_getFetchmailPage()
{
	global $username;
	
	$prev = sq_change_text_domain('squirrelmail');
	$_save = _("Save");
	
	sq_change_text_domain('ispconfig3', SM_PATH . 'plugins/ispconfig3/locale');
	
	$_ispc_remote = new ispc_remote();
	
	// Get available fetchmail entries
	$res = $_ispc_remote->grud_record('get','fetchmail', array('destination' => $username));
	$_vars = array('save' => $_save, 'entries' => $res);
	
	// Fetch data for entry for editing
	sqgetGlobalVar('_id', $id, SQ_GET);
	if (!empty($id) && is_numeric($id)) {
		$form = $_ispc_remote->grud_record('get','fetchmail', $id);
	}
	
	if (is_array($form) && count($form)) 
	{
		$_vars['fetchmaildelete']  = ($form['source_delete'] == 'y') ? 'checked="checked"': '';
		$_vars['fetchmailenabled'] = ($form['active'] == 'y') ? 'checked="checked"': '';
		
		$_vars = array_merge($_vars, $form);
	}
	
	ispc_displayPage('fetchmail', $_vars);
}

function ispc_saveFetchmail()
{
	global $username, $rcmail_config;
	
	sqgetGlobalVar('_id', $id, SQ_POST);
	sqgetGlobalVar('_serverid', $serverid, SQ_POST);
	
    sqgetGlobalVar('_fetchmailtyp', $typ, SQ_POST);
	sqgetGlobalVar('_fetchmailserver', $server, SQ_POST);
	if (empty($server)) {
		section_error('textempty');
		return false;
	}
	sqgetGlobalVar('_fetchmailuser', $user, SQ_POST);
	if (empty($user)) {
		section_error('textempty');
		return false;
	}
	sqgetGlobalVar('_fetchmailpass', $pass, SQ_POST);
	if (empty($pass)) {
		section_error('textempty');
		return false;
	}
	sqgetGlobalVar('_fetchmaildelete', $delete, SQ_POST);
	sqgetGlobalVar('_fetchmailenabled', $enabled, SQ_POST);
	
	$delete = (!$delete) ? 'n' : 'y';
	$enabled = (!$enabled) ? 'n' : 'y';
	
	$_ispc_remote = new ispc_remote();
	
	if (empty($id)) // Adding entry 
	{ 
		$res = $_ispc_remote->grud_record('get','user', array('email' => $username), true);
		$_result = $_ispc_remote->grud_record('get','fetchmail', array('destination' => $username));
					
		$limit = (isset($rcmail_config['fetchmail_limit']) && is_numeric($rcmail_config['fetchmail_limit'])) ? $rcmail_config['fetchmail_limit'] : 999;
		if(count($_result) >= $limit) {
			section_error(_('fetchmaillimitreached'));
			return false;
		}
		
		$params = array('sys_userid' => $res[0]['sys_userid'],
						'server_id' => $res[0]['server_id'],
						'type' => $typ,
						'source_server' => $server,
						'source_username' => $user,
						'source_password' => $pass,							
						'source_delete' => $delete,
						'destination' => $username,
						'active' => $enabled);

		$_ispc_remote->grud_record('add','fetchmail', $params);
		section_message(_("Successfully Saved Options"));
	}
	else 
	{
		/**
		 * @todo Not using our remoting class as the param order of the mail_fetchmail_update function differs from other update calls.
		 * When this is changed upstream revert this section to using the remoting class.
		 */
		$client = new SoapClient(null, array('location' => $rcmail_config['soap_url'].'index.php',
											 'uri'      => $rcmail_config['soap_url']));
		
		try
		{
			$session_id = $client->login($rcmail_config['remote_soap_user'],$rcmail_config['remote_soap_pass']);
			$res = $client->mail_fetchmail_get($session_id, $id);

			if ($res['destination'] == $username) 
			{
				$params = array('sys_userid' => $res['sys_userid'],
								'id' => $id,
								'server_id' => $serverid,
								'type' => $typ,
								'source_server' => $server,
								'source_username' => $user,
								'source_password' => $pass,							
								'source_delete' => $delete,
								'destination' => $username,
								'active' => $enabled);
				
				$client_id = $client->client_get_id($session_id, $res['sys_userid']);
				$add = $client->mail_fetchmail_update($session_id, $id, $client_id, $params);
				
				section_message(_("Successfully Saved Options"));
				$client->logout($session_id);
			} 
			else {
				section_error(_('opnotpermitted'));
			}
		}
		catch (SoapFault $e) {
			section_error('Soap Error: '.$e->getMessage());
		}
	}
}

function ispc_deleteFetchmail()
{	
	global $username;
	
	sqgetGlobalVar('_id', $id, SQ_GET);
		
	if (!empty($id)) 
	{
		$_ispc_remote = new ispc_remote();
		$res = $_ispc_remote->grud_record('get','fetchmail', $id);
		
		if ($res['destination'] == $username) 
		{
			$_ispc_remote->grud_record('delete','fetchmail', $id);
			section_message(_('Deleted'));
		}
	}
}

function ispc_getForwardingPage()
{
	global $username;
	
	$prev = sq_change_text_domain('squirrelmail');
	$_vars = array('save' => _("Save"), 'delete' => _("delete"));
	
	sq_change_text_domain('ispconfig3', SM_PATH . 'plugins/ispconfig3/locale');
	
	$_ispc_remote = new ispc_remote();
	$res = $_ispc_remote->grud_record('get','user', array('email' => $username));
	$filters = $res[0]['custom_mailfilter'];
	
	if (!empty($filters) && preg_match('/^cc "!([a-z0-9][a-z0-9-.+_]*@[a-z0-9]([a-z0-9-][.]?)*[a-z0-9]\.[a-z]{2,5})[^"]*"$/m', $filters, $cc)) {
		$_vars['destination'] = $cc[1];
	}
	
	ispc_displayPage('forwarding', $_vars);
}

function ispc_saveForwarding()
{
	global $username;
	
	sqgetGlobalVar('_forwardingaddress', $address, SQ_POST);
	
	if ($address == $username) {
		section_error(_('forwardingloop'));
	}
	else if (!preg_match("/^[a-z0-9][a-z0-9-.+_]*@[a-z0-9](?:[a-z0-9-][.]?)*[a-z0-9]\.[a-z]{2,5}$/i", $address)) {
      section_error(_('invalidaddress'));
    }
    else {
    	$_ispc_remote = new ispc_remote();
    	$res = $_ispc_remote->grud_record('get','user', array('email' => $username), true);
    	
    	$params = $res[0];
    	unset($params['password']);
    	
    	$params['id'] = $params['mailuser_id'];
		unset($params['mailuser_id']);
    	
   	 	if (!empty($params['custom_mailfilter']) && preg_match('/^cc "!([a-z0-9][a-z0-9-.+_]*@[a-z0-9](?:[a-z0-9-][.]?)*[a-z0-9]\.[a-z]{2,5})([^"]*)"$/m', $params['custom_mailfilter'], $cc)) {
			$params['custom_mailfilter'] = preg_replace('/cc "!'.$cc[1].$cc[2].'"/', 'cc "!'.$address.$cc[2].'"', $params['custom_mailfilter']);
		}
		else {
			// The order of filters is significant. Prepend cc command to the recipes to ensure mail is sent
			// before anything else is processed.
			$params['custom_mailfilter'] = "cc \"!$address\"\n\n" . $params['custom_mailfilter'];
		}
    	
		$_ispc_remote->grud_record('update','user', $params);
		section_message(_("Successfully Saved Options"));
    }
}

function ispc_deleteForwarding()
{
	global $username;
	
	$_ispc_remote = new ispc_remote();
	$res = $_ispc_remote->grud_record('get','user', array('email' => $username), true);
	
	$params = $res[0];
    unset($params['password']);
	
	if (!empty($params['custom_mailfilter']) && preg_match('/^cc "!([a-z0-9][a-z0-9-.+_]*@[a-z0-9](?:[a-z0-9-][.]?)*[a-z0-9]\.[a-z]{2,5})[^"]*"$/m', $params['custom_mailfilter'], $cc))
	{
		if (trim($cc[2]) == '') { // No other addresses, safe to remove line
			$params['custom_mailfilter'] = preg_replace('/^cc "!([a-z0-9][a-z0-9-.+_]*@[a-z0-9](?:[a-z0-9-][.]?)*[a-z0-9]\.[a-z]{2,5})[^"]*"$/m', '', $params['custom_mailfilter']);
			$params['custom_mailfilter'] = rtrim($params['custom_mailfilter'], " \n");
		}
		else {
			$params['custom_mailfilter'] = preg_replace('/cc "!'.$cc[1].$cc[2].'/', 'cc "!'.trim($cc[2]).'"', $params['custom_mailfilter']);
		}
		
		$params['id'] = $params['mailuser_id'];
		unset($params['mailuser_id']);
		
		$_ispc_remote->grud_record('update','user', $params);
		section_message(_('Deleted'));
	}
}


function ispc_getDateTimeHTML($form_element, $default_value, $display_seconds=false)
{
	$_datetime = strtotime($default_value);
	$_showdate = ($_datetime === false) ? false : true;

	$dselect = array('day','month','year','hour','minute');
            if ($display_seconds === true) {
	 	$dselect[] = 'second';
	}
	 
	$out = '';
	 
	foreach ($dselect as $dt_element)
	{
	 	$dt_options = array();
	 	$dt_space = 1;
	 	
	 	switch ($dt_element) {
	 		case 'day':
			 	for ($i = 1; $i <= 31; $i++) {
		            $dt_options[] = array('name' =>  sprintf('%02d', $i),
		            					  'value' => sprintf('%d', $i));
		        }
		        $selected_value = date('d', $_datetime);
	 			break;
	 			
	 		case 'month':
		 		for ($i = 1; $i <= 12; $i++) {
		            $dt_options[] = array('name' => strftime('%b', mktime(0, 0, 0, $i, 1, 2000)),
		            					  'value' => strftime('%m', mktime(0, 0, 0, $i, 1, 2000)));
		        }
		        $selected_value = date('n', $_datetime);
	 			break;
	 			
	 		case 'year':
			 	$start_year = strftime("%Y");
				$years = range((int)$start_year, (int)($start_year+3));
		        
		        foreach ($years as $year) {
		        	$dt_options[] = array('name' => $year,
		            					 'value' => $year);
		        }
		        $selected_value = date('Y', $_datetime);
		        $dt_space = 2;
	 			break;
	 			
	 		case 'hour':
	 			foreach(range(0, 23) as $hour) {
	 				$dt_options[] = array('name' =>  sprintf('%02d', $hour),
            	    					  'value' => sprintf('%d', $hour));
	 			}
	 			$selected_value = date('G', $_datetime);
	 			break;
	 			
	 		case 'minute':
	 			foreach(range(0, 59) as $minute) {
	 				if (($minute % 5) == 0) {
	 					$dt_options[] = array('name' =>  sprintf('%02d', $minute),
											  'value' => sprintf('%d', $minute));
	 				}
	 			}
	 			$selected_value = (int)floor(date('i', $_datetime));
	 			break;
	 			
	 		case 'second':	
	 			foreach(range(0, 59) as $second) {
	 				$dt_options[] = array('name' =>  sprintf('%02d', $second),
					      				  'value' => sprintf('%d', $second));
	 			}
	 			$selected_value = (int)floor(date('s', $_datetime));
	 			break;
	 	}
			 	
		$out .= "<select name=\"".$form_element."[$dt_element]\" id=\"".$form_element."_$dt_element\" class=\"selectInput\">";
		if (!$_showdate) {
			$out .= "<option value=\"-\" selected=\"selected\">--</option>" . PHP_EOL;
		} else {
			$out .= "<option value=\"-\">--</option>" . PHP_EOL;
		}
		 
		foreach ($dt_options as $dt_opt) {
			if ( $_showdate && ($selected_value == $dt_opt['value']) ) {
				$out .= "<option value=\"{$dt_opt['value']}\" selected=\"selected\">{$dt_opt['name']}</option>" . PHP_EOL;
			} else {
				$out .= "<option value=\"{$dt_opt['value']}\">{$dt_opt['name']}</option>" . PHP_EOL;
			}
		}
										        
		$out .= '</select>' . str_repeat('&nbsp;', $dt_space);
	}
	
	return $out;
}

function ispc_getAutoreplyPage()
{
	global $username;
	
	$prev = sq_change_text_domain('squirrelmail');
	$_vars = array('save' => _("Save"));
	
	sq_change_text_domain('ispconfig3', SM_PATH . 'plugins/ispconfig3/locale');
	
	$_ispc_remote = new ispc_remote();
	$res = $_ispc_remote->grud_record('get','user', array('email' => $username), true);
	
	if (is_array($res) && count($res)) 
	{
		$_vars['autoreplyenabled'] = ($res[0]['autoresponder'] == 'y') ? 'checked="checked"': '';
		$_vars = array_merge($_vars, $res[0]);
	}
	
	ispc_displayPage('autoreply', $_vars);
}

function ispc_saveAutoreply()
{
	global $username;
	
	sqgetGlobalVar('_autoreplyenabled', $enabled, SQ_POST);
	sqgetGlobalVar('_autoreplybody', $body, SQ_POST);
	sqgetGlobalVar('_autoresponder_start_date', $start_date_arr, SQ_POST);
	sqgetGlobalVar('_autoresponder_end_date', $end_date_arr, SQ_POST);
	
	if ( $enabled && !$body ) {
		section_error(_('textempty'));
		return false;
	}
	
	$submit_dates = false;
	$result = array_filter($start_date_arr, create_function('$dt_unit', 'return ($dt_unit > 0);'));
	
	if ( $enabled && (count($result) !== 0) ) // Start date has been selected, do validation.
	{
		$compare = mktime(date('H'), (date('i')-30), 0, date('m'), date('d'), date('Y')); // Turn back the clock 30 minutes for slow posters.
		
		// Get start date (unix timestamp)
		$sd_second = 0;
		$filtered_values = array_map(create_function('$item','return (int)$item;'), $start_date_arr);
		extract($filtered_values, EXTR_PREFIX_ALL, 'sd');
		
		$start_date = mktime($sd_hour, $sd_minute, $sd_second, $sd_month, $sd_day, $sd_year);
		
		if ($start_date < $compare) {
			section_error(_('startdate_notfuture'));
			return false;
		}
		
		// Get end date (unix timestamp)
		$ed_second = 0;
		$filtered_values = array_map(create_function('$item','return (int)$item;'), $end_date_arr);
		extract($filtered_values, EXTR_PREFIX_ALL, 'ed');
		
		$end_date = mktime($ed_hour, $ed_minute, $ed_second, $ed_month, $ed_day, $ed_year);
		
		if ($end_date <= $start_date) {
			section_error(_('endate_notgreater'));
			return false;
		}
		
		$submit_dates = true;
	}
	
	$enabled = (!$enabled) ? 'n' : 'y';
	
	$_ispc_remote = new ispc_remote();
    $res = $_ispc_remote->grud_record('get','user', array('email' => $username), true);
    
    $params = $res[0];
    unset($params['password']);
    
    $params['id'] = $params['mailuser_id'];
	unset($params['mailuser_id']);
    
    $params['autoresponder'] = $enabled;
	$params['autoresponder_text'] = $body;
	if ($submit_dates) 
	{
		$params['autoresponder_start_date'] = $start_date_arr;
		$params['autoresponder_end_date'] = $end_date_arr;
	}
	
	$_ispc_remote->grud_record('update','user', $params);
	section_message(_("Successfully Saved Options"));
}

function ispc_getMailFilterPage()
{
	global $username;
	
	$prev = sq_change_text_domain('squirrelmail');
	$_save = _("Save");
	
	sq_change_text_domain('ispconfig3', SM_PATH . 'plugins/ispconfig3/locale');
	
	$_ispc_remote = new ispc_remote();
	
	// Get available mail filter entries
	$res = $_ispc_remote->grud_record('get','user', array('email' => $username), true);
	$res = $_ispc_remote->grud_record('get','user_filter', array('mailuser_id' => $res[0]['mailuser_id']));
	
	$_vars = array('save' => $_save, 'entries' => $res);
	
	// Fetch data for entry for editing
	sqgetGlobalVar('_id', $id, SQ_GET);
	if (!empty($id) && is_numeric($id)) {
		$form = $_ispc_remote->grud_record('get','user_filter', $id);
	}
		
	if (is_array($form) && count($form)) 
	{
		$_vars['filterenabled'] = ($form['active'] == 'y') ? 'checked="checked"': '';
		$_vars = array_merge($_vars, $form);
	}
	
	ispc_displayPage('mailfilter', $_vars);
}

function ispc_saveMailFilter()
{
	global $username, $rcmail_config;
	
	sqgetGlobalVar('_id', $id, SQ_POST);
		
    sqgetGlobalVar('_filtername', $name, SQ_POST);
	if (empty($name)) {
		section_error('textempty');
		return false;
	}
    sqgetGlobalVar('_filtersource', $source, SQ_POST);
	sqgetGlobalVar('_filterop', $op, SQ_POST);
	sqgetGlobalVar('_filtersearchterm', $searchterm, SQ_POST);
	if (empty($searchterm)) {
		section_error('textempty');
		return false;
	}
	sqgetGlobalVar('_filtertarget', $target, SQ_POST);
	if ($action == 'move' && empty($target)) {
		section_error('textempty');
		return false;
	}
	sqgetGlobalVar('_filteraction', $action, SQ_POST);
	if ($action == 'delete') {
		$target = '';
	}
	sqgetGlobalVar('_filterenabled', $enabled, SQ_POST);
	
	$enabled = (!$enabled) ? 'n' : 'y';
	
	$_ispc_remote = new ispc_remote();
	$res = $_ispc_remote->grud_record('get','user', array('email' => $username), true);
	
	if (empty($id)) // Adding entry 
	{ 
		$_result = $_ispc_remote->grud_record('get','user_filter', array('mailuser_id' => $res[0]['mailuser_id']));
					
		$limit = (isset($rcmail_config['filter_limit']) && is_numeric($rcmail_config['filter_limit'])) ? $rcmail_config['filter_limit'] : 999;
		if(count($_result) >= $limit) {
			section_error(_('filterlimitreached'));
			return false;
		}
		
		$params = array('sys_userid' => $res[0]['sys_userid'],
						'mailuser_id' => $res[0]['mailuser_id'],
						'rulename' => $name,
						'source' => $source,
						'searchterm' => $searchterm,
						'op' => $op,
						'action' => $action,
						'target' => $target,
						'active' => $enabled);

		$_ispc_remote->grud_record('add','user_filter', $params);
		section_message(_("Successfully Saved Options"));
	}
	else 
	{
		$_result = $_ispc_remote->grud_record('get','user_filter', $id);
		
		if ($_result['mailuser_id'] == $res[0]['mailuser_id']) 
		{
			$params = array('sys_userid' => $res[0]['sys_userid'],
							'id' => $id,
							'mailuser_id' => $res[0]['mailuser_id'],
							'rulename' => $name,
							'source' => $source,
							'searchterm' => $searchterm,
							'op' => $op,
							'action' => $action,
							'target' => $target,
							'active' => $enabled);

			$_ispc_remote->grud_record('update','user_filter', $params);
			section_message(_("Successfully Saved Options"));
		}
	}
}

function ispc_deleteMailFilter()
{
	global $username;
	
	sqgetGlobalVar('_id', $id, SQ_GET);

	if (!empty($id)) 
	{
		$_ispc_remote = new ispc_remote();
		$res = $_ispc_remote->grud_record('get','user', array('email' => $username), true);
		
		$_result = $_ispc_remote->grud_record('get','user_filter', $id);
		
		if ($_result['mailuser_id'] == $res[0]['mailuser_id'])
		{
			$_ispc_remote->grud_record('delete','user_filter', $id);
			section_message(_('Deleted'));
		}
	}
}

function ispc_getPolicyPage()
{
	global $username;
	
	$prev = sq_change_text_domain('squirrelmail');
	$_save = _("Save");
	
	sq_change_text_domain('ispconfig3', SM_PATH . 'plugins/ispconfig3/locale');
	
	$_ispc_remote = new ispc_remote();
	
	$res = $_ispc_remote->grud_record('get','spamfilter_user', array('email' => $username), true);
	$policies = $_ispc_remote->grud_record('get','policy', array(1 => 1), true);
		
	$policy = array();
	foreach($policies as $val)
	{
		if ($val['id'] == $res[0]['policy_id']) {
			$policy = $val;
			break;
		}
	}
	
	$_vars = array('save' => $_save, 
				   'entries' => $policies,
				   'policy' => $policy,
				   'id' => $res[0]['id'],
				   'priority' => $res[0]['priority']);
	
	ispc_displayPage('policy', $_vars);
}

function ispc_setPolicy()
{
	global $username, $rcmail_config;
	
	sqgetGlobalVar('_id', $id, SQ_GET);
	
	if (!empty($id) && is_numeric($id)) 
	{
		/**
		 * @todo Not using our remoting class as the param order of the mail_spamfilter_user_update function is incorrect.
		 * When this bug is fixed upstream revert this section to using the remoting class.
		 */
		$client = new SoapClient(null, array('location' => $rcmail_config['soap_url'].'index.php',
											 'uri'      => $rcmail_config['soap_url']));
		
		try
		{
			$session_id = $client->login($rcmail_config['remote_soap_user'],$rcmail_config['remote_soap_pass']);
			
			// Validate id is in fact an policy id
			$res = $client->mail_policy_get($session_id, $id);
			if (!count($res)) {
		 		section_error(_('invalid_policyid'));
		 		return false;
		 	}
			
			$res = $client->mail_spamfilter_user_get($session_id, array('email' => $username));
			if (!isset($res[0])) // User doesn't have policy settings yet
			{
				$res = $client->mail_user_get($session_id, array('email' => $username), true);
				
				$params = array('server_id' => $res[0]['server_id'],
								'policy_id' => $id,
								'email' => $username,
								'fullname' => $username,
								'priority' => 7,
								'local' => 'Y');
				
				$client_id = $client->client_get_id($session_id, $res[0]['sys_userid']);
				$client->mail_spamfilter_user_add($session_id, $client_id, $params);
			}
			else {
				$params = $res[0];
				
				$primary_id = $params['id'];
				unset($params['id']);

				$params['policy_id'] = $id;
				
				$client_id = $client->client_get_id($session_id, $params['sys_userid']);
				$client->mail_spamfilter_user_update($session_id, $primary_id, $client_id, $params);
			}
	
			section_message(_("Successfully Saved Options"));
			$client->logout($session_id);
		}
		catch (SoapFault $e) {
			section_error('Soap Error: '.$e->getMessage());
		}
	}
}

function ispc_getWBListPage()
{
	global $username;
	
	$prev = sq_change_text_domain('squirrelmail');
	$_save = _("Save");
	
	sq_change_text_domain('ispconfig3', SM_PATH . 'plugins/ispconfig3/locale');
	
	$_ispc_remote = new ispc_remote();
		
	// Get available wblist entries
	$res = $_ispc_remote->grud_record('get','spamfilter_user', array('email' => $username), true);	
	$res = $_ispc_remote->grud_record('get','spamfilter_whitelist', array('rid' => $res[0]['id']));
	
	$_vars = array('save' => $_save, 'entries' => $res);
	
	// Fetch data for entry for editing
	sqgetGlobalVar('_id', $id, SQ_GET);
	if (!empty($id) && is_numeric($id)) {
		$form = $_ispc_remote->grud_record('get','spamfilter_whitelist', $id);
	}
		
	if (is_array($form) && count($form))
	{
		$_vars['wblistenabled'] = ($form['active'] == 'y') ? 'checked="checked"': '';	
		$_vars = array_merge($_vars, $form);
	}
	
	ispc_displayPage('wblist', $_vars);
}

function ispc_saveWBList()
{
	global $username, $rcmail_config;
	
	sqgetGlobalVar('_id', $id, SQ_POST);
	
	sqgetGlobalVar('_email', $email, SQ_POST);
	if (empty($email)) {
		section_error('textempty');
		return false;
	}
	sqgetGlobalVar('_wb', $wb, SQ_POST);
	$grud = ($wb == 'W') ? 'spamfilter_whitelist' : 'spamfilter_blacklist';
	
	sqgetGlobalVar('_priority', $priority, SQ_POST);
	
	sqgetGlobalVar('_wblistenabled', $enabled, SQ_POST);
	$enabled = (!$enabled) ? 'n' : 'y';
	
	$_ispc_remote = new ispc_remote();
	$_result = $_ispc_remote->grud_record('get','spamfilter_user', array('email' => $username), true);
	
	if (empty($id)) // Adding entry 
	{
		$params = array('sys_userid' => $_result[0]['sys_userid'],
						'server_id' => $_result[0]['server_id'],
						'rid' => $_result[0]['id'],
						'wb' => $wb,
						'email' => $email,
						'priority' => $priority,
						'active' => $enabled);

		$_ispc_remote->grud_record('add', $grud, $params);
		section_message(_("Successfully Saved Options"));
	}
	else 
	{
		/**
		 * @todo Not using our remoting class as the param order of the mail_spamfilter_whitelist(blacklist)_update function is incorrect.
		 * When this bug is fixed upstream revert this section to using the remoting class.
		 * 
		 * $res = $_ispc_remote->grud_record('get', $grud, $id, true);
		 * 
		 * if ($_result[0]['id'] != $res['rid']) {
		 * 	section_error(_('opnotpermitted'));
		 * 	return false;
		 * }
		 * 
		 * $res['wb'] = $wb;
		 * $res['email'] = $email;
		 * $res['priority'] = $priority;
		 * $res['active'] = $active;
		 * 
		 * $_ispc_remote->grud_record('update', $grud, $params);
		 * section_message(_("Successfully Saved Options"));
		 */
		$client = new SoapClient(null, array('location' => $rcmail_config['soap_url'].'index.php',
											 'uri'      => $rcmail_config['soap_url']));
		
		try
		{
			$session_id = $client->login($rcmail_config['remote_soap_user'],$rcmail_config['remote_soap_pass']);
			
			// Validate id is in fact an policy id
			$fname = "mail_{$grud}_get";
			$res = $client->$fname($session_id, $id);
			
			if ($_result[0]['id'] != $res['rid']) {
		 		section_error(_('opnotpermitted'));
		 		return false;
		 	}
		 	
		 	$primary_id = $res['wblist_id'];
			unset($res['wblist_id']);
		 	
		 	$res['wb'] = $wb;
		 	$res['email'] = $email;
		 	$res['priority'] = $priority;
		 	$res['active'] = $enabled;
			
			$client_id = $client->client_get_id($session_id, $res['sys_userid']);
			
			$fname = "mail_{$grud}_update";
			$client->$fname($session_id, $primary_id, $client_id, $res);
				
			section_message(_("Successfully Saved Options"));
			$client->logout($session_id);
		}
		catch (SoapFault $e) {
			section_error('Soap Error: '.$e->getMessage());
		}
	}
}

function ispc_deleteWBList()
{
	global $username;
	
	sqgetGlobalVar('_id', $id, SQ_GET);

	if (!empty($id)) 
	{
		$_ispc_remote = new ispc_remote();
		$res = $_ispc_remote->grud_record('get','spamfilter_user', array('email' => $username), true);
		
		$_result = $_ispc_remote->grud_record('get','spamfilter_whitelist', $id);
		
		if ($_result['rid'] == $res[0]['id'])
		{
			$_ispc_remote->grud_record('delete','spamfilter_whitelist', $id);
			section_message(_('Deleted'));
		}
	}
}

?>