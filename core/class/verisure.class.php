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
		
		foreach (eqLogic::byType('verisure', true) as $verisure) {
			$cmdState = $verisure->getCmd(null, 'getstate');		
			if (!is_object($cmdState)) {						//Si la commande n'existe pas
			  	continue; 										//continue la boucle
			}
			if ($verisure->getConfiguration('nb_smartplug') != "") {
				$cmdState->execCmd(); 							// la commande existe on la lance
			}
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
    
    /* Non obligatoire mais permet de modifier l'affichage du widget si vous en avez besoin
      public function toHtml($_version = 'dashboard') {
    }*/
    
    /* Non obligatoire mais ca permet de déclencher une action après modification de variable de configuration
    public static function postConfig_<Variable>() {
    }*/

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
		}
				
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
			$cmdArmedNight->setDisplay('generic_type', 'ALARM_MODE');
			$cmdArmedNight->save();
		}
		$this->setConfiguration('SetModeNuit',$cmdArmedNight->getId()."|"."Mode Nuit");
		
		$cmdArmedDay = $this->getCmd(null, 'armed_day');
		if (!is_object($cmdArmedDay)) {
			$cmdArmedDay = new verisureCmd();
			$cmdArmedDay->setOrder(7);
			$cmdArmedDay->setName('Mode Jour');
			$cmdArmedDay->setEqLogic_id($this->getId());
			$cmdArmedDay->setLogicalId('armed_day');
			$cmdArmedDay->setType('action');
			$cmdArmedDay->setSubType('other');
			$cmdArmedDay->setDisplay('generic_type', 'ALARM_MODE');
			$cmdArmedDay->save();
		}
		$this->setConfiguration('SetModePresent',$cmdArmedDay->getId()."|"."Mode Jour");
		
		$cmdArmedExt = $this->getCmd(null, 'armed_ext');
		if (!is_object($cmdArmedExt)) {
			$cmdArmedExt = new verisureCmd();
			$cmdArmedExt->setOrder(8);
			$cmdArmedExt->setName('Mode Extérieur');
			$cmdArmedExt->setEqLogic_id($this->getId());
			$cmdArmedExt->setLogicalId('armed_ext');
			$cmdArmedExt->setType('action');
			$cmdArmedExt->setSubType('other');
			$cmdArmedExt->setDisplay('generic_type', 'ALARM_MODE');
			$cmdArmedExt->save();
		}
		$this->setConfiguration('SetModeAbsent',$cmdArmedExt->getId()."|"."Mode Extérieur");
		
		$cmdState = $this->getCmd(null, 'getstate');
		if ( ! is_object($cmdState)) {
			$cmdState = new verisureCmd();
			$cmdState->setOrder(9);
			$cmdState->setName('Statut Alarme');
			$cmdState->setEqLogic_id($this->getId());
			$cmdState->setLogicalId('getstate');
			$cmdState->setType('action');
			$cmdState->setSubType('other');
			$cmdState->save();
		}	
	}
	 

    /*     * **********************Getteur Setteur*************************** */

	public function SynchronizeMyInstallation($numinstall,$username,$password,$country)	{
		
		log::add('verisure', 'info', ''.$jeedom_event_date.'Démarrage de la synchronisation');
		$MyAlarm = new verisureAPI($numinstall,$username,$password,$country);
		$result_login = $MyAlarm->Login();
      	log::add('verisure', 'debug', 'Login					' . var_export($result_login, true));
      	$result_myinst = $MyAlarm->MyInstallation();
		log::add('verisure', 'debug', 'MyInstallation		' . var_export($result_myinst, true));
		$result_logout = $MyAlarm->Logout();
		log::add('verisure', 'debug', 'Logout				' . var_export($result_logout, true));
				
		if ( $result_myinst[0] == 200 && $result_myinst[1] == "OK")  {
			$result = $result_myinst[3];
			log::add('verisure', 'info', ''.$jeedom_event_date.'Synchronisation terminée avec succès !');
		}
		else  {
			throw new Exception($result_myinst[2]);
		}
		return $result;
	}
		
	public function GetStateAlarm()	{
		
		log::add('verisure', 'info', ''.$jeedom_event_date.'Demande de statut');
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_login = $MyAlarm->Login();
      	log::add('verisure', 'debug', 'Login					' . var_export($result_login, true));
		$result_getstate = $MyAlarm->GetState();
		log::add('verisure', 'debug', 'GetState				' . var_export($result_getstate, true));
		$result_logout = $MyAlarm->Logout();
		log::add('verisure', 'debug', 'Logout				' . var_export($result_logout, true));
				
		if ( $result_getstate[3] == 200)  {
			if ( $result_getstate[4] == "OK")  {
				$result = $result_getstate[6];
				log::add('verisure', 'info', ''.$jeedom_event_date.'Statut mis à jour');
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
		
		log::add('verisure', 'info', ''.$jeedom_event_date.'Demande activation mode total');
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_login = $MyAlarm->Login();
      	log::add('verisure', 'debug', 'Login					' . var_export($result_login, true));
		$result_armtotal = $MyAlarm->ArmTotal();
		log::add('verisure', 'debug', 'ArmTotal				' . var_export($result_armtotal, true));
		$result_logout = $MyAlarm->Logout();
		log::add('verisure', 'debug', 'Logout				' . var_export($result_logout, true));
				
		if ( $result_armtotal[3] == 200)  {
			if ( $result_armtotal[4] == "OK")  {
				$result = $result_armtotal[6];
				log::add('verisure', 'info', ''.$jeedom_event_date.'Activation mode total OK');
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
		
		log::add('verisure', 'info', ''.$jeedom_event_date.'Demande activation mode nuit');
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_login = $MyAlarm->Login();
      	log::add('verisure', 'debug', 'Login					' . var_export($result_login, true));
		$result_armnight = $MyAlarm->ArmNight();
		log::add('verisure', 'debug', 'ArmNight				' . var_export($result_armnight, true));
		$result_logout = $MyAlarm->Logout();
		log::add('verisure', 'debug', 'Logout				' . var_export($result_logout, true));
				
		if ( $result_armnight[3] == 200)  {
			if ( $result_armnight[4] == "OK")  {
				$result = $result_armnight[6];
				log::add('verisure', 'info', ''.$jeedom_event_date.'Activation mode nuit OK');
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
		
		log::add('verisure', 'info', ''.$jeedom_event_date.'Demande activation mode jour');
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_login = $MyAlarm->Login();
      	log::add('verisure', 'debug', 'Login					' . var_export($result_login, true));
		$result_armday = $MyAlarm->ArmDay();
		log::add('verisure', 'debug', 'ArmDay				' . var_export($result_armday, true));
		$result_logout = $MyAlarm->Logout();
		log::add('verisure', 'debug', 'Logout				' . var_export($result_logout, true));
				
		if ( $result_armday[3] == 200)  {
			if ( $result_armday[4] == "OK")  {
				$result = $result_armday[6];
				log::add('verisure', 'info', ''.$jeedom_event_date.'Activation mode jour OK');
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
		
		log::add('verisure', 'info', ''.$jeedom_event_date.'Demande activation mode extérieur');
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_login = $MyAlarm->Login();
      	log::add('verisure', 'debug', 'Login					' . var_export($result_login, true));
		$result_armext = $MyAlarm->ArmExt();
		log::add('verisure', 'debug', 'ArmExt				' . var_export($result_armext, true));
		$result_logout = $MyAlarm->Logout();
		log::add('verisure', 'debug', 'Logout				' . var_export($result_logout, true));
				
		if ( $result_armext[3] == 200)  {
			if ( $result_armext[4] == "OK")  {
				$result = $result_armext[6];
				log::add('verisure', 'info', ''.$jeedom_event_date.'Activation mode extérieur OK');
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
		
		log::add('verisure', 'info', ''.$jeedom_event_date.'Demande désactivation');
		$MyAlarm = new verisureAPI($this->getConfiguration('numinstall'),$this->getConfiguration('username'),$this->getConfiguration('password'),$this->getConfiguration('country'));
		$result_login = $MyAlarm->Login();
      	log::add('verisure', 'debug', 'Login					' . var_export($result_login, true));
		$result_disarm = $MyAlarm->Disarm();
		log::add('verisure', 'debug', 'Disarm				' . var_export($result_disarm, true));
		$result_logout = $MyAlarm->Logout();
		log::add('verisure', 'debug', 'Logout				' . var_export($result_logout, true));
				
		if ( $result_disarm[3] == 200)  {
			if ( $result_disarm[4] == "OK")  {
				$result = $result_disarm[6];
				log::add('verisure', 'info', ''.$jeedom_event_date.'Désactivation OK');
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
				$state = $eqlogic->GetStateAlarm(); 						// On lance la fonction GetStatusAlarm() pour récupérer le statut de l'alarme et on le stocke dans la variable $status
				switch ($state)  {
					case '0':
						$eqlogic->checkAndUpdateCmd('enable', "0");			// On met à jour la commande avec le LogicalId 'enable' de l'eqlogic
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
						throw new Exception($state);
						break;
					case 'Erreur de commande Verisure':
						throw new Exception($state);
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
						throw new Exception($state);
						break;
					case 'Erreur de commande Verisure':
						throw new Exception($state);
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
						throw new Exception($state);
						break;
					case 'Erreur de commande Verisure':
						throw new Exception($state);
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
						throw new Exception($state);
						break;
					case 'Erreur de commande Verisure':
						throw new Exception($state);
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
						throw new Exception($state);
						break;
					case 'Erreur de commande Verisure':
						throw new Exception($state);
						break;
				}
				break;	
			
			case 'released':
				$state = $eqlogic->DisarmAlarm();
				switch ($state)  {
					case '0':
						$eqlogic->checkAndUpdateCmd('enable', "0");	
						$eqlogic->checkAndUpdateCmd('mode', "Désactivée");
						break;
					case 'Erreur de connexion au cloud Verisure':
						throw new Exception($state);
						break;
					case 'Erreur de commande Verisure':
						throw new Exception($state);
						break;
				}	
				break;
		}
	}

    /*     * **********************Getteur Setteur*************************** */
}


?>