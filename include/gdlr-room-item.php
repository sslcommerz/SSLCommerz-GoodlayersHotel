<?php
	/*	
	*	Goodlayers Room Item Management File
	*	---------------------------------------------------------------------
	*	This file contains functions that help you create room item
	*	---------------------------------------------------------------------
	*/
	
	// add action to check for room item
	add_action('gdlr_print_item_selector', 'gdlr_check_room_item', 10, 2);
	if( !function_exists('gdlr_check_room_item') ){
		function gdlr_check_room_item( $type, $settings = array() ){
			if($type == 'room'){
				echo gdlr_print_room_item( $settings );
			}else if($type == 'room-category'){
				echo gdlr_print_room_category_item( $settings );
			}
		}
	}

	// print room item
	if( !function_exists('gdlr_print_room_category_item') ){
		function gdlr_print_room_category_item( $settings = array() ){
			
			$item_id = empty($settings['page-item-id'])? '': ' id="' . $settings['page-item-id'] . '" ';

			global $gdlr_spaces;
			$margin = (!empty($settings['margin-bottom']) && 
				$settings['margin-bottom'] != $gdlr_spaces['bottom-blog-item'])? 'margin-bottom: ' . $settings['margin-bottom'] . ';': '';
			$margin_style = (!empty($margin))? ' style="' . $margin . '" ': '';	

			$ret  = gdlr_get_item_title($settings);				
			$ret .= '<div class="room-category-item-wrapper" ' . $item_id . $margin_style . '>';
			
			$current_size = 0;
			$category_meta = get_option('gdlr_hotel_branch', array());
			if( empty($settings['category']) ){
				$room_branches = get_categories( array('taxonomy'=>'room_category', 'hide_empty'=>1) );
			}else{
				$room_branches = get_categories( array('taxonomy'=>'room_category', 'include'=>explode(',', $settings['category'])) );
			}
			foreach( $room_branches as $room_branch ){ 
				if( $current_size % $settings['item-size'] == 0 ){
					$ret .= '<div class="clear"></div>';
				}	
				
				$ret .= '<div class="' . gdlr_get_column_class('1/' . $settings['item-size']) . '">';
				$ret .= '<div class="gdlr-item gdlr-room-category-item">';
				if( !empty($category_meta[$room_branch->slug]['upload']) ){
					$ret .= '<div class="gdlr-room-category-thumbnail" >';
					$ret .= gdlr_get_image($category_meta[$room_branch->slug]['upload'], $settings['thumbnail-size']);
					$ret .= '<div class="gdlr-room-category-thumbnail-overlay"></div>';
					$ret .= '<div class="gdlr-room-category-thumbnail-overlay-icon">';
					$ret .= '<a href="' . get_term_link($room_branch) . '" ><i class="fa fa-link icon-link"></i></a>';
					$ret .= '</div>';
					$ret .= '</div>';
				}
				$ret .= '<h3 class="gdlr-hotel-branches-title" ><a href="' . get_term_link($room_branch) . '">' . $room_branch->cat_name . '</a></h3>';
				$ret .= '</div>'; // gdlr-item
				$ret .= '</div>'; // gdlr-column-class
				
				$current_size++;
				
				// $ret .= $category_meta[$room_branch->slug]['content'];
			}
			
			$ret .= '<div class="clear"></div>';
			$ret .= '</div>'; // room-category-item-wrapper
			return $ret;
		}
	}
	
	// print room item
	if( !function_exists('gdlr_print_room_item') ){
		function gdlr_print_room_item( $settings = array() ){

			$item_id = empty($settings['page-item-id'])? '': ' id="' . $settings['page-item-id'] . '" ';

			global $gdlr_spaces;
			$margin = (!empty($settings['margin-bottom']) && 
				$settings['margin-bottom'] != $gdlr_spaces['bottom-blog-item'])? 'margin-bottom: ' . $settings['margin-bottom'] . ';': '';
			$margin_style = (!empty($margin))? ' style="' . $margin . '" ': '';
			
			// query posts section
			$args = array('post_type' => 'room', 'suppress_filters' => false);
			$args['posts_per_page'] = (empty($settings['num-fetch']))? '5': $settings['num-fetch'];
			$args['orderby'] = (empty($settings['orderby']))? 'post_date': $settings['orderby'];
			$args['order'] = (empty($settings['order']))? 'desc': $settings['order'];
			$args['paged'] = (get_query_var('paged'))? get_query_var('paged') : 1;
			if( !empty($settings['category']) || !empty($settings['tag']) ){
				$args['tax_query'] = array('relation' => 'OR');
				if( !empty($settings['category']) ){
					array_push($args['tax_query'], array('terms'=>explode(',', $settings['category']), 'taxonomy'=>'room_category', 'field'=>'slug'));
				}
				if( !empty($settings['tag']) ){
					array_push($args['tax_query'], array('terms'=>explode(',', $settings['tag']), 'taxonomy'=>'room_tag', 'field'=>'slug'));
				}				
			}			
			$query = new WP_Query( $args );			
			
			$no_space  = (strpos($settings['room-style'], 'no-space') > 0)? 'gdlr-item-no-space': '';
			$settings['room-style'] = str_replace('-no-space', '', $settings['room-style']);
			if( in_array($settings['room-style'], array('classic', 'modern', 'modern-new', 'medium', 'medium-new')) && !empty($settings['enable-carousel']) && $settings['enable-carousel'] == 'enable' ){
				$settings['carousel'] = true;
			}
			
			$ret  = gdlr_get_item_title($settings);				
			$ret .= '<div class="room-item-wrapper type-' . $settings['room-style'] . '" ' . $item_id . $margin_style . '>';
			
			$ret .= '<div class="room-item-holder ' . $no_space . '">';
			if( $settings['room-style'] == 'medium' || $settings['room-style'] == 'medium-new' ){
				global $gdlr_excerpt_length, $gdlr_excerpt_read_more, $gdlr_excerpt_word; 
				$gdlr_excerpt_read_more = false;
				$gdlr_excerpt_length = $settings['num-excerpt'];
				add_filter('excerpt_length', 'gdlr_set_excerpt_length');
				
				if( !empty($settings['enable-carousel']) && $settings['enable-carousel'] == 'enable' ){
					$ret .= gdlr_get_medium_room_carousel($query, $settings['thumbnail-size'], $settings['room-style']);
				}else{
					$ret .= gdlr_get_medium_room($query, $settings['thumbnail-size'], $settings['room-style']);
				}
				$gdlr_excerpt_word = ''; $gdlr_excerpt_read_more = true;
				remove_filter('excerpt_length', 'gdlr_set_excerpt_length');
			}else if( $settings['room-style'] == 'classic' ){	
				if( !empty($settings['enable-carousel']) && $settings['enable-carousel'] == 'enable' ){
					$ret .= gdlr_get_classic_room_carousel($query, $settings['room-size'], $settings['thumbnail-size']);
				}else{
					$ret .= gdlr_get_classic_room($query, $settings['room-size'], $settings['thumbnail-size']);
				}
			}else if( $settings['room-style'] == 'modern' ){
				if( !empty($settings['enable-carousel']) && $settings['enable-carousel'] == 'enable' ){
					$ret .= gdlr_get_modern_room_carousel($query, $settings['room-size'], $settings['thumbnail-size']);
				}else{
					$ret .= gdlr_get_modern_room($query, $settings['room-size'], $settings['thumbnail-size']);
				}
			}else if( $settings['room-style'] == 'modern-new' ){
				if( !empty($settings['enable-carousel']) && $settings['enable-carousel'] == 'enable' ){
					$ret .= gdlr_get_modern_new_room_carousel($query, $settings['room-size'], $settings['thumbnail-size']);
				}else{
					$ret .= gdlr_get_modern_new_room($query, $settings['room-size'], $settings['thumbnail-size']);
				}
			}
			$ret .= '<div class="clear"></div>';
			$ret .= '</div>';
			
			if( $settings['pagination'] == 'enable' ){
				$ret .= gdlr_get_pagination($query->max_num_pages, $args['paged']);
			}
			$ret .= '</div>'; // room-item-wrapper
			return $ret;
		}
	}
	
	// get room style
	if( !function_exists('gdlr_get_medium_room') ){
		function gdlr_get_medium_room($query, $thumbnail_size, $room_style = 'medium'){
			$ret  = '';
			while($query->have_posts()){ $query->the_post();
				$post_option = json_decode(gdlr_decode_preventslashes(get_post_meta(get_the_ID(), 'post-option', true)), true);
				
				$extra_class = ($room_style == 'medium-new')? 'gdlr-medium-room-new': '';

				$ret .= '<div class="gdlr-item gdlr-room-item gdlr-medium-room ' . esc_attr($extra_class) . '">';
				$ret .= '<div class="gdlr-ux gdlr-medium-room-ux">';
				$ret .= '<div class="gdlr-room-thumbnail">' . gdlr_get_room_thumbnail($post_option, $thumbnail_size) . '</div>';	
				$ret .= '<div class="gdlr-room-content-wrapper">';
				$ret .= '<h3 class="gdlr-room-title"><a href="' . get_permalink() . '" >' . get_the_title() . '</a></h3>';
				if( $room_style == 'medium-new' ){
					$ret .= gdlr_hotel_room_info($post_option, array('bed', 'max-people', 'view', 'wifi'), true, 'new-style');
				}else{
					$ret .= gdlr_hotel_room_info($post_option, array('bed', 'max-people', 'view'));
				}
				$ret .= '<div class="gdlr-room-content">' . get_the_excerpt() . '</div>';
				$ret .= '<a class="gdlr-button with-border" href="' . get_permalink() . '">' . __('Check Details', 'gdlr-hotel') . '<i class="fa fa-long-arrow-right icon-long-arrow-right"></i></a>';
				$ret .= gdlr_hotel_room_info($post_option, array('price'), false);
				$ret .= '<div class="clear"></div>';
				$ret .= '</div>';
				$ret .= '<div class="clear"></div>';
				$ret .= '</div>'; // gdlr-ux
				$ret .= '</div>'; // gdlr-item
			}
			wp_reset_postdata();
			
			return $ret;
		}
	}

	// get room style
	if( !function_exists('gdlr_get_medium_room_carousel') ){
		function gdlr_get_medium_room_carousel($query, $thumbnail_size, $room_style = 'medium'){
			$ret  = '';

			$ret .= '<div class="gdlr-room-carousel-item gdlr-item" >';
			$ret .= '<div class="flexslider" data-type="carousel" data-nav-container="room-item-wrapper" data-columns="1" >';	
			$ret .= '<ul class="slides" >';	
			while($query->have_posts()){ $query->the_post();
				$post_option = json_decode(gdlr_decode_preventslashes(get_post_meta(get_the_ID(), 'post-option', true)), true);
				
				$extra_class = ($room_style == 'medium-new')? 'gdlr-medium-room-new': '';

				$ret .= '<li class="gdlr-item gdlr-medium-room ' . esc_attr($extra_class) . '">';
				$ret .= '<div class="gdlr-room-thumbnail">' . gdlr_get_room_thumbnail($post_option, $thumbnail_size) . '</div>';	
				$ret .= '<div class="gdlr-room-content-wrapper">';
				$ret .= '<h3 class="gdlr-room-title"><a href="' . get_permalink() . '" >' . get_the_title() . '</a></h3>';
				if( $room_style == 'medium-new' ){
					$ret .= gdlr_hotel_room_info($post_option, array('bed', 'max-people', 'view', 'wifi'), true, 'new-style');
				}else{
					$ret .= gdlr_hotel_room_info($post_option, array('bed', 'max-people', 'view'));
				}
				$ret .= '<div class="gdlr-room-content">' . get_the_excerpt() . '</div>';
				$ret .= '<a class="gdlr-button with-border" href="' . get_permalink() . '">' . __('Check Details', 'gdlr-hotel') . '<i class="fa fa-long-arrow-right icon-long-arrow-right"></i></a>';
				$ret .= gdlr_hotel_room_info($post_option, array('price'), false);
				$ret .= '<div class="clear"></div>';
				$ret .= '</div>';
				$ret .= '<div class="clear"></div>';
				$ret .= '</li>'; // gdlr-item
			}
			$ret .= '</ul>';
			$ret .= '<div class="clear"></div>';
			$ret .= '</div>'; // close the flexslider
			$ret .= '</div>'; // close the gdlr-item
			wp_reset_postdata();
			
			return $ret;
		}
	}

	if( !function_exists('gdlr_get_classic_room') ){
		function gdlr_get_classic_room($query, $size, $thumbnail_size){
			$current_size = 0; $ret  = '';
			while($query->have_posts()){ $query->the_post();
				$post_option = json_decode(gdlr_decode_preventslashes(get_post_meta(get_the_ID(), 'post-option', true)), true);
				
				if( $current_size % $size == 0 ){
					$ret .= '<div class="clear"></div>';
				}	
				
				$ret .= '<div class="' . gdlr_get_column_class('1/' . $size) . '">';
				$ret .= '<div class="gdlr-item gdlr-room-item gdlr-classic-room">';
				$ret .= '<div class="gdlr-ux gdlr-classic-room-ux">';
				$ret .= '<div class="gdlr-room-thumbnail">' . gdlr_get_room_thumbnail($post_option, $thumbnail_size) . '</div>';
				$ret .= '<h3 class="gdlr-room-title"><a href="' . get_permalink() . '" >' . get_the_title() . '</a></h3>';
				$ret .= '<div class="gdlr-hotel-room-info">'; 
				$ret .= gdlr_hotel_room_info($post_option, array('bed', 'max-people', 'view', 'room-size'), false);
				$ret .= gdlr_hotel_room_info($post_option, array('price'), false);
				$ret .= '<div class="clear"></div></div>';
				$ret .= '<a class="gdlr-button with-border" href="' . get_permalink() . '">' . __('Check Details', 'gdlr-hotel') . '<i class="fa fa-long-arrow-right icon-long-arrow-right"></i></a>';
				$ret .= '</div>'; // gdlr-ux
				$ret .= '</div>'; // gdlr-item
				$ret .= '</div>'; // gdlr-column-class
				$current_size ++;
			}
			wp_reset_postdata();
			
			return $ret;
		}
	}
	
	if( !function_exists('gdlr_get_classic_room_carousel') ){
		function gdlr_get_classic_room_carousel($query, $size, $thumbnail_size){
			$ret = ''; 
			
			$ret .= '<div class="gdlr-room-carousel-item gdlr-item" >';
			$ret .= '<div class="flexslider" data-type="carousel" data-nav-container="room-item-wrapper" data-columns="' . $size . '" >';	
			$ret .= '<ul class="slides" >';			
			while($query->have_posts()){ $query->the_post();
				$post_option = json_decode(gdlr_decode_preventslashes(get_post_meta(get_the_ID(), 'post-option', true)), true);
		
				$ret .= '<li class="gdlr-item gdlr-classic-room">';
				$ret .= '<div class="gdlr-room-thumbnail">' . gdlr_get_room_thumbnail($post_option, $thumbnail_size) . '</div>';
				$ret .= '<h3 class="gdlr-room-title"><a href="' . get_permalink() . '" >' . get_the_title() . '</a></h3>';
				$ret .= '<div class="gdlr-hotel-room-info">'; 
				$ret .= gdlr_hotel_room_info($post_option, array('bed', 'max-people', 'view', 'room-size'), false);
				$ret .= gdlr_hotel_room_info($post_option, array('price'), false);
				$ret .= '<div class="clear"></div></div>';
				$ret .= '<a class="gdlr-button with-border" href="' . get_permalink() . '">' . __('Check Details', 'gdlr-hotel') . '<i class="fa fa-long-arrow-right icon-long-arrow-right"></i></a>';				
				$ret .= '</li>'; // gdlr-item
			}
			$ret .= '</ul>';
			$ret .= '<div class="clear"></div>';
			$ret .= '</div>'; // close the flexslider
			$ret .= '</div>'; // close the gdlr-item
			wp_reset_postdata();
			
			return $ret;
		}
	}		
	
	if( !function_exists('gdlr_get_modern_room') ){
		function gdlr_get_modern_room($query, $size, $thumbnail_size){
			$current_size = 0; $ret  = '';
			while($query->have_posts()){ $query->the_post();
				$post_option = json_decode(gdlr_decode_preventslashes(get_post_meta(get_the_ID(), 'post-option', true)), true);
				
				if( $current_size % $size == 0 ){
					$ret .= '<div class="clear"></div>';
				}	
				
				$ret .= '<div class="' . gdlr_get_column_class('1/' . $size) . '">';
				$ret .= '<div class="gdlr-item gdlr-room-item gdlr-modern-room">';
				$ret .= '<div class="gdlr-ux gdlr-modern-room-ux">';
				$ret .= '<div class="gdlr-room-thumbnail">' . gdlr_get_room_thumbnail($post_option, $thumbnail_size) . '</div>';
				$ret .= '<h3 class="gdlr-room-title"><a href="' . get_permalink() . '" >' . get_the_title() . '</a></h3>';
				$ret .= '<a href="' . get_permalink() . '" class="gdlr-room-detail">' . __('Check Details', 'gdlr-hotel') . '<i class="fa fa-long-arrow-right icon-long-arrow-right"></i></a>';
				$ret .= '</div>'; // gdlr-ux
				$ret .= '</div>'; // gdlr-item
				$ret .= '</div>'; // gdlr-column-class
				$current_size ++;
			}
			wp_reset_postdata();
			
			return $ret;
		}
	}	
	
	if( !function_exists('gdlr_get_modern_room_carousel') ){
		function gdlr_get_modern_room_carousel($query, $size, $thumbnail_size){
			$ret = ''; 
			
			$ret .= '<div class="gdlr-room-carousel-item gdlr-item" >';
			$ret .= '<div class="flexslider" data-type="carousel" data-nav-container="room-item-wrapper" data-columns="' . $size . '" >';	
			$ret .= '<ul class="slides" >';			
			while($query->have_posts()){ $query->the_post();
				$post_option = json_decode(gdlr_decode_preventslashes(get_post_meta(get_the_ID(), 'post-option', true)), true);
		
				$ret .= '<li class="gdlr-item gdlr-modern-room">';
				$ret .= '<div class="gdlr-room-thumbnail">' . gdlr_get_room_thumbnail($post_option, $thumbnail_size) . '</div>';
				$ret .= '<h3 class="gdlr-room-title"><a href="' . get_permalink() . '" >' . get_the_title() . '</a></h3>';
				$ret .= '<a href="' . get_permalink() . '" class="gdlr-room-detail">' . __('Check Details', 'gdlr-hotel') . '<i class="fa fa-long-arrow-right icon-long-arrow-right"></i></a>';
				$ret .= '</li>'; // gdlr-item
			}
			$ret .= '</ul>';
			$ret .= '<div class="clear"></div>';
			$ret .= '</div>'; // close the flexslider
			$ret .= '</div>'; // close the gdlr-item
			wp_reset_postdata();
			
			return $ret;
		}
	}	
	
	if( !function_exists('gdlr_get_modern_new_room') ){
		function gdlr_get_modern_new_room($query, $size, $thumbnail_size){
			$current_size = 0; $ret  = '';
			while($query->have_posts()){ $query->the_post();
				$post_option = json_decode(gdlr_decode_preventslashes(get_post_meta(get_the_ID(), 'post-option', true)), true);
				
				if( $current_size % $size == 0 ){
					$ret .= '<div class="clear"></div>';
				}	
				
				$ret .= '<div class="' . gdlr_get_column_class('1/' . $size) . '">';
				$ret .= '<div class="gdlr-item gdlr-room-item gdlr-modern-room-new">';
				$ret .= '<div class="gdlr-ux gdlr-modern-room-ux">';
				$ret .= '<div class="gdlr-room-thumbnail-wrap">';
				$ret .= '<div class="gdlr-room-thumbnail-inner">' . gdlr_get_room_thumbnail($post_option, $thumbnail_size) . '</div>';
				$ret .= '<div class="gdlr-room-thumbnail-overlay" >';
				$ret .= '<div class="gdlr-room-title-wrap" >';
				$ret .= '<h3 class="gdlr-room-title"><a href="' . get_permalink() . '" >' . get_the_title() . '</a></h3>';
				$ret .= '</div>';
				$ret .= '</div>';
				$ret .= '</div>';

				$ret .= gdlr_hotel_room_info($post_option, array('bed', 'max-people', 'view', 'wifi'), true, 'new-style');
				$ret .= '</div>'; // gdlr-ux
				$ret .= '</div>'; // gdlr-item
				$ret .= '</div>'; // gdlr-column-class
				$current_size ++;
			}
			wp_reset_postdata();
			
			return $ret;
		}
	}	
	
	if( !function_exists('gdlr_get_modern_new_room_carousel') ){
		function gdlr_get_modern_new_room_carousel($query, $size, $thumbnail_size){
			$ret = ''; 
			
			$ret .= '<div class="gdlr-room-carousel-item gdlr-item" >';
			$ret .= '<div class="flexslider" data-type="carousel" data-nav-container="room-item-wrapper" data-columns="' . $size . '" >';	
			$ret .= '<ul class="slides" >';			
			while($query->have_posts()){ $query->the_post();
				$post_option = json_decode(gdlr_decode_preventslashes(get_post_meta(get_the_ID(), 'post-option', true)), true);
		
				$ret .= '<li class="gdlr-item gdlr-modern-room-new">';
				$ret .= '<div class="gdlr-room-thumbnail-wrap">';
				$ret .= '<div class="gdlr-room-thumbnail-inner">' . gdlr_get_room_thumbnail($post_option, $thumbnail_size) . '</div>';
				$ret .= '<div class="gdlr-room-thumbnail-overlay" >';
				$ret .= '<div class="gdlr-room-title-wrap" >';
				$ret .= '<h3 class="gdlr-room-title"><a href="' . get_permalink() . '" >' . get_the_title() . '</a></h3>';
				$ret .= '</div>';
				$ret .= '</div>';
				$ret .= '</div>';

				$ret .= gdlr_hotel_room_info($post_option, array('bed', 'max-people', 'view', 'wifi'), true, 'new-style');
				$ret .= '</li>'; // gdlr-item
			}
			$ret .= '</ul>';
			$ret .= '<div class="clear"></div>';
			$ret .= '</div>'; // close the flexslider
			$ret .= '</div>'; // close the gdlr-item
			wp_reset_postdata();
			
			return $ret;
		}
	}	
	
	// get room thumbnail
	if( !function_exists('gdlr_get_room_thumbnail') ){
		function gdlr_get_room_thumbnail($post_option, $size = 'full'){
			
			if( is_single() && $post_option['inside-thumbnail-type'] != 'thumbnail-type'){ 
				$type = 'inside-';
			}else{ 
				$type = ''; 
			}
			
			$ret = '';
			switch($post_option[$type . 'thumbnail-type']){
				case 'feature-image':
					$image_id = get_post_thumbnail_id();
					if( !empty($image_id) ){
						if( is_single() ){
							$ret  = gdlr_get_image($image_id, $size, true);
						}else{
							$ret  = '<a href="' . get_permalink() . '" >';
							$ret .= gdlr_get_image($image_id, $size);
							$ret .= '</a>';
						}
					}
					break;			
				case 'image':
					$ret = gdlr_get_image($post_option[$type . 'thumbnail-image'], $size, true);
					break;
				case 'video': 
					if( is_single() ){
						$ret = gdlr_get_video($post_option[$type . 'thumbnail-video'], 'full');
					}else{
						$ret = gdlr_get_video($post_option[$type . 'thumbnail-video'], $size);
					}
					break;
				case 'slider': 
					$ret = gdlr_get_slider($post_option[$type . 'thumbnail-slider'], $size);
					break;					
				case 'stack-image': 
					$ret = gdlr_get_stack_images($post_option[$type . 'thumbnail-slider']);
					break;
				default :
					$ret = '';
			}			

			return $ret;
		}
	}

	// room info
	if( !function_exists('gdlr_hotel_room_info_new') ){
		function gdlr_hotel_room_info_new( $options = array(), $list = array(), $wrapper = true, $style = 'classic-style' ){
			$ret  = '';
			$max_count = 99;
			if( !empty($list) ){ $max_count = sizeOf($list); }
			$count = 1;

			$info_class  = 'gdlr-room-info';
			$info_class .= ($style == 'classic-style')? '': '-' . $style;
		
			foreach( $options as $option ){
				$ret .= '<div class="' . esc_attr($info_class) . '">';
				if( $style == 'classic-style' ){
					$ret .= '<i class="fa fa-check-square-o icon-check" ></i>';
					if( !empty($option['title']) ){
						$ret .= '<span class="gdlr-head">' . $option['title'] . '</span>';
					}
				}else{
					if( !empty($option['img']) ){
						$ret .= '<span class="gdlr-head" >';
						if( is_numeric($option['img']) ){
							$ret .= gdlr_get_image($option['img'], 'full');
						}else{
							$ret .= gdlr_get_image(get_template_directory_uri() . '/images/default-icon/' . $option['img'] . '.png', 'full');
						}
						$ret .= '</span>';
					}
				}
				if( !empty($option['value']) ){
					$ret .= '<span class="gdlr-tail">' . $option['value'] . '</span>';
				}
				$ret .= '</div>';
				
				if( $count >= $max_count ){ break; } $count++;
			}
 			
			if( $wrapper && !empty($ret) ){
				if( $style == 'classic-style' ){
					$ret = '<div class="gdlr-hotel-room-info">' . $ret . '<div class="clear"></div></div>';
				}else{
					$ret = '<div class="gdlr-hotel-room-info-' . esc_attr($style) . '">' . $ret . '<div class="clear"></div></div>';
				}
			}
			return $ret;
		}
	}
	
	if( !function_exists('gdlr_hotel_room_info') ){
		function gdlr_hotel_room_info( $post_option = array(), $list = array(), $wrapper = true, $style = 'classic-style' ){
			global $hotel_option;
			
			if( $list != array('price') && $list != array('price-break-down') && !empty($post_option['facilities-and-services']) ){
				$room_info = json_decode($post_option['facilities-and-services'], true);
				return gdlr_hotel_room_info_new($room_info, $list, $wrapper, $style);
			}
			
			if( empty($list) ){
				$list = array('bed', 'max-people', 'view', 'room-size', 'wifi', 'breakfast-included', 'room-service', 'airport-pickup-service');
			}
		
			$ret  = '';
			foreach( $list as $slug ){
				
				switch( $slug ){
					case 'price': 
					case 'price-break-down': 
						if( !empty($post_option['room-base-price']) ){
							$ret .= '<div class="gdlr-room-price">';
							if( $slug == 'price' || empty($hotel_option['booking-price-display']) || $hotel_option['booking-price-display'] == 'start-from' ){
								$ret .= '<span class="gdlr-head">' . __('Start From', 'gdlr-hotel') . '</span>';
								$ret .= '<span class="gdlr-tail">' . gdlr_hotel_money_format($post_option['room-base-price']) . ' / ' . __('Night', 'gdlr-hotel') . '</span>';
							}else{
								$price_breakdown = get_price_breakdown_popup($post_option);
								$ret .= '<span class="gdlr-tail">' . gdlr_hotel_money_format($price_breakdown['total']) . '</span>';
							}
							if( $slug == 'price-break-down' ){
								if( empty($price_breakdown) ){
									$price_breakdown = get_price_breakdown_popup($post_option);
								}
								$ret .= '<div class="gdlr-price-break-down" >' . __('* view price breakdown', 'gdlr-hotel');
								$ret .= $price_breakdown['price-breakdown'];
								$ret .= '</div>';
							}
							$ret .= '</div>';
						}
						break;
					case 'bed': 
						if( !empty($post_option[$slug]) ){
							$ret .= '<div class="gdlr-room-info">';
							$ret .= '<i class="fa fa-check-square-o icon-check" ></i>';
							$ret .= '<span class="gdlr-head">' . __('Bed', 'gdlr-hotel') . '</span>';
							$ret .= '<span class="gdlr-tail">' . $post_option[$slug] . '</span>';
							$ret .= '</div>';
						}
						break;
					case 'max-people':  
						if( !empty($post_option[$slug]) ){
							$ret .= '<div class="gdlr-room-info">';
							$ret .= '<i class="fa fa-check-square-o icon-check" ></i>';
							$ret .= '<span class="gdlr-head">' . __('Max', 'gdlr-hotel') . '</span>';
							$ret .= '<span class="gdlr-tail">' . $post_option[$slug] . ' ' . __('People', 'gdlr-hotel') . '</span>';
							$ret .= '</div>';
						}
						break;
					case 'view':  
						if( !empty($post_option[$slug]) ){
							$ret .= '<div class="gdlr-room-info">';
							$ret .= '<i class="fa fa-check-square-o icon-check" ></i>';
							$ret .= '<span class="gdlr-head">' . __('View', 'gdlr-hotel') . '</span>';
							$ret .= '<span class="gdlr-tail">' . $post_option[$slug] . '</span>';
							$ret .= '</div>';
						}
						break;
					case 'room-size':   
						if( !empty($post_option[$slug]) ){
							$ret .= '<div class="gdlr-room-info">';
							$ret .= '<i class="fa fa-check-square-o icon-check" ></i>';
							$ret .= '<span class="gdlr-head">' . __('Room Size', 'gdlr-hotel') . '</span>';
							$ret .= '<span class="gdlr-tail">' . $post_option[$slug] . '</span>';
							$ret .= '</div>';
						}
						break;
					case 'wifi':   
						if( !empty($post_option[$slug]) ){
							$ret .= '<div class="gdlr-room-info">';
							$ret .= '<i class="fa fa-check-square-o icon-check" ></i>';
							$ret .= '<span class="gdlr-head">' . __('Wifi', 'gdlr-hotel') . '</span>';
							$ret .= '<span class="gdlr-tail">' . $post_option[$slug] . '</span>';
							$ret .= '</div>';
						}
						break;
					case 'breakfast-included':  
						if( !empty($post_option[$slug]) ){
							$ret .= '<div class="gdlr-room-info">';
							$ret .= '<i class="fa fa-check-square-o icon-check" ></i>';
							$ret .= '<span class="gdlr-head">' . __('Breakfast Included', 'gdlr-hotel') . '</span>';
							$ret .= '<span class="gdlr-tail">' . $post_option[$slug] . '</span>';
							$ret .= '</div>';
						}
						break;
					case 'room-service':   
						if( !empty($post_option[$slug]) ){
							$ret .= '<div class="gdlr-room-info">';
							$ret .= '<i class="fa fa-check-square-o icon-check" ></i>';
							$ret .= '<span class="gdlr-head">' . __('Room Service', 'gdlr-hotel') . '</span>';
							$ret .= '<span class="gdlr-tail">' . $post_option[$slug] . '</span>';
							$ret .= '</div>';
						}
						break;
					case 'airport-pickup-service':  
						if( !empty($post_option[$slug]) ){
							$ret .= '<div class="gdlr-room-info">';
							$ret .= '<i class="fa fa-check-square-o icon-check" ></i>';
							$ret .= '<span class="gdlr-head">' . __('Airport Pickup Service', 'gdlr-hotel') . '</span>';
							$ret .= '<span class="gdlr-tail">' . $post_option[$slug] . '</span>';
							$ret .= '</div>';
						}
						break;
				}
			}
			
			if( $wrapper && !empty($ret) ){
				$ret = '<div class="gdlr-hotel-room-info">' . $ret . '<div class="clear"></div></div>';
			}
			return $ret;
		}
	}
	
	// get room thumbnail
	if( !function_exists('gdlr_get_room_thumbnail_control') ){
		function gdlr_get_room_thumbnail_control($post_option){	
			$control = '';
			$slider_var = '';
			
			if( $post_option['inside-thumbnail-type'] == 'thumbnail-type' && $post_option['thumbnail-type'] == 'slider' ){
				$slider_var = json_decode($post_option['thumbnail-slider']);
			}else if($post_option['inside-thumbnail-type'] == 'slider'){
				$slider_var = json_decode($post_option['inside-thumbnail-slider']);
			}
			
			if( !empty($slider_var) && !empty($slider_var[0]) ){
				$control .= '<ul class="gdlr-flex-thumbnail-control" id="gdlr-flex-thumbnail-control" >';
				foreach($slider_var[0] as $thumbnail){
					$control .= '<li>' . gdlr_get_image($thumbnail, 'thumbnail') . '</li>';
				}
				$control .= '</ul>';
			}
			
			return $control;
		}
	}
?>