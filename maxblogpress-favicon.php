<?php
/*
 * Plugin Name:   MaxBlogPress Favicon
 * Version:       2.0.9
 * Plugin URI:    http://www.maxblogpress.com/plugins/mfi/
 * Description:   Easily add favicon to your blog without editing any wordpress files. Adjust your settings <a href="options-general.php?page=maxblogpress-favicon/maxblogpress-favicon.php">here</a>.
 * Author:        MaxBlogPress
 * Author URI:    http://www.maxblogpress.com
 */
 
define('MBPFAVICON_NAME', 'MaxBlogPress Favicon');	// Name of the Plugin
define('MBPFAVICON_VERSION', '2.0.9');				// Current version of the Plugin
$mfi_abspath = str_replace("\\","/",ABSPATH);       // required for Windows
define('MFI_ABSPATH', $mfi_abspath);

/**
 * MBPFavIcon - MaxBlogPress Favicon Class
 * Holds all the necessary functions and variables
 */
class MBPFavIcon 
{
    /**
     * MaxBlogPress Favicon plugin path
     * @var string
     */
	var $mbpfavicon_path = '';
	
    /**
     * MaxBlogPress Favicon plugin's icon directory absolute path
     * @var string
     */
	var $mbpfavicon_fullpath = '';
	
    /**
     * MaxBlogPress Favicon plugin's icon directory full path
     * @var string
     */
	var $mbpfavicon_dir_path = '';
	
    /**
     * MaxBlogPress Favicon image
     * @var string
     */
	var $mbpfavicon = '';

	/**
	 * Constructor. Adds MaxBlogPress Favicon plugin's actions/filters.
	 * @access public
	 */
	function MBPFavIcon() { 
		global $wp_version;
		$this->mbpfavicon_path     = preg_replace('/^.*wp-content[\\\\\/]plugins[\\\\\/]/', '', __FILE__);
		$this->mbpfavicon_path     = str_replace('\\','/',$this->mbpfavicon_path);
		$this->siteurl             = get_bloginfo('wpurl');
		$this->siteurl             = (strpos($this->siteurl,'http://') === false) ? get_bloginfo('siteurl') : $this->siteurl;
		$this->mfi_fullpath        = $this->siteurl.'/wp-content/plugins/'.substr($this->mbpfavicon_path,0,strrpos($this->mbpfavicon_path,'/')).'/';
		$this->mbpfavicon_fullpath = $this->mfi_fullpath.'icons/';
		$curr_path  		       = str_replace("\\", "/", __FILE__);
		$this->mbpfavicon_dir_path = substr($curr_path, 0, strrpos($curr_path,'/')).'/icons/';
		$this->img_how             = '<img src="'.$this->mfi_fullpath.'images/how.gif" border="0" align="absmiddle">';
		$this->img_comment         = '<img src="'.$this->mfi_fullpath.'images/comment.gif" border="0" align="absmiddle">';

	    add_action('activate_'.$this->mbpfavicon_path, array(&$this, 'mbpfaviconActivate'));
		add_action('admin_menu', array(&$this, 'mbpfaviconAddMenu'));
		$this->mfi_activate = get_option('mfi_activate');
		$this->mbpfavicon = get_option('mbp_favicon');
		$this->mfi_code_inj_mode = get_option('mfi_code_inj_mode');
		if ( $this->mfi_activate == 2 && $this->mbpfavicon != '' ) {
			if ( $wp_version < 2.1 ) {
				add_action('wp_head', array(&$this, 'mbpfaviconAdd'), 99);
			} else {
				if ( $this->mfi_code_inj_mode != 1 ) {
					add_filter('get_header', array(&$this, 'mbpfaviconAddStart'), 99);
				}
				add_action('wp_head', array(&$this, 'mbpfaviconAddStart'), 99);
				add_filter('get_footer', array(&$this, 'mbpfaviconAddEnd'), 99);
			}
			add_action('template_redirect', array(&$this, 'mbpfaviconRedirect'));
			add_action('admin_head', array(&$this, 'mbpfaviconAdd'));
			add_action('rss2_head', array(&$this, 'mbpfaviconAddToRSS2Feed'));
			add_action('atom_head', array(&$this, 'mbpfaviconAddToAtomFeed'));
		}		
	}
	
	/**
	 * Called when plugin is activated. Adds option value to the options table.
	 * @access public
	 */
	function mbpfaviconActivate() {
		add_option('mfi_activate', 0);
		add_option('mbp_favicon', '', 'MaxBlogPress Favicon plugin option', 'no');
		return true;
	}
	
	/**
	 * Returns the correct favicon path
	 * @access public
	 */
	function mbpfaviconRedirect() {
		global $wpdb;
		if ( is_404() && strpos($_SERVER['REQUEST_URI'],'favicon.ico') !== false ) {
			$favicon_path = get_option('mbp_favicon');
			header( "Location: $favicon_path" );
		}
	}
	
	/**
	 * Returns the type/extension of image
	 * @access public
	 */
	function mbpfaviconType($faviconpath) {
		if ( preg_match("/\.gif$/i", $faviconpath) )     return 'gif';
		if ( preg_match("/\.ico$/i", $faviconpath) ) 	 return 'x-icon';
		if ( preg_match("/\.jp[e]?g$/i", $faviconpath) ) return 'jpg';
		if ( preg_match("/\.png$/i", $faviconpath) )	 return 'png';
		else return '';
	}
	
	/**
	 * Adds favicon to the rss2 feed
	 * @access public
	 */
	function mbpfaviconAddToRSS2Feed() {
		$mfi_rss  = '<image>'."\n";
		$mfi_rss .= '<link>'.get_bloginfo_rss('url').'</link>'."\n";
		$mfi_rss .= '<url>'.$this->mbpfavicon.'</url>'."\n";
		$mfi_rss .= '<title>'.get_bloginfo_rss('name').'</title>'."\n";
		$mfi_rss .= '</image>'."\n";
		echo $mfi_rss;
	}
	
