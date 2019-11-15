<?php
/**
 * Class file for the Core_Sitemaps_Provider class.
 * This class is a base class for other sitemap providers to extend and contains shared functionality.
 *
 * @package Core_Sitemaps
 */

/**
 * Class Core_Sitemaps_Provider
 */
class Core_Sitemaps_Provider {
	/**
	 * Post type name.
	 *
	 * @var string
	 */
	protected $object_type = '';

	/**
	 * Sub type name.
	 *
	 * @var string
	 */
	protected $sub_type = '';

	/**
	 * Sitemap route
	 * Regex pattern used when building the route for a sitemap.
	 *
	 * @var string
	 */
	public $route = '';

	/**
	 * Sitemap slug
	 * Used for building sitemap URLs.
	 *
	 * @var string
	 */
	public $slug = '';

	/**
	 * Get a URL list for a post type sitemap.
	 *
	 * @param int $page_num Page of results.
	 * @return array $url_list List of URLs for a sitemap.
	 */
	public function get_url_list( $page_num ) {
		$type = $this->get_queried_type();

		$query = new WP_Query(
			array(
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'post_type'              => $type,
				'posts_per_page'         => CORE_SITEMAPS_POSTS_PER_PAGE,
				'paged'                  => $page_num,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
			)
		);

		$posts = $query->get_posts();

		$url_list = array();

		foreach ( $posts as $post ) {
			$url_list[] = array(
				'loc'     => get_permalink( $post ),
				'lastmod' => mysql2date( DATE_W3C, $post->post_modified_gmt, false ),
			);
		}

		/**
		 * Filter the list of URLs for a sitemap before rendering.
		 *
		 * @since 0.1.0
		 * @param array  $url_list List of URLs for a sitemap.
		 * @param string $type     Name of the post_type.
		 * @param int    $page_num Page of results.
		 */
		return apply_filters( 'core_sitemaps_post_url_list', $url_list, $type, $page_num );
	}

	/**
	 * Query for the add_rewrite_rule. Must match the number of Capturing Groups in the route regex.
	 *
	 * @return string Valid add_rewrite_rule query.
	 */
	public function rewrite_query() {
		return 'index.php?sitemap=' . $this->slug . '&paged=$matches[1]';
	}

	/**
	 * Return object type being queried.
	 *
	 * @return string Name of the object type.
	 */
	public function get_queried_type() {
		$type = $this->sub_type;
		if ( empty( $type ) ) {
			$type = $this->object_type;
		}

		return $type;
	}

	/**
	 * Query for determining the number of pages.
	 *
	 * @param string $type Object Type.
	 * @return int Total number of pages.
	 */
	public function max_num_pages( $type = null ) {
		if ( empty( $type ) ) {
			$type = $this->get_queried_type();
		}
		$query = new WP_Query(
			array(
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'post_type'      => $type,
				'posts_per_page' => CORE_SITEMAPS_POSTS_PER_PAGE,
				'paged'          => 1,
			)
		);

		return isset( $query->max_num_pages ) ? $query->max_num_pages : 1;
	}

	/**
	 * List of sitemaps exposed by this provider.
	 *
	 * @return array List of sitemaps.
	 */
	public function get_sitemaps() {
		$sitemaps = array();

		foreach ( $this->get_object_sub_types() as $type ) {
			$total = $this->max_num_pages( $type->name );
			for ( $i = 1; $i <= $total; $i ++ ) {
				$slug       = implode( '-', array_filter( array( $this->slug, $type->name, (string) $i ) ) );
				$sitemaps[] = $slug;
			}
		}

		return $sitemaps;
	}

	/**
	 * Stub a fake object type, to get the name of.
	 * This attempts compatibility with object types such as post, category, user.
	 * This must support providers for multiple sub-types, so a list is returned.
	 *
	 * @return array List of object types.
	 */
	public function get_object_sub_types() {
		$c       = new stdClass();
		$c->name = $this->sub_type;

		return array( $c );
	}
}