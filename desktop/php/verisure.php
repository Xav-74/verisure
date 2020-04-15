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
				<br>
				<span>{{Ajouter}}</span>
			</div>
			
			<div class="cursor eqLogicAction logoSecondary" style="color:#FB0230;" data-action="gotoPluginConf">
				<i class="fas fa-wrench"></i>
				<br>
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
				echo '<br>';
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
											foreach (jeeObject::all() as $object) {
												echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
											}
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
							
								<br /><br />  
                        
 							</fieldset>
						</form>
					</div>

					
					<div class="col-sm-3">
						<form class="form-horizontal">
							<fieldset>	
                        
								<div class="form-group">
									<label class="col-sm-3 control-label">{{}}</label>
									<div class="col-sm-3">
										<img src="plugins/verisure/core/img/alarm_verisure.png" height="130" width="295" />
									</div>
								</div>
							
							</fieldset>
						</form>  
                    </div>
				</div>	
					 
                    
				<form class="form-horizontal">
					<fieldset>    
   
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Numéro d'installation}}</label>
							<div class="col-sm-3">
								<input type="number" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="numinstall" placeholder="12345678"/>
							</div>
						</div>
							
						<div class="form-group">						
							<label class="col-sm-3 control-label">{{Identifiant}}</label>
							<div class="col-sm-3">
								<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="username" placeholder="Identifiant utilisé pour vous connecter à votre compte Verisure"/>
							</div>
						</div>	
							
						<div class="form-group">		
							<label class="col-sm-3 control-label">{{Mot de passe}}</label>
							<div class="col-sm-3">
								<input type="password" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="password" placeholder="Mot de passe utilisé pour vous connecter à votre compte Verisure"/>
							</div>
						</div>
						
						<div class="form-group">		
							<label class="col-sm-3 control-label">{{Pays}}</label>
							<div class="col-sm-3">
								<select id="sel_country" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="country">
									<option value="" disabled selected hidden>{{Choisir dans la liste}}</option>
									<option value="1">{{FR}}</option>
									<option value="2">{{ES}}</option>
									<option value="3">{{GB}}</option>
									<option value="4">{{IT}}</option>
									<option value="5">{{PT}}</option>
								</select>
							</div>
						</div>   
									
						<br /><br />
										
						<div class="form-group">		
							<label class="col-sm-3 control-label">{{Informations}}</label>
							<div class="col-sm-9">
								Ce plugin est compatible avec le matériel Verisure affiché sur l'image ci-dessus.<br />
                                Les informations de connexion demandées sont celles utilisées pour vous connecter sur "https://customers.securitasdirect.fr" ou via l'application mobile My Verisure.<br />
								Le plugin ne vous demandera jamais les mots de passe utilisés pour vous identifier auprès du personnel Securitas Direct lors du déclenchement de votre alarme.<br />
							</div>
						</div>
										
						<br /><br />
								
						<div class="form-group">
							<label class="col-sm-3 control-label">{{Mon installation}}</label>
							<div class="col-sm-6">
								<a class="btn btn-danger btn-sm cmdAction" id="bt_SynchronizeMyInstallation"><i class="fas fa-sync"></i> {{Synchroniser}}</a><br/><br/>
								<span id="nbsp" type="text" class="eqLogicAttr" data-l1key="configuration" data-l2key="nb_smartplug" style="display : none;"></span>
								<table id="table_smartplug" class="table table-bordered table-condensed">
									<thead>
										<tr>
											<th style="width: 20px;">{{ID SmartPlug}}</th>
											<th style="width: 100px;">{{Nom}}</th>
											<th style="width: 200px;">{{Type}}</th>
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