	/**
	 * Adds favicon to the atom feed
	 * @access public
	 */
	function mbpfaviconAddToAtomFeed() {
		$mfi_atom  = '<icon>'.$this->mbpfavicon.'</icon>'."\n";
		$mfi_atom .= '<logo>'.$this->mbpfavicon.'</logo>'."\n";
		echo $mfi_atom;
	}
	
	/**
	 * Adds favicon
	 * @access public
	 */
	function mbpfaviconAdd($not_ver20='') {
		$favicon_type = $this->mbpfaviconType($this->mbpfavicon);
		if ( trim($favicon_type) != '' ) {
			$mfi_favicon = '<link rel="shortcut icon" href="'.$this->mbpfavicon.'" type="image/'.$favicon_type.'" />';
		} else {
			$mfi_favicon = '<link rel="shortcut icon" href="'.$this->mbpfavicon.'" />';
		}
		if ( $not_ver20 != '' ) {
			return $mfi_favicon;
		} else {
			echo "\n".$mfi_favicon."\n";
		}
	}
	
	/**
	 * Start Output Buffer
	 * @access public
	 */
	function mbpfaviconAddStart() {
		if ( $this->mfi_header_executed != 1 ) {
			$this->mfi_header_executed = 1;
			if ( $this->mfi_code_inj_mode != 1 ) {
				ob_start();
			} else {
				$mfi_favicon = $this->mbpfaviconAdd(1);
				echo $mfi_favicon;
			}
		}
	}
	
	/**
	 * Adds favicon. Gets content from output buffer and displays
	 * @access public
	 */
	function mbpfaviconAddEnd() {
		if ( $this->mfi_code_inj_mode != 1 ) {
			$mfi_output  = ob_get_contents();
			ob_end_clean();
			$mfi_favicon = $this->mbpfaviconAdd(1);
			$mfi_output = str_replace("</head>", "\n $mfi_favicon \n </head>", $mfi_output);
			echo $mfi_output;
		} 
	}

	/**
	 * Adds "MBP Favicon" link to admin Options menu
	 * @access public 
	 */
	function mbpfaviconAddMenu() {
		add_options_page('MaxBlogPress Favicon', 'MBP Favicon', 'manage_options', $this->mbpfavicon_path, array(&$this, 'mbpfaviconOptionsPg'));
	}
	
	/**
	 * Creates favicon directory to upload icons
	 * @access public 
	 */
	function mbpfaviconMakeDir($mfi_dir) {
		$mfi_upload_path = MFI_ABSPATH.'wp-content/'.$mfi_dir;
		if ( is_admin() && !is_dir($mfi_upload_path) ) {
			@mkdir($mfi_upload_path);
		}
		return $mfi_upload_path;
	}
	
	/**
	 * Page Header
	 */
	function mfiHeader() {
		if ( !isset($_GET['dnl']) ) {	
			$mfi_version_chk = $this->mfiRecheckData();
			if ( ($mfi_version_chk == '') || strtotime(date('Y-m-d H:i:s')) > (strtotime($mfi_version_chk['last_checked_on']) + $mfi_version_chk['recheck_interval']*60*60) ) {
				$update_arr = $this->mfiExtractUpdateData();
				if ( count($update_arr) > 0 ) {
					$latest_version   = $update_arr[0];
					$recheck_interval = $update_arr[1];
					$download_url     = $update_arr[2];
					$msg_in_plugin    = $update_arr[3];
					$msg_in_plugin    = $update_arr[4];
					$upgrade_url      = $update_arr[5];
					if( MBPFAVICON_VERSION < $latest_version ) {
						$mfi_version_check = array('recheck_interval' => $recheck_interval, 'last_checked_on' => date('Y-m-d H:i:s'));
						$this->mfiRecheckData($mfi_version_check);
						$msg_in_plugin = str_replace("%latest-version%", $latest_version, $msg_in_plugin);
						$msg_in_plugin = str_replace("%plugin-name%", MBPFAVICON_NAME, $msg_in_plugin);
						$msg_in_plugin = str_replace("%upgrade-url%", $upgrade_url, $msg_in_plugin);
						$msg_in_plugin = '<div style="border-bottom:1px solid #CCCCCC;background-color:#FFFEEB;padding:6px;font-size:11px;text-align:center">'.$msg_in_plugin.'</div>';
					} else {
						$msg_in_plugin = '';
					}
				}
			}
		}
		echo '<h2>'.MBPFAVICON_NAME.' '.MBPFAVICON_VERSION.'</h2>';
		if ( trim($msg_in_plugin) != '' && !isset($_GET['dnl']) ) echo $msg_in_plugin;
		echo '<br /><strong>'.$this->img_how.' <a href="http://www.maxblogpress.com/plugins/mfi/mfi-use/" target="_blank">How to use it</a>&nbsp;&nbsp;&nbsp;'; 
        echo $this->img_comment.' <a href="http://www.maxblogpress.com/plugins/mfi/mfi-comments/" target="_blank">Comments and Suggestions</a></strong><br /><br />';
	}
	
	/**
	 * Page Footer
	 */
	function mfiFooter() {
		echo '<p style="text-align:center;margin-top:3em;"><strong>'.MBPFAVICON_NAME.' '.MBPFAVICON_VERSION.' by <a href="http://www.maxblogpress.com/" target="_blank" >MaxBlogPress</a></strong></p>';
	}
	
