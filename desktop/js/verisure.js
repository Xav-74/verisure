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


$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
$("#table_smartplug").sortable({axis: "y", cursor: "move", items: ".smartplug", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

/*
 * Fonction pour l'ajout de commande, appellé automatiquement par plugin.template
 */

function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }
    
	var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
	tr += '<td class="hidden-xs" style="width:5%">';
	tr += '<span class="cmdAttr" data-l1key="id"></span>';
	tr += '</td>';
	tr += '<td style="width:20%">';
	tr += '<input class="cmdAttr form-control input-sm" style="width:80%" data-l1key="name" placeholder="{{Nom de la commande}}">';
	tr += '</td>';
	tr += '<td style="width:10%; padding:5px 0px">';
	tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
	tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
	tr += '</td>';
	tr += '<td style="width:20%">';
	tr += '<input class="cmdAttr form-control input-sm" style="width:80%" data-l1key="logicalId" readonly=true>';
	tr += '</td>';
	tr += '<td style="width:10%">';
	if (init(_cmd.type) == 'info') {
		tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label>';
		//tr += '</br><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label>';
		if (init(_cmd.subType) == 'binary') {
			tr += '</br><label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label>';
		}
	}
	if (init(_cmd.type) == 'action') {
		tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label>';
	}
	tr += '</td>';
	tr += '<td style="width:25%">';
	tr += '<span class="cmdAttr" data-l1key="htmlstate" placeholder="{{Valeur}}">';
	tr += '</td>';	
	tr += '<td style="width:10%">';
	if (is_numeric(_cmd.id)) {
		tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
		tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
	}
	tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove" style="margin-top:4px;"></i>';
	tr += '</td>';
	tr += '</tr>';
	
	$('#table_cmd tbody').append(tr);
	$('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
	if (isset(_cmd.type)) {
		$('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
	}
	jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
};


function printEqLogic(_eqLogic) {
 
	$('#table_smartplug tbody').empty();
	 
	if ($('.eqLogicAttr[data-l2key=nb_smartplug]').value() != "")  {
		for(j = 0; j < $('.eqLogicAttr[data-l2key=nb_smartplug]').value() ; j++) {	
		   	var tr = '<tr>';
      		tr += '<td>';
			tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+_eqLogic.configuration.devices['smartplugID'+j]+'" readonly="true">';
			tr += '</td>';
          	tr += '<td>';
			tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+_eqLogic.configuration.devices['smartplugName'+j]+'" readonly="true">';
			tr += '</td>';
			tr += '<td>';
			tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+_eqLogic.configuration.devices['smartplugModel'+j]+'" readonly="true">';
			tr += '</td>';
          	tr += '</tr>';
      		$('#table_smartplug tbody').append(tr);
        }
      	var tr = $('#table_smartplug tbody tr:last');
    } 
};


$('#bt_Authentication_2FA').on('click',function() {
 
	var alarmtype = $('.eqLogicAttr[data-l2key=alarmtype]').value();
	var numinstall = $('.eqLogicAttr[data-l2key=numinstall]').value();
	var username = $('.eqLogicAttr[data-l2key=username]').value();
	var pwd = $('.eqLogicAttr[data-l2key=password]').value();
	var code = $('.eqLogicAttr[data-l2key=code]').value();
	var country = $('.eqLogicAttr[data-l2key=country]').value();
	
	$('#table_smartplug tbody').empty();
	$('#nbsp').empty();
	$('#nbclimate').empty();
	$('#nbdoor').empty();
	$('#nbcams').empty();
	$('#nbdevice').empty();
  
	$('#div_alert').showAlert({message: '{{Authentification en cours}}', level: 'warning'});	
	$.ajax({													// fonction permettant de faire de l'ajax
		type: "POST", 											// methode de transmission des données au fichier php
		url: "plugins/verisure/core/ajax/verisure.ajax.php", 	// url du fichier php
		data: {
			action: "Authentication_2FA",
			alarmtype: alarmtype,
			numinstall: numinstall,
			username: username,
			pwd: pwd,
			code: code,
			country: country
			},
		dataType: 'json',
			error: function (request, status, error) {
			handleAjaxError(request, status, error);
			},
		success: function (data) { 															
			
			if ($('.eqLogicAttr[data-l2key=alarmtype]').value() == 1)   {
				if (data.state != 'ok' || data.result == null) {
					$('#div_alert').showAlert({message: '{{Erreur lors de l\'authentification 2FA}}', level: 'danger'});
					return;
				}
				else  {
					if ( data.result['type'] == "OTP" ) {
						var nb_phones = data.result['res'].length;
						var message = "\n Vérification de l'identité (2FA) \n Choisissez le téléphone pour l'authentification par SMS :\n\n";
						for(i = 0; i < nb_phones ; i++) {
							var id = parseInt(data.result['res'][i]['id']) + 1;
							message = message + " " + id + " : " + data.result['res'][i]['phone'] + "\n";
						}
						var result = prompt(message, "");
						var phone_id = parseInt(result - 1);
						sendOTP(alarmtype, numinstall, username, pwd, code, country, phone_id);
					}

					if ( data.result['type'] == "devices" ) {
						var nbsp = data.result['res'].length;
						$('#nbsp').append(nbsp); 
						for(j = 0; j < nbsp ; j++) {
							var tr = '<tr>';
							tr += '<td>';
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['res'][j]['id']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugID'+j+'">';
							tr += '</td>';
							tr += '<td>';
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['res'][j]['name']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugName'+j+'">';
							tr += '</td>';
							tr += '<td>';
							if (data.result['res'][j]['type'] == "CENT") { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Centrale de l\'alarme" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">'; }
							if (data.result['res'][j]['type'] == "MG") { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Détecteur de chocs et d\'ouverture" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">'; }
							if (data.result['res'][j]['type'] == "XP" || data.result['res'][j]['type'] == "XR" || data.result['res'][j]['type'] == "YR") { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Détecteur de mouvements avec images" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">'; }
							tr += '</td>';
							tr += '<td>';					
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['res'][j]['type']+'"  style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+j+'">';
							tr += '</td>';
							tr += '</tr>';
							$('#table_smartplug tbody').append(tr);
						}
						var tr = $('#table_smartplug tbody tr:last');
					}
				}
				$('#div_alert').showAlert({message: '{{Authentification 2FA terminée avec succès}}', level: 'success'});
			}
			
			/*if ($('.eqLogicAttr[data-l2key=alarmtype]').value() == 2)   {
				if (data.state != 'ok') {
					$('#div_alert').showAlert({message: '{{Erreur lors de la synchronisation}}', level: 'danger'});
					return;
				}
				else  {
					var nbsp = 0;
					var nbclimate = data.result['climateDevice'].length;
					$('#nbclimate').append(nbclimate); 
					for(j = 0; j < nbclimate ; j++) {
						var tr = '<tr>';
						tr += '<td>';
						tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['climateDevice'][j]['deviceLabel']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugID'+j+'">';
						tr += '</td>';
						tr += '<td>';
						tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['climateDevice'][j]['deviceArea']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugName'+j+'">';
						tr += '</td>';
						tr += '<td>';
						tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['climateDevice'][j]['deviceType']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">';
						tr += '</td>';
						tr += '<td>';					
						tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="climateDevice" style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+j+'">';
						tr += '</td>';
						tr += '</tr>';
						$('#table_smartplug tbody').append(tr);
					}
					nbsp = nbclimate;
					
					var nbdoor = data.result['doorWindowDevice'].length;
					$('#nbdoor').append(nbdoor);
					for(j = 0; j < nbdoor ; j++) {
						var i = j + nbsp;
						var tr = '<tr>';
						tr += '<td>';
						tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['doorWindowDevice'][j]['deviceLabel']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugID'+i+'">';
						tr += '</td>';
						tr += '<td>';
						tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['doorWindowDevice'][j]['area']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugName'+i+'">';
						tr += '</td>';
						tr += '<td>';
						tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="DOORSENSOR1" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+i+'">';
						tr += '</td>';
						tr += '<td>';					
						tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="doorWindowDevice" style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+i+'">';
						tr += '</td>';
						tr += '</tr>';
						$('#table_smartplug tbody').append(tr);
					}
					nbsp += nbdoor;
					
					var nbcams = data.result['cameraDevice'].length;
					$('#nbcams').append(nbcams);
					for(j = 0; j < nbcams ; j++) {
						var i = j + nbsp;
						var tr = '<tr>';
						tr += '<td>';
						tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['cameraDevice'][j]['deviceLabel']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugID'+i+'">';
						tr += '</td>';
						tr += '<td>';
						tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['cameraDevice'][j]['area']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugName'+i+'">';
						tr += '</td>';
						tr += '<td>';
						tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="CAMERA1" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+i+'">';
						tr += '</td>';
						tr += '<td>';					
						tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="cameraDevice" style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+i+'">';
						tr += '</td>';
						tr += '</tr>';
						$('#table_smartplug tbody').append(tr);
					}
					nbsp += nbcams;
										
					var nbdevice = data.result['smartPlugDevice'].length;
					$('#nbdevice').append(nbdevice);
					for(j = 0; j < nbdevice ; j++) {
						var i = j + nbsp;
						var tr = '<tr>';
						tr += '<td>';
						tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['smartPlugDevice'][j]['deviceLabel']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugID'+i+'">';
						tr += '</td>';
						tr += '<td>';
						tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['smartPlugDevice'][j]['area']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugName'+i+'">';
						tr += '</td>';
						tr += '<td>';
						tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="SMARTPLUG1" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+i+'">';
						tr += '</td>';
						tr += '<td>';					
						tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="smartPlugDevice" style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+i+'">';
						tr += '</td>';
						tr += '</tr>';
						$('#table_smartplug tbody').append(tr);
					}
					nbsp += nbdevice;
					
					$('#nbsp').append(nbsp);
					var tr = $('#table_smartplug tbody tr:last');
				}
				$('#div_alert').showAlert({message: '{{Synchronisation terminée avec succès}}', level: 'success'});
			}*/
		}
	});
});


function sendOTP(alarmtype, numinstall, username, pwd, code, country, phone_id)
{
 
	$('#div_alert').showAlert({message: '{{Envoi du SMS en cours}}', level: 'warning'});	
	$.ajax({													// fonction permettant de faire de l'ajax
		type: "POST", 											// methode de transmission des données au fichier php
		url: "plugins/verisure/core/ajax/verisure.ajax.php", 	// url du fichier php
		data: {
			action: "Send_OTP",
			alarmtype: alarmtype,
			numinstall: numinstall,
			username: username,
			pwd: pwd,
			code: code,
			country: country,
			phone_id: phone_id,
			},
		dataType: 'json',
			error: function (request, status, error) {
			handleAjaxError(request, status, error);
			},
		success: function (data) { 			
			
			if (data.state != 'ok') {
				$('#div_alert').showAlert({message: '{{Erreur lors de l\'authentification 2FA}}', level: 'danger'});
				return;
			}
			else  {
				var message = "\n Vérification de l'identité (2FA) \n Saisissez le code reçu par SMS :\n\n";
				var result = prompt(message, "");
				validateDevice(alarmtype, numinstall, username, pwd, code, country, result);
			}
		}
	});
};


function validateDevice(alarmtype, numinstall, username, pwd, code, country, sms_code)
{
 
	$('#div_alert').showAlert({message: '{{Validation de l\'équipement}}', level: 'warning'});	
	$.ajax({													// fonction permettant de faire de l'ajax
		type: "POST", 											// methode de transmission des données au fichier php
		url: "plugins/verisure/core/ajax/verisure.ajax.php", 	// url du fichier php
		data: {
			action: "Validate_Device",
			alarmtype: alarmtype,
			numinstall: numinstall,
			username: username,
			pwd: pwd,
			code: code,
			country: country,
			sms_code: sms_code,
			},
		dataType: 'json',
			error: function (request, status, error) {
			handleAjaxError(request, status, error);
			},
		success: function (data) { 			
			
			if (data.state != 'ok' || data.result == null) {
				$('#div_alert').showAlert({message: '{{Erreur lors de l\'authentification 2FA}}', level: 'danger'});
				return;
			}
			else  {
				var nbsp = data.result['res'].length;
				$('#nbsp').append(nbsp); 
				for(j = 0; j < nbsp ; j++) {
					var tr = '<tr>';
					tr += '<td>';
					tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['res'][j]['id']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugID'+j+'">';
					tr += '</td>';
					tr += '<td>';
					tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['res'][j]['name']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugName'+j+'">';
					tr += '</td>';
					tr += '<td>';
					if (data.result['res'][j]['type'] == "CENT") { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Centrale de l\'alarme" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">'; }
					if (data.result['res'][j]['type'] == "MG") { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Détecteur de chocs et d\'ouverture" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">'; }
					if (data.result['res'][j]['type'] == "XP" || data.result['res'][j]['type'] == "XR" || data.result['res'][j]['type'] == "YR") { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Détecteur de mouvements avec images" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">'; }
					tr += '</td>';
					tr += '<td>';					
					tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['res'][j]['type']+'"  style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+j+'">';
					tr += '</td>';
					tr += '</tr>';
					$('#table_smartplug tbody').append(tr);
				}
				var tr = $('#table_smartplug tbody tr:last');
			}
			$('#div_alert').showAlert({message: '{{Authentification 2FA terminée avec succès}}', level: 'success'});
		}
	});
};


$('#bt_Reporting').on('click',function() {
	
	$('#md_modal').dialog({title: "{{Journal d'activité}}"});
	$('#md_modal').load('index.php?v=d&plugin=verisure&modal=report.verisure&eqLogic_id='+ $('.eqLogicAttr[data-l1key=id]').value()).dialog('open');
});