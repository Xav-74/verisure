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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function verisure_install() {

	message::add('verisure', __('Merci pour l\'installation du plugin Verisure. Lisez bien la documentation avant utilisation et n\'hésitez pas à laisser un avis sur le Market Jeedom !', __FILE__));
	
}

function verisure_update() {

	// Mise à jour de l'ensemble des commandes pour chaque équipement
    log::add('verisure', 'debug', 'Updating verisure plugin commands');
    foreach (eqLogic::byType('verisure') as $eqLogic) {
        $eqLogic->save();
        log::add('verisure', 'debug', 'Verisure plugin commands successfully updated for the equipment '. $eqLogic->getHumanName());
    }
	message::add('verisure', __('Merci pour la mise à jour du plugin Verisure. Consultez les notes de version avant utilisation et n\'hésitez pas à laisser un avis sur le Market Jeedom !', __FILE__));
	
 }

function verisure_remove() {

	// Suppression du cron si existant
    $cron = cron::byClassAndFunction('verisure', 'pullHisto');
    if (is_object($cron)) {
        $cron->remove();
        log::add('verisure', 'debug', 'Remove cron pullHisto');
    }
    
    message::add('verisure', __('Le plugin Verisure a été correctement désinstallé. N\'hésitez pas à laisser un avis sur le Market Jeedom !', __FILE__));

}

?>
