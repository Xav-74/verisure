

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
    
	if (init(_cmd.type) == 'info') {
		var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
		tr += '<td>';
		tr += '<span class="cmdAttr" data-l1key="id" style="display:none;"></span>';
		tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom}}">';
		tr += '</td>';
		tr += '<td>';
		tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
		tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
		tr += '</td>';
		tr += '<td>';
		tr += '<span><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" /> {{Historiser}}<br/></span>';
		tr += '<span><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" /> {{Affichage}}<br/></span>';
		tr += '<span><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label></span> ';
		tr += '</td>';
		tr += '<td>';
		if (is_numeric(_cmd.id)) {
			tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
			tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
		}
		tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>';
		tr += '</td>';
		tr += '</tr>';
		$('#table_cmd tbody').append(tr);
		$('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
		if (isset(_cmd.type)) {
			$('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
		}
		jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
	}
	
	if (init(_cmd.type) == 'action') {
		var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
		tr += '<td>';
		tr += '<span class="cmdAttr" data-l1key="id" style="display:none;"></span>';
		tr += '<input class="cmdAttr form-control input-sm" data-l1key="name" style="width : 140px;" placeholder="{{Nom}}">';
		tr += '</td>';
		tr += '<td>';
		tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
		tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
		tr += '</td>';
		tr += '<td>';
		tr += '<span><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" /> {{Affichage}}<br/></span>';
		tr += '</td>';
		tr += '<td>';
		if (is_numeric(_cmd.id)) {
			tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fa fa-cogs"></i></a> ';
			tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
		}
		tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>';
		tr += '</td>';
		tr += '</tr>';
		$('#table_cmd tbody').append(tr);
		$('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
		if (isset(_cmd.type)) {
			$('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
		}
		jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));
	}	
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


$('#bt_SynchronizeMyInstallation').on('click',function() {
 
	$('#table_smartplug tbody').empty();
	$('#nbsp').empty();
  
	$('#div_alert').showAlert({message: '{{Synchronisation en cours}}', level: 'warning'});	
	$.ajax({													// fonction permettant de faire de l'ajax
		type: "POST", 											// methode de transmission des données au fichier php
		url: "plugins/verisure/core/ajax/verisure.ajax.php", 	// url du fichier php
		data: {
			action: "SynchronizeMyInstallation",
			numinstall: $('.eqLogicAttr[data-l2key=numinstall]').value(),
			username: $('.eqLogicAttr[data-l2key=username]').value(),
			pwd: $('.eqLogicAttr[data-l2key=password]').value(),
			country: $('.eqLogicAttr[data-l2key=country]').value()
			},
		dataType: 'json',
			error: function (request, status, error) {
			handleAjaxError(request, status, error);
			},
		success: function (data) { 															
			
			if (data.state != 'ok') {
				$('#div_alert').showAlert({message: '{{Erreur lors de la synchronisation}}', level: 'danger'});
				return;
			}
			else  {
				var nbsp = data.result['Devices'].length;
				$('#nbsp').append(nbsp); 
				for(j = 0; j < nbsp ; j++) {
					var tr = '<tr>';
					tr += '<td>';
					tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['Devices'][j]['idDev']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugID'+j+'">';
					tr += '</td>';
					tr += '<td>';
					tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['Devices'][j]['alias']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugName'+j+'">';
					tr += '</td>';
					tr += '<td>';
					tr += '<input type="text" class="eqLogicAttr form-control input-sm" value="'+data.result['Devices'][j]['aliasType']+'" readonly="true" data-l1key="configuration" data-l2key="devices" data-l3key="smartplugModel'+j+'">';
					tr += '</td>';
					tr += '</tr>';
					$('#table_smartplug tbody').append(tr);
				}
				var tr = $('#table_smartplug tbody tr:last');
			}
			$('#div_alert').showAlert({message: '{{Synchronisation terminée avec succès}}', level: 'success'});
		}
	});
});


$('#bt_Reporting').on('click',function() {
	
	$('#md_modal').dialog({title: "{{Journal d'activité}}"});
	$('#md_modal').load('index.php?v=d&plugin=verisure&modal=report.verisure&numinstall=' + $('.eqLogicAttr[data-l2key=numinstall]').value() + '&username=' + $('.eqLogicAttr[data-l2key=username]').value()+ '&pwd=' + encodeURIComponent($('.eqLogicAttr[data-l2key=password]').value()) + '&country=' + $('.eqLogicAttr[data-l2key=country]').value()).dialog('open');
});