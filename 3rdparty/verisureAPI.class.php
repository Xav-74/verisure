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


class verisureAPI {
	
    private $baseUrl;
	private $numinstall;
	private $username;
	private $password;
	private $country;
	private $language;
	private $panel;
	
	private $device_id;
	private $uuid;
	private $id_device_indigitall;
	private $apollo_operation_id;

	private $auth_otp_token;
	private $auth_otp_challenge;
	private $auth_otp_code;
	private $auth_token;
	private $refresh_token;

	private $capabilities;
	
	// Device specific configuration for the API
	const DEVICE_BRAND = "apple";
	const DEVICE_NAME = "iPhone14,7";
	const DEVICE_OS_VERSION = "17.2";
	const DEVICE_RESOLUTION = "";
	const DEVICE_TYPE = "";
	const DEVICE_VERSION = "10.102.0";
	const CALLBY = "OWI_10";
		
	
	public function __construct($numinstall, $username, $password, $country) {
		
		$this->numinstall = $numinstall;
		$this->username = $username;
		$this->password = $password;
		switch($country)  {
			case 1:					//Securitas Direct France
				$this->baseUrl = "https://customers.securitasdirect.fr/owa-api/graphql";
				$this->country = "FR";
				$this->language = "fr";
				break;
			case 2:					//Securitas Direct Spain
				$this->baseUrl = "https://customers.securitasdirect.es/owa-api/graphql";
				$this->country = "ES";
				$this->language = "es";
				break;
			case 3:					//Securitas Direct UK
				$this->baseUrl = "https://customers.verisure.co.uk/owa-api/graphql";
				$this->country = "GB";
				$this->language = "en";
				break;
			case 4:					//Securitas Direct Italy
				$this->baseUrl = "https://customers.verisure.it/owa-api/graphql";
				$this->country = "IT";
				$this->language = "it";
				break;
			case 5:					//Securitas Direct Portugal
				$this->baseUrl = "https://customers.securitasdirect.pt/owa-api/graphql";
				$this->country = "PT";
				$this->language = "pt";
				break;
		}
		
		$this->panel = "";

		$this->apollo_operation_id = bin2hex(random_bytes(64));
		$this->auth_otp_token = "";
		$this->auth_token = "";
		$this->auth_otp_challenge = false;
		$this->auth_otp_code = "";
		$this->refresh_token = "";
		$this->capabilities = "";
				
		if (file_exists(dirname(__FILE__).'/../data/device_'.$this->numinstall.'.json')) {
            $this->loadDevice();
		}
		else  {
			$this->createDevice();
		}
	}


