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
			if ( date('G') == 0 && date('i') < 5 ) return;											// Pas de refresh entre 0h00 et 0h05 car maintenance des serveurs
			log::add('verisure', 'debug', 'Exécution du cron30');
			$cmdState->execCmd(); 																	// la commande existe on la lance
		}	
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
		
		$this->createCmd();
	}

	/* fonction appelée pendant la séquence de sauvegarde avant l'insertion 
     * dans la base de données pour une mise à jour d'une entrée */
    public function preUpdate() {
		
		if ( $this->getConfiguration('alarmtype') == 1 )   {
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
			
		$this->emptyCacheWidget(); 		//vide le cache. Pratique pour le développement

		// Traitement des commandes infos
		foreach ($this->getCmd('info') as $cmd) {
			$replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
			$replace['#' . $cmd->getLogicalId() . '_name#'] = $cmd->getName();
			$replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd();
			$replace['#' . $cmd->getLogicalId() . '_visible#'] = $cmd->getIsVisible();
		}

		// Traitement des commandes actions
		foreach ($this->getCmd('action') as $cmd) {
			$replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
			$replace['#' . $cmd->getLogicalId() . '_visible#'] = $cmd->getIsVisible();
			if ($cmd->getSubType() == 'select') {
				$listValue = "<option value>" . $cmd->getName() . "</option>";
				$listValueArray = explode(';', $cmd->getConfiguration('listValue'));
				foreach ($listValueArray as $value) {
					list($id, $name) = explode('|', $value);
					$listValue = $listValue . "<option value=" . $id . ">" . $name . "</option>";
				}
				$replace['#' . $cmd->getLogicalId() . '_listValue#'] = $listValue;
			}
		}
			
		// On definit le template à appliquer par rapport à la version Jeedom utilisée
		if (version_compare(jeedom::version(), '4.0.0') >= 0) {
			if ( $this->getConfiguration('alarmtype') == 1 ) { $template = 'verisure_dashboard_v4_type1'; }
			if ( $this->getConfiguration('alarmtype') == 2 ) { $template = 'verisure_dashboard_v4_type2'; }
		}
		else {
			if ( $this->getConfiguration('alarmtype') == 1 ) { $template = 'verisure_dashboard_v3_type1'; }
			if ( $this->getConfiguration('alarmtype') == 2 ) { $template = 'verisure_dashboard_v3_type2'; }
		}
		$replace['#template#'] = $template;

		return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, $template, 'verisure')));
	}
    
    /* Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    } */

    /* Non obligatoire mais ca permet de déclencher une action avant modification de variable de configuration
    public static function preConfig_<Variable>() {
    } */
	 
	private function createCmd() {
		
		$infoArmed = $this->getCmd(null, 'enable');			//0 = désarmée - 1 = armée
		if (!is_object($infoArmed)) {
			$infoArmed = new verisureCmd();
			$infoArmed->setOrder(1);
			$infoArmed->setName('Etat Activation');
			$infoArmed->setEqLogic_id($this->getId());
			$infoArmed->setLogicalId('enable');
			$infoArmed->setType('info');
			$infoArmed->setSubType('binary');
			$infoArmed->setDisplay('generic_type', 'ALARM_ENABLE_STATE');
			$infoArmed->setTemplate('dashboard', 'lock');
			$infoArmed->setTemplate('mobile', 'lock');
			$infoArmed->save();
			log::add('verisure', 'debug', 'Création de la commande '.$infoArmed->getName().' (LogicalId : '.$infoArmed->getLogicalId().')');
		}
					
		$infoState = $this->getCmd(null, 'state');			//0 = normale - 1 = déclenchée
		if (!is_object($infoState)) {
			$infoState = new verisureCmd();
			$infoState->setOrder(2);
			$infoState->setName('Etat Alarme');
			$infoState->setEqLogic_id($this->getId());
			$infoState->setLogicalId('state');
			$infoState->setType('info');
			$infoState->setSubType('binary');
			$infoState->setDisplay('invertBinary', 1);
			$infoState->setDisplay('generic_type', 'ALARM_STATE');
			$infoState->setTemplate('dashboard', 'alert');
			$infoState->setTemplate('mobile', 'alert');
			$infoState->save();
			log::add('verisure', 'debug', 'Création de la commande '.$infoState->getName().' (LogicalId : '.$infoState->getLogicalId().')');
		}
					
		$infoMode = $this->getCmd(null, 'mode');
		if (!is_object($infoMode)) {
			$infoMode = new verisureCmd();
			$infoMode->setOrder(3);
			$infoMode->setName('Mode Alarme');
			$infoMode->setEqLogic_id($this->getId());
			$infoMode->setLogicalId('mode');
			$infoMode->setType('info');
			$infoMode->setSubType('string');
			$infoMode->setDisplay('generic_type', 'ALARM_MODE');
			$infoMode->setTemplate('dashboard', 'tile');
			$infoMode->setTemplate('mobile', 'tile');
			$infoMode->save();
			log::add('verisure', 'debug', 'Création de la commande '.$infoMode->getName().' (LogicalId : '.$infoMode->getLogicalId().')');
		}
					
		$cmdArmed = $this->getCmd(null, 'armed');
		if (!is_object($cmdArmed)) {
			$cmdArmed = new verisureCmd();
			$cmdArmed->setOrder(4);
			$cmdArmed->setName('Mode Total');
			$cmdArmed->setEqLogic_id($this->getId());
			$cmdArmed->setLogicalId('armed');
			$cmdArmed->setType('action');
			$cmdArmed->setSubType('other');
			$cmdArmed->setDisplay('generic_type', 'ALARM_ARMED');
			$cmdArmed->save();
			log::add('verisure', 'debug', 'Création de la commande '.$cmdArmed->getName().' (LogicalId : '.$cmdArmed->getLogicalId().')');
		}
		$this->setConfiguration('SetModeAbsent',$cmdArmed->getId()."|"."Total");		//Compatibilité Homebridge  - Mode Absent / A distance
					
		$cmdReleased = $this->getCmd(null, 'released');
		if (!is_object($cmdReleased)) {
			$cmdReleased = new verisureCmd();
			$cmdReleased->setOrder(5);
			$cmdReleased->setName('Désactiver');
			$cmdReleased->setEqLogic_id($this->getId());
			$cmdReleased->setLogicalId('released');
			$cmdReleased->setType('action');
			$cmdReleased->setSubType('other');
			$cmdReleased->setDisplay('generic_type', 'ALARM_RELEASED');
			$cmdReleased->save();
			log::add('verisure', 'debug', 'Création de la commande '.$cmdReleased->getName().' (LogicalId : '.$cmdReleased->getLogicalId().')');
		}
		
		$cmdState = $this->getCmd(null, 'getstate');
		if ( ! is_object($cmdState)) {
			$cmdState = new verisureCmd();
			$cmdState->setOrder(6);
			$cmdState->setName('Rafraichir');
			$cmdState->setEqLogic_id($this->getId());
			$cmdState->setLogicalId('getstate');
			$cmdState->setType('action');
			$cmdState->setSubType('other');
			$cmdState->save();
			log::add('verisure', 'debug', 'Création de la commande '.$cmdState->getName().' (LogicalId : '.$cmdState->getLogicalId().')');
		}
		
		if ( $this->getConfiguration('alarmtype') == 1 )   { 
		
			$cmdArmedNight = $this->getCmd(null, 'armed_night');
			if (!is_object($cmdArmedNight)) {
				$cmdArmedNight = new verisureCmd();
				$cmdArmedNight->setOrder(7);
				$cmdArmedNight->setName('Mode Nuit');
				$cmdArmedNight->setEqLogic_id($this->getId());
				$cmdArmedNight->setLogicalId('armed_night');
				$cmdArmedNight->setType('action');
				$cmdArmedNight->setSubType('other');
				$cmdArmedNight->setDisplay('generic_type', 'ALARM_SET_MODE');
				$cmdArmedNight->save();
				log::add('verisure', 'debug', 'Création de la commande '.$cmdArmedNight->getName().' (LogicalId : '.$cmdArmedNight->getLogicalId().')');
			}
			$this->setConfiguration('SetModeNuit',$cmdArmedNight->getId()."|"."Nuit");			//Compatibilité Homebridge - Mode Nuit
			
			$cmdArmedDay = $this->getCmd(null, 'armed_day');
			if (!is_object($cmdArmedDay)) {
				$cmdArmedDay = new verisureCmd();
				$cmdArmedDay->setOrder(8);
				$cmdArmedDay->setName('Mode Jour');
				$cmdArmedDay->setEqLogic_id($this->getId());
				$cmdArmedDay->setLogicalId('armed_day');
				$cmdArmedDay->setType('action');
				$cmdArmedDay->setSubType('other');
				$cmdArmedDay->setDisplay('generic_type', 'ALARM_SET_MODE');
				$cmdArmedDay->save();
				log::add('verisure', 'debug', 'Création de la commande '.$cmdArmedDay->getName().' (LogicalId : '.$cmdArmedDay->getLogicalId().')');
			}
			$this->setConfiguration('SetModePresent',$cmdArmedDay->getId()."|"."Jour");			//Compatibilité Homebridge - Mode Présent / Domicile
			
			$cmdArmedExt = $this->getCmd(null, 'armed_ext');
			if (!is_object($cmdArmedExt)) {
				$cmdArmedExt = new verisureCmd();
				$cmdArmedExt->setOrder(9);
				$cmdArmedExt->setName('Mode Extérieur');
				$cmdArmedExt->setEqLogic_id($this->getId());
				$cmdArmedExt->setLogicalId('armed_ext');
				$cmdArmedExt->setType('action');
				$cmdArmedExt->setSubType('other');
				$cmdArmedExt->save();
				log::add('verisure', 'debug', 'Création de la commande '.$cmdArmedExt->getName().' (LogicalId : '.$cmdArmedExt->getLogicalId().')');
			}
					
			$cmdPictures = $this->getCmd(null, 'getpictures');
			if ( ! is_object($cmdPictures)) {
				$cmdPictures = new verisureCmd();
				$cmdPictures->setOrder(10);
				$cmdPictures->setName('Demande Images');
				$cmdPictures->setEqLogic_id($this->getId());
				$cmdPictures->setLogicalId('getpictures');
				$cmdPictures->setType('action');
				$cmdPictures->setSubType('select');
				log::add('verisure', 'debug', 'Création de la commande '.$cmdPictures->getName().' (LogicalId : '.$cmdPictures->getLogicalId().')');
			}	
			$device_array = $this->getConfiguration('devices');
			for ($j = 0; $j < $this->getConfiguration('nb_smartplug'); $j++)  {
				if ($device_array['smartplugType'.$j] == "YR" || $device_array['smartplugType'.$j] == "XR" || $device_array['smartplugType'.$j] == "XP" || $device_array['smartplugType'.$j] == "QR")  {
					if (isset($listValue))  { $listValue = $listValue .';'. $device_array['smartplugID'.$j].'|'.$device_array['smartplugName'.$j];  }
					else  { $listValue = $device_array['smartplugID'.$j].'|'.$device_array['smartplugName'.$j];  }
				}
			}
			log::add('verisure', 'debug', $this->getHumanName().' - Mise à jour liste smartplugs compatibles images : '.var_export($listValue, true));
			$cmdPictures->setConfiguration('listValue', $listValue);
			$cmdPictures->save();
			
			$cmdNetworkState = $this->getCmd(null, 'networkstate');
			if ( ! is_object($cmdNetworkState)) {
				$cmdNetworkState = new verisureCmd();
				$cmdNetworkState->setOrder(11);
				$cmdNetworkState->setName('Qualité Réseau');
				$cmdNetworkState->setEqLogic_id($this->getId());
				$cmdNetworkState->setLogicalId('networkstate');
				$cmdNetworkState->setType('info');
				$cmdNetworkState->setSubType('numeric');
				$cmdNetworkState->save();
				log::add('verisure', 'debug', 'Création de la commande '.$cmdNetworkState->getName().' (LogicalId : '.$cmdNetworkState->getLogicalId().')');
			}
		}
		
		if ( $this->getConfiguration('alarmtype') == 2 )   { 
			
			$cmdArmedHome = $this->getCmd(null, 'armed_home');
			if (!is_object($cmdArmedHome)) {
				$cmdArmedHome = new verisureCmd();
				$cmdArmedHome->setOrder(7);
				$cmdArmedHome->setName('Mode Partiel');
				$cmdArmedHome->setEqLogic_id($this->getId());
				$cmdArmedHome->setLogicalId('armed_home');
				$cmdArmedHome->setType('action');
				$cmdArmedHome->setSubType('other');
				$cmdArmedHome->setDisplay('generic_type', 'ALARM_SET_MODE');
				$cmdArmedHome->save();
				log::add('verisure', 'debug', 'Création de la commande '.$cmdArmedHome->getName().' (LogicalId : '.$cmdArmedHome->getLogicalId().')');
			}
			$this->setConfiguration('SetModePresent',$cmdArmedHome->getId()."|"."Partiel");			//Compatibilité Homebridge - Mode Présent / Domicile
			
			$cmdPictures = $this->getCmd(null, 'getpictures');
			if ( ! is_object($cmdPictures)) {
				$cmdPictures = new verisureCmd();
				$cmdPictures->setOrder(8);
				$cmdPictures->setName('Demande Images');
				$cmdPictures->setEqLogic_id($this->getId());
				$cmdPictures->setLogicalId('getpictures');
				$cmdPictures->setType('action');
				$cmdPictures->setSubType('select');
				log::add('verisure', 'debug', 'Création de la commande '.$cmdPictures->getName().' (LogicalId : '.$cmdPictures->getLogicalId().')');
			}	
			$device_array = $this->getConfiguration('devices');
			for ($j = 0; $j < $this->getConfiguration('nb_smartplug'); $j++)  {
				if ($device_array['smartplugType'.$j] == "cameraDevice")  {
					$smartplugID = str_replace(" ","%20", $device_array['smartplugID'.$j]);
					if (isset($listValue))  { $listValue = $listValue .';'.$smartplugID.'|'.$device_array['smartplugName'.$j];  }
					else  { $listValue = $smartplugID.'|'.$device_array['smartplugName'.$j];  }
				}
			}
			log::add('verisure', 'debug', $this->getHumanName().' - Mise à jour liste smartplugs compatibles images : '.var_export($listValue, true));
			$cmdPictures->setConfiguration('listValue', $listValue);
			$cmdPictures->save();
			
			//Création des 3 commandes des smartPlugs
			for ($j = 0; $j < $this->getConfiguration('nb_smartplug'); $j++)  {
				$i = 0;
				if ($device_array['smartplugType'.$j] == "smartPlugDevice")  {
					$cmdDeviceState = $this->getCmd(null, $device_array['smartplugID'.$j].'::State');
					if ( ! is_object($cmdDeviceState)) {
						$cmdDeviceState = new verisureCmd();
						$cmdDeviceState->setOrder(9+3*$i);
						$cmdDeviceState->setName('Smartplug '.$device_array['smartplugName'.$j].' Etat');
						$cmdDeviceState->setEqLogic_id($this->getId());
						$cmdDeviceState->setLogicalId($device_array['smartplugID'.$j].'::State');
						$cmdDeviceState->setType('info');
						$cmdDeviceState->setSubType('binary');
						$cmdDeviceState->setDisplay('generic_type', 'ENERGY_STATE');
						$cmdDeviceState->setIsVisible(0);
						$cmdDeviceState->save();
						log::add('verisure', 'debug', 'Création de la commande '.$cmdDeviceState->getName().' (LogicalId : '.$cmdDeviceState->getLogicalId().')');
					}
					
					$cmdDeviceOn = $this->getCmd(null, $device_array['smartplugID'.$j].'::On');
					if ( ! is_object($cmdDeviceOn)) {
						$cmdDeviceOn = new verisureCmd();
						$cmdDeviceOn->setOrder(10+3*$i);
						$cmdDeviceOn->setName('Smartplug '.$device_array['smartplugName'.$j].' On');
						$cmdDeviceOn->setEqLogic_id($this->getId());
						$cmdDeviceOn->setLogicalId($device_array['smartplugID'.$j].'::On');
						$cmdDeviceOn->setType('action');
						$cmdDeviceOn->setSubType('other');
						$cmdDeviceOn->setDisplay('generic_type', 'ENERGY_ON');
						$cmdDeviceOn->setIsVisible(0);
						$cmdDeviceOn->save();
						log::add('verisure', 'debug', 'Création de la commande '.$cmdDeviceOn->getName().' (LogicalId : '.$cmdDeviceOn->getLogicalId().')');
					}	
					
					$cmdDeviceOff = $this->getCmd(null, $device_array['smartplugID'.$j].'::Off');
					if ( ! is_object($cmdDeviceOff)) {
						$cmdDeviceOff = new verisureCmd();
						$cmdDeviceOff->setOrder(11+3*$i);
						$cmdDeviceOff->setName('Smartplug '.$device_array['smartplugName'.$j].' Off');
						$cmdDeviceOff->setEqLogic_id($this->getId());
						$cmdDeviceOff->setLogicalId($device_array['smartplugID'.$j].'::Off');
						$cmdDeviceOff->setType('action');
						$cmdDeviceOff->setSubType('other');
						$cmdDeviceOff->setDisplay('generic_type', 'ENERGY_OFF');
						$cmdDeviceOff->setIsVisible(0);
						$cmdDeviceOff->save();
						log::add('verisure', 'debug', 'Création de la commande '.$cmdDeviceOff->getName().' (LogicalId : '.$cmdDeviceOff->getLogicalId().')');
					}
					$i++;	
				}
			}
			
			//Création de la commande des Climates
			for ($j = 0; $j < $this->getConfiguration('nb_smartplug'); $j++)  {
				$i = 0;
				if ($device_array['smartplugType'.$j] == "climateDevice")  {
					$cmdDeviceTemp = $this->getCmd(null, $device_array['smartplugID'.$j].'::Temp');
					if ( ! is_object($cmdDeviceTemp)) {
						$cmdDeviceTemp = new verisureCmd();
						$cmdDeviceTemp->setName('Temperature '.$device_array['smartplugName'.$j]);
						$cmdDeviceTemp->setEqLogic_id($this->getId());
						$cmdDeviceTemp->setLogicalId($device_array['smartplugID'.$j].'::Temp');
						$cmdDeviceTemp->setType('info');
						$cmdDeviceTemp->setSubType('numeric');
						$cmdDeviceTemp->setDisplay('generic_type', 'TEMPERATURE');
						$cmdDeviceTemp->setIsVisible(0);
						$cmdDeviceTemp->save();
						log::add('verisure', 'debug', 'Création de la commande '.$cmdDeviceTemp->getName().' (LogicalId : '.$cmdDeviceTemp->getLogicalId().')');
					}
				
					if ($device_array['smartplugModel'.$j] == "SMOKE3")   {
						$cmdDeviceHumidity = $this->getCmd(null, $device_array['smartplugID'.$j].'::Humidity');
						if ( ! is_object($cmdDeviceHumidity)) {
							$cmdDeviceHumidity = new verisureCmd();
							$cmdDeviceHumidity->setName('Humidite '.$device_array['smartplugName'.$j]);
							$cmdDeviceHumidity->setEqLogic_id($this->getId());
							$cmdDeviceHumidity->setLogicalId($device_array['smartplugID'.$j].'::Humidity');
							$cmdDeviceHumidity->setType('info');
							$cmdDeviceHumidity->setSubType('numeric');
							$cmdDeviceHumidity->setDisplay('generic_type', 'HUMIDITY');
							$cmdDeviceHumidity->setIsVisible(0);
							$cmdDeviceHumidity->save();
							log::add('verisure', 'debug', 'Création de la commande '.$cmdDeviceHumidity->getName().' (LogicalId : '.$cmdDeviceHumidity->getLogicalId().')');
						}
					}
					$i++;
				}
			}

			//Création de la commande des DoorWindow
			for ($j = 0; $j < $this->getConfiguration('nb_smartplug'); $j++)  {
				$i = 0;
				if ($device_array['smartplugType'.$j] == "doorWindowDevice")  {
					$cmdDeviceDoor = $this->getCmd(null, $device_array['smartplugID'.$j].'::State');
					if ( ! is_object($cmdDeviceDoor)) {
						$cmdDeviceDoor = new verisureCmd();
						$cmdDeviceDoor->setName('Etat ouverture '.$device_array['smartplugName'.$j]);
						$cmdDeviceDoor->setEqLogic_id($this->getId());
						$cmdDeviceDoor->setLogicalId($device_array['smartplugID'.$j].'::State');
						$cmdDeviceDoor->setType('info');
						$cmdDeviceDoor->setSubType('binary');
						$cmdDeviceDoor->setDisplay('generic_type', 'OPENING');
						$cmdDeviceDoor->setIsVisible(0);
						$cmdDeviceDoor->save();
						log::add('verisure', 'debug', 'Création de la commande '.$cmdDeviceDoor->getName().' (LogicalId : '.$cmdDeviceDoor->getLogicalId().')');
					}
					$i++;					
				}
			}
		}
		
		$this->save(true);		//paramètre "true" -> ne lance pas le postsave()
	}
	 

    /*     * **********************Getteur Setteur*************************** */

	public function Authentication_2FA($alarmtype,$numinstall,$username,$password,$code,$country)	{		//Type 1 & 2
		
		if ( $alarmtype == 1 )   {
			log::add('verisure', 'debug', '┌───────── Démarrage de l\'authentification 2FA ─────────');
			log::add('verisure', 'debug', '│ Alarme type '.$alarmtype);
			$MyAlarm = new verisureAPI($numinstall,$username,$password,$country);
			$result_Login = $MyAlarm->Login();
          	log::add('verisure', 'debug', '│ Request mkLoginToken - httpRespCode => '.$result_Login[0].' - response => '.$result_Login[1]);
          	$response_Login = json_decode($result_Login[1], true);

			if ( $response_Login['data']['xSLoginToken']['needDeviceAuthorization'] == true) {
				$result_ValidateDevice = $MyAlarm->ValidateDevice(null);
          		log::add('verisure', 'debug', '│ Request mkValidateDevice - httpRespCode => '.$result_ValidateDevice[0].' - response => '.$result_ValidateDevice[1]);
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
          		log::add('verisure', 'debug', '│ Request mkInstallationList - httpRespCode => '.$result_ListInstallations[0].' - response => '.$result_ListInstallations[1]);
				$result_ListDevices = $MyAlarm->ListDevices();
          		log::add('verisure', 'debug', '│ Request xSDeviceList - httpRespCode => '.$result_ListDevices[0].' - response => '.$result_ListDevices[1]);				
				$response_ListDevices = json_decode($result_ListDevices[1], true);
				
				$result_Logout = $MyAlarm->Logout();
          		log::add('verisure', 'debug', '│ Request mkLogout - httpRespCode => '.$result_Logout[0].' - response => '.$result_Logout[1]);
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
			$result_Login = $MyAlarm->Login_MFA();
          	log::add('verisure', 'debug', '│ Request Login 2FA - domain => '.$result_Login[0].' - httpRespCode => '.$result_Login[1].' - response => '.$result_Login[2]);
			$response_Login = json_decode($result_Login[2], true);
			
			if ( $result_Login[1] == 401 ) {
				$result_Logout = $MyAlarm->Logout();
				log::add('verisure', 'error', '│ Request Logout - httpRespCode => '.$result_Logout[0].' - response => Multifactor authentication needed');
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
					$result_fetchAllInstallations = $MyAlarm->fetchAllInstallations();	
					log::add('verisure', 'debug', '│ Request fetchAllInformations - httpRespCode => '.$result_fetchAllInstallations[0].' - response => '.$result_fetchAllInstallations[1]);
					$result_ListDevices = $MyAlarm->ListDevices();
          			log::add('verisure', 'debug', '│ Request Devices - httpRespCode => '.$result_ListDevices[0].' - response => '.$result_ListDevices[1]);				
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

	public function Send_OTP($alarmtype,$numinstall,$username,$password,$code,$country, $phone_id)	{		//Type 1 & 2
		
		if ( $alarmtype == 1 )   {

			$MyAlarm = new verisureAPI($numinstall,$username,$password,$country);
			$result_SendOTP = $MyAlarm->SendOTP($phone_id);
			log::add('verisure', 'debug', '│ Request mkSendOTP - httpRespCode => '.$result_SendOTP[0].' - response => '.$result_SendOTP[1]);
			return null;
		}

		if ( $alarmtype == 2 )   {

			$MyAlarm = new verisureAPI2($username,$password,$code);
			$result_RequestMFA = $MyAlarm->RequestMFA($phone_id);
			log::add('verisure', 'debug', '│ Request RequestMFA - domain => '.$result_RequestMFA[0].' - httpRespCode => '.$result_RequestMFA[1].' - response => '.$result_RequestMFA[2]);
			return null;
		}
	}

	public function Validate_Device($alarmtype,$numinstall,$username,$password,$code,$country, $sms_code)	{		//Type 1 & 2

		if ( $alarmtype == 1 )   {

			$MyAlarm = new verisureAPI($numinstall,$username,$password,$country);
			$result_ValidateDevice = $MyAlarm->ValidateDevice($sms_code);
          	log::add('verisure', 'debug', '│ Request mkValidateDevice - httpRespCode => '.$result_ValidateDevice[0].' - response => '.$result_ValidateDevice[1]);
			$response_ValidateDevice = json_decode($result_ValidateDevice[1], true);

			if ( $response_ValidateDevice['data']['xSValidateDevice']['res'] == "OK") {

				$result_Login = $MyAlarm->Login();
          		log::add('verisure', 'debug', '│ Request mkLoginToken - httpRespCode => '.$result_Login[0].' - response => '.$result_Login[1]);
          		$result_ListInstallations = $MyAlarm->ListInstallations();
          		log::add('verisure', 'debug', '│ Request mkInstallationList - httpRespCode => '.$result_ListInstallations[0].' - response => '.$result_ListInstallations[1]);
				$result_ListDevices = $MyAlarm->ListDevices();
          		log::add('verisure', 'debug', '│ Request xSDeviceList - httpRespCode => '.$result_ListDevices[0].' - response => '.$result_ListDevices[1]);				
				$response_ListDevices = json_decode($result_ListDevices[1], true);
				$result_Logout = $MyAlarm->Logout();
          		log::add('verisure', 'debug', '│ Request mkLogout - httpRespCode => '.$result_Logout[0].' - response => '.$result_Logout[1]);
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
			log::add('verisure', 'debug', '│ Request ValidateMFA - domain => '.$result_ValidateMFA[0].' - httpRespCode => '.$result_ValidateMFA[1].' - response => '.$result_ValidateMFA[2]);
			$response_ValidateDevice = json_decode($result_ValidateMFA[2], true);

			if ( $response_ValidateDevice['accessToken'] != "") {

				$result_fetchAllInstallations = $MyAlarm->fetchAllInstallations();	
				log::add('verisure', 'debug', '│ Request fetchAllInformations - httpRespCode => '.$result_fetchAllInstallations[0].' - response => '.$result_fetchAllInstallations[1]);
				$result_ListDevices = $MyAlarm->ListDevices();
          		log::add('verisure', 'debug', '│ Request Devices - httpRespCode => '.$result_ListDevices[0].' - response => '.$result_ListDevices[1]);				
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
		
	public function Reset_Token($alarmtype,$numinstall)	{		//Type 1 & 2

		if ( $alarmtype == 1 )   {
			
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
		
			$filename = __PLGBASE__.'/data/'.'cookie.json';
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
	
	public function GetStateAlarm()	{	//Type 1 & 2
		
		if ( $this->getConfiguration('alarmtype') == 1 )   { 
			log::add('verisure', 'debug', '┌───────── Demande de statut ─────────');
			log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
			$result_Login = $MyAlarm->Login();
          	log::add('verisure', 'debug', '│ Request mkLoginToken - httpRespCode => '.$result_Login[0].' - response => '.$result_Login[1]);
			$result_GetStateAlarm = $MyAlarm->GetStateAlarm();
			log::add('verisure', 'debug', '│ Request CheckAlarm - httpRespCode => '.$result_GetStateAlarm[0].' - response => '.$result_GetStateAlarm[1]);
			log::add('verisure', 'debug', '│ Request CheckAlarmStatus - httpRespCode => '.$result_GetStateAlarm[2].' - response => '.$result_GetStateAlarm[3]);
			$response_GetStateAlarm = json_decode($result_GetStateAlarm[3], true);
			$result_Logout = $MyAlarm->Logout();
          	log::add('verisure', 'debug', '│ Request mkLogout - httpRespCode => '.$result_Logout[0].' - response => '.$result_Logout[1]);


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
          	log::add('verisure', 'debug', '│ Request Login - domain => '.$result_Login[0].' - httpRespCode => '.$result_Login[1].' - response => '.$result_Login[2]);
			$result_getStateAlarm = $MyAlarm->getStateAlarm();
			log::add('verisure', 'debug', '│ Request ArmState - httpRespCode => '.$result_getStateAlarm[0].' - response => '.$result_getStateAlarm[1]);
			$response_getStateAlarm = json_decode($result_getStateAlarm[1], true);
			
			//getStateDevice
			$result_getClimatesInformation = $MyAlarm->getClimatesInformation();
			log::add('verisure', 'debug', '│ Request Climate - httpRespCode => '.$result_getClimatesInformation[0].' - response => '.$result_getClimatesInformation[1]);
			$response_getClimatesInformation = json_decode($result_getClimatesInformation[1], true);
			$result_getDoorWindowsInformation = $MyAlarm->getDoorWindowsInformation();
			log::add('verisure', 'debug', '│ Request DoorWindow - httpRespCode => '.$result_getDoorWindowsInformation[0].' - response => '.$result_getDoorWindowsInformation[1]);
			$response_getDoorWindowsInformation = json_decode($result_getDoorWindowsInformation[1], true);
			$result_getCamerasInformation = $MyAlarm->getCamerasInformation();
			log::add('verisure', 'debug', '│ Request Camera - httpRespCode => '.$result_getCamerasInformation[0].' - response => '.$result_getCamerasInformation[1]);
			$response_getCamerasInformation = json_decode($result_getCamerasInformation[1], true);
			$result_getSmartplugsInformation = $MyAlarm->getSmartplugsInformation();
			log::add('verisure', 'debug', '│ Request SmartPlug - httpRespCode => '.$result_getSmartplugsInformation[0].' - response => '.$result_getSmartplugsInformation[1]);
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
	
	public function ArmTotalAlarm()	{	//Type 1 & 2
		
		if ( $this->getConfiguration('alarmtype') == 1 )   { 
			log::add('verisure', 'debug', '┌───────── Demande activation mode total ─────────');
			log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
			$result_Login = $MyAlarm->Login();
          	log::add('verisure', 'debug', '│ Request mkLoginToken - httpRespCode => '.$result_Login[0].' - response => '.$result_Login[1]);
			$result_ArmAlarm = $MyAlarm->ArmAlarm("ARM1", $this->GetAlarmStatus());
			log::add('verisure', 'debug', '│ Request xSArmPanel - httpRespCode => '.$result_ArmAlarm[0].' - response => '.$result_ArmAlarm[1]);
			log::add('verisure', 'debug', '│ Request ArmStatus - httpRespCode => '.$result_ArmAlarm[2].' - response => '.$result_ArmAlarm[3]);
			$response_ArmAlarm = json_decode($result_ArmAlarm[3], true);
			$result_Logout = $MyAlarm->Logout();
          	log::add('verisure', 'debug', '│ Request mkLogout - httpRespCode => '.$result_Logout[0].' - response => '.$result_Logout[1]);


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
          	log::add('verisure', 'debug', '│ Request Login - domain => '.$result_Login[0].' - httpRespCode => '.$result_Login[1].' - response => '.$result_Login[2]);
			$result_setStateAlarm = $MyAlarm->setStateAlarm('armAway');
			log::add('verisure', 'debug', '│ Request armAway - httpRespCode => '.$result_setStateAlarm[0].' - response => '.$result_setStateAlarm[1]);
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
        log::add('verisure', 'debug', '│ Request mkLoginToken - httpRespCode => '.$result_Login[0].' - response => '.$result_Login[1]);
		$result_ArmAlarm = $MyAlarm->ArmAlarm("ARMNIGHT1", $this->GetAlarmStatus());
		log::add('verisure', 'debug', '│ Request xSArmPanel - httpRespCode => '.$result_ArmAlarm[0].' - response => '.$result_ArmAlarm[1]);
		log::add('verisure', 'debug', '│ Request AarmStatus - httpRespCode => '.$result_ArmAlarm[2].' - response => '.$result_ArmAlarm[3]);
		$response_ArmAlarm = json_decode($result_ArmAlarm[3], true);
		$result_Logout = $MyAlarm->Logout();
        log::add('verisure', 'debug', '│ Request mkLogout - httpRespCode => '.$result_Logout[0].' - response => '.$result_Logout[1]);

		if ( $result_ArmAlarm[2] == 200 && $response_ArmAlarm['data']['xSArmStatus']['res'] == "OK" )  {
			$res = $response_ArmAlarm['data']['xSArmStatus']['protomResponse'];
			log::add('verisure', 'debug', '└───────── Activation mode nuit OK ─────────');
		}
		else  {
			$res = "Erreur commande Verisure";
		}
		return $res;
	}
	
	public function ArmDayAlarm()	{	//Type 1
		
		log::add('verisure', 'debug', '┌───────── Demande activation mode jour ─────────');
		log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_Login = $MyAlarm->Login();
        log::add('verisure', 'debug', '│ Request mkLoginToken - httpRespCode => '.$result_Login[0].' - response => '.$result_Login[1]);
		$result_ArmAlarm = $MyAlarm->ArmAlarm("ARMDAY1", $this->GetAlarmStatus());
		log::add('verisure', 'debug', '│ Request xSArmPanel - httpRespCode => '.$result_ArmAlarm[0].' - response => '.$result_ArmAlarm[1]);
		log::add('verisure', 'debug', '│ Request AarmStatus - httpRespCode => '.$result_ArmAlarm[2].' - response => '.$result_ArmAlarm[3]);
		$response_ArmAlarm = json_decode($result_ArmAlarm[3], true);
		$result_Logout = $MyAlarm->Logout();
        log::add('verisure', 'debug', '│ Request mkLogout - httpRespCode => '.$result_Logout[0].' - response => '.$result_Logout[1]);

		if ( $result_ArmAlarm[2] == 200 && $response_ArmAlarm['data']['xSArmStatus']['res'] == "OK" )  {
			$res = $response_ArmAlarm['data']['xSArmStatus']['protomResponse'];
			log::add('verisure', 'debug', '└───────── Activation mode jour OK ─────────');
		}
		else  {
			$res = "Erreur commande Verisure";
		}
		return $res;
	}
	
	public function ArmExtAlarm()	{	//Type 1
		
		log::add('verisure', 'debug', '┌───────── Demande activation mode extérieur ─────────');
		log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_Login = $MyAlarm->Login();
        log::add('verisure', 'debug', '│ Request mkLoginToken - httpRespCode => '.$result_Login[0].' - response => '.$result_Login[1]);
		$result_ArmAlarm = $MyAlarm->ArmAlarm("PERI1", $this->GetAlarmStatus());
		log::add('verisure', 'debug', '│ Request xSArmPanel - httpRespCode => '.$result_ArmAlarm[0].' - response => '.$result_ArmAlarm[1]);
		log::add('verisure', 'debug', '│ Request ArmStatus - httpRespCode => '.$result_ArmAlarm[2].' - response => '.$result_ArmAlarm[3]);
		$response_ArmAlarm = json_decode($result_ArmAlarm[3], true);
		$result_Logout = $MyAlarm->Logout();
        log::add('verisure', 'debug', '│ Request mkLogout - httpRespCode => '.$result_Logout[0].' - response => '.$result_Logout[1]);

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
		log::add('verisure', 'debug', '│ Request Login - domain => '.$result_Login[0].' - httpRespCode => '.$result_Login[1].' - response => '.$result_Login[2]);
		$result_setStateAlarm = $MyAlarm->setStateAlarm('armHome');
		log::add('verisure', 'debug', '│ Request armHome - httpRespCode => '.$result_setStateAlarm[0].' - response => '.$result_setStateAlarm[1]);
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
	
	public function DisarmAlarm()	{	//Type 1 & 2
		
		if ( $this->getConfiguration('alarmtype') == 1 )   { 
			log::add('verisure', 'debug', '┌───────── Demande désactivation ─────────');
			log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
			$result_Login = $MyAlarm->Login();
          	log::add('verisure', 'debug', '│ Request mkLoginToken - httpRespCode => '.$result_Login[0].' - response => '.$result_Login[1]);
			$result_DisarmAlarm = $MyAlarm->DisarmAlarm($this->GetAlarmStatus());
			log::add('verisure', 'debug', '│ Request xSDisarmPanel - httpRespCode => '.$result_DisarmAlarm[0].' - response => '.$result_DisarmAlarm[1]);
			log::add('verisure', 'debug', '│ Request DisarmStatus - httpRespCode => '.$result_DisarmAlarm[2].' - response => '.$result_DisarmAlarm[3]);
			$response_DisarmAlarm = json_decode($result_DisarmAlarm[3], true);
			$result_Logout = $MyAlarm->Logout();
          	log::add('verisure', 'debug', '│ Request mkLogout - httpRespCode => '.$result_Logout[0].' - response => '.$result_Logout[1]);


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
          	log::add('verisure', 'debug', '│ Request Login - domain => '.$result_Login[0].' - httpRespCode => '.$result_Login[1].' - response => '.$result_Login[2]);
			$result_setStateAlarm = $MyAlarm->setStateAlarm('disarm');
			log::add('verisure', 'debug', '│ Request disarm - httpRespCode => '.$result_setStateAlarm[0].' - response => '.$result_setStateAlarm[1]);
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
	
	public function GetReportAlarm()	{		//Type 1 & 2
		
		if ( $this->getConfiguration('alarmtype') == 1 )   {
			
			log::add('verisure', 'debug', '┌───────── Demande du journal d\'activité ─────────');
			log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
			$result_Login = $MyAlarm->Login();
          	log::add('verisure', 'debug', '│ Request mkLoginToken - httpRespCode => '.$result_Login[0].' - response => '.$result_Login[1]);
			$result_GetReportAlarm = $MyAlarm->GetReportAlarm(null);
			log::add('verisure', 'debug', '│ Request ActV2Home - httpRespCode => '.$result_GetReportAlarm[0].' - response => '.$result_GetReportAlarm[1]);
			$response_GetReportAlarm = json_decode($result_GetReportAlarm[1], true);
			$result_Logout = $MyAlarm->Logout();
			log::add('verisure', 'debug', '│ Request mkLogout - httpRespCode => '.$result_Logout[0].' - response => '.$result_Logout[1]);
    
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
          	log::add('verisure', 'debug', '│ Request Login - domain => '.$result_Login[0].' - httpRespCode => '.$result_Login[1].' - response => '.$result_Login[2]);
			$result_getReportAlarm = $MyAlarm->getReportAlarm();
			log::add('verisure', 'debug', '│ Request EventLog - httpRespCode => '.$result_getReportAlarm[0].' - response => '.$result_getReportAlarm[1]);
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

	public function GetPhotosRequest($device)	{		//Type 1 & 2

		if ( $this->getConfiguration('alarmtype') == 1 )   { 
			log::add('verisure', 'debug', '┌───────── Demande de photos ─────────');
			log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
			$result_Login = $MyAlarm->Login();
          	log::add('verisure', 'debug', '│ Request mkLoginToken - httpRespCode => '.$result_Login[0].' - response => '.$result_Login[1]);			
			$result_GetPhotosRequest = $MyAlarm->GetPhotosRequest($device);
			log::add('verisure', 'debug', '│ Request RequestImages - httpRespCode => '.$result_GetPhotosRequest[0].' - response => '.$result_GetPhotosRequest[1]);
			log::add('verisure', 'debug', '│ Request RequestImagesStatus - httpRespCode => '.$result_GetPhotosRequest[2].' - response => '.$result_GetPhotosRequest[3]);	
			log::add('verisure', 'debug', '│ Request ActV2Home - httpRespCode => '.$result_GetPhotosRequest[4].' - response => '.$result_GetPhotosRequest[5]);
			log::add('verisure', 'debug', '│ Request mkGetPhotoImages - httpRespCode => '.$result_GetPhotosRequest[6].' - response => '.$result_GetPhotosRequest[7]);	
			$result_Logout = $MyAlarm->Logout();
			log::add('verisure', 'debug', '│ Request mkLogout - httpRespCode => '.$result_Logout[0].' - response => '.$result_Logout[1]);

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
			log::add('verisure', 'debug', '│ Request Login - domain => '.$result_Login[0].' - httpRespCode => '.$result_Login[1].' - response => '.$result_Login[2]);
			$result_captureImageRequest = $MyAlarm->captureImageRequest($device);
			log::add('verisure', 'debug', '│ Request CaptureImageRequest - httpRespCode => '.$result_captureImageRequest[0].' - response => '.$result_captureImageRequest[1]);
			log::add('verisure', 'debug', '│ Request ImageCaptureStatus - httpRespCode => '.$result_captureImageRequest[2].' - response => '.$result_captureImageRequest[3]);	
			log::add('verisure', 'debug', '│ Request Camera - httpRespCode => '.$result_captureImageRequest[4].' - response => '.$result_captureImageRequest[5]);
			log::add('verisure', 'debug', '│ Request DownloadImage - httpRespCode => '.$result_captureImageRequest[6]);

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
	
	public function SetNetworkState($result)  {		//Type 1
		
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
	
	public function SetEqLogic($numinstall)   {
	
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
		log::add('verisure', 'debug', '│ Request Login - domain => '.$result_Login[0].' - httpRespCode => '.$result_Login[1].' - response => '.$result_Login[2]);
		log::add('verisure', 'debug', '| SmartPlug : '.$device_label.' - Commande envoyée : '.($state?'On':'Off'));
		$result_setStateSmartplug = $MyAlarm->setStateSmartplug($device_label, $state);
		log::add('verisure', 'debug', '│ Request UpdateState - httpRespCode => '.$result_setStateSmartplug[0].' - response => '.$result_setStateSmartplug[1]);
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

	public function GetAlarmStatus() {		//Type1

		$mode = $this->getCmd(null, 'mode');
		if ( $mode == "Désactivée" ) { return "D"; }
		elseif ( $mode == "Total" ) { return "T"; }
		elseif ( $mode == "Nuit" ) { return "Q"; }
		elseif ( $mode == "Jour" ) { return "P"; }
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
		
		if ( $eqlogic->getConfiguration('alarmtype') == 1 )   { 	
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
							$eqlogic->checkAndUpdateCmd('mode', "Jour");
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
							$eqlogic->checkAndUpdateCmd('mode', "Jour");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'B':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Jour + Ext");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
						break;
						case 'Erreur de commande Verisure':
							log::add('verisure', 'debug', '│ /!\ Erreur commande Verisure ArmDayAlarm()');
							log::add('verisure', 'debug', '└───────── Activation mode jour NOK ─────────');
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
		
		$eqlogic->refreshWidget();	
	}
	

    /*     * **********************Getteur Setteur*************************** */
}


?>