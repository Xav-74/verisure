<?php

/* This file is part of the Jeedom Verisure plugin  (https://github.com/Xav-74/verisure)
 * Copyright (c) 2020 Xavier CHARLES  (https://github.com/Xav-74)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */


class verisureAPI2 {
	
    /*Available domains for Securitas Direct / Versisure - @var string */
	private $available_domains = array(
										"https://e-api01.verisure.com",
										"https://e-api02.verisure.com");
		
	/*Working domain for Securitas Direct / Versisure - @var string */
	private $workingDomain;	
	
	/*Base URL for Securitas Direct / Versisure - @var string */
	private $baseUrl = "/xbn/2/"; 
    	
	/* Verisure Username - @var string */
	private $username;
	
	/* Verisure Password - @var string */
	private $password;
	
	/* Verisure code - @var string */
	private $code;
	
	/* Verisure Authorization - @var string */
	private $authorization;
		
	/* Verisure Cookie  */
	private $cookie;
	
	/* Verisure Giid - @var string */
	private $giid;

	/* Verisure transaction ID - @var string */
	private $transactionID;		
	
		
	public function __construct($username, $password, $code) {
		
		$this->username = $username;
		$this->password = $password;
		$this->code = $code;
		$this->authorization = base64_encode(sprintf("CPE/%s:%s", $this->username, $this->password));
		$this->cookie = null;
		$this->giid = null;
		$this->transactionID = null;
		$this->workingDomain = null;
	}


