<?php defined( 'ABSPATH' ) or die( 'Restricted access' );

class gThemeModuleCore 
{
	var $_option_base = 'gtheme';
	var $_option_key = '';
	var $_ajax = false;
	var $_args = array();
	
	function __construct( $args = array() ) 
	{
		if ( ( ! $this->_ajax && self::ajax() )
			|| ( defined( 'WP_INSTALLING' ) && constant( 'WP_INSTALLING' ) ) )
			return;
		
		$this->_args = $args;
		$this->setup_actions( $args );	
	}
	
	public function setup_actions( $args = array() ) {}
	
	public static function ajax() 
	{
		return ( defined( 'DOING_AJAX' ) && constant( 'DOING_AJAX' ) ) ? true : false;
	}
	
	// helper
	// ANCESTOR : shortcode_atts()
	public static function atts( $pairs, $atts ) 
	{
		$atts = (array) $atts;
		$out = array();
		
		foreach( $pairs as $name => $default ) {
			if ( array_key_exists( $name, $atts ) )
				$out[$name] = $atts[$name];
			else
				$out[$name] = $default;
		}
		
		return $out;
	}
	
	// helper
	public static function getUsers()
	{
		$users = array( 0 => __( '&mdash; Select &mdash;', GTHEME_TEXTDOMAIN ) );
		foreach ( get_users( array( 'orderby' => 'display_name' ) ) as $user ) 
			$users[$user->ID] = $user->display_name;
		return $users;
	}
	
	// used by module settings pages
	public function field_debug()
	{
		gThemeUtilities::dump( $this->options );
	}
	
	// default setting sub html
	public function settings_sub_html( $settings_uri, $sub = 'general' )
	{
		echo '<form method="post" action="">';
			settings_fields( 'gtheme_'.$sub );
			do_settings_sections( 'gtheme_'.$sub );
			submit_button();
		echo '</form>';
	}
	
	public function do_settings_field( $atts = array(), $wrap = false )
	{
		$args = shortcode_atts( array( 
			'title' => '',
			'label_for' => '',
		
			'type' => 'enabled',
			'field' => false,
			'values' => array(),
			'filter' => false, // will use via sanitize
			'dir' => false,
			'default' => '',
			'desc' => '',
			'class' => '',
			'option_group' => $this->_option_key,
		), $atts );
	
		if ( $wrap ) {
			if ( ! empty( $args['label_for'] ) )
				echo '<tr><th scope="row"><label for="'.esc_attr( $args['label_for'] ).'">'.$args['title'].'</label></th><td>';
			else
				echo '<tr><th scope="row">'.$args['title'].'</th><td>';
		}	
		
		if ( ! $args['field'] )
			return;
		
		$name = $this->_option_base.'_'.$args['option_group'].'['.esc_attr( $args['field'] ).']';
		$id = $this->_option_base.'-'.$args['option_group'].'-'.esc_attr( $args['field'] );
		$value = isset( $this->options[$args['field']] ) ? $this->options[$args['field']] : $args['default'];
		
		switch ( $args['type'] ) {
			case 'enabled' :
			
				$html = gThemeUtilities::html( 'option', array(
					'value' => '0',
					'selected' => '0' == $value,
				), esc_html__( 'Disabled' ) );
				
				$html .= gThemeUtilities::html( 'option', array(
					'value' => '1',
					'selected' => '1' == $value,
				), esc_html__( 'Enabled' ) );
					
				echo gThemeUtilities::html( 'select', array(
					'class' => $args['class'],
					'name' => $name,
					'id' => $id,
				), $html );
				
				if ( $args['desc'] )
					echo gThemeUtilities::html( 'p', array( 
						'class' => 'description',
					), $args['desc'] );
				
			break;
			
			case 'text' :
				if ( ! $args['class'] )
					$args['class'] = 'regular-text';
				echo gThemeUtilities::html( 'input', array(
					'type' => 'text',
					'class' => $args['class'],
					'name' => $name,
					'id' => $id,
					'value' => $value,
					'dir' => $args['dir'],
				) );
				
				if ( $args['desc'] )
					echo gThemeUtilities::html( 'p', array( 
						'class' => 'description',
					), $args['desc'] );

			break;
			
			case 'checkbox' :
				if ( count( $args['values'] ) ) {
					foreach( $args['values'] as $value_name => $value_title ) {
						$html = gThemeUtilities::html( 'input', array(
							'type' => 'checkbox',
							'class' => $args['class'],
							'name' => $name.'['.$value_name.']',
							'id' => $id.'-'.$value_name,
							'value' => '1',
							'checked' => in_array( $value_name, ( array ) $value ),
							'dir' => $args['dir'],
						) );
					
						echo '<p>'.gThemeUtilities::html( 'label', array(
							'for' => $id.'-'.$value_name,
						), $html.'&nbsp;'.esc_html( $value_title ) ).'</p>';
					}
				} else {
					$html = gThemeUtilities::html( 'input', array(
						'type' => 'checkbox',
						'class' => $args['class'],
						'name' => $name,
						'id' => $id,
						'value' => '1',
						'checked' => $value,
						'dir' => $args['dir'],
					) );
				
					echo '<p>'.gThemeUtilities::html( 'label', array(
						'for' => $id,
					), $html.'&nbsp;'.esc_html( $value_title ) ).'</p>';
				}
				
				if ( $args['desc'] )
					echo gThemeUtilities::html( 'p', array( 
						'class' => 'description',
					), $args['desc'] );
				
			break;
			
			case 'select' :
				
				if ( false !== $args['values'] ) { // alow hiding
					$html = '';
					foreach ( $args['values'] as $value_name => $value_title )
						$html .= gThemeUtilities::html( 'option', array(
							'value' => $value_name,
							'selected' => $value_name == $value,
						), esc_html( $value_title ) );
						
					echo gThemeUtilities::html( 'select', array(
						'class' => $args['class'],
						'name' => $name,
						'id' => $id,
					), $html );
						
					if ( $args['desc'] )
						echo gThemeUtilities::html( 'p', array( 
							'class' => 'description',
						), $args['desc'] );
				}
			break;
			
			case 'textarea' :
			
				echo gThemeUtilities::html( 'textarea', array(
					'class' => array( 'large-text', 'textarea-autosize', $args['class'] ),
					'name' => $name,
					'id' => $id,
					'rows' => 5,
					'cols' => 45,
				), esc_textarea( $value ) );
					
				if ( $args['desc'] )
					echo gThemeUtilities::html( 'p', array( 
						'class' => 'description',
					), $args['desc'] );
			
			
			break;
			
			default :
				echo 'Error: setting type\'s not defind';
				if ( $args['desc'] )
					echo gThemeUtilities::html( 'p', array( 
						'class' => 'description',
					), $args['desc'] );
		}
	
		if ( $wrap )
			echo '</td></tr>';
	}
}