	/**
	 * Displays the page content for "MBP Favicon" Options submenu
	 * Carries out all the operations in Options page
	 * @access public 
	 */
	function mbpfaviconOptionsPg() {
		global $wpdb;
		$this->mbpfavicon_request = $_REQUEST['mbpfavicon'];
		
		$form_1 = 'mfi_reg_form_1';
		$form_2 = 'mfi_reg_form_2';
		// Activate the plugin if email already on list
		if ( trim($_GET['mbp_onlist']) == 1 ) { 
			$this->mfi_activate = 2;
			update_option('mfi_activate', $this->mfi_activate);
			$msg = 'Thank you for registering the plugin. It has been activated'; 
		} 
		// If registration form is successfully submitted
		if ( ((trim($_GET['submit']) != '' && trim($_GET['from']) != '') || trim($_GET['submit_again']) != '') && $this->mfi_activate != 2 ) { 
			update_option('mfi_name', $_GET['name']);
			update_option('mfi_email', $_GET['from']);
			$this->mfi_activate = 1;
			update_option('mfi_activate', $this->mfi_activate);
		}
		if ( intval($this->mfi_activate) == 0 ) { // First step of plugin registration
			$this->mfiRegister_1($form_1);
		} else if ( intval($this->mfi_activate) == 1 ) { // Second step of plugin registration
			$name  = get_option('mfi_name');
			$email = get_option('mfi_email');
			$this->mfiRegister_2($form_2,$name,$email);
		} else if ( intval($this->mfi_activate) == 2 ) { // Options page
			if ( $_GET['action'] == 'upgrade' ) {
				$this->mfiUpgradePlugin();
				exit;
			}
			if ( $this->mbpfavicon_request['save_more'] ) {
				$mfi_code_inj_mode = $this->mbpfavicon_request['mfi_code_inj_mode'];
				update_option('mfi_code_inj_mode', $mfi_code_inj_mode);
				echo '<div id="message" class="updated fade"><p><strong>Options Saved Successfully.</strong></p></div>';
			} else if ( $this->mbpfavicon_request['save'] ) {
				$success = '';
				$mfi_dir = 'mbp-favicon';
				$mfi_valid_file = array("image/x-icon", "image/png", "image/jpeg", "image/pjpeg", "image/gif", "image/bmp");
				if ( $this->mbpfavicon_request['link_type'] == 3 ) { // Upload from URL
					$mfi_upload_path = $this->mbpfaviconMakeDir($mfi_dir);
					$mfi_src_url     = trim($this->mbpfavicon_request['favicon_upload_2']);
					$mfi_src_info    = pathinfo($mfi_src_url);
					$mfi_src_file    = $mfi_src_info['basename'];
					$mfi_src_ext     = $mfi_src_info['extension'];
					if ( $mfi_src_ext == 'ico' || $mfi_src_ext == 'jpg' || $mfi_src_ext == 'gif' || $mfi_src_ext == 'png' || $mfi_src_ext == 'bmp' ) {
						$mfi_dest_url    = $mfi_upload_path.'/'.$mfi_src_file;
						$mfi_favicon_url = $this->siteurl.'/wp-content/'.$mfi_dir.'/'.$mfi_src_file; 
						if ( ini_get('allow_url_fopen') ) {
							@copy($mfi_src_url, $mfi_dest_url);
							$success = 1;
						} else if ( extension_loaded('curl') ) {
							$ch = curl_init();
							curl_setopt($ch, CURLOPT_URL, $mfi_src_url);
							curl_setopt($ch, CURLOPT_HEADER, false);
							curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
							curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
							set_time_limit(300); // 5 minutes for PHP
							curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutes for CURL
							$mfi_outfile = fopen($mfi_dest_url, 'wb');
							curl_setopt($ch, CURLOPT_FILE, $mfi_outfile);
							curl_exec($ch);
							fclose($mfi_outfile);
							curl_close($ch); 	
							$success = 1;
						} else {
							$mfi_msg = "Favicon couldn't be uploaded from URL. 'URL file-access' and/or 'CURL' are/is disabled in your server.";
						}
					} else {
						$mfi_msg = 'Favicon couldn\'t be uploaded from URL. Invalid file type';
					}
					if ( $success == 1 ) {
						$this->mbpfavicon = $mfi_favicon_url;
						$mfi_msg = 'Favicon uploaded from URL and activated.';
					} else {
						$mfi_msg = 'Favicon couldn\'t be uploaded from URL.';
					}
				} else if ( $this->mbpfavicon_request['link_type'] == 2 ) { // Upload from local computer
					$mfi_upload_path   = $this->mbpfaviconMakeDir($mfi_dir);
					$upload_1_name     = $_FILES['favicon_upload_1']['name'];
					$upload_1_type     = $_FILES['favicon_upload_1']['type'];
					$upload_1_size     = $_FILES['favicon_upload_1']['size'];
					$upload_1_tmp_name = $_FILES['favicon_upload_1']['tmp_name'];
					$mfi_favicon_path  = $mfi_upload_path.'/'.$upload_1_name;
					$mfi_favicon_url   = $this->siteurl.'/wp-content/'.$mfi_dir.'/'.$upload_1_name; 
					if ( in_array($upload_1_type,$mfi_valid_file) ) {
						if ( move_uploaded_file($upload_1_tmp_name, $mfi_favicon_path) ) {
							$mfi_msg = 'Favicon uploaded from local computer and activated.';
						} else {
							$mfi_msg = 'Favicon couldn\'t be uploaded from local computer.';
						}
					} else {
						$mfi_msg = 'Favicon couldn\'t be uploaded from local computer. Invalid file type.';
					}
					$this->mbpfavicon = $mfi_favicon_url;
				} else { // Use own link
					$this->mbpfavicon = $this->mbpfavicon_request['favicon'];
					$mfi_msg = 'Favicon activated.';
				}
				update_option("mbp_favicon", $this->mbpfavicon);
				echo '<div id="message" class="updated fade"><p><strong>'. __($mfi_msg, 'mbpfavicon') .'</strong></p></div>';
			}
			// create an array of icons
			$icons_array = array();
			if ( $dir = opendir($this->mbpfavicon_dir_path) ) {
				while ( ($file = readdir($dir)) !== false ) {
					if ( $file != "." && $file != ".." ) {
						$file_info = pathinfo($file);
						if ( $file_info['extension'] == 'ico' )
							$icons_array[] = $file;
					}
				}
				closedir($dir);
			}
			if ( trim($this->mbpfavicon) == '' ) {
				$mbpfavicon_curr_img = $this->mbpfavicon_fullpath.'1x1.gif';
			} else {
				$mbpfavicon_curr_img = $this->mbpfavicon;
			}
			if ( strpos($_SERVER['HTTP_USER_AGENT'],'MSIE') === false ) { 
				$icon_span_style = 'style="padding:4px 5px 6px 5px;background-color:#dddddd;"';
				$icon_style = '';
			} else {
				$icon_span_style = 'style="background-color:#dddddd;"';
				$icon_style = 'style="padding:5px 4px 4px 4px;"';
			}
			$mfi_code_inj_mode = get_option('mfi_code_inj_mode');
			if ( $mfi_code_inj_mode == 1 ) $mfi_code_inj_mode_1_chk = 'checked';
			else $mfi_code_inj_mode_2_chk = 'checked';
			?>
			<script type="text/javascript"><!--
			function selectIcon(icon) {
				document.getElementById('mbpfavicon_curr').value   = icon.src;
				document.getElementById('mbpfavicon_curr_img').src = icon.src;
			}
			function bgColorAlter(cell, onfocus) {
				if ( onfocus == 1 ) {
					cell.style.backgroundColor = '#dddddd';
				} else {
					cell.style.backgroundColor = '#f1f1f1';
				}
			}
			function mfiSwitchType(curr) {
				var mfi_type_1  = document.getElementById('mfi_type_1');
				var mfi_type_2  = document.getElementById('mfi_type_2');
				var mfi_type_3  = document.getElementById('mfi_type_3');
				var mfi_icon    = document.getElementById('mfi_icon');
				var mfi_icon_td = document.getElementById('mfi_icon_td');
				if ( curr == 1 ) {
				    mfi_icon_td.style.width  = '6%';
					mfi_icon.style.display   = 'block';
					mfi_type_1.style.display = 'block';
					mfi_type_2.style.display = 'none';
					mfi_type_3.style.display = 'none';
				} else if ( curr == 2 ) {
				    mfi_icon_td.style.width  = '1%';
					mfi_icon.style.display   = 'none';
					mfi_type_1.style.display = 'none';
					mfi_type_2.style.display = 'block';
					mfi_type_3.style.display = 'none';
				} else if ( curr == 3 ) {
				    mfi_icon_td.style.width  = '1%';
					mfi_icon.style.display   = 'none';
					mfi_type_1.style.display = 'none';
					mfi_type_2.style.display = 'none';
					mfi_type_3.style.display = 'block';
				}
			}
			function mfiShowHide(Div, Img) {
				var divCtrl = document.getElementById(Div);
				var theImg = document.getElementById(Img);
				if ( divCtrl.style == '' || divCtrl.style.display == 'none' ) {
					divCtrl.style.display = 'block';
					theImg.src = '<?php echo $this->mfi_fullpath;?>images/minus.gif';
				} else if ( divCtrl.style != '' || divCtrl.style.display == 'block' ) {
					divCtrl.style.display = 'none';
					theImg.src = '<?php echo $this->mfi_fullpath;?>images/plus.gif';
				}
			}//--></script>
			<div class="wrap">
			 <?php $this->mfiHeader(); ?>
			 <form method="post" action="" enctype="multipart/form-data">
			 <p>
			 <table border="0" width="100%" cellpadding="1" cellspacing="1" style="background-color:#ffffff;">
			  <tr>
			   <td>&nbsp;</td>
			   <td>
			   <input type="radio" name="mbpfavicon[link_type]" id="link_type_1" value="1" <?php echo 'checked';?> onclick="mfiSwitchType(1)" /> Favicon Link &nbsp;    
			   <input type="radio" name="mbpfavicon[link_type]" id="link_type_2" value="2" onclick="mfiSwitchType(2)" /> Upload Favicon From My Computer &nbsp;     
			   <input type="radio" name="mbpfavicon[link_type]" id="link_type_3" value="3" onclick="mfiSwitchType(3)" /> Upload From URL &nbsp; 
			   </td>
			  </tr>
			  <tr>
			   <td width="105"><strong><?php _e('Favorite Icon', 'mbpfavicon'); ?>: </strong></td>
			   <td>
			    <table width="100%" border="0">
				 <tr>
				  <td width="1%" id="mfi_type_1" style="display:block">
			      <input type="text" name="mbpfavicon[favicon]" id="mbpfavicon_curr" value="<?php echo $this->mbpfavicon;?>" size="45">
			      </td>
				  <td width="1%" id="mfi_type_2" style="display:none">
				  <input type="file" name="favicon_upload_1" id="favicon_upload_1" value="" size="25">
				  </td>
				  <td width="1%" id="mfi_type_3" style="display:none">
			      <input type="text" name="mbpfavicon[favicon_upload_2]" id="favicon_upload_2" value="" size="45">
				  </td>
				  <td id="mfi_icon_td" width="6%">
			      <span id="mfi_icon" <?php echo $icon_span_style;?>><img src="<?php echo $mbpfavicon_curr_img;?>" id="mbpfavicon_curr_img" width="16" height="16" border="0" align="absmiddle" <?php echo $icon_style;?> /></span>
				  </td>
				  <td width="91%">
				  <input type="submit" name="mbpfavicon[save]" value="<?php _e('Save', 'mbpfavicon'); ?>" class="button" />
				  </td>
                 </tr>
				</table>
			   </td>
			  </tr>
			 </table>
			 </p>
			 <p>
			 <strong><?php _e('Choose the favorite icon you want to use', 'mbpfavicon'); ?>: </strong>
			 <table border="0" width="300" cellpadding="8" cellspacing="3" bgcolor="#ffffff">
			 <?php 
			 $i = 0;
			 foreach ( $icons_array as $icon ) { 
				if ( $i == 0 ) print '<tr>';
				else if ( $i%10 == 0 ) print '</tr><tr>';
				$i++;
				print '<td style="background-color:#f1f1f1; width:50px; height:16px; text-align:center; padding:8px;" onmouseover="bgColorAlter(this,1);" onmouseout="bgColorAlter(this,0);"><img src="'.$this->mbpfavicon_fullpath.$icon.'" onclick="selectIcon(this);" style="cursor:hand;cursor:pointer;border:0"></td>';
			 } 
			 ?>
			 </table>
			 </p>
			 <p>
			 <strong><?php _e('Not happy with the above icons? You can get more free icons from these websites', 'mbpfavicon'); ?>: </strong><br />
			 <a href="http://www.vistaicons.com/" target="_blank">Vista Icons</a><br />
			 <a href="http://www.famfamfam.com/" target="_blank">FAMFAMFAM</a><br />
			 <a href="http://www.free-icons.co.uk/" target="_blank">Free Icons</a><br />
			 <a href="http://www.iconarchive.com/" target="_blank">Icon Archive</a><br />
			 </p>
			 <span style="font-size:14px;font-weight:bold;"><a onclick="mfiShowHide('mfi_adv_opt','mfi_adv_opt_img');" style="cursor:hand;cursor:pointer"><img src="<?php echo $this->mfi_fullpath;?>images/plus.gif" id="mfi_adv_opt_img" border="0" /><strong>Advanced Options (optional)</strong></a></span>
			 <div id="mfi_adv_opt" style="display:none">
			 <table border="0" width="60%" cellspacing="2" cellpadding="5" style="border:1px solid #dddddd; background-color:#f1f1f1; padding:0;">
			  <tr>
			   <td style="background-color:#f1f1f1" width="45%"><strong>Plugin code injecting mode:</strong></td>
			   <td style="background-color:#f1f1f1">
			   <input type="radio" name="mbpfavicon[mfi_code_inj_mode]" value="1" <?php echo $mfi_code_inj_mode_1_chk;?> /> wp_head() &nbsp;&nbsp;&nbsp;&nbsp;
			   <input type="radio" name="mbpfavicon[mfi_code_inj_mode]" value="2" <?php echo $mfi_code_inj_mode_2_chk;?> /> Buffer Caching</td>
			  </tr>
			  <tr>
			   <td style="background-color:#ffffff" colspan="3"><input type="submit" class="button" name="mbpfavicon[save_more]" value="Save" /></td>
			  </tr>
			 </table>
			 </div>
			 </form>
			 <?php $this->mfiFooter(); ?>
			</div>
			<?php
		}
	}
	
