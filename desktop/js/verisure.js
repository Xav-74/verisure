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
							message = message + "Tapez " + id + " pour le " + data.result['res'][i]['phone'] + "\n";
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
							if (data.result['res'][j]['type'] != "CENT") { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['res'][j]['code']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugID'+j+'">'; }
							else { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="0" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugID'+j+'">'; }
							tr += '</td>';
							tr += '<td>';
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['res'][j]['name']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugName'+j+'">';
							tr += '</td>';
							tr += '<td>';
							if (data.result['res'][j]['type'] == "MG") { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Détecteur de chocs et d\'ouverture" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">'; }
							else if (data.result['res'][j]['type'] == "XP" || data.result['res'][j]['type'] == "XR" || data.result['res'][j]['type'] == "YR") { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Détecteur de mouvements avec images" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">'; }
							else if (data.result['res'][j]['type'] == "FR") { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Détecteur De fumée" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">'; }
							else if (data.result['res'][j]['type'] == "ZR") { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Sirène intérieure" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">'; }
							else if (data.result['res'][j]['type'] == "TI") { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Lecteur de badge" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">'; }
							else if (data.result['res'][j]['type'] == "JX") { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Alarme Sentinel" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">'; }
							else if (data.result['res'][j]['type'] == "CENT") { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Centrale" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">'; }
							else { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['res'][j]['type']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">'; }
							tr += '</td>';
							tr += '<td>';					
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['res'][j]['type']+'" style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+j+'">';
							tr += '</td>';
							tr += '</tr>';
							$('#table_smartplug tbody').append(tr);
						}
						var tr = $('#table_smartplug tbody tr:last');
					}
				}
				$('#div_alert').showAlert({message: '{{Authentification 2FA terminée avec succès}}', level: 'success'});
			}
			
			if ($('.eqLogicAttr[data-l2key=alarmtype]').value() == 2)   {
				if (data.state != 'ok' || data.result == null) {
					$('#div_alert').showAlert({message: '{{Erreur lors de l\'authentification 2FA}}', level: 'danger'});
					return;
				}
				else  {
					if ( data.result['type'] == "OTP" ) {
						var nb_type = data.result['res'].length;
						var message = "\n Vérification de l'identité (2FA) \n Choisissez la méthode pour l'authentification :\n\n";
						for(i = 0; i < nb_type ; i++) {
							var id = i + 1;
							if ( data.result['res'][i] == 'phone' ) { message = message + "Tapez " + id + " pour utiliser votre téléphone (recommandé)" + "\n";}
							if ( data.result['res'][i] == 'email' ) { message = message + "Tapez " + id + " pour utiliser votre email" + "\n";}
						}
						var result = prompt(message, "");
						if ( parseInt(result - 1) == 0 ) { var type = 'phone'; }
						else if ( parseInt(result - 1) == 1 ) { var type = 'email'; }
						sendOTP(alarmtype, numinstall, username, pwd, code, country, type);
					}
					
					if ( data.result['type'] == "devices" ) {
					
						var nbsp = data.result['res'].length;
						var nbclimate = 0;
						var nbdoor = 0;
						var nbcams = 0;
						var nbdevice = 0;

						$('#nbsp').append(nbsp);
						for(j = 0; j < nbsp ; j++) {
							var tr = '<tr>';
							tr += '<td>';
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['res'][j]['deviceLabel']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugID'+j+'">';
							tr += '</td>';
							tr += '<td>';
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['res'][j]['area']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugName'+j+'">';
							tr += '</td>';
							tr += '<td>';
							if (data.result['res'][j]['gui']['deviceGroup'] == "MAGNETIC") { 
								tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Détecteur de chocs et d\'ouverture" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">';
								tr += '</td>';
								tr += '<td>';					
								tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="doorWindowDevice" style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+j+'">';
								tr += '</td>';
								tr += '</tr>';
								nbdoor++;
							}
							else if (data.result['res'][j]['gui']['deviceGroup'] == "CAMERA") {
								tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Détecteur de mouvements avec images" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">';
								tr += '</td>';
								tr += '<td>';					
								tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="cameraDevice" style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+j+'">';
								tr += '</td>';
								tr += '</tr>';
								nbcams++;
							}
							else if (data.result['res'][j]['gui']['deviceGroup'] == "SMOKE") {
								tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Détecteur De fumée" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">';
								tr += '</td>';
								tr += '<td>';					
								tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="climateDevice" style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+j+'">';
								tr += '</td>';
								tr += '</tr>';
								nbclimate++;
							}
							else if (data.result['res'][j]['gui']['deviceGroup'] == "SIREN") {
								tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Sirène" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">';
								tr += '</td>';
								tr += '<td>';					
								tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="climateDevice" style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+j+'">';
								tr += '</td>';
								tr += '</tr>';
								nbclimate++;
							}
							else if (data.result['res'][j]['gui']['deviceGroup'] == "VOICEBOX") {
								tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="VoiceBox" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">';
								tr += '</td>';
								tr += '<td>';					
								tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="climateDevice" style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+j+'">';
								tr += '</td>';
								tr += '</tr>';
								nbclimate++;
							}
							else if (data.result['res'][j]['gui']['deviceGroup'] == "CONTROLPLUG") {
								tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="SmartPlug" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">';
								tr += '</td>';
								tr += '<td>';					
								tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="smartPlugDevice" style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+j+'">';
								tr += '</td>';
								tr += '</tr>';
								nbdevice++;
							}
							else if (data.result['res'][j]['gui']['deviceGroup'] == "KEYPAD") {
								tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Clavier" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">';
								tr += '</td>';
								tr += '<td>';					
								tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="keypadDevice" style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+j+'">';
								tr += '</td>';
								tr += '</tr>';
							}
							else if (data.result['res'][j]['gui']['deviceGroup'] == "GATEWAY") {
								tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Centrale" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">';
								tr += '</td>';
								tr += '<td>';					
								tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="centrale" style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+j+'">';
								tr += '</td>';
								tr += '</tr>';
							}
							else { 
								tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['res'][j]['gui']['deviceGroup']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">';
								tr += '</td>';
								tr += '<td>';					
								tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['res'][j]['gui']['label']+'" style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+j+'">';
								tr += '</td>';
								tr += '</tr>';
							}
							$('#table_smartplug tbody').append(tr);
						}
						var tr = $('#table_smartplug tbody tr:last');
						$('#nbclimate').append(nbclimate);
						$('#nbdoor').append(nbdoor);
						$('#nbcams').append(nbcams);
						$('#nbdevice').append(nbdevice);
					}
					$('#div_alert').showAlert({message: '{{Synchronisation terminée avec succès}}', level: 'success'});
				}
			}
		}
	});
});


