<?php
/**
 * Extension for rendering a Facebook "like" button on any page of your site. Supports globals you can define in system settings for customizing the button.
 *
 * @package Extensions
 * @author Peter Epp
 * @copyright Copyright (c) 2009 Peter Epp (http://teknocat.org)
 * @license GNU Lesser General Public License (http://www.gnu.org/licenses/lgpl.html)
 * @version 1.0
 */
class FbLike extends AbstractExtension {
	/**
	 * Like button layout
	 *
	 * @var string 'standard' or 'button_count'
	 */
	private $_layout = 'standard';
	/**
	 * Whether or not to show profile pictures below the button
	 *
	 * @var string 'true' or 'false'
	 */
	private $_show_faces = 'false';
	/**
	 * Width of the widget iframe
	 *
	 * @var int
	 */
	private $_width = 450;
	/**
	 * Button text. Facebook currently supports "like" or "recommend"
	 *
	 * @var string 'like' or 'recommend' are what FB currently supports
	 */
	private $_verb = 'like';
	/**
	 * Font to use for button. Must be URL encoded
	 *
	 * @var string "arial", "lucida grande", "segoe ui", "tahoma", "trebuchet ms" or "verdana"
	 */
	private $_font = 'lucida grande';
	/**
	 * Colour scheme
	 *
	 * @var string 'light' or 'dark'
	 */
	private $_colorscheme = 'light';
	/**
	 * Height of the iframe. Will be set based on whether or not to show faces, which is true by default
	 *
	 * @var string
	 */
	private $_iframe_height = 80;
	/**
	 * Whether or not the extension can be used. Basically this is dependent on whether or not an image for the site has been defined. The og:image
	 * meta tag is required as part of the standard, so if an image has not been created with a path to the image defined in the system settings then
	 * the extension will do nothing.
	 *
	 * @var string
	 */
	private $_can_use = false;
	/**
	 * Place to store the url to render for the like button
	 *
	 * @var string
	 */
	private $_url;
	/**
	 * Set the meta tags required for FB like to work
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function run() {
		$this->_url = STANDARD_URL;
		$this->set_can_use();
		Event::fire('fb_like_init',$this);
	}
	/**
	 * Set url to override default
	 *
	 * @param string $url Required
	 * @return void
	 * @author Peter Epp
	 */
	public function set_url($url) {
		$this->_url = $url;
	}
	/**
	 * Whether or not the extension can be used.
	 *
	 * @return void
	 * @author Peter Epp
	 */
	public function set_can_use($value = 'default') {
		if ($value == 'default') {
			$this->_can_use = ((SERVER_TYPE == 'PRODUCTION' || DEBUG));
		} else if (is_bool($value)) {
			$this->_can_use = $value;
		}
	}
	/**
	 * Return HTML code for rendering the "like" button iframe
	 *
	 * @return string
	 * @author Peter Epp
	 */
	public function render_button() {
		if ($this->_can_use) {
			$this->set_options();
			if (empty($this->_font)) {
				$font = '';
			} else {
				$font = '='.rawurlencode(urlencode($this->_font));
			}
			$url = rawurlencode($this->_url);
			return '<iframe src="http://www.facebook.com/plugins/like.php?href='.$url.'&amp;layout='.$this->_layout.'&amp;show_faces='.$this->_show_faces.'&amp;width='.$this->_width.'&amp;action='.$this->_verb.'&amp;font'.$font.'&amp;colorscheme='.$this->_colorscheme.'&amp;height='.$this->_iframe_height.'" scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:'.$this->_width.'px; height:'.$this->_iframe_height.'px;" allowTransparency="true"></iframe>';
		}
		return '';
	}
	/**
	 * Set options based on global constants, if defined, to override defaults
	 *
	 * @return void
	 * @author Peter Epp
	 */
	private function set_options() {
		if (defined('FB_LIKE_LAYOUT') && (FB_LIKE_LAYOUT == 'standard' || FB_LIKE_LAYOUT == 'button_count' || FB_LIKE_LAYOUT == 'box_count')) {
			$this->_layout = FB_LIKE_LAYOUT;
		}
		if (defined('FB_LIKE_SHOW_FACES') && (FB_LIKE_SHOW_FACES == 'true' || FB_LIKE_SHOW_FACES == 'false')) {
			$this->_show_faces = FB_LIKE_SHOW_FACES;
			if ($this->_show_faces == 'false') {
				if ($this->_layout == 'box_count') {
					$this->_iframe_height = 60;
				} else {
					$this->_iframe_height = 35;
				}
			}
		}
		if (defined('FB_LIKE_WIDTH') && (int)FB_LIKE_WIDTH > 0) {
			$this->_width = (int)FB_LIKE_WIDTH;
		} else if ($this->_layout == 'box_count') {
			$this->_width = 50;
		}
		if (defined('FB_LIKE_VERB') && (FB_LIKE_VERB == 'like' || FB_LIKE_VERB == 'recommend')) {
			$this->_verb = FB_LIKE_VERB;
		}
		$allowed_fonts = array('arial','lucida grande','segoe ui','tahoma','trebuchet ms','verdana');
		if (defined('FB_LIKE_FONT') && in_array(FB_LIKE_FONT,$allowed_fonts)) {
			$this->_font = FB_LIKE_FONT;
		}
		if (defined('FB_LIKE_COLORSCHEME') && (FB_LIKE_COLORSCHEME == 'light' || FB_LIKE_COLORSCHEME == 'dark')) {
			$this->_colorscheme = FB_LIKE_COLORSCHEME;
		}
	}
	public static function install_migration() {
		$query = "REPLACE INTO `system_settings` (`constant_name`, `friendly_name`, `description`, `value`, `value_type`, `required`, `group_name`) VALUES
		('FB_LIKE_WIDTH', 'Button Width (pixels)', 'Make sure it has room for the text beside it. Defaults to 450 if left blank.', '', NULL, 0, 'Facebook Like Button'),
		('FB_LIKE_LAYOUT', 'Layout', 'Standard shows &ldquo;xxx people like this. Be the first of your friends&rdquo; or &ldquo;[User&rsquo;s Name] and xxx others like this.&rdquo; if the user is logged into Facebook. Button count shows only the number of likes.', 'standard', 'select{standard|button_count|box_count}', 1, 'Facebook Like Button'),
		('FB_LIKE_SHOW_FACES', 'Show Faces', 'Shows the faces of friends who like the page for the user who is currently viewing the site if they are also logged into Facebook in another window or tab', 'false', 'select{true|false}', 1, 'Facebook Like Button'),
		('FB_LIKE_VERB','Button Label', 'Label that shows on the like button', 'like', 'select{like|recommend}', 1, 'Facebook Like Button'),
		('FB_LIKE_FONT','Font','','lucida grande','select{arial|lucida grande|segoe ui|tahoma|trebuchet ms|verdana}', 1, 'Facebook Like Button'),
		('FB_LIKE_COLORSCHEME','Colour Scheme','','light','select{light|dark}', 1, 'Facebook Like Button')";
		DB::query($query);
	}
	public static function uninstall_migration() {
		DB::query("DELETE FROM `system_settings` WHERE `constant_name` LIKE 'FB_LIKE_%'");
	}
}
?>