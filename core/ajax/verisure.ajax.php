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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect()) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }
    
    ajax::init();

	if (init('action') == 'getJSON') {
		$result = file_get_contents( dirname(__FILE__).'/../../data/stateDevices.json');
		ajax::success($result);
	} 

	if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

	if (init('action') == 'Authentication_2FA') {
		$result = verisure::Authentication_2FA(init('alarmtype'),init('numinstall'),init('username'),init('pwd'),init('code'),init('country'));
		ajax::success($result);
	}

    if (init('action') == 'Send_OTP') {
		$result = verisure::Send_OTP(init('alarmtype'),init('numinstall'),init('username'),init('pwd'),init('code'),init('country'), init('phone_id'));
		ajax::success($result);
	}

    if (init('action') == 'Validate_Device') {
		$result = verisure::Validate_Device(init('alarmtype'),init('numinstall'),init('username'),init('pwd'),init('code'),init('country'), init('sms_code'));
		ajax::success($result);
	}

    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
}

catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}

?>