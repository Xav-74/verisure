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


/*
 *	SECURITAS_STATUS :
 *		STATE_ALARM_DISARMED: ['0',("1","32")]
 *		STATE_ALARM_ARMED_HOME: ['P',("311","202")]
 *		STATE_ALARM_ARMED_NIGHT: [('Q'),("46", "203")]
 *		STATE_ALARM_ARMED_AWAY: [('1','A'),("2","31")]
 *		STATE_ALARM_ARMED_PERI: ['3',("204")]
 *		STATE_ALARM_ARMED_AWAY_PERI: ['4',("???")]
 *		STATE_ALARM_ARMED_HOME_PERI: ['B',("???")]
 *		STATE_ALARM_ARMED_NIGHT_PERI: ['C',("???")]
 *		STATE_ALARM_TRIGGERED: ['???',("13","24")]
 *		STATE_ALARM_SOS: ['???',("29")]
*/


class verisureAPI {
	
    /*Base URL for Securitas Direct / Versisure - @var string */
	private $baseUrl = "https://mob2217.securitasdirect.es:12010/WebService/ws.do?";
	
	/* Verisure sessionID - @var string */
	private $sessionID;
    
	/* Versisure Installation number - @var int */
	private $numinstall;
	
	/* Verisure Username - @var string */
	private $username;
	
	/* Verisure Password - @var string */
	private $password;
	
	/* Verisure Country - @var string */
	private $country;
	
	/* Verisure Langauge - @var string */
	private $language;
	
	/* Verisure ID - @var string */
	private $id;
		
	/* Verisure Hash - @var int */
	private $verisure_hash;
	
	/* Verisure request - @var string */
	private $request;
	
	/* Verisure panel - @var string */
	private $panel = "SDVFAST";
	
	/* Verisure callby - @var string */
	private $callby = "IPH_61";
	
	/* Verisure photo request - @var int */
	private $idservice = 1;
	private $instibs;
	private $device;
	private $idsignal;
	private $signaltype;
	
	
	public function __construct($numinstall, $username, $password, $country) {
		
		$this->numinstall = $numinstall;
		$this->username = $username;
		$this->password = $password;
		switch($country)  {
			case 1:					//Securitas Direct France
				$this->country = "FR";
				$this->language = "fr";
				break;
			case 2:					//Securitas Direct Spain
				$this->country = "ES";
				$this->language = "es";
				break;
			case 3:					//Securitas Direct UK
				$this->country = "GB";
				$this->language = "en";
				break;
			case 4:					//Securitas Direct Italy
				$this->country = "IT";
				$this->language = "it";
				break;
			case 5:					//Securitas Direct Portugal
				$this->country = "PT";
				$this->language = "pt";
				break;
		}
		$this->id = null;
		$this->verisure_hash = null;
		$this->request = null;
		$this->sessionID = null;
	}


	public function __destruct()  {
	
	}
 
 
	private function doRequest($data, $method, $headers) {		//Execute all https request to Verisure Cloud
       
		$curl = curl_init();
		
		if($method == "GET")  {
			$url = $this->baseUrl.$data;
			curl_setopt($curl, CURLOPT_URL,				$url);
			curl_setopt($curl, CURLOPT_TIMEOUT,			5);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT,	5);
			curl_setopt($curl, CURLOPT_SSL_CIPHER_LIST, "TLSv1");
			curl_setopt($curl, CURLOPT_RETURNTRANSFER,	true);
			curl_setopt($curl, CURLOPT_HEADER, 			1);
			curl_setopt($curl, CURLOPT_HTTPHEADER, 		$headers);
		}
		