	public function __destruct() {
	}
 
 
	private function doRequest($data, $method, $headers) {		//Execute all https request to Verisure Cloud
       
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL,				$this->baseUrl);
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST,	$method);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER,	true);
		curl_setopt($curl, CURLOPT_POSTFIELDS,		$data);
		curl_setopt($curl, CURLOPT_HEADER, 			true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, 		$headers);
		curl_setopt($curl, CURLOPT_VERBOSE, 		false);
					
		$result = curl_exec($curl);

		if (!$result) {
            throw new \Exception('Unable to retrieve data');
        }

        $httpRespCode  = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		$header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
      	$header = substr($result, 0, $header_size);
		$body = substr($result, $header_size);
      	curl_close($curl);
        
		return array($httpRespCode, $body);
	}


	private function setHeaders($operation) {		//Define headers
		
		$app = json_encode(array('app' => $this::DEVICE_VERSION, 'origine' => 'native'));
				
		$headers = array(
			'Content-Type: application/json',
			'app: '.$app,
			'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/102.0.5005.124 Safari/537.36 Edg/102.0.1245.41',
			'X-APOLLO-OPERATION-ID: '.$this->apollo_operation_id,
			'X-APOLLO-OPERATION-NAME: '.$operation,
			'extension: {"mode":"full"}',
		);
		
		if ($this->auth_otp_challenge == true) {
			$auth = array(
				'user' => $this->username,
				'id' => $this->generateId(),
				'country' => $this->country,
				'lang' => $this->language,
				'callby' => $this::CALLBY,
				'hash' => null,
				'refreshToken' => null
			);
			$headers[] = 'auth: '.json_encode($auth);

			if ($this->auth_otp_token != "" && $this->auth_otp_code != "") {
				$auth = array(
					'token' => $this->auth_otp_code,
                	'type' => 'OTP',
                	'otpHash' => $this->auth_otp_token
				);
				$headers[] = 'security: '.json_encode($auth);
			}
		}

		if ($this->auth_token != "") {
			$auth = array(
				'user' => $this->username,
				'id' => $this->generateId(),
				'country' => $this->country,
				'lang' => $this->language,
				'callby' => $this::CALLBY,
				'hash' => $this->auth_token,
			);
			$headers[] = 'auth: '.json_encode($auth);
			$headers[] = 'numinst: '.strval($this->numinstall);
			$headers[] = 'panel: '.$this->panel;
			$headers[] = 'x-capabilities: '.$this->capabilities;
		}

		//log::add('verisure', 'debug', '| Headers = '.str_replace('\\','',json_encode($headers)));
		return $headers;
	}	


	private function setContent($operation, $data1, $data2, $data3) {		//Set content for https request to Verisure Cloud
		
		$content = "";
		switch($operation) {
			
			case "mkLoginToken":
				$content = array(
					'operationName' => 'mkLoginToken',
					'variables' => array(
						'user' => $this->username,
						'password' => $this->password,
						'id' => $this->generateId(),
						'country' => $this->country,
						'lang' => $this->language,
						'callby' => $this::CALLBY,
						'idDevice' => $this->device_id,
						'idDeviceIndigitall' => $this->id_device_indigitall,
						'deviceType' => $this::DEVICE_TYPE,
						'deviceVersion' => $this::DEVICE_VERSION,
						'deviceResolution' => $this::DEVICE_RESOLUTION,
						'deviceName' => $this::DEVICE_NAME,
						'deviceBrand' => $this::DEVICE_BRAND,
						'deviceOsVersion' => $this::DEVICE_OS_VERSION,
						'uuid' => $this->uuid
					),
					'query' => 'mutation mkLoginToken($user: String!, $password: String!, $id: String!, $country: String!, $lang: String!, $callby: String!, $idDevice: String!, $idDeviceIndigitall: String!, $deviceType: String!, $deviceVersion: String!, $deviceResolution: String!, $deviceName: String!, $deviceBrand: String!, $deviceOsVersion: String!, $uuid: String!) { xSLoginToken(user: $user, password: $password, country: $country, lang: $lang, callby: $callby, id: $id, idDevice: $idDevice, idDeviceIndigitall: $idDeviceIndigitall, deviceType: $deviceType, deviceVersion: $deviceVersion, deviceResolution: $deviceResolution, deviceName: $deviceName, deviceBrand: $deviceBrand, deviceOsVersion: $deviceOsVersion, uuid: $uuid) { __typename res msg hash refreshToken legals changePassword needDeviceAuthorization mainUser } }',
				);
			break;

			case "Logout":
				$content = array(
					'operationName' => 'Logout',
					'variables' => array(),
					'query' => 'mutation Logout { xSLogout }'
				);
			break;

			case "mkValidateDevice":
				$content = array(
					'operationName' => "mkValidateDevice",
					'variables' => array(
						'idDevice' => $this->device_id,
						'idDeviceIndigitall' => $this->id_device_indigitall,
						'uuid' => $this->uuid,
						'deviceName' => $this::DEVICE_NAME,
						'deviceBrand' => $this::DEVICE_BRAND,
						'deviceOsVersion' => $this::DEVICE_OS_VERSION,
						'deviceVersion' => $this::DEVICE_VERSION
					),
					'query' => 'mutation mkValidateDevice($idDevice: String, $idDeviceIndigitall: String, $uuid: String, $deviceName: String, $deviceBrand: String, $deviceOsVersion: String, $deviceVersion: String) { xSValidateDevice(idDevice: $idDevice, idDeviceIndigitall: $idDeviceIndigitall, uuid: $uuid, deviceName: $deviceName, deviceBrand: $deviceBrand, deviceOsVersion: $deviceOsVersion, deviceVersion: $deviceVersion) { res msg hash refreshToken legals } }',
				);
			break;

			case "mkSendOTP":
				$content = array(
					'operationName' => "mkSendOTP",
					'variables' => array(
						'recordId' => (int)$data1,
                		"otpHash" => $this->auth_otp_token
					),
					'query' => 'mutation mkSendOTP($recordId: Int!, $otpHash: String!) { xSSendOtp(recordId: $recordId, otpHash: $otpHash) { res msg } }',
				);
			break;

			case "RefreshLogin":
				$content = array(
					'operationName' => "RefreshLogin",
					'variables' => array(
						'user' => $this->username,
						'password' => $this->password,
						'id' => $this->generateId(),
						'country' => $this->country,
						'lang' => $this->language,
						'callby' => $this::CALLBY,
						'idDevice' => $this->device_id,
						'idDeviceIndigitall' => $this->id_device_indigitall,
						'deviceType' => $this::DEVICE_TYPE,
						'deviceVersion' => $this::DEVICE_VERSION,
						'deviceResolution' => $this::DEVICE_RESOLUTION,
						'deviceName' => $this::DEVICE_NAME,
						'deviceBrand' => $this::DEVICE_BRAND,
						'deviceOsVersion' => $this::DEVICE_OS_VERSION,
						'uuid' => $this->uuid,
						'refreshToken' => $this->refresh_token
					),
					'query' => 'mutation RefreshLogin($refreshToken: String!, $id: String!, $country: String!, $lang: String!, $callby: String!, $idDevice: String!, $idDeviceIndigitall: String!, $deviceType: String!, $deviceVersion: String!, $deviceResolution: String!, $deviceName: String!, $deviceBrand: String!, $deviceOsVersion: String!, $uuid: String!) { xSRefreshLogin(refreshToken: $refreshToken, id: $id, country: $country, lang: $lang, callby: $callby, idDevice: $idDevice, idDeviceIndigitall: $idDeviceIndigitall, deviceType: $deviceType, deviceVersion: $deviceVersion, deviceResolution: $deviceResolution, deviceName: $deviceName, deviceBrand: $deviceBrand, deviceOsVersion: $deviceOsVersion, uuid: $uuid) { __typename res msg hash refreshToken legals changePassword needDeviceAuthorization mainUser } }',
				);
			break;
			
			case "Srv":
				$content = array(
					'operationName' =>  "Srv",
					'variables' => array(
						'numinst' => $this->numinstall,
						'uuid' => $this->uuid,
					),
					'query' => 'query Srv($numinst: String!, $uuid: String) { xSSrv(numinst: $numinst, uuid: $uuid) { res msg language installation { id numinst alias status panel sim instIbs services { id idService active visible bde isPremium codOper totalDevice request multipleReq numDevicesMr secretWord minWrapperVersion description unprotectActive unprotectDeviceStatus instDate genericConfig { total attributes { key value } } devices { id code numDevices cost type name } camerasArlo { id model connectedToInstallation usedForAlarmVerification offer name locationHint batteryLevel connectivity createdDate modifiedDate latestThumbnailUri } attributes { name attributes { name value active } } listdiy { idMant state } listprompt { idNot text type customParam alias } } configRepoUser { alarmPartitions { id enterStates leaveStates } } capabilities } } }',
				);
			break;
			
			case "mkInstallationList":
				$content = array(
					'operationName' =>  "mkInstallationList",
					'query' => 'query mkInstallationList { xSInstallations { installations { numinst alias panel type name surname address city postcode province email phone } } }',
				);
			break;
			
			case "xSDeviceList":
				$content = array(
					'operationName' =>  "xSDeviceList",
					'variables' => array(
						'numinst' => $this->numinstall,
						'panel' => $this->panel
					),
					'query' => 'query xSDeviceList($numinst: String!, $panel: String!) { xSDeviceList(numinst: $numinst, panel: $panel) { res devices { id code name type subtype remoteUse idService } } }',
				);
			break;

			case "CheckAlarm":
				$content = array(
					'operationName' =>  "CheckAlarm",
					'variables' => array(
						'numinst' => $this->numinstall,
						'panel' => $this->panel
					),
					'query' => 'query CheckAlarm($numinst: String!, $panel: String!) { xSCheckAlarm(numinst: $numinst, panel: $panel) { res msg referenceId } }',
				);
			break;

			case "CheckAlarmStatus":
				$content = array(
					'operationName' =>  "CheckAlarmStatus",
					'variables' => array(
						'numinst' => $this->numinstall,
						'panel' => $this->panel,
						'idService' => "11",
						'referenceId' => $data1,
						'counter' => (int)$data2
					),
					'query' => 'query CheckAlarmStatus($numinst: String!, $idService: String!, $panel: String!, $referenceId: String!) { xSCheckAlarmStatus(numinst: $numinst, idService: $idService, panel: $panel, referenceId: $referenceId) { res msg status numinst protomResponse protomResponseDate forcedArmed } }',
				);
			break;

			case "xSArmPanel":
				$content = array(
					'operationName' =>  "xSArmPanel",
					'variables' => array(
						'numinst' => $this->numinstall,
						'panel' => $this->panel,
						'request' => $data1,
						'currentStatus' => $data2
					),
					'query' => 'mutation xSArmPanel($numinst: String!, $request: ArmCodeRequest!, $panel: String!, $currentStatus: String) { xSArmPanel(numinst: $numinst, request: $request, panel: $panel, currentStatus: $currentStatus) { res msg referenceId } }',
				);
			break;

			case "ArmStatus":
				$content = array(
					'operationName' =>  "ArmStatus",
					'variables' => array(
						'numinst' => $this->numinstall,
						'panel' => $this->panel,
						'request' => $data1,
						'referenceId' => $data2,
						'counter' => (int)$data3
					),
					'query' => 'query ArmStatus($numinst: String!, $request: ArmCodeRequest, $panel: String!, $referenceId: String!, $counter: Int!) { xSArmStatus(numinst: $numinst, panel: $panel, referenceId: $referenceId, counter: $counter, request: $request) { res msg status protomResponse protomResponseDate numinst requestId error { code type allowForcing exceptionsNumber referenceId } } }',
				);
			break;

			case "xSDisarmPanel":
				$content = array(
					'operationName' =>  "xSDisarmPanel",
					'variables' => array(
						'numinst' => $this->numinstall,
						'panel' => $this->panel,
						'request' => "DARM1",
						'currentStatus' => $data1
					),
					'query' => 'mutation xSDisarmPanel($numinst: String!, $request: DisarmCodeRequest!, $panel: String!) { xSDisarmPanel(numinst: $numinst, request: $request, panel: $panel) { res msg referenceId } }',
				);
			break;

			case "DisarmStatus":
				$content = array(
					'operationName' =>  "DisarmStatus",
					'variables' => array(
						'numinst' => $this->numinstall,
						'panel' => $this->panel,
						'request' => "DARM1",
						'referenceId' => $data1,
						'counter' => (int)$data2
					),
					'query' => 'query DisarmStatus($numinst: String!, $panel: String!, $referenceId: String!, $counter: Int!, $request: DisarmCodeRequest) { xSDisarmStatus(numinst: $numinst, panel: $panel, referenceId: $referenceId, counter: $counter, request: $request) { res msg status protomResponse protomResponseDate numinst requestId error { code type allowForcing exceptionsNumber referenceId } } }',
				);
			break;

			case "ActV2Home":
				$content = array(
					'operationName' =>  "ActV2Home",
					'variables' => array(
						'numinst' => $this->numinstall,
						'panel' => $this->panel,
						'numRows' => 50,
						'offset' => 0,
						'hasLocksmithRequested' => false,
						'singleActivityFilter' => $data1
					),
					'query' => 'query ActV2Home($numinst: String!, $numRows: Int, $offset: Int, $hasLocksmithRequested: Boolean, $singleActivityFilter: [Int], $panel: String) { xSActV2(numinst: $numinst, input: {timeFilter: ALL, numRows: $numRows, offset: $offset, hasLocksmithRequested: $hasLocksmithRequested, singleActivityFilter: $singleActivityFilter, panel: $panel}) { reg { alias type device source idSignal myVerisureUser time img signalType } } }',
				);
			break;

			case "RequestImages":
				$content = array(
					'operationName' =>  "RequestImages",
					'variables' => array(
						'numinst' => $this->numinstall,
						'panel' => $this->panel,
						'devices' => array( (int)$data1 ),
						'mediaType' => 1,
						'resolution' => 0,
						'deviceType' => 106
					),
					'query' => 'mutation RequestImages($numinst: String!, $panel: String!, $devices: [Int]!, $mediaType: Int, $resolution: Int, $deviceType: Int) { xSRequestImages(numinst: $numinst, panel: $panel, devices: $devices, mediaType: $mediaType, resolution: $resolution, deviceType: $deviceType) { res msg referenceId } }',
				);
			break;

			case "RequestImagesStatus":
				$content = array(
					'operationName' =>  "RequestImagesStatus",
					'variables' => array(
						'numinst' => $this->numinstall,
						'panel' => $this->panel,
						'devices' => array( (int)$data1 ),
						'referenceId' => $data2,
						'counter' => (int)$data3
					),
					'query' => 'query RequestImagesStatus($numinst: String!, $panel: String!, $devices: [Int!]!, $referenceId: String!, $counter: Int) { xSRequestImagesStatus(numinst: $numinst, panel: $panel, devices: $devices, referenceId: $referenceId, counter: $counter) { res msg numinst status } }',
				);
			break;

			case "mkGetPhotoImages":
				$content = array(
					'operationName' =>  "mkGetPhotoImages",
					'variables' => array(
						'numinst' => $this->numinstall,
						'panel' => $this->panel,
						'signalType' => "16",
						'idSignal' => $data1
					),
					'query' => 'query mkGetPhotoImages($numinst: String!, $idSignal: String!, $signalType: String!, $panel: String!) { xSGetPhotoImages(numinst: $numinst, idsignal: $idSignal, signaltype: $signalType, panel: $panel) { devices { id code name images { id image type } } } }',
				);
			break;
		}

		//log::add('verisure', 'debug', '| Content = '.json_encode($content));
		return json_encode($content);
    }


	private function generateId() {

		//date_default_timezone_set('UTC');
		$now = date('YndHis');
		$id = 'OWI_______________'.$this->username.'_______________'.$now.'0';
		return $id;
	}


	private function createDevice() {

		$this->uuid = (string)substr( str_replace("-","",$this->gen_uuid4()), 0, 16);
		$this->device_id = (string)$this->gen_devide_id();
		$this->id_device_indigitall = (string)$this->gen_uuid4();
		$this->saveDevice();
	}


	private function loadDevice() {
	
		$array = json_decode(file_get_contents(dirname(__FILE__).'/../data/device_'.$this->numinstall.'.json'), true);
		$this->uuid = $array['uuid'];
		$this->device_id = $array['device_id'];
		$this->id_device_indigitall = $array['id_device_indigitall'];
		$this->auth_otp_token = $array['auth_opt_token'];
		$this->auth_token = $array['auth_token'];
		$this->refresh_token = $array['refresh_token'];
		$this->panel = $array['panel'];
		$this->capabilities = $array['capabilities'];
		//log::add('verisure', 'debug', '| Device file loaded');
	}


	private function saveDevice() {
		
		$array = array(
			'uuid' => $this->uuid,
			'device_id' => $this->device_id,
			'id_device_indigitall' => $this->id_device_indigitall,
			'auth_opt_token' => $this->auth_otp_token,
			'auth_token' => $this->auth_token,
			'refresh_token' => $this->refresh_token,
			'panel' => $this->panel,
			'capabilities' => $this->capabilities
		);
		file_put_contents(dirname(__FILE__).'/../data/device_'.$this->numinstall.'.json', json_encode($array));
		//log::add('verisure', 'debug', '| Device file saved');
	}
	

	private function gen_uuid4() {
    
		$data = random_bytes(16);
		assert(strlen($data) == 16);
		$data[6] = chr(ord($data[6]) & 0x0f | 0x40);	// set version to 0100
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80);	// set bits 6-7 to 10
    	return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}

	
	private function gen_devide_id() {
    
		$data1 = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');	//base64 urlsafe encode
		$data2 = rtrim(strtr(base64_encode(random_bytes(130)), '+/', '-_'), '=');	//base64 urlsafe encode
		return $data1.":APA91b".substr($data2, 0, 134);
	}

	
	public function Login() {			// Login to Verisure Cloud
		
		$method = "POST";
		$headers = $this->setHeaders("mkLoginToken");
		$content = $this->setContent("mkLoginToken", null, null, null);
		
		$result = $this->doRequest($content, $method, $headers);
		$httpRespCode = $result[0];
		$response = $result[1];
		log::add('verisure', 'debug', '│ Request mkLoginToken - httpRespCode => '.$httpRespCode.' - response => '.$response);  
		
		$res = json_decode($response, true);
		if ( $res['data']['xSLoginToken']['needDeviceAuthorization'] == false ) {
			$this->auth_token = $res['data']['xSLoginToken']['hash'];
			$this->refresh_token = $res['data']['xSLoginToken']['refreshToken'];
			$this->Overview();
			$this->saveDevice();
		}

		return array($httpRespCode, $response);
	}


	public function Logout() {			// Logout to verisure Cloud
		
		$method = "POST";
		$headers = $this->setHeaders("Logout");
		$content = $this->setContent("Logout", null, null, null);
		
		$result = $this->doRequest($content, $method, $headers);
		$httpRespCode = $result[0];
		$response = $result[1];
		log::add('verisure', 'debug', '│ Request Logout - httpRespCode => '.$httpRespCode.' - response => '.$response);

		return array($httpRespCode, $response);
	}


	public function ValidateDevice($code) {			// Validate the device to access information
		
		$this->auth_otp_challenge = true;
		$this->auth_otp_code = $code;

		$method = "POST";
		$headers = $this->setHeaders("mkValidateDevice");
		$content = $this->setContent("mkValidateDevice", null, null, null);
		
		$result = $this->doRequest($content, $method, $headers);
		$httpRespCode = $result[0];
		$response = $result[1];
		log::add('verisure', 'debug', '│ Request mkValidateDevice - httpRespCode => '.$httpRespCode.' - response => '.$response);  
		
		$res = json_decode($response, true);
		if ( $res['errors'][0]['message'] == "Unauthorized" ) {
			$this->auth_otp_token = $res['errors'][0]['data']['auth-otp-hash'];
			$this->saveDevice();
		}
		if ( $res['data']['xSValidateDevice']['res'] == "OK" ) {
			$this->auth_token = $res['data']['xSValidateDevice']['hash'];
			$this->saveDevice();
		}

		$this->auth_otp_challenge = false;

		return array($httpRespCode, $response);
	}

	
	public function SendOTP($phone_id) {			// Send request to obtain 2FA code
		
		$this->auth_otp_challenge = true;
		
		$method = "POST";
		$headers = $this->setHeaders("mkSendOTP");
		$content = $this->setContent("mkSendOTP", $phone_id, null, null);
		
		$result = $this->doRequest($content, $method, $headers);
		$httpRespCode = $result[0];
		$response = $result[1];  
		log::add('verisure', 'debug', '│ Request mkSendOTP - httpRespCode => '.$httpRespCode.' - response => '.$response);

		$this->auth_otp_challenge = false;

		return array($httpRespCode, $response);
	}


	public function RefreshToken() {			// Refresh the token
		
		$this->auth_otp_challenge = true;
		
		$method = "POST";
		$headers = $this->setHeaders("RefreshLogin");
		$content = $this->setContent("RefreshLogin", null, null, null);
		
		$result = $this->doRequest($content, $method, $headers);
		$httpRespCode = $result[0];
		$response = $result[1];  
		log::add('verisure', 'debug', '│ Request RefreshLogin - httpRespCode => '.$httpRespCode.' - response => '.$response);
		
		$res = json_decode($response, true);
		if ( $res['data']['xSRefreshLogin']['needDeviceAuthorization'] == false ) {
			$this->auth_token = $res['data']['xSRefreshLogin']['hash'];
			$this->refresh_token = $res['data']['xSRefreshLogin']['refreshToken'];
			$this->saveDevice();
		}

		$this->auth_otp_challenge = false;

		return array($httpRespCode, $response);
	}
	

	public function Overview() {			// Get the information to the all installation
		
		$method = "POST";
		$headers = $this->setHeaders("Srv");
		$content = $this->setContent("Srv", null, null, null);
		
		$result = $this->doRequest($content, $method, $headers);
		$httpRespCode = $result[0];
		$response = $result[1];
		log::add('verisure', 'debug', '│ Request Srv - httpRespCode => '.$httpRespCode.' - response => '.$response);

		$res = json_decode($response, true);
		if ( $res['data']['xSSrv']['installation']['capabilities'] != "" ) {
			$this->capabilities = $res['data']['xSSrv']['installation']['capabilities'];
		}

		return array($httpRespCode, $response);
	}


	public function ListInstallations() {			// Get the list of available installations
		
		$method = "POST";
		$headers = $this->setHeaders("mkInstallationList");
		$content = $this->setContent("mkInstallationList", null, null, null);
		
		$result = $this->doRequest($content, $method, $headers);
		$httpRespCode = $result[0];
		$response = $result[1];
		log::add('verisure', 'debug', '│ Request mkInstallationList - httpRespCode => '.$httpRespCode.' - response => '.$response);
		
		$res = json_decode($response, true);
		if ( $res['data']['xSInstallations']['installations'] != "" ) {
			
			foreach ( $res['data']['xSInstallations']['installations'] as $installation )  {
				if ( $installation['numinst'] == $this->numinstall ) { $this->panel = $installation['panel']; }
			}
			$this->saveDevice();
		}
		
		return array($httpRespCode, $response);
	}

	
	public function ListDevices() {			// Get the list of available devices
		
		$method = "POST";
		$headers = $this->setHeaders("xSDeviceList");
		$content = $this->setContent("xSDeviceList", null, null, null);
		
		$result = $this->doRequest($content, $method, $headers);
		$httpRespCode = $result[0];
		$response = $result[1];
		log::add('verisure', 'debug', '│ Request xSDeviceList - httpRespCode => '.$httpRespCode.' - response => '.$response);

		return array($httpRespCode, $response);
	}


	public function GetStateAlarm()  {			// Get the status of the alarm

		$method = "POST";
		$headers = $this->setHeaders("CheckAlarm");
		$content = $this->setContent("CheckAlarm", null, null, null);
		
		$result = $this->doRequest($content, $method, $headers);
		$httpRespCode = $result[0];
		$response = $result[1];
		log::add('verisure', 'debug', '│ Request CheckAlarm - httpRespCode => '.$httpRespCode.' - response => '.$response);

		$res = json_decode($response, true);
		$referenceId = $res['data']['xSCheckAlarm']['referenceId'];
		if ( $res['data']['xSCheckAlarm']['res'] == "OK" ) {

			$counter = 1;
			$wait = "WAIT";
			While ($wait == "WAIT")  {
				sleep(2);
				$method2 = "POST";
				$headers2 = $this->setHeaders("CheckAlarmStatus");
				$content2 = $this->setContent("CheckAlarmStatus", $referenceId, $counter, null);
				
				$result2 = $this->doRequest($content2, $method2, $headers2);
				$httpRespCode2 = $result2[0];
				$response2 = $result2[1];
				
				$res2 = json_decode($response2, true);
				$wait = $res2['data']['xSCheckAlarmStatus']['res'];
				$counter++;
			}
		}
		log::add('verisure', 'debug', '│ Request CheckAlarmStatus - httpRespCode => '.$httpRespCode2.' - response => '.$response2);
		
		return array($httpRespCode, $response, $httpRespCode2, $response2);
	}


	public function ArmAlarm($mode, $currentStatus)  {			// Arm the alarm in mode total, day, night, peri

		$method = "POST";
		$headers = $this->setHeaders("xSArmPanel");
		$content = $this->setContent("xSArmPanel", $mode, $currentStatus, null);
		
		$result = $this->doRequest($content, $method, $headers);
		$httpRespCode = $result[0];
		$response = $result[1];
		log::add('verisure', 'debug', '│ Request xSArmPanel - httpRespCode => '.$httpRespCode.' - response => '.$response);

		$res = json_decode($response, true);
		$referenceId = $res['data']['xSArmPanel']['referenceId'];
		if ( $res['data']['xSArmPanel']['res'] == "OK" ) {

			$counter = 1;
			$wait = "WAIT";
			While ($wait == "WAIT")  {
				sleep(1);
				$method2 = "POST";
				$headers2 = $this->setHeaders("ArmStatus");
				$content2 = $this->setContent("ArmStatus", $mode, $referenceId, $counter);
				
				$result2 = $this->doRequest($content2, $method2, $headers2);
				$httpRespCode2 = $result2[0];
				$response2 = $result2[1];
				
				$res2 = json_decode($response2, true);
				$wait = $res2['data']['xSArmStatus']['res'];
				$counter++;
			}
		}
		log::add('verisure', 'debug', '│ Request ArmStatus - httpRespCode => '.$httpRespCode2.' - response => '.$response2);
		
		return array($httpRespCode, $response, $httpRespCode2, $response2);
	}


	public function DisarmAlarm($currentStatus)  {			// Disarm the alarm (all mode)

		$method = "POST";
		$headers = $this->setHeaders("xSDisarmPanel");
		$content = $this->setContent("xSDisarmPanel", $currentStatus, null, null);
		
		$result = $this->doRequest($content, $method, $headers);
		$httpRespCode = $result[0];
		$response = $result[1];
		log::add('verisure', 'debug', '│ Request xSDisarmPanel - httpRespCode => '.$httpRespCode.' - response => '.$response);

		$res = json_decode($response, true);
		$referenceId = $res['data']['xSDisarmPanel']['referenceId'];
		if ( $res['data']['xSDisarmPanel']['res'] == "OK" ) {

			$counter = 1;
			$wait = "WAIT";
			While ($wait == "WAIT")  {
				sleep(1);
				$method2 = "POST";
				$headers2 = $this->setHeaders("DisarmStatus");
				$content2 = $this->setContent("DisarmStatus", $referenceId, $counter, null);
				
				$result2 = $this->doRequest($content2, $method2, $headers2);
				$httpRespCode2 = $result2[0];
				$response2 = $result2[1];
								
				$res2 = json_decode($response2, true);
				$wait = $res2['data']['xSDisarmStatus']['res'];
				$counter++;
			}
		}
		log::add('verisure', 'debug', '│ Request DisarmStatus - httpRespCode => '.$httpRespCode2.' - response => '.$response2);

		return array($httpRespCode, $response, $httpRespCode2, $response2);
	}


	public function GetReportAlarm($filter)  {			// Get the information of last actions
		
		if ( $filter == null ) { $filter = [1,2,13,16,24,25,26,29,31,32,40,46,202,203,204,311]; }
		
		$method = "POST";
		$headers = $this->setHeaders("ActV2Home");
		$content = $this->setContent("ActV2Home", $filter, null, null);
		
		$result = $this->doRequest($content, $method, $headers);
		$httpRespCode = $result[0];
		$response = $result[1];
		log::add('verisure', 'debug', '│ Request ActV2Home - httpRespCode => '.$httpRespCode.' - response => '.$response);

		return array($httpRespCode, $response);
	}

	
	public function GetPhotosRequest($device)  {	// Photos request
		
		$method = "POST";
		$headers = $this->setHeaders("RequestImages");
		$content = $this->setContent("RequestImages", $device, null, null);
		
		$result = $this->doRequest($content, $method, $headers);
		$httpRespCode = $result[0];
		$response = $result[1];
		log::add('verisure', 'debug', '│ Request RequestImages - httpRespCode => '.$httpRespCode.' - response => '.$response);

		$res = json_decode($response, true);
		$referenceId = $res['data']['xSRequestImages']['referenceId'];
		if ( $res['data']['xSRequestImages']['res'] == "OK" ) {

			$counter = 1;
			$wait = "WAIT";
			While ($wait == "WAIT")  {
				sleep(1);
				$method2 = "POST";
				$headers2 = $this->setHeaders("RequestImagesStatus");
				$content2 = $this->setContent("RequestImagesStatus", $device, $referenceId, $counter);
				
				$result2 = $this->doRequest($content2, $method2, $headers2);
				$httpRespCode2 = $result2[0];
				$response2 = $result2[1];
				
				$res2 = json_decode($response2, true);
				$wait = $res2['data']['xSRequestImagesStatus']['res'];
				$counter++;
			}
			log::add('verisure', 'debug', '│ Request RequestImagesStatus - httpRespCode => '.$httpRespCode2.' - response => '.$response2);

			if ( $res2['data']['xSRequestImagesStatus']['res'] == "OK" ) {

				$now = date("Y-m-d H:i:s");
				$report_check = false;
				$retry = 10;
				While ( $retry > 0 && $report_check != true ) {
					sleep(5);
					$result3 = $this->GetReportAlarm([16]);
					$httpRespCode3 = $result3[0];
					$response3 = $result3[1];
					$res3 = json_decode($response3, true)['data']['xSActV2'];
					if ( $now < $res3['reg'][0]['time'] )  {
						$report_check = true;
						$idSignal = $res3['reg'][0]['idSignal'];
					}
					$retry--;	
				}
				
				$method4 = "POST";
				$headers4 = $this->setHeaders("mkGetPhotoImages");
				$content4 = $this->setContent("mkGetPhotoImages", $idSignal, null, null);
				
				$result4 = $this->doRequest($content4, $method4, $headers4);
				$httpRespCode4 = $result4[0];
				$response4 = $result4[1];
				log::add('verisure', 'debug', '│ Request mkGetPhotoImages - httpRespCode => '.$httpRespCode4.' - response => '.$response4);

				$res4 = json_decode($response4, true);
				$img = $res4['data']['xSGetPhotoImages']['devices'][0]['images'][0]['image'];
			}
		}

		return array($httpRespCode, $response, $httpRespCode2, $response2, $httpRespCode3, $response3, $httpRespCode4, $response4, $img);
	}
}

?>