<?php

class ET_Builder_Module_Delibera_Categoria2 extends ET_Builder_Module {
	function init() {
		$this->name = esc_html__( 'Delibera categoria2', 'et_builder' );
		$this->slug = 'et_pb_delibera_categoria2';

		$this->whitelisted_fields = array(
			'name',
			'position',
			'image_url',
			'animation',
			'background_layout',
			'content_new',
			'admin_label',
			'module_id',
			'module_class',
			'icon_color',
			'icon_hover_color',
			'include_categories',
			'texto',
			'url_tema',
			'background_color',
			'button_image_url'
		);

		$this->fields_defaults = array(
			'animation'         => array( 'off' ),
			'background_layout' => array( 'light' ),
			'background_color'	=> array( '#ffffff' ),
			'button_image_url'	=> ''
		);

		$this->main_css_element = '%%order_class%%.et_pb_delibera_categoria';
		$this->advanced_options = array(
			'fonts' => array(
				'header' => array(
					'label'    => esc_html__( 'Header', 'et_builder' ),
					'css'      => array(
						'main' => "{$this->main_css_element} h4",
					),
					),
					'body'   => array(
						'label'    => esc_html__( 'Body', 'et_builder' ),
						'css'      => array(
							'main' => "{$this->main_css_element} *",
						),
						),
						),
						'border' => array(),
						'custom_margin_padding' => array(
							'css' => array(
								'important' => 'all',
							),
						),
							);
		$this->custom_css_options = array(
			'member_image' => array(
				'label'    => esc_html__( 'Member Image', 'et_builder' ),
				'selector' => '.et_pb_delibera_member_image',
			),
			'member_description' => array(
				'label'    => esc_html__( 'Member Description', 'et_builder' ),
				'selector' => '.et_pb_delibera_member_description',
			),
			'title' => array(
				'label'    => esc_html__( 'Title', 'et_builder' ),
				'selector' => '.et_pb_delibera_member_description h4',
			),
			'member_position' => array(
				'label'    => esc_html__( 'Member Position', 'et_builder' ),
				'selector' => '.et_pb_member_position',
			),
			'member_social_links' => array(
				'label'    => esc_html__( 'Member Social Links', 'et_builder' ),
				'selector' => '.et_pb_member_social_links',
			),
		);
	}

