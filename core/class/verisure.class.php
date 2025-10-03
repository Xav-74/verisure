<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';
define('__PLGBASE__', dirname(dirname(__DIR__)));

if (!class_exists('verisureAPI')) {
	require_once __DIR__ . '/../../3rdparty/verisureAPI.class.php';
}

if (!class_exists('verisureAPI2')) {
	require_once __DIR__ . '/../../3rdparty/verisureAPI2.class.php';
}

class verisure extends eqLogic {
	
    /*     * *************************Attributs****************************** */

	public static $_widgetPossibility = array(
		'custom' => true,
		//'custom::layout' => false,
		'parameters' => array(),
	);
	
	public function decrypt() {
		$this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
	}

	public function encrypt() {
		$this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
	}

    /*     * ***********************Methode static*************************** */
    
    public static function cron30() {
		
		foreach (eqLogic::byType('verisure', true) as $verisure) {									// type = verisure et eqLogic enable
			$cmdState = $verisure->getCmd(null, 'getstate');		
			if (!is_object($cmdState) || $verisure->getConfiguration('nb_smartplug') == "") {		// Si la commande n'existe pas ou condition non respectée
			  	continue; 																			// continue la boucle
			}
			log::add('verisure', 'debug', 'Exécution du cron30');
			$cmdState->execCmd(); 																	// la commande existe on la lance
		}	
	}

	public static function getConfigForCommunity() {

		$index = 1;
		$CommunityInfo = "```\n";
		foreach (eqLogic::byType('verisure', true) as $verisure)  {
			$CommunityInfo = $CommunityInfo . "Alarm #" . $index . " - Type : ". $verisure->getConfiguration('alarmtype') . "\n";
			$index++;
		}
		$CommunityInfo = $CommunityInfo . "```";
		return $CommunityInfo;
	}	

    /*     * *********************Méthodes d'instance************************* */

    /* fonction appelée pendant la séquence de sauvegarde avant l'insertion 
     * dans la base de données pour une nouvelle entrée */
    public function preInsert() {
	}

	/* fonction appelée pendant la séquence de sauvegarde après l'insertion 
     * dans la base de données pour une nouvelle entrée */
    public function postInsert() {
    }

	 /* fonction appelée avant le début de la séquence de sauvegarde */
    public function preSave() {
    }

	/* fonction appelée après la fin de la séquence de sauvegarde */
    public function postSave() {
		
		$this->createCmd('enable', 'Etat Activation', 1, 'info', 'binary', 1, 0, ['generic_type', 'ALARM_ENABLE_STATE'], [], ['dashboard', 'lock'], ['mobile', 'lock']);	//0 = désarmée - 1 = armée
		$this->createCmd('state', 'Etat Alarme', 2, 'info', 'binary', 1, 0, ['generic_type', 'ALARM_STATE'], ['invertBinary', 1], ['dashboard', 'alert'], ['mobile', 'alert']);		//0 = normale - 1 = déclenchée
		$this->createCmd('mode', 'Mode Alarme', 3, 'info', 'string', 1, 0, ['generic_type', 'ALARM_MODE'], [], ['dashboard', 'tile'], ['mobile', 'tile']);
		$this->createCmd('armed', 'Mode Total', 4, 'action', 'other', 1, 0, ['generic_type', 'ALARM_ARMED'], [], [], []);
		$this->createCmd('released', 'Désactiver', 5, 'action', 'other', 1, 0, ['generic_type', 'ALARM_RELEASED'], [], [], []);
		$this->createCmd('getstate', 'Rafraichir', 6, 'action', 'other', 1, 0, [], [], [], []);			
				
		if ( $this->getConfiguration('alarmtype') == 1 )   { 
		
			$this->createCmd('armed_night', 'Mode Nuit', 7, 'action', 'other', 1, 0, ['generic_type', 'ALARM_SET_MODE'], [], [], []);
			$this->createCmd('armed_day', 'Mode Jour', 8, 'action', 'other', 1, 0, ['generic_type', 'ALARM_SET_MODE'], [], [], []);
			$this->createCmd('armed_ext', 'Mode Extérieur', 9, 'action', 'other', 1, 0, [], [], [], []);
			$this->createCmd('getpictures', 'Demande Images', 10, 'action', 'select', 1, 0, [], [], [], []);
			$this->createCmd('networkstate', 'Qualité Réseau', 11, 'info', 'numeric', 1, 0, [], [], [], []);
		}
		
		if ( $this->getConfiguration('alarmtype') == 2 )   { 
			
			$this->createCmd('armed_home', 'Mode Partiel', 7, 'action', 'other', 1, 0, ['generic_type', 'ALARM_SET_MODE'], [], [], []);
			$this->createCmd('getpictures', 'Demande Images', 8, 'action', 'select', 1, 0, [], [], [], []);
			
			$device_array = $this->getConfiguration('devices');
			$order = 9;
			//Création des 3 commandes des smartPlugs
			for ($j = 0; $j < $this->getConfiguration('nb_smartplug'); $j++)  {
				if ($device_array['smartplugType'.$j] == "smartPlugDevice")  {
					$this->createCmd($device_array['smartplugID'.$j].'::State', 'Smartplug '.$device_array['smartplugName'.$j].' Etat', $order, 'info', 'binary', 0, 0, ['generic_type', 'ENERGY_STATE'], [], [], []);	
					$order++;
					$this->createCmd($device_array['smartplugID'.$j].'::On', 'Smartplug '.$device_array['smartplugName'.$j].' On', $order, 'action', 'other', 0, 0, ['generic_type', 'ENERGY_ON'], [], [], []);
					$order++;
					$this->createCmd($device_array['smartplugID'.$j].'::Off', 'Smartplug '.$device_array['smartplugName'.$j].' Off', $order, 'action', 'other', 0, 0, ['generic_type', 'ENERGY_OFF'], [], [], []);
					$order++;	
				}
			}
			//Création de la commande des Climates
			for ($j = 0; $j < $this->getConfiguration('nb_smartplug'); $j++)  {
				if ($device_array['smartplugType'.$j] == "climateDevice")  {
					$this->createCmd($device_array['smartplugID'.$j].'::Temp', 'Température '.$device_array['smartplugName'.$j], $order, 'info', 'numeric', 0, 0, ['generic_type', 'TEMPERATURE'], [], [], []);	
					$order++;
					if ($device_array['smartplugModel'.$j] == "Détecteur de fumée")   {
						$this->createCmd($device_array['smartplugID'.$j].'::Humidity', 'Humidité '.$device_array['smartplugName'.$j], $order, 'info', 'numeric', 0, 0, ['generic_type', 'HUMIDITY'], [], [], []);
						$order++;
					}
				}
			}
			//Création de la commande des DoorWindow
			for ($j = 0; $j < $this->getConfiguration('nb_smartplug'); $j++)  {
				if ($device_array['smartplugType'.$j] == "doorWindowDevice")  {
					$this->createCmd($device_array['smartplugID'.$j].'::State', 'Etat ouverture '.$device_array['smartplugName'.$j], $order, 'info', 'binary', 0, 0, ['generic_type', 'OPENING'], [], [], []);	
					$order++;				
				}
			}
		}
		
		if ( $this->getConfiguration('alarmtype') == 3 )   { 
		
			$this->setConfiguration('connectedLock', 0);
			$this->createCmd('armed_day', 'Mode Partiel', 7, 'action', 'other', 1, 0, ['generic_type', 'ALARM_SET_MODE'], [], [], []);
			$this->createCmd('armed_ext', 'Mode Extérieur', 8, 'action', 'other', 0, 0, [], [], [], []);
			$this->createCmd('getpictures', 'Demande Images', 9, 'action', 'select', 1, 0, [], [], [], []);
			$this->createCmd('networkstate', 'Qualité Réseau', 10, 'info', 'numeric', 1, 0, [], [], [], []);

			$device_array = $this->getConfiguration('devices');
			//Création des 3 commandes de la serrure connectée
			for ($j = 0; $j < $this->getConfiguration('nb_smartplug'); $j++)  {
				if ($device_array['smartplugType'.$j] == "DR")  {
					$id = str_pad($device_array['smartplugID'.$j], 2, "0", STR_PAD_LEFT); 	//id sur 2 digits
					$this->createCmd($id.'::connectedLockState', 'Etat serrure connectée', 11, 'info', 'binary', 1, 0, ['generic_type', 'LOCK_STATE'], [], ['dashboard', 'lock'], ['mobile', 'lock']);	
					$this->createCmd($id.'::connectedLockOpen', 'Ouverture serrure connectée', 12, 'action', 'other', 1, 0, ['generic_type', 'LOCK_OPEN'], [], [], []);
					$this->createCmd($id.'::connectedLockClose', 'Fermeture serrure connectée', 13, 'action', 'other', 1, 0, ['generic_type', 'LOCK_CLOSE'], [], [], []);
					$this->setConfiguration('connectedLock', 1);
					break;
				}
			}

			$this->createCmd('getstatehisto', 'État via historique', 14, 'action', 'other', 1, 0, [], [], [], []);
		}

		$this->save(true);		//paramètre "true" -> ne lance pas le postsave()
	}

