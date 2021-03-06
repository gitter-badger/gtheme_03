<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gThemeShortCodes extends gThemeModuleCore 
{

	function setup_actions( $args = array() )
	{
		extract( shortcode_atts( array(
			'defaults' => true,
			'caption_override' => true,
			'gallery_override' => true,
		), $args ) );
		
		if ( $defaults )
			add_action( 'init', array( & $this, 'init' ), 14 );
			
		if ( $caption_override )
			add_filter( 'img_caption_shortcode', array( & $this, 'img_caption_shortcode' ), 10, 3 );
		
		if ( $gallery_override )
			add_filter( 'post_gallery', array( & $this, 'post_gallery' ), 10, 2 );
	}
	
	public function init()
	{
		$shortcodes = array(
			'theme-image' => 'shortcode_theme_image',
			'panel-group' => 'shortcode_panel_group',
			'panel' => 'shortcode_panel',
			'children' => 'shortcode_children',
			'siblings' => 'shortcode_siblings',
		);
	
		foreach ( $shortcodes as $shortcode => $method ) {
			remove_shortcode( $shortcode ); 
			add_shortcode( $shortcode, array( & $this, $method) ); 
		}
	}
	
	public function img_caption_shortcode( $empty, $attr, $content ) 
	{
		$args = shortcode_atts( array(
			'id'      => false,
			'align'   => 'alignnone',
			'width'   => '',
			'caption' => false,
			'class'   => '',
		), $attr, 'caption' );
		
		$args['width'] = (int) $args['width'];
		if ( $args['width'] < 1 || empty( $args['caption'] ) )
			return $content;
		
		if ( ! empty( $args['id'] ) )
			$args['id'] = 'id="'.esc_attr( $args['id'] ).'" ';
			
		$class = trim( 'the-img-caption '.$args['align'].' '.$args['class'] );
			
		return '<figure '.$args['id']
			   //.' style="width: '.(int) $args['width'].'px;"'
			   .' class="'.esc_attr( $class ).'">'
			   .do_shortcode( $content )
			   .'<figcaption class="the-img-caption-text">'
			   .gThemeL10N::str( $args['caption'] )
			   .'</figcaption></figure>';
	} 
	
	var $_gallery_count = 0;
	
	public function post_gallery( $empty, $attr )
	{
		if ( is_feed() )
			return $empty;
	
		$post = get_post();
		
		$args = shortcode_atts( array(
			'order'      => 'ASC',
			'orderby'    => 'menu_order ID',
			'id'         => $post ? $post->ID : 0,
			//'itemtag'    => 'figure',
			//'icontag'    => 'div',
			//'captiontag' => 'figcaption',
			'columns'    => 3,
			'size'       => 'thumbnail',
			'include'    => '',
			'exclude'    => '',
			'link'       => '', // 'file', 'none', empty
			
			'file_size'  => gThemeOptions::info( 'gallery_file_size', 'big' ),
			'nocaption'  => '<span class="genericon genericon-search"></span>',
		), $attr, 'gallery' );
	
		$id = intval( $args['id'] );
		$posts_args = array(
			'post_status' => 'inherit',
			'post_type' => 'attachment',
			'post_mime_type' => 'image',
			'order' => $args['order'],
			'orderby' => $args['orderby'],
		);
		
		if ( ! empty( $args['include'] ) ) {
			$attachments = array();
			$posts_args['include'] = $args['include'];
			foreach ( get_posts( $posts_args ) as $key => $val )
				$attachments[$val->ID] = $val;
		} elseif ( ! empty( $args['exclude'] ) ) {
			$posts_args['post_parent'] = $id;
			$posts_args['exclude'] = $args['exclude'];
			$attachments = get_children( $posts_args );
		} else {
			$posts_args['post_parent'] = $id;
			$attachments = get_children( $posts_args );
		}

		if ( empty( $attachments ) )
			return $empty;
		
		if ( 'none' == $args['link'] ) {
			$default = '<figure class="gallery-img">%1$s<figcaption><div class="gallery-description"><p>%3$s</p></div></figcaption></figure>';
		} else {
			$default = '<div class="gallery-wrap"><figure class="gallery-img">%1$s<figcaption><a href="%2$s" title="%4$s" id="%5$s"><div class="gallery-description"><p>%3$s</p></div></a></figcaption></figure></div>';
			if ( gThemeOptions::supports( 'zoom', true ) ) {
				$args['link'] = 'file';
	
				// CAUTION: css must added manually
				wp_enqueue_script( 'gtheme-zoom', GTHEME_URL.'/libs/zoom.min.js', array( 'jquery' ), '20141123', true );
			}
		}
		
		// CAUTION: css must added manually
		wp_register_script( 'gtheme-imagesloaded', GTHEME_URL.'/js/jquery.imagesloaded.min.js', array( 'jquery' ), '3.0.4', true );
		wp_enqueue_script( 'gtheme-gallery', GTHEME_URL.'/js/script.gallery.js', array( 'jquery', 'gtheme-imagesloaded' ), GTHEME_VERSION, true );
		
		$html = '';
		$template = gThemeOptions::info( 'gallery_template', $default );
		$this->_gallery_count++;
		$selector = 'gallery-'.$this->_gallery_count;
		
		foreach ( $attachments as $id => $attachment ) {

			if ( 'file' == $args['link'] ) {
				//$url = wp_get_attachment_url( $id ); 
				// geting the 'big' file, not 'raw' or full url
				list( $url, $width, $height ) = wp_get_attachment_image_src( $id, $args['file_size'] );
			} else if ( 'none' == $args['link'] ) {
				$url = '';
			} else {
				$url = get_attachment_link( $id );
			}
		
			if ( trim( $attachment->post_excerpt ) ) {
				$attr = array( 'aria-describedby' => "$selector-$id" );
				$title = esc_attr( $attachment->post_excerpt );
				$caption = wptexturize( $attachment->post_excerpt );
			} else {
				$attr = $title = '';
				$caption = $args['nocaption'];
			}

			$html .= sprintf( $template, 
				wp_get_attachment_image( $id, $args['size'], false, $attr ), 
				$url,
				$caption, 				
				$title, 
				$selector.'-'.$id
			);
		}	
		
		return '<div class="gallery-spinner"></div>'.gThemeUtilities::html( 'div', array( 
			'id' => $selector,
			'class' => array(
				'gallery',
				'gallery-columns-'.$args['columns'],
				'gallery-size-'.sanitize_html_class( $args['size'] ),
			),
		), $html );
	}	
	
	/** SYNTAX:
	
	[panel-group id="" class="" role=""]
		[panel parent="" id="" title="" title_tag="" context="" expanded=""]...[/panel]
		[panel parent="" id="" title="" title_tag="" context="" expanded=""]...[/panel]
		[panel parent="" id="" title="" title_tag="" context="" expanded=""]...[/panel]
	[/panel-group]
	
	**/
	
	var $_panel_group_count = 0;
	var $_panel_count = 0;
	var $_panel_parent = false;
	
	function shortcode_panel_group( $atts, $content = null, $tag = '' ) 
	{
		if ( is_null( $content ) )
			return $content;
	
		$args = shortcode_atts( array(
			'class' => '',
			'id'    => 'panel-group-'.$this->_panel_group_count,
			'role'  => 'tablist',
		), $atts, $tag );
		
		$this->_panel_parent = $args['id'];
		
		$html  = '<div class="panel-group '.$args['class'].'" id="'.$args['id'].'" role="'.$args['role'].'" aria-multiselectable="true">';
		$html .= do_shortcode( $content );
		$html .= '</div>';
	
		$this->_panel_parent = false;
		$this->_panel_group_count++;
		
		return $html;
	}
	
	function shortcode_panel( $atts, $content = null, $tag = '' ) 
	{
		if ( is_null( $content ) )
			return $content;
	
		$args = shortcode_atts( array(
			'parent'    => ( $this->_panel_parent ? $this->_panel_parent : 'panel-group-'.$this->_panel_group_count ),
			'id'        => 'panel-'.$this->_panel_count,
			'title'     => _x( 'Untitled', 'Panel Shortcode', GTHEME_TEXTDOMAIN ),
			'title_tag' => 'h4',
			'context'   => 'default',
			'expanded'  => false,
		), $atts, $tag );

		$html  = '<div class="panel panel-'.$args['context'].'">';
		$html .= '<div class="panel-heading" role="tab" id="'.$args['id'].'-wrap">';
		$html .= '<'.$args['title_tag'].' class="panel-title"><a data-toggle="collapse" data-parent="#'.$args['parent'].'" href="#'.$args['id'].'" aria-expanded="'.( $args['expanded'] ? 'true' : 'false').'" aria-controls="'.$args['id'].'">';
		$html .= $args['title'].'</a></'.$args['title_tag'].'></div>';
		$html .= '<div id="'.$args['id'].'" class="panel-collapse collapse'.( $args['expanded'] ? ' in' : '' ).'" role="tabpanel" aria-labelledby="'.$args['id'].'-wrap">';
		$html .= '<div class="panel-body">'.$content.'</div></div></div>';
	
		$this->_panel_count++;
		return $html;
	}
	
	function shortcode_theme_image( $atts, $content = null, $tag = '' ) 
	{
		$args = shortcode_atts( array(
			'src' => false,
			'alt' => false,
			'title' => false,
			'width' => false,
			'height' => false,
			'url' => false,
			'dir' => 'images',
		), $atts, $tag );
	
		if ( ! $args['src'] )
			return $content;
	
		$html = gThemeUtilities::html( 'img', array( 
			'src' => GTHEME_CHILD_URL.'/'.$args['dir'].'/'.$args['src'],
			'alt' => $args['alt'],
			'title' => ( $args['url'] ? false : $args['title'] ),
			'width' => $args['width'],
			'height' => $args['height'],
		) );
		
		if ( $args['url'] )	
			return gThemeUtilities::html( 'a', array( 
				'href' => $args['url'],
				'title' => $args['title'],
			), $html );
		
		return $html;
	}
	
	function shortcode_children( $atts, $content = null, $tag = '' ) 
	{
		$args = shortcode_atts( array(
			'id' => get_queried_object_id(),
			'type' => 'page',
			'excerpt' => true,
		), $atts, $tag );

		if ( ! $args['id'] )
			return $content;
		
		if ( ! is_singular( $args['type'] ) )
			return $content;
			
		$children = wp_list_pages( array( 
			'child_of' => $args['id'],
			'post_type' => $args['type'],
			'excerpt' => $args['excerpt'],
			'echo' => false,
			'depth' => 1,
			'title_li' => '',
			'sort_column' => 'menu_order, post_title',
			'walker' => new gTheme_Walker_Page(),
		) );
		
		if ( ! $children )
			return $content;
		
		return '<div class="list-group children">'.$children.'</div>';
	}
	
	function shortcode_siblings( $atts, $content = null, $tag = '' ) 
	{
		$args = shortcode_atts( array(
			'parent' => null,
			'type' => 'page',
			'excerpt' => true,
		), $atts, $tag );

		if ( ! is_singular( $args['type'] ) )
			return $content;
		
		if ( is_null( $args['parent'] ) ) {
			$object = get_queried_object();
			if ( $object && isset( $object->post_parent ) )
				$args['parent'] = $object->post_parent;
		}
		
		if ( ! $args['parent'] )
			return $content;
			
		$siblings = wp_list_pages( array( 
			'child_of' => $args['parent'],
			'post_type' => $args['type'],
			'excerpt' => $args['excerpt'],
			'echo' => false,
			'depth' => 1,
			'title_li' => '',
			'sort_column' => 'menu_order, post_title',
			'walker' => new gTheme_Walker_Page(),
		) );
		
		if ( ! $siblings )
			return $content;
		
		return '<div class="list-group siblings">'.$siblings.'</div>';
	}	
}

