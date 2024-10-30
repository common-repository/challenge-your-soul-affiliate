<?php
/*
Plugin Name: Challenge Your Soul Affiliate
Plugin URI: http://sayfsolutions.com/2011/challengeyoursoul-affiliate-wordpress-plugin/
Description: A plugin that adds a WordPress shortcode to easily add ChallengeYourSoul affiliate ads in your posts and pages. Also includes a widget that displays a random affiliate ad. Based off the affiliate ad script found on the challengeyoursoul website in the affiliate tools section.
Version: 2.0
Author: Abu Sabah Abdullah
Author URI: http://sayfsolutions.com/
License: GPLv2
*/
/*
 * LICENSE
 * 
 * Copyright 2011  Abu Sabah Abdullah  (email : info@sayfsolutions.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as 
 * published by the Free Software Foundation.
 *	
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

// Set-up hooks.
add_action( 'admin_init', array('SayfSolutions_CYS_Affiliate', 'init' ) );
add_action( 'admin_menu', array('SayfSolutions_CYS_Affiliate', 'menu' ) );
add_action( 'widgets_init', array('SayfSolutions_CYS_Affiliate', 'register_widget' ) );
add_filter( 'plugin_action_links', array( 'SayfSolutions_CYS_Affiliate', 'add_settings_link' ), 10, 2 );

// Add Shortcodes
add_shortcode( 'cys-affiliate', array( 'SayfSolutions_CYS_Affiliate', 'shortcode' ) );
add_shortcode( 'cys_affiliate', array( 'SayfSolutions_CYS_Affiliate', 'shortcode' ) );
/**
 * The Challenge Your Soul Affilate widget
 */
class Sayf_CYS_Widget extends WP_Widget
{
	/**
	 * Process the widget.
	 */
	function Sayf_CYS_Widget()
	{
		$widget_ops = array(
			'classname'   => 'sayf-cys-widget',
			'description' => 'Randomly displays one Challenge Your Soul affilate ad.'
		);
		
		$this->WP_Widget( 'sayf_cys_widget', 'CYS Affilate', $widget_ops );
	}
	
	/**
	 * The form for the widget settings.
	 * 
	 * @param array $instance 
	 */
	function form()
	{		
		?>
		<p>
			Make sure you set you affiliate id in the 
			<a href="options-general.php?page=cys-settings">settings page</a>.
		</p>
		<?php
	}
	
	/**
	 * Display the widget.
	 * 
	 * Displays the widget on the front end of the site.
	 * 
	 * @param array $args
	 */
	function widget( $args )
	{
		$settings = get_option('sayf_cys_settings');		
		extract( $args );
		$affilate_id = !isset( $settings['sayf_cys_affiliate_id'] ) ? 413 : absint( $settings['sayf_cys_affiliate_id'] );
		$products    = SayfSolutions_CYS_Affiliate::get_products();
		$key         = array_rand($products);
		$url         = "http://multimedia.challengeyoursoul.com/showproduct.php?productid={$products[$key]['id']}&aid=$affilate_id";
		$title       = apply_filters( 'widget_title', $products[$key]['title'] );
		$image       = 'images/' . $products[$key]['image'];		
		
		echo $before_widget;
		echo $before_title . $title . $after_title;
		echo '<a href="'.$url.'" target="_blank"><img style="float:left;margin:0 5px 0 0;" height="130" width="90" src="' . plugins_url( $image, __FILE__ ) . '"></a>';
		echo '<p>'.$products[$key]['short_dis'].'</p>';
		echo '<a class="cys-purchase-link" href="'.$url.'" target="_blank">Purchase & Download Now</a>';
		echo $after_widget;
	}
}

class SayfSolutions_CYS_Affiliate
{
	function init()
	{
		register_setting( 
			'sayf_cys_settings', 
			'sayf_cys_settings',
			array( 'SayfSolutions_CYS_Affiliate', 'validate' )
		);
		
		add_settings_section(
			'sayf_cys_settings_main',
			'',
			array( 'SayfSolutions_CYS_Affiliate', 'overview' ),
			'sayf-cys-settings'
		);
		
		add_settings_field(
			'sayf_cys_affiliate_id',
			'Your Affiliate Id',
			array( 'SayfSolutions_CYS_Affiliate', 'affiliate_id_control' ),
			'sayf-cys-settings',
			'sayf_cys_settings_main'
		);
	}
	
	function menu()
	{
		if ( !current_user_can( 'manage_options' ) ) return;
		
		add_options_page(
			'Challenge Your Soul Settings', // What goes in between the <title> tags.
			'CYS Settings', // The name of the menu item in the dashboard.
			'manage_options', // Only users with manage_options (admin) permissions can see it.
			'cys-settings', // The page slug. /wp-admin/options-general.php?page=cys-settings
			array( 'SayfSolutions_CYS_Affiliate', 'draw' ) // The function called to draw the settings page.
		);
	}
	
	function draw()
	{
		$products = self::get_products();
		?>
<div class="wrap">
	<?php screen_icon(); ?>
	<h2>Challenge Your Soul Affiliate Settings</h2>
	<form action="options.php" method="post">
		<?php settings_fields( 'sayf_cys_settings' ); ?>
		<?php do_settings_sections( 'sayf-cys-settings' ); ?>
		<p class="submit">
			<input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Settings' ); ?>" />
		</p>
	</form>
	<h3>Support</h3>
	<p>This plugin was created by 
		<a href="http://sayfsolutions.com">Sayf Web Solutions</a>. You can request
		support 
		<a href="http://sayfsolutions.com/2011/challengeyoursoul-affiliate-wordpress-plugin/">here</a>.
	</p>
	<h3>Affiliate Products</h3>
	<table class="widefat">
		<thead>
			<tr>
				<th>Image</th>
				<th>Product Id</th>
				<th>Title</th>
				<th>Category</th>
				<th>Short Description</th>
				<th>Shortcode</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th>Image</th>
				<th>Product Id</th>
				<th>Title</th>
				<th>Category</th>
				<th>Short Description</th>
				<th>Shortcode</th>
			</tr>
		</tfoot>
		<tbody>
			<?php foreach ( $products as $p ) : ?>
			<tr>
				<td><img height="100px" src="<?php echo plugins_url( 'images/' . $p[ 'image' ], __FILE__ ) ?>" /></td>
				<td><?php echo $p[ 'id' ] ?></td>
				<td><?php echo $p[ 'title' ] ?></td>
				<td><?php echo $p[ 'category' ] ?></td>
				<td width="45%"><?php echo $p[ 'short_dis' ] ?></td>
				<td>[cys-affiliate pid=<?php echo $p[ 'id' ] ?>]</td>
			</tr>
			<?php endforeach ?>
		</tbody>
	</table>
</div>
		<?php
	}
	
	function overview()
	{
		?>
<p>
	Please enter the affiliate id that you were given via email from
	<a href="http://challengeyoursoul.com" target="_blank">Challenge Your Soul</a>
	when you signed up with them as an affiliate.
</p>
<p>
	Without, the correct affiliate id you will not get credited for any referrals
	or sales generated through the advertisements created by the Challenge Your 
	Soul Affiliate plugin.
</p>
		<?php
	}
	
	function affiliate_id_control()
	{
		$settings = get_option( 'sayf_cys_settings' );
    ?>
<input id="sayf_cys_affiliate_id"  
			 name="sayf_cys_settings[sayf_cys_affiliate_id]" 
       class="regular-text" 
       value="<?php echo $settings[ 'sayf_cys_affiliate_id' ]; ?>" />
    <?php
	}
	