	/**
	 * Gets recheck data fro displaying auto upgrade information
	 */
	function mfiRecheckData($data='') {
		if ( $data != '' ) {
			update_option('mfi_version_check',$data);
		} else {
			$version_chk = get_option('mfi_version_check');
			return $version_chk;
		}
	}
	
	/**
	 * Extracts plugin update data
	 */
	function mfiExtractUpdateData() {
		$arr = array();
		$version_chk_file = "http://www.maxblogpress.com/plugin-updates/maxblogpress-favicon.php?v=".MBPFAVICON_VERSION;
		$content = wp_remote_fopen($version_chk_file);
		if ( $content ) {
			$content          = nl2br($content);
			$content_arr      = explode('<br />', $content);
			$latest_version   = trim(trim(strstr($content_arr[0],'~'),'~'));
			$recheck_interval = trim(trim(strstr($content_arr[1],'~'),'~'));
			$download_url     = trim(trim(strstr($content_arr[2],'~'),'~'));
			$msg_plugin_mgmt  = trim(trim(strstr($content_arr[3],'~'),'~'));
			$msg_in_plugin    = trim(trim(strstr($content_arr[4],'~'),'~'));
			$upgrade_url      = $this->siteurl.'/wp-admin/options-general.php?page='.$this->mbpfavicon_path.'&action=upgrade&dnl='.$download_url;
			$arr = array($latest_version, $recheck_interval, $download_url, $msg_plugin_mgmt, $msg_in_plugin, $upgrade_url);
		}
		return $arr;
	}
	
