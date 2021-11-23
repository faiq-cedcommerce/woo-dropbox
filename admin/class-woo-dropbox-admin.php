<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://www.cedcommerce.com
 * @since      1.0.0
 *
 * @package    Woo_Dropbox
 * @subpackage Woo_Dropbox/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Woo_Dropbox
 * @subpackage Woo_Dropbox/admin
 * @author     Faiq Masood <faiqmasood@cedcommerce.com>
 */
class Woo_Dropbox_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woo_Dropbox_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woo_Dropbox_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/woo-dropbox-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Woo_Dropbox_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Woo_Dropbox_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/woo-dropbox-admin.js', array( 'jquery' ), $this->version, false );

	}

	//Creating Admin Menu
	public function add_menu()
    {
        add_menu_page( "DropBox API Settings", "DropBox API Settings", 'manage_options', 'dropbox-api-woo-settings', array( $this, 'dropbox_api_woo_settings' ));
    }

	function dropbox_api_woo_settings() {
		?>
		<div class="wrap">
		<h1>Dropbox API Settings</h1>
		
		<form method="post" >
			<table class="form-table">
				<tr valign="top">
				<th scope="row">DropBox Access Token</th>
				<td><textarea name="dropbox_access_token" rows="6" cols="50"><?php echo esc_attr( get_option('dropbox_woo_access_token') ); ?></textarea></td>
				</tr>
				
			</table>
			
			<?php submit_button(); ?>
		
		</form>
		</div>
		<?php 

	if(isset($_POST['submit']))
		update_option('dropbox_woo_access_token', $_POST['dropbox_access_token']);
		exit;
		
	}

	//Meta box for WooCommerce Product
	public function add_custom_product_meta_boxes() {
		add_meta_box(
			'wp_custom_content_meta_box',
			'Dropbox Image Upload',
			array($this,'wp_custom_attachment'),
			'product',
			'side'
		);
	} 

	//Creating Custom Meta Box (Image upload to DropBox)
	function wp_custom_attachment() {
		global $post;
		wp_nonce_field(plugin_basename(__FILE__), 'dropsync-upload_nonce');
		if( get_post_meta($post->ID, 'dropbox_woo_product_img_status', true) == 'yes'){
			$img_src = get_post_meta($post->ID, 'dropbox_woo_product_img_final_url', true);
			$checked_status = "checked";
		}
		else{
			
			$placeholder_image = get_option( 'woocommerce_placeholder_image', 0 );
			$size = 'woocommerce_thumbnail';
      		$img_src = wp_get_attachment_image_src( $placeholder_image, $size )[0];


			$checked_status = "";
		}
		
		$html = '<p class="description">';
		$html .= 'Upload Image to the Dropbox.';
		$html .= '</p>';
		$html .= '<input type="file" name="dropsync-upload" accept="image/*"><br><br><br><input type="checkbox" value="no" name="show_feature_img" '. $checked_status .' > Use as Product Image</br></br>';	
		$html .= '<img src="'.$img_src.'" width="100" height="100" />';
		$html .= '<input type="hidden" name="custom_product_field_nonce" value="' . wp_create_nonce() . '">';
		print_r(wp_get_attachment_url( $post->ID ));
		echo $html;	
	}

	//Saving the URL of the Image to the post_meta
	public function save_wp_custom_content_meta_box( $post_id ) {
			
		if ( ! isset( $_POST[ 'custom_product_field_nonce' ] ) ) {
			return $post_id;
		}
		$nonce = $_REQUEST[ 'custom_product_field_nonce' ];
		//Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce ) ) {
			return $post_id;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}
		if ( 'product' == $_POST[ 'post_type' ] ){
			if ( ! current_user_can( 'edit_product', $post_id ) )
				return $post_id;
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		}
		if($_FILES['dropsync-upload']['name'] != ""){

			$upload_dir = trailingslashit( wp_upload_dir()['basedir'] ) . 'dropsync';
			wp_mkdir_p( $upload_dir );	
			$path		=	mt_rand(100000, 999999).'-'.$_FILES['dropsync-upload']['name'];
			$pathto		=	$upload_dir.'/'.$path;			
			move_uploaded_file($_FILES['dropsync-upload']['tmp_name'], $pathto) or die( "Could not copy file!");
		
		}
		$token = get_option('dropbox_woo_access_token');
	
		$url = 'https://content.dropboxapi.com/2/files/upload';
		$dropbox_path = '/'.''.$path;
		$dropapi = array('path'=>$dropbox_path,'mode'=>'add');
		$response = wp_remote_post($url, 
							array('method'=>'POST', 
							'headers' => array('Content-Type' => 'application/octet-stream',
												'Authorization' => 'Bearer '.$token,
												'Dropbox-API-Arg' => json_encode($dropapi),
												
												),
							'body'	=>file_get_contents($pathto),											
								)
							);
		$json = json_decode($response['body'], true);
		if(isset($_REQUEST['show_feature_img'])){
			update_post_meta($post_id, 'dropbox_woo_product_img_status', 'yes');	
		}else{
			update_post_meta($post_id, 'dropbox_woo_product_img_status', 'no');	
		}
		$parameters = array("path" => $dropbox_path );
		$headers = array('Authorization: Bearer '.$token,'Content-Type: application/json');
		$curlOptions = array(
						CURLOPT_HTTPHEADER => $headers,
						CURLOPT_POST => true,
						CURLOPT_POSTFIELDS => json_encode($parameters),
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_VERBOSE => true
					);
		$ch = curl_init('https://api.dropboxapi.com/2/sharing/create_shared_link_with_settings');
		curl_setopt_array($ch, $curlOptions);
		$dropbox_img_response = curl_exec($ch);
		//update_post_meta($post_id, 'dropbox_woo_product_img_status', 'yes');
		$json_response 			= json_decode($dropbox_img_response, true);
		$img_url_response 		= $json_response['url'];	
		if($img_url_response != NULL){
			update_post_meta($post_id, 'dropbox_woo_product_img_final_url', str_replace('dl=0', 'raw=1',$json_response["url"]));
		}	
		curl_close($ch);
		unlink($pathto);
		
}
	
	//Put down the multipart/form-data in form tag of the product edit page
	public function post_edit_form_tag( ) {
			global $post;	
			echo ' enctype="multipart/form-data"';
	}

	public function pn_change_product_image_link( $image, $attachment_id, $size, $icon ){
			global $post;
			global $product;
			if( get_post_meta($post->ID, 'dropbox_woo_product_img_status', true) == 'no' ){
				return $image;
			}
			if( get_post_meta($post->ID, 'dropbox_woo_product_img_status', true) == 'yes' ){
			 	$src = get_post_meta($post->ID, 'dropbox_woo_product_img_final_url',true);
				 $width  = ''; 
				$height = ''; 
				$image  = array( $src, $width, $height );		
				return $image;
			}		
			return $image;
		}
		function custom_new_product_image( $_product_img, $cart_item, $cart_item_key ) {
			
			if( get_post_meta($cart_item['product_id'], 'dropbox_woo_product_img_status', true) == 'yes' ){
				$src 	= get_post_meta($cart_item['product_id'], 'dropbox_woo_product_img_final_url',true);
				$a 		= '<img src="'.$src.'" />'; 	
		   		return $a;
		   	}else{
				   return $_product_img;
			   }
		}
		
		
}
