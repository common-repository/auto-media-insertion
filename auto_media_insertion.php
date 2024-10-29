<?php
/*
Plugin Name:  Auto Media Insertion
Description: Automatically adds image or video to post.
Version: 1.3
Author: <a href="http://LinkAuthority.com">LinkAuthority.com</a> and <a href="http://ArticleRanks.com">ArticleRanks.com</a> Team

*/

// Auto_Media_Insertion

include("image_easy.php");
include("newbrowser.php");


register_activation_hook( __FILE__, 'auto_image_activate' );

function auto_image_activate(){
	global $wpdb;

		
	update_option('auto_image_type', 'both');
	update_option('auto_image_position', 'any');
	update_option('auto_image_imgwidth', '150');
	update_option('auto_image_vidwidth', '260');
	update_option('auto_image_vidheight', '200');
	update_option('auto_image_keywordln', '5');
	update_option('auto_image_keys', '2');
	update_option('auto_image_freq', 'random');
	update_option('auto_image_count', 0);
	
	
	$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}ami_img` (
			  `post_id` int(11) NOT NULL,
			  `img` text NOT NULL
			) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
			";
	$wpdb->query($sql);		
	
		
	$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}ami_vid` (
			  `post_id` int(11) NOT NULL,
			  `video` text NOT NULL
			) ENGINE=MyISAM DEFAULT CHARSET=cp1251;
			";
	$wpdb->query($sql);		
	

}


//add_action( 'save_post', 'auto_image_act' );

add_action( 'publish_post', 'auto_image_act' );
add_action( 'xmlrpc_publish_post ', 'auto_image_act' );

