<?php
/**
 * 
 *
 *  PageLines Posts Handling
 *
 *
 *  @package PageLines
 *  @subpackage Posts
 *  @since 2.0.b2
 *
 */
class PageLinesPosts {

	var $tabs = array();	
	
	/**
	 * PHP5 constructor
	 *
	 */
	function __construct( ) {
	
		global $pagelines_layout; 
		global $post;
		global $wp_query;
		
		$this->count = 1;  // Used to get the number of the post as we loop through them.
		$this->clipcount = 2; // The number of clips in a row

		$this->post_count = $wp_query->post_count;  // Used to prevent markup issues when there aren't an even # of posts.
		$this->paged = intval(get_query_var('paged')); // Control output if on a paginated page

		$this->thumb_space = get_option('thumbnail_size_w') + 33; // Space for thumb with padding

		add_filter('pagelines_post_metabar', 'do_shortcode', 20);

	}
	
	/**
	 * Loads the content using WP's standard output functions
	 *
	 * @since 2.0.0
	 *
	 */
	function load_loop(){
	
		if(have_posts())
			while (have_posts()) : the_post();  $this->get_article(); endwhile;
		else 
			$this->posts_404();
		
	}
	
	function get_article(){
		global $wp_query;
		
		/* clip handling */
		$clip = ($this->pagelines_show_clip($this->count, $this->paged)) ? true : false;
		$format = ($clip) ? 'clip' : 'feature';
		$clip_row_start = ($this->clipcount % 2 == 0) ? true : false;
		$clip_row_end = ( ($this->clipcount+1) % 2 == 0 || $this->count == $this->post_count ) ? true : false;
		
		$pagelines_post_classes = ($clip) ? ( $clip_row_end ? 'clip clip-right' : 'clip' ) : 'fpost';
		$post_classes = join(' ', get_post_class( $pagelines_post_classes ));
		
		$wrap_start = ( $clip && $clip_row_start ) ? sprintf('<div class="clip_box fix">') : ''; 	
		$wrap_end = ( $clip && $clip_row_end ) ? sprintf('</div>') : '';

		echo sprintf('%s<article class="%s" id="post-%s">%s%s</article>%s', $wrap_start, $post_classes, get_the_ID(), $this->post_header( $format ), $this->post_entry(), $wrap_end);
		
		// Count the clips
		if( $clip ) 
			$this->clipcount++;
		
		// Count the posts
		$this->count++;
	 }
	
	function post_entry(){ 
		
		if( $this->pagelines_show_content( get_the_ID() ) ){
		
			$the_tags = sprintf('<div class="tags">%s&nbsp;</div>', get_the_tag_list(__('Tagged with: ', 'pagelines'),' &bull; ','<br />') );
		
			$post_entry = sprintf('<div class="entry_wrap fix"><div class="entry_content">%s</div>%s</div>', $this->post_content(), $the_tags);
		
			return apply_filters('pagelines_post_entry', $post_entry);
		
		} else 
			return '';
	}
	
	function post_content(){
	
		ob_start();
			pagelines_register_hook( 'pagelines_loop_before_post_content', 'theloop' ); // Hook
			the_content( __('<p>Continue reading &raquo;</p>','pagelines') );
			echo '<div class="clear"></div>';
			if( is_single() || is_page() ) 
				wp_link_pages(array('before'=> __('<p class="content-pagination"><span class="cp-desc">pages:</span>', 'pagelines'), 'after' => '</p>', 'pagelink' => '<span class="cp-num">%</span>')); 
		
			// Edit Link
			$edit_type = (is_page()) ? __('Edit Page','pagelines') : __('Edit Post','pagelines');
			edit_post_link( '['.$edit_type.']', '', '');
			pagelines_register_hook( 'pagelines_loop_after_post_content', 'theloop' ); // Hook 
		$the_content = ob_get_clean();
		
		return $the_content;
		
	}
	