	/* fonction appelée pendant la séquence de sauvegarde avant l'insertion 
     * dans la base de données pour une mise à jour d'une entrée */
    public function preUpdate() {
		
		if ( $this->getConfiguration('alarmtype') == 1 || $this->getConfiguration('alarmtype') == 3 )   {
			if (empty($this->getConfiguration('numinstall'))) {
				throw new Exception('Le numéro d\'installation ne peut pas être vide');
			}
			if (empty($this->getConfiguration('username'))) {
				throw new Exception('L\'identifiant ne peut pas être vide');
			}
			if (empty($this->getConfiguration('password'))) {
				throw new Exception('Le mot de passe ne peut etre vide');
			}
			if (empty($this->getConfiguration('country'))) {
				throw new Exception('Le pays ne peut pas être vide');
			}
		}
		
		if ( $this->getConfiguration('alarmtype') == 2 )   {
			if (empty($this->getConfiguration('username'))) {
				throw new Exception('L\'identifiant ne peut pas être vide');
			}
			if (empty($this->getConfiguration('password'))) {
				throw new Exception('Le mot de passe ne peut etre vide');
			}
			if (empty($this->getConfiguration('code'))) {
				throw new Exception('Le code ne peut pas être vide');
			}
		}
	}

	/* fonction appelée pendant la séquence de sauvegarde après l'insertion 
     * dans la base de données pour une mise à jour d'une entrée */
    public function postUpdate() {
	}

	/* fonction appelée avant l'effacement d'une entrée */
    public function preRemove() {
    }

	/* fonnction appelée aprés l'effacement d'une entrée */
    public function postRemove() {
    }
    
