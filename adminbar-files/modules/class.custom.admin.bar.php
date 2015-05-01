<?php

if( ! class_exists( 'CustomAdminBar' ) ){

	class CustomAdminBar extends EasyCustomAdminBar{

		public $plugin_url;
	    public $plugin_dir;
		public $version;
		public $options;
		public $admin_bar_table;
		public $nodes;
		public $parent_nodes;

		public function __construct() {
			global $adminbar;
			global $wpdb;
			//parent::__construct();
			if( $adminbar ){
				$this->plugin_dir = $adminbar->plugin_dir;
		        $this->plugin_url = $adminbar->plugin_url;
				$this->version = $adminbar->version;
				$this->options = $adminbar->options;
			}
			$this->admin_bar_table = $wpdb->prefix . 'admin_bar_table';
			//$this->nodes = $this->get_all_custom_nodes();
			//$this->parent_nodes = $this->get_all_custom_parent_nodes();
			add_action('init', array($this, 'get_all_custom_nodes'));
            add_action('init', array($this, 'get_all_custom_parent_nodes'));
			add_filter( 'adminbar_form', array( $this, 'AdminBar_style_form' ), 16 );
			add_action( 'plg_tables_installed', array( $this, 'menu_tables_install' ), 13 );
			add_action( 'save_adminbar_data', array( $this, 'save_admin_bar' ), 13  );
			add_action( 'admin_bar_menu', array( $this, 'custom_admin_bar' ), 999 );

		}

		public function is_custom_node_empty() {
			global $wpdb;
			return $wpdb->get_var( 'select count(*) from ' . $this->admin_bar_table ) < 1;
		}

		public function menu_tables_install() {
			global $wpdb;
			global $jal_db_version;

			$sql = "CREATE TABLE IF NOT EXISTS " . $this->admin_bar_table . " (
				id INT(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				parent_node VARCHAR(255) NOT NULL,
				node_id VARCHAR(255),
				node_name VARCHAR(255),
				node_link VARCHAR(255)
				)";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
			add_option( "jal_db_version", $jal_db_version );
		}

		public function save_admin_bar( $data ) {
			global $wpdb;
			if( isset($_POST['adminbar_nonce_field']) && wp_verify_nonce( $_POST['adminbar_nonce_field'], 'adminbar_nonce_action' ) ){
				if( $_POST['admin_bar_menu'] != '' && $_POST['admin_bar_link'] != '' ) {
					$q = $wpdb->insert( $this->admin_bar_table, array( 'parent_node'=>$_POST['admin_bar_parent'], 'node_name'=>$_POST['admin_bar_menu'], 'node_link'=>$_POST['admin_bar_link'], 'node_id'=>strtolower( str_replace( ' ', '_', $_POST['admin_bar_menu'] ) ) ) );
					wp_redirect( admin_url( 'admin.php?page=adminbar-settings&msg=' . __( 'Settings+saved!', 'ecab' ) ) );
				}
			}

			if( isset( $_GET['adminbar_nonce_field'] ) && wp_verify_nonce( $_GET['adminbar_nonce_field'], 'adminbar_nonce_action' ) ) {
				echo 100;
				if( isset( $_GET['action'] ) && isset( $_GET['id'] ) && $_GET['action'] == 'delete_node' ) {
					$wpdb->delete( $this->admin_bar_table, array( 'id' => sprintf( '%d', $_REQUEST['id'] ) ) );
					$this->nodes = $this->get_all_custom_nodes();
					$this->parent_nodes = $this->get_all_custom_parent_nodes();
					wp_redirect( admin_url( 'admin.php?page=adminbar-settings&msg=' . __( 'Admin+bar+menu+deleted!', 'ecab' ) ) );
				}
			}
		}

		public function get_all_custom_nodes() {
			global $wpdb;
			$sql = "SELECT * from " . $this->admin_bar_table;
			$this->nodes = $wpdb->get_results( $sql, 'ARRAY_A' );
            //return $wpdb->get_results( $sql, 'ARRAY_A' );
            //var_dump($this->nodes);
		}

		public function get_all_custom_parent_nodes() {
			global $wpdb;
            $this->parent_nodes = $wpdb->get_results( "SELECT * from " . $this->admin_bar_table . " where parent_node = ''", 'ARRAY_A' );
			//return $wpdb->get_results( "SELECT * from " . $this->admin_bar_table . " where parent_node = ''", 'ARRAY_A' );

		}

		public function get_node_by_id( $id ){
			global $wpdb;
			$node = $wpdb->get_row( "SELECT * FROM " . $this->admin_bar_table . " WHERE id = " . $id );
			return $node->node_id;
		}

		public function custom_admin_bar( $wp_admin_bar ) {
			if( isset( $this->options['remove_logo'] ) && $this->options['remove_logo'] == 1 ){
				$wp_admin_bar->remove_node( 'wp-logo' );
			}
			foreach( $this->nodes as $node ) {
				$parent = $node['parent_node'] == '' ? false : $this->get_node_by_id( $node['parent_node'] );
				$args = array(
					'id'		=> $node['node_id'],
					'title'		=> $node['node_name'],
					'href'		=> $node['node_link'],
					'parent'	=> $parent
				);
				$wp_admin_bar->add_node( $args );
			}
			//var_dump($wp_admin_bar);

		}

		public function AdminBar_style_form( $form ) {

			ob_start();
			?>
			<div class="postbox dg_ap_box">
				<h3 class="hndle"><span><?php _e( 'Custom Admin Bar' ) ?></span></h3>
				<div class="inside">
					<?php //var_dump($this->options); ?>
					<div style="width:100%; padding-top: 10px;">
                        <strong><?php _e( 'Remove Wordpress Logo', 'ecab' ) ?></strong>
                        <input <?php echo isset( $this->options['remove_logo'] ) && $this->options['remove_logo'] == 1 ? 'checked="checked"' : ''; ?> type="checkbox" name="ecab[remove_logo]" value="1" />
                    </div>
                    <div class="clear"></div>
                    <div style="width:100%; padding-top: 10px;">
                        <strong><?php _e( 'Add menu in admin bar', 'ecab' ) ?></strong>
                    </div>
                    <div class="clear"></div>
                    <div style="width:100%; padding-top: 10px;">
                        <strong><?php _e( 'Menu Name', 'ecab' ) ?></strong><br>
                        <input type="text" size="60" name="admin_bar_menu" />
                    </div>
                    <div class="clear"></div>
                    <div style="width:100%; padding-top: 10px;">
                        <strong><?php _e( 'Menu Link', 'ecab' ) ?></strong><br>
                        <input type="text" size="60" name="admin_bar_link" />
                    </div>
                    <div class="clear"></div>
                    <?php if( ! $this->is_custom_node_empty() ) { ?>
                    <div style="width:100%; padding-top: 10px;">
                        <strong><?php _e( 'Parent Menu', 'ecab' ) ?></strong><br>
                        <select name="admin_bar_parent">
							<option value=""><?php _e( 'Select Parent Menu' ) ?></option>
							<?php foreach( $this->parent_nodes as $node ) { ?>
								<option value="<?php echo $node['id'] ?>"><?php echo $node['node_name'] ?></option>
							<?php } ?>
						</select>
                    </div>
                    <div class="clear"></div>
                    <?php } else { ?>
						<input type="hidden" name="admin_bar_parent" value="" />
					<?php } ?>

					<table class="form-table">
						<?php if( ! $this->is_custom_node_empty() ) { ?>
						<tr>
							<th><?php _e( 'Created menus', 'ecab' ) ?></th>
							<td>
								<?php foreach( $this->parent_nodes as $node ){ ?>
									<div class="widget_list">
										<?php echo isset($node['node_name']) ? $node['node_name'] : '' ?>
										<div>
											<a href="<?php echo admin_url( 'admin.php?page=adminbar-settings&&noheader=true&&action=delete_node&&id=' . $node['id'] . '&&adminbar_nonce_field=' . wp_create_nonce( 'adminbar_nonce_action' ) ) ?>"><i class="fa fa-times"></i></a>
										</div>
									</div>
									<?php foreach( $this->nodes as $child_node ){ ?>
										<?php if( $child_node['parent_node'] == $node['id'] ) { ?>
											<div class="widget_list" style="padding-left: 20px">
												<?php echo isset($child_node['node_name']) ? $child_node['node_name'] : '' ?>
												<div>
													<a href="<?php echo admin_url( 'admin.php?page=adminbar-settings&&noheader=true&&action=delete_node&&id=' . $child_node['id']  . '&&adminbar_nonce_field=' . wp_create_nonce( 'adminbar_nonce_action' ) ) ?>"><i class="fa fa-times"></i></a>
												</div>
											</div>
										<?php } ?>
									<?php } ?>
								<?php } ?>
							</td>
						</tr>
						<?php } ?>
					</table>
				</div>
			</div>
			<?php
			$output = $form . ob_get_contents();
			ob_end_clean();
			return $output;
		}

	}

	new CustomAdminBar();

}