	/**
	 * Interface for upgrading plugin
	 */
	function mfiUpgradePlugin() {
		global $wp_version;
		$plugin = $this->mbpfavicon_path;
		echo '<div class="wrap">';
		$this->mfiHeader();
		echo '<h3>Upgrade Plugin &raquo;</h3>';
		if ( $wp_version >= 2.5 ) {
			$res = $this->mfiDoPluginUpgrade($plugin);
		} else {
			echo '&raquo; Wordpress 2.5 or higher required for automatic upgrade.<br><br>';
		}
		if ( $res == false ) echo '&raquo; Plugin couldn\'t be upgraded.<br><br>';
		echo '<br><strong><a href="'.$this->siteurl.'/wp-admin/plugins.php">Go back to plugins page</a> | <a href="'.$this->siteurl.'/wp-admin/options-general.php?page='.$this->mbpfavicon_path.'">'.MBPFAVICON_NAME.' home page</a></strong>';
		$this->mfiFooter();
		echo '</div>';
		include('admin-footer.php');
	}
	
	/**
	 * Carries out plugin upgrade
	 */
	function mfiDoPluginUpgrade($plugin) {
		set_time_limit(300);
		global $wp_filesystem;
		$debug = 0;
		$was_activated = is_plugin_active($plugin); // Check current status of the plugin to retain the same after the upgrade

		// Is a filesystem accessor setup?
		if ( ! $wp_filesystem || !is_object($wp_filesystem) ) {
			WP_Filesystem();
		}
		if ( ! is_object($wp_filesystem) ) {
			echo '&raquo; Could not access filesystem.<br /><br />';
			return false;
		}
		if ( $wp_filesystem->errors->get_error_code() ) {
			echo '&raquo; Filesystem error '.$wp_filesystem->errors.'<br /><br />';
			return false;
		}
		
		if ( $debug ) echo '> File System Okay.<br /><br />';
		
		// Get the URL to the zip file
		$package = $_GET['dnl'];
		if ( empty($package) ) {
			echo '&raquo; Upgrade package not available.<br /><br />';
			return false;
		}
		// Download the package
		$file = download_url($package);
		if ( is_wp_error($file) || $file == '' ) {
			echo '&raquo; Download failed. '.$file->get_error_message().'<br /><br />';
			return false;
		}
		$working_dir = MFI_ABSPATH . 'wp-content/upgrade/' . basename($plugin, '.php');
		
		if ( $debug ) echo '> Working Directory = '.$working_dir.'<br /><br />';
		
		// Unzip package to working directory
		$result = $this->mfiUnzipFile($file, $working_dir);
		if ( is_wp_error($result) ) {
			unlink($file);
			$wp_filesystem->delete($working_dir, true);
			echo '&raquo; Couldn\'t unzip package to working directory. Make sure that "/wp-content/upgrade/" folder has write permission (CHMOD 755).<br /><br />';
			return $result;
		}
		
		if ( $debug ) echo '> Unzip package to working directory successful<br /><br />';
		
		// Once extracted, delete the package
		unlink($file);
		if ( is_plugin_active($plugin) ) {
			deactivate_plugins($plugin, true); //Deactivate the plugin silently, Prevent deactivation hooks from running.
		}
		
		// Remove the old version of the plugin
		$plugin_dir = dirname(MFI_ABSPATH . PLUGINDIR . "/$plugin");
		$plugin_dir = trailingslashit($plugin_dir);
		// If plugin is in its own directory, recursively delete the directory.
		if ( strpos($plugin, '/') && $plugin_dir != $base . PLUGINDIR . '/' ) {
			$deleted = $wp_filesystem->delete($plugin_dir, true);
		} else {

			$deleted = $wp_filesystem->delete($base . PLUGINDIR . "/$plugin");
		}
		if ( !$deleted ) {
			$wp_filesystem->delete($working_dir, true);
			echo '&raquo; Could not remove the old plugin. Make sure that "/wp-content/plugins/" folder has write permission (CHMOD 755).<br /><br />';
			return false;
		}
		
		if ( $debug ) echo '> Old version of the plugin removed successfully.<br /><br />';

		// Copy new version of plugin into place
		if ( !$this->mfiCopyDir($working_dir, MFI_ABSPATH . PLUGINDIR) ) {
			echo '&raquo; Installation failed. Make sure that "/wp-content/plugins/" folder has write permission (CHMOD 755)<br /><br />';
			return false;
		}
		//Get a list of the directories in the working directory before we delete it, we need to know the new folder for the plugin
		$filelist = array_keys( $wp_filesystem->dirlist($working_dir) );
		// Remove working directory
		$wp_filesystem->delete($working_dir, true);
		// if there is no files in the working dir
		if( empty($filelist) ) {
			echo '&raquo; Installation failed.<br /><br />';
			return false; 
		}
		$folder = $filelist[0];
		$plugin = get_plugins('/' . $folder);      // Pass it with a leading slash, search out the plugins in the folder, 
		$pluginfiles = array_keys($plugin);        // Assume the requested plugin is the first in the list
		$result = $folder . '/' . $pluginfiles[0]; // without a leading slash as WP requires
		
		if ( $debug ) echo '> Copy new version of plugin into place successfully.<br /><br />';
		
		if ( is_wp_error($result) ) {
			echo '&raquo; '.$result.'<br><br>';
			return false;
		} else {
			//Result is the new plugin file relative to PLUGINDIR
			echo '&raquo; Plugin upgraded successfully<br><br>';	
			if( $result && $was_activated ){
				echo '&raquo; Attempting reactivation of the plugin...<br><br>';	
				echo '<iframe style="display:none" src="' . wp_nonce_url('update.php?action=activate-plugin&plugin=' . $result, 'activate-plugin_' . $result) .'"></iframe>';
				sleep(15);
				echo '&raquo; Plugin reactivated successfully.<br><br>';	
			}
			return true;
		}
	}
	
