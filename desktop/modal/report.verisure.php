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
    
	$report = verisure::GetReportAlarm(init('numinstall'),init('username'),init('pwd'),init('country'));
	
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
							$i = 0;
							$j = 1;
							$filter = array("1","2","13","16","24","29","31","32","40","46","202","204","311");
							foreach ($report['REG'] as $reg)  {
								if (in_array($report['REG'][$i]['@attributes']['type'], $filter))  {
									echo '<tr>';
									echo '<td>';
									echo $j;
									echo '</td>';
									echo '<td>';
									$date = date_create_from_format('ymdHis', $report['REG'][$i]['@attributes']['time']);
									echo $date->format('d/m/Y H:i:s');
									echo '</td>';
									switch ($report['REG'][$i]['@attributes']['type'])  {
										case 1:
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_entree.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $report['REG'][$i]['@attributes']['alias'];
											echo '<br/>';
											echo 'User : '. $report['REG'][$i]['@attributes']['user']. $report['REG'][$i]['@attributes']['myverisureUser'];
											echo '</td>';
											break;
										case 2:
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_sortie.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $report['REG'][$i]['@attributes']['alias'];
											echo '<br/>';
											echo 'User : '. $report['REG'][$i]['@attributes']['user']. $report['REG'][$i]['@attributes']['myverisureUser'];
											echo '</td>';
											break;
										case 13:
										case 24:
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_alerte.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $report['REG'][$i]['@attributes']['alias'];
											echo '<br/>';
											echo 'Smartplug : '. $report['REG'][$i]['@attributes']['device'];
											echo '</td>';
											break;
										case 16:
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_photos.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $report['REG'][$i]['@attributes']['alias'];
											echo '<br/>';
											echo 'Smartplug : '. $report['REG'][$i]['@attributes']['device'];
											echo '<br/>';
											echo 'Source : '. $report['REG'][$i]['@attributes']['source'].' - User : '. $report['REG'][$i]['@attributes']['myverisureUser'];
											echo '</td>';
											break;
										case 29:
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_sos.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $report['REG'][$i]['@attributes']['alias'];
											echo '<br/>';
											echo 'Source : Centrale';
											echo '</td>';
											break;
										case 31:
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_total.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $report['REG'][$i]['@attributes']['alias'];
											echo '<br/>';
											echo 'Source : '. $report['REG'][$i]['@attributes']['source'].' - User : '. $report['REG'][$i]['@attributes']['user']. $report['REG'][$i]['@attributes']['myverisureUser'];
											echo '</td>';
											break;
										case 32:
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_desactive.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $report['REG'][$i]['@attributes']['alias'];
											echo '<br/>';
											echo 'Source : '. $report['REG'][$i]['@attributes']['source'].' - User : '. $report['REG'][$i]['@attributes']['user']. $report['REG'][$i]['@attributes']['myverisureUser'];
											echo '</td>';
											break;
										case 46:
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_nuit.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $report['REG'][$i]['@attributes']['alias'];
											echo '<br/>';
											echo 'Source : '. $report['REG'][$i]['@attributes']['source'].' - User : '. $report['REG'][$i]['@attributes']['user']. $report['REG'][$i]['@attributes']['myverisureUser'];
											echo '</td>';
											break;
										case 202:
										case 311:
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_jour.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $report['REG'][$i]['@attributes']['alias'];
											echo '<br/>';
											echo 'Source : '. $report['REG'][$i]['@attributes']['source'].' - User : '. $report['REG'][$i]['@attributes']['user']. $report['REG'][$i]['@attributes']['myverisureUser'];
											echo '</td>';
											break;
										case 40:
										case 204:
											echo '<td>';
											echo '<img src="plugins/verisure/core/img/logo_ext.png" height="35" width="35"/>';
											echo '</td>';
											echo '<td>';
											echo $report['REG'][$i]['@attributes']['alias'];
											echo '<br/>';
											echo 'Source : '. $report['REG'][$i]['@attributes']['source'].' - User : '. $report['REG'][$i]['@attributes']['user']. $report['REG'][$i]['@attributes']['myverisureUser'];
											echo '</td>';
											break;
									}		
									echo '</tr>';
									$j++;
								}
								$i++;	
							}	
						?>
					</tbody>
				</table>
			</div>
		</fieldset>
	</form>
</div>