		if ($method == "POST")  {
			curl_setopt($curl, CURLOPT_URL,				$this->baseUrl);
			curl_setopt($curl, CURLOPT_TIMEOUT,			5);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT,	5);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST,	$method);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER,	true);
			curl_setopt($curl, CURLOPT_SSL_CIPHER_LIST, "TLSv1");
			curl_setopt($curl, CURLOPT_POSTFIELDS,		$data);
			curl_setopt($curl, CURLOPT_HEADER, 			1);
			curl_setopt($curl, CURLOPT_HTTPHEADER, 		$headers);
		}
			
		$resultXML = curl_exec($curl);
        $httpRespCode  = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
      	$header = substr($resultXML, 0, $header_size);
		$body = substr($resultXML, $header_size);
      	curl_close($curl);
      
      	$xml = simplexml_load_string($body);
		$result = json_decode(json_encode((array) $xml), true);
      
		preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $header, $matches);
		$cookies = array();
		foreach($matches[1] as $item) {
   			parse_str($item, $cookie);
   		 	$cookies = array_merge($cookies, $cookie);
		}
		
		if (!empty($cookies['JSESSIONID']))	{
			$this->sessionID = $cookies['JSESSIONID'];
        }
				
		return array($httpRespCode, $result);
	}


	private function setHeaders()   {				//Define headers
		
		$headers = array();
        $headers[] = 'Accept: */*';  
        $headers[] = 'User-Agent: Verisure/1 CFNetwork/1197 Darwin/20.0.0'; 
        $headers[] = sprintf('Cookie: JSESSIONID=%s', $this->sessionID);
     	return $headers;
	}	


	private function setParams($request) {			//Set params for https request to Verisure Cloud
		
		$this->request = $request;
		switch($this->request)  {
			case "LOGIN":
				$date = date('YmdHis');
				$this->id = "IPH_________________________".$this->username.$date;
				$params = array( 'request' => $this->request, 'ID' => $this->id, 'Country' => $this->country, 'lang' => $this->language, 'user' => $this->username, 'pwd' => $this->password );
				break;
			case "CLS":
				$params = array( 'request' => $this->request, 'ID' => $this->id, 'Country' => $this->country, 'lang' => $this->language, 'user' => $this->username, 'callby' => $this->callby,
								 'hash' => $this->verisure_hash, 'numinst' => null	);
				break;
			case "MYINSTALLATION":
				$params = array( 'request' => $this->request, 'ID' => $this->id, 'Country' => $this->country, 'lang' => $this->language, 'user' => $this->username, 'callby' => $this->callby,
								 'hash' => $this->verisure_hash, 'numinst' => $this->numinstall );
				break;
			case "ACT_V2":
				$params = array( 'request' => $this->request, 'ID' => $this->id, 'Country' => $this->country, 'lang' => $this->language, 'user' => $this->username, 'callby' => $this->callby,
								 'hash' => $this->verisure_hash, 'panel' => $this->panel, 'numinst' => $this->numinstall, 'timefilter' => "3", 'activityfilter' => "0" );
				break;
			case "EST1":
			case "EST2":
			case "ARM1":
			case "ARM2":
			case "ARMNIGHT1":
			case "ARMNIGHT2":
			case "ARMDAY1":
			case "ARMDAY2":
			case "PERI1":
			case "PERI2":
			case "DARM1":
			case "DARM2":
			case "SRV":
				$params = array( 'request' => $this->request, 'ID' => $this->id, 'Country' => $this->country, 'lang' => $this->language, 'user' => $this->username, 'callby' => $this->callby,
								 'hash' => $this->verisure_hash, 'panel' => $this->panel, 'numinst' => $this->numinstall );
				break;
			case "IMG1":
			case "IMG2":
				$params = array( 'request' => $this->request, 'ID' => $this->id, 'Country' => $this->country, 'lang' => $this->language, 'user' => $this->username, 'callby' => $this->callby,
								 'hash' => $this->verisure_hash, 'panel' => $this->panel, 'numinst' => $this->numinstall, 'idservice' => $this->idservice, 'instibs' => $this->instibs, 'device' => $this->device );
				break;
			case "INF":
				$params = array( 'request' => $this->request, 'ID' => $this->id, 'Country' => $this->country, 'lang' => $this->language, 'user' => $this->username, 'callby' => $this->callby,
								 'hash' => $this->verisure_hash, 'panel' => $this->panel, 'numinst' => $this->numinstall, 'idsignal' => $this->idsignal, 'signaltype' => $this->signaltype );
				break;
		}
		$params_string = http_build_query($params);
		return $params_string;
    }


	private function CallServer()  {
		$method = "GET";
		$headers = $this->setHeaders();
		$params = $this->setParams("SRV");
		$result = $this->doRequest($params, $method, $headers);
		
		$httpRespCode = $result[0];
		$RespArray = $result[1];    
      	$result_rq = $RespArray['RES'];
		$result_msg = $RespArray['MSG'];
		$result_instibs = $RespArray['INSTALATION']['INSTIBS'];
		
		return array($httpRespCode, $result_rq, $result_msg, $result_instibs);
	}
	

	public function Login()  {			// Login to Verisure Cloud
		
		$method = "POST";
		$headers = $this->setHeaders();
		$params = $this->setParams("LOGIN");
		$result = $this->doRequest($params, $method, $headers);
		
		$httpRespCode = $result[0];
		$RespArray = $result[1];    
      	$result_rq = $RespArray['RES'];
		$result_msg = $RespArray['MSG'];
      	$this->verisure_hash = $RespArray['HASH'];
		
		return array($httpRespCode, $result_rq, $result_msg, $this->verisure_hash, $this->sessionID);
	}


	public function Logout()  {			// Logout to verisure Cloud
		
		$method = "GET";
		$headers = $this->setHeaders();
		$params = $this->setParams("CLS");
		$result = $this->doRequest($params, $method, $headers);
		
		$httpRespCode = $result[0];
		$RespArray = $result[1];    
      	$result_rq = $RespArray['RES'];
		$result_msg = $RespArray['MSG'];
		
		return array($httpRespCode, $result_rq, $result_msg, $this->sessionID);
	}


	public function MyInstallation()  {			// Get the sensor IDs and other information related to the installation
		
		$method = "POST";
		$headers = $this->setHeaders();
		$params = $this->setParams("MYINSTALLATION");
		$result = $this->doRequest($params, $method, $headers);
		
		$httpRespCode = $result[0];
		$RespArray = $result[1];    
      	$result_rq = $RespArray['RES'];
		$result_msg = $RespArray['MSG'];
		
		$i = 0;
		$tab_device = array();
		foreach ($RespArray['INSTALLATION']['DEVICES']['DEVICE'] as $device) {
           	$tab_device["Devices"][$i] = $RespArray['INSTALLATION']['DEVICES']['DEVICE'][$i]['@attributes'];
          	$i++;
		}	
		
		return array ($httpRespCode, $result_rq, $result_msg, $this->sessionID, $tab_device);
	}
	
	
	public function GetReport()  {			// Get the information of last actions
		
		$method = "GET";
		$headers = $this->setHeaders();
		$params = $this->setParams("ACT_V2");
		$result = $this->doRequest($params, $method, $headers);
		
		$httpRespCode = $result[0];
		$RespArray = $result[1];    
      	$result_rq = $RespArray['RES'];
		$result_list = $RespArray['LIST'];
		
		return array ($httpRespCode, $result_rq, $this->sessionID, $result_list);
	}
	
	
	public function GetState()  {			// Get the status of the alarm
		
		$method = "GET";
		$headers = $this->setHeaders();
		$params = $this->setParams("EST1");
		$result = $this->doRequest($params, $method, $headers);
		
		$httpRespCode = $result[0];
		$RespArray = $result[1];    
      	$result_rq = $RespArray['RES'];
		$result_msg = $RespArray['MSG'];
		$sID = $this->sessionID;
		
		if($result_rq == "OK")  {
			$headers = $this->setHeaders();
			$params = $this->setParams("EST2");
			$result_rq2 = "WAIT";
			While ($result_rq2 == "WAIT")  {
				sleep(5);
				$result2 = $this->doRequest($params, $method, $headers);
				$httpRespCode2 = $result2[0];
				$RespArray2 = $result2[1];    
				$result_rq2 = $RespArray2['RES'];
				$result_msg2 = $RespArray2['MSG'];
				$result_status = $RespArray2['STATUS'];
				$sID2 = $this->sessionID;
			}
			return array ($httpRespCode, $result_rq, $result_msg, $sID, $httpRespCode2, $result_rq2, $result_msg2, $result_status, $sID2); 
		}
	}
	

	public function ArmTotal()  {			// Arm the alarm in "total" mode
		
		$method = "GET";
		$headers = $this->setHeaders();
		$params = $this->setParams("ARM1");
		$result = $this->doRequest($params, $method, $hearders);
		
		$httpRespCode = $result[0];
		$RespArray = $result[1];    
      	$result_rq = $RespArray['RES'];
		$result_msg = $RespArray['MSG'];	
		$sID = $this->sessionID;
		
		if($result_rq == "OK")  {
			$headers = $this->setHeaders();
			$params = $this->setParams("ARM2");
			$result_rq2 = "WAIT";
			While ($result_rq2 == "WAIT")  {
				sleep(5);
				$result2 = $this->doRequest($params, $method, $hearders);
				$httpRespCode2 = $result2[0];
				$RespArray2 = $result2[1];    
				$result_rq2 = $RespArray2['RES'];
				$result_msg2 = $RespArray2['MSG'];
				$result_status = $RespArray2['STATUS'];
				$sID2 = $this->sessionID;
			}
			return array ($httpRespCode, $result_rq, $result_msg, $sID, $httpRespCode2, $result_rq2, $result_msg2, $result_status, $sID2); 
		}
	}


	public function ArmNight()  {			// Arm the alarm in "night" mode
		
		$method = "GET";
		$headers = $this->setHeaders();
		$params = $this->setParams("ARMNIGHT1");
		$result = $this->doRequest($params, $method, $headers);
		
		$httpRespCode = $result[0];
		$RespArray = $result[1];    
      	$result_rq = $RespArray['RES'];
		$result_msg = $RespArray['MSG'];
		$sID = $this->sessionID;		
		
		if($result_rq == "OK")  {
			$headers = $this->setHeaders();
			$params = $this->setParams("ARMNIGHT2");
			$result_rq2 = "WAIT";
			While ($result_rq2 == "WAIT")  {
				sleep(5);
				$result2 = $this->doRequest($params, $method, $headers);
				$httpRespCode2 = $result2[0];
				$RespArray2 = $result2[1];    
				$result_rq2 = $RespArray2['RES'];
				$result_msg2 = $RespArray2['MSG'];
				$result_status = $RespArray2['STATUS'];
				$sID2 = $this->sessionID;
			}
			return array ($httpRespCode, $result_rq, $result_msg, $sID, $httpRespCode2, $result_rq2, $result_msg2, $result_status, $sID2); 
		}
	}


	public function ArmDay()  {			// Arm the alarm in "day" mode
		
		$method = "GET";
		$headers = $this->setHeaders();
		$params = $this->setParams("ARMDAY1");
		$result = $this->doRequest($params, $method, $headers);
		
		$httpRespCode = $result[0];
		$RespArray = $result[1];    
      	$result_rq = $RespArray['RES'];
		$result_msg = $RespArray['MSG'];
		$sID = $this->sessionID;		
		
		if($result_rq == "OK")  {
			$headers = $this->setHeaders();
			$params = $this->setParams("ARMDAY2");
			$result_rq2 = "WAIT";
			While ($result_rq2 == "WAIT")  {
				sleep(5);
				$result2 = $this->doRequest($params, $method, $headers);
				$httpRespCode2 = $result2[0];
				$RespArray2 = $result2[1];    
				$result_rq2 = $RespArray2['RES'];
				$result_msg2 = $RespArray2['MSG'];
				$result_status = $RespArray2['STATUS'];
				$sID2 = $this->sessionID;
			}
			return array ($httpRespCode, $result_rq, $result_msg, $sID, $httpRespCode2, $result_rq2, $result_msg2, $result_status, $sID2);
		}
	}


	public function ArmExt()  {			// Arm the alarm in "outside" mode
		
		$method = "GET";
		$headers = $this->setHeaders();
		$params = $this->setParams("PERI1");
		$result = $this->doRequest($params, $method, $headers);
		
		$httpRespCode = $result[0];
		$RespArray = $result[1];    
      	$result_rq = $RespArray['RES'];
		$result_msg = $RespArray['MSG'];
		$sID = $this->sessionID;		
		
		if($result_rq == "OK")  {
			$headers = $this->setHeaders();
			$params = $this->setParams("PERI2");
			$result_rq2 = "WAIT";
			While ($result_rq2 == "WAIT")  {
				sleep(5);
				$result2 = $this->doRequest($params, $method, $headers);
				$httpRespCode2 = $result2[0];
				$RespArray2 = $result2[1];    
				$result_rq2 = $RespArray2['RES'];
				$result_msg2 = $RespArray2['MSG'];
				$result_status = $RespArray2['STATUS'];
				$sID2 = $this->sessionID;
			}
			return array ($httpRespCode, $result_rq, $result_msg, $sID, $httpRespCode2, $result_rq2, $result_msg2, $result_status, $sID2); 
		}
	}


	public function Disarm()  {			// Disarm the alarm (all mode)
		
		$method = "GET";
		$headers = $this->setHeaders();
		$params = $this->setParams("DARM1");
		$result = $this->doRequest($params, $method, $headers);
		
		$httpRespCode = $result[0];
		$RespArray = $result[1];    
      	$result_rq = $RespArray['RES'];
		$result_msg = $RespArray['MSG'];
		$sID = $this->sessionID;
		
		if($result_rq == "OK")  {
			$headers = $this->setHeaders();
			$params = $this->setParams("DARM2");
			$result_rq2 = "WAIT";
			While ($result_rq2 == "WAIT")  {
				sleep(5);
				$result2 = $this->doRequest($params, $method, $headers);
				$httpRespCode2 = $result2[0];
				$RespArray2 = $result2[1];    
				$result_rq2 = $RespArray2['RES'];
				$result_msg2 = $RespArray2['MSG'];
				$result_status = $RespArray2['STATUS'];
				$sID2 = $this->sessionID;
			}
			return array ($httpRespCode, $result_rq, $result_msg, $sID, $httpRespCode2, $result_rq2, $result_msg2, $result_status, $sID2); 
		}
	}
	
	public function PhotosRequest($device)  {	// Photos request
		
		$this->device = $device;
		$srv = $this->CallServer();
		$httpRespCode = $srv[0];
		$result_rq = $srv[1];
		$result_msg = $srv[2];
		$this->instibs = $srv[3];
		$sID = $this->sessionID;
		
		$method = "GET";
		$headers = $this->setHeaders();
		$params = $this->setParams("IMG1");
		$result2 = $this->doRequest($params, $method, $headers);
		
		$httpRespCode2 = $result2[0];
		$RespArray2 = $result2[1];    
      	$result_rq2 = $RespArray2['RES'];
		$result_msg2 = $RespArray2['MSG'];
		$sID2 = $this->sessionID;
				
		if($result_rq2 == "OK")  {
			$headers = $this->setHeaders();
			$params = $this->setParams("IMG2");
			$result_rq3 = "WAIT";
			While ($result_rq3 == "WAIT")  {
				sleep(5);
				$result3 = $this->doRequest($params, $method, $headers);
				$httpRespCode3 = $result3[0];
				$RespArray3 = $result3[1];    
				$result_rq3 = $RespArray3['RES'];
				$result_msg3 = $RespArray3['MSG'];
				$result_status = $RespArray3['STATUS'];
				$sID3 = $this->sessionID;
			}
			
			if($result_rq3 == "OK")  {
				$report_type = 0;
				$report_time = date("ymdHis");
				$report_check = false;
				While ($report_type != 16 && $report_check != true)  {
					sleep(5);
					$result4 = $this->GetReport();
					$httpRespCode4 = $result4[0];
					$result_rq4 = $result4[1];
					$RespArray4 = $result4[3];
					$sID4 = $this->sessionID;
					if ($report_time < $RespArray4['REG'][0]['@attributes']['time'])  {
						$report_check = true;
						$report_type = $RespArray4['REG'][0]['@attributes']['type'];
						$this->idsignal = $RespArray4['REG'][0]['@attributes']['idsignal'];
						$this->signaltype = $RespArray4['REG'][0]['@attributes']['signaltype'];
					}	
				}
				if($result_rq4 == "OK")  {
					$method = "GET";
					$headers = $this->setHeaders();
					$params = $this->setParams("INF");
					$result5 = $this->doRequest($params, $method, $headers);
					$httpRespCode5 = $result5[0];
					$RespArray5 = $result5[1];    
					$result_rq5 = $RespArray5['RES'];
					$picture = $RespArray5['DEVICES']['DEVICE']['IMG'][2];		//Base64 encoded image
					$sID5 = $this->sessionID;
				}
				return array ($httpRespCode, $result_rq, $result_msg, $this->instibs, $sID, $httpRespCode2, $result_rq2, $result_msg2, $sID2,
							  $httpRespCode3, $result_rq3, $result_msg3, $result_status, $sID3, $httpRespCode4, $result_rq4, $this->idsignal, $sID4,
							  $httpRespCode5, $result_rq5, $sID5, $picture); 
			}
		}
	}

}

?>