	/**
	 * Copies directory from given source to destinaktion
	 */
	function mfiCopyDir($from, $to) {
		global $wp_filesystem;
		$dirlist = $wp_filesystem->dirlist($from);
		$from = trailingslashit($from);
		$to = trailingslashit($to);
		foreach ( (array) $dirlist as $filename => $fileinfo ) {
			if ( 'f' == $fileinfo['type'] ) {
				if ( ! $wp_filesystem->copy($from . $filename, $to . $filename, true) ) return false;
				$wp_filesystem->chmod($to . $filename, 0644);
			} elseif ( 'd' == $fileinfo['type'] ) {
				if ( !$wp_filesystem->mkdir($to . $filename, 0755) ) return false;
				if ( !$this->mfiCopyDir($from . $filename, $to . $filename) ) return false;
			}
		}
		return true;
	}
	
	/**
	 * Unzips the file to given directory
	 */
	function mfiUnzipFile($file, $to) {
		global $wp_filesystem;
		if ( ! $wp_filesystem || !is_object($wp_filesystem) )
			return new WP_Error('fs_unavailable', __('Could not access filesystem.'));
		$fs =& $wp_filesystem;
		require_once(ABSPATH . 'wp-admin/includes/class-pclzip.php');
		$archive = new PclZip($file);
		// Is the archive valid?
		if ( false == ($archive_files = $archive->extract(PCLZIP_OPT_EXTRACT_AS_STRING)) )
			return new WP_Error('incompatible_archive', __('Incompatible archive'), $archive->errorInfo(true));
		if ( 0 == count($archive_files) )
			return new WP_Error('empty_archive', __('Empty archive'));
		$to = trailingslashit($to);
		$path = explode('/', $to);
		$tmppath = '';
		for ( $j = 0; $j < count($path) - 1; $j++ ) {
			$tmppath .= $path[$j] . '/';
			if ( ! $fs->is_dir($tmppath) )
				$fs->mkdir($tmppath, 0755);
		}
		foreach ($archive_files as $file) {
			$path = explode('/', $file['filename']);
			$tmppath = '';
			// Loop through each of the items and check that the folder exists.
			for ( $j = 0; $j < count($path) - 1; $j++ ) {
				$tmppath .= $path[$j] . '/';
				if ( ! $fs->is_dir($to . $tmppath) )
					if ( !$fs->mkdir($to . $tmppath, 0755) )
						return new WP_Error('mkdir_failed', __('Could not create directory'));
			}
			// We've made sure the folders are there, so let's extract the file now:
			if ( ! $file['folder'] )
				if ( !$fs->put_contents( $to . $file['filename'], $file['content']) )
					return new WP_Error('copy_failed', __('Could not copy file'));
				$fs->chmod($to . $file['filename'], 0755);
		}
		return true;
	}
	
