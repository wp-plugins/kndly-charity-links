<?php
/*
Plugin Name: Knd.ly Charity Links 
Plugin URI: http://knd.ly/
Description: Checks for links to Amazon products. If links are found they are converted in to clean affiliate links. All money generated through affiliate sales will be donated to the charity of your choice. <b>IMPORTANT NOTE</b>: the default option is for all money earned to be divided evenly between the charities we support. If you would like to support a specific charity (and donate much more money to them) you must choose that charity in the Knd.ly Config page.
Author: Appible LLC
Version: 1.0
Author URI: http://appible.org/
*/

/* Copyright 2009 knd.ly
 
     This program is free software: you can redistribute it and/or modify
     it under the terms of the GNU General Public License as published by
     the Free Software Foundation, either version 3 of the License, or
     (at your option) any later version.

     This program is distributed in the hope that it will be useful,
     but WITHOUT ANY WARRANTY; without even the implied warranty of
     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
     GNU General Public License for more details.

     You should have received a copy of the GNU General Public License
     along with this program.  If not, see <http://www.gnu.org/licenses/>.

 */


add_filter('the_content','filter_kndly_charity_links');
add_filter('comment_text','filter_kndly_charity_links');

function filter_kndly_charity_links($content) {
  $affiliate_code=get_option('kndly_charity_links_id');
  
  $content = preg_replace_callback("/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i", "get_new_links", $content);

  return $content;
}

function get_new_links($url) {
	
	$d = explode("/", $url[0]);
	if($d[2] != "knd.ly"){
		$newurl = "http://knd.ly/api/redirect.php?u=".urlencode($url[0])."&a=".get_option('kndly_charity_links_id')."&d=".$d[2]."&b=".urlencode(get_option('kndly_blog_id'))."&rd=".urlencode(get_option('kndly_blog_domain'))."&t=".urlencode(get_option('kndly_team_id'));
	}else{
		$newurl = $url[0];
	}
	
	// Open the file using the HTTP headers set above
	return $newurl;
}

function set_kndly_charity_links_options () {
  add_option("kndly_charity_links_id","-kndly");
  add_option("kndly_blog_id","Anonymous");
  add_option("kndly_team_id","Knd.ly");
  add_option("kndly_blog_domain","knd.ly");
}
function unset_kndly_charity_links_options () {
  delete_option("kndly_charity_links_id");
}
function modify_menu_kndly_charity_links () {
  add_options_page(
    'Knd.ly Config',         //Title
    'Knd.ly Config',         //Sub-menu title
    'manage_options', //Security
    'kndly-charity-links',         //File to open
    'kndly_charity_links_options'  //Function to call
  );  
}
function kndly_charity_links_options () {
  echo '<div class="wrap"><h2>Knd.ly Charity Links Configuration</h2>';
  if ($_REQUEST['submit']) {
    update_kndly_charity_links_options();
  }
  print kndly_charity_links_form();
  echo '</div>';
}
function update_kndly_charity_links_options() {
  $updated = false;
  $error = '';
  $message = '';
  if ($_REQUEST['kndly_charity_links_id']) {
    update_option('kndly_charity_links_id', $_REQUEST['kndly_charity_links_id']);
    $updated = true;
  }
  if ($_REQUEST['kndly_blog_id'] && $_REQUEST['kndly_blog_id'] != get_option('kndly_blog_id')) {
			$url = 'http://knd.ly/api/checknames.php?field=namestatus&action='.$_REQUEST['kndly_blog_id'];
			//Get the file
			$ch = curl_init();
			// set url
			curl_setopt($ch, CURLOPT_URL, $url);
			//return the transfer as a string
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			// $output contains the output string
			$output = curl_exec($ch);
			// close curl resource to free up system resources
			curl_close($ch);
			if($output != "This name is Avaliable!"){
				$error .= "Blog ".$output." ";
			}else{
		  		update_option('kndly_blog_id', $_REQUEST['kndly_blog_id']);
		  		$updated = true;
			}
  }
  if ($_REQUEST['kndly_team_id'] && $_REQUEST['kndly_team_id'] != get_option('kndly_team_id')) {
			$url = 'http://knd.ly/api/checknames.php?field=teamstatus&action='.$_REQUEST['kndly_team_id'];
			//Get the file
			$ch = curl_init();
			// set url
			curl_setopt($ch, CURLOPT_URL, $url);
			//return the transfer as a string
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			// $output contains the output string
			$output = curl_exec($ch);
			// close curl resource to free up system resources
			curl_close($ch);
			if($output != "This name is Avaliable!" || $output != "name is inappropriate."){
		  		update_option('kndly_team_id', $_REQUEST['kndly_team_id']);
		  		$updated = true;
				if($output != "This name is Avaliable!") $message .= "Team ".$output." ";
				if($output == "This name is Avaliable!") $message .= "You have started the team \"".$_REQUEST['kndly_team_id']."\".";
			}else{
				$error .=  "Team ".$output." ";
		}
  }
  if ($updated) {
    echo '<div id="message" class="updated fade">';
    echo '<p>Configuration Updated</p>';
	if($message) echo '<p>'.$message.'</p>';
    echo '</div>';
	if($error){
		echo '<div id="message" class="error fade">';
		echo '<p>Unable to update some options</p>';
		if($error) echo '<p>'.$error.'</p>';
		echo '</div>';
	}
	
  } else {
    echo '<div id="message" class="error fade">';
    echo '<p>Unable to update options</p>';
	if($error) echo '<p>'.$error.'</p>';
    echo '</div>';
  }
}

