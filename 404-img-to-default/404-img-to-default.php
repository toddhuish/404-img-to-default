<?php
/**
Plugin Name: Insert placeholder for 404 images
Plugin URI: https://9seeds.com
Description: a plugin to insert placeholder images for any img request that throw a 404
Author: Todd Huish
Version: 1.0
Author URI: http://greenpixeldev.com
**/

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp', array( 'Default_404_Img', 'default_img') );
add_action( 'admin_menu', array( 'Default_404_Img', 'menu' ) );

register_activation_hook( __FILE__, array( 'Default_404_Img', 'activate' ) );

class Default_404_Img {
	public static function default_img() {
		if(is_404()) 
		{
			$wud = wp_upload_dir();

			$uri = $_SERVER['REQUEST_URI'];
			preg_match('/(wp-content.*?$)/',$uri,$wp_content_url);
			$uri = $wp_content_url[1];

			$ext = substr($uri,-3);
			//peg and ebp are jpeg and webp but I only look at last 3 chars.
			$types = array('peg','jpg','gif','png','ebp','svg');
			preg_match("/(\d+x\d+)\..*?$/",$uri,$size);
			$x = $y = 0;
			if(isset($size[1])){
				$sizes = explode('x',$size[1]);
				$x = $sizes[0];
				$y = $sizes[1];
			}
			if(!$x && !$y){
				$x = $y = 500;
			}

			if( in_array($ext,$types) ) {
				status_header( 200 );
				self::img_header($ext);
				$stream = fopen('php://output', 'w');

				$imgurl = self::get_img_url( $uri, $x, $y );
				$remote = wp_parse_url( $imgurl );
				$dest = $wud['basedir'].str_replace( 'wp-content/uploads/', '', $remote['path']);
				$img = file_get_contents( $imgurl );
				file_put_contents('php://output', $img );
				
				//make sure dir is created
				$dirname = dirname($dest);
				if( !is_dir($dirname) ){
					$old = umask(0);
					mkdir( $dirname, 0755, true);
					umask($old);
				}

				file_put_contents( $dest, $img );
				die();
			}
		}
	}

	public static function img_header($ext){
		switch($ext){
			case 'jpg':
				header('Content-type: image/jpeg');
				break;
			case 'png':
				header('Content-type: image/png');
				break;
			case 'gif':
				header('Content-type: image/gif');
				break;
			case 'ebp':
				header('Content-type: image/webp');
				break;
			case 'svg':
				header('Content-type: image/svg+xml');
				break;
		}
	}
	
	public static function get_img_url( $uri, $x, $y ) {
		$option = get_option('default_404_img');
		if( isset($option['provider']) && strlen($option['provider'])) {
			switch($option['provider']){
				case 'fillmurray':
					$uri = 'https://www.fillmurray.com/'.$x.'/'.$y;
					break;
				case 'placeholder':
					$uri = 'https://via.placeholder.com/'.$x.'x'.$y;
					break;
				case 'picsum':
					$uri = 'https://picsum.photos/'.$x.'/'.$y;
					break;
				case 'placekitten':
					$uri = 'https://placekitten.com/'.$x.'/'.$y;
					break;
				case 'baconmockup':
					$uri = 'https://baconmockup.com/'.$x.'/'.$y;
					break;
				case 'other':
					$uri = $option['site'].'/'.$uri;
					break;
				default:
					$uri = 'https://www.fillmurray.com/'.$x.'/'.$y;
			}
		
		}
		return $uri;
	}

	public static function menu(){
		add_management_page( 'Default 404 Img', 'Default 404 Img', 'install_plugins', 'default404img', array( 'Default_404_Img', 'ui') );
	}
	public static function ui(){
		$option = get_option('default_404_img');
		if( !$option ) {
			$option = array( 'site' => '', 'provider' => '' ); 
		}

		if (isset($_POST['default-404-img-nonce']) && wp_verify_nonce($_POST['default-404-img-nonce'], plugin_basename(__FILE__))) {
			$option = array( 'site' => '', 'provider' => '' ); 
			if(isset($_POST['imgsite']) && wp_http_validate_url($_POST['imgsite'])){
				$option['site'] = $_POST['imgsite'];
			}
			
			if(isset($_POST['imgprovider']) && strlen($_POST['imgprovider'])){
				$option['provider'] = $_POST['imgprovider'];
			}

			update_option('default_404_img', $option);
		}

		$providers = array(
			'fillmurray' => 'Fill Murray',
			'placeholder' => 'Placeholder.com',
			'picsum' => 'Lorem Picsum',
			'placekitten' => 'Place Kitten',
			'baconmockup' => 'Bacon Mockup',
			'other' => 'Origin Site',
		);
		?>
		<div id="wrap">
		<h1>Default 404 Image Settings</h1>
		<form method="post" action="">
			<input type="hidden" name="default-404-img-nonce" value="<?php echo wp_create_nonce(plugin_basename(__FILE__)); ?>" />
			<h3>Generated Image Providers</h3>
			<?php foreach( $providers as $k => $v ) { 
				echo '<input type="radio" name="imgprovider" id="'.$k.'" value="'.$k.'" '.(isset($option['provider']) && $option['provider'] == $k ? 'checked':'').' /><label for="'.$k.'">'.$v.'</label><br>';
			} ?>
			<div id="otherdiv" style="display:none;">
				<h3>A specific WP site URL</h3>
				<input type="text" name="imgsite" class="widefat" style="width:50%" value="<?php echo $option['site']; ?>" placeholder="URL of remote WP site" /><br>
			</div>
			<input class="submit-primary" type="submit" name="submit" value="Save Settings" />
		</form>
		</div>
		<script type="text/javascript">
		jQuery(document).ready(function($) {
			if($('#other').is(':checked') ){
				$('#otherdiv').show();
			}
			$('input:radio[name="imgprovider"]').change(function(){
				if($('#other').is(':checked') ){
					$('#otherdiv').show();
				} else {
					$('#otherdiv').hide();
				}
			});
		});
		</script>
		<?php
	}

	public static function activate() {
		$option = get_option('default_404_img');
		if( !$option ) {
			$option = array( 'site' => '', 'provider' => 'fillmurray' ); 
			update_option('default_404_img', $option);
		}
	}
}
