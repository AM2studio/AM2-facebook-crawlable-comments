<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AM2_Facebook {
	
	private $plugin;
	private $fb_app_id;
	private $fb_app_secret;
	private $fb_app_token;
	private $current_url;
	private $current_object_id;
	private $fb_comments;

	public function __construct ( $plugin ) {
		$this->plugin = $plugin;
		
		$this->fb_app_id = get_option($plugin->settings->base . "fb_app_id");
		$this->fb_app_secret = get_option($plugin->settings->base . "fb_app_secret");
		$this->fb_app_token = $this->fb_app_id . "|" . $this->fb_app_secret; 
		$this->include_js_sdk = get_option($plugin->settings->base . "include_js_sdk");
		$this->sdk_locale = get_option($plugin->settings->base . "sdk_locale");						
		
		if(!is_admin())		
			add_action( 'init', array( $this, 'setupComments' ), 20 );		
				
	}
	
	public function setupComments(){
		global $wp;
		global $wpdb;		
		
		$this->current_url = home_url(add_query_arg(array(),$wp->request)); // $current_url = "http://she.hr/ah-ta-sretna-maja/"; // 	
				
		$fbo_wp_id = $wpdb->get_var($wpdb->prepare("SELECT wpm.post_id FROM $wpdb->postmeta wpm WHERE wpm.meta_value = '%s' AND wpm.meta_key = 'object_url'", $current_url));						
		
		if(empty($fbo_wp_id)){
			
			$fbo = $this->fb_get("https://graph.facebook.com/v2.4/?access_token={$this->fb_app_token}&id=" . $this->current_url);			
			
			if(!empty($fbo)) {
				$this->current_object_id = $fbo->og_object->id;
			
				$my_post = array(
					'post_title'    => $current_url,
					'post_content'  => '',
					'post_status'   => 'draft',
					'post_type' => 'fb_object',				
				);
				
				$post_id = wp_insert_post( $my_post );
				
				$this->fb_comments = $this->fb_get("https://graph.facebook.com/v2.4/{$this->current_object_id}/comments?access_token={$this->fb_app_token}&filter=toplevel&fields=comments.summary(true),message,from,likes,parent.fields(id)&limit=100");
				
				$this->saveFBObject($this->fb_comments);
			}			
						
		} else {
			
			$fbo_wp = get_post_meta($fbo_wp_id);
			$fbo_time_last_fetch = $fbo_wp['time_last_fetch'][0];
			$fbo_id = $fbo_wp['object_id'][0];
			
			if($fbo_time_last_fetch < strtotime('-1 minute')){				
			
				$this->fb_comments = $this->fb_get("https://graph.facebook.com/v2.4/{$fbo_id}/comments?access_token={$this->fb_app_token}&filter=toplevel&fields=comments.summary(true),message,from,likes,parent.fields(id)&limit=100");
				
				$this->saveFBObject ( $this->fb_comments );
				
			} else {
				
				$this->fb_comments = unserialize($fbo_wp['fb_comments'][0]);
				
			}
			
		}
		
		add_shortcode( 'am2_fb_crawl_comments', array( $this, 'displayComments' ) );
	}	
	
	public function displayComments($atts){
		if($this->include_js_sdk == 'on'){?>
			<div id="fb-root"></div>
			<script>(function(d, s, id) {
				var js, fjs = d.getElementsByTagName(s)[0];
				if (d.getElementById(id)) return;
				js = d.createElement(s); js.id = id;
				js.src = "//connect.facebook.net/<?php echo $this->sdk_locale; ?>/sdk.js#xfbml=1&version=v2.4&appId=<?php echo $this->fb_app_id;?>";
				fjs.parentNode.insertBefore(js, fjs);
			}(document, 'script', 'facebook-jssdk'));</script>
		<?php }
		
		echo '<div class="fb-comments" data-href="'.$this->current_url.'" data-numposts="5"></div>';		
		
		$this->printCrawlableComments($this->fb_comments->data);
	}
	
	public function printCrawlableComments($fb_comments){
		if(empty($fb_comments)) return;
		
		echo "<ul>";
		foreach($fb_comments as $fb_comment){ ?>
			<li>
				<?php echo $fb_comment->message;?>
				<?php if(isset($fb_comment->comments)) $this->printCrawlableComments($fb_comment->comments->data); ?>
			</li>
		<?php }
		echo "</ul>";
	}
	
	public function saveFBObject($fb_comments){
		if($fb_comments){
			update_post_meta($post_id, 'object_url', $current_url );
			update_post_meta($post_id, 'object_id', $fbo->og_object->id );
			update_post_meta($post_id, 'time_last_fetch', time());
			update_post_meta($post_id, 'fb_comments', $fb_comments);			
		}			
	}
	
	public function fb_get($url)
	{			
			$result = wp_remote_get($url, array( 'sslverify' => false ));
						
			if(is_wp_error($result))
			{					
					return $result;
			}
						
			return json_decode($result['body']);
	}
}

?>