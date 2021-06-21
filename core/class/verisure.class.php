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
			
		if ( $this->getConfiguration('alarmtype') == 1 )   { 
			$replace['#numinstall#'] = $this->getConfiguration('numinstall');
			$replace['#username#'] = $this->getConfiguration('username');
			$replace['#password#'] = $this->getConfiguration('password');
			$replace['#country#'] = $this->getConfiguration('country');
		}
			
		if ( $this->getConfiguration('alarmtype') == 2 )   { 
			$replace['#nb_smartplug#'] = $this->getConfiguration('nb_smartplug');
			$replace['#nb_climate#'] = $this->getConfiguration('nb_climate');
			$replace['#nb_doorsensor#'] = $this->getConfiguration('nb_doorsensor');
			$replace['#nb_camera#'] = $this->getConfiguration('nb_camera');
			$replace['#nb_device#'] = $this->getConfiguration('nb_device');
			$replace['#username#'] = $this->getConfiguration('username');
			$replace['#password#'] = $this->getConfiguration('password');
			$replace['#code#'] = $this->getConfiguration('code');
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
				if ($device_array['smartplugType'.$j] == "YR" || $device_array['smartplugType'.$j] == "XR" || $device_array['smartplugType'.$j] == "XP")  {
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
					if (isset($listValue))  { $listValue = $listValue .';'. $device_array['smartplugID'.$j].'|'.$device_array['smartplugName'.$j];  }
					else  { $listValue = $device_array['smartplugID'.$j].'|'.$device_array['smartplugName'.$j];  }
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
					$i++;					
				}
			}	
		}
		
		$this->save(true);		//paramètre "true" -> ne lance pas le postsave()
	}
	 

    /*     * **********************Getteur Setteur*************************** */

	public function SynchronizeMyInstallation($alarmtype,$numinstall,$username,$password,$code,$country)	{		//Type 1 & 2
		
		if ( $alarmtype == 1 )   {
			log::add('verisure', 'debug', '┌───────── Démarrage de la synchronisation ─────────');
			log::add('verisure', 'debug', '│ Alarme type '.$alarmtype);
			$MyAlarm = new verisureAPI($numinstall,$username,$password,$country);
			$result_login = $MyAlarm->Login();
			log::add('verisure', 'debug', '│ Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2].' - 3 => '.$result_login[3].' - 4 => '.$result_login[4]);
			$result_myinst = $MyAlarm->MyInstallation();
			log::add('verisure', 'debug', '│ Request MYINSTALLATION - 0 => '.$result_myinst[0].' - 1 => '.$result_myinst[1].' - 2 => '.$result_myinst[2].' - 3 => '.$result_myinst[3].' - 4 => '.json_encode($result_myinst[4]));
			$result_logout = $MyAlarm->Logout();
			log::add('verisure', 'debug', '│ Request CLS - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1].' - 2 => '.$result_logout[2].' - 3 => '.$result_logout[3]);
					
			if ( $result_myinst[0] == 200 && $result_myinst[1] == "OK")  {
				$result = $result_myinst[4];
				log::add('verisure', 'debug', '└───────── Synchronisation terminée avec succès ─────────');
			}
			else  {
				$result = null;
				log::add('verisure', 'debug', '│ /!\ Erreur de connexion au cloud Verisure');
				log::add('verisure', 'debug', '└───────── Erreur de synchronisation !! ─────────');
			}
			return $result;
		}
		
		if ( $alarmtype == 2 )   {
			log::add('verisure', 'debug', '┌───────── Démarrage de la synchronisation ─────────');
			log::add('verisure', 'debug', '│ Alarme type '.$alarmtype);
			$MyAlarm = new verisureAPI2($username,$password,$code);
			$result_login = $MyAlarm->Login();
			log::add('verisure', 'debug', '│ Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2]);
			$result_giid = $MyAlarm->getGiid();
			log::add('verisure', 'debug', '│ Request GIID - 0 => '.$result_giid[0].' - 1 => '.$result_giid[1]);
			$result_myinst = $MyAlarm->MyInstallation();
			log::add('verisure', 'debug', '│ Request MYINSTALLATION - 0 => '.$result_myinst[0].' - 1 => '.$result_myinst[1]);
			$result_logout = $MyAlarm->Logout();
			log::add('verisure', 'debug', '│ Request LOGOUT - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1]);
			
			if ( $result_myinst[0] == 200)  {
				$result = $result_myinst[2];
				log::add('verisure', 'debug', '└───────── Synchronisation terminée avec succès ─────────');
			}
			else  {
				$result = null;
				log::add('verisure', 'debug', '│ /!\ Erreur de connexion au cloud Verisure');
				log::add('verisure', 'debug', '└───────── Erreur de synchronisation !! ─────────');
			}
			return $result;
		}
	}
		
	public function GetStateAlarm()	{	//Type 1 & 2
		
		if ( $this->getConfiguration('alarmtype') == 1 )   { 
			log::add('verisure', 'debug', '┌───────── Demande de statut ─────────');
			log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
			$result_login = $MyAlarm->Login();
			log::add('verisure', 'debug', '│ Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2].' - 3 => '.$result_login[3].' - 4 => '.$result_login[4]);
			$result_getstate = $MyAlarm->GetState();
			log::add('verisure', 'debug', '│ Request EST1 - 0 => '.$result_getstate[0].' - 1 => '.$result_getstate[1].' - 2 => '.$result_getstate[2].' - 3 => '.$result_getstate[3]);
			log::add('verisure', 'debug', '│ Request EST2 - 0 => '.$result_getstate[4].' - 1 => '.$result_getstate[5].' - 2 => '.$result_getstate[6].' - 3 => '.$result_getstate[7].' - 4 => '.$result_getstate[8]);
			$result_logout = $MyAlarm->Logout();
			log::add('verisure', 'debug', '│ Request CLS - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1].' - 2 => '.$result_logout[2].' - 3 => '.$result_logout[3]);
									
			if ( $result_getstate[4] == 200 )  {
				if ( $result_getstate[5] == "OK")  {
					$result = $result_getstate[7];
					log::add('verisure', 'debug', '└───────── Mise à jour statut OK ─────────');
				}
				else  {
					$result = "Erreur de commande Verisure";	
				}
			}	
			else  {
				$result = "Erreur de connexion au cloud Verisure";
			}
			return $result;
		}
		
		if ( $this->getConfiguration('alarmtype') == 2 )   { 
			log::add('verisure', 'debug', '┌───────── Demande de statut ─────────');
			log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI2($this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('code'));
			$result_login = $MyAlarm->Login();
			log::add('verisure', 'debug', '│ Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2]);
			$result_giid = $MyAlarm->getGiid();
			log::add('verisure', 'debug', '│ Request GIID - 0 => '.$result_giid[0].' - 1 => '.$result_giid[1]);
			$result_getstatealarm = $MyAlarm->getStateAlarm();
			log::add('verisure', 'debug', '│ Request GETSTATEALARM - 0 => '.$result_getstatealarm[0].' - 1 => '.$result_getstatealarm[1]);
			$result_getstatedevices = $MyAlarm->getStateDevices();
			log::add('verisure', 'debug', '│ Request GETSTATEDEVICES - 0 => '.$result_getstatedevices[0].' - 1 => '.$result_getstatedevices[1]);
			if ( $result_getstatedevices[0] == 200 )  {
				$filename = __PLGBASE__.'/data/'.'stateDevices.json';
				if (file_put_contents($filename, $result_getstatedevices[1], LOCK_EX)) {
					log::add('verisure', 'debug', '│ Fichier JSON enregistré avec succès dans '. $filename);
				}
				else {
					log::add('verisure', 'debug', '│ Fichier JSON non enregistré !');
				}		
			}
			else  {
				log::add('verisure', 'debug', '│ Fichier JSON pas mis à jour ! !');
			}
			$result_logout = $MyAlarm->Logout();
			log::add('verisure', 'debug', '│ Request LOGOUT - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1]);
			
			if ( $result_getstatealarm[0] == 200 )  {
				$result = $result_getstatealarm[1];
				$this->SetClimateDeviceTemp();
				log::add('verisure', 'debug', '└───────── Mise à jour statut OK ─────────');
			}
			else  {
				$result = "Erreur de connexion au cloud Verisure";
			}
			return $result;
		}
	}
	
	public function ArmTotalAlarm()	{	//Type 1 & 2
		
		if ( $this->getConfiguration('alarmtype') == 1 )   { 
			log::add('verisure', 'debug', '┌───────── Demande activation mode total ─────────');
			log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
			$result_login = $MyAlarm->Login();
			log::add('verisure', 'debug', '│ Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2].' - 3 => '.$result_login[3].' - 4 => '.$result_login[4]);
			$result_armtotal = $MyAlarm->ArmTotal();
			log::add('verisure', 'debug', '│ Request ARM1 - 0 => '.$result_armtotal[0].' - 1 => '.$result_armtotal[1].' - 2 => '.$result_armtotal[2].' - 3 => '.$result_armtotal[3]);
			log::add('verisure', 'debug', '│ Request ARM2 - 0 => '.$result_armtotal[4].' - 1 => '.$result_armtotal[5].' - 2 => '.$result_armtotal[6].' - 3 => '.$result_armtotal[7].' - 4 => '.$result_armtotal[8]);
			$result_logout = $MyAlarm->Logout();
			log::add('verisure', 'debug', '│ Request CLS - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1].' - 2 => '.$result_logout[2].' - 3 => '.$result_logout[3]);
					
			if ( $result_armtotal[4] == 200 )  {
				if ( $result_armtotal[5] == "OK")  {
					$result = $result_armtotal[7];
					log::add('verisure', 'debug', '└───────── Activation mode total OK ─────────');
				}
				else  {
					$result = "Erreur de commande Verisure";
					throw new Exception($result_armtotal[6]);					
				}
			}	
			else  {
				$result = "Erreur de connexion au cloud Verisure";
			}
			return $result;
		}
		
		if ( $this->getConfiguration('alarmtype') == 2 )   {
			log::add('verisure', 'debug', '┌───────── Demande activation mode total ─────────');
			log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI2($this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('code'));
			$result_login = $MyAlarm->Login();
			log::add('verisure', 'debug', '│ Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2]);
			$result_giid = $MyAlarm->getGiid();
			log::add('verisure', 'debug', '│ Request GIID - 0 => '.$result_giid[0].' - 1 => '.$result_giid[1]);
			$result_setstate = $MyAlarm->setStateAlarm('ARMED_AWAY');
			log::add('verisure', 'debug', '│ Request SETSTATEALARM : ARMED_AWAY - 0 => '.$result_setstate[0].' - 1 => '.$result_setstate[1].' - 2 => '.$result_setstate[2].' - 3 => '.$result_setstate[3]);
			$result_logout = $MyAlarm->Logout();
			log::add('verisure', 'debug', '│ Request LOGOUT - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1]);
			
			if ( $result_setstate[0] == 200 && $result_setstate[3] == "OK" )  {
				$result = 'ARMED_AWAY';
				log::add('verisure', 'debug', '└───────── Activation mode total OK ─────────');
			}
			else  {
				$result = "Erreur de connexion au cloud Verisure";
			}
			return $result;
		}
	}
		
	public function ArmNightAlarm()	{	//Type 1
		
		log::add('verisure', 'debug', '┌───────── Demande activation mode nuit ─────────');
		log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_login = $MyAlarm->Login();
      	log::add('verisure', 'debug', '│ Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2].' - 3 => '.$result_login[3].' - 4 => '.$result_login[4]);
			$result_armnight = $MyAlarm->ArmNight();
		log::add('verisure', 'debug', '│ Request ARMNIGHT1 - 0 => '.$result_armnight[0].' - 1 => '.$result_armnight[1].' - 2 => '.$result_armnight[2].' - 3 => '.$result_armnight[3]);
		log::add('verisure', 'debug', '│ Request ARMNIGHT2 - 0 => '.$result_armnight[4].' - 1 => '.$result_armnight[5].' - 2 => '.$result_armnight[6].' - 3 => '.$result_armnight[7].' - 4 => '.$result_armnight[8]);
		$result_logout = $MyAlarm->Logout();
		log::add('verisure', 'debug', '│ Request CLS - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1].' - 2 => '.$result_logout[2].' - 3 => '.$result_logout[3]);
					
		if ( $result_armnight[4] == 200 )  {
			if ( $result_armnight[5] == "OK")  {
				$result = $result_armnight[7];
				log::add('verisure', 'debug', '└───────── Activation mode nuit OK ─────────');
			}
			else  {
				$result = "Erreur de commande Verisure";
				throw new Exception($result_armnight[6]);
			}
		}
		else  {
			$result = "Erreur de connexion au cloud Verisure";
		}
		return $result;
	}
	
	public function ArmDayAlarm()	{	//Type 1
		
		log::add('verisure', 'debug', '┌───────── Demande activation mode jour ─────────');
		log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_login = $MyAlarm->Login();
      	log::add('verisure', 'debug', '│ Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2].' - 3 => '.$result_login[3].' - 4 => '.$result_login[4]);
		$result_armday = $MyAlarm->ArmDay();
		log::add('verisure', 'debug', '│ Request ARMDAY1 - 0 => '.$result_armday[0].' - 1 => '.$result_armday[1].' - 2 => '.$result_armday[2].' - 3 => '.$result_armday[3]);
		log::add('verisure', 'debug', '│ Request ARMDAY2 - 0 => '.$result_armday[4].' - 1 => '.$result_armday[5].' - 2 => '.$result_armday[6].' - 3 => '.$result_armday[7].' - 4 => '.$result_armday[8]);
		$result_logout = $MyAlarm->Logout();
		log::add('verisure', 'debug', '│ Request CLS - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1].' - 2 => '.$result_logout[2].' - 3 => '.$result_logout[3]);
					
		if ( $result_armday[4] == 200)  {
			if ( $result_armday[5] == "OK")  {
				$result = $result_armday[7];
				log::add('verisure', 'debug', '└───────── Activation mode jour OK ─────────');
			}
			else  {
				$result = "Erreur de commande Verisure";
				throw new Exception($result_armday[6]);				
			}
		}
		else  {
			$result = "Erreur de connexion au cloud Verisure";
		}
		return $result;
	}
	
	public function ArmExtAlarm()	{	//Type 1
		
		log::add('verisure', 'debug', '┌───────── Demande activation mode extérieur ─────────');
		log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_login = $MyAlarm->Login();
      	log::add('verisure', 'debug', '│ Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2].' - 3 => '.$result_login[3].' - 4 => '.$result_login[4]);
		$result_armext = $MyAlarm->ArmExt();
		log::add('verisure', 'debug', '│ Request PERI1 - 0 => '.$result_armext[0].' - 1 => '.$result_armext[1].' - 2 => '.$result_armext[2].' - 3 => '.$result_armext[3]);
		log::add('verisure', 'debug', '│ Request PERI2 - 0 => '.$result_armext[4].' - 1 => '.$result_armext[5].' - 2 => '.$result_armext[6].' - 3 => '.$result_armext[7].' - 4 => '.$result_armext[8]);
		$result_logout = $MyAlarm->Logout();
		log::add('verisure', 'debug', '│ Request CLS - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1].' - 2 => '.$result_logout[2].' - 3 => '.$result_logout[3]);
					
		if ( $result_armext[4] == 200)  {
			if ( $result_armext[5] == "OK")  {
				$result = $result_armext[7];
				log::add('verisure', 'debug', '└───────── Activation mode extérieur OK ─────────');
			}
			else  {
				$result = "Erreur de commande Verisure";
				throw new Exception($result_armext[6]);				
			}
		}
		else  {
			$result = "Erreur de connexion au cloud Verisure";
		}
		return $result;
	}
	
	public function ArmHomeAlarm()	{	//Type 2
		
		log::add('verisure', 'debug', '┌───────── Demande activation mode home ─────────');
		log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
		$MyAlarm = new verisureAPI2($this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('code'));
		$result_login = $MyAlarm->Login();
		log::add('verisure', 'debug', '│ Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2]);
		$result_giid = $MyAlarm->getGiid();
		log::add('verisure', 'debug', '│ Request GIID - 0 => '.$result_giid[0].' - 1 => '.$result_giid[1]);
		$result_setstate = $MyAlarm->setStateAlarm('ARMED_HOME');
		log::add('verisure', 'debug', '│ Request SETSTATEALARM : ARMED_HOME - 0 => '.$result_setstate[0].' - 1 => '.$result_setstate[1].' - 2 => '.$result_setstate[2].' - 3 => '.$result_setstate[3]);
		$result_logout = $MyAlarm->Logout();
		log::add('verisure', 'debug', '│ Request LOGOUT - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1]);
		
		if ( $result_setstate[0] == 200 && $result_setstate[3] == "OK" )  {
			$result = 'ARMED_HOME';
			log::add('verisure', 'debug', '└───────── Activation mode home OK ─────────');
		}
		else  {
			$result = "Erreur de connexion au cloud Verisure";
		}
		return $result;
	}
	
	public function DisarmAlarm()	{	//Type 1 & 2
		
		if ( $this->getConfiguration('alarmtype') == 1 )   { 
			log::add('verisure', 'debug', '┌───────── Demande désactivation ─────────');
			log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
			$result_login = $MyAlarm->Login();
			log::add('verisure', 'debug', '│ Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2].' - 3 => '.$result_login[3].' - 4 => '.$result_login[4]);
			$result_disarm = $MyAlarm->Disarm();
			log::add('verisure', 'debug', '│ Request DARM1 - 0 => '.$result_disarm[0].' - 1 => '.$result_disarm[1].' - 2 => '.$result_disarm[2].' - 3 => '.$result_disarm[3]);
			log::add('verisure', 'debug', '│ Request DARM2 - 0 => '.$result_disarm[4].' - 1 => '.$result_disarm[5].' - 2 => '.$result_disarm[6].' - 3 => '.$result_disarm[7].' - 4 => '.$result_disarm[8]);
			$result_logout = $MyAlarm->Logout();
			log::add('verisure', 'debug', '│ Request CLS - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1].' - 2 => '.$result_logout[2].' - 3 => '.$result_logout[3]);
					
			if ( $result_disarm[4] == 200)  {
				if ( $result_disarm[5] == "OK")  {
					$result = $result_disarm[7];
					log::add('verisure', 'debug', '└───────── Désactivation OK ─────────');
				}
				else  {
					$result = "Erreur de commande Verisure";	
				}
			}	
			else  {
				$result = "Erreur de connexion au cloud Verisure";
			}
			return $result;
		}
		
		if ( $this->getConfiguration('alarmtype') == 2 )   { 
			log::add('verisure', 'debug', '┌───────── Demande désactivation ─────────');
			log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
			$MyAlarm = new verisureAPI2($this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('code'));
			$result_login = $MyAlarm->Login();
			log::add('verisure', 'debug', '│ Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2]);
			$result_giid = $MyAlarm->getGiid();
			log::add('verisure', 'debug', '│ Request GIID - 0 => '.$result_giid[0].' - 1 => '.$result_giid[1]);
			$result_setstate = $MyAlarm->setStateAlarm('DISARMED');
			log::add('verisure', 'debug', '│ Request SETSTATEALARM : DISARMED - 0 => '.$result_setstate[0].' - 1 => '.$result_setstate[1].' - 2 => '.$result_setstate[2].' - 3 => '.$result_setstate[3]);
			$result_logout = $MyAlarm->Logout();
			log::add('verisure', 'debug', '│ Request LOGOUT - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1]);
			
			if ( $result_setstate[0] == 200 && $result_setstate[3] == "OK" )  {
				$result = 'DISARMED';
				log::add('verisure', 'debug', '└───────── Désactivation OK ─────────');
			}
			else  {
				$result = "Erreur de connexion au cloud Verisure";
			}
			return $result;
		}
	}
	
	public function GetReportAlarm($alarmtype,$numinstall,$username,$password,$code,$country)	{		//Type 1 & 2
		
		if ( $alarmtype == 1 )   {
			$eqLogic = self::SetEqLogic($numinstall);
			log::add('verisure', 'debug', '┌───────── Demande du journal d\'activité ─────────');
			log::add('verisure', 'debug', '│ Alarme type = '.$alarmtype);
			$MyAlarm = new verisureAPI($numinstall,$username,$password,$country);
			$result_login = $MyAlarm->Login();
			log::add('verisure', 'debug', '│ Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2].' - 3 => '.$result_login[3].' - 4 => '.$result_login[4]);
			$result_getreport = $MyAlarm->GetReport();
			log::add('verisure', 'debug', '│ Request ACT_V2 - 0 => '.$result_getreport[0].' - 1 => '.$result_getreport[1].' - 2 => '.$result_getreport[2].' - 3 => '.json_encode($result_getreport[3]));
			$result_logout = $MyAlarm->Logout();
			log::add('verisure', 'debug', '│ Request CLS - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1].' - 2 => '.$result_logout[2].' - 3 => '.$result_logout[3]);
			
			if ( $result_getreport[0] == 200)  {
				if ( $result_getreport[1] == "OK")  {
					$result = $result_getreport[3];
					log::add('verisure', 'debug', '└───────── Journal d\'activité OK ─────────');
					$eqLogic->checkAndUpdateCmd('networkstate', $eqLogic->SetNetworkState(1));
				}
				else  {
					//throw new Exception("Erreur de commande Verisure");
					$result = null;
					log::add('verisure', 'debug', '│ /!\ Erreur de commande Verisure GetReport()');
					log::add('verisure', 'debug', '└───────── Journal d\'activité NOK ─────────');
					$eqLogic->checkAndUpdateCmd('networkstate', $eqLogic->SetNetworkState(0));
				}
			}	
			else  {
				//throw new Exception("Erreur de connexion au cloud Verisure");
				$result = null;
				log::add('verisure', 'debug', '│ /!\ Erreur de connexion au cloud Verisure');
				log::add('verisure', 'debug', '└───────── Journal d\'activité NOK ─────────');
				$eqLogic->checkAndUpdateCmd('networkstate', $eqLogic->SetNetworkState(0));
			}
			$eqLogic->refreshWidget();
			return $result;
		}
		
		if ( $alarmtype == 2 )   {
			log::add('verisure', 'debug', '┌───────── Demande du journal d\'activité ─────────');
			log::add('verisure', 'debug', '│ Alarme type = '.$alarmtype);
			$MyAlarm = new verisureAPI2($username,$password,$code);
			$result_login = $MyAlarm->Login();
			log::add('verisure', 'debug', '│ Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2]);
			$result_giid = $MyAlarm->getGiid();
			log::add('verisure', 'debug', '│ Request GIID - 0 => '.$result_giid[0].' - 1 => '.$result_giid[1]);
			$result_getreport = $MyAlarm->getReport();
			log::add('verisure', 'debug', '│ Request GETREPORT - 0 => '.$result_getreport[0].' - 1 => '.$result_getreport[1]);
			$result_logout = $MyAlarm->Logout();
			log::add('verisure', 'debug', '│ Request LOGOUT - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1]);
			
			if ( $result_getreport[0] == 200 )  {
				$result = $result_getreport[2];
				log::add('verisure', 'debug', '└───────── Journal d\'activité OK ─────────');
			}
			else  {
				$result = null;
				log::add('verisure', 'debug', '│ /!\ Erreur de connexion au cloud Verisure');
				log::add('verisure', 'debug', '└───────── Journal d\'activité NOK ─────────');
			}
			return $result;
		}
	}

	public function GetPhotosRequest($numinstall,$username,$password,$country,$device)	{	//Type 1 pour le moment
		
		$eqLogic = self::SetEqLogic($numinstall);
		log::add('verisure', 'debug', '┌───────── Demande de photos ─────────');
		$MyAlarm = new verisureAPI($numinstall,$username,$password,$country);
		$result_login = $MyAlarm->Login();
      	log::add('verisure', 'debug', '│ Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2].' - 3 => '.$result_login[3].' - 4 => '.$result_login[4]);
		$result_getimg = $MyAlarm->PhotosRequest($device);
		log::add('verisure', 'debug', '│ Request SRV - 0 => '.$result_getimg[0].' - 1 => '.$result_getimg[1].' - 2 => '.$result_getimg[2].' - 3 => '.$result_getimg[3].' - 4 => '.$result_getimg[4]);
		log::add('verisure', 'debug', '│ Request IMG1 - 0 => '.$result_getimg[5].' - 1 => '.$result_getimg[6].' - 2 => '.$result_getimg[7].' - 3 => '.$result_getimg[8]);
		log::add('verisure', 'debug', '│ Request IMG2 - 0 => '.$result_getimg[9].' - 1 => '.$result_getimg[10].' - 2 => '.$result_getimg[11].' - 3 => '.$result_getimg[12].' - 4 => '.$result_getimg[13]);
		log::add('verisure', 'debug', '│ Request ACT_V2 - 0 => '.$result_getimg[14].' - 1 => '.$result_getimg[15].' - 2 => '.$result_getimg[16].' - 3 => '.$result_getimg[17]);
		log::add('verisure', 'debug', '│ Request INF - 0 => '.$result_getimg[18].' - 1 => '.$result_getimg[19].' - 2 => '.$result_getimg[20]);
		$result_logout = $MyAlarm->Logout();
		log::add('verisure', 'debug', '│ Request CLS - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1].' - 2 => '.$result_logout[2].' - 3 => '.$result_logout[3]);
		
		if ( $result_getimg[18] == 200)  {
			if ( $result_getimg[19] == "OK")  {
				$result = $result_getimg[21];
				log::add('verisure', 'debug', '└───────── Demande de photos OK ─────────');
				$eqLogic->checkAndUpdateCmd('networkstate', $eqLogic->SetNetworkState(1));
			}
			else  {
				//throw new Exception("Erreur de commande Verisure");
				$result = null;
				log::add('verisure', 'debug', '│ /!\ Erreur de commande Verisure GetPhotosRequest()');
				log::add('verisure', 'debug', '└───────── Demande de photos NOK ─────────');
				$eqLogic->checkAndUpdateCmd('networkstate', $eqLogic->SetNetworkState(0));				
			}
		}	
		else  {
			//throw new Exception("Erreur de connexion au cloud Verisure");
			$result = null;
			log::add('verisure', 'debug', '│ /!\ Erreur de connexion au cloud Verisure');
			log::add('verisure', 'debug', '└───────── Demande de photos NOK ─────────');
			$eqLogic->checkAndUpdateCmd('networkstate', $eqLogic->SetNetworkState(0));
		}
		$eqLogic->refreshWidget();
		return $result;
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
		return $eqLogic;
		} 
	}
	
	public function SetSmartplugState($device_label, $state)	{	//Type 2
		
		log::add('verisure', 'debug', '┌───────── Demande set Smartplug ─────────');
		log::add('verisure', 'debug', '│ Equipement '.$this->getHumanName().' - Alarme type '.$this->getConfiguration('alarmtype'));
		$MyAlarm = new verisureAPI2($this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('code'));
		$result_login = $MyAlarm->Login();
		log::add('verisure', 'debug', '│ Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2]);
		$result_giid = $MyAlarm->getGiid();
		log::add('verisure', 'debug', '│ Request GIID - 0 => '.$result_giid[0].' - 1 => '.$result_giid[1]);
		$result_setstate = $MyAlarm->setStateSmartplug($device_label, $state);
		log::add('verisure', 'debug', '│ Request SETSTATESMARTPLUG - 0 => '.$result_setstate[0].' - 1 => '.$result_setstate[1].' - 2 => '.$result_setstate[2]);
		$result_logout = $MyAlarm->Logout();
		log::add('verisure', 'debug', '│ Request LOGOUT - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1]);
		
		if ( $result_setstate[0] == 200 )  {
			$result = 'OK';
			log::add('verisure', 'debug', '└───────── Demande set Smartplug OK ─────────');
		}
		else  {
			$result = "Erreur de connexion au cloud Verisure";
		}
		return $result;
	}
	
	public function SetClimateDeviceTemp()	{	//Type 2
		
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
			$device_label = $climateDevice['deviceLabel'];
			$temp = $climateDevice['temperature'];
			$this->checkAndUpdateCmd($device_label.'::Temp', $temp);
			log::add('verisure', 'debug',  '│ Mise à jour température '.$device_label.' : '.$temp);
		}
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
						case '0':
							$eqlogic->checkAndUpdateCmd('state', "0");			// On met à jour la commande avec le LogicalId 'state' de l'eqlogic
							$eqlogic->checkAndUpdateCmd('enable', "0");
							$eqlogic->checkAndUpdateCmd('mode', "Désactivée");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
							break;
						case 'A':
						case '1':
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
						case '3':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Extérieur");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
							break;
						case '4':
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
						case 'Erreur de connexion au cloud Verisure':
							//throw new Exception($state);
							log::add('verisure', 'debug', '│ /!\ Erreur de connexion au cloud Verisure');
							log::add('verisure', 'debug', '└───────── Mise à jour statut NOK ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(0));
							break;
						case 'Erreur de commande Verisure':
							//throw new Exception($state);
							log::add('verisure', 'debug', '│ /!\ Erreur de commande Verisure GetStateAlarm()');
							log::add('verisure', 'debug', '└───────── Mise à jour statut NOK ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(0));
							break;
					}
					break;
					
				case 'armed':
					$state = $eqlogic->ArmTotalAlarm();
					switch ($state)  {
						case 'A':
						case '1':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Total");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
							break;
						case '4':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Total + Ext");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
							break;
						case 'Erreur de connexion au cloud Verisure':
							log::add('verisure', 'debug', '│ /!\ Erreur de connexion au cloud Verisure');
							log::add('verisure', 'debug', '└───────── Activation mode total NOK ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(0));
							break;
						case 'Erreur de commande Verisure':
							log::add('verisure', 'debug', '│ /!\ Erreur de commande Verisure ArmTotalAlarm()');
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
						case 'Erreur de connexion au cloud Verisure':
							log::add('verisure', 'debug', '│ /!\ Erreur de connexion au cloud Verisure');
							log::add('verisure', 'debug', '└───────── Activation mode nuit NOK ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(0));
							break;
						case 'Erreur de commande Verisure':
							log::add('verisure', 'debug', '│ /!\ Erreur de commande Verisure ArmNightAlarm()');
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
						case 'Erreur de connexion au cloud Verisure':
							log::add('verisure', 'debug', '│ /!\ Erreur de connexion au cloud Verisure');
							log::add('verisure', 'debug', '└───────── Activation mode jour NOK ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(0));
							break;
						case 'Erreur de commande Verisure':
							log::add('verisure', 'debug', '│ /!\ Erreur de commande Verisure ArmDayAlarm()');
							log::add('verisure', 'debug', '└───────── Activation mode jour NOK ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(0));
							break;
					}
					break;
					
				case 'armed_ext':
					$state = $eqlogic->ArmExtAlarm();
					switch ($state)  {
						case '3':
							$eqlogic->checkAndUpdateCmd('enable', "1");
							$eqlogic->checkAndUpdateCmd('mode', "Extérieur");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
							break;
						case '4':
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
						case 'Erreur de connexion au cloud Verisure':
							log::add('verisure', 'debug', '│ /!\ Erreur de connexion au cloud Verisure');
							log::add('verisure', 'debug', '└───────── Activation mode extérieur NOK ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(0));
							break;
						case 'Erreur de commande Verisure':
							log::add('verisure', 'debug', '│ /!\ Erreur de commande Verisure ArmExtAlarm()');
							log::add('verisure', 'debug', '└───────── Activation mode extérieur NOK ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(0));
							break;
					}
					break;	
				
				case 'released':
					$state = $eqlogic->DisarmAlarm();
					switch ($state)  {
						case '0':
							$eqlogic->checkAndUpdateCmd('state', "0");	
							$eqlogic->checkAndUpdateCmd('enable', "0");	
							$eqlogic->checkAndUpdateCmd('mode', "Désactivée");
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(1));
							break;
						case 'Erreur de connexion au cloud Verisure':
							log::add('verisure', 'debug', '│ /!\ Erreur de connexion au cloud Verisure');
							log::add('verisure', 'debug', '└───────── Désactivation NOK ─────────');
							$eqlogic->checkAndUpdateCmd('networkstate', $eqlogic->SetNetworkState(0));
							break;
						case 'Erreur de commande Verisure':
							log::add('verisure', 'debug', '│ /!\ Erreur de commande Verisure DisarmAlarm())');
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
							case 'Erreur de connexion au cloud Verisure':
								log::add('verisure', 'debug', '│ /!\ Erreur de connexion au cloud Verisure');
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
							case 'Erreur de connexion au cloud Verisure':
								log::add('verisure', 'debug', '│ /!\ Erreur de connexion au cloud Verisure');
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
					case 'Erreur de connexion au cloud Verisure':
						log::add('verisure', 'debug', '│ /!\ Erreur de connexion au cloud Verisure');
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
					case 'Erreur de connexion au cloud Verisure':
						log::add('verisure', 'debug', '│ /!\ Erreur de connexion au cloud Verisure');
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
					case 'Erreur de connexion au cloud Verisure':
						log::add('verisure', 'debug', '│ /!\ Erreur de connexion au cloud Verisure');
						log::add('verisure', 'debug', '└───────── Désactivation NOK ─────────');
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
					case 'Erreur de connexion au cloud Verisure':
						log::add('verisure', 'debug', '│ /!\ Erreur de connexion au cloud Verisure');
						log::add('verisure', 'debug', '└───────── Désactivation NOK ─────────');
						break;
				}	
			}
		}
		
		$eqlogic->refreshWidget();	
	}
	

    /*     * **********************Getteur Setteur*************************** */
}


?>