	/**
	 * Plugin registration form
	 * @access public 
	 */
	function mfiRegistrationForm($form_name, $submit_btn_txt='Register', $name, $email, $hide=0, $submit_again='') {
		$wp_url = get_bloginfo('wpurl');
		$wp_url = (strpos($wp_url,'http://') === false) ? get_bloginfo('siteurl') : $wp_url;
		$thankyou_url = $wp_url.'/wp-admin/options-general.php?page='.$_GET['page'];
		$onlist_url   = $wp_url.'/wp-admin/options-general.php?page='.$_GET['page'].'&amp;mbp_onlist=1';
		if ( $hide == 1 ) $align_tbl = 'left';
		else $align_tbl = 'center';
		?>
		
		<?php if ( $submit_again != 1 ) { ?>
		<script><!--
		function trim(str){
			var n = str;
			while ( n.length>0 && n.charAt(0)==' ' ) 
				n = n.substring(1,n.length);
			while( n.length>0 && n.charAt(n.length-1)==' ' )	
				n = n.substring(0,n.length-1);
			return n;
		}
		function mfiValidateForm_0() {
			var name = document.<?php echo $form_name;?>.name;
			var email = document.<?php echo $form_name;?>.from;
			var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
			var err = ''
			if ( trim(name.value) == '' )
				err += '- Name Required\n';
			if ( reg.test(email.value) == false )
				err += '- Valid Email Required\n';
			if ( err != '' ) {
				alert(err);
				return false;
			}
			return true;
		}
		//-->
		</script>
		<?php } ?>
		<table align="<?php echo $align_tbl;?>">
		<form name="<?php echo $form_name;?>" method="post" action="http://www.aweber.com/scripts/addlead.pl" <?php if($submit_again!=1){;?>onsubmit="return mfiValidateForm_0()"<?php }?>>
		 <input type="hidden" name="unit" value="maxbp-activate">
		 <input type="hidden" name="redirect" value="<?php echo $thankyou_url;?>">
		 <input type="hidden" name="meta_redirect_onlist" value="<?php echo $onlist_url;?>">
		 <input type="hidden" name="meta_adtracking" value="mfi-w-activate">
		 <input type="hidden" name="meta_message" value="1">
		 <input type="hidden" name="meta_required" value="from,name">
	 	 <input type="hidden" name="meta_forward_vars" value="1">	
		 <?php if ( $submit_again != '' ) { ?> 	
		 <input type="hidden" name="submit_again" value="1">
		 <?php } ?>		 
		 <?php if ( $hide == 1 ) { ?> 
		 <input type="hidden" name="name" value="<?php echo $name;?>">
		 <input type="hidden" name="from" value="<?php echo $email;?>">
		 <?php } else { ?>
		 <tr><td>Name: </td><td><input type="text" name="name" value="<?php echo $name;?>" size="25" maxlength="150" /></td></tr>
		 <tr><td>Email: </td><td><input type="text" name="from" value="<?php echo $email;?>" size="25" maxlength="150" /></td></tr>
		 <?php } ?>
		 <tr><td>&nbsp;</td><td><input type="submit" name="submit" value="<?php echo $submit_btn_txt;?>" class="button" /></td></tr>
		</form>
		</table>
		<?php
	}
	
