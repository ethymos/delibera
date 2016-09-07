<?php

class ET_Builder_Module_Delibera_Categoria extends ET_Builder_Module {
	function init() {
		$this->name = esc_html__( 'Delibera categoria', 'et_builder' );
		$this->slug = 'et_pb_delibera_categoria';

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
		);

		$this->fields_defaults = array(
			'animation'         => array( 'off' ),
			'background_layout' => array( 'light' ),
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
						'background' => array(
							'settings' => array(
								'color' => 'alpha',
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
				'label'            => esc_html__( 'Incluir Temas', 'et_builder' ),
				'renderer'         => 'et_builder_include_categories_delibera_option',
				'option_category'  => 'basic_option',
				'renderer_options' => array(
					'use_terms' => true,
					'term_name' => 'tema',
					'post_type'=>'pauta'
				),
				'description'      => esc_html__( 'Escolha qual o Tema você quer incluir na grade de pautas', 'et_builder' ),
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
		);
		return $fields;
	}

	function shortcode_callback( $atts, $content = null, $function_name ) {
		$module_id         = $this->shortcode_atts['module_id'];
		$module_class      = $this->shortcode_atts['module_class'];
		$name              = $this->shortcode_atts['name'];
		$position          = $this->shortcode_atts['position'];
		$image_url         = $this->shortcode_atts['image_url'];
		$animation         = $this->shortcode_atts['animation'];
		$background_layout = $this->shortcode_atts['background_layout'];
		$icon_color        = $this->shortcode_atts['icon_color'];
		$icon_hover_color  = $this->shortcode_atts['icon_hover_color'];
		$include_categories = $this->shortcode_atts['include_categories'];
		$texto =          $this->shortcode_atts['texto'];

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

			$term_list = wp_get_post_terms($wp_posts[$key]->ID, 'tema', array("fields" => "all"));

			$autor = get_userdata($wp_posts[$key]->post_author)->display_name;

			$tags = get_the_tag_list('Tags: ',', ','',$wp_posts[$key]->ID);

			$tema = $term_list[0]->name;

			$avatar = get_avatar( $wp_posts[$key]->post_author, '25');

			$temaLink = get_home_url().'/'.$term_list[0]->slug;

			$image_code = '';
			$pauta_url = "";
			$titulo = "";

			if (has_post_thumbnail( $wp_posts[$key]->ID ) ){
				$image_pauta_url = wp_get_attachment_image_src( get_post_thumbnail_id( $wp_posts[$key]->ID  ), 'thumbnail' );
				$image_code = $image_pauta_url[0];
			}
			$pauta_url = $wp_posts[$key]->guid;
			$titulo = $wp_posts[$key]->post_title;

			if ( '' !== $icon_color ) {
				ET_Builder_Element::set_style( $function_name, array(
					'selector'    => '%%order_class%% .et_pb_member_social_links a',
					'declaration' => sprintf(
							'color: %1$s;',
							esc_html( $icon_color )
							),
				) );
			}

			if ( '' !== $icon_hover_color ) {
				ET_Builder_Element::set_style( $function_name, array(
					'selector'    => '%%order_class%% .et_pb_member_social_links a:hover',
					'declaration' => sprintf(
							'color: %1$s;',
							esc_html( $icon_hover_color )
							),
				) );
			}

			if($image_code !='')
				$image_url = $image_code;

				if ( '' !== $image_url ) {
					$image = sprintf(
							'<div class="et_pb_delibera_member_image et-waypoint%3$s">
					<img src="%1$s" alt="%2$s" />
				</div>',
							esc_url( $image_url ),
							esc_attr( $titulo ),
							esc_attr( " et_pb_animation_{$animation}" )
							);
				}

				$i++;

				if($i==1)
				{
					$aux = '<div style="width: 100%; float: left; min-width: 400px; clear: both; padding-top:20px;">';
					$auxClose = '';
				}
				else
				{
					$aux = '';
					$auxClose = '';
				}

				if($i == 4)
				{
					$auxClose = '</div>';
					$i = 0;
				}

				$output .= $aux;

				$output .= sprintf(
						'
    <div class="et_pb_column et_pb_column_1_4  et_pb_column_4">
        <div class="et_pb_module et_pb_delibera_member%4$s%9$s  et_pb_team_pauta22v_0 et_pb_bg_layout_%8$s clearfix">
            <div class="et_pb_delibera_member_image et-waypoint et_pb_animation_off et-animated"></div>
				%2$s
				<div class="et_pb_delibera_member_description">
				<div class="tema" id="tema"><a href="%12$s">%11$s</a></div>
				<a href=%10$s>
					%5$s
					%6$s
					%1$s
					%7$s
				</div> <!-- .et_pb_delibera_member_description -->
			</a>
			<BR><div class="tags" id="tags">%14$s</div>

			<BR>
			<div class="user" id="user">
                <div class="imageInterna">%15$s</div>
                <div class="name">%13$s</div>
			</div>

			<div class="faixa"><img src="http://acidadequeeuquero.beta.campanhacompleta.com.br/files/2016/04/opn.png"></div>

			</div>
    </div><!-- .et_pb_delibera_member -->',
						$this->shortcode_content,
						( '' !== $image ? $image : '' ),
						( '' !== $module_id ? sprintf( ' id="%1$s"', esc_attr( $module_id ) ) : '' ),
						( '' !== $module_class ? sprintf( ' %1$s', esc_attr( str_replace("delibera","team",$module_class) ) ) : '' ),
						( '' !== $titulo ? sprintf( '<h4>%1$s</h4>', esc_html( $titulo ) ) : '' ),
						( '' !== $position ? sprintf( '<p class="et_pb_member_position">%1$s</p>', esc_html( $position ) ) : '' ),
						$social_links,
						$background_layout,
						( '' === $image ? ' et_pb_delibera_member_no_image' : '' ),
						$pauta_url,
						$tema,
						$temaLink,
						$autor,
						$tags,
						$avatar
						);

				$output .= $auxClose;
		}

		$output = '<div class="et_pb_section  et_pb_section_2 et_section_regular">'.$output.'</div>';

		return $output;
	}
}
new ET_Builder_Module_Delibera_Categoria;
