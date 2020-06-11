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

if (!class_exists('verisureAPI')) {
	require_once __DIR__ . '/../../3rdparty/verisureAPI.class.php';
}

class verisure extends eqLogic {
	
    /*     * *************************Attributs****************************** */



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
        
		$replace['#numinstall#'] = $this->getConfiguration('numinstall');
		$replace['#username#'] = $this->getConfiguration('username');
		$replace['#password#'] = $this->getConfiguration('password');
		$replace['#country#'] = $this->getConfiguration('country');
		
		$this->emptyCacheWidget(); 		//vide le cache. Pratique pour le développement

        // Traitement des commandes infos
        foreach ($this->getCmd('info') as $cmd) {
			$replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
			$replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd();
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
            $template = 'verisure_dashboard_v4';
        }
		else {
            $template = 'verisure_dashboard_v3';
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
		$this->setConfiguration('SetModeAbsent',$cmdArmed->getId()."|"."Mode Total");		//Compatibilité Homebridge  - Mode Absent / A distance
				
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
				
		$cmdArmedNight = $this->getCmd(null, 'armed_night');
		if (!is_object($cmdArmedNight)) {
			$cmdArmedNight = new verisureCmd();
			$cmdArmedNight->setOrder(6);
			$cmdArmedNight->setName('Mode Nuit');
			$cmdArmedNight->setEqLogic_id($this->getId());
			$cmdArmedNight->setLogicalId('armed_night');
			$cmdArmedNight->setType('action');
			$cmdArmedNight->setSubType('other');
			$cmdArmedNight->setDisplay('generic_type', 'ALARM_SET_MODE');
			$cmdArmedNight->save();
			log::add('verisure', 'debug', 'Création de la commande '.$cmdArmedNight->getName().' (LogicalId : '.$cmdArmedNight->getLogicalId().')');
		}
		$this->setConfiguration('SetModeNuit',$cmdArmedNight->getId()."|"."Mode Nuit");			//Compatibilité Homebridge - Mode Nuit
		
		$cmdArmedDay = $this->getCmd(null, 'armed_day');
		if (!is_object($cmdArmedDay)) {
			$cmdArmedDay = new verisureCmd();
			$cmdArmedDay->setOrder(7);
			$cmdArmedDay->setName('Mode Jour');
			$cmdArmedDay->setEqLogic_id($this->getId());
			$cmdArmedDay->setLogicalId('armed_day');
			$cmdArmedDay->setType('action');
			$cmdArmedDay->setSubType('other');
			$cmdArmedDay->setDisplay('generic_type', 'ALARM_SET_MODE');
			$cmdArmedDay->save();
			log::add('verisure', 'debug', 'Création de la commande '.$cmdArmedDay->getName().' (LogicalId : '.$cmdArmedDay->getLogicalId().')');
		}
		$this->setConfiguration('SetModePresent',$cmdArmedDay->getId()."|"."Mode Jour");		//Compatibilité Homebridge - Mode Présent / Domicile
		
		$cmdArmedExt = $this->getCmd(null, 'armed_ext');
		if (!is_object($cmdArmedExt)) {
			$cmdArmedExt = new verisureCmd();
			$cmdArmedExt->setOrder(8);
			$cmdArmedExt->setName('Mode Extérieur');
			$cmdArmedExt->setEqLogic_id($this->getId());
			$cmdArmedExt->setLogicalId('armed_ext');
			$cmdArmedExt->setType('action');
			$cmdArmedExt->setSubType('other');
			$cmdArmedExt->save();
			log::add('verisure', 'debug', 'Création de la commande '.$cmdArmedExt->getName().' (LogicalId : '.$cmdArmedExt->getLogicalId().')');
		}
				
		$cmdState = $this->getCmd(null, 'getstate');
		if ( ! is_object($cmdState)) {
			$cmdState = new verisureCmd();
			$cmdState->setOrder(9);
			$cmdState->setName('Rafraichir');
			$cmdState->setEqLogic_id($this->getId());
			$cmdState->setLogicalId('getstate');
			$cmdState->setType('action');
			$cmdState->setSubType('other');
			$cmdState->save();
			log::add('verisure', 'debug', 'Création de la commande '.$cmdState->getName().' (LogicalId : '.$cmdState->getLogicalId().')');
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
			if ($device_array['smartplugType'.$j] == "YR")  {
				if (isset($listValue))  { $listValue = $listValue .';'. $device_array['smartplugID'.$j].'|'.$device_array['smartplugName'.$j];  }
				else  { $listValue = $device_array['smartplugID'.$j].'|'.$device_array['smartplugName'.$j];  }
           	}
		}
		log::add('verisure', 'debug', 'Mise à jour liste smartplugs compatibles images : '.var_export($listValue, true));
		$cmdPictures->setConfiguration('listValue', $listValue);
		$cmdPictures->save();
						
		$this->save(true);		//paramètre "true" -> ne lance pas le postsave()
	}
	 

