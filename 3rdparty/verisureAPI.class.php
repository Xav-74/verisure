<?php

/* This file is part of the Jeedom Verisure plugin  (https://github.com/Xav-74/verisure)
 * Copyright (c) 2020 Xavier CHARLES  (https://github.com/Xav-74)
 * Version : 1.0
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


class verisureAPI {
	
   /*Base URL for Securitas Direct / Versisure - @var string */
	private $baseUrl = "https://mob2217.securitasdirect.es:12010/WebService/ws.do?"; 
    
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
	
	
	public function __construct($numinstall,$username,$password,$country) {
		
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
	
	}


	public function __destruct()  {
	}
 
 
	private function doRequest($data, $method, $retry = null) {		//Execute all https request to Verisure Cloud
       
		$curl = curl_init();
		
		if($method == "GET")  {
			$url = $this->baseUrl.$data;
			curl_setopt($curl, CURLOPT_URL,				$url);
			curl_setopt($curl, CURLOPT_TIMEOUT,			5);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT,	5);
			curl_setopt($curl, CURLOPT_SSL_CIPHER_LIST, "TLSv1");
			curl_setopt($curl, CURLOPT_RETURNTRANSFER,	true);
		}
		
		if ($method == "POST")  {
			curl_setopt($curl, CURLOPT_URL,				$this->baseUrl);
			curl_setopt($curl, CURLOPT_TIMEOUT,			5);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT,	5);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST,	$method);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER,	true);
			curl_setopt($curl, CURLOPT_SSL_CIPHER_LIST, "TLSv1");
			curl_setopt($curl, CURLOPT_POSTFIELDS,		$data);
		}
			
		$resultXML = curl_exec($curl);
        $httpRespCode  = curl_getinfo($curl, CURLINFO_HTTP_CODE);
				
		curl_close($curl);
        
		// TODO -> Gérer les exceptions
		/*if($httpRespCode != 200) {			// If Verisure Cloud don't reply (Internet issue or Cloud issue)
            while ($retry > 0) {
                --$retry;
                sleep(5);
                $this->doRequest($data, $method, $retry);
            }
            if ($retry == 0) {
                throw new \Exception("Unable to connect to Verisure Cloud. Please check your internet connection and/or retry later.");
            }
        }*/
		
		$xml = simplexml_load_string($resultXML);
		$result = json_decode(json_encode((array) $xml), true);
		return array($httpRespCode, $result);
	}


	public function Login()  {			// Login to Verisure Cloud
		
		$method = "POST";
		$this->request = "LOGIN";
		$date = date('YmdHis');
		$this->id = "IPH_________________________".$this->username.$date;
		$params = array(
			'request' => $this->request,
			'ID' => $this->id,
			'Country' => $this->country,
			'lang' => $this->language,
			'user' => $this->username,
			'pwd' => $this->password
		);
		$params_string = http_build_query($params);
		
		$result = $this->doRequest($params_string, $method);
		
		$httpRespCode = $result[0];
		$RespArray = $result[1];    
      	$result_rq = $RespArray['RES'];
		$result_msg = $RespArray['MSG'];
      	$this->verisure_hash = $RespArray['HASH'];
		
		return array($httpRespCode, $result_rq, $result_msg, $this->verisure_hash);
		
		// TODO -> Gérer les exceptions
      	/*try {
            if(isset($result_rq)) {
				if ($result_rq == "OK")  {
					return array($httpRespCode, $result_rq, $result_msg, $this->verisure_hash);
				}
				else  {
					throw new \Exception("User not found");
				}
			}		 
			else  {
				throw new \Exception("Unable to login to Verisure Cloud (http code : ". $httpRespCode . ")");
			}
		} catch (Exception $e) {
            throw $e;
        }*/	
	}


	public function Logout()  {			// Logout to verisure Cloud
		
		$method = "GET";
		$this->request = "CLS";
		$params = array(
			'request' => $this->request,
			'ID' => $this->id,
			'Country' => $this->country,
			'lang' => $this->language,
			'user' => $this->username,
			'pwd' => $this->password,
			'hash' => $this->verisure_hash,
			'numinst' => null
		);
		$params_string = http_build_query($params);
		
		$result = $this->doRequest($params_string, $method);
		
		$httpRespCode = $result[0];
		$RespArray = $result[1];    
      	$result_rq = $RespArray['RES'];
		$result_msg = $RespArray['MSG'];
		
		return array($httpRespCode, $result_rq, $result_msg);
	}


	public function MyInstallation()  {			// Get the sensor IDs and other information related to the installation
		
		$method = "POST";
		$this->request = "MYINSTALLATION";
		$params = array(
			'request' => $this->request,
			'ID' => $this->id,
			'Country' => $this->country,
			'lang' => $this->language,
			'user' => $this->username,
			'pwd' => $this->password,
			'hash' => $this->verisure_hash,
			'numinst' => $this->numinstall
		);
		$params_string = http_build_query($params);
		
		$result = $this->doRequest($params_string, $method);
		
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
		
		return array ($httpRespCode, $result_rq, $result_msg, $tab_device);
	}
	
	
	public function GetState()  {			// Get the status of the alarm
		
		$method = "GET";
		$this->request = "EST1";
		$params = array(
			'request' => $this->request,
			'ID' => $this->id,
			'Country' => $this->country,
			'lang' => $this->language,
			'user' => $this->username,
			'pwd' => $this->password,
			'hash' => $this->verisure_hash,
			'panel' => $this->panel,
			'numinst' => $this->numinstall
		);
		$params_string = http_build_query($params);
		
		$result = $this->doRequest($params_string, $method);
		
		$httpRespCode = $result[0];
		$RespArray = $result[1];    
      	$result_rq = $RespArray['RES'];
		$result_msg = $RespArray['MSG'];	
		
		if($result_rq == "OK")  {
			$method = "GET";
			$this->request = "EST2";
			$params = array(
				'request' => $this->request,
				'ID' => $this->id,
				'Country' => $this->country,
				'lang' => $this->language,
				'user' => $this->username,
				'pwd' => $this->password,
				'hash' => $this->verisure_hash,
				'panel' => $this->panel,
				'numinst' => $this->numinstall
			);
			$params_string = http_build_query($params);
			
			$result_rq2 = "WAIT";
			While ($result_rq2 == "WAIT")  {
				sleep(5);
				$result2 = $this->doRequest($params_string, $method);
				$httpRespCode2 = $result2[0];
				$RespArray2 = $result2[1];    
				$result_rq2 = $RespArray2['RES'];
				$result_msg2 = $RespArray2['MSG'];
				$result_status = $RespArray2['STATUS'];
			}
									
			return array ($httpRespCode, $result_rq, $result_msg, $httpRespCode2, $result_rq2, $result_msg2, $result_status); 
		}
	}
	

	public function ArmTotal()  {			// Arm the alarm in "total" mode
		
		$method = "GET";
		$this->request = "ARM1";
		$params = array(
			'request' => $this->request,
			'ID' => $this->id,
			'Country' => $this->country,
			'lang' => $this->language,
			'user' => $this->username,
			'pwd' => $this->password,
			'hash' => $this->verisure_hash,
			'panel' => $this->panel,
			'numinst' => $this->numinstall
		);
		$params_string = http_build_query($params);
		
		$result = $this->doRequest($params_string, $method);
		
		$httpRespCode = $result[0];
		$RespArray = $result[1];    
      	$result_rq = $RespArray['RES'];
		$result_msg = $RespArray['MSG'];	
		
		if($result_rq == "OK")  {
			$method = "GET";
			$this->request = "ARM2";
			$params = array(
				'request' => $this->request,
				'ID' => $this->id,
				'Country' => $this->country,
				'lang' => $this->language,
				'user' => $this->username,
				'pwd' => $this->password,
				'hash' => $this->verisure_hash,
				'panel' => $this->panel,
				'numinst' => $this->numinstall
			);
			$params_string = http_build_query($params);
			
			$result_rq2 = "WAIT";
			While ($result_rq2 == "WAIT")  {
				sleep(5);
				$result2 = $this->doRequest($params_string, $method);
				$httpRespCode2 = $result2[0];
				$RespArray2 = $result2[1];    
				$result_rq2 = $RespArray2['RES'];
				$result_msg2 = $RespArray2['MSG'];
				$result_status = $RespArray2['STATUS'];
			}
									
			return array ($httpRespCode, $result_rq, $result_msg, $httpRespCode2, $result_rq2, $result_msg2, $result_status); 
		}
	}


	public function ArmNight()  {			// Arm the alarm in "night" mode
		
		$method = "GET";
		$this->request = "ARMNIGHT1";
		$params = array(
			'request' => $this->request,
			'ID' => $this->id,
			'Country' => $this->country,
			'lang' => $this->language,
			'user' => $this->username,
			'pwd' => $this->password,
			'hash' => $this->verisure_hash,
			'panel' => $this->panel,
			'numinst' => $this->numinstall
		);
		$params_string = http_build_query($params);
		
		$result = $this->doRequest($params_string, $method);
		
		$httpRespCode = $result[0];
		$RespArray = $result[1];    
      	$result_rq = $RespArray['RES'];
		$result_msg = $RespArray['MSG'];	
		
		if($result_rq == "OK")  {
			$method = "GET";
			$this->request = "ARMNIGHT2";
			$params = array(
				'request' => $this->request,
				'ID' => $this->id,
				'Country' => $this->country,
				'lang' => $this->language,
				'user' => $this->username,
				'pwd' => $this->password,
				'hash' => $this->verisure_hash,
				'panel' => $this->panel,
				'numinst' => $this->numinstall
			);
			$params_string = http_build_query($params);
			
			$result_rq2 = "WAIT";
			While ($result_rq2 == "WAIT")  {
				sleep(5);
				$result2 = $this->doRequest($params_string, $method);
				$httpRespCode2 = $result2[0];
				$RespArray2 = $result2[1];    
				$result_rq2 = $RespArray2['RES'];
				$result_msg2 = $RespArray2['MSG'];
				$result_status = $RespArray2['STATUS'];
			}
									
			return array ($httpRespCode, $result_rq, $result_msg, $httpRespCode2, $result_rq2, $result_msg2, $result_status); 
		}
	}


	public function ArmDay()  {			// Arm the alarm in "day" mode
		
		$method = "GET";
		$this->request = "ARMDAY1";
		$params = array(
			'request' => $this->request,
			'ID' => $this->id,
			'Country' => $this->country,
			'lang' => $this->language,
			'user' => $this->username,
			'pwd' => $this->password,
			'hash' => $this->verisure_hash,
			'panel' => $this->panel,
			'numinst' => $this->numinstall
		);
		$params_string = http_build_query($params);
		
		$result = $this->doRequest($params_string, $method);
		
		$httpRespCode = $result[0];
		$RespArray = $result[1];    
      	$result_rq = $RespArray['RES'];
		$result_msg = $RespArray['MSG'];	
		
		if($result_rq == "OK")  {
			$method = "GET";
			$this->request = "ARMDAY2";
			$params = array(
				'request' => $this->request,
				'ID' => $this->id,
				'Country' => $this->country,
				'lang' => $this->language,
				'user' => $this->username,
				'pwd' => $this->password,
				'hash' => $this->verisure_hash,
				'panel' => $this->panel,
				'numinst' => $this->numinstall
			);
			$params_string = http_build_query($params);
			
			$result_rq2 = "WAIT";
			While ($result_rq2 == "WAIT")  {
				sleep(5);
				$result2 = $this->doRequest($params_string, $method);
				$httpRespCode2 = $result2[0];
				$RespArray2 = $result2[1];    
				$result_rq2 = $RespArray2['RES'];
				$result_msg2 = $RespArray2['MSG'];
				$result_status = $RespArray2['STATUS'];
			}
									
			return array ($httpRespCode, $result_rq, $result_msg, $httpRespCode2, $result_rq2, $result_msg2, $result_status); 
		}
	}


	public function ArmExt()  {			// Arm the alarm in "day" mode
		
		$method = "GET";
		$this->request = "PERI1";
		$params = array(
			'request' => $this->request,
			'ID' => $this->id,
			'Country' => $this->country,
			'lang' => $this->language,
			'user' => $this->username,
			'pwd' => $this->password,
			'hash' => $this->verisure_hash,
			'panel' => $this->panel,
			'numinst' => $this->numinstall
		);
		$params_string = http_build_query($params);
		
		$result = $this->doRequest($params_string, $method);
		
		$httpRespCode = $result[0];
		$RespArray = $result[1];    
      	$result_rq = $RespArray['RES'];
		$result_msg = $RespArray['MSG'];	
		
		if($result_rq == "OK")  {
			$method = "GET";
			$this->request = "PERI2";
			$params = array(
				'request' => $this->request,
				'ID' => $this->id,
				'Country' => $this->country,
				'lang' => $this->language,
				'user' => $this->username,
				'pwd' => $this->password,
				'hash' => $this->verisure_hash,
				'panel' => $this->panel,
				'numinst' => $this->numinstall
			);
			$params_string = http_build_query($params);
			
			$result_rq2 = "WAIT";
			While ($result_rq2 == "WAIT")  {
				sleep(5);
				$result2 = $this->doRequest($params_string, $method);
				$httpRespCode2 = $result2[0];
				$RespArray2 = $result2[1];    
				$result_rq2 = $RespArray2['RES'];
				$result_msg2 = $RespArray2['MSG'];
				$result_status = $RespArray2['STATUS'];
			}
									
			return array ($httpRespCode, $result_rq, $result_msg, $httpRespCode2, $result_rq2, $result_msg2, $result_status); 
		}
	}


	public function Disarm()  {			// Disarm the alarm (all mode)
		
		$method = "GET";
		$this->request = "DARM1";
		$params = array(
			'request' => $this->request,
			'ID' => $this->id,
			'Country' => $this->country,
			'lang' => $this->language,
			'user' => $this->username,
			'pwd' => $this->password,
			'hash' => $this->verisure_hash,
			'panel' => $this->panel,
			'numinst' => $this->numinstall
		);
		$params_string = http_build_query($params);
		
		$result = $this->doRequest($params_string, $method);
		
		$httpRespCode = $result[0];
		$RespArray = $result[1];    
      	$result_rq = $RespArray['RES'];
		$result_msg = $RespArray['MSG'];	
		
		if($result_rq == "OK")  {
			$method = "GET";
			$this->request = "DARM2";
			$params = array(
				'request' => $this->request,
				'ID' => $this->id,
				'Country' => $this->country,
				'lang' => $this->language,
				'user' => $this->username,
				'pwd' => $this->password,
				'hash' => $this->verisure_hash,
				'panel' => $this->panel,
				'numinst' => $this->numinstall
			);
			$params_string = http_build_query($params);
			
			$result_rq2 = "WAIT";
			While ($result_rq2 == "WAIT")  {
				sleep(5);
				$result2 = $this->doRequest($params_string, $method);
				$httpRespCode2 = $result2[0];
				$RespArray2 = $result2[1];    
				$result_rq2 = $RespArray2['RES'];
				$result_msg2 = $RespArray2['MSG'];
				$result_status = $RespArray2['STATUS'];
			}
									
			return array ($httpRespCode, $result_rq, $result_msg, $httpRespCode2, $result_rq2, $result_msg2, $result_status); 
		}
	}
	
	//TODO -> Photos request
}

?>