    /* Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin */
    public function toHtml($_version = 'dashboard') {
    		
		$replace = $this->preToHtml($_version);
		if (!is_array($replace)) {
			return $replace;
		}
		$version = jeedom::versionAlias($_version);
		$replace['#version#'] = $_version;
			
		if ( $this->getConfiguration('alarmtype') == 2 )   { 
			$replace['#nb_smartplug#'] = $this->getConfiguration('nb_smartplug');
			$replace['#nb_climate#'] = $this->getConfiguration('nb_climate');
			$replace['#nb_doorsensor#'] = $this->getConfiguration('nb_doorsensor');
			$replace['#nb_camera#'] = $this->getConfiguration('nb_camera');
			$replace['#nb_device#'] = $this->getConfiguration('nb_device');
		}
		if ( $this->getConfiguration('alarmtype') == 3 )   { 
			$replace['#connectedLock#'] = $this->getConfiguration('connectedLock');
		}
			
		$this->emptyCacheWidget(); 		//vide le cache. Pratique pour le développement

		// Traitement des commandes infos
		foreach ($this->getCmd('info') as $cmd) {
			if ( strpos($cmd->getLogicalId(), 'connectedLockState') != false ) { $logicalId = 'connectedLockState'; }
			else { $logicalId = $cmd->getLogicalId(); }
			$replace['#' . $logicalId . '_id#'] = $cmd->getId();
			$replace['#' . $logicalId . '_name#'] = $cmd->getName();
			$replace['#' . $logicalId . '#'] = $cmd->execCmd();
			$replace['#' . $logicalId . '_visible#'] = $cmd->getIsVisible();
		}

		// Traitement des commandes actions
		foreach ($this->getCmd('action') as $cmd) {
			if ( strpos($cmd->getLogicalId(), 'connectedLockOpen') != false ) { $logicalId = 'connectedLockOpen'; }
			else if ( strpos($cmd->getLogicalId(), 'connectedLockClose') != false ) { $logicalId = 'connectedLockClose'; }
			else { $logicalId = $cmd->getLogicalId(); }
			$replace['#' . $logicalId . '_id#'] = $cmd->getId();
			$replace['#' . $logicalId . '_visible#'] = $cmd->getIsVisible();
			if ($cmd->getSubType() == 'select') {
				$listValue = "<option value>" . $cmd->getName() . "</option>";
				$listValueArray = explode(';', $cmd->getConfiguration('listValue'));
				foreach ($listValueArray as $value) {
					list($id, $name) = explode('|', $value);
					$listValue = $listValue . "<option value=" . $id . ">" . $name . "</option>";
				}
				$replace['#' . $logicalId . '_listValue#'] = $listValue;
			}
		}
			
		// On definit le template à appliquer par rapport à la version Jeedom utilisée
		//if (version_compare(jeedom::version(), '4.0.0') >= 0) { }
		if ( $this->getConfiguration('alarmtype') == 1 ) { $template = 'verisure_dashboard_v4_type1'; }
		if ( $this->getConfiguration('alarmtype') == 2 ) { $template = 'verisure_dashboard_v4_type2'; }
		if ( $this->getConfiguration('alarmtype') == 3 ) { $template = 'verisure_dashboard_v4_type3'; }
		$replace['#template#'] = $template;

		return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, $template, 'verisure')));
	}
    
    /* Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    } */

    /* Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    } */
	 
	private function createCmd($commandName, $commandDescription, $order, $type, $subType, $isVisible, $isHistorized, $display1, $display2, $template1, $template2)
	{	
		$cmd = $this->getCmd(null, $commandName);
        if (!is_object($cmd)) {
            $cmd = new verisureCmd();
            $cmd->setOrder($order);
			$cmd->setName($commandDescription);
			$cmd->setEqLogic_id($this->getId());
			$cmd->setLogicalId($commandName);
			$cmd->setType($type);
			$cmd->setSubType($subType);
			$cmd->setIsVisible($isVisible);
			$cmd->setIsHistorized($isHistorized);
			if (!empty($display1)) { $cmd->setDisplay($display1[0], $display1[1]); }
			if (!empty($display2)) { $cmd->setDisplay($display2[0], $display2[1]); }
			if (!empty($template1)) { $cmd->setTemplate($template1[0], $template1[1]); }
			if (!empty($template2)) { $cmd->setTemplate($template2[0], $template2[1]); }
			$cmd->save();
			log::add('verisure', 'debug', 'Add command '.$cmd->getName().' (LogicalId : '.$cmd->getLogicalId().')');
		}

		if ( $commandName == 'armed' ) { $this->setConfiguration('SetModeAbsent', $cmd->getId()."|"."Total"); }		//Compatibilité Homebridge - Mode Absent / A distance

		if ( $this->getConfiguration('alarmtype') == 1 ) {

			if ( $commandName == 'armed_night' ) { $this->setConfiguration('SetModeNuit', $cmd->getId()."|"."Nuit"); }		//Compatibilité Homebridge - Mode Nuit
			if ( $commandName == 'armed_day' ) { $this->setConfiguration('SetModePresent', $cmd->getId()."|"."Jour"); }		//Compatibilité Homebridge - Mode Présent / Domicile
			if ( $commandName == 'getpictures' ) {
				$device_array = $this->getConfiguration('devices');
				for ($j = 0; $j < $this->getConfiguration('nb_smartplug'); $j++)  {
					if ($device_array['smartplugType'.$j] == "YR" || $device_array['smartplugType'.$j] == "XR" || $device_array['smartplugType'.$j] == "XP" || $device_array['smartplugType'.$j] == "QR")  {
						if (isset($listValue))  { $listValue = $listValue .';'. $device_array['smartplugID'.$j].'|'.$device_array['smartplugName'.$j];  }
						else  { $listValue = $device_array['smartplugID'.$j].'|'.$device_array['smartplugName'.$j];  }
					}
				}
				log::add('verisure', 'debug', $this->getHumanName().' - Mise à jour liste smartplugs compatibles images : '.var_export($listValue, true));
				$cmd->setConfiguration('listValue', $listValue);
				$cmd->save();
			}
		}

		if ( $this->getConfiguration('alarmtype') == 2 ) {
			
			if ( $commandName == 'armed_home' ) { $this->setConfiguration('SetModePresent',$cmd->getId()."|"."Partiel"); }		//Compatibilité Homebridge - Mode Présent / Domicile
			if ( $commandName == 'getpictures' ) {
				$device_array = $this->getConfiguration('devices');
				for ($j = 0; $j < $this->getConfiguration('nb_smartplug'); $j++)  {
					if ($device_array['smartplugType'.$j] == "cameraDevice")  {
						$smartplugID = str_replace(" ","%20", $device_array['smartplugID'.$j]);
						if (isset($listValue))  { $listValue = $listValue .';'.$smartplugID.'|'.$device_array['smartplugName'.$j];  }
						else  { $listValue = $smartplugID.'|'.$device_array['smartplugName'.$j];  }
					}
				}
				log::add('verisure', 'debug', $this->getHumanName().' - Mise à jour liste smartplugs compatibles images : '.var_export($listValue, true));
				$cmd->setConfiguration('listValue', $listValue);
				$cmd->save();
			}
		}

		if ( $this->getConfiguration('alarmtype') == 3 ) {
			
			if ( $commandName == 'armed_day' ) { $this->setConfiguration('SetModePresent',$cmd->getId()."|"."Partiel");	}		//Compatibilité Homebridge - Mode Présent / Domicile
			if ( $commandName == 'getpictures' ) {
				$device_array = $this->getConfiguration('devices');
				for ($j = 0; $j < $this->getConfiguration('nb_smartplug'); $j++)  {
					if ($device_array['smartplugType'.$j] == "YR" || $device_array['smartplugType'.$j] == "XR" || $device_array['smartplugType'.$j] == "XP" || $device_array['smartplugType'.$j] == "QR")  {
						if (isset($listValue))  { $listValue = $listValue .';'. $device_array['smartplugID'.$j].'|'.$device_array['smartplugName'.$j];  }
						else  { $listValue = $device_array['smartplugID'.$j].'|'.$device_array['smartplugName'.$j];  }
					}
				}
				log::add('verisure', 'debug', $this->getHumanName().' - Mise à jour liste smartplugs compatibles images : '.var_export($listValue, true));
				$cmd->setConfiguration('listValue', $listValue);
				$cmd->save();
			}
		}
    }


    /*     * **********************Getteur Setteur*************************** */

	public static function Authentication_2FA($alarmtype,$numinstall,$username,$password,$code,$country)	{		//Type 1 2 & 3
		
		if ( $alarmtype == 1 || $alarmtype == 3 )   {
			log::add('verisure', 'debug', '┌───────── Démarrage de l\'authentification 2FA ─────────');
			log::add('verisure', 'debug', '│ Alarme type '.$alarmtype);
			$MyAlarm = new verisureAPI($numinstall,$username,$password,$country);
			$result_Login = $MyAlarm->Login();
          	$response_Login = json_decode($result_Login[1], true);

			if ( $response_Login['data']['xSLoginToken']['needDeviceAuthorization'] == true) {
				$result_ValidateDevice = $MyAlarm->ValidateDevice(null);
          		$response_ValidateDevice = json_decode($result_ValidateDevice[1], true);
				
				if ( $response_ValidateDevice['errors'][0]['data']['auth-type'] == "OTP" ) {
					$result = array();
					$result['type'] = "OTP";
					$result['res'] = $response_ValidateDevice['errors'][0]['data']['auth-phones'];
					return $result;
				}
			}

			if ( $response_Login['data']['xSLoginToken']['needDeviceAuthorization'] == false) {
				$result_ListInstallations = $MyAlarm->ListInstallations();
				$result_ListDevices = $MyAlarm->ListDevices();
				$response_ListDevices = json_decode($result_ListDevices[1], true);
				$result_Logout = $MyAlarm->Logout();
          		log::add('verisure', 'debug', '└───────── Authentification 2FA terminée avec succès ─────────');
				
				if ( $response_ListDevices['data']['xSDeviceList']['res'] == "OK" ) {
					$result = array();
					$result['type'] = "devices";
					$result['res'] = $response_ListDevices['data']['xSDeviceList']['devices'];
					return $result;
				}
			}
			log::add('verisure', 'debug', '└───────── Erreur d\'authentification 2FA !! ─────────');
			return null;
		}
		
		if ( $alarmtype == 2 )   {
		
			log::add('verisure', 'debug', '┌───────── Démarrage de l\'authentification 2FA ─────────');
			log::add('verisure', 'debug', '│ Alarme type '.$alarmtype);
			$MyAlarm = new verisureAPI2($username,$password,$code);
			$result_Login = $MyAlarm->LoginMFA();
          	$response_Login = json_decode($result_Login[2], true);
			
			if ( $result_Login[1] == 401 ) {
				$result_Logout = $MyAlarm->Logout();
				log::add('verisure', 'debug', '└───────── Erreur d\'authentification 2FA !! ─────────');
				return null;
			}
			else {
				if ( $response_Login['stepUpToken'] != "") {
					$result = array();
					$result['type'] = "OTP";
					$result['res'] = array('phone','email');
					return $result;
				}
				else {
					$result_AccountInstallations = $MyAlarm->AccountInstallations();	
					$result_ListDevices = $MyAlarm->ListDevices();
          			$response_ListDevices = json_decode($result_ListDevices[1], true);
					log::add('verisure', 'debug', '└───────── Authentification 2FA terminée avec succès ─────────');

					if ( $result_ListDevices[0] == 200 ) {
						$result = array();
						$result['type'] = "devices";
						$result['res'] = $response_ListDevices['data']['installation']['devices'];
						return $result;
					}
				}
			}
			log::add('verisure', 'debug', '└───────── Erreur d\'authentification 2FA !! ─────────');
			return null;
		}
	}

	public static function Send_OTP($alarmtype,$numinstall,$username,$password,$code,$country, $phone_id)	{		//Type 1 2 & 3
		
		if ( $alarmtype == 1 || $alarmtype == 3 )   {

			$MyAlarm = new verisureAPI($numinstall,$username,$password,$country);
			$result_SendOTP = $MyAlarm->SendOTP($phone_id);
			return null;
		}

		if ( $alarmtype == 2 )   {

			$MyAlarm = new verisureAPI2($username,$password,$code);
			$result_RequestMFA = $MyAlarm->RequestMFA($phone_id);
			return null;
		}
	}

	public static function Validate_Device($alarmtype,$numinstall,$username,$password,$code,$country, $sms_code)	{		//Type 1 2 & 3

		if ( $alarmtype == 1 || $alarmtype == 3 )   {

			$MyAlarm = new verisureAPI($numinstall,$username,$password,$country);
			$result_ValidateDevice = $MyAlarm->ValidateDevice($sms_code);
          	$response_ValidateDevice = json_decode($result_ValidateDevice[1], true);

			if ( $response_ValidateDevice['data']['xSValidateDevice']['res'] == "OK") {

				$result_Login = $MyAlarm->Login();
          		$result_ListInstallations = $MyAlarm->ListInstallations();
          		$result_ListDevices = $MyAlarm->ListDevices();
          		$response_ListDevices = json_decode($result_ListDevices[1], true);
				$result_Logout = $MyAlarm->Logout();
          		log::add('verisure', 'debug', '└───────── Authentification 2FA terminée avec succès ─────────');
				
				if ( $response_ListDevices['data']['xSDeviceList']['res'] == "OK" ) {
					$result = array();
					$result['type'] = "devices";
					$result['res'] = $response_ListDevices['data']['xSDeviceList']['devices'];
					return $result;
				}
			}
			log::add('verisure', 'debug', '└───────── Erreur d\'authentification 2FA !! ─────────');
			return null;
		}

		if ( $alarmtype == 2 )   {

			$MyAlarm = new verisureAPI2($username,$password,$code);
			$result_ValidateMFA = $MyAlarm->ValidateMFA($sms_code);
			$response_ValidateDevice = json_decode($result_ValidateMFA[2], true);

			if ( $response_ValidateDevice['accessToken'] != "") {

				$result_AccountInstallations = $MyAlarm->AccountInstallations();	
				$result_ListDevices = $MyAlarm->ListDevices();
          		$response_ListDevices = json_decode($result_ListDevices[1], true);
				log::add('verisure', 'debug', '└───────── Authentification 2FA terminée avec succès ─────────');

				if ( $result_ListDevices[0] == 200 ) {
					$result = array();
					$result['type'] = "devices";
					$result['res'] = $response_ListDevices['data']['installation']['devices'];
					return $result;
				}
			}
			log::add('verisure', 'debug', '└───────── Erreur d\'authentification 2FA !! ─────────');
			return null;
		}
	}
		
	public static function Reset_Token($alarmtype,$numinstall)	{		//Type 1 2 & 3

		if ( $alarmtype == 1 || $alarmtype == 3 )   {
			
			$filename = __PLGBASE__.'/data/'.'device_'.$numinstall.'.json';
			if ( file_exists($filename) === true ) {
				unlink($filename);
				$result = array();
				$result['res'] = "OK";
				log::add('verisure', 'debug', 'Suppression du fichier '.$filename);
				return $result;
			}
			else { 
				log::add('verisure', 'debug', 'Le fichier '.$filename.' n\'existe pas'); 
				return null;
			}
		}

		if ( $alarmtype == 2 )   {
		
			$filename = __PLGBASE__.'/data/'.'cookie.txt';
			if ( file_exists($filename) === true ) {
				unlink($filename);
				$result = array();
				$result['res'] = "OK";
				log::add('verisure', 'debug', 'Suppression du fichier '.$filename);
				return $result;
			}
			else { 
				log::add('verisure', 'debug', 'Le fichier '.$filename.' n\'existe pas'); 
				return null;
			}
		}
	}
	
	public function GetStateAlarm()	{	//Type 1 2 & 3
		
		if ( $this->getConfiguration('alarmtype') == 1  || $this->getConfiguration('alarmtype') == 3 )   { 
			log::add('verisure', 'debug', '┌───────── Demande de statut ─────────');
			log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
			$result_Login = $MyAlarm->Login();
			$result_GetStateAlarm = $MyAlarm->GetStateAlarm();
			$response_GetStateAlarm = json_decode($result_GetStateAlarm[3], true);
			
			//getStateLock
			if ( $this->getConfiguration('connectedLock') == 1 ) {
				$result_GetStateLock = $MyAlarm->GetStateLock();
				$response_GetStateLock = json_decode($result_GetStateLock[1], true);
						
				if ( $result_GetStateLock[0] == 200 && $response_GetStateLock['data']['xSGetLockCurrentMode']['res'] == "OK" )  {
					$device = $response_GetStateLock['data']['xSGetLockCurrentMode']['smartlockInfo'][0]['deviceId'];
					$lockStatus = $response_GetStateLock['data']['xSGetLockCurrentMode']['smartlockInfo'][0]['lockStatus'];
					if ( $lockStatus == 1 ) { $this->checkAndUpdateCmd($device.'::connectedLockState', 0); }
					if ( $lockStatus == 2 ) { $this->checkAndUpdateCmd($device.'::connectedLockState', 1); }
				}
			}

			$result_Logout = $MyAlarm->Logout();
          	
			if ( $result_GetStateAlarm[2] == 200 && $response_GetStateAlarm['data']['xSCheckAlarmStatus']['res'] == "OK" )  {
				$res = $response_GetStateAlarm['data']['xSCheckAlarmStatus']['protomResponse'];
				log::add('verisure', 'debug', '└───────── Mise à jour statut OK ─────────');
			}
			else  {
				$res = "Erreur commande Verisure";
			}
			return $res;
		}
		
		if ( $this->getConfiguration('alarmtype') == 2 )   { 
			log::add('verisure', 'debug', '┌───────── Demande de statut ─────────');
			log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI2($this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('code'));
			$result_Login = $MyAlarm->Login();
          	$result_getStateAlarm = $MyAlarm->getStateAlarm();
			$response_getStateAlarm = json_decode($result_getStateAlarm[1], true);
			
			//getStateDevice
			$result_getClimatesInformation = $MyAlarm->getClimatesInformation();
			$response_getClimatesInformation = json_decode($result_getClimatesInformation[1], true);
			$result_getDoorWindowsInformation = $MyAlarm->getDoorWindowsInformation();
			$response_getDoorWindowsInformation = json_decode($result_getDoorWindowsInformation[1], true);
			$result_getCamerasInformation = $MyAlarm->getCamerasInformation();
			$response_getCamerasInformation = json_decode($result_getCamerasInformation[1], true);
			$result_getSmartplugsInformation = $MyAlarm->getSmartplugsInformation();
			$response_getSmartplugsInformation = json_decode($result_getSmartplugsInformation[1], true);

			if ( $result_getClimatesInformation[0] == 200 && $result_getDoorWindowsInformation[0] == 200 && $result_getCamerasInformation[0] == 200 && $result_getSmartplugsInformation[0] == 200)  {
				$tab_device = array();
				$tab_device['lastModified'] = date("Y-m-d H:i:s");
				$tab_device['climateDevice'] = $response_getClimatesInformation['data']['installation']['climates'];
				$tab_device['doorWindowDevice'] = $response_getDoorWindowsInformation['data']['installation']['doorWindows'];
				$tab_device['cameraDevice'] = $response_getCamerasInformation['data']['installation']['cameras'];
				$tab_device['smartPlugDevice'] = $response_getSmartplugsInformation['data']['installation']['smartplugs'];
								
				$filename = __PLGBASE__.'/data/'.'stateDevices.json';
				if (file_put_contents($filename, json_encode($tab_device), LOCK_EX)) {
					log::add('verisure', 'debug', '│ Fichier JSON enregistré avec succès dans '. $filename);
				}
				else {
					log::add('verisure', 'debug', '│ Fichier JSON non enregistré !');
				}		
			}
			else  {
				log::add('verisure', 'debug', '│ Fichier JSON pas mis à jour ! !');
			}

			if ( $result_getStateAlarm[0] == 200 && $response_getStateAlarm['data']['installation']['armState']['statusType'] != "" )  {
				$res = $response_getStateAlarm['data']['installation']['armState']['statusType'];
				$this->SetDeviceAttribute();
				log::add('verisure', 'debug', '└───────── Mise à jour statut OK ─────────');
			}
			else  {
				$res = "Erreur commande Verisure";
			}

			return $res;
		}
	}

	public function GetStateAlarmFromHistory()	{	//Type 3
		//Heliospeed
		if	( $this->getConfiguration('alarmtype') == 3 )   {
			log::add('verisure', 'debug', '┌───────── Demande de statut ─────────');
			$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
			$result_Login = $MyAlarm->Login();
          	$result_GetHistory = $MyAlarm->GetStateAlarmFromHistory(null);
			$response_GetHistory = json_decode($result_GetHistory[1], true);
			$result_Logout = $MyAlarm->Logout();
			          
          	if ( $result_GetHistory[0] == 200 )  {
				$res = $response_GetHistory['data']['xSActV2'];

				log::add('verisure', 'debug', '└───────── Historique statut OK ─────────');
			}
			else  {
				$res = null;
				log::add('verisure', 'debug', '│ /!\ Erreur commande Verisure GetStateAlarmFromHistory()');
				log::add('verisure', 'debug', '└───────── Historique statut NOK ─────────');
			}
			
          	return $res;
		}
	}

	function ConvertVerisureToAlarmState(array $history, bool $armedExt = false) {
		// Analyse de l'historique des événements pour déterminer le statut actuel

		$internal = 'unknown'; // total, partiel, desactive
		$external = $armedExt ? 'unknown' : 'desactive'; // actif, desactive (s'il n'y a pas d'alarme extérieure, on la considère comme désactivée)

		// On limite à 10 événements max
		$events = array_slice($history, 0, 10);

		foreach ($events as $event) {
			$type = intval($event['type']);

			switch ($type) {
				// Désactivation interne + externe
				case 822:
					if ($internal === 'unknown') { $internal = 'desactive'; }
					if ($external === 'unknown') { $external = 'desactive'; }	
					break;
                
                // Désactivation interne
                case 700:  
                case 800:
					if ($internal === 'unknown') { $internal = 'desactive'; }
					break;

				// Activation interne total
				case 701:
				case 801:
					if ($internal === 'unknown') { $internal = 'total'; }
					break;

				// Activation interne partiel
				case 702:
				case 802:
					if ($internal === 'unknown') { $internal = 'partiel'; }
					break;

				// Désactivation externe
				case 720:
				case 820:
					if ($external === 'unknown') { $external = 'desactive'; }
					break;

				// Activation externe
				case 721:
				case 821:
					$external = 'actif';
					break;

				// Activation total + externe
				case 823:
					if ($internal === 'unknown') { $internal = 'total'; }
					if ($external === 'unknown') { $external = 'actif'; }
					break;
				
				// Activation partiel + externe
				case 824:
					if ($internal === 'unknown') { $internal = 'partiel'; }
					if ($external === 'unknown') { $external = 'actif'; }
					break;

				default:
					// Types non gérés
					break;
			}

			// Critère de sortie : si les 2 états sont connus, on arrête
			if ($internal !== 'unknown' && $external !== 'unknown') {
				break;
			}
		}

		if ($internal === 'desactive' && $external === 'desactive') return "D";
		if ($internal === 'desactive' && $external === 'actif') return "E"; // extérieur activé
		if ($internal === 'partiel' && $external === 'desactive') return "P";
		if ($internal === 'partiel' && $external === 'actif') return "B"; // partiel + extérieur
		if ($internal === 'total' && $external === 'desactive') return "T";
		if ($internal === 'total' && $external === 'actif') return "A";   // total + extérieur

		// Si on n'a pas assez d'infos dans l'historique
		return null;
	}
	
	public function ArmTotalAlarm()	{	//Type 1 2 & 3
		
		if ( $this->getConfiguration('alarmtype') == 1 || $this->getConfiguration('alarmtype') == 3 )   { 
			log::add('verisure', 'debug', '┌───────── Demande activation mode total ─────────');
			log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
			$result_Login = $MyAlarm->Login();
          	$result_ArmAlarm = $MyAlarm->ArmAlarm("ARM1", $this->GetAlarmStatus());
			$response_ArmAlarm = json_decode($result_ArmAlarm[3], true);
			$result_Logout = $MyAlarm->Logout();
          	
			if ( $result_ArmAlarm[2] == 200 && $response_ArmAlarm['data']['xSArmStatus']['res'] == "OK" )  {
				$res = $response_ArmAlarm['data']['xSArmStatus']['protomResponse'];
				log::add('verisure', 'debug', '└───────── Activation mode total OK ─────────');
			}
			else  {
				$res = "Erreur commande Verisure";
			}
			return $res;
		}
		
		if ( $this->getConfiguration('alarmtype') == 2 )   {
			log::add('verisure', 'debug', '┌───────── Demande activation mode total ─────────');
			log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI2($this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('code'));
			$result_Login = $MyAlarm->Login();
          	$result_setStateAlarm = $MyAlarm->setStateAlarm('armAway');
			$response_setStateAlarm = json_decode($result_setStateAlarm[1], true);

			if ( $result_setStateAlarm[0] == 200 && $response_setStateAlarm['data']['armStateArmAway'] != "" )  {
				$res = 'ARMED_AWAY';
				log::add('verisure', 'debug', '└───────── Activation mode total OK ─────────');
			}
			else  {
				$res = "Erreur commande Verisure";
			}
			return $res;
		}
	}
		
	public function ArmNightAlarm()	{	//Type 1
		
		log::add('verisure', 'debug', '┌───────── Demande activation mode nuit ─────────');
		log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_Login = $MyAlarm->Login();
        $result_ArmAlarm = $MyAlarm->ArmAlarm("ARMNIGHT1", $this->GetAlarmStatus());
		$response_ArmAlarm = json_decode($result_ArmAlarm[3], true);
		$result_Logout = $MyAlarm->Logout();
        
		if ( $result_ArmAlarm[2] == 200 && $response_ArmAlarm['data']['xSArmStatus']['res'] == "OK" )  {
			$res = $response_ArmAlarm['data']['xSArmStatus']['protomResponse'];
			log::add('verisure', 'debug', '└───────── Activation mode nuit OK ─────────');
		}
		else  {
			$res = "Erreur commande Verisure";
		}
		return $res;
	}
	
	public function ArmDayAlarm()	{	//Type 1 & 3
				
		if ( $this->getConfiguration('alarmtype') == 1 ) { log::add('verisure', 'debug', '┌───────── Demande activation mode jour ─────────'); }
		if ( $this->getConfiguration('alarmtype') == 3 ) { log::add('verisure', 'debug', '┌───────── Demande activation mode partiel ─────────'); }
		log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_Login = $MyAlarm->Login();
		$result_ArmAlarm = $MyAlarm->ArmAlarm("ARMDAY1", $this->GetAlarmStatus());
		$response_ArmAlarm = json_decode($result_ArmAlarm[3], true);
		$result_Logout = $MyAlarm->Logout();
		
		if ( $result_ArmAlarm[2] == 200 && $response_ArmAlarm['data']['xSArmStatus']['res'] == "OK" )  {
			$res = $response_ArmAlarm['data']['xSArmStatus']['protomResponse'];
			if ( $this->getConfiguration('alarmtype') == 1 ) { log::add('verisure', 'debug', '└───────── Activation mode jour OK ─────────'); }
			if ( $this->getConfiguration('alarmtype') == 3 ) { log::add('verisure', 'debug', '└───────── Activation mode partiel OK ─────────'); }
		}
		else  {
			$res = "Erreur commande Verisure";
		}
		return $res;
	}
	
	public function ArmExtAlarm()	{	//Type 1 & 3
		
		log::add('verisure', 'debug', '┌───────── Demande activation mode extérieur ─────────');
		log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_Login = $MyAlarm->Login();
        $result_ArmAlarm = $MyAlarm->ArmAlarm("PERI1", $this->GetAlarmStatus());
		$response_ArmAlarm = json_decode($result_ArmAlarm[3], true);
		$result_Logout = $MyAlarm->Logout();
        
		if ( $result_ArmAlarm[2] == 200 && $response_ArmAlarm['data']['xSArmStatus']['res'] == "OK" )  {
			$res = $response_ArmAlarm['data']['xSArmStatus']['protomResponse'];
			log::add('verisure', 'debug', '└───────── Activation mode extérieur OK ─────────');
		}
		else  {
			$res = "Erreur commande Verisure";
		}
		return $res;
	}
	
	public function ArmHomeAlarm()	{	//Type 2
		
		log::add('verisure', 'debug', '┌───────── Demande activation mode partiel ─────────');
		log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
		$MyAlarm = new verisureAPI2($this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('code'));
		$result_Login = $MyAlarm->Login();
		$result_setStateAlarm = $MyAlarm->setStateAlarm('armHome');
		$response_setStateAlarm = json_decode($result_setStateAlarm[1], true);

		if ( $result_setStateAlarm[0] == 200 && $response_setStateAlarm['data']['armStateArmHome'] != "" )  {
			$res = 'ARMED_HOME';
			log::add('verisure', 'debug', '└───────── Activation mode partiel OK ─────────');
		}
		else  {
			$res = "Erreur commande Verisure";
		}
		return $res;
	
	}
	
	public function DisarmAlarm()	{	//Type 1 2 & 3
		
		if ( $this->getConfiguration('alarmtype') == 1 || $this->getConfiguration('alarmtype') == 3 )   { 
			log::add('verisure', 'debug', '┌───────── Demande désactivation ─────────');
			log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
			$result_Login = $MyAlarm->Login();
          	$result_DisarmAlarm = $MyAlarm->DisarmAlarm($this->GetAlarmStatus());
			$response_DisarmAlarm = json_decode($result_DisarmAlarm[3], true);
			$result_Logout = $MyAlarm->Logout();
          	
			if ( $result_DisarmAlarm[2] == 200 && $response_DisarmAlarm['data']['xSDisarmStatus']['res'] == "OK" )  {
				$res = $response_DisarmAlarm['data']['xSDisarmStatus']['protomResponse'];
				log::add('verisure', 'debug', '└───────── Désactivation OK ─────────');
			}
			else  {
				$res = "Erreur commande Verisure";
			}
			return $res;
		}
		
		if ( $this->getConfiguration('alarmtype') == 2 )   { 
			log::add('verisure', 'debug', '┌───────── Demande désactivation ─────────');
			log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI2($this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('code'));
			$result_Login = $MyAlarm->Login();
          	$result_setStateAlarm = $MyAlarm->setStateAlarm('disarm');
			$response_setStateAlarm = json_decode($result_setStateAlarm[1], true);

			if ( $result_setStateAlarm[0] == 200 && $response_setStateAlarm['data']['armStateDisarm'] != "" )  {
				$res = 'DISARMED';
				log::add('verisure', 'debug', '└───────── Désactivation OK ─────────');
			}
			else  {
				$res = "Erreur commande Verisure";
			}
			return $res;			
		}
	}

	public function GetReportAlarm()	{		//Type 1 2 & 3
		
		if ( $this->getConfiguration('alarmtype') == 1 || $this->getConfiguration('alarmtype') == 3 )   {
			
			log::add('verisure', 'debug', '┌───────── Demande du journal d\'activité ─────────');
			log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
			$result_Login = $MyAlarm->Login();
          	$result_GetReportAlarm = $MyAlarm->GetReportAlarm(null);
			$response_GetReportAlarm = json_decode($result_GetReportAlarm[1], true);
			$result_Logout = $MyAlarm->Logout();
			
			if ( $result_GetReportAlarm[0] == 200 )  {
				$res = $response_GetReportAlarm['data']['xSActV2'];
				log::add('verisure', 'debug', '└───────── Journal d\'activité OK ─────────');
				$this->checkAndUpdateCmd('networkstate', $this->SetNetworkState(1));
			}
			else  {
				$res = null;
				log::add('verisure', 'debug', '│ /!\ Erreur commande Verisure GetReportAlarm()');
				log::add('verisure', 'debug', '└───────── Journal d\'activité NOK ─────────');
				$this->checkAndUpdateCmd('networkstate', $this->SetNetworkState(0));
			}
			return $res;
		}
		
		if ( $this->getConfiguration('alarmtype') == 2 )   {
			
			log::add('verisure', 'debug', '┌───────── Demande du journal d\'activité ─────────');
			log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI2($this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('code'));
			$result_Login = $MyAlarm->Login();
          	$result_getReportAlarm = $MyAlarm->getReportAlarm();
			$response_getReportAlarm = json_decode($result_getReportAlarm[1], true);

			if ( $result_getReportAlarm[0] == 200 )  {
				$res = array();
				$res['eventLog'] = $response_getReportAlarm['data']['installation']['eventLog']['pagedList'];
				log::add('verisure', 'debug', '└───────── Journal d\'activité OK ─────────');
			}
			else  {
				$res = null;
				log::add('verisure', 'debug', '│ /!\ Erreur commande Verisure GetReportAlarm()');
				log::add('verisure', 'debug', '└───────── Journal d\'activité NOK ─────────');
			}
			return $res;
		}
	}

	public function GetPhotosRequest($device)	{		//Type 1 2 & 3

		if ( $this->getConfiguration('alarmtype') == 1 || $this->getConfiguration('alarmtype') == 3 )   { 
			log::add('verisure', 'debug', '┌───────── Demande de photos ─────────');
			log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
			$result_Login = $MyAlarm->Login();
          	$result_GetPhotosRequest = $MyAlarm->GetPhotosRequest($device);
			$result_Logout = $MyAlarm->Logout();
			
			if ( $result_GetPhotosRequest[6] == 200 )  {
				$res = $result_GetPhotosRequest[8];
				log::add('verisure', 'debug', '└───────── Demande de photos OK ─────────');
				$this->checkAndUpdateCmd('networkstate', $this->SetNetworkState(1));
			}
			else  {
				$res = null;
				log::add('verisure', 'debug', '│ /!\ Erreur commande Verisure GetPhotosRequest()');
				log::add('verisure', 'debug', '└───────── Demande de photos NOK ─────────');
				$this->checkAndUpdateCmd('networkstate', $this->SetNetworkState(0));
			}
			return $res;
		}

		if ( $this->getConfiguration('alarmtype') == 2 )   {

			log::add('verisure', 'debug', '┌───────── Demande de photos ─────────');
			log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI2($this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('code'));
			$result_Login = $MyAlarm->Login();
			$result_captureImageRequest = $MyAlarm->captureImageRequest($device);
			
			if ( $result_captureImageRequest[6] == 200 )  {
				$res = $result_captureImageRequest[7];
				log::add('verisure', 'debug', '└───────── Demande de photos OK ─────────');
			}
			else  {
				$res = null;
				log::add('verisure', 'debug', '│ /!\ Erreur commande Verisure GetPhotosRequest()');
				log::add('verisure', 'debug', '└───────── Demande de photos NOK ─────────');
			}
			return $res;
		}
	}
	
	public function SetStateLock($device, $lock)	{		//Type 3

		log::add('verisure', 'debug', '┌───────── Demande set connectedLock ─────────');
		log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_Login = $MyAlarm->Login();
		log::add('verisure', 'debug', '| connectedLock : '.$device.' - Commande envoyée : '.($lock?'Close':'Open'));
        $result_SetStateLock = $MyAlarm->SetStateLock($device, $lock);
		$result_Logout = $MyAlarm->Logout();
		$response_SetStateLock = json_decode($result_SetStateLock[5], true);
		
		if ( $result_SetStateLock[4] == 200 && $response_SetStateLock['data']['xSGetLockCurrentMode']['res'] == "OK")  {
			$result = $response_SetStateLock['data']['xSGetLockCurrentMode']['smartlockInfo'][0]['lockStatus'];
			log::add('verisure', 'debug', '└───────── Demande set connectedLock OK ─────────');
		}
		else  {
			$result = "Erreur commande Verisure";
		}
		return $result;
	}

	public function SetNetworkState($result)  {		//Type 1 & 3
		
		$quality = 0;
		$networkstate = array();
				
		if ( $this->getConfiguration('networkstate') == "" )   {
			$networkstate = array(1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1,1);
		}
		else   {
			$networkstate = json_decode($this->getConfiguration('networkstate'),true);
		}
		
		array_shift($networkstate); 				//dépile la première valeur du tableau
		$networkstate[24] = $result;				//ajoute le résultat de la dernière requête en dernière position du tableau
		$this->setConfiguration('networkstate', json_encode($networkstate));
		$this->save(true);
		
		$quality = array_count_values($networkstate)[1] / count($networkstate);
		log::add('verisure', 'debug', 'Etat du réseau : '.json_encode($networkstate));
		log::add('verisure', 'debug', 'Qualité du réseau : '.$quality);
		return $quality;
	}
	
	public static function SetEqLogic($numinstall)   {
	
		foreach (eqLogic::byTypeAndSearhConfiguration('verisure', 'numinstall') as $verisure) {
			if ($verisure->getConfiguration('numinstall') == $numinstall)   {
				$eqLogic = $verisure;
			}
		}
		return $eqLogic;		
	}
	
	public function SetSmartplugState($device_label, $state)	{	//Type 2
		
		log::add('verisure', 'debug', '┌───────── Demande set Smartplug ─────────');
		log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
		$MyAlarm = new verisureAPI2($this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('code'));
		$result_Login = $MyAlarm->Login();
		log::add('verisure', 'debug', '| SmartPlug : '.$device_label.' - Commande envoyée : '.($state?'On':'Off'));
		$result_setStateSmartplug = $MyAlarm->setStateSmartplug($device_label, $state);
		$response_setStateSmartplug = json_decode($result_setStateSmartplug[1], true);
		
		if ( $result_setStateSmartplug[0] == 200 && $response_setStateSmartplug['data']['SmartPlugSetState'] == true )  {
			$result = 'OK';
			log::add('verisure', 'debug', '└───────── Demande set Smartplug OK ─────────');
		}
		else  {
			$result = "Erreur commande Verisure";
		}
		return $result;
	}
	
	public function SetDeviceAttribute()	{	//Type 2
		
		$filename = __PLGBASE__.'/data/'.'stateDevices.json';
		if ( file_exists($filename) === false ) {
			log::add('verisure', 'debug', '│ Impossible de trouver le fichier stateDevices.json');
		}
		
		$content = file_get_contents($filename);
        if (!is_json($content)) {
            log::add('verisure', 'debug', '│ Le fichier JSON est corrompu');
        }

        $data = json_decode($content, true);
        
		foreach ($data['climateDevice'] as $climateDevice)  {
			$device_label = $climateDevice['device']['deviceLabel'];
			$temp = $climateDevice['temperatureValue'];
			$this->checkAndUpdateCmd($device_label.'::Temp', $temp);
			log::add('verisure', 'debug',  '│ Mise à jour température '.$device_label.' : '.$temp);
			
			if ( $climateDevice['humidityValue'] != null )   {
				$humidity = $climateDevice['humidityValue'];
				$this->checkAndUpdateCmd($device_label.'::Humidity', $humidity);
				log::add('verisure', 'debug',  '│ Mise à jour humidité '.$device_label.' : '.$humidity);
			}
		}
		
		foreach ($data['smartPlugDevice'] as $smartPlugDevice)  {
			$device_label = $smartPlugDevice['device']['deviceLabel'];
			if ( $smartPlugDevice['currentState'] == "ON" )   {
				$this->checkAndUpdateCmd($device_label.'::State', "1");
				log::add('verisure', 'debug',  '│ Mise à jour état SmartPlug '.$device_label.' : '."ON");
			}
			elseif ( $smartPlugDevice['currentState'] == "OFF" )   {
				$this->checkAndUpdateCmd($device_label.'::State', "0");
				log::add('verisure', 'debug',  '│ Mise à jour état SmartPlug '.$device_label.' : '."OFF");
			}
		}
		
		foreach ($data['doorWindowDevice'] as $doorWindowDevice)  {
			$device_label = $doorWindowDevice['device']['deviceLabel'];
			if ( $doorWindowDevice['state'] == "OPEN" )   {
				$this->checkAndUpdateCmd($device_label.'::State', "1");
				log::add('verisure', 'debug',  '│ Mise à jour état ouverture '.$device_label.' : '."OPEN");
			}
			elseif ( $doorWindowDevice['state'] == "CLOSE" )   {
				$this->checkAndUpdateCmd($device_label.'::State', "0");
				log::add('verisure', 'debug',  '│ Mise à jour état ouverture '.$device_label.' : '."CLOSE");
			}
		}
	}

	public function GetAlarmStatus() {		//Type 1 & 3

		$mode = $this->getCmd(null, 'mode');
		if ( $mode == "Désactivée" ) { return "D"; }
		elseif ( $mode == "Total" ) { return "T"; }
		elseif ( $mode == "Nuit" ) { return "Q"; }
		elseif ( $mode == "Jour" || $mode =="Partiel" ) { return "P"; }
		elseif ( $mode == "Extérieur" || $mode == "Total + Ext" || $mode == "Nuit + Ext" || $mode == "Jour + Ext" ) { return "E"; }
		else { return "D"; }
	}

}


