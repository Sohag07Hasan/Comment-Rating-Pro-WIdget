<?php 
/*
 * plugin name: wp rated comments widget
 * Description: This extends the functionalities of wp comment rating widget and displays the most rated comments in widget
 * Author: Mahibul Hasan
 * Author URI: http://sohag07hasan.elance.com
 * Plugin uri: http://healerswiki.org
 * */

//original class to extend the the widget class

if(!class_exists('wp_rated_comments_widget')) : 

	class wp_rated_comments_widget extends Wp_Widget{
		
		var $ck_cache;
		//constructor function
		function wp_rated_comments_widget(){
			$widget_opt = array(
								'classname' => 'wp_rated_comments_widget',
								'description' => 'Displays most rated comments including rating feature'
							);
			$this -> WP_Widget('wp_rated_comments_widget_id',__('WP Rated Comments'),$widget_opt);
		}
		
		//settings form for the widget
		function form($instance){
			$defaults = array(
						'title' => 'Most Wanted Comments',
						'comments_number' => '5',
						 
					);
			$instance = wp_parse_args( (array)$instance,$defaults );
			
			//assinging the values
			$title = $instance['title'];
			$comments_number = $instance['comments_number'];
			
			//form is starting here
			?>
			
			<p>Title:<input class="widfet" name="<?php echo $this->get_field_name('title');?>" type="text" value="<?php echo esc_attr($title);?>" /></p>
					
			<p>Comments Number:<input class="widfet" name="<?php echo $this->get_field_name('comments_number');?>" type="text" value="<?php echo esc_attr($comments_number);?>" /></p>
			
			
			<?php 
		}
		
		//form data update function
		function update($new_instance,$old_instance){
			$instance = $old_instance;
			$instance['title'] = $new_instance['title'];
			$instance['comments_number'] = $new_instance['comments_number'];
			return $instance;
		}
		
		//display the widget in the screen > frontend 
		function widget($arg,$instance){

			//var_dump($this->get_comments());
			//exit;
			extract($arg);
			echo $before_widget;
			$title = apply_filters('widget_title',$instance['title']);
			$number = isset($instance['comments_number']) ? $instance['comments_number'] : 5 ;
			
			if(!empty($title)){echo $before_title.$title.$after_title;}			

			$result = $this->ckrating_display_filter($number);
			//var_dump($result);
			
			echo $this->parsing_comments($result);
			
			
			echo $after_widget;
		}
		
		
		//registering the widget]]
		function create_newone(){
			return register_widget('wp_rated_comments_widget');
		}
		
		function get_comments($limit){
			global $wpdb;
			$sql = "SELECT `comment_ID`, `comment_post_ID`, `comment_author`, `comment_content`, `comment_karma`, `user_id` FROM $wpdb->comments WHERE `comment_approved` = 1 ORDER BY `comment_karma` DESC LIMIT $limit ";
			
			$result = $wpdb->get_results($sql);
			return $result;
		}
		
		
		
		/*************************************************************************************************
		 *      Functions to manipulate widget data
		 **************************************************************************************************/
		//adding images and votes number in the comments

		//parsing the comment contents
		function parsing_comments($contents){
			$new = '<ul>';
			foreach($contents as $value){
				$new .= '<li>' . $value . '</li>';
			}
			$new .= '</ul>';
			return $new;
		}
		
	function ckrating_display_filter($limit){
		
		$total_contents = array();
		
		$comments = $this->get_comments($limit);
		
		foreach ($comments as $ck_comment) :
		
			   $ck_comment_ID = $ck_comment->comment_ID;
			   $ck_comment_author = $ck_comment->comment_author;
			   
			   $post_author = get_userdata($ck_comment->user_id);
			   $ck_author_name = $post_author->display_name ;
			   $comment_link = esc_url( get_comment_link($ck_comment_ID));
			   $text = "<a href='$comment_link'> $ck_comment->comment_content </a>";
			   
			   /*
			   if (get_option('ckrating_admin_off') == 'yes' && 
			       ($ck_author_name == $ck_comment_author || $ck_comment_author == 'admin')
			      )
			      return $text;
			     */
			
			   $arr = $this->ckrating_display_content_two($ck_comment);
			   
			
			   // $content is the modifed comment text.
			   $content = $text;
			
			   if (((int)$arr[1] - (int)$arr[2]) >= (int)get_option('ckrating_goodRate')) {
			      $content = '<div style="' . get_option('ckrating_styleComment') . '">' .
			               $text .  '</div>';
			   }
			   else if ( ((int)$arr[2] - (int)$arr[1])>= (int)get_option('ckrating_negative') &&
			              ! ($ck_author_name == $ck_comment_author || $ck_comment_author == 'admin')
			           )
			   {
			      $content = '<p>'.__('Hidden due to','ckrating').' '.__('low','ckrating');
			      if ( (get_option('ckrating_inline_style_off') == 'yes') &&
			           (get_option('ckrating_javascript_off') == 'yes')) {
			         $content .= ' '. __('comment rating','ckrating');
			      }
			      else {
			         $content .= ' '.__('comment rating','ckrating').'.';
			      }
			      $content .= " <a href=\"javascript:crSwitchDisplay('ckhide-$ck_comment_ID');\" title=\"".__('Click to see comment','ckrating')."\">".__('Click here to see', 'ckrating')."</a>.</p>" .
			              "<div id='ckhide-$ck_comment_ID' style=\"display:none; ".get_option('ckrating_hide_style').';">' .
			              $text .
			              "</div>";
			   }
			   else if (((int)$arr[1] + (int)$arr[2]) >= (int)get_option('ckrating_debated')) {
			      $content = '<div style="' . get_option('ckrating_style_debated') . '">' .
			               $text .  '</div>';
			   }
							 
				//populating the array			   
			     $total_contents[] = $content. $arr[0] ;
			   
		     endforeach;
		     return $total_contents;
		}
			
				
		//new modified function to add vote logo
		function ckrating_display_content_two($ck_comment){
			   
			global $wpdb;
			$table = $wpdb->prefix . 'comment_rating';
			$ck_comment_ID = $ck_comment->comment_ID ;
				
		    $ck_ratings = $wpdb->get_row("SELECT * FROM $table WHERE `ck_comment_id`='$ck_comment_ID' ");
			   
			$ips = $ck_ratings->ck_ips;
			$ck_ratings_up = $ck_ratings->ck_rating_up;
			$ck_ratings_down = $ck_ratings->ck_rating_down;
						   
			$plugin_path = plugins_url('',__FILE__);
			   
			$ck_link = str_replace('http://', '', get_bloginfo('wpurl'));
			  
		   
		  		
		   
			   $content = '';
			   
			
			   $imgIndex = get_option('ckrating_image_index') . '_' . get_option('ckrating_image_size') . '_';
			   $ip = getenv("HTTP_X_FORWARDED_FOR") ? getenv("HTTP_X_FORWARDED_FOR") : getenv("REMOTE_ADDR");
			   $voteMsg = "";
			   $votedInCookie = false;
			   $votedInID = false;
			   $votedInIP = false;
			   
			    if ( get_option('ckrating_voting_fraud') == 2 ) {
			      $votedInIP = strstr($ips, $ip);  
			   }
			   elseif ( get_option('ckrating_voting_fraud') == 3 ) {
			      if (isset( $_COOKIE['Comment_Rating'])) {
			         $value = $_COOKIE['Comment_Rating'];
			         $cookieIDs = split(',', $value);
			         $votedInCookie = in_array($ck_comment_ID, $cookieIDs);
			      }
			   }
			   // by user ID
			   elseif ( get_option('ckrating_voting_fraud') == 4) {
			      if (is_user_logged_in()) {
			         global $current_user;
			         get_currentuserinfo();
			         $votedInID = strstr($ips, ','.$current_user->ID.'='); 
			      }
			      else
			         $votedInID = true;  // force the icons to be gray.
			   }
			
			   if ( ( get_option('ckrating_logged_in_user') == 'yes' || get_option('ckrating_voting_fraud') == 4)
			          && !is_user_logged_in() )
			   {
			      $imgUp = $imgIndex . "gray_up.png";
			      $imgDown = $imgIndex . "gray_down.png";
			      $imgStyle = 'style="padding: 0px; margin: 0px; border: none;"';
			      $onclick_add = '';
			      $onclick_sub = '';
			      $voteMsg = get_option('ckrating_vote_message');
			   }
			   elseif ( $votedInIP || $votedInCookie || $votedInID )
			   {
			      $imgUp = $imgIndex . "gray_up.png";
			      $imgDown = $imgIndex . "gray_down.png";
			      $imgStyle = 'style="padding: 0px; margin: 0px; border: none;"';
			      $onclick_add = '';
			      $onclick_sub = '';
			   }
			   else {
			      $imgUp = $imgIndex . "up.png";
			      $imgDown = $imgIndex . "down.png";
			      if (get_option('ckrating_mouseover') == 1)
			         // no effect
			         $imgStyle = 'style="padding: 0px; margin: 0px; border: none; cursor: pointer;"';
			      else
			         // enlarge
			         $imgStyle = 'style="padding: 0px; margin: 0px; border: none; cursor: pointer;" onmouseover="this.width=this.width*1.3" onmouseout="this.width=this.width/1.2"';
			//      $onclick_add = "onclick=\"javascript:ckratingKarma('$ck_comment_ID', 'add', '{$ck_link}/wp-content/plugins/".COMMENTRATING_NAME."/', '$imgIndex');\" title=\"". __('Thumb up','ckrating'). "\"";
			//      $onclick_sub = "onclick=\"javascript:ckratingKarma('$ck_comment_ID', 'subtract', '{$ck_link}/wp-content/plugins/".COMMENTRATING_NAME."/', '$imgIndex')\" title=\"". __('Thumb down', 'ckrating') ."\"";
			
			       $onclick_add = "onclick=\"javascript:ckratingKarma('$ck_comment_ID', 'add', '{$ck_link}/wp-content/plugins/".COMMENTRATING_NAME."/', '$imgIndex');\" title=\"". get_option('ckrating_up_alt_text')."\"";
			      $onclick_sub = "onclick=\"javascript:ckratingKarma('$ck_comment_ID', 'subtract', '{$ck_link}/wp-content/plugins/".COMMENTRATING_NAME."/', '$imgIndex')\" title=\"".get_option('ckrating_down_alt_text')."\"";
			
			   }
			
			   $total = $ck_ratings_up - $ck_ratings_down;
			   if ($total > 0) $total = "+$total";
			   //Use onClick for the image instead, fixes the style link underline problem as well.
			   if ( ((int)$ck_ratings_up - (int)$ck_ratings_down)
			           >= (int)get_option('ckrating_goodRate')) {
			      $content .= get_option('ckrating_words_good');
			   }
			   else if ( ((int)$ck_ratings_down - (int)$ck_ratings_up)
			            >= (int)get_option('ckrating_negative')) {
			      $content .= get_option('ckrating_words_poor');
			   }
			   else if ( ((int)$ck_ratings_down + (int)$ck_ratings_up)
			            >= (int)get_option('ckrating_debated')) {
			      $content .= get_option('ckrating_words_debated');
			   }
			   else
			      $content .= get_option('ckrating_words');
			
			   $likesStyle = 'style="' . get_option('ckrating_likes_style') .  ';"';
			   $dislikesStyle = 'style="' . get_option('ckrating_dislikes_style') .  ';"';
			   // apply ckrating_vote_type
			   if ( get_option('ckrating_vote_type') !== 'dislikes' )
			   {
			      $content .= " <img $imgStyle id=\"up-$ck_comment_ID\" src=\"{$plugin_path}/images/$imgUp\" alt=\"".__('Thumb up', 'ckrating') ."\" $onclick_add />";
			      if ( get_option('ckrating_value_display') !== 'one' )
			         $content .= " <span id=\"karma-{$ck_comment_ID}-up\" $likesStyle>{$ck_cache['ck_rating_up']}</span>";
			   }
			   if ( get_option('ckrating_vote_type') !== 'likes' )
			   {
			      $content .= "&nbsp;<img $imgStyle id=\"down-$ck_comment_ID\" src=\"{$plugin_path}/images/$imgDown\" alt=\"". __('Thumb down', 'ckrating')."\" $onclick_sub />"; //Phew
			      if ( get_option('ckrating_value_display') !== 'one' )
			         $content .= " <span id=\"karma-{$ck_comment_ID}-down\" $dislikesStyle>{$ck_cache['ck_rating_down']}</span>";
			   }
			
			   $totalStyle = '';
			   if ($total > 0) $totalStyle = $likesStyle;
			   else if ($total < 0) $totalStyle = $dislikesStyle;
			   if ( get_option('ckrating_value_display') == 'one' )
			      $content .= " <span id=\"karma-{$ck_comment_ID}-total\" $totalStyle>{$total}</span>";
			   if ( get_option('ckrating_value_display') == 'three' )
			      $content .= " (<span id=\"karma-{$ck_comment_ID}-total\" $totalStyle>{$total}</span>)";
			
			   if ( (get_option('ckrating_logged_in_user') == 'yes' || get_option('ckrating_voting_fraud') == 4)
			         && !is_user_logged_in() )
			      $content .= " $voteMsg";
			
			//var_dump($ck_cache);
			//exit;
			
			   return array($content, $ck_ratings_up, $ck_ratings_down);
			}
		
				
	}
	
	$widget_object = new wp_rated_comments_widget();
	//registering the widget
	add_action('widgets_init',array($widget_object,'create_newone')) ;

endif;

?>