    /*     * **********************Getteur Setteur*************************** */

	public function SynchronizeMyInstallation($numinstall,$username,$password,$country)	{
		
		log::add('verisure', 'info', 'Démarrage de la synchronisation');
		$MyAlarm = new verisureAPI($numinstall,$username,$password,$country);
		$result_login = $MyAlarm->Login();
      	log::add('verisure', 'debug', 'Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2].' - 3 => '.$result_login[3]);
      	$result_myinst = $MyAlarm->MyInstallation();
		log::add('verisure', 'debug', 'Request MYINSTALLATION - 0 => '.$result_myinst[0].' - 1 => '.$result_myinst[1].' - 2 => '.$result_myinst[2].' - 3 => '.var_export($result_myinst[3], true));
		$result_logout = $MyAlarm->Logout();
		log::add('verisure', 'debug', 'Request CLS - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1].' - 2 => '.$result_logout[2]);
				
		if ( $result_myinst[0] == 200 && $result_myinst[1] == "OK")  {
			$result = $result_myinst[3];
			log::add('verisure', 'info', 'Synchronisation terminée avec succès !');
		}
		else  {
			throw new Exception($result_myinst[2]);
		}
		return $result;
	}
		
	public function GetStateAlarm()	{
		
		log::add('verisure', 'info', 'Demande de statut');
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_login = $MyAlarm->Login();
      	log::add('verisure', 'debug', 'Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2].' - 3 => '.$result_login[3]);
		$result_getstate = $MyAlarm->GetState();
		log::add('verisure', 'debug', 'Request EST1 - 0 => '.$result_getstate[0].' - 1 => '.$result_getstate[1].' - 2 => '.$result_getstate[2]);
		log::add('verisure', 'debug', 'Request EST2 - 0 => '.$result_getstate[3].' - 1 => '.$result_getstate[4].' - 2 => '.$result_getstate[5].' - 3 => '.$result_getstate[6]);
		$result_logout = $MyAlarm->Logout();
		log::add('verisure', 'debug', 'Request CLS - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1].' - 2 => '.$result_logout[2]);
								
		if ( $result_getstate[3] == 200)  {
			if ( $result_getstate[4] == "OK")  {
				$result = $result_getstate[6];
				log::add('verisure', 'info', 'Statut mis à jour');
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
	
	public function ArmTotalAlarm()	{
		
		log::add('verisure', 'info', 'Demande activation mode total');
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_login = $MyAlarm->Login();
      	log::add('verisure', 'debug', 'Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2].' - 3 => '.$result_login[3]);
		$result_armtotal = $MyAlarm->ArmTotal();
		log::add('verisure', 'debug', 'Request ARM1 - 0 => '.$result_armtotal[0].' - 1 => '.$result_armtotal[1].' - 2 => '.$result_armtotal[2]);
		log::add('verisure', 'debug', 'Request ARM2 - 0 => '.$result_armtotal[3].' - 1 => '.$result_armtotal[4].' - 2 => '.$result_armtotal[5].' - 3 => '.$result_armtotal[6]);
		$result_logout = $MyAlarm->Logout();
		log::add('verisure', 'debug', 'Request CLS - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1].' - 2 => '.$result_logout[2]);
				
		if ( $result_armtotal[3] == 200)  {
			if ( $result_armtotal[4] == "OK")  {
				$result = $result_armtotal[6];
				log::add('verisure', 'info', 'Activation mode total OK');
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
		
	public function ArmNightAlarm()	{
		
		log::add('verisure', 'info', 'Demande activation mode nuit');
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_login = $MyAlarm->Login();
      	log::add('verisure', 'debug', 'Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2].' - 3 => '.$result_login[3]);
		$result_armnight = $MyAlarm->ArmNight();
		log::add('verisure', 'debug', 'Request ARMNIGHT1 - 0 => '.$result_armnight[0].' - 1 => '.$result_armnight[1].' - 2 => '.$result_armnight[2]);
		log::add('verisure', 'debug', 'Request ARMNIGHT2 - 0 => '.$result_armnight[3].' - 1 => '.$result_armnight[4].' - 2 => '.$result_armnight[5].' - 3 => '.$result_armnight[6]);
		$result_logout = $MyAlarm->Logout();
		log::add('verisure', 'debug', 'Request CLS - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1].' - 2 => '.$result_logout[2]);
				
		if ( $result_armnight[3] == 200)  {
			if ( $result_armnight[4] == "OK")  {
				$result = $result_armnight[6];
				log::add('verisure', 'info', 'Activation mode nuit OK');
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
	
	public function ArmDayAlarm()	{
		
		log::add('verisure', 'info', 'Demande activation mode jour');
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_login = $MyAlarm->Login();
      	log::add('verisure', 'debug', 'Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2].' - 3 => '.$result_login[3]);
		$result_armday = $MyAlarm->ArmDay();
		log::add('verisure', 'debug', 'Request ARMDAY1 - 0 => '.$result_armday[0].' - 1 => '.$result_armday[1].' - 2 => '.$result_armday[2]);
		log::add('verisure', 'debug', 'Request ARMDAY2 - 0 => '.$result_armday[3].' - 1 => '.$result_armday[4].' - 2 => '.$result_armday[5].' - 3 => '.$result_armday[6]);
		$result_logout = $MyAlarm->Logout();
		log::add('verisure', 'debug', 'Request CLS - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1].' - 2 => '.$result_logout[2]);
				
		if ( $result_armday[3] == 200)  {
			if ( $result_armday[4] == "OK")  {
				$result = $result_armday[6];
				log::add('verisure', 'info', 'Activation mode jour OK');
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
	
	public function ArmExtAlarm()	{
		
		log::add('verisure', 'info', 'Demande activation mode extérieur');
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_login = $MyAlarm->Login();
      	log::add('verisure', 'debug', 'Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2].' - 3 => '.$result_login[3]);
		$result_armext = $MyAlarm->ArmExt();
		log::add('verisure', 'debug', 'Request PERI1 - 0 => '.$result_armext[0].' - 1 => '.$result_armext[1].' - 2 => '.$result_armext[2]);
		log::add('verisure', 'debug', 'Request PERI2 - 0 => '.$result_armext[3].' - 1 => '.$result_armext[4].' - 2 => '.$result_armext[5].' - 3 => '.$result_armext[6]);
		$result_logout = $MyAlarm->Logout();
		log::add('verisure', 'debug', 'Request CLS - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1].' - 2 => '.$result_logout[2]);
				
		if ( $result_armext[3] == 200)  {
			if ( $result_armext[4] == "OK")  {
				$result = $result_armext[6];
				log::add('verisure', 'info', 'Activation mode extérieur OK');
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
	
	public function DisarmAlarm()	{
		
		log::add('verisure', 'info', 'Demande désactivation');
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_login = $MyAlarm->Login();
      	log::add('verisure', 'debug', 'Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2].' - 3 => '.$result_login[3]);
		$result_disarm = $MyAlarm->Disarm();
		log::add('verisure', 'debug', 'Request DARM1 - 0 => '.$result_disarm[0].' - 1 => '.$result_disarm[1].' - 2 => '.$result_disarm[2]);
		log::add('verisure', 'debug', 'Request DARM2 - 0 => '.$result_disarm[3].' - 1 => '.$result_disarm[4].' - 2 => '.$result_disarm[5].' - 3 => '.$result_disarm[6]);
		$result_logout = $MyAlarm->Logout();
		log::add('verisure', 'debug', 'Request CLS - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1].' - 2 => '.$result_logout[2]);
				
		if ( $result_disarm[3] == 200)  {
			if ( $result_disarm[4] == "OK")  {
				$result = $result_disarm[6];
				log::add('verisure', 'info', 'Désactivation OK');
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
	
	public function GetReportAlarm($numinstall,$username,$password,$country)	{
		
		log::add('verisure', 'info', 'Demande du journal d\'activité');
		$MyAlarm = new verisureAPI($numinstall,$username,$password,$country);
		$result_login = $MyAlarm->Login();
      	log::add('verisure', 'debug', 'Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2].' - 3 => '.$result_login[3]);
		$result_getreport = $MyAlarm->GetReport();
		log::add('verisure', 'debug', 'Request ACT_V2 - 0 => '.$result_getreport[0].' - 1 => '.$result_getreport[1]);
		$result_logout = $MyAlarm->Logout();
		log::add('verisure', 'debug', 'Request CLS - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1].' - 2 => '.$result_logout[2]);
		
		if ( $result_getreport[0] == 200)  {
			if ( $result_getreport[1] == "OK")  {
				$result = $result_getreport[2];
				log::add('verisure', 'info', 'Journal d\'activité OK');
			}
			else  {
				//throw new Exception("Erreur de commande Verisure");
				log::add('verisure', 'error', 'Erreur de commande Verisure GetReport()');
			}
		}	
		else  {
			//throw new Exception("Erreur de connexion au cloud Verisure");
			log::add('verisure', 'error', 'Erreur de connexion au cloud Verisure');
		}
		return $result;
	}

	public function GetPhotosRequest($numinstall,$username,$password,$country,$device)	{
		
		log::add('verisure', 'info', 'Demande de photos');
		$MyAlarm = new verisureAPI($numinstall,$username,$password,$country);
		$result_login = $MyAlarm->Login();
      	log::add('verisure', 'debug', 'Request LOGIN - 0 => '.$result_login[0].' - 1 => '.$result_login[1].' - 2 => '.$result_login[2].' - 3 => '.$result_login[3]);
		$result_getimg = $MyAlarm->PhotosRequest($device);
		log::add('verisure', 'debug', 'Request SRV - 0 => '.$result_getimg[0].' - 1 => '.$result_getimg[1].' - 2 => '.$result_getimg[2].' - 3 => '.$result_getimg[3]);
		log::add('verisure', 'debug', 'Request IMG1 - 0 => '.$result_getimg[4].' - 1 => '.$result_getimg[5].' - 2 => '.$result_getimg[6]);
		log::add('verisure', 'debug', 'Request IMG2 - 0 => '.$result_getimg[7].' - 1 => '.$result_getimg[8].' - 2 => '.$result_getimg[9].' - 3 => '.$result_getimg[10]);
		log::add('verisure', 'debug', 'Request ACT_V2 - 0 => '.$result_getimg[11].' - 1 => '.$result_getimg[12].' - 2 => '.$result_getimg[13]);
		log::add('verisure', 'debug', 'Request INF - 0 => '.$result_getimg[14].' - 1 => '.$result_getimg[15]);
		$result_logout = $MyAlarm->Logout();
		log::add('verisure', 'debug', 'Request CLS    - 0 => '.$result_logout[0].' - 1 => '.$result_logout[1].' - 2 => '.$result_logout[2]);
		
		if ( $result_getimg[14] == 200)  {
			if ( $result_getimg[15] == "OK")  {
				$result = $result_getimg[16];
				log::add('verisure', 'info', 'Demande de photos OK');
			}
			else  {
				//throw new Exception("Erreur de commande Verisure");
				log::add('verisure', 'error', 'Erreur de commande Verisure GetPhotosRequest()');				
			}
		}	
		else  {
			//throw new Exception("Erreur de connexion au cloud Verisure");
			log::add('verisure', 'error', 'Erreur de connexion au cloud Verisure');
		}
		return $result;
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
		
		switch ($this->getLogicalId()) {									// On vérifie le logicalid de la commande 			
			case 'getstate': 												// LogicalId de la commande
				$state = $eqlogic->GetStateAlarm(); 						// On lance la fonction GetStatusAlarm() pour récupérer le statut de l'alarme et on le stocke dans la variable $state
				switch ($state)  {
					case '0':
						$eqlogic->checkAndUpdateCmd('state', "0");			// On met à jour la commande avec le LogicalId 'state' de l'eqlogic
						$eqlogic->checkAndUpdateCmd('enable', "0");
						$eqlogic->checkAndUpdateCmd('mode', "Désactivée");
						break;
					case 'A':
					case '1':
						$eqlogic->checkAndUpdateCmd('enable', "1");
						$eqlogic->checkAndUpdateCmd('mode', "Mode Total");
						break;
					case 'C':
					case 'Q':
						$eqlogic->checkAndUpdateCmd('enable', "1");
						$eqlogic->checkAndUpdateCmd('mode', "Mode Nuit");
						break;
					case 'P':
						$eqlogic->checkAndUpdateCmd('enable', "1");
						$eqlogic->checkAndUpdateCmd('mode', "Mode Jour");
						break;
					case '3':
						$eqlogic->checkAndUpdateCmd('enable', "1");
						$eqlogic->checkAndUpdateCmd('mode', "Mode Extérieur");
						break;		
					case 'Erreur de connexion au cloud Verisure':
						//throw new Exception($state);
						log::add('verisure', 'error', 'Erreur de connexion au cloud Verisure');
						break;
					case 'Erreur de commande Verisure':
						//throw new Exception($state);
						log::add('verisure', 'error', 'Erreur de commande Verisure GetStateAlarm()');
						break;
				}
				break;
				
			case 'armed':
				$state = $eqlogic->ArmTotalAlarm();
				switch ($state)  {
					case 'A':
					case '1':
						$eqlogic->checkAndUpdateCmd('enable', "1");
						$eqlogic->checkAndUpdateCmd('mode', "Mode Total");
						break;
					case 'Erreur de connexion au cloud Verisure':
						log::add('verisure', 'error', 'Erreur de connexion au cloud Verisure');
						break;
					case 'Erreur de commande Verisure':
						log::add('verisure', 'error', 'Erreur de commande Verisure ArmTotalAlarm()');
						break;
				}
				break;
				
			case 'armed_night':
				$state = $eqlogic->ArmNightAlarm();
				switch ($state)  {
					case 'C':
					case 'Q':
						$eqlogic->checkAndUpdateCmd('enable', "1");
						$eqlogic->checkAndUpdateCmd('mode', "Mode Nuit");
						break;
					case 'Erreur de connexion au cloud Verisure':
						log::add('verisure', 'error', 'Erreur de connexion au cloud Verisure');
						break;
					case 'Erreur de commande Verisure':
						log::add('verisure', 'error', 'Erreur de commande Verisure ArmNightAlarm()');
						break;
				}
				break;
			
			case 'armed_day':
				$state = $eqlogic->ArmDayAlarm();
				switch ($state)  {
					case 'P':
						$eqlogic->checkAndUpdateCmd('enable', "1");
						$eqlogic->checkAndUpdateCmd('mode', "Mode Jour");
						break;
					case 'Erreur de connexion au cloud Verisure':
						log::add('verisure', 'error', 'Erreur de connexion au cloud Verisure');
						break;
					case 'Erreur de commande Verisure':
						log::add('verisure', 'error', 'Erreur de commande Verisure ArmDayAlarm()');
						break;
				}
				break;
				
			case 'armed_ext':
				$state = $eqlogic->ArmExtAlarm();
				switch ($state)  {
					case '3':
						$eqlogic->checkAndUpdateCmd('enable', "1");
						$eqlogic->checkAndUpdateCmd('mode', "Mode Extérieur");
						break;
					case 'Erreur de connexion au cloud Verisure':
						log::add('verisure', 'error', 'Erreur de connexion au cloud Verisure');
						break;
					case 'Erreur de commande Verisure':
						log::add('verisure', 'error', 'Erreur de commande Verisure ArmExtAlarm()');
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
						break;
					case 'Erreur de connexion au cloud Verisure':
						log::add('verisure', 'error', 'Erreur de connexion au cloud Verisure');
						break;
					case 'Erreur de commande Verisure':
						log::add('verisure', 'error', 'Erreur de commande Verisure DisarmAlarm())');
						break;
				}	
				break;
		}
		$eqlogic->refreshWidget();
	}

    /*     * **********************Getteur Setteur*************************** */
}


?>