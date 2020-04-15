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
include_file('core', 'authentification', 'php');
if (!isConnect()) {
    include_file('desktop', '404', 'php');
    die();
}
?>

<form class="form-horizontal">
    
	<fieldset>
				
		<div class="form-group">
            <br />
			<label class="col-lg-3 control-label">{{Information importante :}}</label>
            <div class="col-lg-7">
                Ce plugin utilise les API de Verisure Europe (Securitas Direct) pour obtenir les informations de votre alarme.<br />
				NOTE : CE PLUGIN N'EST EN AUCUN CAS ASSOCIÉ OU LIÉ AUX SOCIÉTÉS DU GROUPE SECURITAS DIRECT - VERISURE.<br />
				L'usage de ce plugin est destiné à des fins strictement personnelles et privées.<br />
				Par conséquent, le développeur n'approuve ni ne tolère aucune utilisation inappropriée, et n'assume aucune responsabilité légale pour la fonctionnalité ou la sécurité de vos alarmes et appareils.<br />
				<br />
				Contributions :<br />
				- Merci à Cebeerre pour les API Securitas Direct - Verisure     (https://github.com/Cebeerre/VerisureEUAPI)<br />
				- Merci à apages2 pour son aide     (https://github.com/apages2)<br />
				- Merci à mguyard pour les exemples	    (https://github.com/mguyard)<br />
				<br />
			</div>
        </div>
		
    </fieldset>
	
</form>