class verisureCmd extends cmd {
	
    /*     * *************************Attributs****************************** */


    /*     * ***********************Methode static*************************** */


    /*     * *********************Methode d'instance************************* */

    /* Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
      public function dontRemoveCmd() {
      return true;
      }*/

    public function execute($_options = array()) {
    
		$eqlogic = $this->getEqLogic(); 										// On récupère l'éqlogic de la commande $this
		$logical = $this->getLogicalId();
		
		if ( $eqlogic->getConfiguration('alarmtype') == 1 || $eqlogic->getConfiguration('alarmtype') == 3 )   { 	
			switch ($logical) {													// On vérifie le logicalid de la commande 			
				case 'getstate': 												// LogicalId de la commande
					$state = $eqlogic->GetStateAlarm(); 						// On lance la fonction GetStatusAlarm() pour récupérer le statut de l'alarme et on le stocke dans la variable $state
					switch ($state)  {
						case 'D':
							$eqlogic->checkAndUpdateCmd('state', "0");			// On met à jour la commande avec le LogicalId 'state' de l'eqlogic
							$eqlogic->checkAndUpdateCmd('enable', "0");
							$eqlogic->checkAndUpdateCmd('mode', "Désactivée");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'T':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Total");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'Q':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Nuit");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'P':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							if ( $eqlogic->getConfiguration('alarmtype') == 1 ) { $eqlogic->checkAndUpdateCmd('mode', "Jour"); }
							if ( $eqlogic->getConfiguration('alarmtype') == 3 ) { $eqlogic->checkAndUpdateCmd('mode', "Partiel"); }
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'E':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Extérieur");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'A':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Total + Ext");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'C':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Nuit + Ext");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'B':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Jour + Ext");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'Erreur commande Verisure':
							//throw new Exception($state);
							log::add('verisure', 'debug', '│ /!\ Erreur commande Verisure GetStateAlarm()');
							log::add('verisure', 'debug', '└───────── Mise à jour statut NOK ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(0));
						break;
					}
				break;

				case 'getstatehisto': 												// LogicalId de la commande
					$statesHisto = $eqlogic->GetStateAlarmFromHistory(); 				// On lance la fonction GetStateAlarmFromHistory() pour récupérer l'historique des statuts de l'alarme

                    // On récupère uniquement les événements
                    $history = $statesHisto['reg'] ?? [];

					// On vérifie si la commande 'armed_ext' existe (présence de l'alarme extérieure)
					$armedExtCmdExists = is_object($eqlogic->getCmd(null, 'armed_ext'));
                	log::add('verisure', 'debug', '│ Alarme Extérieure présente : ' . ($armedExtCmdExists ? 'oui' : 'non'));

                    // Appel de ta fonction d’analyse
                    $state = $eqlogic->ConvertVerisureToAlarmState($history, $armedExtCmdExists); // On le stocke le statut dans la variable $state
					log::add('verisure', 'debug', '│ Résultat analyse = ' . $state);

					switch ($state)  {
						case 'D':
							$eqlogic->checkAndUpdateCmd('state', "0");			// On met à jour la commande avec le LogicalId 'state' de l'eqlogic
							$eqlogic->checkAndUpdateCmd('enable', "0");
							$eqlogic->checkAndUpdateCmd('mode', "Désactivée");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'T':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Total");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'P':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Partiel");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'E':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Extérieur");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'A':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Total + Ext");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'B':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Partiel + Ext");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						
						default:
							//throw new Exception($state);
							log::add('verisure', 'debug', '│ /!\ Erreur commande Verisure GetStateAlarmFromHistory()');
							log::add('verisure', 'debug', '└───────── Mise à jour statut NOK ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(0));
							break;
					}
				break;
					