	/**
	 * Register Plugin - Step 2
	 * @access public 
	 */
	function mfiRegister_2($form_name='frm2',$name,$email) {
		$msg = 'You have not clicked on the confirmation link yet. A confirmation email has been sent to you again. Please check your email and click on the confirmation link to activate the plugin.';
		if ( trim($_GET['submit_again']) != '' && $msg != '' ) {
			echo '<div id="message" class="updated fade"><p><strong>'.$msg.'</strong></p></div>';
		}
		?>
		<div class="wrap"><h2> <?php echo MBPFAVICON_NAME.' '.MBPFAVICON_VERSION; ?></h2>
		 <center>
		 <table width="640" cellpadding="5" cellspacing="1" bgcolor="#ffffff" style="border:1px solid #e9e9e9">
		  <tr><td align="center"><h3>Almost Done....</h3></td></tr>
		  <tr><td><h3>Step 1:</h3></td></tr>
		  <tr><td>A confirmation email has been sent to your email "<?php echo $email;?>". You must click on the link inside the email to activate the plugin.</td></tr>
		  <tr><td><strong>The confirmation email will look like:</strong><br /><img src="http://www.maxblogpress.com/images/activate-plugin-email.jpg" vspace="4" border="0" /></td></tr>
		  <tr><td>&nbsp;</td></tr>
		  <tr><td><h3>Step 2:</h3></td></tr>
		  <tr><td>Click on the button below to Verify and Activate the plugin.</td></tr>
		  <tr><td><?php $this->mfiRegistrationForm($form_name.'_0','Verify and Activate',$name,$email,$hide=1,$submit_again=1);?></td></tr>
		 </table>
		 <p>&nbsp;</p>
		 <table width="640" cellpadding="5" cellspacing="1" bgcolor="#ffffff" style="border:1px solid #e9e9e9">
           <tr><td><h3>Troubleshooting</h3></td></tr>
           <tr><td><strong>The confirmation email is not there in my inbox!</strong></td></tr>
           <tr><td>Dont panic! CHECK THE JUNK, spam or bulk folder of your email.</td></tr>
           <tr><td>&nbsp;</td></tr>
           <tr><td><strong>It's not there in the junk folder either.</strong></td></tr>
           <tr><td>Sometimes the confirmation email takes time to arrive. Please be patient. WAIT FOR 6 HOURS AT MOST. The confirmation email should be there by then.</td></tr>
           <tr><td>&nbsp;</td></tr>
           <tr><td><strong>6 hours and yet no sign of a confirmation email!</strong></td></tr>
           <tr><td>Please register again from below:</td></tr>
           <tr><td><?php $this->mfiRegistrationForm($form_name,'Register Again',$name,$email,$hide=0,$submit_again=2);?></td></tr>
           <tr><td><strong>Help! Still no confirmation email and I have already registered twice</strong></td></tr>
           <tr><td>Okay, please register again from the form above using a DIFFERENT EMAIL ADDRESS this time.</td></tr>
           <tr><td>&nbsp;</td></tr>
           <tr>
             <td><strong>Why am I receiving an error similar to the one shown below?</strong><br />
                 <img src="http://www.maxblogpress.com/images/no-verification-error.jpg" border="0" vspace="8" /><br />
               You get that kind of error when you click on &quot;Verify and Activate&quot; button or try to register again.<br />
               <br />
               This error means that you have already subscribed but have not yet clicked on the link inside confirmation email. In order to  avoid any spam complain we don't send repeated confirmation emails. If you have not recieved the confirmation email then you need to wait for 12 hours at least before requesting another confirmation email. </td>
           </tr>
           <tr><td>&nbsp;</td></tr>
           <tr><td><strong>But I've still got problems.</strong></td></tr>
           <tr><td>Stay calm. <strong><a href="http://www.maxblogpress.com/contact-us/" target="_blank">Contact us</a></strong> about it and we will get to you ASAP.</td></tr>
         </table>
		 </center>		
		<p style="text-align:center;margin-top:3em;"><strong><?php echo MBPFAVICON_NAME.' '.MBPFAVICON_VERSION; ?> by <a href="http://www.maxblogpress.com/" target="_blank" >MaxBlogPress</a></strong></p>
	    </div>
		<?php
	}

	/**
	 * Register Plugin - Step 1
	 * @access public 
	 */
	function mfiRegister_1($form_name='frm1') {
		global $userdata;
		$name  = trim($userdata->first_name.' '.$userdata->last_name);
		$email = trim($userdata->user_email);
		?>
		<div class="wrap"><h2> <?php echo MBPFAVICON_NAME.' '.MBPFAVICON_VERSION; ?></h2>
		 <center>
		 <table width="620" cellpadding="3" cellspacing="1" bgcolor="#ffffff" style="border:1px solid #e9e9e9">
		  <tr><td align="center"><h3>Please register the plugin to activate it. (Registration is free)</h3></td></tr>
		  <tr><td align="left">In addition you'll receive complimentary subscription to MaxBlogPress Newsletter which will give you many tips and tricks to attract lots of visitors to your blog.</td></tr>
		  <tr><td align="center"><strong>Fill the form below to register the plugin:</strong></td></tr>
		  <tr><td><?php $this->mfiRegistrationForm($form_name,'Register',$name,$email);?></td></tr>
		  <tr><td align="center"><font size="1">[ Your contact information will be handled with the strictest confidence <br />and will never be sold or shared with third parties ]</font></td></td></tr>
		 </table>
		 </center>
		<p style="text-align:center;margin-top:3em;"><strong><?php echo MBPFAVICON_NAME.' '.MBPFAVICON_VERSION; ?> by <a href="http://www.maxblogpress.com/" target="_blank" >MaxBlogPress</a></strong></p>
	    </div>
		<?php
	}
		
} // Eof Class

$MBPFavIcon = new MBPFavIcon();
?>