function kndly_charity_links_form () {
  $charity_id = get_option('kndly_charity_links_id');
  $form='
    <form method="post">
    <h3>Choose a Charity:</h3>
	<div style="margin-left:20px; line-height:2.25;font-size:1.2em;">  
    <input type="radio" name="kndly_charity_links_id" value="-kndly-alz" ';
  if($charity_id == "-kndly-alz") $form .= 'checked';
  $form .= '/> <a href="http://alz.org/">Alzheimer\'s Association</a><br/>
  
      <input type="radio" name="kndly_charity_links_id" value="-kndly-amcancer" ';
  if($charity_id == "-kndly-amcancer") $form .= 'checked';
  $form .= '/> <a href="http://cancer.org/">American Cancer Society</a><br/>
    
    <input type="radio" name="kndly_charity_links_id" value="-kndly-catholicrelief" ';
  if($charity_id == "-kndly-catholicrelief") $form .= 'checked';
  $form .= '/> <a href="http://crs.org/">Catholic Relief Services</a><br/>
    
    <input type="radio" name="kndly_charity_links_id" value="-kndly-cdls" ';
  if($charity_id == "-kndly-cdls") $form .= 'checked';
  $form .= '/> <a href="http://cdlsusa.org/">CdLS-USA Foundation</a><br/>
  
    <input type="radio" name="kndly_charity_links_id" value="-kndly-habitat" ';
  if($charity_id == "-kndly-habitat") $form .= 'checked';
  $form .= '/> <a href="http://habitat.org/">Habitat for Humanity</a><br/>
  
    <input type="radio" name="kndly_charity_links_id" value="-kndly-kiva" ';
  if($charity_id == "-kndly-kiva") $form .= 'checked';
  $form .= '/> <a href="http://kiva.org/">Kiva</a><br/>

    <input type="radio" name="kndly_charity_links_id" value="-kndly-lds" ';
  if($charity_id == "-kndly-lds") $form .= 'checked';
  $form .= '/> <a href="http://ldsphilanthropies.org/">LDS Philanthropies</a><br/>
  
    <input type="radio" name="kndly_charity_links_id" value="-kndly-mercycorps" ';
  if($charity_id == "-kndly-mercycorps") $form .= 'checked';
  $form .= '/> <a href="http://mercycorps.org/">  Mercy Corps</a><br/>
  
    <input type="radio" name="kndly_charity_links_id" value="-kndly-red" ';
  if($charity_id == "-kndly-red") $form .= 'checked';
  $form .= '/> <a href="http://joinred.com/">(RED)</a><br/>
  
    <input type="radio" name="kndly_charity_links_id" value="-kndly-redcross" ';
  if($charity_id == "-kndly-redcross") $form .= 'checked';
  $form .= '/> <a href="http://redcross.org/en/">Red Cross</a><br/>
      
    <input type="radio" name="kndly_charity_links_id" value="-kndly-salvationarmy" ';
  if($charity_id == "-kndly-salvationarmy") $form .= 'checked';
  $form .= '/> <a href="http://salvationarmyusa.org/">Salvation Army</a><br/>
  
    <input type="radio" name="kndly_charity_links_id" value="-kndly-komen" ';
  if($charity_id == "-kndly-komen") $form .= 'checked';
  $form .= '/> <a href="http://komen.org/">Susan G. Komen Foundation</a><br/>
  
    <input type="radio" name="kndly_charity_links_id" value="-kndly-unitedway" ';
  if($charity_id == "-kndly-unitedway") $form .= 'checked';
  $form .= '/> <a href="http://liveunited.org/">United Way</a><br/>


  <br/>
    <input type="radio" name="kndly_charity_links_id" value="-kndly" ';
  if($charity_id == "-kndly") $form .= 'checked';
  $form .= '/> Divide evenly among all the charities<br/>
    <br />
	Blog Name:<input type="text" name="kndly_blog_id" value="'.get_option('kndly_blog_id').'" />
	<br/><small>This is how we track our most successful linkers. This is not required if you wouldn\'t like to compete against other bloggers.</small>
	<br/>
	Team Name:<input type="text" Name="kndly_team_id" value="'.get_option('kndly_team_id').'" />
	<br/><small>Join forces with other bloggers to build a team and compete against other teams to become the most Knd.ly bloggers. If you don\'t join a team you will be counted toward the team "Knd.ly"</small>
	</div>
	<br/>
    <input type="submit" name="submit" value="Submit" />
    </form>
    <p>At Knd.ly we are dedicated to making the world better for everyone. Because there\'s no "I" in kindness.</p><p>We promise that 75% of all revenue generated through the use of this plugin will be donated to the charity of your choice. We keep 25% to cover our overhead and keep the lights on.</p><p><small>Knd.ly is a service of Appible LLC, a for-profit company. To learn more about how Knd.ly works and why we do it visit us at <a href="http://knd.ly/about">http://knd.ly/about</a> to learn more.</small></p>';
  return $form;
}

//Add "Settings" link to plugins page
function kndly_filter_plugin_actions($links, $file) {
   static $this_plugin;
   if( ! $this_plugin ) $this_plugin = plugin_basename(__FILE__);

   if( $file == $this_plugin ){
      $settings_link = '<a href="options-general.php?page=kndly-charity-links">' . __('Settings') . '</a>';
      array_unshift( $links, $settings_link );
   }
   return $links;
}

add_filter( 'plugin_action_links', 'kndly_filter_plugin_actions', 10, 2 );

//Add Sidebar Badge Widget
function kndly_badge_display() {
	echo '<a href = "http://knd.ly/badge-ref"><img src="'.WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)).'kndly_badge.png" /></a>';
}
 
function init_kndly_badge_display(){
	register_sidebar_widget("Knd.ly Badge", "kndly_badge_display");     
}
 
add_action("plugins_loaded", "init_kndly_badge_display");


add_action('admin_menu','modify_menu_kndly_charity_links');
register_activation_hook(__FILE__,"set_kndly_charity_links_options");

?>