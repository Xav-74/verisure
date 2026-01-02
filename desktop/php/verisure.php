<?php

if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('verisure');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());

?>


<div class="row row-overflow">

	<div class="col-xs-12 eqLogicThumbnailDisplay">

		<div class="row">
			
			<div class="col-xs-12">
				<legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
				<div class="eqLogicThumbnailContainer">
					
					<div class="cursor eqLogicAction logoPrimary" style="color:#FB0230" data-action="add">
						<i class="fas fa-plus-circle"></i>
						<br/>
						<span>{{Ajouter}}</span>
					</div>
					
					<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
						<i class="fas fa-wrench"></i>
						<br/>
						<span>{{Configuration}}</span>
					</div>

					<!--Bouton Community-->
					<?php
						// uniquement si on est en version 4.4 ou supérieur
						$jeedomVersion  = jeedom::version() ?? '0';
						$displayInfoValue = version_compare($jeedomVersion, '4.4.0', '>=');
						if ($displayInfoValue) {
							echo '<div class="cursor eqLogicAction warning" data-action="createCommunityPost" title="{{Ouvrir une demande d\'aide sur le forum communautaire}}">';
							echo '<i class="fas fa-ambulance"></i>';
							echo '<span>{{Community}}</span>';
							echo '</div>';
						}
					?>
				 
				</div>
			</div>

		</div>

		
		<legend><i class="fas fa-table"></i> {{Mes équipements}}</legend>
		<div class="input-group" style="margin-bottom:5px;">
			<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic"/>
			<div class="input-group-btn" style="margin-bottom:5px;">
				<a id="bt_resetObjectSearch" class="btn" style="width:30px"><i class="fas fa-times"></i></a>
				<a class="btn roundedRight hidden" id="bt_pluginDisplayAsTable" data-coreSupport="1" data-state="0"><i class="fas fa-grip-lines"></i></a>
			</div>
		</div>	
		<div class="eqLogicThumbnailContainer">
			<?php
				foreach ($eqLogics as $eqLogic)	{
					$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
					echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
					if ( $eqLogic->getConfiguration('alarmtype') == 1 ) { echo '<img id="img_eq" src="/plugins/verisure/core/img/alarm_verisure.png" style="transform:scale(80%); left:7px !important" />'; }
					else if ( $eqLogic->getConfiguration('alarmtype') == 2 ) { echo '<img id="img_eq" src="/plugins/verisure/core/img/alarm_verisure_2.png" style="transform:scale(60%); left:0px !important" />'; }
					else if ( $eqLogic->getConfiguration('alarmtype') == 3 ) { echo '<img id="img_eq" src="/plugins/verisure/core/img/alarm_verisure_3.png" style="transform:scale(80%); left:0px !important" />'; }
					else { echo '<img id="img_eq" src="' . $plugin->getPathImgIcon() . '" style="left:20px !important" />'; }
					echo '<br/>';
					echo '<div class="name" style="line-height:20px !important">' . $eqLogic->getHumanName(true, true) . '</div>';
					echo '</div>';
				}
			?>
		</div>
	</div>


	<div class="col-xs-12 eqLogic" style="display: none;">
		<div class="input-group pull-right" style="display:inline-flex">
			<span class="input-group-btn">
				<a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fa fa-cogs"></i> {{Configuration avancée}}</a><a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}</a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a><a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
			</span>
		</div>
		<ul class="nav nav-tabs" role="tablist">
			<li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
			<li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
			<li role="presentation"><a href="#notificationsVerisure" aria-controls="notificationsVerisure" role="tab" data-toggle="tab"><i class="fas fa-envelope"></i></i> {{Notifications Verisure}}</a></li>
			<li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fa fa-list-alt"></i> {{Commandes}}</a></li>
		</ul>
		<div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
			<div role="tabpanel" class="tab-pane active" id="eqlogictab">
				<br/>
                           			
				<div class="row">
				
					<div class="col-sm-6">  
						<form class="form-horizontal">
							<fieldset>
						 		
								<div class="form-group">
									<label class="col-sm-6 control-label">{{Nom de l'équipement}}</label>
									<div class="col-sm-6">
										<input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
										<input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}"/>
									</div>
								</div>
							
								<div class="form-group">
									<label class="col-sm-6 control-label" >{{Objet parent}}</label>
									<div class="col-sm-6">
										<select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
											<option value="">{{Aucun}}</option>
											<?php
											$options = '';
											foreach ((jeeObject::buildTree(null, false)) as $object) {
												$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
											}
											echo $options;
											?>
										</select>
									</div>
								</div>
									
								<div class="form-group">
									<label class="col-sm-6 control-label">{{Catégorie}}</label>
									<div class="col-sm-6">
										<?php
										foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
										echo '<label class="checkbox-inline">';
										echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
										echo '</label>';
										}
										?>
									</div>
								</div>
                        
								<div class="form-group">
								<label class="col-sm-6 control-label"></label>
									<div class="col-sm-6">
										<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
										<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
									</div>
								</div>
							                       
 							</fieldset>
						</form>
					</div>
				</div>
				
				<br/><br/> 
				
				<div class="row">
					<div class="col-sm-6">      
						<form class="form-horizontal">
							<fieldset>    
								
								<div class="form-group">		
									<label class="col-sm-6 control-label">{{Type d'alarme}}</label>
									<div class="col-sm-6">
										<select id="sel_alarmtype" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="alarmtype">
											<option value="" disabled selected hidden>{{Choisir dans la liste}}</option>
											<option value="1">{{Alarme type 1}}</option>
											<option value="2">{{Alarme type 2}}</option>
											<option value="3">{{Alarme type 3}}</option>
										</select>
									</div>
								</div>   
								
								<div id="div_numinstall" class="form-group">
									<label class="col-sm-6 control-label help" data-help="{{Attention ! Ce numéro doit être rigoureusement identique à celui affiché sur votre application My Verisure. Si votre numéro d'installation commence par un 0 mais que celui-ci n'est pas présent dans l'application, supprimez-le !}}">{{Numéro d'installation}}</label>
									<div class="col-sm-6">
										<input type="number" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="numinstall" placeholder="12345678"/>
									</div>
								</div>
									
								<div id="div_user" class="form-group">						
									<label class="col-sm-6 control-label">{{Identifiant}}</label>
									<div class="col-sm-6">
										<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="username" placeholder="Identifiant utilisé pour vous connecter à votre compte Verisure" style="margin-bottom:0px !important"/>
									</div>
								</div>	
									
								<div id="div_pwd" class="form-group">		
									<label class="col-sm-6 control-label">{{Mot de passe}}</label>
									<div class="col-sm-6 pass_show">
										<input type="password" id="pwd" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="password" placeholder="Mot de passe utilisé pour vous connecter à votre compte Verisure" style="margin-bottom:0px !important"/>
										<span class="eye fa fa-fw fa-eye toggle-pwd"></span>
									</div>
								</div>
								
								<div id="div_code" class="form-group">		
									<label class="col-sm-6 control-label">{{Code Alarme}}</label>
									<div class="col-sm-6 pass_show">
										<input type="password" id="code" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="code" placeholder="Code à 4 ou 6 digits de votre alarme" style="margin-bottom:0px !important"/>
										<span class="eye fa fa-fw fa-eye toggle-code"></span>
									</div>
								</div>
								
								<div id="div_country" class="form-group">		
									<label class="col-sm-6 control-label">{{Pays}}</label>
									<div class="col-sm-6">
										<select id="sel_country" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="country">
											<option value="" disabled selected hidden>{{Choisir dans la liste}}</option>
											<option value="1">FR</option>';
											<option value="2">ES</option>';
											<option value="3">GB</option>';
											<option value="4">IT</option>';
											<option value="5">PT</option>';
										</select>
									</div>
								</div>
								
								<div id="div_option" class="form-group">						
									<label class="col-sm-6 control-label">{{Options}}</label>
									<div class="col-sm-6">
										<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="externalAlarm"/>{{Présence alarme extérieure}}</label>
										<br/>
										<label class="checkbox-inline help" data-help="{{Attention ! Cette option permet de forcer l'activation de l'alarme même si une porte ou une fenêtre est restée ouverte. A vos risques et périls !}}"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="allowForcing"/>{{Armement forcé}}</label>
										<br/>
										<label class="checkbox-inline help" data-help="{{Pensez à paramétrer et activer le cron personnalisé dans la configuration du plugin}}"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="refreshHisto"/>{{Raffraichissment via historique}}</label>
									</div>
								</div>	
							</fieldset>
						</form>  
                    </div>								
									
					<div class="col-sm-6">      
						<form class="form-horizontal">
							<fieldset> 
                      
								<div class="form-group">
									<div id="div_img" class="col-sm-9" style="text-align: center;">
									</div>
								</div>
							
							</fieldset>
						</form>  
                    </div>
				</div>

				<br/><br/>
										
				<div class="row">
					<div class="col-sm-12">      
						<form class="form-horizontal">
							<fieldset>

								<div class="form-group">		
									<label class="col-sm-3 control-label" style="margin-left:-10px;">{{Informations}}</label>
									<div class="col-sm-9">
										Ce plugin est compatible avec 3 générations de matériels Verisure. Sélectionnez le type d'alarme correspondant à vos matériels (voir image associée).<br/>
										Les informations de connexion demandées sont celles utilisées pour vous connecter sur le portail web Verisure ou via l'application mobile My Verisure.<br/>
										<b>Le plugin ne vous demandera jamais les mots de passe utilisés pour vous identifier auprès du personnel Securitas Direct lors du déclenchement de votre alarme.</b><br/>
									</div>
								</div>

							</fieldset>
						</form>  
                    </div>
				</div>										
						
				<br/><br/>
				
				<div class="row">
					<div class="col-sm-12">      
						<form class="form-horizontal">
							<fieldset>
				
								<div class="form-group">
									<label class="col-sm-3 control-label help" data-help="{{Attention, la suppression du token nécessitera obligatoirement une nouvelle authentification 2FA !}}" style="margin-left:-10px;">{{Mon installation}}</label>
									<div class="col-sm-6">
										<a class="btn btn-default btn-sm cmdAction" id="bt_Authentication_2FA"><i class="fas fa-sync"></i> {{Authentification}}</a>
										<a class="btn btn-danger btn-sm cmdAction" id="bt_Reset_Token"><i class="far fa-trash-alt"></i> {{Suppression Token}}</a>
										<a class="btn btn-info btn-sm cmdAction" id="bt_Reporting"><i class="fas fa-info"></i> {{Journal d'activité}}</a>
										<br/><br/>
										<span id="nbsp" type="text" class="eqLogicAttr" data-l1key="configuration" data-l2key="nb_smartplug" style="display : none;"></span>
										<span id="nbclimate" type="text" class="eqLogicAttr" data-l1key="configuration" data-l2key="nb_climate" style="display : none;"></span>
										<span id="nbdoor" type="text" class="eqLogicAttr" data-l1key="configuration" data-l2key="nb_doorsensor" style="display : none;"></span>
										<span id="nbcams" type="text" class="eqLogicAttr" data-l1key="configuration" data-l2key="nb_camera" style="display : none;"></span>
										<span id="nbdevice" type="text" class="eqLogicAttr" data-l1key="configuration" data-l2key="nb_device" style="display : none;"></span>
										<table id="table_smartplug" class="table table-bordered table-condensed">
											<thead>
												<tr>
													<th style="width: 15%;">{{ID}}</th>
													<th style="width: 42.5%;">{{Nom}}</th>
													<th style="width: 42.5%;">{{Type}}</th>
												</tr>
											</thead>
											<tbody>
											</tbody>
										</table>
									</div>
								</div>
							
							</fieldset>
						</form>
					</div>
				</div>
			
			</div>
						
			<script>
			
			if ($('.eqLogicAttr[data-l2key=alarmtype]').value() != "1" && $('.eqLogicAttr[data-l2key=alarmtype]').value() != "2") {
				$('#div_numinstall').hide();
				$('#div_user').hide();
				$('#div_pwd').hide();
				$('#div_code').hide();
				$('#div_country').hide();
			}
			
			$('#sel_alarmtype').on("change",function (){
				
				if ($('.eqLogicAttr[data-l2key=alarmtype]').value() == "1") {
					$('#div_img').empty();
					var img ='<img src="plugins/verisure/core/img/alarm_verisure.png" height="170" />';
					$('#div_img').append(img);
					$('#div_numinstall').show();
					$('#div_user').show();
					$('#div_pwd').show();
					$('#div_code').hide();
					$('#div_country').show();
					$('#div_option').hide();
				}

				if ($('.eqLogicAttr[data-l2key=alarmtype]').value() == "2") {
					$('#div_img').empty();
					var img ='<img src="plugins/verisure/core/img/alarm_verisure_2.png" height="130" />';
					$('#div_img').append(img);
					$('#div_numinstall').hide();
					$('#div_user').show();
					$('#div_pwd').show();
					$('#div_code').show();
					$('#div_country').hide();
					$('#div_option').hide();
				}

				if ($('.eqLogicAttr[data-l2key=alarmtype]').value() == "3") {
					$('#div_img').empty();
					var img ='<img src="plugins/verisure/core/img/alarm_verisure_3.png" height="170" />';
					$('#div_img').append(img);
					$('#div_numinstall').show();
					$('#div_user').show();
					$('#div_pwd').show();
					$('#div_code').hide();
					$('#div_country').show();
					$('#div_option').show();
				}
			});

			$('body').off('click', '.toggle-pwd').on('click', '.toggle-pwd', function () {
				$(this).toggleClass("fa-eye fa-eye-slash");
				var input = $("#pwd");
				if (input.attr("type") === "password") {
				input.attr("type", "text");
				} else {
				input.attr("type", "password");
				}
			});

			$('body').off('click', '.toggle-code').on('click', '.toggle-code', function () {
				$(this).toggleClass("fa-eye fa-eye-slash");
				var input = $("#code");
				if (input.attr("type") === "password") {
				input.attr("type", "text");
				} else {
				input.attr("type", "password");
				}
			});
			
			</script>
			
			<style>
				
				.pass_show {
					position: relative
				}

				.pass_show .eye {
					position: absolute;
					top: 60% !important;
					right: 20px;
					z-index: 1;
					margin-top: -10px;
					cursor: pointer;
					transition: .3s ease all;
				}

			</style>
			
			
			<div role="tabpanel" class="tab-pane" id="notificationsVerisure">
				<div class="container">
					<br/>
                    <h4>Gestion des notifications</h4>
					<br/>
					<div class="form-group">
						Les API Verisure ne permettent pas les remontées d'informations et notifications automatiques directes, telles que l'activation/désactivation depuis un badge ou une télécommande ou encore le déclenchement de l'alarme.
						<br/><br/>
						Pour palier à cela, il est nécessaire de récupérer les informations depuis :<br/>
						<ul>
							<li>les notifications Mail pour l'activation/désactivation de l'alarme</li>
							<li>les notifications SMS pour l'activation/désactivation de l'alarme</li>
						</ul>
						<br/>
						<label class="control-label">{{1. Notifications Mail}}</label>
						<br/><br/>
						Actuellement seul le plugin suivant a été testé et est officiellement supporté pour recevoir de façon automatisée les alertes :<br/>
						<ul>
							<li><a href="https://market.jeedom.com/index.php?v=d&p=market&type=plugin&&name=maillistener">Plugin  Mail Listener de Lunarok </a></li>
						</ul>
						<i>Pour supporter d'autres plugins n'hésitez pas à contacter le développeur en ouvrant <a href="https://github.com/Xav-74/verisure/issues/new"> une "demande d'évolution" sur le Github du plugin</a></i>
						<br/><br/>
						Pour mettre en place cette fonctionnalité, assurez-vous que les notifications mails sont bien activées sur votre compte Verisure et que l'option "Contrôle d'accès" est bien validée pour l'ensemble de vos badges et télécommandes !<br/>
						Installez ensuite le plugin Mail Listener, puis configurez le avec les paramètres de votre compte mail qui reçoit les notifications Verisure. Reportez-vous à la documentation du plugin pour de plus amples informations.<br/>
						Dernière étape : la création du scénario qui déclenchera le refresh du statut de l'alarme lors de la réception d'un email provenant de Securitas Direct - Verisure.<br/>
						Pour cela, renddez-vous dans le menu "Outils" de Jeedom puis "Scénarios" et enfin "Ajouter". Renseignez le premier onglet "Général" comme suit :<br/>
						<br/>
						<img src="plugins/verisure/core/img/scenario_1.png" height="368" width="1100"/><br/>
						<br/>
						<i>Dans cet exemple, [Maison][Mail Domotique] représente l'équipement créé dans le plugin Mail Listener.</i><br/>
						<br/>
						Passez maintenant à l'onglet "Scénario" :<br/>
						<br/>
						<img src="plugins/verisure/core/img/scenario_2.png" height="123" width="1100"/><br/>
						<br/>
						<i>Dans cet exemple, [Maison][Alarme Verisure] représente l'équipement créé dans le plugin Verisure.</i><br/>
						<br/>
						N'oubliez pas de sauvegarder !<br/>
						Voilà, maintenant, chaque mail provenant de l'adresse "serviceclient@securitasdirect.fr" déclenchera automatiquement un refresh du statut de l'alarme.<br/>
						<br/>
						<label class="control-label">{{2. Notifications SMS}}</label>
						<br/><br/>
						Actuellement seul le plugin suivant a été testé et est officiellement supporté pour recevoir de façon automatisée les alertes :<br/>
						<ul>
							<li><a href="https://market.jeedom.com/index.php?v=d&p=market_display&id=16">Plugin SMS officiel de Jeedom SAS </a></li>
						</ul>
						<i>Pour supporter d'autres plugins n'hésitez pas à contacter le développeur en ouvrant <a href="https://github.com/Xav-74/verisure/issues/new"> une "demande d'évolution" sur le Github du plugin</a></i>
						<br/><br/>
						Pour mettre en place cette fonctionnalité, vous devez disposer d'une clé 3G/4G compatible ainsi que d'un forfait SMS chez un opérateur mobile. Assurez-vous que les notifications SMS sont bien activées sur votre compte Verisure et que l'option "Contrôle d'accès" est bien validée pour l'ensemble de vos badges et télécommandes !<br/>
						Installez ensuite le plugin SMS, puis configurez un nouvel équipement en désactivant les interactions. Ajoutez ensuite un nouveau numéro dans l'onglet commande comme sur cet exemple :<br/>
						<br/>
						<img src="plugins/verisure/core/img/config_plugin_SMS.png" height="225" width="1100"/><br/>
						<br/>
						Reportez-vous à la documentation du plugin pour de plus amples informations.<br/>
						Dernière étape : la création du scénario qui déclenchera le refresh du statut de l'alarme lors de la réception d'un SMS provenant de Securitas Direct - Verisure.<br/>
						Pour cela, renddez-vous dans le menu "Outils" de Jeedom puis "Scénarios" et enfin "Ajouter". Renseignez le premier onglet "Général" comme suit :<br/>
						<br/>
						<img src="plugins/verisure/core/img/scenario_3.png" height="411" width="1100"/><br/>
						<br/>
						<i>Dans cet exemple, [Maison][SMS Free] représente l'équipement créé dans le plugin SMS.</i><br/>
						<br/>
						Passez maintenant à l'onglet "Scénario" :<br/>
						<br/>
						<img src="plugins/verisure/core/img/scenario_4.png" height="122" width="1100"/><br/>
						<br/>
						<i>Dans cet exemple, [Maison][Alarme Verisure] représente l'équipement créé dans le plugin Verisure.</i><br/>
						<br/>
						N'oubliez pas de sauvegarder !<br/>
						Voilà, maintenant, chaque SMS provenant du numéro "VERISURE" déclenchera automatiquement un refresh du statut de l'alarme.<br/>
						<br/><br/>
					</div>
				</div>	
			</div>
			
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<!--<a class="btn btn-success btn-sm cmdAction pull-right" data-action="add" style="margin-top:5px;"><i class="fa fa-plus-circle"></i> {{Commandes}}</a><br/><br/>-->
				<table id="table_cmd" class="table table-bordered table-condensed">
					<thead>
						<tr>
							<th>{{ID}}</th><th>{{Nom}}</th><th>{{Type}}</th><th>{{Logical ID}}</th><th>{{Options}}</th><th>{{Valeur}}</th><th>{{Action}}</th>
						</tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
			
		</div>
	</div>

</div>


<?php include_file('desktop', 'verisure', 'js', 'verisure');?>
<?php include_file('core', 'plugin.template', 'js');?>