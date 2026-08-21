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
			log::add('verisure', 'debug', 'Executing cron30');
			$cmdState->execCmd(); 																	// la commande existe on la lance
		}	
	}

	public static function pullHisto() {
		
		foreach (eqLogic::byType('verisure', true) as $verisure) {
			if ( ($verisure->getConfiguration('alarmtype') == 1 || $verisure->getConfiguration('alarmtype') == 3) && $verisure->getConfiguration('refreshHisto') == true ) {
				$cmdStateHisto = $verisure->getCmd(null, 'getstatehisto');		
				if (!is_object($cmdStateHisto) ) {
					continue; 
				}
				log::add('verisure', 'debug', 'Cron Histo '.config::byKey('cronPattern', 'verisure'));
				$cmdStateHisto->execCmd();
			}
		}
	}

	public static function manageCron($action, $cronPattern = null) {

		if ( $action === 'enable') {
			$cron = cron::byClassAndFunction('verisure', 'pullHisto');
			if (!is_object($cron)) {
				$cron = new cron();
				$cron->setClass('verisure');
				$cron->setFunction('pullHisto');
				$cron->setEnable(1);
				$cron->setDeamon(0);
				$cron->setSchedule($cronPattern);
				$cron->setTimeout(5);
				$cron->save();
				log::add('verisure', 'debug', 'Create cron pullHisto - setSchedule : '.$cronPattern);
			}
			else {
				$cron->setEnable(1);
				$cron->setSchedule($cronPattern);
				$cron->save();
				log::add('verisure', 'debug', 'Enable cron pullHisto - setSchedule : '.$cronPattern);
			}
		}
		
		else if ( $action === 'disable') {
			$cron = cron::byClassAndFunction('verisure', 'pullHisto');
			if (is_object($cron)) {
				$cron->setEnable(0);
				$cron->save();
				log::add('verisure', 'debug', 'Disable cron pullHisto');
			}	
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
		
		$this->createCmd('enable', __('Etat Activation', __FILE__), 1, 'info', 'binary', 1, 0, ['generic_type', 'ALARM_ENABLE_STATE'], [], ['dashboard', 'lock'], ['mobile', 'lock']);	//0 = désarmée - 1 = armée
		$this->createCmd('state', __('Etat Alarme', __FILE__), 2, 'info', 'binary', 1, 0, ['generic_type', 'ALARM_STATE'], ['invertBinary', 1], ['dashboard', 'alert'], ['mobile', 'alert']);		//0 = normale - 1 = déclenchée
		$this->createCmd('mode', __('Mode Alarme', __FILE__), 3, 'info', 'string', 1, 0, [], [], ['dashboard', 'tile'], ['mobile', 'tile']);
		$this->createCmd('armed', __('Mode Total', __FILE__), 4, 'action', 'other', 1, 0, ['generic_type', 'ALARM_ARMED'], [], [], []);
		$this->createCmd('released', __('Désactiver', __FILE__), 5, 'action', 'other', 1, 0, ['generic_type', 'ALARM_RELEASED'], [], [], []);
		$this->createCmd('getstate', __('Rafraichir', __FILE__), 6, 'action', 'other', 1, 0, [], [], [], []);			
				
		if ( $this->getConfiguration('alarmtype') == 1 )   { 
		
			$this->createCmd('armed_night', __('Mode Nuit', __FILE__), 7, 'action', 'other', 1, 0, ['generic_type', 'ALARM_SET_MODE'], [], [], []);
			$this->createCmd('armed_day', __('Mode Jour', __FILE__), 8, 'action', 'other', 1, 0, ['generic_type', 'ALARM_SET_MODE'], [], [], []);
			$this->createCmd('armed_ext', __('Mode Extérieur', __FILE__), 9, 'action', 'other', 1, 0, [], [], [], []);
			$this->createCmd('getpictures', __('Demande Images', __FILE__), 10, 'action', 'select', 1, 0, [], [], [], []);
			$this->createCmd('networkstate', __('Qualité Réseau', __FILE__), 11, 'info', 'numeric', 1, 0, [], [], [], []);
			$this->createCmd('getstatehisto', __('Rafraichir via historique', __FILE__), 12, 'action', 'other', 1, 0, [], [], [], []);
		}
		
		if ( $this->getConfiguration('alarmtype') == 2 )   { 
			
			$this->createCmd('armed_home', __('Mode Partiel', __FILE__), 7, 'action', 'other', 1, 0, ['generic_type', 'ALARM_SET_MODE'], [], [], []);
			$this->createCmd('getpictures', __('Demande Images', __FILE__), 8, 'action', 'select', 1, 0, [], [], [], []);
			
			$device_array = $this->getConfiguration('devices');
			$order = 9;
			//Création des 3 commandes des smartPlugs
			for ($j = 0; $j < $this->getConfiguration('nb_smartplug'); $j++)  {
				if ($device_array['smartplugType'.$j] == "smartPlugDevice")  {
					$this->createCmd($device_array['smartplugID'.$j].'::State', __('Smartplug', __FILE__).' '.$device_array['smartplugName'.$j].' '.__('Etat',__FILE__), $order, 'info', 'binary', 0, 0, ['generic_type', 'ENERGY_STATE'], [], [], []);	
					$order++;
					$this->createCmd($device_array['smartplugID'.$j].'::On', __('Smartplug', __FILE__).' '.$device_array['smartplugName'.$j].' On', $order, 'action', 'other', 0, 0, ['generic_type', 'ENERGY_ON'], [], [], []);
					$order++;
					$this->createCmd($device_array['smartplugID'.$j].'::Off', __('Smartplug', __FILE__).' '.$device_array['smartplugName'.$j].' Off', $order, 'action', 'other', 0, 0, ['generic_type', 'ENERGY_OFF'], [], [], []);
					$order++;	
				}
			}
			//Création de la commande des Climates
			for ($j = 0; $j < $this->getConfiguration('nb_smartplug'); $j++)  {
				if ($device_array['smartplugType'.$j] == "climateDevice")  {
					$this->createCmd($device_array['smartplugID'.$j].'::Temp', __('Température', __FILE__).' '.$device_array['smartplugName'.$j], $order, 'info', 'numeric', 0, 0, ['generic_type', 'TEMPERATURE'], [], [], []);	
					$order++;
					if ($device_array['smartplugModel'.$j] == "Détecteur de fumée")   {
						$this->createCmd($device_array['smartplugID'.$j].'::Humidity', __('Humidité', __FILE__).' '.$device_array['smartplugName'.$j], $order, 'info', 'numeric', 0, 0, ['generic_type', 'HUMIDITY'], [], [], []);
						$order++;
					}
				}
			}
			//Création de la commande des DoorWindow
			for ($j = 0; $j < $this->getConfiguration('nb_smartplug'); $j++)  {
				if ($device_array['smartplugType'.$j] == "doorWindowDevice")  {
					$this->createCmd($device_array['smartplugID'.$j].'::State', __('Etat ouverture', __FILE__).' '.$device_array['smartplugName'.$j], $order, 'info', 'binary', 0, 0, ['generic_type', 'OPENING'], [], [], []);	
					$order++;				
				}
			}
		}
		
		if ( $this->getConfiguration('alarmtype') == 3 )   { 
		
			$this->setConfiguration('connectedLock', 0);
			$this->createCmd('armed_day', __('Mode Partiel', __FILE__), 7, 'action', 'other', 1, 0, ['generic_type', 'ALARM_SET_MODE'], [], [], []);
			$this->createCmd('getpictures', __('Demande Images', __FILE__), 8, 'action', 'select', 1, 0, [], [], [], []);
			$this->createCmd('networkstate', __('Qualité Réseau', __FILE__), 9, 'info', 'numeric', 1, 0, [], [], [], []);
			$order = 10;

			//Création de la commande mode Extérieur si option activée
			if ( $this->getConfiguration('externalAlarm') == true )  {
				$this->createCmd('armed_ext', __('Mode Extérieur', __FILE__), $order, 'action', 'other', 1, 0, [], [], [], []);
				$order++;
			}
			
			$device_array = $this->getConfiguration('devices');
			//Création des 3 commandes de la serrure connectée
			for ($j = 0; $j < $this->getConfiguration('nb_smartplug'); $j++)  {
				if ($device_array['smartplugType'.$j] == "DR")  {
					$id = str_pad($device_array['smartplugID'.$j], 2, "0", STR_PAD_LEFT); 	//id sur 2 digits
					$this->createCmd($id.'::connectedLockState', __('Etat serrure connectée', __FILE__), $order, 'info', 'binary', 1, 0, ['generic_type', 'LOCK_STATE'], [], ['dashboard', 'lock'], ['mobile', 'lock']);	
					$order++;
					$this->createCmd($id.'::connectedLockOpen', __('Ouverture serrure connectée', __FILE__), $order, 'action', 'other', 1, 0, ['generic_type', 'LOCK_OPEN'], [], [], []);
					$order++;
					$this->createCmd($id.'::connectedLockClose', __('Fermeture serrure connectée', __FILE__), $order, 'action', 'other', 1, 0, ['generic_type', 'LOCK_CLOSE'], [], [], []);
					$order++;
					$this->setConfiguration('connectedLock', 1);
					break;
				}
			}

			$this->createCmd('getstatehisto', __('Rafraichir via historique', __FILE__), $order, 'action', 'other', 1, 0, [], [], [], []);
			$order++;
			$this->createCmd('mode_basic', __('Mode Basique', __FILE__), $order, 'info', 'string', 0, 0, ['generic_type', 'ALARM_MODE'], [], [], []); // création commande mode_basique pour homebridge
		}

		$this->save(true);		//paramètre "true" -> ne lance pas le postsave()
	}

	/* fonction appelée pendant la séquence de sauvegarde avant l'insertion 
     * dans la base de données pour une mise à jour d'une entrée */
    public function preUpdate() {
		
		if ( $this->getConfiguration('alarmtype') == 1 || $this->getConfiguration('alarmtype') == 3 )   {
			if (empty($this->getConfiguration('numinstall'))) {
				throw new Exception(__('Le numéro d\'installation ne peut pas être vide', __FILE__));
			}
			if (empty($this->getConfiguration('username'))) {
				throw new Exception(__('L\'identifiant ne peut pas être vide', __FILE__));
			}
			if (empty($this->getConfiguration('password'))) {
				throw new Exception(__('Le mot de passe ne peut etre vide', __FILE__));
			}
			if (empty($this->getConfiguration('country'))) {
				throw new Exception(__('Le pays ne peut pas être vide', __FILE__));
			}
		}
		
		if ( $this->getConfiguration('alarmtype') == 2 )   {
			if (empty($this->getConfiguration('username'))) {
				throw new Exception(__('L\'identifiant ne peut pas être vide', __FILE__));
			}
			if (empty($this->getConfiguration('password'))) {
				throw new Exception(__('Le mot de passe ne peut etre vide', __FILE__));
			}
			if (empty($this->getConfiguration('code'))) {
				throw new Exception(__('Le code ne peut pas être vide', __FILE__));
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

			$device_array = $this->getConfiguration('devices');
			$nb_smartplug = $this->getConfiguration('nb_smartplug');
			$doorWindowIndex = 0;
			$doorWindowDevices = array();

			for ($j = 0; $j < $nb_smartplug; $j++) {
				if (($device_array['smartplugType'.$j] ?? null) === "doorWindowDevice") {
					$doorWindowDevices[] = array(
						'id' => $device_array['smartplugID'.$j] ?? '',
						'name' => $device_array['smartplugName'.$j] ?? ''
					);
				}
			}
			$replace['#doorWindowDevices_json#'] = json_encode($doorWindowDevices);
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

		$filepath = 'plugins/'.__CLASS__.'/core/template/'.$version.'/'.$template.'.html';
        $html = template_replace($replace, getTemplate('core', $version, $template, 'verisure'));
        $html = translate::exec($html, $filepath);
        return $this->postToHtml($_version, $html);
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
						if (isset($listValue))  { $listValue = $listValue .';'. $device_array['smartplugID'.$j].'-106'.'|'.$device_array['smartplugName'.$j];  }
						else  { $listValue = $device_array['smartplugID'.$j].'-106'.'|'.$device_array['smartplugName'.$j];  }
					}
					if ($device_array['smartplugType'.$j] == "QP")  {
						if (isset($listValue))  { $listValue = $listValue .';'. $device_array['smartplugID'.$j].'-107'.'|'.$device_array['smartplugName'.$j];  }
						else  { $listValue = $device_array['smartplugID'.$j].'-107'.'|'.$device_array['smartplugName'.$j];  }
					}
				}
				log::add('verisure', 'debug', $this->getHumanName().' - Updating image-compatible devices list : '.var_export($listValue, true));
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
				log::add('verisure', 'debug', $this->getHumanName().' - Updating image-compatible devices list : '.var_export($listValue, true));
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
						if (isset($listValue))  { $listValue = $listValue .';'. $device_array['smartplugID'.$j].'-106'.'|'.$device_array['smartplugName'.$j];  }
						else  { $listValue = $device_array['smartplugID'.$j].'-106'.'|'.$device_array['smartplugName'.$j];  }
					}
					if ($device_array['smartplugType'.$j] == "QP")  {
						if (isset($listValue))  { $listValue = $listValue .';'. $device_array['smartplugID'.$j].'-107'.'|'.$device_array['smartplugName'.$j];  }
						else  { $listValue = $device_array['smartplugID'.$j].'-107'.'|'.$device_array['smartplugName'.$j];  }
					}
				}
				log::add('verisure', 'debug', $this->getHumanName().' - Updating image-compatible devices list : '.var_export($listValue, true));
				$cmd->setConfiguration('listValue', $listValue);
				$cmd->save();
			}
		}
    }


    /*     * **********************Getteur Setteur*************************** */

	public static function Authentication_2FA($alarmtype,$numinstall,$username,$password,$code,$country)	{		//Type 1 2 & 3
		
		if ( $alarmtype == 1 || $alarmtype == 3 )   {
			log::add('verisure', 'debug', '┌───────── Starting 2FA authentication ─────────');
			log::add('verisure', 'debug', '│ Alarm type '.$alarmtype);
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
          		log::add('verisure', 'debug', '└───────── 2FA authentication successful ─────────');
				
				if ( $response_ListDevices['data']['xSDeviceList']['res'] == "OK" ) {
					$result = array();
					$result['type'] = "devices";
					$result['res'] = $response_ListDevices['data']['xSDeviceList']['devices'];
					return $result;
				}
			}
			log::add('verisure', 'debug', '└───────── 2FA authentication failed!! ─────────');
			return null;
		}
		
		if ( $alarmtype == 2 )   {
		
			log::add('verisure', 'debug', '┌───────── Starting 2FA authentication ─────────');
			log::add('verisure', 'debug', '│ Alarme type '.$alarmtype);
			$MyAlarm = new verisureAPI2($username,$password,$code);
			$result_Login = $MyAlarm->LoginMFA();
          	$response_Login = json_decode($result_Login[2], true);
			
			if ( $result_Login[1] == 401 ) {
				$result_Logout = $MyAlarm->Logout();
				log::add('verisure', 'debug', '└───────── 2FA authentication failed!! ─────────');
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
					log::add('verisure', 'debug', '└───────── 2FA authentication successful ─────────');

					if ( $result_ListDevices[0] == 200 ) {
						$result = array();
						$result['type'] = "devices";
						$result['res'] = $response_ListDevices['data']['installation']['devices'];
						return $result;
					}
				}
			}
			log::add('verisure', 'debug', '└───────── 2FA authentication failed!! ─────────');
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
          		log::add('verisure', 'debug', '└───────── 2FA authentication successful ─────────');
				
				if ( $response_ListDevices['data']['xSDeviceList']['res'] == "OK" ) {
					$result = array();
					$result['type'] = "devices";
					$result['res'] = $response_ListDevices['data']['xSDeviceList']['devices'];
					return $result;
				}
			}
			log::add('verisure', 'debug', '└───────── 2FA authentication failed!! ─────────');
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
				log::add('verisure', 'debug', '└───────── 2FA authentication successful ─────────');

				if ( $result_ListDevices[0] == 200 ) {
					$result = array();
					$result['type'] = "devices";
					$result['res'] = $response_ListDevices['data']['installation']['devices'];
					return $result;
				}
			}
			log::add('verisure', 'debug', '└───────── 2FA authentication failed!! ─────────');
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
				log::add('verisure', 'debug', 'Deleting file '.$filename);
				return $result;
			}
			else { 
				log::add('verisure', 'debug', 'File '.$filename.' not found'); 
				return null;
			}
		}

		if ( $alarmtype == 2 )   {
		
			$filename = __PLGBASE__.'/data/'.'cookie.txt';
			if ( file_exists($filename) === true ) {
				unlink($filename);
				$result = array();
				$result['res'] = "OK";
				log::add('verisure', 'debug', 'Deleting file '.$filename);
				return $result;
			}
			else { 
				log::add('verisure', 'debug', 'File '.$filename.' not found'); 
				return null;
			}
		}
	}
	
	public function GetStateAlarm()	{	//Type 1 2 & 3
		
		if ( $this->getConfiguration('alarmtype') == 1  || $this->getConfiguration('alarmtype') == 3 )   { 
			log::add('verisure', 'debug', '┌───────── Status request ─────────');
			log::add('verisure', 'debug', '│ Equipment '.$this->getHumanName().' - Alarm type '.$this->getConfiguration('alarmtype'));
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
				log::add('verisure', 'debug', '└───────── Status updated successfully ─────────');
			}
			else  {
				$res = "Erreur commande Verisure";
			}
			return $res;
		}
		
		if ( $this->getConfiguration('alarmtype') == 2 )   { 
			log::add('verisure', 'debug', '┌───────── Status request ─────────');
			log::add('verisure', 'debug', '│ Equipment '.$this->getHumanName().' - Alarm type '.$this->getConfiguration('alarmtype'));
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
					log::add('verisure', 'debug', '│ JSON file successfully saved to '. $filename);
				}
				else {
					log::add('verisure', 'debug', '│ JSON file not saved!');
				}		
			}
			else  {
				log::add('verisure', 'debug', '│ JSON file not updated!!');
			}

			if ( $result_getStateAlarm[0] == 200 && $response_getStateAlarm['data']['installation']['armState']['statusType'] != "" )  {
				$res = $response_getStateAlarm['data']['installation']['armState']['statusType'];
				$this->SetDeviceAttribute();
				log::add('verisure', 'debug', '└───────── Status updated successfully ─────────');
			}
			else  {
				$res = "Erreur commande Verisure";
			}

			return $res;
		}
	}

	public function GetStateAlarmFromHistory()	{	//Type 1 & 3
		
		if	( $this->getConfiguration('alarmtype') == 1  || $this->getConfiguration('alarmtype') == 3 )   {
			log::add('verisure', 'debug', '┌───────── Status request via history ─────────');
			$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
			$result_Login = $MyAlarm->Login();
          	$result_GetHistory = $MyAlarm->GetStateAlarmFromHistory(null);
			$response_GetHistory = json_decode($result_GetHistory[1], true);
			$result_Logout = $MyAlarm->Logout();
			          
          	if ( $result_GetHistory[0] == 200 )  {
				$res = $response_GetHistory['data']['xSActV2'];
				//log::add('verisure', 'debug', '└───────── Status updated successfully from history ─────────');
			}
			else  {
				$res = null;
				//log::add('verisure', 'debug', '│ /!\ Verisure command error GetStateAlarmFromHistory()');
				//log::add('verisure', 'debug', '└───────── Failed to update status from history ─────────');
			}
			
          	return $res;
		}
	}

	public function ConvertVerisureToAlarmState(array $history) {
		
		// Analyse de l'historique des événements pour déterminer le statut actuel
		$internal = 'unknown'; // total, partiel, desactive
		if ( $this->getConfiguration('externalAlarm') == true ) {
			$external = 'unknown';
			log::add('verisure', 'debug', '│ External alarm detected');
		 }
		else { $external = 'desactive'; } 	// actif, desactive (s'il n'y a pas d'alarme extérieure, on la considère comme désactivée)

		// On limite à 10 événements max
		$events = array_slice($history, 0, 10);

		foreach ($events as $event) {
			$type = intval($event['type']);

			switch ($type) {
				// Désactivation interne + externe
				case 1;
				case 32;
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
				case 2;
				case 31;
				case 701:
				case 801:
					if ($internal === 'unknown') { $internal = 'total'; }
					break;

				// Activation interne partiel
				case 702:
				case 802:
					if ($internal === 'unknown') { $internal = 'partiel'; }
					break;

				// Activation interne jour
				case 202:
				case 311:
					if ($internal === 'unknown') { $internal = 'jour'; }
					break;

				// Activation interne nuit
				case 46:
				case 203:
					if ($internal === 'unknown') { $internal = 'nuit'; }
					break;

				// Désactivation externe
				case 720:
				case 820:
					if ($external === 'unknown') { $external = 'desactive'; }
					break;

				// Activation externe
				case 40;
				case 204;
				case 721:
				case 821:
					if ($external === 'unknown') { $external = 'actif'; }
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
		if ($internal === 'jour' && $external === 'desactive') return "P";
		if ($internal === 'nuit' && $external === 'desactive') return "Q";
		if ($internal === 'partiel' && $external === 'actif') return "B"; // partiel + extérieur
		if ($internal === 'jour' && $external === 'actif') return "B"; // jour + extérieur
		if ($internal === 'nuit' && $external === 'actif') return "C"; // nuit + extérieur
		if ($internal === 'total' && $external === 'desactive') return "T";
		if ($internal === 'total' && $external === 'actif') return "A";   // total + extérieur
		
		// Si on n'a pas assez d'infos dans l'historique
		return null;
	}
	
	public function ArmTotalAlarm()	{	//Type 1 2 & 3
		
		if ( $this->getConfiguration('alarmtype') == 1 || $this->getConfiguration('alarmtype') == 3 )   { 
			log::add('verisure', 'debug', '┌───────── Total mode activation request ─────────');
			log::add('verisure', 'debug', '│ Equipment '.$this->getHumanName().' - Alarm type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
			$result_Login = $MyAlarm->Login();
			
			if ( $this->getConfiguration('allowForcing') == true ) { 
				$allowForcing = true;
				log::add('verisure', 'debug', '│ Forced arming active');
			}
			else { $allowForcing = false; }
			
			if ( $this->getConfiguration('externalAlarm') == true ) { 
				$mode = "ARM1PERI1";
				log::add('verisure', 'debug', '│ External alarm detected');
			}
			else { $mode = "ARM1"; }
			log::add('verisure', 'debug', '│ Mode : '.$mode);

			$result_ArmAlarm = $MyAlarm->ArmAlarm($mode, $this->GetAlarmStatus(), $allowForcing);
			$response_ArmAlarm = json_decode($result_ArmAlarm[3], true);
			$result_Logout = $MyAlarm->Logout();
          	
			if ( $result_ArmAlarm[2] == 200 && $response_ArmAlarm['data']['xSArmStatus']['res'] == "OK" )  {
				$res = $response_ArmAlarm['data']['xSArmStatus']['protomResponse'];
				log::add('verisure', 'debug', '└───────── Total mode activated successfully ─────────');
			}
			else  {
				$res = "Erreur commande Verisure";
			}
			return $res;
		}
		
		if ( $this->getConfiguration('alarmtype') == 2 )   {
			log::add('verisure', 'debug', '┌───────── Total mode activation request ─────────');
			log::add('verisure', 'debug', '│ Equipment '.$this->getHumanName().' - Alarm type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI2($this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('code'));
			$result_Login = $MyAlarm->Login();
          	$result_setStateAlarm = $MyAlarm->setStateAlarm('armAway');
			$response_setStateAlarm = json_decode($result_setStateAlarm[1], true);

			if ( $result_setStateAlarm[0] == 200 && $response_setStateAlarm['data']['armStateArmAway'] != "" )  {
				$res = 'ARMED_AWAY';
				log::add('verisure', 'debug', '└───────── Total mode activated successfully ─────────');
			}
			else  {
				$res = "Erreur commande Verisure";
			}
			return $res;
		}
	}
		
	public function ArmNightAlarm()	{	//Type 1
		
		log::add('verisure', 'debug', '┌───────── Night mode activation request ─────────');
		log::add('verisure', 'debug', '│ Equipment '.$this->getHumanName().' - Alarm type '.$this->getConfiguration('alarmtype'));
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_Login = $MyAlarm->Login();
		log::add('verisure', 'debug', '│ Mode : ARMNIGHT1');
        $result_ArmAlarm = $MyAlarm->ArmAlarm("ARMNIGHT1", $this->GetAlarmStatus());
		$response_ArmAlarm = json_decode($result_ArmAlarm[3], true);
		$result_Logout = $MyAlarm->Logout();
        
		if ( $result_ArmAlarm[2] == 200 && $response_ArmAlarm['data']['xSArmStatus']['res'] == "OK" )  {
			$res = $response_ArmAlarm['data']['xSArmStatus']['protomResponse'];
			log::add('verisure', 'debug', '└───────── Night mode activated successfully ─────────');
		}
		else  {
			$res = "Erreur commande Verisure";
		}
		return $res;
	}
	
	public function ArmDayAlarm()	{	//Type 1 & 3
				
		if ( $this->getConfiguration('alarmtype') == 1 ) { log::add('verisure', 'debug', '┌───────── Day mode activation request ─────────'); }
		if ( $this->getConfiguration('alarmtype') == 3 ) { log::add('verisure', 'debug', '┌───────── Partial mode activation request ─────────'); }
		log::add('verisure', 'debug', '│ Equipment '.$this->getHumanName().' - Alarm type '.$this->getConfiguration('alarmtype'));
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_Login = $MyAlarm->Login();
		
		if ( $this->getConfiguration('allowForcing') == true ) { 
			$allowForcing = true;
			log::add('verisure', 'debug', '│ Forced arming active');
		}
		else { $allowForcing = false; }
		
		if ( $this->getConfiguration('externalAlarm') == true ) { 
				$mode = "ARMDAY1PERI1";
				log::add('verisure', 'debug', '│ External alarm detected');
			}
			else { $mode = "ARMDAY1"; }
		log::add('verisure', 'debug', '│ Mode : '.$mode);
		
		$result_ArmAlarm = $MyAlarm->ArmAlarm($mode, $this->GetAlarmStatus(), $allowForcing);
		$response_ArmAlarm = json_decode($result_ArmAlarm[3], true);
		$result_Logout = $MyAlarm->Logout();
		
		if ( $result_ArmAlarm[2] == 200 && $response_ArmAlarm['data']['xSArmStatus']['res'] == "OK" )  {
			$res = $response_ArmAlarm['data']['xSArmStatus']['protomResponse'];
			if ( $this->getConfiguration('alarmtype') == 1 ) { log::add('verisure', 'debug', '└───────── Day mode activated successfully ─────────'); }
			if ( $this->getConfiguration('alarmtype') == 3 ) { log::add('verisure', 'debug', '└───────── Partial mode activated successfully ─────────'); }
		}
		else  {
			$res = "Erreur commande Verisure";
		}
		return $res;
	}
	
	public function ArmExtAlarm()	{	//Type 1 & 3
		
		log::add('verisure', 'debug', '┌───────── External mode activation request ─────────');
		log::add('verisure', 'debug', '│ Equipment '.$this->getHumanName().' - Alarm type '.$this->getConfiguration('alarmtype'));
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_Login = $MyAlarm->Login();
        log::add('verisure', 'debug', '│ Mode : PERI1');
		$result_ArmAlarm = $MyAlarm->ArmAlarm("PERI1", $this->GetAlarmStatus());
		$response_ArmAlarm = json_decode($result_ArmAlarm[3], true);
		$result_Logout = $MyAlarm->Logout();
        
		if ( $result_ArmAlarm[2] == 200 && $response_ArmAlarm['data']['xSArmStatus']['res'] == "OK" )  {
			$res = $response_ArmAlarm['data']['xSArmStatus']['protomResponse'];
			log::add('verisure', 'debug', '└───────── External mode activated successfully ─────────');
		}
		else  {
			$res = "Erreur commande Verisure";
		}
		return $res;
	}
	
	public function ArmHomeAlarm()	{	//Type 2
		
		log::add('verisure', 'debug', '┌───────── Partial mode activation request ─────────');
		log::add('verisure', 'debug', '│ Equipment '.$this->getHumanName().' - Alarm type '.$this->getConfiguration('alarmtype'));
		$MyAlarm = new verisureAPI2($this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('code'));
		$result_Login = $MyAlarm->Login();
		$result_setStateAlarm = $MyAlarm->setStateAlarm('armHome');
		$response_setStateAlarm = json_decode($result_setStateAlarm[1], true);

		if ( $result_setStateAlarm[0] == 200 && $response_setStateAlarm['data']['armStateArmHome'] != "" )  {
			$res = 'ARMED_HOME';
			log::add('verisure', 'debug', '└───────── Partial mode activated successfully ─────────');
		}
		else  {
			$res = "Erreur commande Verisure";
		}
		return $res;
	
	}
	
	public function DisarmAlarm()	{	//Type 1 2 & 3
		
		if ( $this->getConfiguration('alarmtype') == 1 || $this->getConfiguration('alarmtype') == 3 )   { 
			log::add('verisure', 'debug', '┌───────── Deactivation request ─────────');
			log::add('verisure', 'debug', '│ Equipment '.$this->getHumanName().' - Alarm type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
			$result_Login = $MyAlarm->Login();
			$mode = $this->GetDisarmMode();
          	log::add('verisure', 'debug', '│ Mode : '.$mode);
			$result_DisarmAlarm = $MyAlarm->DisarmAlarm($mode, $this->GetAlarmStatus());
			$response_DisarmAlarm = json_decode($result_DisarmAlarm[3], true);
			$result_Logout = $MyAlarm->Logout();
          	
			if ( $result_DisarmAlarm[2] == 200 && $response_DisarmAlarm['data']['xSDisarmStatus']['res'] == "OK" )  {
				$res = $response_DisarmAlarm['data']['xSDisarmStatus']['protomResponse'];
				log::add('verisure', 'debug', '└───────── Deactivated successfully ─────────');
			}
			else  {
				$res = "Erreur commande Verisure";
			}
			return $res;
		}
		
		if ( $this->getConfiguration('alarmtype') == 2 )   { 
			log::add('verisure', 'debug', '┌───────── Deactivation request ─────────');
			log::add('verisure', 'debug', '│ Equipment '.$this->getHumanName().' - Alarm type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI2($this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('code'));
			$result_Login = $MyAlarm->Login();
          	$result_setStateAlarm = $MyAlarm->setStateAlarm('disarm');
			$response_setStateAlarm = json_decode($result_setStateAlarm[1], true);

			if ( $result_setStateAlarm[0] == 200 && $response_setStateAlarm['data']['armStateDisarm'] != "" )  {
				$res = 'DISARMED';
				log::add('verisure', 'debug', '└───────── Deactivated successfully ─────────');
			}
			else  {
				$res = "Erreur commande Verisure";
			}
			return $res;			
		}
	}

	public function GetReportAlarm()	{		//Type 1 2 & 3
		
		if ( $this->getConfiguration('alarmtype') == 1 || $this->getConfiguration('alarmtype') == 3 )   {
			
			log::add('verisure', 'debug', '┌───────── Activity log request ─────────');
			log::add('verisure', 'debug', '│ Equipment '.$this->getHumanName().' - Alarm type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
			$result_Login = $MyAlarm->Login();
          	$result_GetReportAlarm = $MyAlarm->GetReportAlarm(null);
			$response_GetReportAlarm = json_decode($result_GetReportAlarm[1], true);
			$result_Logout = $MyAlarm->Logout();
			
			if ( $result_GetReportAlarm[0] == 200 )  {
				$res = $response_GetReportAlarm['data']['xSActV2'];
				log::add('verisure', 'debug', '└───────── Activity log retrieved successfully ─────────');
				$this->checkAndUpdateCmd('networkstate', $this->SetNetworkState(1));
			}
			else  {
				$res = null;
				log::add('verisure', 'debug', '│ /!\ Verisure command error GetReportAlarm()');
				log::add('verisure', 'debug', '└───────── Failed to retrieve activity log ─────────');
				$this->checkAndUpdateCmd('networkstate', $this->SetNetworkState(0));
			}
			return $res;
		}
		
		if ( $this->getConfiguration('alarmtype') == 2 )   {
			
			log::add('verisure', 'debug', '┌───────── Activity log request ─────────');
			log::add('verisure', 'debug', '│ Equipment '.$this->getHumanName().' - Alarm type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI2($this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('code'));
			$result_Login = $MyAlarm->Login();
          	$result_getReportAlarm = $MyAlarm->getReportAlarm();
			$response_getReportAlarm = json_decode($result_getReportAlarm[1], true);

			if ( $result_getReportAlarm[0] == 200 )  {
				$res = array();
				$res['eventLog'] = $response_getReportAlarm['data']['installation']['eventLog']['pagedList'];
				log::add('verisure', 'debug', '└───────── Activity log retrieved successfully ─────────');
			}
			else  {
				$res = null;
				log::add('verisure', 'debug', '│ /!\ Verisure command error GetReportAlarm()');
				log::add('verisure', 'debug', '└───────── Failed to retrieve activity log ─────────');
			}
			return $res;
		}
	}

	public function GetPhotosRequest($device, $code = null)	{		//Type 1 2 & 3

		if ( $this->getConfiguration('alarmtype') == 1 || $this->getConfiguration('alarmtype') == 3 )   { 
			log::add('verisure', 'debug', '┌───────── Images request ─────────');
			log::add('verisure', 'debug', '│ Equipment '.$this->getHumanName().' - Alarm type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
			$result_Login = $MyAlarm->Login();
          	$result_GetPhotosRequest = $MyAlarm->GetPhotosRequest($device, $code);
			$result_Logout = $MyAlarm->Logout();
			
			if ( $result_GetPhotosRequest[6] == 200 )  {
				$res = $result_GetPhotosRequest[8];
				log::add('verisure', 'debug', '└───────── Images retrieved successfully ─────────');
				$this->checkAndUpdateCmd('networkstate', $this->SetNetworkState(1));
			}
			else  {
				$res = null;
				log::add('verisure', 'debug', '│ /!\ Verisure command error GetPhotosRequest()');
				log::add('verisure', 'debug', '└───────── Failed to retrieve images ─────────');
				$this->checkAndUpdateCmd('networkstate', $this->SetNetworkState(0));
			}
			return $res;
		}

		if ( $this->getConfiguration('alarmtype') == 2 )   {

			log::add('verisure', 'debug', '┌───────── Images request ─────────');
			log::add('verisure', 'debug', '│ Equipment '.$this->getHumanName().' - Alarm type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI2($this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('code'));
			$result_Login = $MyAlarm->Login();
			$result_captureImageRequest = $MyAlarm->captureImageRequest($device);
			
			if ( $result_captureImageRequest[6] == 200 )  {
				$res = $result_captureImageRequest[7];
				log::add('verisure', 'debug', '└───────── Images retrieved successfully ─────────');
			}
			else  {
				$res = null;
				log::add('verisure', 'debug', '│ /!\ Verisure command error GetPhotosRequest()');
				log::add('verisure', 'debug', '└───────── Failed to retrieve images ─────────');
			}
			return $res;
		}
	}
	
	public function SetStateLock($device, $lock)	{		//Type 3

		log::add('verisure', 'debug', '┌───────── Set connectedLock request ─────────');
		log::add('verisure', 'debug', '│ Equipment '.$this->getHumanName().' - Alarm type '.$this->getConfiguration('alarmtype'));
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_Login = $MyAlarm->Login();
		log::add('verisure', 'debug', '| connectedLock : '.$device.' - Command sent : '.($lock?'Close':'Open'));
        $result_SetStateLock = $MyAlarm->SetStateLock($device, $lock);
		$result_Logout = $MyAlarm->Logout();
		$response_SetStateLock = json_decode($result_SetStateLock[5], true);
		
		if ( $result_SetStateLock[4] == 200 && $response_SetStateLock['data']['xSGetLockCurrentMode']['res'] == "OK")  {
			$result = $response_SetStateLock['data']['xSGetLockCurrentMode']['smartlockInfo'][0]['lockStatus'];
			log::add('verisure', 'debug', '└───────── Set connectedLock request successful ─────────');
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
		log::add('verisure', 'debug', 'Network status : '.json_encode($networkstate));
		log::add('verisure', 'debug', 'Network quality : '.$quality);
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
		
		log::add('verisure', 'debug', '┌───────── Set Smartplug request ─────────');
		log::add('verisure', 'debug', '│ Equipment '.$this->getHumanName().' - Alarm type '.$this->getConfiguration('alarmtype'));
		$MyAlarm = new verisureAPI2($this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('code'));
		$result_Login = $MyAlarm->Login();
		log::add('verisure', 'debug', '| SmartPlug : '.$device_label.' - Command sent : '.($state?'On':'Off'));
		$result_setStateSmartplug = $MyAlarm->setStateSmartplug($device_label, $state);
		$response_setStateSmartplug = json_decode($result_setStateSmartplug[1], true);
		
		if ( $result_setStateSmartplug[0] == 200 && $response_setStateSmartplug['data']['SmartPlugSetState'] == true )  {
			$result = 'OK';
			log::add('verisure', 'debug', '└───────── Set Smartplug request successful ─────────');
		}
		else  {
			$result = "Erreur commande Verisure";
		}
		return $result;
	}
	
	public function SetDeviceAttribute()	{	//Type 2
		
		$filename = __PLGBASE__.'/data/'.'stateDevices.json';
		if ( file_exists($filename) === false ) {
			log::add('verisure', 'debug', '│ File stateDevices.json not found');
		}
		
		$content = file_get_contents($filename);
        if (!is_json($content)) {
            log::add('verisure', 'debug', '│ JSON file is corrupted');
        }

        $data = json_decode($content, true);
        
		foreach ($data['climateDevice'] as $climateDevice)  {
			$device_label = $climateDevice['device']['deviceLabel'];
			$temp = $climateDevice['temperatureValue'];
			$this->checkAndUpdateCmd($device_label.'::Temp', $temp);
			log::add('verisure', 'debug',  '│ Updating temperature '.$device_label.' : '.$temp);
			
			if ( $climateDevice['humidityValue'] != null )   {
				$humidity = $climateDevice['humidityValue'];
				$this->checkAndUpdateCmd($device_label.'::Humidity', $humidity);
				log::add('verisure', 'debug',  '│ Updating humidity '.$device_label.' : '.$humidity);
			}
		}
		
		foreach ($data['smartPlugDevice'] as $smartPlugDevice)  {
			$device_label = $smartPlugDevice['device']['deviceLabel'];
			if ( $smartPlugDevice['currentState'] == "ON" )   {
				$this->checkAndUpdateCmd($device_label.'::State', "1");
				log::add('verisure', 'debug',  '│ Updating SmartPlug status '.$device_label.' : '."ON");
			}
			elseif ( $smartPlugDevice['currentState'] == "OFF" )   {
				$this->checkAndUpdateCmd($device_label.'::State', "0");
				log::add('verisure', 'debug',  '│ Updating SmartPlug status '.$device_label.' : '."OFF");
			}
		}
		
		foreach ($data['doorWindowDevice'] as $doorWindowDevice)  {
			$device_label = $doorWindowDevice['device']['deviceLabel'];
			if ( $doorWindowDevice['state'] == "OPEN" )   {
				$this->checkAndUpdateCmd($device_label.'::State', "1");
				log::add('verisure', 'debug',  '│ Updating opening status '.$device_label.' : '."OPEN");
			}
			elseif ( $doorWindowDevice['state'] == "CLOSE" )   {
				$this->checkAndUpdateCmd($device_label.'::State', "0");
				log::add('verisure', 'debug',  '│ Updating opening status '.$device_label.' : '."CLOSE");
			}
		}
	}

	public function GetAlarmStatus() {		//Type 1 & 3

		$mode = $this->getCmd(null, 'mode')->execCmd();
		if ( $mode == __('Désactivée', __FILE__) ) { return "D"; }
		elseif ( $mode == __('Total', __FILE__) ) { return "T"; }
		elseif ( $mode == __('Nuit', __FILE__) ) { return "Q"; }
		elseif ( $mode == __('Jour', __FILE__) || $mode == __('Partiel', __FILE__) ) { return "P"; }
		elseif ( $mode == __('Extérieur', __FILE__) || $mode == __('Total + Ext', __FILE__) || $mode == __('Nuit + Ext', __FILE__) || $mode == __('Jour + Ext', __FILE__) || $mode == __('Partiel + Ext', __FILE__) ) { return "E"; }
		else { return "D"; }
	}
	
	public function GetDisarmMode() {		//Type 1 & 3

		$mode = $this->getCmd(null, 'mode')->execCmd();
		if ( $mode == __('Désactivée', __FILE__) ) { return "DARM1"; }
		elseif ( $mode == __('Total', __FILE__) ) { return "DARM1"; }
		elseif ( $mode == __('Nuit', __FILE__) ) { return "DARM1"; }
		elseif ( $mode == __('Jour', __FILE__) || $mode == __('Partiel', __FILE__) ) { return "DARM1"; }
		elseif ( $mode == __('Extérieur', __FILE__) ) { return "DARMPERI"; }
		elseif ( $mode == __('Total + Ext', __FILE__) || $mode == __('Nuit + Ext', __FILE__) || $mode == __('Jour + Ext', __FILE__) || $mode == __('Partiel + Ext', __FILE__) ) { return "DARM1DARMPERI"; }
		else { return "DARM1"; }

		return $mode;
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
							$eqlogic->checkAndUpdateCmd('mode', __('Désactivée', __FILE__));
							$eqlogic->checkAndUpdateCmd('mode_basic', __('Désactivée', __FILE__));
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'T':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', __('Total', __FILE__));
							$eqlogic->checkAndUpdateCmd('mode_basic', __('Total', __FILE__));
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'Q':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', __('Nuit', __FILE__));
							$eqlogic->checkAndUpdateCmd('mode_basic', __('Nuit', __FILE__));
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'P':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							if ( $eqlogic->getConfiguration('alarmtype') == 1 ) { $eqlogic->checkAndUpdateCmd('mode', __('Jour', __FILE__)); $eqlogic->checkAndUpdateCmd('mode_basic', __('Jour', __FILE__)); }
							if ( $eqlogic->getConfiguration('alarmtype') == 3 ) { $eqlogic->checkAndUpdateCmd('mode', __('Partiel', __FILE__)); $eqlogic->checkAndUpdateCmd('mode_basic', __('Partiel', __FILE__)); }
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'E':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', __('Extérieur', __FILE__));
							$eqlogic->checkAndUpdateCmd('mode_basic', __('Extérieur', __FILE__));
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'A':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', __('Total + Ext', __FILE__));
							$eqlogic->checkAndUpdateCmd('mode_basic', __('Total', __FILE__));
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'C':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', __('Nuit + Ext', __FILE__));
							$eqlogic->checkAndUpdateCmd('mode_basic', __('Nuit', __FILE__));
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'B':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							if ( $eqlogic->getConfiguration('alarmtype') == 1 ) { $eqlogic->checkAndUpdateCmd('mode', __('Jour + Ext', __FILE__)); $eqlogic->checkAndUpdateCmd('mode_basic', __('Jour', __FILE__)); }
							if ( $eqlogic->getConfiguration('alarmtype') == 3 ) { $eqlogic->checkAndUpdateCmd('mode', __('Partiel + Ext', __FILE)); $eqlogic->checkAndUpdateCmd('mode_basic', __('Partiel', __FILE__)); }
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'Erreur commande Verisure':
							//throw new Exception($state);
							log::add('verisure', 'debug', '│ /!\ Verisure command error GetStateAlarm()');
							log::add('verisure', 'debug', '└───────── Failed to update status ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(0));
						break;
					}
				break;

				case 'getstatehisto': 												// LogicalId de la commande
					$statesHisto = $eqlogic->GetStateAlarmFromHistory(); 			// On lance la fonction GetStateAlarmFromHistory() pour récupérer l'historique des statuts de l'alarme

                    // On récupère uniquement les événements
                    $history = $statesHisto['reg'] ?? [];

					// Appel de ta fonction d’analyse
                    $state = $eqlogic->ConvertVerisureToAlarmState($history);
					log::add('verisure', 'debug', '│ Analysis result = ' . $state);

					switch ($state)  {
						case 'D':
							$eqlogic->checkAndUpdateCmd('state', "0");			// On met à jour la commande avec le LogicalId 'state' de l'eqlogic
							$eqlogic->checkAndUpdateCmd('enable', "0");
							$eqlogic->checkAndUpdateCmd('mode', __('Désactivée', __FILE__));
							$eqlogic->checkAndUpdateCmd('mode_basic', __('Désactivée', __FILE__));
							log::add('verisure', 'debug', '└───────── Status updated successfully from history ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'T':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', __('Total', __FILE__));
							$eqlogic->checkAndUpdateCmd('mode_basic', __('Total', __FILE__));
							log::add('verisure', 'debug', '└───────── Status updated successfully from history ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'Q':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', __('Nuit', __FILE__));
							$eqlogic->checkAndUpdateCmd('mode_basic', __('Nuit', __FILE__));
							log::add('verisure', 'debug', '└───────── Status updated successfully from history ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'P':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							if ( $eqlogic->getConfiguration('alarmtype') == 1 ) { $eqlogic->checkAndUpdateCmd('mode', __('Jour', __FILE__)); $eqlogic->checkAndUpdateCmd('mode_basic', __('Jour', __FILE__)); }
							if ( $eqlogic->getConfiguration('alarmtype') == 3 ) { $eqlogic->checkAndUpdateCmd('mode', __('Partiel', __FILE__)); $eqlogic->checkAndUpdateCmd('mode_basic', __('Partiel', __FILE__)); }
							log::add('verisure', 'debug', '└───────── Status updated successfully from history ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'E':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', __('Extérieur', __FILE__));
							$eqlogic->checkAndUpdateCmd('mode_basic', __('Extérieur', __FILE__));
							log::add('verisure', 'debug', '└───────── Status updated successfully from history ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'A':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', __('Total + Ext', __FILE__));
							$eqlogic->checkAndUpdateCmd('mode_basic', __('Total', __FILE__));
							log::add('verisure', 'debug', '└───────── Status updated successfully from history ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'C':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', __('Nuit + Ext', __FILE__));
							$eqlogic->checkAndUpdateCmd('mode_basic', __('Nuit', __FILE__));
							log::add('verisure', 'debug', '└───────── Status updated successfully from history ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'B':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							if ( $eqlogic->getConfiguration('alarmtype') == 1 ) { $eqlogic->checkAndUpdateCmd('mode', __('Jour + Ext', __FILE__)); $eqlogic->checkAndUpdateCmd('mode_basic', __('Jour', __FILE__)); }
							if ( $eqlogic->getConfiguration('alarmtype') == 3 ) { $eqlogic->checkAndUpdateCmd('mode', __('Partiel + Ext', __FILE__)); $eqlogic->checkAndUpdateCmd('mode_basic', __('Partiel', __FILE__)); }
							log::add('verisure', 'debug', '└───────── Status updated successfully from history ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						default:
							//throw new Exception($state);
							log::add('verisure', 'debug', '│ /!\ Verisure command error GetStateAlarmFromHistory()');
							log::add('verisure', 'debug', '└───────── Failed to update status from history ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(0));
							break;
					}
				break;
					
				case 'armed':
					$state = $eqlogic->ArmTotalAlarm();
					switch ($state)  {
						case 'T':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', __('Total', __FILE__));
							$eqlogic->checkAndUpdateCmd('mode_basic', __('Total', __FILE__));
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'A':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', __('Total + Ext', __FILE__));
							$eqlogic->checkAndUpdateCmd('mode_basic', __('Total', __FILE__));
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'Erreur commande Verisure':
							log::add('verisure', 'debug', '│ /!\ Verisure command error ArmTotalAlarm()');
							log::add('verisure', 'debug', '└───────── Failed to activate Total mode ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(0));
						break;
					}
				break;

				case 'armed_night':
					$state = $eqlogic->ArmNightAlarm();
					switch ($state)  {
						case 'Q':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', __('Nuit', __FILE__));
							$eqlogic->checkAndUpdateCmd('mode_basic', __('Nuit', __FILE__));
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'C':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', __('Nuit + Ext', __FILE));
							$eqlogic->checkAndUpdateCmd('mode_basic', __('Nuit', __FILE__));
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'Erreur commande Verisure':
							log::add('verisure', 'debug', '│ /!\ Verisure command error ArmNightAlarm()');
							log::add('verisure', 'debug', '└───────── Failed to activate Night mode ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(0));
						break;
					}
				break;
				
				case 'armed_day':
					$state = $eqlogic->ArmDayAlarm();
					switch ($state)  {
						case 'P':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							if ( $eqlogic->getConfiguration('alarmtype') == 1 ) { $eqlogic->checkAndUpdateCmd('mode', __('Jour', __FILE__));	$eqlogic->checkAndUpdateCmd('mode_basic', __('Jour', __FILE__)); }
							if ( $eqlogic->getConfiguration('alarmtype') == 3 ) { $eqlogic->checkAndUpdateCmd('mode', __('Partiel', __FILE__)); $eqlogic->checkAndUpdateCmd('mode_basic', __('Partiel', __FILE__)); }
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'B':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							if ( $eqlogic->getConfiguration('alarmtype') == 1 ) { $eqlogic->checkAndUpdateCmd('mode', __('Jour + Ext', __FILE)); $eqlogic->checkAndUpdateCmd('mode_basic', __('Jour', __FILE__)); }
							if ( $eqlogic->getConfiguration('alarmtype') == 3 ) { $eqlogic->checkAndUpdateCmd('mode', __('Partiel + Ext', __FILE__)); $eqlogic->checkAndUpdateCmd('mode_basic', __('Partiel', __FILE__)); }
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'Erreur commande Verisure':
							log::add('verisure', 'debug', '│ /!\ Verisure command error ArmDayAlarm()');
							if ( $eqlogic->getConfiguration('alarmtype') == 1 ) { log::add('verisure', 'debug', '└───────── Failed to activate Day mode ─────────'); }
							if ( $eqlogic->getConfiguration('alarmtype') == 3 ) { log::add('verisure', 'debug', '└───────── Failed to activate Partial mode ─────────'); }
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(0));
						break;
					}
				break;
					
				case 'armed_ext':
					$state = $eqlogic->ArmExtAlarm();
					switch ($state)  {
						case 'E':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', __('Extérieur', __FILE__));
							$eqlogic->checkAndUpdateCmd('mode_basic', __('Extérieur', __FILE__));
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'A':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', __('Total + Ext', __FILE__));
							$eqlogic->checkAndUpdateCmd('mode_basic', __('Total', __FILE__));
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'C':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', __('Nuit + Ext', __FILE__));
							$eqlogic->checkAndUpdateCmd('mode_basic', __('Nuit', __FILE__));
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'B':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							if ( $eqlogic->getConfiguration('alarmtype') == 1 ) { $eqlogic->checkAndUpdateCmd('mode', __('Jour + Ext', __FILE__)); $eqlogic->checkAndUpdateCmd('mode_basic', __('Jour', __FILE__)); }
							if ( $eqlogic->getConfiguration('alarmtype') == 3 ) { $eqlogic->checkAndUpdateCmd('mode', __('Partiel + Ext', __FILE__)); $eqlogic->checkAndUpdateCmd('mode_basic', __('Partiel', __FILE__)); }
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'Erreur commande Verisure':
							log::add('verisure', 'debug', '│ /!\ Verisure command error ArmExtAlarm()');
							log::add('verisure', 'debug', '└───────── Failed to activate External mode ─────────');
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
							$eqlogic->checkAndUpdateCmd('mode', __('Désactivée', __FILE__));
							$eqlogic->checkAndUpdateCmd('mode_basic', __('Désactivée', __FILE__));
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'Erreur commande Verisure':
							log::add('verisure', 'debug', '│ /!\ Verisure command error DisarmAlarm()');
							log::add('verisure', 'debug', '└───────── Failed to deactivate ─────────');
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
								log::add('verisure', 'debug', '│ /!\ Verisure command error SetSmartplugState()');
								log::add('verisure', 'debug', '└───────── Set SmartPlug request failed ─────────');
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
								log::add('verisure', 'debug', '│ /!\ Verisure command error SetSmartplugState()');
								log::add('verisure', 'debug', '└───────── Set SmartPlug request failed ─────────');
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
						$eqlogic->checkAndUpdateCmd('mode', __('Désactivée', __FILE__));
						$eqlogic->checkAndUpdateCmd('mode_basic', __('Désactivée', __FILE__));
					break;
					case 'ARMED_AWAY':
						$eqlogic->checkAndUpdateCmd('enable', "1");
						$eqlogic->checkAndUpdateCmd('mode', __('Total', __FILE__));
						$eqlogic->checkAndUpdateCmd('mode_basic', __('Total', __FILE__));
					break;
					case 'ARMED_HOME':
						$eqlogic->checkAndUpdateCmd('enable', "1");
						$eqlogic->checkAndUpdateCmd('mode', __('Partiel', __FILE__));
						$eqlogic->checkAndUpdateCmd('mode_basic', __('Partiel', __FILE__));
					break;
					case 'Erreur commande Verisure':
						log::add('verisure', 'debug', '│ /!\ Verisure command error GetStateAlarm()');
						log::add('verisure', 'debug', '└───────── Failed to update status ─────────');
					break;
				}
			}
			
			if ($logical == 'released')   {			
				$state = $eqlogic->DisarmAlarm();
				switch ($state)  {
					case 'DISARMED':
						$eqlogic->checkAndUpdateCmd('state', "0");	
						$eqlogic->checkAndUpdateCmd('enable', "0");	
						$eqlogic->checkAndUpdateCmd('mode', __('Désactivée', __FILE__));
						$eqlogic->checkAndUpdateCmd('mode_basic', __('Désactivée', __FILE__));
					break;
					case 'Erreur commande Verisure':
						log::add('verisure', 'debug', '│ /!\ Verisure command error DisarmAlarm()');
						log::add('verisure', 'debug', '└───────── Failed to deactivate ─────────');
					break;
				}	
			}
			
			if ($logical == 'armed_home')   {
				$state = $eqlogic->ArmHomeAlarm();
				switch ($state)  {
					case 'ARMED_HOME':
						$eqlogic->checkAndUpdateCmd('enable', "1");	
						$eqlogic->checkAndUpdateCmd('mode', __('Partiel', __FILE__));
						$eqlogic->checkAndUpdateCmd('mode_basic', __('Partiel', __FILE__));
					break;
					case 'Erreur commande Verisure':
						log::add('verisure', 'debug', '│ /!\ Verisure command error ArmHomeAlarm()');
						log::add('verisure', 'debug', '└───────── Failed to activate Partial mode ─────────');
					break;
				}	
			}
					
			if ($logical == 'armed')   {
				$state = $eqlogic->ArmTotalAlarm();
				switch ($state)  {
					case 'ARMED_AWAY':
						$eqlogic->checkAndUpdateCmd('enable', "1");	
						$eqlogic->checkAndUpdateCmd('mode', __('Total', __FILE__));
						$eqlogic->checkAndUpdateCmd('mode_basic', __('Total', __FILE__));
					break;
					case 'Erreur commande Verisure':
						log::add('verisure', 'debug', '│ /!\ Verisure command error ArmTotalAlarm()');
						log::add('verisure', 'debug', '└───────── Failed to activate Total mode ─────────');
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
								log::add('verisure', 'debug', '│ /!\ Verisure command error SetStateLock()');
								log::add('verisure', 'debug', '└───────── Set connectedLock request failed ─────────');
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
								log::add('verisure', 'debug', '│ /!\ Verisure command error SetStateLock()');
								log::add('verisure', 'debug', '└───────── Set connectedLock request failed ─────────');
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