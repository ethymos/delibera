<?php

// PHP 5.3 and later:
namespace Delibera\Conf;

class Permission
{

	public function __construct()
	{
		add_action('init', array($this, 'admin_init'));
	}
	
	public function admin_init()
	{
		add_action('delibera_menu_itens', array($this, 'addMenu'));
	}
	
	public function addMenu($base_page)
	{
		add_submenu_page($base_page, __('Delibera Permissões','delibera'),__('Delibera Permissões','delibera'), 'manage_options', 'delibera-perm', array($this, 'confPage'));
	}
	
	public function confPage()
	{
		$this->html();
	}
	
	/**
	 * * Copied from Sendpress **
	 */
	function str_lreplace($search, $replace, $subject)
	{
		$pos = strrpos($subject, $search);
		
		if($pos !== false)
		{
			$subject = substr_replace($subject, $replace, $pos, strlen($search));
		}
		
		return $subject;
	}

	function save()
	{
		// $this->security_check();
		foreach($this->get_editable_roles() as $role => $role_name)
		{
			if($role != 'administrator')
			{
				$sp_view = false;
				
				// $role = str_replace(" ","_", strtolower( $role) );
				
				/*
				 * $pos = strrpos($role, "s2member");
				 *
				 * if($pos !== false){
				 * $role = $this->str_lreplace(" ", "", $role);
				 * }
				 */
				$saverole = get_role($role);
				
				if(false !== get_class($saverole))
				{
					
					if(isset($_POST[$role . "_edit"]))
					{
						$sp_view = true;
						$saverole->add_cap('delibera_email');
					}
					else
					{
						$saverole->remove_cap('delibera_email');
					}
					
					if(isset($_POST[$role . "_send"]))
					{
						$sp_view = true;
						$saverole->add_cap('delibera_email_send');
					}
					else
					{
						$saverole->remove_cap('delibera_email_send');
					}
					
					if(isset($_POST[$role . "_reports"]))
					{
						$sp_view = true;
						$saverole->add_cap('delibera_reports');
					}
					else
					{
						$saverole->remove_cap('delibera_reports');
					}
					
					if(isset($_POST[$role . "_subscribers"]))
					{
						$sp_view = true;
						$saverole->add_cap('delibera_subscribers');
					}
					else
					{
						$saverole->remove_cap('delibera_subscribers');
					}
					
					if(isset($_POST[$role . "_settings"]))
					{
						$sp_view = true;
						$saverole->add_cap('delibera_settings');
					}
					else
					{
						$saverole->remove_cap('delibera_settings');
					}
					
					if(isset($_POST[$role . "_settings_access"]))
					{
						$sp_view = true;
						$saverole->add_cap('delibera_settings_access');
					}
					else
					{
						$saverole->remove_cap('delibera_settings_access');
					}
					
					if(isset($_POST[$role . "_addons"]))
					{
						$sp_view = true;
						$saverole->add_cap('delibera_addons');
					}
					else
					{
						$saverole->remove_cap('delibera_addons');
					}
					if(isset($_POST[$role . "_queue"]))
					{
						$sp_view = true;
						$saverole->add_cap('delibera_queue');
					}
					else
					{
						$saverole->remove_cap('delibera_queue');
					}
					
					if($sp_view == true)
					{
						$saverole->add_cap('delibera_view');
					}
					else
					{
						$saverole->remove_cap('delibera_view');
					}
				}
			}
		}
		
		// SendPress_Admin::redirect('Settings_Access');
	}