	function validate( $input )
	{
		$clean = array();
		
		if ( empty( $input[ 'sayf_cys_affiliate_id' ] )) {
			add_settings_error(
				'sayf_cys_settings', 
        'settings_updated', 
        __( 'Affiliate Id can not be left blank!' ) 
			);
			return;
		}
		
		$clean[ 'sayf_cys_affiliate_id' ] = absint( $input[ 'sayf_cys_affiliate_id' ] );
		
		return $clean;
	}
	
	function register_widget()
	{
		register_widget( 'Sayf_CYS_Widget' );
	}
	
	function add_settings_link($links, $file) 
	{
		static $this_plugin;
		
		if ( !$this_plugin ) $this_plugin = plugin_basename( __FILE__ );
 
		if ( $file == $this_plugin ) {
			$settings_link = '<a href="options-general.php?page=cys-settings">Settings</a>';
			array_unshift( $links, $settings_link );
		}

		return $links;
	}
 
	function shortcode( $attr )
	{
		$products = self::get_products();
		$settings = get_option( 'sayf_cys_settings' );
		$aid      = isset( $settings[ 'sayf_cys_affiliate_id' ] ) ? absint( $settings[ 'sayf_cys_affiliate_id' ] ) : 413;
		
		if ( isset( $attr[ 'pid' ] ) ) {
			$pid = absint( $attr[ 'pid' ] );
			$url = "http://multimedia.challengeyoursoul.com/showproduct.php?productid={$pid}&aid={$aid}";
			foreach ( $products as $p ) {
				if ( $p['id'] == $pid ) {
					$image_src = plugins_url( 'images/' . $p['image'], __FILE__ );
					return <<<ALLAH
<div class="cys-affiliate-product">
	<a href="$url">
		<img style="float:left;margin-right:5px;" src="$image_src" />
	</a>
	<p>{$p['long_dis']}</p>
	<div style="clear:both"></div>
	<p style="text-align:center;margin-top:20px;">{$p['video']}</p>
	<p style="text-align:center;"><a href="$url">Purchase & Download Now</a></p>
</div>
ALLAH;
				}
			}
		}
		
		if ( isset ( $attr[ 'cid' ] ) ) {
			$cid  = esc_html( $attr[ 'cid' ] );
			$html = '';
			foreach ( $products as $p ) {
				if ( $p[ 'category' ] == $cid ) {
					$image_src = plugins_url( 'images/' . $p['image'], __FILE__ );
					$url = "http://multimedia.challengeyoursoul.com/showproduct.php?productid={$p['id']}&aid={$aid}";
					$html .= <<<ALLAH
<p><a href="$url">{$p['title']}</a></p>
<div style="margin-bottom:30px;" class="cys-affiliate-product">
	<a href="$url"><img style="float:left;margin-right:5px;" src="$image_src"></a>
	<p>{$p['long_dis']}</p>
	<p style="text-align:center;"><a href="$url">Purchase & Download Now</a></p>
	<div style="clear:both"></div>
</div>
ALLAH;
				}
			}
			return $html;
		}
		
		foreach ( $products as $p ) {
			$image_src = plugins_url( 'images/' . $p['image'], __FILE__ );
			$url = "http://multimedia.challengeyoursoul.com/showproduct.php?productid={$p['id']}&aid={$aid}";
			$html .= <<<ALLAH
<p><a href="$url">{$p['title']}</a></p>
<div style="margin-bottom:30px;" class="cys-affiliate-product">
	<a href="$url"><img style="float:left;margin-right:5px;" src="$image_src"></a>
	<p>{$p['long_dis']}</p>
	<p style="text-align:center;"><a href="$url">Purchase & Download Now</a></p>
	<div style="clear:both"></div>
</div>
ALLAH;
		}
		return $html;
	}
	