function sendOTP(alarmtype, numinstall, username, pwd, code, country, phone_id)
{
 
	$('#div_alert').showAlert({message: '{{Envoi du code en cours}}', level: 'warning'});	
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
				var message = "\n Vérification de l'identité (2FA) \n Saisissez le code reçu :\n";
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
			
			if ($('.eqLogicAttr[data-l2key=alarmtype]').value() == 1)   {
			
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
						if (data.result['res'][j]['type'] != "CENT") { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['res'][j]['code']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugID'+j+'">'; }
						else { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="0" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugID'+j+'">'; }
						tr += '</td>';
						tr += '<td>';
						tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['res'][j]['name']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugName'+j+'">';
						tr += '</td>';
						tr += '<td>';
						if (data.result['res'][j]['type'] == "MG") { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Détecteur de chocs et d\'ouverture" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">'; }
						else if (data.result['res'][j]['type'] == "XP" || data.result['res'][j]['type'] == "XR" || data.result['res'][j]['type'] == "YR") { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Détecteur de mouvements avec images" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">'; }
						else if (data.result['res'][j]['type'] == "FR") { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Détecteur De fumée" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">'; }
						else if (data.result['res'][j]['type'] == "ZR") { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Sirène intérieure" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">'; }
						else if (data.result['res'][j]['type'] == "TI") { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Lecteur de badge" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">'; }
						else if (data.result['res'][j]['type'] == "JX") { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Alarme Sentinel" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">'; }
						else if (data.result['res'][j]['type'] == "CENT") { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Centrale" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">'; }
						else { tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['res'][j]['type']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">'; }
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

			if ($('.eqLogicAttr[data-l2key=alarmtype]').value() == 2)   {
			
				if (data.state != 'ok' || data.result == null) {
					$('#div_alert').showAlert({message: '{{Erreur lors de l\'authentification 2FA}}', level: 'danger'});
					return;
				}
				else  {
					var nbsp = data.result['res'].length;
					var nbclimate = 0;
					var nbdoor = 0;
					var nbcams = 0;
					var nbdevice = 0;

					$('#nbsp').append(nbsp);
					for(j = 0; j < nbsp ; j++) {
						var tr = '<tr>';
						tr += '<td>';
						tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['res'][j]['deviceLabel']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugID'+j+'">';
						tr += '</td>';
						tr += '<td>';
						tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['res'][j]['area']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugName'+j+'">';
						tr += '</td>';
						tr += '<td>';
						if (data.result['res'][j]['gui']['deviceGroup'] == "MAGNETIC") { 
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Détecteur de chocs et d\'ouverture" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">';
							tr += '</td>';
							tr += '<td>';					
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="doorWindowDevice" style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+j+'">';
							tr += '</td>';
							tr += '</tr>';
							nbdoor++;
						}
						else if (data.result['res'][j]['gui']['deviceGroup'] == "CAMERA") {
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Détecteur de mouvements avec images" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">';
							tr += '</td>';
							tr += '<td>';					
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="cameraDevice" style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+j+'">';
							tr += '</td>';
							tr += '</tr>';
							nbcams++;
						}
						else if (data.result['res'][j]['gui']['deviceGroup'] == "SMOKE") {
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Détecteur De fumée" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">';
							tr += '</td>';
							tr += '<td>';					
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="climateDevice" style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+j+'">';
							tr += '</td>';
							tr += '</tr>';
							nbclimate++;
						}
						else if (data.result['res'][j]['gui']['deviceGroup'] == "SIREN") {
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Sirène" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">';
							tr += '</td>';
							tr += '<td>';					
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="climateDevice" style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+j+'">';
							tr += '</td>';
							tr += '</tr>';
							nbclimate++;
						}
						else if (data.result['res'][j]['gui']['deviceGroup'] == "VOICEBOX") {
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="VoiceBox" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">';
							tr += '</td>';
							tr += '<td>';					
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="climateDevice" style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+j+'">';
							tr += '</td>';
							tr += '</tr>';
							nbclimate++;
						}
						else if (data.result['res'][j]['gui']['deviceGroup'] == "CONTROLPLUG") {
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="SmartPlug" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">';
							tr += '</td>';
							tr += '<td>';					
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="smartPlugDevice" style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+j+'">';
							tr += '</td>';
							tr += '</tr>';
							nbdevice++;
						}
						else if (data.result['res'][j]['gui']['deviceGroup'] == "KEYPAD") {
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Clavier" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">';
							tr += '</td>';
							tr += '<td>';					
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="keypadDevice" style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+j+'">';
							tr += '</td>';
							tr += '</tr>';
						}
						else if (data.result['res'][j]['gui']['deviceGroup'] == "GATEWAY") {
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="Centrale" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">';
							tr += '</td>';
							tr += '<td>';					
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="centrale" style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+j+'">';
							tr += '</td>';
							tr += '</tr>';
						}
						else { 
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['res'][j]['gui']['deviceGroup']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">';
							tr += '</td>';
							tr += '<td>';					
							tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['res'][j]['gui']['label']+'" style="display : none;" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugType'+j+'">';
							tr += '</td>';
							tr += '</tr>';
						}
						$('#table_smartplug tbody').append(tr);
					}
					var tr = $('#table_smartplug tbody tr:last');
					$('#nbclimate').append(nbclimate);
					$('#nbdoor').append(nbdoor);
					$('#nbcams').append(nbcams);
					$('#nbdevice').append(nbdevice);
				}
				$('#div_alert').showAlert({message: '{{Synchronisation terminée avec succès}}', level: 'success'});
			}
		}
	});
};


$('#bt_Reporting').on('click',function() {
	
	$('#md_modal').dialog({title: "{{Journal d'activité}}"});
	$('#md_modal').load('index.php?v=d&plugin=verisure&modal=report.verisure&eqLogic_id='+ $('.eqLogicAttr[data-l1key=id]').value()).dialog('open');
});


$('#bt_Reset_Token').on('click',function() {
	
	var alarmtype = $('.eqLogicAttr[data-l2key=alarmtype]').value();
	var numinstall = $('.eqLogicAttr[data-l2key=numinstall]').value();
		
	$.ajax({													// fonction permettant de faire de l'ajax
		type: "POST", 											// methode de transmission des données au fichier php
		url: "plugins/verisure/core/ajax/verisure.ajax.php", 	// url du fichier php
		data: {
			action: "Reset_Token",
			alarmtype: alarmtype,
			numinstall: numinstall
			},
		dataType: 'json',
			error: function (request, status, error) {
			handleAjaxError(request, status, error);
			},
		success: function (data) { 		

			if (data.state != 'ok' || data.result == null) {
				$('#div_alert').showAlert({message: '{{Erreur lors de la suppression du token}}', level: 'danger'});
				return;
			}
			else  {
				if ( data.result['res'] == "OK" ) {
					$('#div_alert').showAlert({message: '{{Suppression du token réalisée avec succès}}', level: 'success'});
				}
			}
		}
	});

});