	function html()
	{
		?>
		<form method="post" id="post">
			<!--
			<div style="float:right;" >
				<a href="" class="btn btn-large btn-default" ><i class="icon-remove"></i> <?php _e('Cancel','delibera'); ?></a> <a href="#" id="save-update" class="btn btn-primary btn-large"><i class="icon-white icon-ok"></i> <?php _e('Save','delibera'); ?></a>
			</div>
			-->
			<br class="clear"> <br class="clear">
			<table class="table table-bordered table-striped">
				<tr>
					<th><?php _e('Delete Pautas','delibera'); ?></th>
					<th><?php _e('Delete Private Pautas','delibera'); ?></th>
					<th><?php _e('Edit Pauta','delibera'); ?></th>
					<th><?php _e('Edit Pautas','delibera'); ?></th>
					<th><?php _e('Edit Private Pautas','delibera'); ?></th>
					<th><?php _e('Publish Pautas','delibera'); ?></th>
					<th><?php _e('Read Pauta','delibera'); ?></th>
					<th><?php _e('Read Private Pautas','delibera'); ?></th>
					<th><?php _e('Delete Published Pautas','delibera'); ?></th>
					<th><?php _e('Forcar Fim de Prazo','delibera'); ?></th>
					<th><?php _e('Reabrir Pauta','delibera'); ?></th>
					<th><?php _e('Edit Published Pautas','delibera'); ?></th>
					<th><?php _e('Edit Published Pauta','delibera'); ?></th>
					<th><?php _e('Edit Encaminhamento','delibera'); ?></th>
					<th><?php _e('Votar','delibera'); ?></th>
					<th><?php _e('Relatoria','delibera'); ?></th>
					<th><?php _e('Edit Others Pautas','delibera'); ?></th>
					<th><?php _e('Edit Others Pauta','delibera'); ?></th>
					<th><?php _e('Delete Others Pautas','delibera'); ?></th>
					<th><?php _e('Delete Others Pauta','delibera'); ?></th>
				</tr>
			
			
					<?php
				foreach($this->get_editable_roles() as $role => $role_name)
				{
					if($role != 'administrator')
					{
						
						// $role = str_replace(" ","_", strtolower( $role) );
						
						/*
						 * $pos = strrpos($role, "s2member");
						 *
						 * if($pos !== false){
						 * $role = $this->str_lreplace("_", "", $role);
						 * }
						 *
						 * echo $role . "<br>";
						 */
						// $saverole = get_role( $role );
						
						$listrole = get_role(str_replace(" ", "_", strtolower($role)));
						// $role = str_replace(" ","_", strtolower( $role) );
						$checked = '';
						
						echo "<tr>";
						echo "<td>". $role_name . "</td>";
						
						if(false !== get_class($listrole))
						{
							echo "<tr>";
							$checked = '';
							if($listrole->has_cap('delete_pautas'))
							{
								$checked = 'checked';
							}
							echo "<td><input $checked name='" . $role .
									 "_delete_pautas' type='checkbox' >&nbsp;" .
									 /*__('Send', 'delibera') .*/ "</td>";
							$checked = '';
							if($listrole->has_cap('delete_private_pautas'))
							{
								$checked = 'checked';
							}
							echo "<td><input $checked name='" . $role .
									 "_delete_private_pautas' type='checkbox' >&nbsp;" .
									 /*__('Send', 'delibera') .*/ "</td>";
							$checked = '';
							if($listrole->has_cap('edit_pauta'))
							{
								$checked = 'checked';
							}
							echo "<td><input $checked name='" . $role .
									 "_edit_pauta' type='checkbox' >&nbsp;" .
									 /*__('Send', 'delibera') .*/ "</td>";
							$checked = '';
							if($listrole->has_cap('edit_pautas'))
							{
								$checked = 'checked';
							}
							echo "<td><input $checked name='" . $role .
									 "_edit_pautas' type='checkbox' >&nbsp;" .
									 /*__('Send', 'delibera') .*/ "</td>";
							$checked = '';
							if($listrole->has_cap('edit_private_pautas'))
							{
								$checked = 'checked';
							}
							echo "<td><input $checked name='" . $role .
									 "_edit_private_pautas' type='checkbox' >&nbsp;" .
									 /*__('Send', 'delibera') .*/ "</td>";
							$checked = '';
							if($listrole->has_cap('publish_pautas'))
							{
								$checked = 'checked';
							}
							echo "<td><input $checked name='" . $role .
									 "_publish_pautas' type='checkbox' >&nbsp;" .
									 /*__('Send', 'delibera') .*/ "</td>";
							$checked = '';
							if($listrole->has_cap('read_pauta'))
							{
								$checked = 'checked';
							}
							echo "<td><input $checked name='" . $role .
									 "_read_pauta' type='checkbox' >&nbsp;" .
									 /*__('Send', 'delibera') .*/ "</td>";
							$checked = '';
							if($listrole->has_cap('read_private_pautas'))
							{
								$checked = 'checked';
							}
							echo "<td><input $checked name='" . $role .
									 "_read_private_pautas' type='checkbox' >&nbsp;" .
									 /*__('Send', 'delibera') .*/ "</td>";
							$checked = '';
							if($listrole->has_cap('delete_published_pautas'))
							{
								$checked = 'checked';
							}
							echo "<td><input $checked name='" . $role .
									 "_delete_published_pautas' type='checkbox' >&nbsp;" .
									 /*__('Send', 'delibera') .*/ "</td>";
							$checked = '';
							if($listrole->has_cap('forcar_prazo'))
							{
								$checked = 'checked';
							}
							echo "<td><input $checked name='" . $role .
									 "_forcar_prazo' type='checkbox' >&nbsp;" .
									 /*__('Send', 'delibera') .*/ "</td>";
							$checked = '';
							if($listrole->has_cap('delibera_reabrir_pauta'))
							{
								$checked = 'checked';
							}
							echo "<td><input $checked name='" . $role .
									 "_delibera_reabrir_pauta' type='checkbox' >&nbsp;" .
									 /*__('Send', 'delibera') .*/ "</td>";
							$checked = '';
							if($listrole->has_cap('edit_published_pautas'))
							{
								$checked = 'checked';
							}
							echo "<td><input $checked name='" . $role .
									 "_edit_published_pautas' type='checkbox' >&nbsp;" .
									 /*__('Send', 'delibera') .*/ "</td>";
							$checked = '';
							if($listrole->has_cap('edit_published_pauta'))
							{
								$checked = 'checked';
							}
							echo "<td><input $checked name='" . $role .
									 "_edit_published_pauta' type='checkbox' >&nbsp;" .
									 /*__('Send', 'delibera') .*/ "</td>";
							$checked = '';
							if($listrole->has_cap('edit_encaminhamento'))
							{
								$checked = 'checked';
							}
							echo "<td><input $checked name='" . $role .
									 "_edit_encaminhamento' type='checkbox' >&nbsp;" .
									 /*__('Send', 'delibera') .*/ "</td>";
							$checked = '';
							if($listrole->has_cap('votar'))
							{
								$checked = 'checked';
							}
							echo "<td><input $checked name='" . $role .
									 "_votar' type='checkbox' >&nbsp;" .
									 /*__('Send', 'delibera') .*/ "</td>";
							$checked = '';
							if($listrole->has_cap('relatoria'))
							{
								$checked = 'checked';
							}
							echo "<td><input $checked name='" . $role .
									 "_relatoria' type='checkbox' >&nbsp;" .
									 /*__('Send', 'delibera') .*/ "</td>";
							$checked = '';
							if($listrole->has_cap('edit_others_pautas'))
							{
								$checked = 'checked';
							}
							echo "<td><input $checked name='" . $role .
									 "_edit_others_pautas' type='checkbox' >&nbsp;" .
									 /*__('Send', 'delibera') .*/ "</td>";
							$checked = '';
							if($listrole->has_cap('edit_others_pauta'))
							{
								$checked = 'checked';
							}
							echo "<td><input $checked name='" . $role .
									 "_edit_others_pauta' type='checkbox' >&nbsp;" .
									 /*__('Send', 'delibera') .*/ "</td>";
							$checked = '';
							if($listrole->has_cap('delete_others_pautas'))
							{
								$checked = 'checked';
							}
							echo "<td><input $checked name='" . $role .
									 "_delete_others_pautas' type='checkbox' >&nbsp;" .
									 /*__('Send', 'delibera') .*/ "</td>";
							$checked = '';
							if($listrole->has_cap('delete_others_pauta'))
							{
								$checked = 'checked';
							}
							echo "<td><input $checked name='" . $role .
									 "_delete_others_pauta' type='checkbox' >&nbsp;" .
									 /*__('Send', 'delibera') .*/ "</td>";
							echo "</tr>";
						}
						// print_r($role);
					}
				}
				echo "</table>";
				/*
				 * echo "<pre>";
				 * foreach ($this->get_editable_roles() as $role)
				 * {
				 * if($role['name'] != 'Administrator'){
				 * print_r($role);
				 * }
				 * }
				 * echo "</pre>";
				 */
				?>
			<?php wp_nonce_field( 'delibera-permission-nonce', '_delibera-permission-nonce' );; ?>
		</form><?php
	}

	function get_role($role)
	{
		// $this->security_check();
		global $wp_roles;
		
		if(! isset($wp_roles))
			$wp_roles = new WP_Roles();
		
		return $wp_roles->get_role($role);
	}

	function get_editable_roles()
	{
		// $this->security_check();
		global $wp_roles;
		
		if(! isset($wp_roles))
			$wp_roles = new WP_Roles();
		
		$all_roles = $wp_roles->get_names();
		$editable_roles = apply_filters('editable_roles', $all_roles);
		
		return $all_roles;
	}
	/*** End Copied from Sendpress ***/
}

$DeliberaConfPermission = new \Delibera\Conf\Permission();
