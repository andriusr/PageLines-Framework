<?php
/*
	Section: NavXT Breadcrumbs
	Author: Simple Mama Blog Design
	Author URI: http://www.simplemama.com/
	External: http://www.simplemama.com/navxt-breadcrumbs/
	Demo: http://www.simplemama.com/navxt-breadcrumbs/
	Description: NavXT Breadcrumbs allows you to display post hierarchy for reader and SEO benefit. This section requires that the NavXT Breadcrumbs Wordpress plugin be installed and creates a seamless integration with PageLines Framework.
	Version: 1.0
	Class Name: NavXTBreadcrumbs
	Workswith: main, header, morefoot, footer
	Cloning: true
*/
/* This section was coded mostly by pross of PageLines. I have created a section for it to be used with PageLines Framework per client request.*/
class NavXTBreadcrumbs extends PageLinesSection {
	function section_persistent() {
		$options = array(
			'breadcrumb_no_link' => array(
					'default'	=> false,
					'type'		=> 'check',
					'inputlabel'=> __( 'Disable NavXT Breadcrumb Links?', 'pagelines' ),
					'title'		=> __( 'NavXT Breadcrumb Links', 'pagelines' ),
					'shortexp'	=> __( 'To link or not to link?', 'pagelines' ),
					'exp'		=> __( 'NavXT Breadcrumbs automatically links each breadcrumb. Checking this box will disable those links. Link titles will still be shown but will not be clickable.', 'pagelines' )
				),
		);
		
		pl_global_option( array( 'menu' => 'blog_and_posts', 'options' => $options, 'location' => 'top' ) );
	}
	/**
	* Section template
	*/
	function section_template($clone_id) {
		if( function_exists( 'bcn_display' ) )
			printf( '<div class="pl_breadcrumb" style ="text-align: center;">%s</div>', $this->get_breadcrumb() );
	}
	function get_breadcrumb() {
		ob_start();
		if( ploption( 'breadcrumb_no_link' ) ) {
			//Make new breadcrumb object
			$breadcrumb_trail = new bcn_breadcrumb_trail;
			$breadcrumb_trail->fill();
			bcn_display(false,false);
		}else{
			bcn_display();
		}
	return ob_get_clean();
	}
}