				case 'armed':
					$state = $eqlogic->ArmTotalAlarm();
					switch ($state)  {
						case 'T':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Total");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'A':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Total + Ext");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'Erreur commande Verisure':
							log::add('verisure', 'debug', '│ /!\ Erreur commande Verisure ArmTotalAlarm()');
							log::add('verisure', 'debug', '└───────── Activation mode total NOK ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(0));
						break;
					}
				break;

				case 'armed_night':
					$state = $eqlogic->ArmNightAlarm();
					switch ($state)  {
						case 'Q':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Nuit");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'C':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Nuit + Ext");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'Erreur commande Verisure':
							log::add('verisure', 'debug', '│ /!\ Erreur commande Verisure ArmNightAlarm()');
							log::add('verisure', 'debug', '└───────── Activation mode nuit NOK ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(0));
						break;
					}
				break;
				
				case 'armed_day':
					$state = $eqlogic->ArmDayAlarm();
					switch ($state)  {
						case 'P':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							if ( $eqlogic->getConfiguration('alarmtype') == 1 ) { $eqlogic->checkAndUpdateCmd('mode', "Jour"); }
							if ( $eqlogic->getConfiguration('alarmtype') == 3 ) { $eqlogic->checkAndUpdateCmd('mode', "Partiel"); }
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'B':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Jour + Ext");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'Erreur commande Verisure':
							log::add('verisure', 'debug', '│ /!\ Erreur commande Verisure ArmDayAlarm()');
							if ( $eqlogic->getConfiguration('alarmtype') == 1 ) { log::add('verisure', 'debug', '└───────── Activation mode jour NOK ─────────'); }
							if ( $eqlogic->getConfiguration('alarmtype') == 3 ) { log::add('verisure', 'debug', '└───────── Activation mode partiel NOK ─────────'); }
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(0));
						break;
					}
				break;
					
				case 'armed_ext':
					$state = $eqlogic->ArmExtAlarm();
					switch ($state)  {
						case 'E':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Extérieur");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'A':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Total + Ext");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'C':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Nuit + Ext");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'B':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Jour + Ext");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'Erreur commande Verisure':
							log::add('verisure', 'debug', '│ /!\ Erreur commande Verisure ArmExtAlarm()');
							log::add('verisure', 'debug', '└───────── Activation mode extérieur NOK ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(0));
						break;
					}
				break;	
				
				case 'released':
					$state = $eqlogic->DisarmAlarm();
					switch ($state)  {
						case 'D':
							$eqlogic->checkAndUpdateCmd('state', "0");	
							$eqlogic->checkAndUpdateCmd('enable', "0");	
							$eqlogic->checkAndUpdateCmd('mode', "Désactivée");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'Erreur commande Verisure':
							log::add('verisure', 'debug', '│ /!\ Erreur commande Verisure DisarmAlarm()');
							log::add('verisure', 'debug', '└───────── Désactivation NOK ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(0));
						break;
					}	
				break;
			}
		}
		
		if ( $eqlogic->getConfiguration('alarmtype') == 2 )   { 
			
			if  (strpos($logical, '::') !== false)   {
				$command = explode('::', $logical);
				$device_label = $command[0];
				$param = $command[1];
				
				switch ($param)   {
					case 'On':
						$state = $eqlogic->SetSmartplugState($device_label, true);
						switch ($state)  {
							case 'OK':
								$eqlogic->checkAndUpdateCmd($device_label.'::State', "1");	
							break;
							case 'Erreur commande Verisure':
								log::add('verisure', 'debug', '│ /!\ Erreur commande Verisure SetSmartplugState()');
								log::add('verisure', 'debug', '└───────── Demande set Smartplug NOK ─────────');
							break;
						}
					break;
					
					case 'Off':
						$state = $eqlogic->SetSmartplugState($device_label, false);
						switch ($state)  {
							case 'OK':
								$eqlogic->checkAndUpdateCmd($device_label.'::State', "0");	
							break;
							case 'Erreur commande Verisure':
								log::add('verisure', 'debug', '│ /!\ Erreur commande Verisure SetSmartplugState()');
								log::add('verisure', 'debug', '└───────── Demande set Smartplug NOK ─────────');
							break;
						}
					break;
				}
			}
			
			if ($logical == 'getstate')   {
				$state = $eqlogic->GetStateAlarm();
				switch ($state)  {
					case 'DISARMED':
						$eqlogic->checkAndUpdateCmd('state', "0");
						$eqlogic->checkAndUpdateCmd('enable', "0");
						$eqlogic->checkAndUpdateCmd('mode', "Désactivée");
					break;
					case 'ARMED_AWAY':
						$eqlogic->checkAndUpdateCmd('enable', "1");
						$eqlogic->checkAndUpdateCmd('mode', "Total");
					break;
					case 'ARMED_HOME':
						$eqlogic->checkAndUpdateCmd('enable', "1");
						$eqlogic->checkAndUpdateCmd('mode', "Partiel");
					break;
					case 'Erreur commande Verisure':
						log::add('verisure', 'debug', '│ /!\ Erreur commande Verisure GetStateAlarm()');
						log::add('verisure', 'debug', '└───────── Mise à jour statut NOK ─────────');
					break;
				}
			}
			
			if ($logical == 'released')   {			
				$state = $eqlogic->DisarmAlarm();
				switch ($state)  {
					case 'DISARMED':
						$eqlogic->checkAndUpdateCmd('state', "0");	
						$eqlogic->checkAndUpdateCmd('enable', "0");	
						$eqlogic->checkAndUpdateCmd('mode', "Désactivée");
					break;
					case 'Erreur commande Verisure':
						log::add('verisure', 'debug', '│ /!\ Erreur commande Verisure DisarmAlarm()');
						log::add('verisure', 'debug', '└───────── Désactivation NOK ─────────');
					break;
				}	
			}
			
			if ($logical == 'armed_home')   {
				$state = $eqlogic->ArmHomeAlarm();
				switch ($state)  {
					case 'ARMED_HOME':
						$eqlogic->checkAndUpdateCmd('enable', "1");	
						$eqlogic->checkAndUpdateCmd('mode', "Partiel");
					break;
					case 'Erreur commande Verisure':
						log::add('verisure', 'debug', '│ /!\ Erreur commande Verisure ArmHomeAlarm()');
						log::add('verisure', 'debug', '└───────── Activation mode home NOK ─────────');
					break;
				}	
			}
					
			if ($logical == 'armed')   {
				$state = $eqlogic->ArmTotalAlarm();
				switch ($state)  {
					case 'ARMED_AWAY':
						$eqlogic->checkAndUpdateCmd('enable', "1");	
						$eqlogic->checkAndUpdateCmd('mode', "Total");
					break;
					case 'Erreur commande Verisure':
						log::add('verisure', 'debug', '│ /!\ Erreur commande Verisure ArmTotalAlarm()');
						log::add('verisure', 'debug', '└───────── Activation mode total NOK ─────────');
					break;
				}	
			}
		}

		if ( $eqlogic->getConfiguration('alarmtype') == 3 )   { 
			
			if  (strpos($logical, '::') !== false)   {
				$command = explode('::', $logical);
				$device = $command[0];
				$lock = $command[1];
				
				switch ($lock)   {
					case 'connectedLockOpen':
						$state = $eqlogic->SetStateLock($device, false);
						switch ($state)  {
							case '1':
								$eqlogic->checkAndUpdateCmd($device.'::connectedLockState', 0);	
							break;
							case '2':
								$eqlogic->checkAndUpdateCmd($device.'::connectedLockState', 1);	
							break;
							case 'Erreur commande Verisure':
								log::add('verisure', 'debug', '│ /!\ Erreur commande Verisure SetStateLock()');
								log::add('verisure', 'debug', '└───────── Demande set connectedLock NOK ─────────');
							break;
						}
					break;
					
					case 'connectedLockClose':
						$state = $eqlogic->SetStateLock($device, true);
						switch ($state)  {
							case '1':
								$eqlogic->checkAndUpdateCmd($device.'::connectedLockState', 0);	
							break;
							case '2':
								$eqlogic->checkAndUpdateCmd($device.'::connectedLockState', 1);	
							break;
							case 'Erreur commande Verisure':
								log::add('verisure', 'debug', '│ /!\ Erreur commande Verisure SetStateLock()');
								log::add('verisure', 'debug', '└───────── Demande set connectedLock NOK ─────────');
							break;
						}
					break;
				}
			}
		}
		
		$eqlogic->refreshWidget();	
	}
	

    /*     * **********************Getteur Setteur*************************** */
}


?>