	function get_fields() {
		$fields = array(
			'include_categories' => array(
				'label'            => esc_html__( 'Include Categories', 'et_builder' ),
				'renderer'         => 'et_builder_include_categories_delibera_option',
				'option_category'  => 'basic_option',
				'renderer_options' => array(
					'use_terms' => true,
					'term_name' => 'tema',
					'post_type'=>'pauta'
				),
				'description'      => esc_html__( 'Choose which categories you would like to include in the feed.', 'et_builder' ),
			),
			'name' => array(
				'label'           => esc_html__( 'Title', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'description'     => 'Insira um título',
			),
			'url_tema' => array(
				'label'           => esc_html__( 'Texto', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'description'     => 'Se preenchida sobreescreve a url do tema',
			),
			'position' => array(
				'label'           => esc_html__( 'Subtitle', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'description'     =>'insira um subtitulo',
			),
			'image_url' => array(
				'label'              => esc_html__( 'Image URL', 'et_builder' ),
				'type'               => 'upload',
				'option_category'    => 'basic_option',
				'upload_button_text' => esc_attr__( 'Upload an image', 'et_builder' ),
				'choose_text'        => esc_attr__( 'Choose an Image', 'et_builder' ),
				'update_text'        => esc_attr__( 'Set As Image', 'et_builder' ),
				'description'        => esc_html__( 'Upload your desired image, or type in the URL to the image you would like to display.', 'et_builder' ),
			),
			'animation' => array(
				'label'             => esc_html__( 'Animation', 'et_builder' ),
				'type'              => 'select',
				'option_category'   => 'configuration',
				'options'           => array(
					'off'     => esc_html__( 'No Animation', 'et_builder' ),
					'fade_in' => esc_html__( 'Fade In', 'et_builder' ),
					'left'    => esc_html__( 'Left To Right', 'et_builder' ),
					'right'   => esc_html__( 'Right To Left', 'et_builder' ),
					'top'     => esc_html__( 'Top To Bottom', 'et_builder' ),
					'bottom'  => esc_html__( 'Bottom To Top', 'et_builder' ),
				),
				'description'       => esc_html__( 'This controls the direction of the lazy-loading animation.', 'et_builder' ),
			),
			'background_layout' => array(
				'label'           => esc_html__( 'Text Color', 'et_builder' ),
				'type'            => 'select',
				'option_category' => 'color_option',
				'options'           => array(
					'light' => esc_html__( 'Dark', 'et_builder' ),
					'dark'  => esc_html__( 'Light', 'et_builder' ),
				),
				'description' => esc_html__( 'Here you can choose the value of your text. If you are working with a dark background, then your text should be set to light. If you are working with a light background, then your text should be dark.', 'et_builder' ),
			),
			'content_new' => array(
				'label'           => esc_html__( 'Description', 'et_builder' ),
				'type'            => 'tiny_mce',
				'option_category' => 'basic_option',
				'description'     => esc_html__( 'Input the main text content for your module here.', 'et_builder' ),
			),
			'icon_color' => array(
				'label'             => esc_html__( 'Icon Color', 'et_builder' ),
				'type'              => 'color',
				'custom_color'      => true,
				'tab_slug'          => 'advanced',
			),
			'icon_hover_color' => array(
				'label'             => esc_html__( 'Icon Hover Color', 'et_builder' ),
				'type'              => 'color',
				'custom_color'      => true,
				'tab_slug'          => 'advanced',
			),
			'disabled_on' => array(
				'label'           => esc_html__( 'Disable on', 'et_builder' ),
				'type'            => 'multiple_checkboxes',
				'options'         => array(
					'phone'   => esc_html__( 'Phone', 'et_builder' ),
					'tablet'  => esc_html__( 'Tablet', 'et_builder' ),
					'desktop' => esc_html__( 'Desktop', 'et_builder' ),
				),
				'additional_att'  => 'disable_on',
				'option_category' => 'configuration',
				'description'     => esc_html__( 'This will disable the module on selected devices', 'et_builder' ),
			),
			'admin_label' => array(
				'label'       => esc_html__( 'Admin Label', 'et_builder' ),
				'type'        => 'text',
				'description' => esc_html__( 'This will change the label of the module in the builder for easy identification.', 'et_builder' ),
			),
			'module_id' => array(
				'label'           => esc_html__( 'CSS ID', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'tab_slug'        => 'custom_css',
				'option_class'    => 'et_pb_custom_css_regular',
			),
			'module_class' => array(
				'label'           => esc_html__( 'CSS Class', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'configuration',
				'tab_slug'        => 'custom_css',
				'option_class'    => 'et_pb_custom_css_regular',
			),
			'background_color' => array(
				'label'       => esc_html__( 'Background Color', 'et_builder' ),
				'type'        => 'color-alpha',
				'description' => esc_html__( 'Use the color picker to choose a background color for this module.', 'et_builder' ),
			),
			'button_image_url' => array(
				'label'              => esc_html__( 'Button Image URL', 'et_builder' ),
				'type'               => 'upload',
				'option_category'    => 'basic_option',
				'upload_button_text' => esc_attr__( 'Upload an image', 'et_builder' ),
				'choose_text'        => esc_attr__( 'Choose an Image', 'et_builder' ),
				'update_text'        => esc_attr__( 'Set As Image', 'et_builder' ),
				'description'        => esc_html__( 'Upload your desired image, or type in the URL to the image you would like to display as link button.', 'et_builder' ),
			),
			'url_tema' => array(
				'label'           => esc_html__( 'URL Tema', 'et_builder' ),
				'type'            => 'text',
				'option_category' => 'basic_option',
				'description'     => 'Se preenchida sobreescreve a url do tema',
			),
		);
		return $fields;
	}

	function shortcode_callback( $atts, $content = null, $function_name ) {
		$module_id        			= $this->shortcode_atts['module_id'];
		$module_class     			= $this->shortcode_atts['module_class'];
		$name             			= $this->shortcode_atts['name'];
		$position         			= $this->shortcode_atts['position'];
		$image_url        			= $this->shortcode_atts['image_url'];
		$animation        			= $this->shortcode_atts['animation'];
		$background_layout			= $this->shortcode_atts['background_layout'];
		$icon_color					= $this->shortcode_atts['icon_color'];
		$icon_hover_color			= $this->shortcode_atts['icon_hover_color'];
		$include_categories			= $this->shortcode_atts['include_categories'];
		$texto						= $this->shortcode_atts['texto'];
		$background_color			= $this->shortcode_atts['background_color'];
		$url_tema 					= $this->shortcode_atts['url_tema'];
		$button_image_url			= $this->shortcode_atts['button_image_url'];
		

		$module_class = ET_Builder_Element::add_module_order_class( $module_class, $function_name );

		$image = $social_links = '';

		$args = array(
			'post_type' => 'pauta',
			'orderby' => 'rand',
			'post_status'        => 'publish',
			'tax_query' => array(
				array(
					'taxonomy' => 'tema',
					'field' => 'tag_id',
					'terms' => $include_categories,
					'include_children' => false
				)
			)
		);

		$wp_posts = get_posts($args);

		$output = "";

		$auxClose = '';

		$i = 0;

		foreach($wp_posts as $key=>$value)
		{
			
			$output .= '[et_pb_delibera_pauta22v pauta_id="'.$wp_posts[$key]->ID.'"';
			foreach ($this->shortcode_atts as $key => $value)
			{
				if(!empty($value))
				{
					$output .= $key.'="'.$value.'" ';
				}
			}
			$output .= '][/et_pb_delibera_pauta22v]';
		}
		$output = do_shortcode($output);

		$output = '<div class="et_pb_section  et_pb_section_2 et_section_regular clearfix">'.$output.'</div>';

		return $output;
	}
}
new ET_Builder_Module_Delibera_Categoria2;
