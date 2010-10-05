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

class ispc_remote
{
	private $_soap_client;
	private $_session_id; 
	
	function __construct()
	{
		global $ispc_config;
		
		if (is_null($this->_soap_client)) {
			$this->_soap_client = new SoapClient(null, array('location' => $ispc_config['soap_url'].'index.php',
                                     			 			 'uri'      => $ispc_config['soap_url']));
			
			try {
				$this->_session_id = $this->_soap_client->login($ispc_config['remote_soap_user'],$ispc_config['remote_soap_pass']);
			} 
			catch (SoapFault $e) {
				section_error('Soap Error: '.$e->getMessage());
			}
		}
	}

	public function grud_record($tGrud, $tType, $tParams=array(), $tFilter=false)
	{
			$soap_function = 'mail_'. strtolower($tType) . '_' . strtolower($tGrud);
			try 
			{
		        $_args = (array)$this->_session_id;
		        switch ($tGrud)
		        {
		        	case 'add':
		        	case 'update':
		        		if (!isset($tParams['sys_userid'])) {
		        			section_error('No user id in params array.', $color);
		        		}
		        		$_args[] = $this->_soap_client->client_get_id($this->_session_id, $tParams['sys_userid']);
		        		unset($tParams['sys_userid']);
		        		
		        		if ($tGrud == 'update') {
			        		if (!isset($tParams['id'])) { // No primary id = no update.
			                       section_error('No primary id in params array for update.', $color);
			                }
			                $_args[] = $tParams['id'];
			                unset($tParams['id']);
		        		}
		        		
		        		break;
		        }
		
		        if (sizeof($tParams) > 0) {
		                $_args[] = $tParams;
		    	}
				
		    	$res = call_user_func_array(array($this->_soap_client, $soap_function), $_args);
	    	}
	    	catch (SoapFault $e) {
	    		section_error('Soap Error: '.$e->getMessage(), $color);
	    	}
	    	
	    	if ($tGrud == 'get' && $tFilter === true && is_array($res)) 
	    	{
	    		if (isset($res['sys_userid']))
	    		{
		    		foreach ($res as $k => $v) {
		    			if ($k != 'sys_userid' && strpos($k,'sys_') === 0) {
		    				unset($res[$k]);
		    			}
		    		}
	    		}
	    		else
	    		{
	    			foreach ($res as $pos => $params) {
		    			foreach ($params as $k => $v) {
			    			if ($k != 'sys_userid' && strpos($k,'sys_') === 0) {
			    				unset($res[$pos][$k]);
			    			}
			    		}
	    			}
	    		}
	    	}
	    	
	        return $res;
	}
	
	function __destruct() 
	{
		try {
			$this->_soap_client->logout($this->_session_id);
		}
		catch (SoapFault $e) {
			section_error('Soap Error: '.$e->getMessage(), $color);
		}
	}
}
?>