function auto_image_act($post_id) {
	global $wpdb;
	
	$content = $wpdb->get_var( $wpdb->prepare("SELECT `post_content` FROM {$wpdb->prefix}posts WHERE `ID` = {$post_id};"));
	
	$frequency = get_option('auto_image_freq');
	$count = get_option('auto_image_count');
	$count++;
	
	
	if ($frequency == 'random'){
	
				$frequency = rand (1,2);
				switch($position){
				
					case 1:
						$frequency = 1;
					break;					
					case 2:
						$frequency = 11;
					break;					
				
				}
				
	}
	
	
	if ($count >= $frequency){
	
		$act = true;
	
	}else{
	
		$act = false;
		
	}
		
	
	

	if (strpos($content, '<img') === false && strpos($content, '<embed') === false && $act == true){
	
		update_option('auto_image_count', 0);
		
		$type = get_option('auto_image_type');
		$position = get_option('auto_image_position');
		$imgwidth = get_option('auto_image_imgwidth');
		$vidwidth = get_option('auto_image_vidwidth');
		$vidheight = get_option('auto_image_vidheight');
		$length = get_option('auto_image_keywordln');
		$keywords_quantity = get_option('auto_image_keys');
		 
			
		//preparing text
		$text = strip_tags($content);
		$text = preg_replace("/[\s\.\,\!0-9\"«»\-\]\[]+/", " ", $text);
		
		$text = explode(" ", $text);
		foreach($text as $k=>$v){

			if (strlen($v) < $length){
			
				unset($text[$k]);
			
			}else{
				$text[$k] = strtolower($v);
			}
			
			
		}

			$count_array = array_count_values($text);
			arsort($count_array);
				
			$result=array();	
			$i=0;
			foreach ($count_array as $key => $value)

			{
				$i++;
				$result[] = $key;
				if ($i==2){
					break;
				}
			}
			//$result[0];
			
			
			
			
			if ($type == 'both'){
				$type = rand (1,2);
				if ($type == 1){$type = 'video';}else{$type = 'image';}
			}
			
			if ($position == 'any'){
				$position = rand (1,3);
				switch($position){
				
					case 1:
						$position = 'left';
					break;					
					case 2:
						$position = 'center';
					break;					
					case 3:
						$position = 'right';
					break;					
				
				}
				
			}
			
			
			
			
			$keywords = array();
			for ($i=0;$i<$keywords_quantity;$i++){
			
				$keywords[] = $result[$i];				
			
			}	
			$keywords = implode('+',$keywords);
				
			
			  $parser = new cms_http_parse;
   
			if ($type == 'image'){
			
				//image
				
				$url = "http://www.google.com/images?hl=ru&sa=N&start=0&ndsp=20&sout=1&q=".$keywords;
			
				$PageParse = $parser->get($url);
				
				
				preg_match_all("#imgurl(.*)&amp;#iU",$PageParse,$Links);

				for($c=0;$c<count($Links[1]);$c++){
				
					$k = rand(0,count($Links[1]));
				
					$Link = $Links[1][$k];
					$Link = str_replace("\\x3d",null,$Link);
					$Link = str_replace("\\",null,$Link);
					$Link = str_replace("=",null,$Link);
					
								
					$wasimg = $wpdb->get_var( $wpdb->prepare("SELECT `img` FROM `{$wpdb->prefix}ami_img` WHERE `img` = '".md5($Link)."';"));
					if ($wasimg != ''){
						continue;
					}

				//	die($Link
				
					switch ($position){
								case 'left':
									$echo_position = "alignleft";
								break;
								case 'right':
									$echo_position = "alignright";
								break;
								case 'center':
									$echo_position = "aligncenter";
								break;
							
							}
				
				
					if( ini_get('safe_mode') ){
					
						$wpdb->query("INSERT INTO `{$wpdb->prefix}ami_img` (`post_id`, `img`) VALUES ('{$post_id}', '".md5($Link)."');");	
						
						$add_to_post = '<img class="'.$echo_position.' size-thumbnail" style="width:'.$imgwidth.'px; margin: 5px;" src="'.$Link.'"/>';
						
					
					
					}else{
					
						$upload_dir = wp_upload_dir(); 
						 
					
						if (@copy($Link,$upload_dir['path']."/".md5($Link).".jpg")==TRUE){
						
							$wpdb->query("INSERT INTO `{$wpdb->prefix}ami_img` (`post_id`, `img`) VALUES ('{$post_id}', '".md5($Link)."');");	
						
							$img_name = md5($Link).".jpg";
									
							$image = new SimpleImage();
							$image->load($upload_dir['path']."/".$img_name);
							$image->resizeToWidth($imgwidth);
							$image->save($upload_dir['path']."/".$img_name);
							
							$image_src = $upload_dir['url']."/".$img_name;
							
							
							$add_to_post = '<img class="'.$echo_position.'  size-thumbnail"  style="margin: 5px;" src="'.$image_src.'"/>';
							
												
							break;
									  
						}
					}
				}
							
			}else{
			
				//video
				$PageParse=$parser->get("http://www.youtube.com/results?search_type=videos&search_query=".$keywords."&page=1");

				if(strpos($PageParse, "/watch?v=")!=FALSE){
				
					preg_match_all("/href=\"\/watch\?v=([^\"]*)\"/sU", $PageParse, $matches);
					
					$resultmovies=implode(" ", $matches[1]);
					$resultmovies=str_replace("&hd=1", null, $resultmovies);
					$resultmovies=str_replace("&feature=browch", null, $resultmovies);
					$resultmovies=explode(" ", $resultmovies);
					$resultmovies=array_unique($resultmovies);
							
				}
			
				
				
				
				$repeater = false;
				while ($repeater == false){
				
					$movie_number = rand(0, count($resultmovies));
					
					
					
					
					$wasvid = $wpdb->get_var( $wpdb->prepare("SELECT `video` FROM `{$wpdb->prefix}ami_vid` WHERE `video` = '{$resultmovies[$movie_number]}';"));
					if ($wasvid == ''){
						$repeater = true;
					}				
					
					if (trim($resultmovies[$movie_number]) == ''){
						
						$repeater = false;
					
					}
					
				
				}
				
						
				
				$wpdb->query("INSERT INTO `{$wpdb->prefix}ami_vid` (`post_id`, `video`) VALUES ('{$post_id}', '{$resultmovies[$movie_number]}');");	
				
				switch ($position){
					case 'left':
						$echo_position = "alignleft";
					break;
					case 'right':
						$echo_position = "alignright";
					break;
					case 'center':
						$echo_position = "aligncenter";
					break;
						
				}
				
				$add_to_post = '
				<object class="'.$echo_position.' size-thumbnail"  style="margin: 5px;" width="'.$vidwidth.'" height="'.$vidheight.'">
				<param name="movie" value="http://www.youtube.com/v/'.trim($resultmovies[$movie_number]).'?version=3&amp;hl=en_US&amp;rel=0"></param>
				<param name="allowFullScreen" value="true"></param>
				<param name="allowscriptaccess" value="always"></param>
				<embed src="http://www.youtube.com/v/'.trim($resultmovies[$movie_number]).'?version=3&amp;hl=en_US&amp;rel=0" type="application/x-shockwave-flash" width="'.$vidwidth.'" height="'.$vidheight.'" allowscriptaccess="always" allowfullscreen="true"></embed>
				</object>
				';
		
		 }
		 
		 
		 
		//	$wpdb->query("UPDATE {$wpdb->prefix}posts SET `post_content` = '{$content}' WHERE `ID` = {$post_id};");
		 
		  $my_post = array();
		  $my_post['ID'] = $post_id;
		  $my_post['post_content'] = $add_to_post.$content;

		// Update the post into the database
		  wp_update_post( $my_post );
		 
	}

	return 0;
	
}




