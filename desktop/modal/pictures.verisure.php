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

	if (!isConnect()) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }
    
	$eqLogic = eqLogic::byId(init('eqLogic_id'));
		
	if ($eqLogic->getConfiguration('alarmtype') == 1 || $eqLogic->getConfiguration('alarmtype') == 3)   {
		
		list($device, $code) = explode('-', init('device'));
		$path = "plugins/verisure/data/".date("Ymd_His")."_smartplugID_".$device.".jpg";	
		$img = $eqLogic->GetPhotosRequest($device, $code);
		$image = base64_decode($img);
		file_put_contents($path,$image);
	}
	
	if ($eqLogic->getConfiguration('alarmtype') == 2 )   {
		
		$path = "plugins/verisure/data/".date("Ymd_His")."_smartplugID_".init('device').".jpg";	
		$img = $eqLogic->GetPhotosRequest(init('device'));
		file_put_contents($path,$img);
	}
			
?>

<img src="<?php echo $path?>" style="height:100%; width:100%"></img>