	function post_header( $format = '' ){ 
		
		if( $this->show_post_header() ){
			
			global $post;
			
			$thumb = ( $this->pagelines_show_thumb( get_the_ID() ) ) ? $this->post_thumbnail_markup() : '';
			
			$excerpt = ( $this->pagelines_show_excerpt( get_the_ID() ) ) ? $this->post_excerpt_markup( ) : '';
			
			$classes = (!$this->pagelines_show_thumb($post->ID)) ? 'post-nothumb' : '';
		
			$style = ($this->pagelines_show_thumb($post->ID)) ? 'margin-left:'.$this->thumb_space.'px' : '';
			
			$title = sprintf('<section class="bd post-title-section fix"><hgroup class="post-title fix">%s%s</hgroup></section>', $this->pagelines_get_post_title(), $this->pagelines_get_post_metabar( $format ));
			
			$post_header = sprintf('<section class="post-meta media fix">%s<section class="bd post-header fix %s" >%s %s</section></section>', $thumb, $classes, $title, $excerpt);
			
			return apply_filters( 'pagelines_post_header', $post_header );
			
		} else 
			return '';
		
			
	}
	
	
	/**
	 * Determines if the post title area should be shown
	 *
	 * @since 2.0.0
	 *
	 * @return bool True if the title area should be shown
	 */
	function show_post_header( ) {
		
		if( !is_page() || (is_page() && pagelines_option('pagetitles')) )
			return true;
		else
			return false;
		
	}
	
	/**
	 * Get post excerpt and markup
	 *
	 * @since 2.0.0
	 *
	 * @return string the excerpt markup
	 */
	function post_excerpt_markup( ) {
		
		$pagelines_excerpt = sprintf( '<aside class="post-excerpt">%s</aside>', get_the_excerpt() );
		
		if(pagelines_is_posts_page() && !$this->pagelines_show_content( get_the_ID() )) // 'Continue Reading' link
			$pagelines_excerpt .= $this->get_continue_reading_link( get_the_ID() );
		
		return apply_filters('pagelines_excerpt', $pagelines_excerpt);
		
	}
	
	
	/**
	 * Get post thumbnail and markup
	 *
	 * @since 2.0.0
	 *
	 * @return string the thumbnail markup
	 */
	function post_thumbnail_markup( ) {
		
		$thumb_link = sprintf('<a class="post-thumb img" href="%s" rel="bookmark" title="%s %s"><span class="c_img">%s</span></a>', get_permalink(), __('Link To', 'pagelines'), the_title_attribute( array('echo' => false) ), get_the_post_thumbnail(null, 'thumbnail') );
		
		
		return apply_filters('pagelines_thumb_markup', $thumb_link);
		
	}
	
	/**
	 * Adds the metabar or byline under the post title
	 *
	 * @since 1.1.0
	 */

	function pagelines_get_post_metabar( $format = '' ) {

		$metabar = '';

		if ( is_page() )
			return; // don't do post-info on pages

		if( $format == 'clip'){
			
			$metabar = ( pagelines_option( 'metabar_clip' ) ) 
				? pagelines_option( 'metabar_clip' ) 
				: sprintf( '%s [post_date] %s [post_author_posts_link] [post_edit]', __('On','pagelines'), __('By','pagelines'));

		} else {

			$metabar = ( pagelines_option( 'metabar_standard' ) ) 
				? pagelines_option( 'metabar_standard' ) 
				: sprintf( '%s [post_author_posts_link] %s [post_date] &middot; [post_comments] &middot; %s [post_categories] [post_edit]', __('By','pagelines'), __('On','pagelines'), __('In','pagelines'));

		}

		return sprintf( '<div class="metabar"><em>%s</em></div>', apply_filters('pagelines_post_metabar', $metabar, $format) );

	}

	/**
	 * 
	 *  Gets the Post Title for Blog Posts
	 *
	 *  @package PageLines
	 *  @subpackage Functions Library
	 *  @since 1.1.0
	 *
	 */
	function pagelines_get_post_title( $format = '' ){ 

		if(is_page() && pagelines_option('pagetitles')){
			$title = sprintf( '<h1 class="entry-title pagetitle">%s</h1>', apply_filters( 'pagelines_post_title_text', get_the_title() ) );	
		} elseif(!is_page()) {

			if ( is_singular() ) 
				$title = sprintf( '<h1 class="entry-title">%s</h1>', apply_filters( 'pagelines_post_title_text', get_the_title() ) );
			elseif( $format == 'clip')
				$title = sprintf( '<h4 class="entry-title"><a href="%s" title="%s" rel="bookmark">%s</a></h4>', get_permalink(), the_title_attribute('echo=0'), apply_filters( 'pagelines_post_title_text', get_the_title() ) );
			else
				$title = sprintf( '<h2 class="entry-title"><a href="%s" title="%s" rel="bookmark">%s</a></h2>', get_permalink(), the_title_attribute('echo=0'), apply_filters( 'pagelines_post_title_text', get_the_title() ) );

		} else {$title = '';}


		return apply_filters('pagelines_post_title_output', $title) . "\n";

	}