add_action('admin_menu', 'auto_image_plugin');

function auto_image_plugin() {

	//create new top-level menu
	add_menu_page('Auto Media Insertion settings', 'Auto Media Insertion', 'administrator', __FILE__, 'auto_image_settings');

}


function auto_image_settings() {
if (isset ($_POST['option_change'])){


	update_option('auto_image_type', $_POST['auto_image_type']);
	update_option('auto_image_position', $_POST['auto_image_position']);
	update_option('auto_image_imgwidth', $_POST['auto_image_imgwidth']);
	update_option('auto_image_vidwidth', $_POST['auto_image_vidwidth']);
	update_option('auto_image_vidheight', $_POST['auto_image_vidheight']);
	update_option('auto_image_keywordln', $_POST['auto_image_keywordln']);
	update_option('auto_image_keys', $_POST['auto_image_keys']);
	update_option('auto_image_freq', $_POST['auto_image_freq']);


	$update = true;
}

?>
<div class="wrap">
<h2>Auto Media Insertion settings</h2>

<a href='http://www.linkauthority.com'><img src='http://www.linkauthority.com/i/b/linkauthority_336x280.gif' border=0></a>
<a href="http://www.articleranks.com/" ><img src="http://www.articleranks.com/images/banner/it1/articleranks_336x280.gif" alt="Article Marketing" width="336" height="280" border="none" ></a>
<br>
	<?php if($update){?>
		<div class="updated"><p><strong>Settings were saved</strong></p></div>
	<?php }?>
	
	<?php if( ini_get('safe_mode') ){ ?>
	
		<div class="error"><p><strong>Safe_mode is enabled on your server. Images will be hotlinked</strong></p></div>
	
	<?php } ?>

<form method="post"  >
	<input name="option_change" type="hidden" value="">
	
	<h3>What to add?</h3>	
	<input name="auto_image_type" type="radio" value="image" <?php if (get_option('auto_image_type') == 'image'){echo "checked";}?>> Image<br>
	<input name="auto_image_type" type="radio" value="video" <?php if (get_option('auto_image_type') == 'video'){echo "checked";}?>> Video<br>
	<input name="auto_image_type" type="radio" value="both" <?php if (get_option('auto_image_type') == 'both'){echo "checked";}?>> Both<br>
	
	<h3>frequency</h3>	
	
	<select size="1" name="auto_image_freq">
		<option value="random" <?php if (get_option('auto_image_freq') == 'random'){echo "selected='selected'";}?>>random</option>
		<option value="1" <?php if (get_option('auto_image_freq') == '1'){echo "selected='selected'";}?>>every post</option>
		<option value="2"<?php if (get_option('auto_image_freq') == '2'){echo "selected='selected'";}?>>1 out of 2</option>
		<option value="3"<?php if (get_option('auto_image_freq') == '3'){echo "selected='selected'";}?>>1 out of 3</option>
		<option value="4"<?php if (get_option('auto_image_freq') == '4'){echo "selected='selected'";}?>>1 out of 4</option>
		<option value="5"<?php if (get_option('auto_image_freq') == '5'){echo "selected='selected'";}?>>1 out of 5</option>
		<option value="6"<?php if (get_option('auto_image_freq') == '6'){echo "selected='selected'";}?>>1 out of 6</option>
		<option value="7"<?php if (get_option('auto_image_freq') == '7'){echo "selected='selected'";}?>>1 out of 7</option>
		<option value="8<?php if (get_option('auto_image_freq') == '8'){echo "selected='selected'";}?>">1 out of 8</option>
		<option value="9<?php if (get_option('auto_image_freq') == '9'){echo "selected='selected'";}?>">1 out of 9</option>
		<option value="10<?php if (get_option('auto_image_freq') == '10'){echo "selected='selected'";}?>">1 out of 10</option>
	</select>
	<br>
	
	<h3>Keyword minimum length</h3>	
	<p>This is set to how we will match the best media for your post - We will scan the post for the most popular terms a default setting of 5 is good as it removes words such as 'and', 'of' 'etc' etc</p>
	<input name="auto_image_keywordln" type="text" value="<?php if (get_option('auto_image_keywordln') != ''){echo get_option('auto_image_keywordln');}?>">characters
	
	<h3>How many keywords to use?</h3>	
	<p>This parameter again helps us find the best image or video to insert into your post - for example if you have a setting of 1 and your most commonly used phrase is real estate then the system may deliver irrelevant images as it will only choose the word real or estate if you set it as 2 then it will choose both words when looking for a relevant image or video.</p>
	<input name="auto_image_keys" type="text" value="<?php if (get_option('auto_image_keys') != ''){echo get_option('auto_image_keys');}?>">
	
	<h3>Position</h3>	
	<input name="auto_image_position" type="radio" value="left" <?php if (get_option('auto_image_position') == 'left'){echo "checked";}?>> Left<br>
	<input name="auto_image_position" type="radio" value="center" <?php if (get_option('auto_image_position') == 'center'){echo "checked";}?>> Center<br>
	<input name="auto_image_position" type="radio" value="right" <?php if (get_option('auto_image_position') == 'right'){echo "checked";}?>> Right<br>
	<input name="auto_image_position" type="radio" value="any" <?php if (get_option('auto_image_position') == 'any'){echo "checked";}?>> Any<br>
	
	<h3>Image parameters</h3>
	<input name="auto_image_imgwidth" type="text" value="<?php if (get_option('auto_image_imgwidth') != ''){echo get_option('auto_image_imgwidth');}?>">px. width
	
	<h3>Video parameters</h3>
	<input name="auto_image_vidwidth" type="text" value="<?php if (get_option('auto_image_vidwidth') != ''){echo get_option('auto_image_vidwidth');}?>">px. width<br>
	<input name="auto_image_vidheight" type="text" value="<?php if (get_option('auto_image_vidheight') != ''){echo get_option('auto_image_vidheight');}?>">px. height<br>
	

    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
    </p>

</form>
</div>
<?php

}

?>