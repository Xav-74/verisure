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
		<legend><i class="fas fa-cog"></i>  {{Gestion}}</legend>
		<div class="eqLogicThumbnailContainer">
			
			<div class="cursor eqLogicAction logoPrimary" style="color:#FB0230;" data-action="add">
				<i class="fas fa-plus-circle"></i>
				<br/>
				<span>{{Ajouter}}</span>
			</div>
			
			<div class="cursor eqLogicAction logoSecondary" style="color:#FB0230;" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br/>
				<span>{{Configuration}}</span>
			</div>
		
		</div>
		
		<legend><i class="fas fa-table"></i> {{Mes équipements}}</legend>
		<input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
		<div class="eqLogicThumbnailContainer">
			<?php
			foreach ($eqLogics as $eqLogic)	{
				$opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
				echo '<div class="eqLogicDisplayCard cursor '.$opacity.'" data-eqLogic_id="' . $eqLogic->getId() . '">';
				echo '<img src="' . $plugin->getPathImgIcon() . '"/>';
				echo '<br/>';
				echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
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
												$decay = $object->getConfiguration('parentNumber');
												$options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $decay) . $object->getName() . '</option>';
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
							
								<br/><br/>  
                        
 							</fieldset>
						</form>
					</div>

					
					<div class="col-sm-3">
						<form class="form-horizontal">
							<fieldset>	
                        
								<div class="form-group">
									<label class="col-sm-3 control-label">{{}}</label>
									<div id="div_img" class="col-sm-3">
									</div>
								</div>
							
							</fieldset>
						</form>  
                    </div>
				</div>	
					 
                    
				<form class="form-horizontal">
					<fieldset>    
						
						<div class="form-group">		
							<label class="col-sm-3 control-label">{{Type d'alarme}}</label>
							<div class="col-sm-3">
								<select id="sel_alarmtype" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="alarmtype">
									<option value="" disabled selected hidden>{{Choisir dans la liste}}</option>
									<option value="1">{{Alarme type 1}}</option>
									<option value="2">{{Alarme type 2}}</option>
								</select>
							</div>
						</div>   
						
						<div id="div_numinstall" class="form-group">
							<label class="col-sm-3 control-label">{{Numéro d'installation}}</label>
							<div class="col-sm-3">
								<input type="number" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="numinstall" placeholder="12345678"/>
							</div>
						</div>
							
						<div id="div_user" class="form-group">						
							<label class="col-sm-3 control-label">{{Identifiant}}</label>
							<div class="col-sm-3">
								<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="username" placeholder="Identifiant utilisé pour vous connecter à votre compte Verisure"/>
							</div>
						</div>	
							
						<div id="div_pwd" class="form-group">		
							<label class="col-sm-3 control-label">{{Mot de passe}}</label>
							<div class="col-sm-3">
								<input type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="password" placeholder="Mot de passe utilisé pour vous connecter à votre compte Verisure"/>
							</div>
						</div>
						
						<div id="div_code" class="form-group">		
							<label class="col-sm-3 control-label">{{Code Alarme}}</label>
							<div class="col-sm-3">
								<input type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="code" placeholder="Code à 4 ou 6 digits de votre alarme"/>
							</div>
						</div>
						
						<div id="div_country" class="form-group">		
							<label class="col-sm-3 control-label">{{Pays}}</label>
							<div class="col-sm-3">
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
									
						<br/><br/>
										
						<div class="form-group">		
							<label class="col-sm-3 control-label">{{Informations}}</label>
							<div class="col-sm-9">
								Ce plugin est compatible avec 2 générations de matériels Verisure. Sélectionnez le type d'alarme correspondant à vos matériels (voir image associée).<br/>
                                Les informations de connexion demandées sont celles utilisées pour vous connecter sur le portail web Verisure ou via l'application mobile My Verisure.<br/>
								Le plugin ne vous demandera jamais les mots de passe utilisés pour vous identifier auprès du personnel Securitas Direct lors du déclenchement de votre alarme.<br/>
							</div>
						</div>
										
						<br/><br/>
								
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Mon installation}}</label>
							<div class="col-sm-6">
								<a class="btn btn-danger btn-sm cmdAction" id="bt_SynchronizeMyInstallation"><i class="fas fa-sync"></i> {{Synchroniser}}</a>
								<a class="btn btn-info btn-sm cmdAction" id="bt_Reporting"><i class="fas fa-info"></i> {{Journal d'activité}}</a>
								<br/><br/>
								<span id="nbsp" type="text" class="eqLogicAttr" data-l1key="configuration" data-l2key="nb_smartplug" style="display : none;"></span>
								<span id="nbclimate" type="text" class="eqLogicAttr" data-l1key="configuration" data-l2key="nb_climate" style="display : none;"></span>
								<span id="nbdoor" type="text" class="eqLogicAttr" data-l1key="configuration" data-l2key="nb_doorsensor" style="display : none;"></span>
								<span id="nbcams" type="text" class="eqLogicAttr" data-l1key="configuration" data-l2key="nb_camera" style="display : none;"></span>
								<table id="table_smartplug" class="table table-bordered table-condensed">
									<thead>
										<tr>
											<th style="width: 15%;">{{ID SmartPlug}}</th>
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
					var img ='<img src="plugins/verisure/core/img/alarm_verisure.png" height="130" width="295" />';
					$('#div_img').append(img);
					
					/*$('#sel_country').empty();
					var country = '<option value="" disabled selected hidden>{{Choisir dans la liste}}</option>';
					country += '<option value="1">FR</option>';
					country += '<option value="2">ES</option>';
					country += '<option value="3">GB</option>';
					country += '<option value="4">IT</option>';
					country += '<option value="5">PT</option>';
					$('#sel_country').append(country);*/
										
					$('#div_numinstall').show();
					$('#div_user').show();
					$('#div_pwd').show();
					$('#div_code').hide();
					$('#div_country').show();
				}

				if ($('.eqLogicAttr[data-l2key=alarmtype]').value() == "2") {
					$('#div_img').empty();
					var img ='<img src="plugins/verisure/core/img/alarm_verisure_2.png" height="130" width="295" />';
					$('#div_img').append(img);
					
					/*$('#sel_country').empty();
					var country = '<option value="" disabled selected hidden>{{Choisir dans la liste}}</option>';
					country += '<option value="1">FR</option>';
					country += '<option value="2">BE (fr)</option>';
					country += '<option value="3">BE (nl)</option>';
					country += '<option value="4">NL</option>';
					country += '<option value="5">UK</option>';
					country += '<option value="6">DK</option>';
					country += '<option value="7">FI</option>';
					country += '<option value="8">NO</option>';
					country += '<option value="9">SE</option>';
					country += '<option value="10">DE</option>';
					$('#sel_country').append(country);*/
					
					$('#div_numinstall').hide();
					$('#div_user').show();
					$('#div_pwd').show();
					$('#div_code').show();
					$('#div_country').hide();
				}
				
			});
			
			</script>			
			
			
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
							<li>les notifications SMS pour la détection d'intrusion (dev en cours)</li>
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
						<br/><br/>
						<img src="plugins/verisure/core/img/scenario_1.png" height="368" width="1100"/><br/>
						<br/>
						<i>Dans cet exemple, [Maison][Mail Domotique] représente l'équipement créé dans le plugin Mail Listener.</i><br/>
						<br/>
						Passez maintenant à l'onglet "Scénario" :<br/>
						<br/><br/>
						<img src="plugins/verisure/core/img/scenario_2.png" height="123" width="1100"/><br/>
						<br/>
						<i>Dans cet exemple, [Maison][Alarme Verisure] représente l'équipement créé dans le plugin Verisure.</i><br/>
						<br/>
						N'oubliez pas de sauvegarder !<br/>
						Voilà, maintenant, chaque mail provenant de l'adresse "serviceclient@securitasdirect.fr" déclenchera automatiquement un refresh du statut de l'alarme.<br/>
						<br/><br/>
						<label class="control-label">{{2. Notifications SMS}}</label>
						<br/><br/>
						Dévelopement en cours...<br/>
						<br/>
					</div>
				</div>	
			</div>
			
			<div role="tabpanel" class="tab-pane" id="commandtab">
				<!--<a class="btn btn-success btn-sm cmdAction pull-right" data-action="add" style="margin-top:5px;"><i class="fa fa-plus-circle"></i> {{Commandes}}</a><br/><br/>-->
				<table id="table_cmd" class="table table-bordered table-condensed">
					<thead>
						<tr>
							<th>{{Nom}}</th><th>{{Type}}</th><th>{{Action}}</th>
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