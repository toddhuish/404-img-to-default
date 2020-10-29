<?php
/*
Plugin Name: Insert placeholder for 404 images
Plugin URI: https://9seeds.com
Description: a plugin to insert placeholder images for any img request that throw a 404
Author: Todd Huish
Version: 1.0
Author URI: http://9seeds.com
*/

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'wp', array( 'Default_404_Img', 'default_img') );
add_action( 'admin_menu', array( 'Default_404_Img', 'menu' ) );
class Default_404_Img {
	public static function default_img() {
		if(is_404()) 
		{
			$uri = $_SERVER['REQUEST_URI'];
			preg_match('/(wp-content.*?$)/',$uri,$wp_content_url);
			$uri = $wp_content_url[1];

			$ext = substr($uri,-3);
			$types = array('jpg','gif','png','ebp');
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

				file_put_contents('php://output', file_get_contents( $imgurl ) );
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
		}
	}
	
	public static function get_img_url( $uri, $x, $y ) {
		$option = get_option('default_404_img');
		if(isset($option['site']) && wp_http_validate_url($option['site'])){
			$uri = $option['site'].'/'.$uri;
		} elseif( isset($option['provider']) && strlen($option['provider'])) {
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
			} else {
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
			<h3>A specific WP site URL</h3>
			<input type="text" name="imgsite" class="widefat" style="width:50%" value="<?php echo $option['site']; ?>" placeholder="URL of remote WP site" /><br>
			<input class="submit-primary" type="submit" name="submit" value="Save Settings" />
		</form>
		</div>
		<?php
	}
}
