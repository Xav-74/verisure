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

	if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }
    
	$eqLogic = eqLogic::byId(init('eqLogic_id'));
	$report = $eqLogic->GetReportAlarm();

?>

<div class="container">
	<h2>Journal d'activité - Verisure</h2>
	<h6>(Les demandes de statut ne sont pas incluses dans ce rapport)</h6>
	<br/><br/> 
	<form class="form-horizontal">
		<fieldset>
			<div class="form-group">
				
				
				<table id="table_report" class="table table-bordered table-condensed">
					<thead>
						<tr>
							<th style="width: 10%;">{{ID}}</th>
							<th style="width: 20%;">{{Date}}</th>
							<th style="width: 10%;">{{Type}}</th>
							<th style="width: 60%;">{{Activité}}</th>
						</tr>
					</thead>
					<tbody>
						<?php
							if ( $eqLogic->getConfiguration('alarmtype') == 1 || $eqLogic->getConfiguration('alarmtype') == 3 )  {
								
								$i = 1;
								foreach ($report['reg'] as $reg)  {
									echo '<tr>';
									echo '<td>';
									echo $i;
									echo '</td>';
									echo '<td>';
									echo $reg['time'];
									echo '</td>';
									switch ($reg['type'])  {
										case 1:
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_entree.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $reg['alias'];
											echo '<br/>';
											if ( $reg['source'] != " " )  { echo 'User : '. $reg['source'].' - '.$reg['myVerisureUser']; }
											else { echo 'User : '. $reg['myVerisureUser']; }
											echo '</td>';
										break;
										case 2:
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_sortie.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $reg['alias'];
											echo '<br/>';
											if ( $reg['source'] != " " )  { echo 'User : '. $reg['source'].' - '.$reg['myVerisureUser']; }
											else { echo 'User : '. $reg['myVerisureUser']; }
											echo '</td>';
										break;
										case 13:
										case 24:
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_alerte.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $reg['alias'];
											echo '<br/>';
											echo 'Smartplug : '. $reg['device'];
											echo '</td>';
										break;
										case 16:
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_photos.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $reg['alias'];
											echo '<br/>';
											echo 'Smartplug : '. $reg['device'];
											echo '<br/>';
											if ( $reg['source'] != " " )  { echo 'User : '. $reg['source'].' - '.$reg['myVerisureUser']; }
											else { echo 'User : '. $reg['myVerisureUser']; }
											echo '</td>';
										break;
										case 25:
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_def_elec.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $reg['alias'];
											echo '<br/>';
											echo 'Source : Centrale';
											echo '</td>';
										break;
										case 26:
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_ret_elec.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $reg['alias'];
											echo '<br/>';
											echo 'Source : Centrale';
											echo '</td>';
										break;
										case 29:
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_sos.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $reg['alias'];
											echo '<br/>';
											echo 'Source : Centrale';
											echo '</td>';
										break;
										case 31:
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_total.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $reg['alias'];
											echo '<br/>';
											if ( $reg['source'] != " " )  { echo 'User : '. $reg['source'].' - '.$reg['myVerisureUser']; }
											else { echo 'User : '. $reg['myVerisureUser']; }
											echo '</td>';
										break;
										case 32:
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_desactive.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $reg['alias'];
											echo '<br/>';
											if ( $reg['source'] != " " )  { echo 'User : '. $reg['source'].' - '.$reg['myVerisureUser']; }
											else { echo 'User : '. $reg['myVerisureUser']; }
											echo '</td>';
										break;
										case 46:
										case 203:
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_nuit.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $reg['alias'];
											echo '<br/>';
											if ( $reg['source'] != " " )  { echo 'User : '. $reg['source'].' - '.$reg['myVerisureUser']; }
											else { echo 'User : '. $reg['myVerisureUser']; }
											echo '</td>';
										break;
										case 202:
										case 311:
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_jour.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $reg['alias'];
											echo '<br/>';
											if ( $reg['source'] != " " )  { echo 'User : '. $reg['source'].' - '.$reg['myVerisureUser']; }
											else { echo 'User : '. $reg['myVerisureUser']; }
											echo '</td>';
										break;
										case 40:
										case 204:
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_ext.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $reg['alias'];
											echo '<br/>';
											if ( $reg['source'] != " " )  { echo 'User : '. $reg['source'].' - '.$reg['myVerisureUser']; }
											else { echo 'User : '. $reg['myVerisureUser']; }
											echo '</td>';
										break;
									}		
									echo '</tr>';
									$i++;
								}
							}
							
							if ( $eqLogic->getConfiguration('alarmtype') == 2 )  {
								$j = 1;
								foreach ($report['eventLog'] as $eventLog)  {
									echo '<tr>';
									echo '<td>';
									echo $j;
									echo '</td>';
									echo '<td>';
									$date = new DateTime($eventLog['eventTime'], new DateTimeZone('UTC'));
									$date->setTimezone(new DateTimeZone(config::byKey('timezone')));
									echo $date->format('d/m/Y H:i:s');
									echo '</td>';
									switch ($eventLog['eventCategory'])  {
										case 'ARM':
											echo '<td>';
											if ($eventLog['armState'] == 'ARMED_AWAY') {echo '<img src="plugins/verisure/core/img/logo_total.png" height="35" width="35"/>';}
                                            if ($eventLog['armState'] == 'ARMED_HOME') {echo '<img src="plugins/verisure/core/img/logo_home.png" height="35" width="35"/>';}
											echo '</td>';
											echo '<td>';
											echo $eventLog['armState'];
											echo '<br/>';
											echo 'Device / User : '. $eventLog['device']['gui']['label'].' - '. $eventLog['device']['area'].' - '.$eventLog['userName'];
											echo '</td>';
										break;
                                        case 'DISARM':
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_desactive.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $eventLog['armState'];
											echo '<br/>';
											echo 'Device / User : '. $eventLog['device']['gui']['label'].' - '. $eventLog['device']['area'].' - '.$eventLog['userName'];
											echo '</td>';
										break;
										case 'INTRUSION':
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_alerte.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $eventLog['eventCategory'];
											echo '<br/>';
											echo 'Device : '. $eventLog['device']['gui']['label'].' - '. $eventLog['device']['area'];
											echo '</td>';
										break;
										case 'SOS':
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_sos.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $eventLog['eventCategory'];
											echo '<br/>';
											echo 'Device : '. $eventLog['device']['gui']['label'].' - '. $eventLog['device']['area'];
											echo '</td>';
										break;
										case 'PICTURE':
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_photos.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $eventLog['eventCategory'];
											echo '<br/>';
											echo 'Device : '. $eventLog['device']['gui']['label'].' - '. $eventLog['device']['area'];
											echo '</td>';
										break;
									}
									echo '</tr>';
									$j++;
								}
							}
						?>
					</tbody>
				</table>
			</div>
		</fieldset>
	</form>
</div>