	public function __destruct()  {
	
	}
 
 
	private function doRequest($method, $url, $headers, $data) {			//Execute all https request to Verisure Cloud
       
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 			$url);
		curl_setopt($curl, CURLOPT_HTTPHEADER, 		$headers);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 	$method);
		curl_setopt($curl, CURLOPT_POSTFIELDS,		$data);
		curl_setopt($curl, CURLOPT_TIMEOUT,			5);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT,	5);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,	true);
        curl_setopt($curl, CURLOPT_VERBOSE, 		false);
		
		$result = curl_exec($curl);
        $httpRespCode  = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		
		return array($httpRespCode, $result);
	}


	private function setHeaders()   {				//Define headers
		
		$headers = array();
        $headers[] = 'Accept: application/json, text/javascript, */*; q=0.01';
        $headers[] = 'Content-Type: application/json';

        if ($this->cookie == null)  {				// Login
			$headers[] = sprintf('Authorization: Basic %s', $this->authorization);
		}
		else  {										// Other request
            $headers[] = sprintf('Cookie: vid=%s', $this->cookie);
		}
		
		$headers[] = 'UserAgent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/84.0.4147.125 Safari/537.36';
		return $headers;
	}	

	
	public function Login()  {					// Login to Verisure Cloud
		
		$method = "POST";
		$headers = $this->setHeaders();
		$data = null;
		
		$this->workingDomain = $this->available_domains[0];
		$url = $this->workingDomain.$this->baseUrl.'cookie';
		$result = $this->doRequest($method, $url, $headers, $data);
		$httpRespCode = $result[0];
		$jsonResult = $result[1];
		
		if ($httpRespCode != 200)   {
			$this->workingDomain = $this->available_domains[1];
			$url = $this->workingDomain.$this->baseUrl.'cookie';
			$result2 = $this->doRequest($method, $url, $headers, $data);
			$httpRespCode2 = $result2[0];
			$jsonResult2 = $result2[1];
			
			if ($httpRespCode2 != 200)   {
				$this->cookie = "Err cookie";
				return array("No Verisure server is available at this moment", $httpRespCode2, $this->cookie);
			}
			else   {
				$this->cookie = json_decode($jsonResult2, false)->{'cookie'};
				return array($this->workingDomain, $httpRespCode2, $this->cookie);
			}
		}
		else   {
			$this->cookie = json_decode($jsonResult, false)->{'cookie'};
			return array($this->workingDomain, $httpRespCode, $this->cookie);
		}
	}
	
	
	public function Logout()  {					// Logout to Verisure Cloud
		
		$method = "DELETE";
		$url = $this->workingDomain.$this->baseUrl.'cookie';
		$headers = $this->setHeaders();
		$data = null;
		$result = $this->doRequest($method, $url, $headers, $data);
		
		$httpRespCode = $result[0];
		$jsonResult = $result[1];
				
		return array($httpRespCode, $jsonResult);
	}
	
	
	public function getGiid()  {				// Get the giid number
		
		$method = "GET";
		$url = sprintf($this->workingDomain.$this->baseUrl.'installation/search?email=%s', urlencode($this->username));
		$headers = $this->setHeaders();
		$data = null;
		$result = $this->doRequest($method, $url, $headers, $data);
		
		$httpRespCode = $result[0];
		$jsonResult = $result[1];
		if ($httpRespCode == 200)  {
        	$this->giid = json_decode($jsonResult, false)[0]->{'giid'};		// Installation ID 0 by default
        }
      			
		return array($httpRespCode, $this->giid);
	}
  
  
  	public function MyInstallation()  {				// Get the installation's information
		
		$method = "GET";
		$url = sprintf($this->workingDomain.$this->baseUrl.'installation/%s/overview', $this->giid);
		$headers = $this->setHeaders();
		$data = null;
		$result = $this->doRequest($method, $url, $headers, $data);
		
		$httpRespCode = $result[0];
		$jsonResult = json_decode($result[1],true);
		
		$tab_device = array();
		$tab_device['climateDevice'] = $jsonResult['climateValues'];
        $tab_device['doorWindowDevice'] = $jsonResult['doorWindow']['doorWindowDevice'];
		$tab_device['cameraDevice'] = $jsonResult['customerImageCameras'];
		
		return array($httpRespCode, $result[1], $tab_device);
	}
  

	public function getStateAlarm()  {				// Get the status of alarm
		
		$method = "GET";
		$url = sprintf($this->workingDomain.$this->baseUrl.'installation/%s/armstate', $this->giid);
		$headers = $this->setHeaders();
		$data = null;
		$result = $this->doRequest($method, $url, $headers, $data);
		
		$httpRespCode = $result[0];
		$jsonResult = $result[1];
		
		if ($httpRespCode == 200)  {
        	$stateAlarm = json_decode($jsonResult, false)->{'statusType'};
        }
		
		return array($httpRespCode, $stateAlarm);
	}
	
	
	public function getStateDevices()  {				// Get the status of alarm devices
		
		$method = "GET";
		$url = sprintf($this->workingDomain.$this->baseUrl.'installation/%s/overview', $this->giid);
		$headers = $this->setHeaders();
		$data = null;
		$result = $this->doRequest($method, $url, $headers, $data);
		
		$httpRespCode = $result[0];
		$jsonResult = json_decode($result[1],true);
		
		$tab_device = array();
		$tab_device['lastModified'] = date("Y-m-d H:i:s");
		$tab_device['climateDevice'] = $jsonResult['climateValues'];
        $tab_device['doorWindowDevice'] = $jsonResult['doorWindow']['doorWindowDevice'];
		$tab_device['cameraDevice'] = $jsonResult['customerImageCameras'];
		
		return array($httpRespCode, json_encode($tab_device));
	}
	
	
	public function setStateAlarm($state)  {				// Set the status of alarm - DISARMED / ARMED_HOME / ARMED_AWAY
		
		$method = "PUT";
		$url = sprintf($this->workingDomain.$this->baseUrl.'installation/%s/armstate/code/', $this->giid);
		$headers = $this->setHeaders();
		$data = json_encode(array( 'code' => $this->code, 'state' => $state));
		$result = $this->doRequest($method, $url, $headers, $data);
		
		$httpRespCode = $result[0];
		$jsonResult = $result[1];
		
		if ($httpRespCode == 200)  {
        	$this->transactionID = json_decode($jsonResult, false)->{'armStateChangeTransactionId'};
        }
		
		$result2 = $this->getTransactionID();
		$httpRespCode2 = $result2[0];
		$jsonResult2 = $result2[1];
		
		if ($httpRespCode2 == 200)  {
			$chgange_state = json_decode($jsonResult2, false)->{'result'};
		}
		
		return array($httpRespCode, $this->transactionID, $jsonResult2, $chgange_state);
	}
	
	
	public function getTransactionID()  {				// Get the transactionID information
		
		$method = "GET";
		$url = sprintf($this->workingDomain.$this->baseUrl.'installation/%s/code/result/%s', $this->giid, $this->transactionID);
		$headers = $this->setHeaders();
		$data = null;
		$result = $this->doRequest($method, $url, $headers, $data);
		
		$httpRespCode = $result[0];
		$jsonResult = $result[1];
				
		return array($httpRespCode, $jsonResult);
	}
	
	
	public function getReport()  {				// Get the information of last actions
	
		$offset = 0;				//Skip pagesize * offset first events
		$pagesize = 100;			//Number of events to display
		$filters = array();			//String set : 'ARM', 'DISARM', 'FIRE', 'INTRUSION', 'TECHNICAL', 'SOS', 'WARNING', 'LOCK', 'UNLOCK', 'PICTURE', 'CLIMATE'
		
      	$method = "GET";
		$headers = $this->setHeaders();
		$params = array( 'offset' => $offset, 'pagesize' => $pagesize, 'eventCategories' => $filters);
		$data = http_build_query($params);
      	$url = sprintf($this->workingDomain.'/celapi/customereventlog/installation/%s/eventlog?', $this->giid).$data;
      	$result = $this->doRequest($method, $url, $headers, null);
		
		$httpRespCode = $result[0];
		$jsonResult = $result[1];
		
		return array($httpRespCode, $jsonResult);
	}
	
}

?>