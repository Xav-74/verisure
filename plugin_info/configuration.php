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
				
		<legend><i class="fas fa-exclamation-triangle"></i> {{ Informations importantes}}</legend>
        <div class="form-group">
           	<div class="col-sm-8" style="margin-left: 50px;">
                Ce plugin utilise les API de Verisure Europe (Securitas Direct) pour obtenir les informations de votre alarme.<br/>
				NOTE : CE PLUGIN N'EST EN AUCUN CAS ASSOCIÉ OU LIÉ AUX SOCIÉTÉS DU GROUPE SECURITAS DIRECT - VERISURE.<br/>
				L'usage de ce plugin est destiné à des fins strictement personnelles et privées.<br/>
				Par conséquent, le développeur n'approuve ni ne tolère aucune utilisation inappropriée, et n'assume aucune responsabilité légale pour la fonctionnalité ou la sécurité de vos alarmes et appareils.<br/> <br/>
			</div>
        </div>

        <legend><i class="fas fa-wrench"></i> {{ Paramètre d'auto-actualisation via historique (Alarme type 3 uniquement)}}</legend>
        <div class="form-group pull_class">
            <label class="col-sm-2 control-label" >{{Cron personnalisé}}
                <sup><i class="fas fa-question-circle tooltips" title="{{Fréquence de rafraîchissement du statut de l'alarme via l'historique Verisure}}"></i></sup>
            </label>
            <div class="col-sm-3">
                <div class="input-group">
                    <input id="cronPattern" class="form-control configKey" data-l1key="cronPattern" placeholder="*/5 * * * *"/>
                    <span class="input-group-btn">
                        <a class="btn btn-primary jeeHelper" data-helper="cron" style="width:32px;" title="{{Assistant cron}}"><i class="fas fa-question-circle"></i></a>
                    </span>
                </div>
                <a id="bt_enable" class="btn btn-success" style="width:32px;" title="{{Activation cron}}"><i class="fas fa-play"></i></a>
                <a id="bt_disable" class="btn btn-danger" style="width:32px;" title="{{Désactivation cron}}"><i class="fas fa-stop"></i></a>
            </div>
        </div>
        <br/>
		
    </fieldset>
	
</form>

<script>
    
    var CommunityButton = document.querySelector('#createCommunityPost > span');
    if(CommunityButton) {CommunityButton.innerHTML = "{{Community}}";}

    /* Fonction permettant l'activation du cron */
    document.getElementById('bt_enable').addEventListener('click', function() {
        document.getElementById('bt_savePluginConfig').click();
        enableCron();
    });

    /* Fonction permettant la désactivation du cron */
    document.getElementById('bt_disable').addEventListener('click', function() {
        document.getElementById('bt_savePluginConfig').click();
        disableCron();
    });

    function enableCron()  {
        
        if ( document.getElementById('cronPattern').value == '' ) { var cronPattern = "*/5 * * * *"; }
        else { var cronPattern = document.getElementById('cronPattern').value; }
        const cronRegex = /(^((\*\/)?([0-5]?[0-9])((\,|\-|\/)([0-5]?[0-9]))*|\*) ((\*\/)?((2[0-3]|1[0-9]|[0-9]|00))((\,|\-|\/)(2[0-3]|1[0-9]|[0-9]|00))*|\*) ((\*\/)?([1-9]|[12][0-9]|3[01])((\,|\-|\/)([1-9]|[12][0-9]|3[01]))*|\*) ((\*\/)?([1-9]|1[0-2])((\,|\-|\/)([1-9]|1[0-2]))*|\*|(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|des)) ((\*\/)?[0-6]((\,|\-|\/)[0-6])*|\*|00|(sun|mon|tue|wed|thu|fri|sat))\s*$)|@(annually|yearly|monthly|weekly|daily|hourly|reboot)/; 
        
        if ( cronRegex.test(cronPattern) == true ) {
            $.ajax({
                type: "POST",
                url: "plugins/verisure/core/ajax/verisure.ajax.php",
                data: {
                    action: "enableCron",
                    cronPattern: cronPattern,
                },
                dataType: 'json',
                    error: function (request, status, error) {
                    handleAjaxError(request, status, error);
                },
                success: function (data) { 			

                    if (data.state != 'ok') {
                        $('#div_alert').showAlert({message: '{{Erreur lors de l\'activation du cron}}'+' ('+cronPattern+')', level: 'danger'});
                        return;
                    }
                    else  {
                        $('#div_alert').showAlert({message: '{{Activation du cron réalisée avec succès}}'+' ('+cronPattern+')', level: 'success'});
                    }
                }
            });
        }
        else { $('#div_alert').showAlert({message: '{{Expression cron erronée}}', level: 'danger'}); }
    };

    function disableCron()  {
        
        $.ajax({
            type: "POST",
            url: "plugins/verisure/core/ajax/verisure.ajax.php",
            data: {
                action: "disableCron",
            },
            dataType: 'json',
                error: function (request, status, error) {
                handleAjaxError(request, status, error);
            },
            success: function (data) { 			

                if (data.state != 'ok') {
                    $('#div_alert').showAlert({message: '{{Erreur lors de la désactivation du cron}}', level: 'danger'});
                    return;
                }
                else  {
                    $('#div_alert').showAlert({message: '{{Désactivation du cron réalisée avec succès}}', level: 'success'});
                }
            }
        });
    };

</script>