	function get_products()
	{
		return array
		(
			array
			(
				'id'        => 17,
				'title'     => 'Purpose Of Life Part 1',
				'image'     => 'purposeoflife1_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/j67VetsFlng&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/j67VetsFlng&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'What Is The Purpose of Life? Why are we here and where are we going? Through the verses of the Holy Quran, Shaykh Khalid Yasin expounds upon the creation of the universe and this amazing world we live in - and how it came to be.',
				'long_dis'  => 'What Is The Purpose of Life? Why are we here and where are we going? Through the verses of the Holy Quran, Shaykh Khalid Yasin expounds upon the creation of the universe and this amazing world we live in - and how it came to be. With his logical style of argument, the Shaykh answers these questions with much wisdom.',
				'category'  => 'purpose'
			),
			array
			(
				'id'        => 18,
				'title'     => 'Purpose Of Life Part 2',
				'image'     => 'purposeoflife_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/QznFoULRaJk&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/QznFoULRaJk&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'Shaykh Khalid Yasin delves into the life of Jesus Christ (peace be upon him) and his message, and shows that prophet Muhammad (peace be upon him) came with the same message; to worship God, do good in this world and strive for the eternal life hereafter.',
				'long_dis'  => 'A continuation of the first part of "The Purpose of Life", Shaykh Khalid Yasin delves into the life of Jesus Christ (peace be upon him) and his message, and shows that prophet Muhammad (peace be upon him) came with the same message; to worship God, do good in this world and strive for the eternal life hereafter. Alhamdulillah (all praise is due to Allah); twenty-two non-Muslims reverted to Islam on the night of this lecture.',
				'category'  => 'purpose'
			),
			array
			(
				'id'        => 28,
				'title'     => 'Purpose Of Life Part 3',
				'image'     => 'purpose_of_life_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/hOov22SvLos&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/hOov22SvLos&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'Shaykh Khalid Yasin applies the analogy of the governmental body to the human being. From its conception it is insignificant without power or sovereignty, but as it acquires power and develops it becomes robust and arrogant. However as time passes over, the body deteriorates and collapses; such is the similitude of the Human being.',
				'long_dis'  => 'The Purpose of Life 3 is a continuation of the popular Purpose of Life lectures 1 & 2. In this lecture Shaykh Khalid Yasin applies the analogy of the governmental body to the human being. From its conception it is insignificant without power or sovereignty, but as it acquires power and develops it becomes robust and arrogant. However as time passes over, the body deteriorates and collapses; such is the similitude of the Human being.',
				'category'  => 'purpose'
			),
			array
			(
				'id'        => 34,
				'title'     => 'Window To Islam',
				'image'     => 'windowtoislam_small.jpg',
				'video'     => '',
				'short_dis' => 'Window to Islam is a continuation of the lecture "The Purpose of Life part 3", held over two nights and resulting in many accepting Islam. In this lecture, Shaykh Khalid Yasin explains Islam and entertains many questions from the non-Muslim guests, who had a whole different picture of Islam before this lecture.',
				'long_dis'  => 'Window to Islam is a continuation of the lecture "The Purpose of Life part 3", held over two nights and resulting in many accepting Islam. In this lecture, Shaykh Khalid Yasin explains Islam and entertains many questions from the non-Muslim guests, who had a whole different picture of Islam before this lecture.',
				'category'  => 'purpose'
			),
			array
			(
				'id'        => 7,
				'title'     => 'Some Advice To The Muslim Women',
				'image'     => 'someadvicetomuslimwomen_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/-kewns-wF3c&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/-kewns-wF3c&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'Shaykh Khalid Yasin goes through many important topics: the rights and responsibilities of the wife to husband and vice versa, the controversial issue of polygamy, advice for a successful and happy marriage and much more.',
				'long_dis'  => 'This lecture is a vital one to watch, for both sisters and brothers. Shaykh Khalid Yasin goes through many important topics, such as the rights and responsibilities of the wife to husband and vice versa, the controversial issue of polygamy (multiple marriage) - the wisdom and legitimacy behind it and much more. The Shaykh also gives lots of advice for a successful and happy marriage.',
				'category'  => 'purpose'
			),
			array
			(
				'id'        => 9,
				'title'     => 'Why Man Should Recognise God',
				'image'     => 'whymanshouldrecognisegod_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/n57ov1uwmps&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/n57ov1uwmps&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'Every building has a builder; therefore through common sense and logic, the whole of creation must have a creator. Surely this universe and all that exists within it did not come about without a great designer behind it. Allah says in the Holy Qur\'an "Verily in the creation of the heavens and earth and the alternation of night and day are signs for a people who reflect". (Q 4:190)',
				'long_dis'  => 'Every building has a builder; therefore through common sense and logic, the whole of creation must have a creator. Surely this universe and all that exists within it did not come about without a great designer behind it. Allah says in the Holy Qur\'an "Verily in the creation of the heavens and earth and the alternation of night and day are signs for a people who reflect". (Q 4:190)',
				'category'  => 'purpose'  
			),
			array
			(
				'id'        => 10,
				'title'     => 'We Must Deliver The Message',
				'image'     => 'wemustdeliverthemessage_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/BbknYJaJyrQ&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/BbknYJaJyrQ&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'This lecture is designed for Muslims, highlighting the importance of propagating Islam to others especially in these times we are living in. Whether it be through our behaviour or actions, it is vital for Dawah that we are very careful not to damage the image of Islam to the world. Another important aspect of a Daee is having knowledge of Islam and also putting that knowledge into practise.',
				'long_dis'  => 'This lecture is designed for Muslims, highlighting the importance of propagating Islam to others especially in these times we are living in. Whether it be through our behaviour or actions, it is vital for Dawah that we are very careful not to damage the image of Islam to the world. Another important aspect of a Daee (one propagating Islam) is having knowledge of Islam and also putting that knowledge into practise.',
				'category'  => 'purpose'
			),
			array
			(
				'id'        => 14,
				'title'     => 'Changing The World Through DAWAH',
				'image'     => 'changingworlddawah_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/v2G2rTrA4Mg&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/v2G2rTrA4Mg&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'The misfortunes occurring to the Muslims is simply due to the fact that Muslims are moving away from the true teachings of Islam, as taught by Prophet Muhammed (peace be upon him). The world will be a better place if Islam is revived back into the Muslims\' lives and ultimately, spread to the rest of the world.',
				'long_dis'  => 'The misfortunes occurring to the Muslims is simply due to the fact that Muslims are moving away from the true teachings of Islam, as taught by Prophet Muhammed (peace be upon him). The world will be a better place if Islam is revived back into the Muslims\' lives and ultimately, spread to the rest of the world.',
				'category'  => 'purpose'
			),
			array
			(
				'id'        => 20,
				'title'     => 'The Enemy Within',
				'image'     => 'theenemywithin_small.jpg',
				'video'     => '',
				'short_dis' => 'History in Islam has shown that when the true teachings of Islam are implemented within the Muslims and the Muslims are strongly united, they can never be defeated. However the only time Muslims are defeated is by the enemy within which is: disobedience to Allah and His Messenger and arguing over minor issues or differences, which leads to disunity. This is when the enemies of Islam find it easy to conquer the Muslims.',
				'long_dis'  => 'History in Islam has shown that when the true teachings of Islam are implemented within the Muslims and the Muslims are strongly united, they can never be defeated. However the only time Muslims are defeated is by the enemy within which is: disobedience to Allah and His Messenger and arguing over minor issues or differences, which leads to disunity. This is when the enemies of Islam find it easy to conquer the Muslims.',
				'category'  => 'purpose'
			),
			array
			(
				'id'        => 21,
				'title'     => 'Brotherhood In Islam',
				'image'     => 'brotherhoodinislam_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/kBJ2sNX1OgI&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/kBJ2sNX1OgI&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'The Prophet (peace be upon him) said: "You do not truly believe until you love for your brother what you love for yourself". In this lecture, Shaykh Khalid Yasin gives beautiful examples of what it means to really love for your brother what you love for yourself. He advises us of the qualities or actions that we are lacking in, so that we may increase the brotherhood in Islam.',
				'long_dis'  => 'The Prophet (peace be upon him) said: "You do not truly believe until you love for your brother what you love for yourself". In this lecture, Shaykh Khalid Yasin gives beautiful examples of what it means to really love for your brother what you love for yourself. He advises us of the qualities or actions that we are lacking in, so that we may increase the brotherhood in Islam.',
				'category'  => 'purpose'
			),
			array
			(
				'id'        => 22,
				'title'     => 'Jesus - A Prophet Of Allah',
				'image'     => 'jesusaprophetofallah_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/2vAdoK5dgqQ&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/2vAdoK5dgqQ&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'Through evidences cited from the Holy Quran and the Bible, Shaykh Khalid Yasin proves that Jesus Christ (peace be upon him) never once claimed to be more than a servant and Prophet of God, and through his Prophecy, it verified & described the coming of the comforter after he departs. Here Shaykh Khalid proves that the Comforter described by Jesus Christ (peace be upon him) was none other than Muhammad (peace be upon him).',
				'long_dis'  => 'Through evidences cited from the Holy Quran and the Bible, Shaykh Khalid Yasin proves that Jesus Christ (peace be upon him) never once claimed to be more than a servant and Prophet of God, and through his Prophecy, it verified & described the coming of the comforter after he departs. Here Shaykh Khalid proves that the Comforter described by Jesus Christ (peace be upon him) was none other than Muhammad (peace be upon him).',
				'category'  => 'purpose'
			),
			array
			(
				'id'        => 23,
				'title'     => 'What Do You Really Know About Islam?',
				'image'     => 'whatdoyoureallyknowislam_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/vyAa89bgtm4&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/vyAa89bgtm4&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'The media tends to portray the religion of Islam according to the actions of a handful of so-called \'Muslims\', and therefore unfairly labelling the whole religion negatively. Shaykh Khalid Yasin gives the viewer an insight into the beliefs and pillars of Islam, through which one sees that Islam is innocent from such claims. This lecture is especially useful for non-Muslims who wish to gain the proper knowledge about the religion of Islam.',
				'long_dis'  => 'The media tends to portray the religion of Islam according to the actions of a handful of so-called \'Muslims\', and therefore unfairly labelling the whole religion negatively. Shaykh Khalid Yasin gives the viewer an insight into the beliefs and pillars of Islam, through which one sees that Islam is innocent from such claims. This lecture is especially useful for non-Muslims who wish to gain the proper knowledge about the religion of Islam.',
				'category'  => 'purpose'
			),
			array
			(
				'id'        => 24,
				'title'     => 'Love And Loyalty For Allah And His Messenger',
				'image'     => 'loveloyaltyallahmessenger_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/s3bPB2jbnwE&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/s3bPB2jbnwE&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'In this lecture, Shaykh Khalid Yasin gives various examples of great companions who demonstrated true love and loyalty to Allah and His Messenger (peace be upon him). Whether it was through their personal lives, families or wealth, their sacrifices are amazing, leaving the viewer wondering just how much we truly love Allah and His Messenger (peace be upon him).',
				'long_dis'  => 'In this lecture, Shaykh Khalid Yasin gives various examples of great companions who demonstrated true love and loyalty to Allah and His Messenger (peace be upon him). Whether it was through their personal lives, families or wealth, their sacrifices are amazing, leaving the viewer wondering just how much we truly love Allah and His Messenger (peace be upon him).',
				'category'  => 'purpose'
			),
			array
			(
				'id'        => 25,
				'title'     => 'Character Of A Muslim',
				'image'     => 'charactermuslim_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/ZIfdP36BaGs&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/ZIfdP36BaGs&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'Allah (SWT) says in the Holy Quran (68:4); "And verily you (Oh Muhammad) are on an exalted standard of character". The Prophet Muhammad (peace be upon him) said, "I was sent to perfect and complete the good character". In a time when the character and behaviour of only a few \'Muslims\' is actually destroying the image of the religion of Islam and it\'s followers, it is vital as Muslims to look to the example of Prophet Muhammad (peace be upon him) and adopt it into our lives, for it was through his good manners and character that many reverted to Islam.',
				'long_dis'  => 'Allah (SWT) says in the Holy Quran (68:4); "And verily you (Oh Muhammad) are on an exalted standard of character". The Prophet Muhammad (peace be upon him) said, "I was sent to perfect and complete the good character". In a time when the character and behaviour of only a few \'Muslims\' is actually destroying the image of the religion of Islam and it\'s followers, it is vital as Muslims to look to the example of Prophet Muhammad (peace be upon him) and adopt it into our lives, for it was through his good manners and character that many reverted to Islam.',
				'category'  => 'purpose'
			),
			array
			(
				'id'        => 26,
				'title'     => 'Islam And The Modern World',
				'image'     => 'islammodernworld_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/cqaiGKU33Rw&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/cqaiGKU33Rw&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'Unfortunately with war in Iraq almost a certainty, its seems as though with all the advancements of man, man still can\'t seem to solve issues without war and bloodshed. Islam and the World is a lecture wherein Shaykh Khalid Yasin compares the difference between a world with an Islamic system and a world without. One can only look at the state of the world today run by so-called peacemakers to see the result. However Shaykh Khalid gives us an example through the comparison of Muslim communities within non-Muslim countries. ',
				'long_dis'  => 'Unfortunately with war in Iraq almost a certainty, its seems as though with all the advancements of man, man still can\'t seem to solve issues without war and bloodshed. Islam and the World is a lecture wherein Shaykh Khalid Yasin compares the difference between a world with an Islamic system and a world without. One can only look at the state of the world today run by so-called peacemakers to see the result. However Shaykh Khalid gives us an example through the comparison of Muslim communities within non-Muslim countries. ',
				'category'  => 'purpose'
			),
			array
			(
				'id'        => 4,
				'title'     => 'What Is True Success In Life?',
				'image'     => 'what_is_true_success_in_life_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/6Hu26cOJKXw&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/6Hu26cOJKXw&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'Shaykh Khalid Yasin reveals that true success in life is in knowing your purpose on earth and where you ultimate destination is. The grave cannot be the end...',
				'long_dis'  => 'Is true success in life achieving fame and fortune? Evidently not, as we constantly hear stories of famous people, who experience depression, use drugs and even commit suicide. Money did not buy them happiness after all, as many believe. The West - so called \'advanced nations\' - have the highest rate of suicide, divorce, drug and alcohol abuse and many other social problems - all because they wish to forget and escape the miseries of their life - hopelessly attempting to obtain happiness. In this lecture, Shaykh Khalid Yasin reveals that true success in life is in knowing your purpose on earth and where you ultimate destination is. The grave cannot be the end...',
				'category'  => 'success'
			),
			array
			(
				'id'        => 5,
				'title'     => 'Private Session',
				'image'     => 'private_session_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/XSyFAPB9_HY&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/XSyFAPB9_HY&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'Shaykh Khalid Yasin clarifies any questions or queries about Islam that non-Muslims had after attending some of his lectures, held privately and exclusively for non-Muslims.',
				'long_dis'  => 'Shaykh Khalid Yasin, in this privately held question and answer session with non-Muslims and new revers, clarifies the questions and concerns that non-Muslims had after attending some of his lectures.',
				'category'  => 'success'
			),
			array
			(
				'id'        => 6,
				'title'     => 'Our Beginning Our End',
				'image'     => 'our_beginning_our_end_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/kjzPrzJZeJ8&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/kjzPrzJZeJ8&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'Shaykh Khalid Yasin provides a beautiful description of the amazing creation of human life, from the embryo in the womb to its astonishing development. He then goes through the stages of human life until the human takes his final breath and he makes it clear that every soul will be judged.',
				'long_dis'  => 'Shaykh Khalid Yasin provides a beautiful description of the amazing creation of human life - from the embryo in the womb to its astonishing development. Miraculously, the Glorious Qur\'an gives exact details of this opening stage of life, a process that has only just been recently been discovered by scientists. Glory be to Allah - Creator of all that exists in the universe. Shaykh Khalid Yasin then goes through the stages of human life - until the human finally takes his last breath and enters once again into the womb - of the earth this time. Shaykh Khalid Yasin depicts the scenario of death and the hereafter and makes clear that every soul will ultimately be judged.',
				'category'  => 'success'
			),
			array
			(
				'id'        => 8,
				'title'     => 'Mary The Mother Of... ?',
				'image'     => 'marythemotherof_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/NDXCxrMmPas&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/NDXCxrMmPas&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'What role does Mary, the mother of Jesus play in 3 of the world\'s largest religions? An open forum with Shaykh Khalid Yasin, Robert Haddad and Rev. Andrew Katay.',
				'long_dis'  => 'What role does Mary, the mother of Jesus play in 3 of the world\'s largest religions? An open forum with Shaykh Khalid Yasin the Executive Director of the Islamic Teaching Institute (ITI),  Robert Haddad, educator at St Charbel\'s College and  Rev. Andrew Katay, convener at  Sydney Uni Anglican Chaplaincy',
				'category'  => 'success'
			),
			array
			(
				'id'        => 15,
				'title'     => 'Islam - The Only Solution To World Peace',
				'image'     => 'islamsolutionworldpeace_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/YvLLowJyH-w&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/YvLLowJyH-w&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'The lecture highlights the comparison between the major religions and political systems of the world. After going through each system and some shocking statistics, one is left with absolutely no doubt that Islam has and always will have the only solution to world peace. God the creator has given us Islam; the perfect system of life.',
				'long_dis'  => 'The lecture highlights the comparison between the major religions and political systems of the world. After going through each system and some shocking statistics, one is left with absolutely no doubt that Islam has and always will have the only solution to world peace. God the creator has given us Islam; the perfect system of life.',
				'category'  => 'success'
			),
			array
			(
				'id'        => 11,
				'title'     => 'The Only Solution To World Peace Part 2',
				'image'     => 'solutiontoworldpeacepart2_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/L-GFim64xtY&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/L-GFim64xtY&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'Following part 1, Shaykh Khalid Yasin outlines the necessary qualities for a system that is worthy of attaining the title "The Solution to World Peace". As he begins to describe these qualities, one slowly recognises, that all political systems and religions are disqualified - except the true and final religion of Allah - Islam; a complete and perfect system and way of life.',
				'long_dis'  => 'Following part 1, Shaykh Khalid Yasin outlines the necessary qualities for a system that is worthy of attaining the title "The Solution to World Peace". As he begins to describe these qualities, one slowly recognises, that all political systems and religions are disqualified - except the true and final religion of Allah - Islam; a complete and perfect system and way of life.',
				'category'  => 'success'
			),
			array
			(
				'id'        => 12,
				'title'     => 'Islam And The Media',
				'image'     => 'islamandthemedia_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/V047wNg4ED8&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/V047wNg4ED8&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'One needs only to look at today\'s media in general to witness the outrageously offensive things being broadcast. From television, magazines, newspapers, radio stations to billboards - the kind of disgusting material exposed to our young is horrific. Violence, drugs, sex - the media have no shame in making it public. This highlights the importance of the Muslim community to set up their own media platform.',
				'long_dis'  => 'One needs only to look at today\'s media in general to witness the outrageously offensive things being broadcast. From television, magazines, newspapers, radio stations to billboards - the kind of disgusting material exposed to our young is horrific. Violence, drugs, and sex - the media have no shame in making it public. This highlights the importance of the Muslim community to set up their own media platform. Shaykh Khalid Yasin gives details of the upcoming establishment of television and radio stations owned by Muslims in the near future Inshallah.',
				'category'  => 'success'
			),
			array
			(
				'id'        => 13,
				'title'     => 'From The Root To The Fruit',
				'image'     => 'roottothefruit_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/BvepxyWj81Y&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/BvepxyWj81Y&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'Shaykh Khalid Yasin focuses primarily on the youth, taking them back to the heart of Islam - believing in the oneness of God and advises them on ways to improve themselves. Also included is a short talk from World Champion Boxer Hajj Nasim Hamed.',
				'long_dis'  => 'In this lecture, Shaykh Khalid Yasin focuses primarily on the youth, taking them back to the heart of Islam - believing in the oneness of God and fearing Him wherever they may be. Khalid Yasin also advises them on ways to improve personally and possibly become the soldiers of Islam. Also included is a short talk from World Champion Boxer Hajj Nasim Hamed.',
				'category'  => 'success'
			),
			array
			(
				'id'        => 27,
				'title'     => 'Dawah Technique Course',
				'image'     => 'dawahtech_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/C6k83601Gs8&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/C6k83601Gs8&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'As Muslims we are always presented with the opportunity to give Dawah but few of us take up this great challenge. Are we too afraid to propagate the truth, do we lack the correct knowledge or are we just in need of Dawah ourselves? Shaykh Khalid Yasin lets us into his mind, his techniques and his motivation behind his success. This 4 seminar series is without doubt the best Dawah course available to Muslims living in non-Muslims environments.',
				'long_dis'  => 'Allah\'s Messenger said, "If anyone calls others to follow right guidance, his reward will be equivalent to those who follow him without their reward being diminished". As Muslims we are always presented with the opportunity to give Dawah but few of us take up this great challenge. Why is this the case? Are we too afraid to propagate the truth, do we lack the correct knowledge or are we just in need of Dawah ourselves? Insha Allah, no more can we accept excuses for not being at the forefront of Islamic propagation. Why? Shaykh Khalid Yasin, the world\'s most eminent Daee in giving Dawah directly to the non-Muslim population and having been Blessed by Allah The All Giver, the gift of bringing many thousands of people to Islam, has now let us into his mind, his techniques and his motivation behind his success. This 4 seminar series is without doubt, not only the best, but also the only Dawah course available to Muslims living in non-Muslims environments. Clear your mind and open your heart to accept what Shaykh Khalid teachers and implement it to further your own Dawah objectives and increase your Imaan Insha Allah.',
				'category'  => 'dawah'
			),
			array
			(
				'id'        => 30,
				'title'     => 'The Historical Jesus',
				'image'     => 'historical_jesus_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/A5FtVtQc278&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/A5FtVtQc278&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'And remember when Isa the son of Maryam said: "O children of Israel! I am the Rasool of Allah towards you, confirming the Torah which came before me, and to give you good news of a Rasool that will come after me whose name shall be Ahmed." But when he came to them with clear signs, they said "This is plain magic." Surat As-Saff : Verse 6',
				'long_dis'  => 'And remember when Isa (Jesus) the son of Maryam said: "O children of Israel! I am the Rasool (Messenger) of Allah towards you, confirming the Torah which came before me, and to give you good news of a Rasool that will come after me whose name shall be Ahmed (another name of Muhammad, meaning \'The praised one\')." But when he (Muhammad) came to them with clear signs, they said "This is plain magic." Surat As-Saff : Verse 6',
				'category'  => 'treasure'
			),
			array
			(
				'id'        => 31,
				'title'     => 'Islam VS Terrorism',
				'image'     => 'Islam_vs_terrorism_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/Yz917adlc4Y&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/Yz917adlc4Y&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'Unfortunately today people have been misled to think that Islam is a religion of Terror and oppression. In this lecture Shaykh Khalid Yasin reveals through historical accounts who in essence are the genuine terrorists and that Islam in is innocent of such claims.',
				'long_dis'  => 'Islam promotes and teaches peace while "Terrorism" suggests doing the exact opposite of that. Then why is the word "Terrorist" always coupled with the word Islamic? A complete oxymoron! Unfortunately today people have been misled to think that Islam is a religion of Terror and oppression. In this lecture Shaykh Khalid Yasin reveals through historical accounts who in essence are the genuine terrorists and that Islam in is innocent of such claims.',
				'category'  => 'treasure'
			),
			array
			(
				'id'        => 32,
				'title'     => 'Muhammed - The Man And His Message',
				'image'     => 'muhammedmanmessenger_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/_cTYdijXJF0&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/_cTYdijXJF0&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => '1426 years ago a man known as Muhammad was chosen by Allah (the Most High) to deliver a message, a message that would change the world forever. So who was Muhammad and what message did he bring? Why are over 1.6 billion people following him today and why is he considered the most influential personality in the annals of History? In this lecture Shaykh Khalid Yasin explains this great man, his mission and his message. ',
				'long_dis'  => '1426 years ago a man known as Muhammad was chosen by Allah (the Most High) to deliver a message, a message that would change the world forever. So who was Muhammad and what message did he bring? Why are over 1.6 billion people following him today and why is he considered the most influential personality in the annals of History? In this lecture Shaykh Khalid Yasin explains this great man, his mission and his message. Allah (the Most High) says: "O Muhammad, We have not sent you but as a mercy for all the worlds." Qur\'an 21:107',
				'category'  => 'treasure'
			),
			array
			(
				'id'        => 33,
				'title'     => 'Strangers',
				'image'     => 'strangers_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/F_jw7KgxfZY&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/F_jw7KgxfZY&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'Muhammad (pbuh) said that this religion began as something strange and will return as something strange... so give glad tidings to the strangers. We see many cases of Muslims reforming themselves to become better Muslims, and in most cases they are seen as extreme, strict and sometimes even considered as crazy. They have become strangers. In this lecture Sheikh Khalid Yasin explains the importance of being patient in difficult times like these.',
				'long_dis'  => 'Muhammad (pbuh) said that this religion began as something strange and will return as something strange... so give glad tidings to the strangers. We see many cases of Muslims reforming themselves to become better Muslims, and in most cases they are seen as extreme, strict and sometimes even considered as crazy. They have become strangers. In this lecture Sheikh Khalid Yasin explains the importance of being patient in difficult times like these.',
				'category'  => 'treasure'
			),
			array
			(
				'id'        => 35,
				'title'     => 'Islam - The Treasure Uncovered',
				'image'     => 'islamtreasureuncovered_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/rAJoH9OFccc&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/rAJoH9OFccc&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'In this lecture, Brother Khalid Yasin explains that Islam stands on its own, there are those who do not comply with the teachings of Islam and call themselves Muslims just like those who do not follow the true teachings of any other religion they follow. Bad Muslims does not mean Islam is bad.',
				'long_dis'  => 'In the west today, many Muslims and non-Muslims work, socialise and go to school together on a regular basis. Some Muslims may not be practicing Muslims; therefore Islam is misrepresented to others. Islam is a perfect way of life that must be shown to all human beings in its true sense; otherwise the religion of Islam will continue to be misunderstood by society. In this lecture, Brother Khalid Yasin explains that Islam stands on its own, there are those who do not comply with the teachings of Islam and call themselves Muslims just like those who do not follow the true teachings of any other religion they follow. Bad Muslims does not mean Islam is bad. Many non-Muslims accepted this message and became Muslims at the end of this lecture& Alhamdulillah (all praise is due to Allah).',
				'category'  => 'treasure'
			),
			array
			(
				'id'        => 36,
				'title'     => 'Critical Issues Facing Muslim Women',
				'image'     => 'criticalissuesfacingmuslimwomen_small.jpg',
				'video'     => '',
				'short_dis' => 'Due to recent false assertions about Muslim women through the media, Brother Khalid Yasin was asked to speak on the issues relating to Muslim women in this day and age. Addressing many non-Muslims, Brother Khalid attempted to shed light on issues such as the veil, marriage, Muslim women in the work force and more...',
				'long_dis'  => 'Due to recent false assertions about Muslim women through the media, Brother Khalid Yasin was asked to speak on the issues relating to Muslim women in this day and age. Addressing many non-Muslims, Brother Khalid attempted to shed light on issues such as the veil, marriage, Muslim women in the work force and more...',
				'category'  => 'treasure'
			),
			array
			(
				'id'        => 37,
				'title'     => 'Dawah In The West',
				'image'     => 'dawahinthewest_small.jpg',
				'video'     => '',
				'short_dis' => 'These days, Dawah is becoming more and more important to Muslims living in the West. With so much misinformation splashed around about Islam and Muslims. It\'s time we increase our knowledge and be prepared to rectify this misinformation, whether it is our neighbours, work colleagues or school friends. In this lecture, Shaykh Khalid Yasin touches on many important aspects of Dawah and different scenarios we may come across.',
				'long_dis'  => 'These days, Dawah is becoming more and more important to Muslims living in the West. With so much misinformation splashed around about Islam and Muslims. It\'s time we increase our knowledge and be prepared to rectify this misinformation, whether it is our neighbours, work colleagues or school friends. In this lecture, Shaykh Khalid Yasin touches on many important aspects of Dawah and different scenarios we may come across.',
				'category'  => 'treasure'
			),
			array
			(
				'id'        => 38,
				'title'     => 'Obligations On The Muslims',
				'image'     => 'obligationsonthemuslims_small.jpg',
				'video'     => '',
				'short_dis' => 'Much good work is being done by Muslims, however most groups are working alone and therefore struggling to build schools, mosques, youth centres and other needed establishments in the Muslim community. Muhammad (pbuh) said, "You do not truly believe until you love for your brother/sister what you love for yourself". The time has come for Muslims to start putting these ahadith into practice and look towards working together for the sake of Allah.',
				'long_dis'  => 'Unfortunately today, Muslims are divided over petty issues and forget their obligations as a Muslim group. Much good work is being done by Muslims, however most groups are working alone and therefore struggling to build schools, mosques, youth centres and other needed establishments in the Muslim community. Muhammad (pbuh) said, "You do not truly believe until you love for your brother/sister what you love for yourself". The time has come for Muslims to start putting these ahadith into practice and look towards working together for the sake of Allah. In this lecture Shaykh Khalid Yasin explains a list of important points that demonstrate many obligations on the Muslims.',
				'category'  => 'treasure'
			),
			array
			(
				'id'        => 40,
				'title'     => 'Our Vision For The Future Part 1',
				'image'     => 'ourvisionforthefuturepart1_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/mjB91G212D0&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/mjB91G212D0&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'In This Lecture, Shaykh Khalid Yasin asks, "What is our vision for America and the world?" The Shaykh observes that it is imperative to have a very clear mission statement that our fellow Americans and the rest of the world can judge us by. The Shaykh insists that producing and pursuing this vision is how we will be able to inspire and motivate ourselves and others.',
				'long_dis'  => 'In This Lecture, Shaykh Khalid Yasin asks, "What is our vision for America and the world?" The Shaykh observes that it is imperative to have a very clear mission statement that our fellow Americans and the rest of the world can judge us by. The Shaykh insists that producing and pursuing this vision is how we will be able to inspire and motivate ourselves and others.',
				'category'  => 'vision'
			),
			array
			(
				'id'        => 41,
				'title'     => 'Our Vision For The Future Part 2',
				'image'     => 'ourvisionforthefuturepart2_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/rIv_1ayqhi0&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/rIv_1ayqhi0&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'In This Lecture, Shaykh Khalid Yasin asks, "What is our vision for America and the world?" The Shaykh observes that it is imperative to have a very clear mission statement that our fellow Americans and the rest of the world can judge us by. The Shaykh insists that producing and pursuing this vision is how we will be able to inspire and motivate ourselves and others.',
				'long_dis'  => 'In This Lecture, Shaykh Khalid Yasin asks, "What is our vision for America and the world?" The Shaykh observes that it is imperative to have a very clear mission statement that our fellow Americans and the rest of the world can judge us by. The Shaykh insists that producing and pursuing this vision is how we will be able to inspire and motivate ourselves and others.',
				'category'  => 'vision'
			),
			array
			(
				'id'        => 43,
				'title'     => 'Lost Identity And Legacy Of The Ummah',
				'image'     => 'lostidentityandlegacyoftheummah_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/rJ75bgBWGU0&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/rJ75bgBWGU0&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'In this lecture, Shaykh Khalid Yasin expounds upon the identity crisis that is presently gripping the Muslim world, and how the ideals of the Quran and the Sunnah have become secondary to national and ethnic culture. After making a diagnosis of the present condition, the Shaykh offers an inspirational prognosis that Islam will survive and prevail over every challenge and obstacle.',
				'long_dis'  => 'In this lecture, Shaykh Khalid Yasin expounds upon the identity crisis that is presently gripping the Muslim world, and how the ideals of the Quran and the Sunnah have become secondary to national and ethnic culture. After making a diagnosis of the present condition, the Shaykh offers an inspirational prognosis that Islam will survive and prevail over every challenge and obstacle.',
				'category'  => 'vision'
			),
			array
			(
				'id'        => 44,
				'title'     => 'Community Responsibilities',
				'image'     => 'communityresponsibilities_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/i5IY70yEJH0&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/i5IY70yEJH0&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'In this lecture, Shaykh Khalid Yasin defines the meaning and the relationship between the terms "Community and Jamaa\'ah". He talks about the evolution and gradual steps involved in the establishment of a community. The Shaykh identifies the components of the community, and how they must mutually interact for its welfare and growth.',
				'long_dis'  => 'In this lecture, Shaykh Khalid Yasin defines the meaning and the relationship between the terms "Community and Jamaa\'ah". He talks about the evolution and gradual steps involved in the establishment of a community. The Shaykh identifies the components of the community, and how they must mutually interact for its welfare and growth.',
				'category'  => 'vision'
			),
			array
			(
				'id'        => 45,
				'title'     => 'Steps Towards Faith',
				'image'     => 'stepstowardsfaith_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/0y4SHU0F4Bw&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/0y4SHU0F4Bw&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'In this lecture, Shaykh Khalid Yasin offers a powerful, step by step explanation and illustration of the Islamic Faith in simple words. He reminds the audience that faith is the highest of all human resources and invites them to take small, but meaningful steps to improve their faith.',
				'long_dis'  => 'In this lecture, Shaykh Khalid Yasin offers a powerful, step by step explanation and illustration of the Islamic Faith in simple words. This is another excellent tool for presenting Islam to non-Muslims, and removing misconceptions and distortions about Islam and Muslims. Shaykh Khalid Yasin reminds the audience that faith is the highest of all human resources. He is inviting the audience to take small, but meaningful steps to improve their faith.',
				'category'  => 'vision'
			),
			array
			(
				'id'        => 53,
				'title'     => 'Advice To Our Future Leaders',
				'image'     => 'advicetoourfutureleaders_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/S9pbnx31Wb0&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/S9pbnx31Wb0&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'Shaykh Khalid Yasin and Imam Alamin A. Latif offer their combined experience to inspire and encourage the university students towards their inevitable responsibility of leadership in the future. The two Shaykhs remind these young adults, that they are our hope and our most precious resource for the future.',
				'long_dis'  => 'This Lecture was delivered at the University of Trinidad in 2006. Shaykh Khalid Yasin and Imam Alamin A. Latif offer their combined experience to inspire and encourage the university students towards their inevitable responsibility of leadership in the future. The two Shaykhs remind these young adults, that they are our hope and our most precious resource for the future.',
				'category'  => 'vision'
			),
			array
			(
				'id'        => 54,
				'title'     => 'Historical Sermon',
				'image'     => 'historicalsermon_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/b6DbMV2qzUA&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/b6DbMV2qzUA&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'This is a Khutba-al-Juma\'ah delivered by Shaykh Khalid Yasin at Masjid Dawood in Brooklyn, New York in August 2005. The Shaykh is urging the Muslims to deliver the message and to fulfil their responsibility to the people of the American society.',
				'long_dis'  => 'This is a Khutba-al-Juma\'ah delivered by Shaykh Khalid Yasin at Masjid Dawood in Brooklyn, New York in August 2005. This is the same Masjid, where Shaykh Khalid became a Muslim in 1965 forty years earlier at the hands of the late and honoured Shaykh Dawood Ahmed Faisal, who established the Islamic Mission of America in 1945, which was the first Masjid established by an indigenous Muslim in North America. The Shaykh is urging the Muslims to deliver the message and to fulfil their responsibility to the people of the American society.',
				'category'  => 'vision'
			),
			array
			(
				'id'        => 55,
				'title'     => 'Reform And Revival',
				'image'     => 'reformandrevival_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/CZVvS-NjMYs&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/CZVvS-NjMYs&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'In this lecture, the Shaykh Khalid Yasin is advising the Muslims to reshape and reform their thinking and the behaviour to meet the demands and the standards of Islam. He is reminding the Muslims that we have a unique and profound opportunity with our liberties and social privileges in the Western societies, to contribute and possibly initiate a movement to restore the consciousness of Muslims towards revival and dynamism.',
				'long_dis'  => 'In this lecture, the Shaykh Khalid Yasin is advising the Muslims to reshape and reform their thinking and the behaviour to meet the demands and the standards of Islam. He is reminding the Muslims that we have a unique and profound opportunity with our liberties and social privileges in the Western societies, to contribute and possibly initiate a movement to restore the consciousness of Muslims towards revival and dynamism.',
				'category'  => 'vision'
			),
			array
			(
				'id'        => 56,
				'title'     => 'Inspiration For Young People',
				'image'     => 'inspirationforyoungpeople_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/9BQ419xD2cI&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/9BQ419xD2cI&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'In this lecture, Sheikh Khalid Yasin reaches out to the youth, reminding them of the reality of their lives and talking to them about their challenges and responsibilities. The Sheikh uses special techniques to bond with young people while communicating to them in the language that they understand. The Sheikh reminds us that our young people are the greatest resource for the future.',
				'long_dis'  => 'In this lecture, Sheikh Khalid Yasin reaches out to the youth, reminding them of the reality of their lives and talking to them about their challenges and responsibilities. The Sheikh uses special techniques to bond with young people while communicating to them in the language that they understand. The Sheikh reminds us that our young people are the greatest resource for the future.',
				'category'  => 'vision'
			),
			array
			(
				'id'        => 57,
				'title'     => 'Islam Beyond The Differences',
				'image'     => 'islambeyondthedifferences_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/JbVl_0LjClU&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/JbVl_0LjClU&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'In this lecture, Sheikh Khalid Yasin discusses the many ethnic, cultural, and ideological differences that exist in the Muslim Community, and how we must modify them in order to achieve solidarity. The Sheikh observes that the major challenge for Muslims is to move beyond the historical differences to a unified diversity.',
				'long_dis'  => 'In this lecture, Sheikh Khalid Yasin discusses the many ethnic, cultural, and ideological differences that exist in the Muslim Community, and how we must modify them in order to achieve solidarity. The Sheikh observes that the major challenge for Muslims is to move beyond the historical differences to a unified diversity.',
				'category'  => 'vision'
			),
			array
			(
				'id'        => 61,
				'title'     => 'Judgement Day',
				'image'     => 'judgementday_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/zLhnAI7Fhlo&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/zLhnAI7Fhlo&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'In this lecture, Sheikh Khalid Yasin depicts the evolution of life, from the womb to the tomb depicting various challenges and stages that every human is subjected to between life and death. He reminds us that we are beneficiaries and that we are accountable both in this life and after death for our actions and moral convictions - which ultimately will be laid bare on the "Day of Judgment," a day about which there is no doubt.',
				'long_dis'  => 'In this lecture, Sheikh Khalid Yasin depicts the evolution of life, from the womb to the tomb depicting various challenges and stages that every human is subjected to between life and death. He reminds the audience that the Creator of this world, is also the Creator of the human beings, and is also the Owner and the Designer of this universe and all of its life system. He reminds us that we are beneficiaries and that we are accountable both in this life and after death for our actions and moral convictions - which ultimately will be laid bare on the "Day of Judgment," a day about which there is no doubt.',
				'category'  => 'vision'
			),
			array
			(
				'id'        => 62,
				'title'     => 'Malcolm X',
				'image'     => 'malcolmx_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/q9DEBBe9crQ&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/q9DEBBe9crQ&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'The life and legacy of Al Hajj Malik Shabazz better known to the world as Malcolm X, is still after forty years, having a profound influence throughout the world, especially among young people, oppressed people, and people searching for identity and social empowerment. In this lecture, Sheikh Khalid Yasin, who met Malcolm X, examines the Man, his Mission, and his Message, in the context of, and relatives of our social challenges today.',
				'long_dis'  => 'The life and legacy of Al Hajj Malik Shabazz better known to the world as Malcolm X, is still after forty years, having a profound influence throughout the world, especially among young people, oppressed people, and people searching for identity and social empowerment. In this lecture, Sheikh Khalid Yasin, who met Malcolm X, examines the Man, his Mission, and his Message, in the context of, and relatives of our social challenges today.',
				'category'  => 'vision'
			),
			array
			(
				'id'        => 63,
				'title'     => 'Responsibilities Of Muslim Women',
				'image'     => 'responsibilitiesofmuslimwomen_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/aXbgY56Mi-o&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/aXbgY56Mi-o&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'Today, we are witnessing the social empowerment of women in business, science, education and politics, all over the world. Conversely, we are witnessing the erosion of family values, unprecedented raise in violence, use of drugs, and suicide amongst young people all over the world. . In this lecture, Shaykh Khalid Yasin reminds the Muslim women, of their great challenge and responsibility, to cultivate and protect the future generations.',
				'long_dis'  => 'Today, we are witnessing the social empowerment of women in business, science, education and politics, all over the world. Conversely, we are witnessing the erosion of family values, unprecedented raise in violence, use of drugs, and suicide amongst young people all over the world. This social phenomenon has also manifested itself in the Muslim countries and among the Muslim families who live in the non-Muslim societies. In this lecture, Shaykh Khalid Yasin reminds the Muslim women, of their great challenge and responsibility, to cultivate and protect the future generations.',
				'category'  => 'vision'
			),
			array
			(
				'id'        => 64,
				'title'     => 'Pathology Of the Ummah',
				'image'     => 'pathologyoftheummah_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/9smbjUEd-So&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/9smbjUEd-So&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'In this lecture, Sheikh Khalid Yasin examines and evaluates the current conditions of the Muslim world, the symptoms and causes of its diseases and its dysfunction. He suggests that a new and dynamic thinking, reformation of the systems and an alternative to the sterile and stagnate leadership is the way forward.',
				'long_dis'  => 'In this lecture, Sheikh Khalid Yasin examines and evaluates the current conditions of the Muslim world, the symptoms and causes of its diseases and its dysfunction. He suggests that a new and dynamic thinking, reformation of the systems and an alternative to the sterile and stagnate leadership is the way forward.',
				'category'  => 'vision'
			),
			array
			(
				'id'        => 65,
				'title'     => 'Towards Empowerment',
				'image'     => 'towardsempowerment_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/ckG02JXwXxw&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/ckG02JXwXxw&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'In this lecture, Shaykh Khalid Yasin proposes that Muslims, especially those who are living as minorities in the western countries, must move beyond religious dogmatism and values to development and acquisition of human and natural resources. By doing so, we will be able to compete with and positively impact upon the society that we live in.',
				'long_dis'  => 'In this lecture, Shaykh Khalid Yasin proposes that Muslims, especially those who are living as minorities in the western countries, must move beyond religious dogmatism and values to development and acquisition of human and natural resources. By doing so, we will be able to compete with and positively impact upon the society that we live in.',
				'category'  => 'vision'
			),
			array
			(
				'id'        => 66,
				'title'     => 'Islam And America',
				'image'     => 'islamandamerica_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/ZzlvlRFdp9U&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/ZzlvlRFdp9U&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'As a direct result of the 9/11 tragedy, and several international conflictions, Islam and Muslims have been severely distorted. What is the Truth? America is the world\'s super power and the most advanced country in the world. Its citizens enjoy higher quality of life and social privileges than nearly every other society on earth. Yet, a provocative foreign policy has stained its international image, and on the domestic front, it is facing many critical challenges which are a cause for every America to be concerned for the future.',
				'long_dis'  => 'Islam is the fastest growing system of faith in America and in the world. There are 1.6 billion Muslims in the world, and there are already, 8-10 million Muslims in North America. As a direct result of the 9/11 tragedy, and several international conflictions, Islam and Muslims have been severely distorted. What is the Truth? America is the world\'s super power and the most advanced country in the world. Its citizens enjoy higher quality of life and social privileges than nearly every other society on earth. Yet, a provocative foreign policy has stained its international image, and on the domestic front, it is facing many critical challenges which are a cause for every America to be concerned for the future.',
				'category'  => 'vision'
			),
			array
			(
				'id'        => 67,
				'title'     => 'A Special Television Interview',
				'image'     => 'aspecialtelevisioninterview_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/J4HhmkKtc70&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/J4HhmkKtc70&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'This lecture was conducted in Kuwait in the year 2003. The interviewer asked the Shaykh many critical questions about Islam in America, the post 9/11 dynamics, the phenomenal rate of people embracing Islam in the Western world, and just what the Shaykh anticipates for the future of Islam in America and the world.',
				'long_dis'  => 'This lecture was conducted in Kuwait in the year 2003. The interviewer asked the Shaykh many critical questions about Islam in America, the post 9/11 dynamics, the phenomenal rate of people embracing Islam in the Western world, and just what the Shaykh anticipates for the future of Islam in America and the world. This is one of the best television interviews that has ever been held with Sheikh Khalid Yasin.',
				'category'  => 'vision'
			),
			array
			(
				'id'        => 69,
				'title'     => 'Reason, Rationale and Reality',
				'image'     => 'reasonrationaleandreality_small.jpg',
				'video'     => '<object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/AuD1kxGHtO8&hl=en_GB&fs=1&"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/AuD1kxGHtO8&hl=en_GB&fs=1&" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed></object>',
				'short_dis' => 'In this lecture, Sheikh Khalid Yasin produces a step by step proposition for non-Muslims to consider the process of creation. The phenomena of science and the logical conclusion that this world is the result of intelligent design. The Sheikh reminds us that as beneficiaries, we human beings should recognize, honour and subordinate ourselves to our common benefactor.',
				'long_dis'  => 'In this lecture, Sheikh Khalid Yasin produces a step by step proposition for non-Muslims to consider the process of creation. The phenomena of science and the logical conclusion that this world is the result of intelligent design. The Sheikh reminds us that as beneficiaries, we human beings should recognize, honour and subordinate ourselves to our common benefactor.',
				'category'  => 'vision'
			),
			array
			(
				'id'        => 46,
				'title'     => 'Citizenship And Responsibility',
				'image'     => 'citizenshipandresponsibility_small.jpg',
				'short_dis' => 'Shaykh Khalid Yasin talks about citizenship and responsibility.',
				'long_dis'  => 'Shaykh Khalid Yasin talks about citizenship and responsibility.',
				'category'  => 'audio'
			),
			array
			(
				'id'        => 47,
				'title'     => 'Friday\'s Khutbah',
				'image'     => 'fridayskhutbah_small.jpg',
				'short_dis' => 'Shaykh Khalid Yasin gives a Khutbah at Friday\'s prayer.',
				'long_dis'  => 'Shaykh Khalid Yasin gives a Khutbah at Friday\'s prayer.',
				'category'  => 'audio'
			),
			array
			(
				'id'        => 48,
				'title'     => 'Manners of Dawah',
				'image'     => 'mannersofdawah_small.jpg',
				'short_dis' => 'Shaykh Khalid Yasin explains the manners of Dawah.',
				'long_dis'  => 'Shaykh Khalid Yasin explains the manners of Dawah.',
				'category'  => 'audio'
			),
			array
			(
				'id'        => 49,
				'title'     => 'Marriage',
				'image'     => 'marriage_small.jpg',
				'short_dis' => 'Shaykh Khalid Yasin talks about Marriage.',
				'long_dis'  => 'Shaykh Khalid Yasin talks about Marriage.',
				'category'  => 'audio'
			),
			array
			(
				'id'        => 50,
				'title'     => 'Muslim Identity',
				'image'     => 'muslimidentity_small.jpg',
				'short_dis' => 'Shaykh Khalid Yasin talks about Muslim Identity.',
				'long_dis'  => 'Shaykh Khalid Yasin talks about Muslim Identity.',
				'category'  => 'audio'
			),
			array
			(
				'id'        => 51,
				'title'     => 'What Is The Purpose Of Life?',
				'image'     => 'whatisthepurposeoflife_small.jpg',
				'short_dis' => 'Shaykh Khalid Yasin talks about the purpose of life.',
				'long_dis'  => 'Shaykh Khalid Yasin talks about the purpose of life.',
				'category'  => 'audio'
			),
			array
			(
				'id'        => 52,
				'title'     => 'Is The Bible God\'s Word?',
				'image'     => 'isthebiblegodsword_small.jpg',
				'short_dis' => 'Talk about the whether the Bible is God\'s Word.',
				'long_dis'  => 'Talk about the whether the Bible is God\'s Word.',
				'category'  => 'audio'
			)
		);
	}
}
?>