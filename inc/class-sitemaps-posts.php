<?php

/**
 * Class Core_Sitemaps_Posts.
 * Builds the sitemap pages for Posts.
 */
class Core_Sitemaps_Posts extends Core_Sitemaps_Provider {
	public function __construct() {
		$this->post_type = 'posts';
		parent::__construct();
	}

	/**
	 * Bootstrapping the filters.
	 */
	public function bootstrap() {
		add_action( 'core_sitemaps_setup_sitemaps', array( $this, 'register_sitemap' ), 99 );
		add_action( 'template_redirect', array( $this, 'render_sitemap' ) );
		parent::bootstrap();
	}

	/**
	 * Sets up rewrite rule for sitemap_index.
	 */
	public function register_sitemap() {
		$this->registry->add_sitemap( $this->post_type . 's', '^sitemap-posts\.xml$' );
	}

	/**
	 * Produce XML to output.
	 */
	public function render_sitemap() {
		$sitemap = get_query_var( 'sitemap' );
		$paged   = get_query_var( 'paged' );

		if ( 'posts' === $sitemap ) {
			$content = $this->get_content_per_page( $this->post_type, $paged );

			header( 'Content-type: application/xml; charset=UTF-8' );
			echo '<?xml version="1.0" encoding="UTF-8" ?>';
			echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
			foreach ( $content as $post ) {
				$url_data = array(
					'loc'        => get_permalink( $post ),
					// DATE_W3C does not contain a timezone offset, so UTC date must be used.
					'lastmod'    => mysql2date( DATE_W3C, $post->post_modified_gmt, false ),
					'priority'   => '0.5',
					'changefreq' => 'monthly',
				);
				printf(
					'<url>
<loc>%1$s</loc>
<lastmod>%2$s</lastmod>
<changefreq>%3$s</changefreq>
<priority>%4$s</priority>
</url>',
					esc_html( $url_data['loc'] ),
					esc_html( $url_data['lastmod'] ),
					esc_html( $url_data['changefreq'] ),
					esc_html( $url_data['priority'] )
				);
			}
			echo '</urlset>';
			exit;
		}
	}

	/**
	 * Get content for a page.
	 *
	 * @param string $post_type Name of the post_type.
	 * @param int    $page_num Page of results.
	 *
	 * @return int[]|WP_Post[] Query result.
	 */
	public function get_content_per_page( $post_type, $page_num = 1 ) {
		$query = new WP_Query();

		return $query->query(
			array(
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'post_type'      => $post_type,
				'posts_per_page' => CORE_SITEMAPS_POSTS_PER_PAGE,
				'paged'          => $page_num,
			)
		);
	}
}