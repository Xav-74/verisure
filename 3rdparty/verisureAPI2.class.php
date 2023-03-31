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
	
   	private $availableDomain = array(
								"https://automation01.verisure.com",
								"https://automation02.verisure.com");
	
	private $workingDomain;	
	private $baseUrl = "/graphql"; 
 	private $username;
	private $password;
	private $code;
	private $authorization;
	private $giid;

	
	public function __construct($username, $password, $code) {
		
		$this->username = $username;
		$this->password = $password;
		$this->code = $code;
		$this->giid = null;
		$this->workingDomain = null;
		$this->authorization = base64_encode(sprintf("%s:%s", $this->username, $this->password));

	}


	public function __destruct()  {
	
	}
 
 
	private function doRequest($data, $method, $headers, $url) {		//Execute all https request to Verisure Cloud
       
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL,				$url);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST,	$method);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,	true);
		curl_setopt($curl, CURLOPT_POSTFIELDS,		$data);
		curl_setopt($curl, CURLOPT_HEADER, 			true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, 		$headers);
		curl_setopt($curl, CURLOPT_VERBOSE, 		false);
		if ( file_exists(dirname(__FILE__).'/../data/cookie.txt') ) { curl_setopt($curl, CURLOPT_COOKIEFILE, dirname(__FILE__).'/../data/cookie.txt'); }
		if ( $url == $this->workingDomain.'/auth/login' || $url == $this->workingDomain.'/auth/mfa/validate' ) { curl_setopt($curl, CURLOPT_COOKIEJAR, dirname(__FILE__).'/../data/cookie.txt'); }
		
		$result = curl_exec($curl);

		if (!$result) {
            throw new \Exception('Unable to retrieve data');
        }

        $httpRespCode  = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
      	$header = substr($result, 0, $header_size);
		$body = substr($result, $header_size);
      	curl_close($curl);
        
		return array($httpRespCode, $body, $header);
	}


	private function setHeaders($operation)   {				//Define headers
		
		$headers = array(
			'Content-Type: application/json',
			'Accept: application/json',
			'APPLICATION_ID: PS_PYTHON',
			'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.5005.124 Safari/537.36 Edg/102.0.1245.41'
		);
       	
		if ( $operation == 'login_mfa' ) {				// Login with MFA
			$headers[] = sprintf('Authorization: Basic %s', $this->authorization);
		}

		//log::add('verisure', 'debug', '| Headers = '.str_replace('\\','',json_encode($headers)));
		return $headers;
	}	


	private function setContent($operation, $data1, $data2, $data3) {		//Set content for https request to Verisure Cloud
		
		$content = "";
		switch($operation) {
			
			case "fetchAllInstallations":
				$content = array(
					'operationName' => 'fetchAllInstallations',
					'variables' => array(
						'email' => $this->username
					),
					'query' => 'query fetchAllInstallations($email: String!){ account(email: $email) { installations { giid alias customerType dealerId subsidiary pinCodeLength locale address { street city postalNumber __typename } __typename } __typename } }',
				);
			break;

			case "Devices":
				$content = array(
					'operationName' => 'Devices',
					'variables' => array(
						'giid' => $this->giid
					),
					'query' => 'query Devices($giid: String!) { installation(giid: $giid) { devices { deviceLabel area capability gui { support picture deviceGroup sortOrder label __typename } monitoring { operatorMonitored __typename } canChangeEntryExit entryExit __typename } __typename } }',
				);
			break;			
			
			case "Climate":
				$content = array(
					'operationName' => 'Climate',
					'variables' => array(
						'giid' => $this->giid
					),
					'query' => 'query Climate($giid: String!) { installation(giid: $giid) { climates { device { deviceLabel area gui { label __typename } __typename } humidityEnabled humidityTimestamp humidityValue temperatureTimestamp temperatureValue thresholds { aboveMaxAlert belowMinAlert sensorType __typename } __typename } __typename } }',
				);
			break;

			case "DoorWindow":
				$content = array(
					'operationName' => 'DoorWindow',
					'variables' => array(
						'giid' => $this->giid
					),
					'query' => 'query DoorWindow($giid: String!) { installation(giid: $giid) { doorWindows { device { deviceLabel __typename } type area state wired reportTime __typename } __typename } }',
				);
			break;

			case "Camera":
				$content = array(
					'operationName' => 'Camera',
					'variables' => array(
						'giid' => $this->giid,
						"all" => true
					),
					'query' => 'query Camera($giid: String!, $all: Boolean!) { installation(giid: $giid) { cameras(allCameras: $all) { visibleOnCard initiallyConfigured imageCaptureAllowed imageCaptureAllowedByArmstate device { deviceLabel area __typename } latestCameraSeries { image { imageId imageStatus captureTime url } } } } }',
				);
			break;

			case "SmartPlug":
				$content = array(
					'operationName' => 'SmartPlug',
					'variables' => array(
						'giid' => $this->giid
					),
					'query' => 'query SmartPlug($giid: String!) { installation(giid: $giid) { smartplugs { device { deviceLabel area __typename } currentState icon isHazardous __typename } __typename } }',
				);
			break;

			case "EventLog":
				$content = array(
					'operationName' => 'EventLog',
					'variables' => array(
						'giid' => $this->giid,
						'offset' => 0,
						'pagesize' => 50,
						'eventCategories' => $data1,
						'eventContactIds' => [],
						'eventDeviceLabels' => [],
						'fromDate' => null,
						'toDate' => null
					),
					'query' => 'query EventLog($giid: String!, $offset: Int!, $pagesize: Int!, $eventCategories: [String], $fromDate: String, $toDate: String, $eventContactIds: [String], $eventDeviceLabels: [String]) { installation(giid: $giid) { eventLog(offset: $offset, pagesize: $pagesize, eventCategories: $eventCategories, eventContactIds: $eventContactIds, eventDeviceLabels: $eventDeviceLabels, fromDate: $fromDate, toDate: $toDate) { moreDataAvailable pagedList { device { deviceLabel area gui { label __typename } __typename } arloDevice { name __typename } gatewayArea eventType eventCategory eventSource eventId eventTime userName armState userType climateValue sensorType eventCount __typename } __typename } __typename } }',
				);
			break;

			case "ArmState":
				$content = array(
					'operationName' => 'ArmState',
					'variables' => array(
						'giid' => $this->giid
					),
					'query' => 'query ArmState($giid: String!) { installation(giid: $giid) { armState { type statusType date name changedVia __typename } __typename } }',
				);
			break;

			case "disarm":
				$content = array(
					'operationName' => 'disarm',
					'variables' => array(
						'giid' => $this->giid,
						'code' => $this->code
					),
					'query' => 'mutation disarm($giid: String!, $code: String!) { armStateDisarm(giid: $giid, code: $code) }',
				);
			break;

			case "armAway":
				$content = array(
					'operationName' => 'armAway',
					'variables' => array(
						'giid' => $this->giid,
						'code' => $this->code
					),
					'query' => 'mutation armAway($giid: String!, $code: String!) { armStateArmAway(giid: $giid, code: $code) }',
				);
			break;

			case "armHome":
				$content = array(
					'operationName' => 'armHome',
					'variables' => array(
						'giid' => $this->giid,
						'code' => $this->code
					),
					'query' => 'mutation armHome($giid: String!, $code: String!) { armStateArmHome(giid: $giid, code: $code) }',
				);
			break;

			case "UpdateState":
				$content = array(
					'operationName' => 'UpdateState',
					'variables' => array(
						'giid' => $this->giid,
						'deviceLabel' => $data1,
						'state' => $data2
					),
					'query' => 'mutation UpdateState($giid: String!, $deviceLabel: String!, $state: Boolean!) { SmartPlugSetState(giid: $giid, input:[{deviceLabel: $deviceLabel, state: $state}]) }',
				);
			break;

			case "CaptureImageRequest":
				$content = array(
					'operationName' => 'CaptureImageRequest',
					'variables' => array(
						'giid' => $this->giid,
						'deviceLabel' => $data1,
					),
					'query' => 'mutation CaptureImageRequest($giid: String!, $deviceLabel: String!) { CameraRequestImageCapture(giid: $giid, deviceLabel: $deviceLabel) { requestId __typename } }',
				);
			break;

			case "ImageCaptureStatus":
				$content = array(
					'operationName' => 'ImageCaptureStatus',
					'variables' => array(
						'giid' => $this->giid,
						'deviceLabel' => $data1,
						'requestId' => $data2
					),
					'query' => 'query ImageCaptureStatus($giid: String!, $deviceLabel: String!, $requestId: String!) { installation(giid: $giid) { imageCaptureRequestStatus(search: {deviceLabel: $deviceLabel, requestId: $requestId}) { seriesId imageId completionTime captureTime requestTime failedReason status imageOrientation __typename } __typename } }',
				);
			break;


		}

		//log::add('verisure', 'debug', '| Content = '.json_encode($content));
		return json_encode($content);
	}


	private function getExpirationDate($str) {

		$result = strstr(strrchr($str,'Expires='),';',true);
		return $result;
	}


	public function LoginMFA()  {										// Login to Verisure Cloud with MFA
		
		$method = "POST";
		$headers = $this->setHeaders('login_mfa');
		$data = null;
		$this->workingDomain = $this->availableDomain[0];
		$url = $this->workingDomain.'/auth/login';
		
		$result = $this->doRequest($data, $method, $headers, $url);
		$httpRespCode = $result[0];
		$response = $result[1];
				
		if ($httpRespCode != 200)   {
			$this->workingDomain = $this->availableDomain[1];
			$url = $this->workingDomain.'/auth/login';
			$result2 = $this->doRequest($data, $method, $headers, $url);
			$httpRespCode2 = $result2[0];
			$response2 = $result2[1];
			
			if ($httpRespCode2 != 200)   {
				return array("Verisure session error", $httpRespCode2, $response2);
			}
			else   {
				return array($this->workingDomain, $httpRespCode2, $response2);
			}
		}
		else   {
			return array($this->workingDomain, $httpRespCode, $response);
		}
	}


	public function Login()  {										// Login to Verisure Cloud with cookies
		
		$method = "GET";
		$headers = $this->setHeaders(null);
		$data = null;
		$this->workingDomain = $this->availableDomain[0];
		$url = $this->workingDomain.'/auth/login';
		
		$result = $this->doRequest($data, $method, $headers, $url);
		$httpRespCode = $result[0];
		$response = $result[1];

		if ($httpRespCode == 400 && json_decode($response, false)->errorGroup == "UNAUTHORIZED")   {
			$res = $this->LoginMFA();
			$this->fetchAllInstallations();
			return $res;
		}
		elseif ($httpRespCode != 200)   {
			$this->workingDomain = $this->availableDomain[1];
			$url = $this->workingDomain.'/auth/login';
			$result2 = $this->doRequest($data, $method, $headers, $url);
			$httpRespCode2 = $result2[0];
			$response2 = $result2[1];
			
			if ($httpRespCode == 400 && json_decode($response2, false)->errorGroup == "UNAUTHORIZED")   {
				$res = $this->LoginMFA();
				$this->fetchAllInstallations();
				return $res;
			}
			elseif ($httpRespCode2 != 200)   {
				return array("Verisure session error", $httpRespCode2, $response2);
			}
			else   {
				$date = $this->getExpirationDate($result2[2]);
				$this->fetchAllInstallations();
				return array($this->workingDomain, $httpRespCode2, $response2.'Token refreshed - '.$date);
			}
		}
		else   {
			$date = $this->getExpirationDate($result[2]);
			$this->fetchAllInstallations();
			return array($this->workingDomain, $httpRespCode, $response.'Token refreshed - '.$date);
		}
	}
	
	
	public function Logout()  {									// Logout to Verisure Cloud
		
		$method = "DELETE";
		$url = $this->workingDomain.'/auth/logout';
		$headers = $this->setHeaders(null);
		$data = null;
		$result = $this->doRequest($data, $method, $headers, $url);
		
		$httpRespCode = $result[0];
		$response = $result[1];
		
		if ( file_exists(dirname(__FILE__).'/../data/cookie.txt') ) { unlink(dirname(__FILE__).'/../data/cookie.txt'); }

		return array($httpRespCode, $response);
	}
	
	
	public function RequestMFA($type)  {	

		$method = "POST";
		$this->workingDomain = $this->availableDomain[0];
		$url = $this->workingDomain.'/auth/mfa?type='.$type;
		$headers = $this->setHeaders(null);
		$data = null;
		$result = $this->doRequest($data, $method, $headers, $url);
		
		$httpRespCode = $result[0];
		$response = $result[1];

		if ($httpRespCode != 200)   {
			$this->workingDomain = $this->availableDomain[1];
			$url = $this->workingDomain.'/auth/mfa?type='.$type;
			$result2 = $this->doRequest($data, $method, $headers, $url);
			$httpRespCode2 = $result2[0];
			$response2 = $result2[1];
			
			if ($httpRespCode2 != 200)   {
				return array("Verisure session error", $httpRespCode2, $response2);
			}
			else   {
				return array($this->workingDomain, $httpRespCode2, $response2);
			}
		}
		else   {
			return array($this->workingDomain, $httpRespCode, $response);
		}
	}


	public function ValidateMFA($code)  {	

		$method = "POST";
		$this->workingDomain = $this->availableDomain[0];
		$url = $this->workingDomain.'/auth/mfa/validate';
		$headers = $this->setHeaders(null);
		$data = json_encode(array('token' => $code));
		$result = $this->doRequest($data, $method, $headers, $url);
		
		$httpRespCode = $result[0];
		$response = $result[1];
				
		if ($httpRespCode != 200)   {
			$this->workingDomain = $this->availableDomain[1];
			$url = $this->workingDomain.'/auth/mfa/validate';
			$result2 = $this->doRequest($data, $method, $headers, $url);
			$httpRespCode2 = $result2[0];
			$response2 = $result2[1];
			
			if ($httpRespCode2 != 200)   {
				return array("Verisure session error", $httpRespCode2, $response2);
			}
			else   {
				return array($this->workingDomain, $httpRespCode2, $response2);
			}
		}
		else   {
			return array($this->workingDomain, $httpRespCode, $response);
		}
	}

	
	public function fetchAllInstallations()  {					// Get the giid number
		
		$method = "POST";
		$url = $this->workingDomain.$this->baseUrl;
		$headers = $this->setHeaders(null);
		$data = $this->setContent('fetchAllInstallations', null, null, null);
		$result = $this->doRequest($data, $method, $headers, $url);
		
		$httpRespCode = $result[0];
		$response = $result[1];
		
		if ($httpRespCode == 200)  {
        	$this->giid = json_decode($response, false)->{'data'}->{'account'}->{'installations'}[0]->{'giid'};		// Installation ID 0 by default
		}
      			
		return array($httpRespCode, $response);
	}
  
  
	public function ListDevices() {								// Get the list of available devices
		
		$method = "POST";
		$url = $this->workingDomain.$this->baseUrl;
		$headers = $this->setHeaders(null);
		$data = $this->setContent("Devices", null, null, null);
		$result = $this->doRequest($data, $method, $headers, $url);
		
		$httpRespCode = $result[0];
		$response = $result[1];
		
		return array($httpRespCode, $response);
	}
	
	
	public function getStateAlarm()  {							// Get the status of alarm
		
		$method = "POST";
		$url = $this->workingDomain.$this->baseUrl;
		$headers = $this->setHeaders(null);
		$data = $this->setContent('ArmState', null, null, null);
		$result = $this->doRequest($data, $method, $headers, $url);
		
		$httpRespCode = $result[0];
		$response = $result[1];
		      			
		return array($httpRespCode, $response);
	}

	
	public function getClimatesInformation()  {					// Get climates information
		
		$method = "POST";
		$url = $this->workingDomain.$this->baseUrl;
		$headers = $this->setHeaders(null);
		$data = $this->setContent('Climate', null, null, null);
		$result = $this->doRequest($data, $method, $headers, $url);
		
		$httpRespCode = $result[0];
		$response = $result[1];
		      			
		return array($httpRespCode, $response);
	}
  

	public function getDoorWindowsInformation()  {				// Get door/windows information
		
		$method = "POST";
		$url = $this->workingDomain.$this->baseUrl;
		$headers = $this->setHeaders(null);
		$data = $this->setContent('DoorWindow', null, null, null);
		$result = $this->doRequest($data, $method, $headers, $url);
		
		$httpRespCode = $result[0];
		$response = $result[1];
		      			
		return array($httpRespCode, $response);
	}


	public function getCamerasInformation()  {					// Get cameras information
		
		$method = "POST";
		$url = $this->workingDomain.$this->baseUrl;
		$headers = $this->setHeaders(null);
		$data = $this->setContent('Camera', null, null, null);
		$result = $this->doRequest($data, $method, $headers, $url);
		
		$httpRespCode = $result[0];
		$response = $result[1];
		      			
		return array($httpRespCode, $response);
	}


	public function getSmartplugsInformation()  {				// Get smartplugs information
		
		$method = "POST";
		$url = $this->workingDomain.$this->baseUrl;
		$headers = $this->setHeaders(null);
		$data = $this->setContent('SmartPlug', null, null, null);
		$result = $this->doRequest($data, $method, $headers, $url);
		
		$httpRespCode = $result[0];
		$response = $result[1];
		      			
		return array($httpRespCode, $response);
	}


	public function setStateAlarm($state)  {					// Set the status of alarm - disarm / armHome / armAway
		
		$method = "POST";
		$url = $this->workingDomain.$this->baseUrl;
		$headers = $this->setHeaders(null);
		$data = $this->setContent($state, null, null, null);
		$result = $this->doRequest($data, $method, $headers, $url);
		
		$httpRespCode = $result[0];
		$response = $result[1];
		      			
		return array($httpRespCode, $response);
	}
		
	
	public function setStateSmartplug($device_label, $state)  {				// Set the status of smartplugs - ON (True) / OFF (False)

		$method = "POST";
		$url = $this->workingDomain.$this->baseUrl;
		$headers = $this->setHeaders(null);
		$data = $this->setContent('UpdateState', $device_label, $state, null);
		$result = $this->doRequest($data, $method, $headers, $url);
		
		$httpRespCode = $result[0];
		$response = $result[1];
		      			
		return array($httpRespCode, $response);
	}


	public function getReportAlarm()  {	

		//$filter = ["INTRUSION", "FIRE", "SOS", "WATER", "ANIMAL", "TECHNICAL", "WARNING", "ARM", "DISARM", "LOCK", "UNLOCK", "PICTURE", "CLIMATE", "CAMERA_SETTINGS"];
		$filter = ["INTRUSION", "SOS", "ARM", "DISARM", "PICTURE"];

		$method = "POST";
		$url = $this->workingDomain.$this->baseUrl;
		$headers = $this->setHeaders(null);
		$data = $this->setContent('EventLog', $filter, null, null);
		$result = $this->doRequest($data, $method, $headers, $url);
		
		$httpRespCode = $result[0];
		$response = $result[1];
		      			
		return array($httpRespCode, $response);
	}

	public function captureImageRequest($device)  {					// Photos request)

		$method = "POST";
		$url = $this->workingDomain.$this->baseUrl;
		$headers = $this->setHeaders(null);
		$data = $this->setContent('CaptureImageRequest', $device, null, null);
		$result = $this->doRequest($data, $method, $headers, $url);
		
		$httpRespCode = $result[0];
		$response = $result[1];
		$res = json_decode($response, true);
		$requestId = $res['data']['CameraRequestImageCapture']['requestId'];

		if ( $httpRespCode == 200 && $res['data']['CameraRequestImageCapture']['requestId'] != "" ) {

			$retry = 20;
			$wait = "REQUESTED";
			While ( $retry > 0 && $wait != "COMPLETED")  {
				sleep(2);
				$method2 = "POST";
				$url2 = $this->workingDomain.$this->baseUrl;
				$headers2 = $this->setHeaders(null);
				$data2 = $this->setContent("ImageCaptureStatus", $device, $requestId, null);
				$result2 = $this->doRequest($data2, $method2, $headers2, $url2);
				
				$httpRespCode2 = $result2[0];
				$response2 = $result2[1];
				$res2 = json_decode($response2, true);
				$wait = $res2['data']['installation']['imageCaptureRequestStatus']['status'];
				$retry--;
			}

			if ( $res2['data']['installation']['imageCaptureRequestStatus']['status'] == "COMPLETED" )  {

				$method3 = "POST";
				$url3 = $this->workingDomain.$this->baseUrl;
				$headers3 = $this->setHeaders(null);
				$data3 = $this->setContent("Camera", null, null, null);
				$result3 = $this->doRequest($data3, $method3, $headers3, $url3);

				$httpRespCode3 = $result3[0];
				$response3 = $result3[1];
				$res3 = json_decode($response3, true);

				foreach ( $res3['data']['installation']['cameras'] as $cameras )  {
					if ( $cameras['device']['deviceLabel'] == $device ) { $urlDownload  = $cameras['latestCameraSeries']['image'][0]['url']; }
				}

				if ( $urlDownload != "" )  {

					$method4 = "GET";
					$url4 = $urlDownload;
					$headers4 = $this->setHeaders(null);
					$headers4[] = 'Accept: image/jpeg';
					$data4 = null;
					$result4 = $this->doRequest($data4, $method4, $headers4, $url4);

					$httpRespCode4 = $result4[0];
					$img = $result4[1];
				}
			}

			return array($httpRespCode, $response, $httpRespCode2, $response2, $httpRespCode3, $response3, $httpRespCode4, $img);
		}
	}
}

?>