class gTheme_Walker_Page extends Walker_Page 
{
	public function start_el( & $output, $page, $depth = 0, $args = array(), $current_page = 0 ) 
	{
		$css_class = array( 'list-group-item', 'page-item-'.$page->ID );

		if ( isset( $args['pages_with_children'][$page->ID] ) )
			$css_class[] = 'page_item_has_children';

		if ( ! empty( $current_page ) ) {
			$_current_page = get_post( $current_page );
			if ( $_current_page && in_array( $page->ID, $_current_page->ancestors ) ) {
				$css_class[] = 'current_page_ancestor';
			}
			if ( $page->ID == $current_page ) {
				$css_class[] = 'active';
				$css_class[] = 'current_page_item';
			} elseif ( $_current_page && $page->ID == $_current_page->post_parent ) {
				$css_class[] = 'current_page_parent';
			}
		} elseif ( $page->ID == get_option('page_for_posts') ) {
			$css_class[] = 'current_page_parent';
		}

		$css_classes = implode( ' ', apply_filters( 'page_css_class', $css_class, $page, $depth, $args, $current_page ) );

		if ( '' === $page->post_title )
			$page->post_title = sprintf( __( '#%d (no title)' ), $page->ID );

		if ( isset( $args['excerpt'] ) && $args['excerpt'] && ! empty( $page->post_excerpt ) ) {
			$output .= sprintf(
				'<a class="%s" href="%s"><h4 class="list-group-item-heading">%s</h4><p class="list-group-item-text">%s</p></a>',
				$css_classes,
				get_permalink( $page->ID ),
				apply_filters( 'the_title', $page->post_title, $page->ID ),
				$page->post_excerpt
			);
		} else {
			$output .= sprintf(
				'<a class="%s" href="%s">%s</a>',
				$css_classes,
				get_permalink( $page->ID ),
				apply_filters( 'the_title', $page->post_title, $page->ID )
			);
		}
		
		/**
		if ( ! empty( $args['show_date'] ) ) {
			if ( 'modified' == $args['show_date'] ) {
				$time = $page->post_modified;
			} else {
				$time = $page->post_date;
			}

			$date_format = empty( $args['date_format'] ) ? '' : $args['date_format'];
			$output .= " " . mysql2date( $date_format, $time );
		}
		**/
	}
	
	public function end_el( &$output, $page, $depth = 0, $args = array() ) 
	{
		$output .= "\n";
	}
}