	/**
	 * 
	 *  Gets the continue reading link after excerpts
	 *
	 *  @package PageLines
	 *  @subpackage Functions Library
	 *  @since 1.3.0
	 *
	 */
	function get_continue_reading_link($post_id){

		$text = sprintf('%s', load_pagelines_option('continue_reading_text', __('Continue Reading', 'pagelines')));

		$thetext = apply_filters('continue_reading_link_text', $text);

		$link = sprintf('<a class="continue_reading_link" href="%s" title="%s %s">%s</a>', get_permalink(), __("View", 'pagelines'), the_title_attribute(array('echo'=> 0)), $thetext );

		return apply_filters('continue_reading_link', $link);
	}
	
	function pagelines_show_thumb($post = null, $location = null){

		 if( function_exists('the_post_thumbnail') && has_post_thumbnail($post) ){

			// For Hook Parsing
			if(is_admin() || !get_option(PAGELINES_SETTINGS)) return true;

			if($location == 'clip' && pagelines_option('thumb_clip')) return true;

			if( !isset($location) ){
				// Thumb Page
				if(is_single() && pagelines_option('thumb_single')) return true;

				// Blog Page
				elseif(is_home() && pagelines_option('thumb_blog')) return true;

				// Search Page
				elseif(is_search() && pagelines_option('thumb_search')) return true;

				// Category Page
				elseif(is_category() && pagelines_option('thumb_category')) return true;

				// Archive Page
				elseif(is_archive() && pagelines_option('thumb_archive')) return true;

				else return false;
			} else return false;
		} else return false;

	}
	
	function pagelines_show_excerpt($post = null){

			if(is_page())
				return false;

			// Thumb Page
			if(is_single() && pagelines_option('excerpt_single')) 
				return true;

			// Blog Page
			elseif(is_home() && pagelines_option('excerpt_blog')) return true;

			// Search Page
			elseif(is_search() && pagelines_option('excerpt_search')) return true;

			// Category Page
			elseif(is_category() && pagelines_option('excerpt_category')) return true;

			// Archive Page
			elseif(is_archive() && pagelines_option('excerpt_archive')) return true;

			else return false;
	}

	function pagelines_show_content($post = null){
			// For Hook Parsing
			if(is_admin()) 
				return true;

			// show on single post pages only
			if(is_page() || is_single()) 
				return true;

			// Blog Page
			elseif(is_home() && pagelines_option('content_blog')) 
				return true;

			// Search Page
			elseif(is_search() && pagelines_option('content_search')) 
				return true;

			// Category Page
			elseif(is_category() && pagelines_option('content_category')) 
				return true;

			// Archive Page
			elseif(is_archive() && pagelines_option('content_archive')) 
				return true;

			else 
				return false;

	}

	/*
		Show clip or full width post
	*/
	function pagelines_show_clip($count, $paged){

		if(!VPRO) 
			return false;

		if(is_home() && pagelines_option('blog_layout_mode') == 'magazine' && $count <= pagelines_option('full_column_posts') && $paged == 0)
			return false;

		elseif(pagelines_option('blog_layout_mode') != 'magazine') 
			return false;

		elseif(is_page() || is_single()) 
			return false;

		else 
			return true;
	}
	
	
	function posts_404(){
		
		$head = ( is_search() ) ? sprintf(__('No results for "%s"', 'pagelines'), get_search_query()) : __('Nothing Found', 'pagelines');
		
		$subhead = ( is_search() ) ? __('Try another search?', 'pagelines') : __('Sorry, what you are looking for isn\'t here.', 'pagelines');
		
		$the_text = sprintf('<h2 class="center">"%s"</h2><p class="subhead center">%s</p>', $head, $subhead);
		
		printf( '<section class="billboard">%s <div class="center fix">%s</div></section', apply_filters('pagelines_posts_404', $the_text), get_search_form( false ));
		
	}
	

}
/* ------- END OF CLASS -------- */


/**
 *  Determines if this page is showing several posts.
 *
 * @since 4.0.0
 */
function pagelines_is_posts_page(){	
	if(is_home() || is_search() || is_archive() || is_category() || is_tag()) return true; 
	else return false;
}

function pagelines_non_meta_data_page(){
	if(pagelines_is_posts_page() || is_404()) return true; 
	else return false;
}

function pagelines_special_pages(){
	return array('main-posts', 'main-search', 'main-archive', 'main-tag', 'main-category', 'main-404');
}
