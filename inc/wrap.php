<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gThemeWrap extends gThemeModuleCore 
{
	function setup_actions( $args = array() )
	{
		extract( shortcode_atts( array(
			'images_404' => true,
		), $args ) );
		
		if ( $images_404 )
			add_filter( 'template_include', array( & $this, 'template_include_404_images' ), -1 );
		
		add_action( 'wp_head', array( & $this, 'wp_head' ) );
		add_filter( 'template_include', array( 'gThemeWrap', 'wrap' ), 99 );
	}

	// http://wpengineer.com/2377/implement-404-image-in-your-theme/
	function template_include_404_images( $template )
	{
		if ( is_admin() )
			return $template;

		if ( ! is_404() )
			return $template;

		// @version 2011.12.23
		// matches 'img.png' and 'img.gif?hello=world'
		if ( preg_match( '~\.(jpe?g|png|gif|svg|bmp)(\?.*)?$~i', $_SERVER['REQUEST_URI'] ) ) {
			header( 'Content-Type: image/png' );
			//header( 'Content-Type: image/svg+xml' );
			locate_template( 'images/404.png', true, true );
			exit;
		}
		
		return $template;
	}

	//////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////
	// http://scribu.net/wordpress/theme-wrappers.html
	// https://gist.github.com/1209013

	static $main_template; // stores the full path to the main template file
	static $base; // stores the base name of the template file; e.g. 'page' for 'page.php' etc.

	static function wrap( $template ) 
	{
		self::$main_template = $template;

		self::$base = substr( basename( self::$main_template ), 0, -4 );

		if ( 'index' == self::$base )
			self::$base = false;

		$templates = array( 'base.php' );

		if ( self::$base )
			array_unshift( $templates, sprintf( 'base-%s.php', self::$base ) );

		return locate_template( $templates );
	}
	
	//////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////
	// SEE : https://make.wordpress.org/core/2014/10/29/title-tags-in-4-1/
	// SEE : https://core.trac.wordpress.org/ticket/18548
	// DEPRECATED use in: head.php
	public static function html_title( $sep = ' &raquo; ', $display = true, $seplocation = '' ) 
	{
		echo "\t".'<title>';
		wp_title( trim( gtheme_get_info( 'title_sep', $sep ) ), true, ( gThemeUtilities::is_rtl() ? 'right' : $seplocation ) );
		echo '</title>'."\n";
	}
	
	public function wp_head()
	{
		self::html_title();
	}

	//////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////
	//////////////////////////////////////////////////////////////////////
	// used in: head.php
	// http://www.paulirish.com/2008/conditional-stylesheets-vs-css-hacks-answer-neither/
	public static function html_open()
	{
		$attributes = array();
		$html_attributes = '';

		if ( function_exists( 'is_rtl' ) && is_rtl() )
			$attributes[] = 'dir="rtl"';

		if ( $lang = get_bloginfo( 'language' ) )
			$attributes[] = "lang=\"$lang\"";

		$html_attributes = ' '.apply_filters( 'language_attributes', implode( ' ', $attributes ) );
		
		$classes = array( 'no-js' );

		if ( is_admin_bar_showing() )
			$classes[] = 'html-admin-bar';
		
		$html_classes = join( ' ', $classes );	
		
		?><!--[if lt IE 7 ]> <html<?php echo $html_attributes; ?> class="<?php echo $html_classes.' ie ie6 lte9 lte8 lte7'; ?>"> <![endif]-->
<!--[if IE 7 ]> <html<?php echo $html_attributes; ?> class="<?php echo $html_classes.' ie ie7 lte9 lte8 lte7'; ?>"> <![endif]-->
<!--[if IE 8 ]> <html<?php echo $html_attributes; ?> class="<?php echo $html_classes.' ie ie8 lte9 lte8'; ?>"> <![endif]-->
<!--[if IE 9 ]> <html<?php echo $html_attributes; ?> class="<?php echo $html_classes.' ie ie9 lte9'; ?>"> <![endif]-->
<!--[if gt IE 9]> <html<?php echo $html_attributes; ?> class="<?php echo $html_classes; ?>"> <![endif]-->
<!--[if !IE]><!--> <html<?php echo $html_attributes; ?> class="<?php echo $html_classes; ?>"> <!--<![endif]--><?php
	}
} 


function gtheme_template_path() {
	return gThemeWrap::$main_template;
}

function gtheme_template_base() {
	return gThemeWrap::$base;
}