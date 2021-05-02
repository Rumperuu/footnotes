<?php // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
/**
 * Includes the core function of the Plugin - Search and Replace the Footnotes.
 *
 * @package footnotes
 * @since 1.5.0
 * @since 2.0.5 Enable all hoooks by default.
 * @since 2.8.0 Rename file from `task.php` to `class-footnotes-parser.php`,
 *                              move from `class/` sub-directory to `public/`.
 */

declare(strict_types=1);

namespace footnotes\general;

use footnotes\includes as Includes;

/**
 * Searches and replaces the footnotes and generates the reference container.
 *
 * @package footnotes
 * @since 1.5.0
 * @since 2.8.0 Rename class from `Footnotes_Task` to `Parser`.
 */
class Parser {

	/**
	 * Contains all footnotes found in the searched content.
	 *
	 * @since 1.5.0
	 * @var string[]
	 */
	public static array $a_arr_footnotes = array();

	/**
	 * Flag if the display of 'LOVE FOOTNOTES' is allowed on the current public page.
	 *
	 * @since 1.5.0
	 */
	public static bool $a_bool_allow_love_me = true;

	/**
	 * Prefix for the Footnote html element ID.
	 *
	 * @since 1.5.8
	 */
	public static string $a_str_prefix = '';

	/**
	 * Autoload a.k.a. infinite scroll, or archive view.
	 *
	 * As multiple posts are appended to each other, functions and fragment IDs must be disambiguated.
	 * post ID to make everything unique wrt infinite scroll and archive view.
	 *
	 * @since 2.0.6
	 */
	public static int $a_int_post_id = 0;

	/**
	 * Multiple reference containers in content and widgets.
	 *
	 * This ID disambiguates multiple reference containers in a page
	 * as they may occur when the widget_text hook is active and the page
	 * is built with Elementor and has an accordion or similar toggle sections.
	 *
	 * @since 2.2.9
	 * @var int   Incremented every time after a reference container is inserted.
	 */
	public static int $a_int_reference_container_id = 1;

	/**
	 * Hard links for AMP compatibility.
	 *
	 * A property because used both in {@see search()} and {@see reference_container()}.
	 *
	 * @since 2.0.0
	 */
	public static bool $a_bool_hard_links_enabled = false;

	/**
	 * The referrer slug.
	 *
	 * @since 2.3.0
	 * @var string
	 */
	public static $a_str_referrer_link_slug = 'r';

	/**
	 * The footnote slug.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	public static $a_str_footnote_link_slug = 'f';

	/**
	 * The slug and identifier separator.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	private static $a_str_link_ids_separator = '+';

	/**
	 * Contains the concatenated fragment ID base.
	 *
	 * @since 2.3.0
	 *
	 * @var string
	 */
	public static $a_str_post_container_id_compound = '';

	/**
	 * Scroll offset.
	 *
	 * Websites may use high fixed headers not contracting at scroll.
	 * Scroll offset may now need to get into inline CSS.
	 * Hence it needs to be loaded twice, because priority levels may not match.
	 *
	 * @since 2.1.4
	 */
	public static int $a_int_scroll_offset = 34;

	/*
	 * Optional link element for footnote referrers and backlinks
	 *
	 * Although widely used for that purpose, hyperlinks are disliked for footnote linking.
	 * Browsers may need to be prevented from logging these clicks in the browsing history,
	 * as logging compromises the usability of the 'return to previous' button in browsers.
	 * For that purpose, and for scroll animation, this linking is performed by JavaScript.
	 *
	 * Link elements raise concerns, so that mitigating their proliferation may be desired.
	 *
	 * By contrast, due to an insufficiency in the CSS standard, coloring elements with the
	 * theme’s link color requires real link elements and cannot be done with named colors,
	 * as CSS does not support 'color: link|hover|active|visited', after the pseudo-classes
	 * of the link element.
	 *
	 * Yet styling these elements with the link color is not universally preferred, so that
	 * the very presence of these link elements may need to be avoided.
	 */
	/**
	 * The span element name.
	 *
	 * @since 2.3.0
	 * @todo Remove.
	 */
	public static string $a_str_link_span = 'span';

	/**
	 * The opening tag.
	 *
	 * @since 2.3.0
	 * @todo Remove.
	 */
	public static string $a_str_link_open_tag = '';

	/**
	 * The closing tag.
	 *
	 * @since 2.3.0
	 * @todo Remove.
	 */
	public static string $a_str_link_close_tag = '';

	/*
	 * Dedicated tooltip text.
	 *
	 * Tooltips can display another content than the footnote entry
	 * in the reference container. The trigger is a shortcode in
	 * the footnote text separating the tooltip text from the note.
	 * That is consistent with what WordPress does for excerpts.
	 */

	/**
	 * The tooltip delimiter shortcode.
	 *
	 * @since 2.5.2
	 * @var string
	 */
	public static $a_str_tooltip_shortcode = '[[/tooltip]]';

	/**
	 * The tooltip delimiter shortcode length.
	 *
	 * @since 2.5.2
	 */
	public static int $a_int_tooltip_shortcode_length = 12;

	/**
	 * Whether to mirror the tooltip text in the reference container.
	 *
	 * @since 2.5.2
	 */
	public static bool $a_bool_mirror_tooltip_text = false;

	/**
	 * Footnote delimiter start short code.
	 *
	 * @since 1.5.0
	 * @since 2.6.2  Move from constant to class property.
	 */
	public static string|int $a_str_start_tag = '';

	/**
	 * Footnote delimiter end short code.
	 *
	 * @since 1.5.0
	 * @since 2.6.2  Move from constant to class property.
	 */
	public static string|int $a_str_end_tag = '';

	/**
	 * Footnote delimiter start short code in RegEx format.
	 *
	 * @since 2.4.0
	 * @since 2.6.2  Move from global constant to class property.
	 */
	public static ?string $a_str_start_tag_regex = '';

	/**
	 * Footnote delimiter end short code in RegEx format.
	 *
	 * @since 2.4.0
	 * @since 2.6.2  Move from global constant to class property.
	 */
	public static ?string $a_str_end_tag_regex = '';

	/**
	 * Footnote delimiter syntax validation enabled.
	 *
	 * The algorithm first checks for balanced footnote opening and closing tag short codes.
	 * The first encountered error triggers the display of a warning below the post title.
	 *
	 * Unbalanced short codes have caused significant trouble because they are hard to detect.
	 * Any compiler or other tool reports syntax errors in the first place. Footnotes' exception
	 * is considered a design flaw, and the feature is released as a bug fix after overdue 2.3.0
	 * released in urgency to provide AMP compat before 2021.
	 *
	 * @since 2.4.0
	 */
	public static bool $a_bool_syntax_error_flag = true;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 2.8.0
	 * @todo Reorganise dependencies.
	 * @todo Move call to `register_hooks()` to {@see General}.
	 */
	public function __construct() {
		// TODO: Reorg dependencies.
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-config.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-convert.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-settings.php';
		require_once plugin_dir_path( __DIR__ ) . 'includes/class-template.php';

		// TODO: Move to `General`.
		$this->register_hooks();
	}

	/**
	 * Register WordPress hooks to replace Footnotes in the content of a public page.
	 *
	 * @since 1.5.0
	 * @since 1.5.4  Add support for @see 'the_post' hook.
	 * @since 2.0.5  Enable all hooks by default.
	 * @since 2.1.0  Remove @see 'the_post' support.
	 * @todo Move to {@see General}.
	 */
	public function register_hooks(): void {
		// Get values from settings.
		$l_int_the_title_priority    = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_EXPERT_LOOKUP_THE_TITLE_PRIORITY_LEVEL );
		$l_int_the_content_priority  = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_EXPERT_LOOKUP_THE_CONTENT_PRIORITY_LEVEL );
		$l_int_the_excerpt_priority  = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_EXPERT_LOOKUP_THE_EXCERPT_PRIORITY_LEVEL );
		$l_int_widget_title_priority = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_EXPERT_LOOKUP_WIDGET_TITLE_PRIORITY_LEVEL );
		$l_int_widget_text_priority  = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_EXPERT_LOOKUP_WIDGET_TEXT_PRIORITY_LEVEL );

		// PHP_INT_MAX can be set by -1.
		$l_int_the_title_priority    = ( -1 === $l_int_the_title_priority ) ? PHP_INT_MAX : $l_int_the_title_priority;
		$l_int_the_content_priority  = ( -1 === $l_int_the_content_priority ) ? PHP_INT_MAX : $l_int_the_content_priority;
		$l_int_the_excerpt_priority  = ( -1 === $l_int_the_excerpt_priority ) ? PHP_INT_MAX : $l_int_the_excerpt_priority;
		$l_int_widget_title_priority = ( -1 === $l_int_widget_title_priority ) ? PHP_INT_MAX : $l_int_widget_title_priority;
		$l_int_widget_text_priority  = ( -1 === $l_int_widget_text_priority ) ? PHP_INT_MAX : $l_int_widget_text_priority;

		// Append custom css to the header.
		add_filter(
			'wp_head',
			fn() => $this->footnotes_output_head(),
			PHP_INT_MAX
		);

		// Append the love and share me slug to the footer.
		add_filter(
			'wp_footer',
			fn() => $this->footnotes_output_footer(),
			PHP_INT_MAX
		);

		if ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_EXPERT_LOOKUP_THE_TITLE ) ) ) {
			add_filter(
				'the_title',
				fn( string $p_str_content): string => $this->footnotes_in_title( $p_str_content ),
				$l_int_the_title_priority
			);
		}

		// Configurable priority level for reference container relative positioning; default 98.
		if ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_EXPERT_LOOKUP_THE_CONTENT ) ) ) {
			add_filter(
				'the_content',
				fn( string $p_str_content): string => $this->footnotes_in_content( $p_str_content ),
				$l_int_the_content_priority
			);

			/**
			 * Hook for category pages.
			 *
			 * Category pages can have rich HTML content in a term description with
			 * article status.
			 * For this to happen, WordPress' built-in partial HTML blocker needs to
			 * be disabled.
			 *
			 * @link https://docs.woocommerce.com/document/allow-html-in-term-category-tag-descriptions/
			 *
			 * @since 2.5.0
			 */
			add_filter(
				'term_description',
				fn( string $p_str_content): string => $this->footnotes_in_content( $p_str_content ),
				$l_int_the_content_priority
			);

			/**
			 * Hook for popup maker popups.
			 *
			 * - Bugfix: Hooks: support footnotes in Popup Maker popups, thanks to @squatcher bug report.
			 *
			 * @reporter @squatcher
			 * @link https://wordpress.org/support/topic/footnotes-use-in-popup-maker/
			 *
			 * @since 2.5.1
			 */
			add_filter(
				'pum_popup_content',
				fn( string $p_str_content): string => $this->footnotes_in_content( $p_str_content ),
				$l_int_the_content_priority
			);
		}

		if ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_EXPERT_LOOKUP_THE_EXCERPT ) ) ) {
			/**
			 * Adds a filter to the excerpt hook.
			 *
			 * @since 1.5.0  The hook @see 'get_the_excerpt' is filtered too.
			 * @since 1.5.5  The hook @see 'get_the_excerpt' is removed but not documented in changelog or docblock.
			 * @since 2.6.2  The hook @see 'get_the_excerpt' is readded when attempting to debug excerpt handling.
			 * @since 2.6.6  The hook @see 'get_the_excerpt' is removed again because it seems to cause issues in some themes.
			 */
			add_filter(
				'the_excerpt',
				fn( string $p_str_excerpt): string => $this->footnotes_in_excerpt( $p_str_excerpt ),
				$l_int_the_excerpt_priority
			);
		}

		if ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_EXPERT_LOOKUP_WIDGET_TITLE ) ) ) {
			/**
			 * TODO
			 */
			add_filter(
				'widget_title',
				fn( string $p_str_content): string => $this->footnotes_in_widget_title( $p_str_content ),
				$l_int_widget_title_priority
			);
		}

		if ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_EXPERT_LOOKUP_WIDGET_TEXT ) ) ) {
			/**
			 * TODO
			 */
			add_filter(
				'widget_text',
				fn( string $p_str_content): string => $this->footnotes_in_widget_text( $p_str_content ),
				$l_int_widget_text_priority
			);
		}

		// Reset stored footnotes when displaying the header.
		self::$a_arr_footnotes      = array();
		self::$a_bool_allow_love_me = true;
	}

	/**
	 * Outputs the custom css to the header of the public page.
	 *
	 * @since 1.5.0
	 * @todo Refactor to enqueue stylesheets properly in {@see General}.
	 */
	public function footnotes_output_head(): void {

		// Insert start tag without switching out of PHP.
		echo "\r\n<style type=\"text/css\" media=\"all\">\r\n";

		/*
		 * Enables CSS smooth scrolling.
		 *
		 * Native smooth scrolling only works in recent browsers.
		 */
		if ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_CSS_SMOOTH_SCROLLING ) ) ) {
			echo "html {scroll-behavior: smooth;}\r\n";
		}

		/*
		 * Normalizes the referrers' vertical alignment and font size.
		 *
		 * Cannot be included in external stylesheet, as it is only optional.
		 * The scope is variable too: referrers only, or all superscript elements.
		 */
		$l_str_normalize_superscript = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTE_REFERRERS_NORMAL_SUPERSCRIPT );
		if ( 'no' !== $l_str_normalize_superscript ) {
			if ( 'all' === $l_str_normalize_superscript ) {
				echo 'sup {';
			} else {
				echo '.footnote_plugin_tooltip_text {';
			}
			echo "vertical-align: super; font-size: smaller; position: static;}\r\n";
		}

		// Reference container display on home page.
		if ( ! Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_REFERENCE_CONTAINER_START_PAGE_ENABLE ) ) ) {

			echo ".home .footnotes_reference_container { display: none; }\r\n";
		}

		// Reference container top and bottom margins.
		$l_int_reference_container_top_margin    = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_REFERENCE_CONTAINER_TOP_MARGIN );
		$l_int_reference_container_bottom_margin = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_REFERENCE_CONTAINER_BOTTOM_MARGIN );
		echo '.footnotes_reference_container {margin-top: ';
		echo empty( $l_int_reference_container_top_margin ) ? '0' : $l_int_reference_container_top_margin;
		echo 'px !important; margin-bottom: ';
		echo empty( $l_int_reference_container_bottom_margin ) ? '0' : $l_int_reference_container_bottom_margin;
		echo "px !important;}\r\n";

		// Reference container label bottom border.
		if ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_REFERENCE_CONTAINER_LABEL_BOTTOM_BORDER ) ) ) {
			echo '.footnote_container_prepare > ';
			echo Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_REFERENCE_CONTAINER_LABEL_ELEMENT );
			echo " {border-bottom: 1px solid #aaaaaa !important;}\r\n";
		}

		/*
		 * Reference container table row borders.
		 *
		 * Moving this internal CSS to external using `wp_add_inline_style()` is
		 * discouraged, because that screws up support, and it is pointless from
		 * a performance point of view. Moreover, that would cause cache busting
		 * issues as browsers won’t reload these style sheets after settings are
		 * changed while the version string is not.
		 */
		if ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_REFERENCE_CONTAINER_ROW_BORDERS_ENABLE ) ) ) {
			echo '.footnotes_table, .footnotes_plugin_reference_row {';
			echo 'border: 1px solid #060606;';
			echo " !important;}\r\n";
			// Adapt left padding to the presence of a border.
			echo '.footnote_plugin_index, .footnote_plugin_index_combi {';
			echo "padding-left: 6px !important}\r\n";
		}

		// Ref container first column width and max-width.
		$l_bool_column_width_enabled     = Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_BACKLINKS_COLUMN_WIDTH_ENABLED ) );
		$l_bool_column_max_width_enabled = Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_BACKLINKS_COLUMN_MAX_WIDTH_ENABLED ) );

		if ( $l_bool_column_width_enabled || $l_bool_column_max_width_enabled ) {
			echo '.footnote-reference-container { table-layout: fixed; }';
			echo '.footnote_plugin_index, .footnote_plugin_index_combi {';

			if ( $l_bool_column_width_enabled ) {
				$l_int_column_width_scalar = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_BACKLINKS_COLUMN_WIDTH_SCALAR );
				$l_str_column_width_unit   = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_BACKLINKS_COLUMN_WIDTH_UNIT );

				if ( ! empty( $l_int_column_width_scalar ) ) {
					if ( '%' === $l_str_column_width_unit && $l_int_column_width_scalar > 100 ) {
						$l_int_column_width_scalar = 100;
					}
				} else {
					$l_int_column_width_scalar = 0;
				}

				echo ' width: ' . $l_int_column_width_scalar . $l_str_column_width_unit . ' !important;';
			}

			if ( $l_bool_column_max_width_enabled ) {
				$l_int_column_max_width_scalar = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_BACKLINKS_COLUMN_MAX_WIDTH_SCALAR );
				$l_str_column_max_width_unit   = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_BACKLINKS_COLUMN_MAX_WIDTH_UNIT );

				if ( ! empty( $l_int_column_max_width_scalar ) ) {
					if ( '%' === $l_str_column_max_width_unit && $l_int_column_max_width_scalar > 100 ) {
						$l_int_column_max_width_scalar = 100;
					}
				} else {
					$l_int_column_max_width_scalar = 0;
				}

				echo ' max-width: ' . $l_int_column_max_width_scalar . $l_str_column_max_width_unit . ' !important;';

			}
			echo "}\r\n";
		}

		// Hard links scroll offset.
		self::$a_bool_hard_links_enabled = Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_HARD_LINKS_ENABLE ) );

		// Correct hard links enabled status depending on AMP-compatible or alternative reference container enabled status.
		if ( General::$a_bool_amp_enabled || 'jquery' !== General::$a_str_script_mode ) {
			self::$a_bool_hard_links_enabled = true;
		}

		self::$a_int_scroll_offset = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_FOOTNOTES_SCROLL_OFFSET );
		if ( self::$a_bool_hard_links_enabled ) {
			echo '.footnote_referrer_anchor, .footnote_item_anchor {bottom: ';
			echo self::$a_int_scroll_offset;
			echo "vh;}\r\n";
		}

		// Tooltips.
		if ( General::$a_bool_tooltips_enabled ) {
			echo '.footnote_tooltip {';

			// Tooltip appearance: Tooltip font size.
			echo ' font-size: ';
			if ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_MOUSE_OVER_BOX_FONT_SIZE_ENABLED ) ) ) {
				echo Includes\Settings::instance()->get( \footnotes\includes\Settings::C_FLO_MOUSE_OVER_BOX_FONT_SIZE_SCALAR );
				echo Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_MOUSE_OVER_BOX_FONT_SIZE_UNIT );
			} else {
				echo 'inherit';
			}
			echo ' !important;';

			// Tooltip Text color.
			$l_str_color = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_MOUSE_OVER_BOX_COLOR );
			if ( ! empty( $l_str_color ) ) {
				printf( ' color: %s !important;', $l_str_color );
			}

			// Tooltip Background color.
			$l_str_background = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_MOUSE_OVER_BOX_BACKGROUND );
			if ( ! empty( $l_str_background ) ) {
				printf( ' background-color: %s !important;', $l_str_background );
			}

			// Tooltip Border width.
			$l_int_border_width = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_FOOTNOTES_MOUSE_OVER_BOX_BORDER_WIDTH );
			if ( ! empty( $l_int_border_width ) && (int) $l_int_border_width > 0 ) {
				printf( ' border-width: %dpx !important; border-style: solid !important;', $l_int_border_width );
			}

			// Tooltip Border color.
			$l_str_border_color = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_MOUSE_OVER_BOX_BORDER_COLOR );
			if ( ! empty( $l_str_border_color ) ) {
				printf( ' border-color: %s !important;', $l_str_border_color );
			}

			// Tooltip Corner radius.
			$l_int_border_radius = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_FOOTNOTES_MOUSE_OVER_BOX_BORDER_RADIUS );
			if ( ! empty( $l_int_border_radius ) && (int) $l_int_border_radius > 0 ) {
				printf( ' border-radius: %dpx !important;', $l_int_border_radius );
			}

			// Tooltip Shadow color.
			$l_str_box_shadow_color = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_MOUSE_OVER_BOX_SHADOW_COLOR );
			if ( ! empty( $l_str_box_shadow_color ) ) {
				printf( ' -webkit-box-shadow: 2px 2px 11px %s;', $l_str_box_shadow_color );
				printf( ' -moz-box-shadow: 2px 2px 11px %s;', $l_str_box_shadow_color );
				printf( ' box-shadow: 2px 2px 11px %s;', $l_str_box_shadow_color );
			}

			// Tooltip position, dimensions and timing.
			if ( ! General::$a_bool_alternative_tooltips_enabled && ! General::$a_bool_amp_enabled ) {
				/*
				 * Dimensions of jQuery tooltips.
				 *
				 * Position and timing of jQuery tooltips are script-defined.
				 */
				$l_int_max_width = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_FOOTNOTES_MOUSE_OVER_BOX_MAX_WIDTH );
				if ( ! empty( $l_int_max_width ) && (int) $l_int_max_width > 0 ) {
					printf( ' max-width: %dpx !important;', $l_int_max_width );
				}
				echo "}\r\n";

			} else {
				// AMP-compatible and alternative tooltips.
				echo "}\r\n";

				// Dimensions.
				$l_int_alternative_tooltip_width = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_FOOTNOTES_ALTERNATIVE_MOUSE_OVER_BOX_WIDTH );
				echo '.footnote_tooltip.position {';
				echo ' width: max-content; ';

				// Set also as max-width wrt short tooltip shrinking.
				echo ' max-width: ' . $l_int_alternative_tooltip_width . 'px;';

				// Position.
				$l_str_alternative_position = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_ALTERNATIVE_MOUSE_OVER_BOX_POSITION );
				$l_int_offset_x             = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_FOOTNOTES_ALTERNATIVE_MOUSE_OVER_BOX_OFFSET_X );

				if ( 'top left' === $l_str_alternative_position || 'bottom left' === $l_str_alternative_position ) {
					echo ' right: ' . ( empty( $l_int_offset_x ) ? 0 : $l_int_offset_x ) . 'px;';
				} else {
					echo ' left: ' . ( empty( $l_int_offset_x ) ? 0 : $l_int_offset_x ) . 'px;';
				}

				$l_int_offset_y = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_FOOTNOTES_ALTERNATIVE_MOUSE_OVER_BOX_OFFSET_Y );

				if ( 'top left' === $l_str_alternative_position || 'top right' === $l_str_alternative_position ) {
					echo ' bottom: ' . ( empty( $l_int_offset_y ) ? 0 : $l_int_offset_y ) . 'px;';
				} else {
					echo ' top: ' . ( empty( $l_int_offset_y ) ? 0 : $l_int_offset_y ) . 'px;';
				}
				echo "}\r\n";

				// Timing.
				$l_int_fade_in_delay     = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_MOUSE_OVER_BOX_FADE_IN_DELAY );
				$l_int_fade_in_delay     = empty( $l_int_fade_in_delay ) ? '0' : $l_int_fade_in_delay;
				$l_int_fade_in_duration  = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_MOUSE_OVER_BOX_FADE_IN_DURATION );
				$l_int_fade_in_duration  = empty( $l_int_fade_in_duration ) ? '0' : $l_int_fade_in_duration;
				$l_int_fade_out_delay    = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_MOUSE_OVER_BOX_FADE_OUT_DELAY );
				$l_int_fade_out_delay    = empty( $l_int_fade_out_delay ) ? '0' : $l_int_fade_out_delay;
				$l_int_fade_out_duration = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_MOUSE_OVER_BOX_FADE_OUT_DURATION );
				$l_int_fade_out_duration = empty( $l_int_fade_out_duration ) ? '0' : $l_int_fade_out_duration;

				/*
				 * AMP-compatible tooltips.
				 *
				 * To streamline internal CSS, immutable rules are in external stylesheet.
				 */
				if ( General::$a_bool_amp_enabled ) {

					echo 'span.footnote_referrer > span.footnote_tooltip {';
					echo 'transition-delay: ' . $l_int_fade_out_delay . 'ms;';
					echo 'transition-duration: ' . $l_int_fade_out_duration . 'ms;';
					echo "}\r\n";

					echo 'span.footnote_referrer:focus-within > span.footnote_tooltip, span.footnote_referrer:hover > span.footnote_tooltip {';
					echo 'transition-delay: ' . $l_int_fade_in_delay . 'ms;';
					echo 'transition-duration: ' . $l_int_fade_in_duration . 'ms;';
					echo "}\r\n";

					/*
					 * Alternative tooltips.
					 *
					 * To streamline internal CSS, immutable rules are in external stylesheet.
					 */
				} else {

					echo '.footnote_tooltip.hidden {';
					echo 'transition-delay: ' . $l_int_fade_out_delay . 'ms;';
					echo 'transition-duration: ' . $l_int_fade_out_duration . 'ms;';
					echo "}\r\n";

					echo '.footnote_tooltip.shown {';
					echo 'transition-delay: ' . $l_int_fade_in_delay . 'ms;';
					echo 'transition-duration: ' . $l_int_fade_in_duration . 'ms;';
					echo "}\r\n";
				}
			}
		}

		/*
		 * Custom CSS.
		 *
		 * Set custom CSS to override settings, not conversely.
		 * Legacy Custom CSS is used until it’s set to disappear after dashboard tab migration.
		 */
		if ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_CUSTOM_CSS_LEGACY_ENABLE ) ) ) {
			echo Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_CUSTOM_CSS );
			echo "\r\n";
		}
		echo Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_CUSTOM_CSS_NEW );

		// Insert end tag without switching out of PHP.
		echo "\r\n</style>\r\n";

		/*
		 * Alternative tooltip implementation relying on plain JS and CSS transitions.
		 *
		 * The script for alternative tooltips is printed formatted, not minified,
		 * for transparency. It isn’t indented though (the PHP open tag neither).
		 */
		if ( General::$a_bool_alternative_tooltips_enabled ) {

			// Start internal script.
			?>
<script content="text/javascript">
	function footnote_tooltip_show(footnote_tooltip_id) {
		document.getElementById(footnote_tooltip_id).classList.remove('hidden');
		document.getElementById(footnote_tooltip_id).classList.add('shown');
	}
	function footnote_tooltip_hide(footnote_tooltip_id) {
		document.getElementById(footnote_tooltip_id).classList.remove('shown');
		document.getElementById(footnote_tooltip_id).classList.add('hidden');
	}
</script>
			<?php
			// Indenting this PHP open tag would mess up the page source.
			// End internal script.
		};
	}

	/**
	 * Displays the 'LOVE FOOTNOTES' slug if enabled.
	 *
	 * @since 1.5.0
	 */
	public function footnotes_output_footer(): void {
		if ( 'footer' === Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_REFERENCE_CONTAINER_POSITION ) ) {
			echo $this->reference_container();
		}
		// Get setting for love and share this plugin.
		$l_str_love_me_index = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_LOVE );
		// Check if the admin allows to add a link to the footer.
		if ( empty( $l_str_love_me_index ) || 'no' === strtolower( $l_str_love_me_index ) || ! self::$a_bool_allow_love_me ) {
			return;
		}
		// Set a hyperlink to the word "footnotes" in the Love slug.
		$l_str_linked_name = sprintf( '<a href="https://wordpress.org/plugins/footnotes/" target="_blank" style="text-decoration:none;">%s</a>', \footnotes\includes\Config::C_STR_PLUGIN_PUBLIC_NAME );
		// Get random love me text.
		if ( 'random' === strtolower( $l_str_love_me_index ) ) {
			$l_str_love_me_index = 'text-' . wp_rand( 1, 7 );
		}
		switch ( $l_str_love_me_index ) {
			// Options named wrt backcompat, simplest is default.
			case 'text-1':
				/* Translators: 2: Link to plugin page 1: Love heart symbol */
				$l_str_love_me_text = sprintf( __( 'I %2$s %1$s', 'footnotes' ), $l_str_linked_name, \footnotes\includes\Config::C_STR_LOVE_SYMBOL );
				break;
			case 'text-2':
				/* Translators: %s: Link to plugin page */
				$l_str_love_me_text = sprintf( __( 'This website uses the awesome %s plugin.', 'footnotes' ), $l_str_linked_name );
				break;
			case 'text-4':
				/* Translators: 1: Link to plugin page 2: Love heart symbol */
				$l_str_love_me_text = sprintf( '%1$s %2$s', $l_str_linked_name, \footnotes\includes\Config::C_STR_LOVE_SYMBOL );
				break;
			case 'text-5':
				/* Translators: 1: Love heart symbol 2: Link to plugin page */
				$l_str_love_me_text = sprintf( '%1$s %2$s', \footnotes\includes\Config::C_STR_LOVE_SYMBOL, $l_str_linked_name );
				break;
			case 'text-6':
				/* Translators: %s: Link to plugin page */
				$l_str_love_me_text = sprintf( __( 'This website uses %s.', 'footnotes' ), $l_str_linked_name );
				break;
			case 'text-7':
				/* Translators: %s: Link to plugin page */
				$l_str_love_me_text = sprintf( __( 'This website uses the %s plugin.', 'footnotes' ), $l_str_linked_name );
				break;
			case 'text-3':
			default:
				/* Translators: %s: Link to plugin page */
				$l_str_love_me_text = $l_str_linked_name;
				break;
		}
		echo sprintf( '<div style="text-align:center; color:#acacac;">%s</div>', $l_str_love_me_text );
	}

	/**
	 * Replaces footnotes in the post/page title.
	 *
	 * @since 1.5.0
	 *
	 * @param string $p_str_content  Title.
	 * @return string  $p_str_content  Title with replaced footnotes.
	 */
	public function footnotes_in_title( string $p_str_content ): string {
		// Appends the reference container if set to "post_end".
		return $this->exec( $p_str_content, false );
	}

	/**
	 * Replaces footnotes in the content of the current page/post.
	 *
	 * @since 1.5.0
	 *
	 * @param string $p_str_content  Page/Post content.
	 * @return string  $p_str_content  Content with replaced footnotes.
	 */
	public function footnotes_in_content( string $p_str_content ): string {

		$l_str_ref_container_position            = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_REFERENCE_CONTAINER_POSITION );
		$l_str_footnote_section_shortcode        = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTE_SECTION_SHORTCODE );
		$l_int_footnote_section_shortcode_length = strlen( $l_str_footnote_section_shortcode );

		if ( ! str_contains( $p_str_content, (string) $l_str_footnote_section_shortcode ) ) {

			// phpcs:disable WordPress.PHP.YodaConditions.NotYoda
			// Appends the reference container if set to "post_end".
			return $this->exec( $p_str_content, 'post_end' === $l_str_ref_container_position );
			// phpcs:enable WordPress.PHP.YodaConditions.NotYoda

		} else {

			$l_str_rest_content       = $p_str_content;
			$l_arr_sections_raw       = array();
			$l_arr_sections_processed = array();

			do {
				$l_int_section_end    = strpos( $l_str_rest_content, (string) $l_str_footnote_section_shortcode );
				$l_arr_sections_raw[] = substr( $l_str_rest_content, 0, $l_int_section_end );
				$l_str_rest_content   = substr( $l_str_rest_content, $l_int_section_end + $l_int_footnote_section_shortcode_length );
			} while ( str_contains( $l_str_rest_content, (string) $l_str_footnote_section_shortcode ) );
			$l_arr_sections_raw[] = $l_str_rest_content;

			foreach ( $l_arr_sections_raw as $l_str_section ) {
				$l_arr_sections_processed[] = self::exec( $l_str_section, true );
			}
			return implode( $l_arr_sections_processed );

		}
	}

	/**
	 * Processes existing excerpt or replaces it with a new one generated on the basis of the post.
	 *
	 * The input was already the processed excerpt, no more footnotes to search.
	 * But issue #65 brought up that manual excerpts can include processable footnotes.
	 * Default 'manual' is fallback and is backwards-compatible with the initial setup.
	 *
	 * @since 1.5.0
	 *
	 * @param string $p_str_excerpt  Excerpt content.
	 * @return string  $p_str_excerpt  Processed or new excerpt.
	 */
	public function footnotes_in_excerpt( string $p_str_excerpt ): string {
		$l_str_excerpt_mode = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_IN_EXCERPT );

		if ( 'yes' === $l_str_excerpt_mode ) {
			return $this->generate_excerpt_with_footnotes( $p_str_excerpt );

		} elseif ( 'no' === $l_str_excerpt_mode ) {
			return $this->generate_excerpt( $p_str_excerpt );

		} else {
			return $this->exec( $p_str_excerpt );
		}
	}

	/**
	 * Generates excerpt on the basis of the post.
	 *
	 * Applies full WordPress excerpt processing.
	 *
	 * @link https://developer.wordpress.org/reference/functions/wp_trim_excerpt/
	 * @link https://developer.wordpress.org/reference/functions/wp_trim_words/
	 *
	 * @since 2.6.2
	 *
	 * @param string $p_str_content  The post.
	 * @return string  $p_str_content  An excerpt of the post.
	 */
	public function generate_excerpt( string $p_str_content ): string {

		// Discard existing excerpt and start on the basis of the post.
		$p_str_content = get_the_content( get_the_id() );

		// Get footnote delimiter shortcodes and unify them.
		$p_str_content = self::unify_delimiters( $p_str_content );

		// Remove footnotes.
		$p_str_content = preg_replace( '#' . self::$a_str_start_tag_regex . '.+?' . self::$a_str_end_tag_regex . '#', '', $p_str_content );

		// Apply WordPress excerpt processing.
		$p_str_content = strip_shortcodes( $p_str_content );
		$p_str_content = excerpt_remove_blocks( $p_str_content );

		// Here the footnotes would be processed as part of WordPress content processing.
		$p_str_content = apply_filters( 'the_content', $p_str_content );

		// According to Advanced Excerpt, this is some kind of precaution against malformed CDATA in RSS feeds.
		$p_str_content = str_replace( ']]>', ']]&gt;', $p_str_content );

		$l_int_excerpt_length = (int) _x( '55', 'excerpt_length' );
		$l_int_excerpt_length = (int) apply_filters( 'excerpt_length', $l_int_excerpt_length );
		$l_str_excerpt_more   = apply_filters( 'excerpt_more', ' [&hellip;]' );

		// Function wp_trim_words() calls wp_strip_all_tags() that wrecks the footnotes.
		$p_str_content = wp_trim_words( $p_str_content, $l_int_excerpt_length, $l_str_excerpt_more );

		return $p_str_content;
	}

	/**
	 * Generates excerpt with footnotes on the basis of the post.
	 *
	 * Does not apply full WordPress excerpt processing.
	 *
	 * @see self::generate_excerpt()
	 * Uses information and some code from Advanced Excerpt.
	 * @link https://wordpress.org/plugins/advanced-excerpt/
	 *
	 * @since 2.6.3
	 *
	 * @param string $p_str_content  The post.
	 * @return string  $p_str_content  An excerpt of the post.
	 */
	public function generate_excerpt_with_footnotes( string $p_str_content ): string {

		// Discard existing excerpt and start on the basis of the post.
		$p_str_content = get_the_content( get_the_id() );

		// Get footnote delimiter shortcodes and unify them.
		$p_str_content = self::unify_delimiters( $p_str_content );

		// Apply WordPress excerpt processing.
		$p_str_content = strip_shortcodes( $p_str_content );
		$p_str_content = excerpt_remove_blocks( $p_str_content );

		// But do not process footnotes at this point; do only this.
		$p_str_content = str_replace( ']]>', ']]&gt;', $p_str_content );

		// Prepare the excerpt length argument.
		$l_int_excerpt_length = (int) _x( '55', 'excerpt_length' );
		$l_int_excerpt_length = (int) apply_filters( 'excerpt_length', $l_int_excerpt_length );

		// Prepare the Read-on string.
		$l_str_excerpt_more = apply_filters( 'excerpt_more', ' [&hellip;]' );

		// Safeguard the footnotes.
		preg_match_all(
			'#' . self::$a_str_start_tag_regex . '.+?' . self::$a_str_end_tag_regex . '#',
			$p_str_content,
			$p_arr_saved_footnotes
		);

		// Prevent the footnotes from altering the excerpt: previously hard-coded '5ED84D6'.
		$l_int_placeholder = '@' . wp_rand( 100_000_000, 2_147_483_647 ) . '@';
		$p_str_content     = preg_replace(
			'#' . self::$a_str_start_tag_regex . '.+?' . self::$a_str_end_tag_regex . '#',
			$l_int_placeholder,
			$p_str_content
		);

		// Replace line breaking markup with a separator.
		$l_str_separator = ' ';
		$p_str_content   = preg_replace( '#<br *>#', $l_str_separator, $p_str_content );
		$p_str_content   = preg_replace( '#<br */>#', $l_str_separator, $p_str_content );
		$p_str_content   = preg_replace( '#<(p|li|div)[^>]*>#', $l_str_separator, $p_str_content );
		$p_str_content   = preg_replace( '#' . $l_str_separator . '#', '', $p_str_content, 1 );
		$p_str_content   = preg_replace( '#</(p|li|div) *>#', '', $p_str_content );
		$p_str_content   = preg_replace( '#[\r\n]#', '', $p_str_content );

		// To count words like Advanced Excerpt does it.
		$l_arr_tokens  = array();
		$l_str_output  = '';
		$l_int_counter = 0;

		// Tokenize into tags and words as in Advanced Excerpt.
		preg_match_all( '#(<[^>]+>|[^<>\s]+)\s*#u', $p_str_content, $l_arr_tokens );

		// Count words following one option of Advanced Excerpt.
		foreach ( $l_arr_tokens[0] as $l_str_token ) {

			if ( $l_int_counter >= $l_int_excerpt_length ) {
				break;
			}
			// If token is not a tag, increment word count.
			if ( '<' !== $l_str_token[0] ) {
				$l_int_counter++;
			}
			// Append the token to the output.
			$l_str_output .= $l_str_token;
		}

		// Complete unbalanced markup, used by Advanced Excerpt.
		$p_str_content = force_balance_tags( $l_str_output );

		// Readd footnotes in excerpt.
		$l_int_index = 0;
		while ( 0 !== preg_match( '#' . $l_int_placeholder . '#', $p_str_content ) ) {
			$p_str_content = preg_replace(
				'#' . $l_int_placeholder . '#',
				$p_arr_saved_footnotes[0][ $l_int_index ],
				$p_str_content,
				1
			);
			$l_int_index++;
		}

		// Append the Read-on string as in wp_trim_words().
		$p_str_content .= $l_str_excerpt_more;

		// Process readded footnotes without appending the reference container.
		$p_str_content = self::exec( $p_str_content, false );

		return $p_str_content;

	}

	/**
	 * Replaces footnotes in the widget title.
	 *
	 * @since 1.5.0
	 *
	 * @param string $p_str_content  Widget content.
	 * @return string  $p_str_content  Content with replaced footnotes.
	 */
	public function footnotes_in_widget_title( string $p_str_content ): string {
		// Appends the reference container if set to "post_end".
		return $this->exec( $p_str_content, false );
	}

	/**
	 * Replaces footnotes in the content of the current widget.
	 *
	 * @since 1.5.0
	 *
	 * @param string $p_str_content  Widget content.
	 * @return string  $p_str_content  Content with replaced footnotes.
	 */
	public function footnotes_in_widget_text( string $p_str_content ): string {
		// phpcs:disable WordPress.PHP.YodaConditions.NotYoda
		// Appends the reference container if set to "post_end".
		return $this->exec( $p_str_content, 'post_end' === Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_REFERENCE_CONTAINER_POSITION ) );
		// phpcs:enable WordPress.PHP.YodaConditions.NotYoda
	}

	/**
	 * Replaces all footnotes that occur in the given content.
	 *
	 * @since 1.5.0
	 *
	 * @param string $p_str_content  Any string that may contain footnotes to be replaced.
	 * @param bool   $p_bool_output_references  Appends the Reference Container to the output if set to true, default true.
	 * @param bool   $p_bool_hide_footnotes_text  Hide footnotes found in the string.
	 */
	public function exec( string $p_str_content, bool $p_bool_output_references = false, bool $p_bool_hide_footnotes_text = false ): string {

		// Process content.
		$p_str_content = $this->search( $p_str_content, $p_bool_hide_footnotes_text );

		/*
		 * Reference container customized positioning through shortcode.
		 */

		// Append the reference container or insert at shortcode.
		$l_str_reference_container_position_shortcode = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_REFERENCE_CONTAINER_POSITION_SHORTCODE );
		if ( empty( $l_str_reference_container_position_shortcode ) ) {
			$l_str_reference_container_position_shortcode = '[[references]]';
		}

		if ( $p_bool_output_references ) {

			if ( strpos( $p_str_content, (string) $l_str_reference_container_position_shortcode ) ) {

				$p_str_content = str_replace( $l_str_reference_container_position_shortcode, $this->reference_container(), $p_str_content );

			} else {

				$p_str_content .= $this->reference_container();

			}

			// Increment the container ID.
			self::$a_int_reference_container_id++;
		}

		// Delete position shortcode should any remain.
		$p_str_content = str_replace( $l_str_reference_container_position_shortcode, '', $p_str_content );

		// Take a look if the LOVE ME slug should NOT be displayed on this page/post, remove the short code if found.
		if ( strpos( $p_str_content, \footnotes\includes\Config::C_STR_NO_LOVE_SLUG ) ) {
			self::$a_bool_allow_love_me = false;
			$p_str_content              = str_replace( \footnotes\includes\Config::C_STR_NO_LOVE_SLUG, '', $p_str_content );
		}
		// Return the content with replaced footnotes and optional reference container appended.
		return $p_str_content;
	}

	/**
	 * Brings the delimiters and unifies their various HTML escapement schemas.
	 *
	 * While the Classic Editor (visual mode) escapes both pointy brackets,
	 * the Block Editor enforces balanced escapement only in code editor mode
	 * when the opening tag is already escaped. In visual mode, the Block Editor
	 * does not escape the greater-than sign.
	 *
	 * @since 2.1.14
	 *
	 * @param string $p_str_content  The footnote, including delimiters.
	 */
	public function unify_delimiters( string $p_str_content ): string {

		// Get footnotes start and end tag short codes.
		$l_str_starting_tag = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_SHORT_CODE_START );
		$l_str_ending_tag   = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_SHORT_CODE_END );
		if ( 'userdefined' === $l_str_starting_tag || 'userdefined' === $l_str_ending_tag ) {
			$l_str_starting_tag = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_SHORT_CODE_START_USER_DEFINED );
			$l_str_ending_tag   = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_SHORT_CODE_END_USER_DEFINED );
		}

		// If any footnotes short code is empty, return the content without changes.
		if ( empty( $l_str_starting_tag ) || empty( $l_str_ending_tag ) ) {
			return $p_str_content;
		}

		if ( preg_match( '#[&"\'<>]#', $l_str_starting_tag . $l_str_ending_tag ) ) {

			$l_str_harmonized_start_tag = '{[(|fnote_stt|)]}';
			$l_str_harmonized_end_tag   = '{[(|fnote_end|)]}';

			// Harmonize footnotes without escaping any HTML special characters in delimiter shortcodes.
			// The footnote has been added in the Block Editor code editor (doesn’t work in Classic Editor text mode).
			$p_str_content = str_replace( $l_str_starting_tag, $l_str_harmonized_start_tag, $p_str_content );
			$p_str_content = str_replace( $l_str_ending_tag, $l_str_harmonized_end_tag, $p_str_content );

			// Harmonize footnotes while escaping HTML special characters in delimiter shortcodes.
			// The footnote has been added in the Classic Editor visual mode.
			$p_str_content = str_replace( htmlspecialchars( $l_str_starting_tag ), $l_str_harmonized_start_tag, $p_str_content );
			$p_str_content = str_replace( htmlspecialchars( $l_str_ending_tag ), $l_str_harmonized_end_tag, $p_str_content );

			// Harmonize footnotes while escaping HTML special characters except greater-than sign in delimiter shortcodes.
			// The footnote has been added in the Block Editor visual mode.
			$p_str_content = str_replace( str_replace( '&gt;', '>', htmlspecialchars( $l_str_starting_tag ) ), $l_str_harmonized_start_tag, $p_str_content );
			$p_str_content = str_replace( str_replace( '&gt;', '>', htmlspecialchars( $l_str_ending_tag ) ), $l_str_harmonized_end_tag, $p_str_content );

			// Assign the delimiter shortcodes.
			self::$a_str_start_tag = $l_str_harmonized_start_tag;
			self::$a_str_end_tag   = $l_str_harmonized_end_tag;

			// Assign the regex-conformant shortcodes.
			self::$a_str_start_tag_regex = '\{\[\(\|fnote_stt\|\)\]\}';
			self::$a_str_end_tag_regex   = '\{\[\(\|fnote_end\|\)\]\}';

		} else {

			// Assign the delimiter shortcodes.
			self::$a_str_start_tag = $l_str_starting_tag;
			self::$a_str_end_tag   = $l_str_ending_tag;

			// Make shortcodes conform to regex syntax.
			self::$a_str_start_tag_regex = preg_replace( '#([\(\)\{\}\[\]\|\*\.\?\!])#', '\\\\$1', self::$a_str_start_tag );
			self::$a_str_end_tag_regex   = preg_replace( '#([\(\)\{\}\[\]\|\*\.\?\!])#', '\\\\$1', self::$a_str_end_tag );
		}

		return $p_str_content;
	}

	/**
	 * Replaces all footnotes in the given content and appends them to the static property.
	 *
	 * @since 1.5.0
	 * @todo Refactor to parse DOM rather than using RegEx.
	 * @todo Decompose.
	 *
	 * @param string $p_str_content  Any content to be parsed for footnotes.
	 * @param bool   $p_bool_hide_footnotes_text  Hide footnotes found in the string.
	 */
	public function search( string $p_str_content, bool $p_bool_hide_footnotes_text ): string {

		// Get footnote delimiter shortcodes and unify them.
		$p_str_content = self::unify_delimiters( $p_str_content );

		/*
		 * Checks for balanced footnote delimiters; delimiter syntax validation.
		 *
		 * If footnotes short codes are unbalanced, and syntax validation is not disabled,
		 * prepend a warning to the content; displays de facto beneath the post title.
		 */

		// If enabled.
		if ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTE_SHORTCODE_SYNTAX_VALIDATION_ENABLE ) ) ) {

			// Apply different regex depending on whether start shortcode is double/triple opening parenthesis.
			if ( '((' === self::$a_str_start_tag || '(((' === self::$a_str_start_tag ) {

				// This prevents from catching a script containing e.g. a double opening parenthesis.
				$l_str_validation_regex = '#' . self::$a_str_start_tag_regex . '(((?!' . self::$a_str_end_tag_regex . ')[^\{\}])*?)(' . self::$a_str_start_tag_regex . '|$)#s';

			} else {

				// Catch all only if the start shortcode is not double/triple opening parenthesis, i.e. is unlikely to occur in scripts.
				$l_str_validation_regex = '#' . self::$a_str_start_tag_regex . '(((?!' . self::$a_str_end_tag_regex . ').)*?)(' . self::$a_str_start_tag_regex . '|$)#s';
			}

			// Check syntax and get error locations.
			preg_match( $l_str_validation_regex, $p_str_content, $p_arr_error_location );
			if ( empty( $p_arr_error_location ) ) {
				self::$a_bool_syntax_error_flag = false;
			}

			// Prevent generating and inserting the warning multiple times.
			if ( self::$a_bool_syntax_error_flag ) {

				// Get plain text string for error location.
				$l_str_error_spot_string = wp_strip_all_tags( $p_arr_error_location[1] );

				// Limit string length to 300 characters.
				if ( strlen( $l_str_error_spot_string ) > 300 ) {
					$l_str_error_spot_string = substr( $l_str_error_spot_string, 0, 299 ) . '…';
				}

				// Compose warning box.
				$l_str_syntax_error_warning  = '<div class="footnotes_validation_error"><p>';
				$l_str_syntax_error_warning .= __( 'WARNING: unbalanced footnote start tag short code found.', 'footnotes' );
				$l_str_syntax_error_warning .= '</p><p>';

				// Syntax validation setting in the dashboard under the General settings tab.
				/* Translators: 1: General Settings 2: Footnote start and end short codes 3: Check for balanced shortcodes */
				$l_str_syntax_error_warning .= sprintf( __( 'If this warning is irrelevant, please disable the syntax validation feature in the dashboard under %1$s &gt; %2$s &gt; %3$s.', 'footnotes' ), __( 'General settings', 'footnotes' ), __( 'Footnote start and end short codes', 'footnotes' ), __( 'Check for balanced shortcodes', 'footnotes' ) );

				$l_str_syntax_error_warning .= '</p><p>';
				$l_str_syntax_error_warning .= __( 'Unbalanced start tag short code found before:', 'footnotes' );
				$l_str_syntax_error_warning .= '</p><p>“';
				$l_str_syntax_error_warning .= $l_str_error_spot_string;
				$l_str_syntax_error_warning .= '”</p></div>';

				// Prepend the warning box to the content.
				$p_str_content = $l_str_syntax_error_warning . $p_str_content;

				// Checked, set flag to false to prevent duplicate warning.
				self::$a_bool_syntax_error_flag = false;

				return $p_str_content;
			}
		}

		/*
		 * Patch to allow footnotes in input field labels.
		 *
		 * When the HTML 'input' element 'value' attribute value is derived from
		 * 'label', footnotes need to be removed in the value of 'value'.
		 */
		$l_str_value_regex = '#(<input [^>]+?value=["\'][^>]+?)' . self::$a_str_start_tag_regex . '[^>]+?' . self::$a_str_end_tag_regex . '#';

		do {
			$p_str_content = preg_replace( $l_str_value_regex, '$1', $p_str_content );
		} while ( preg_match( $l_str_value_regex, $p_str_content ) );

		// Optionally moves footnotes outside at the end of the label element.
		$l_str_label_issue_solution = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_LABEL_ISSUE_SOLUTION );

		if ( 'move' === $l_str_label_issue_solution ) {

			$l_str_move_regex = '#(<label ((?!</label).)+?)(' . self::$a_str_start_tag_regex . '((?!</label).)+?' . self::$a_str_end_tag_regex . ')(((?!</label).)*?</label>)#';

			do {
				$p_str_content = preg_replace( $l_str_move_regex, '$1$5<span class="moved_footnote">$3</span>', $p_str_content );
			} while ( preg_match( $l_str_move_regex, $p_str_content ) );
		}

		/*
		 * Optionally disconnects labels with footnotes from their input element.
		 *
		 * This option is discouraged because of accessibility issues.
		 * This only edits those labels' 'for' value that have footnotes,
		 * but leaves all other labels (those without footnotes) alone.
		 * @link https://wordpress.org/support/topic/compatibility-issue-with-wpforms/#post-14212318
		 */
		if ( 'disconnect' === $l_str_label_issue_solution ) {

			$l_str_disconnect_text = 'optionally-disconnected-from-input-field-to-prevent-toggling-while-clicking-footnote-referrer_';

			$p_str_content = preg_replace(
				'#(<label [^>]+?for=["\'])(((?!</label).)+' . self::$a_str_start_tag_regex . ')#',
				'$1' . $l_str_disconnect_text . '$2',
				$p_str_content
			);
		}

		// Post ID to make everything unique wrt infinite scroll and archive view.
		self::$a_int_post_id = (int) get_the_id();

		/*
		 * Empties the footnotes list every time Footnotes is run when the_content hook is called.
		 *
		 * Under certain circumstances, footnotes were duplicated, because the footnotes list was
		 * not emptied every time before the search algorithm was run. That happened eg when both
		 * the reference container resides in the widget area, and the YOAST SEO plugin is active
		 * and calls the hook the_content to generate the Open Graph description, while Footnotes
		 * is set to avoid missing out on the footnotes (in the content) by hooking in as soon as
		 * the_content is called, whereas at post end Footnotes seems to hook in the_content only
		 * the time it's the blog engine processing the post for display and appending the refs.
		 *
		 * Emptying the footnotes list only when the_content hook is called is ineffective
		 * when footnotes are processed in `generate_excerpt_with_footnotes()`.
		 * Footnotes duplication is prevented also when resetting the list here.
		 */
		self::$a_arr_footnotes = array();

		// Resets the footnote number.
		$l_int_footnote_index = 1;

		// Contains the starting position for the lookup of a footnote.
		$l_int_pos_start = 0;

		/*
		 * Load footnote referrer template file.
		 */

		// Set to null in case all templates are unnecessary.
		$l_obj_template         = null;
		$l_obj_template_tooltip = null;

		// On the condition that the footnote text is not hidden.
		if ( ! $p_bool_hide_footnotes_text ) {

			// Whether AMP compatibility mode is enabled.
			if ( General::$a_bool_amp_enabled ) {

				// Whether first clicking a referrer needs to expand the reference container.
				if ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_REFERENCE_CONTAINER_COLLAPSE ) ) ) {

					// Load 'public/partials/amp-footnote-expand.html'.
					$l_obj_template = new Includes\Template( \footnotes\includes\Template::C_STR_PUBLIC, 'amp-footnote-expand' );

				} else {

					// Load 'public/partials/amp-footnote.html'.
					$l_obj_template = new Includes\Template( \footnotes\includes\Template::C_STR_PUBLIC, 'amp-footnote' );
				}
			} elseif ( General::$a_bool_alternative_tooltips_enabled ) {

				// Load 'public/partials/footnote-alternative.html'.
				$l_obj_template = new Includes\Template( \footnotes\includes\Template::C_STR_PUBLIC, 'footnote-alternative' );

				// Else jQuery tooltips are enabled.
			} else {

				// Load 'public/partials/footnote.html'.
				$l_obj_template = new Includes\Template( \footnotes\includes\Template::C_STR_PUBLIC, 'footnote' );

				// Load tooltip inline script.
				$l_obj_template_tooltip = new Includes\Template( \footnotes\includes\Template::C_STR_PUBLIC, 'tooltip' );
			}
		}

		// Search footnotes short codes in the content.
		do {
			// Get first occurrence of the footnote start tag short code.
			$i_int_len_content = strlen( $p_str_content );
			if ( $l_int_pos_start > $i_int_len_content ) {
				$l_int_pos_start = $i_int_len_content;
			}
			$l_int_pos_start = strpos( $p_str_content, self::$a_str_start_tag, $l_int_pos_start );
			// No short code found, stop here.
			if ( ! $l_int_pos_start ) {
				break;
			}
			// Get first occurrence of the footnote end tag short code.
			$l_int_pos_end = strpos( $p_str_content, self::$a_str_end_tag, $l_int_pos_start );
			// No short code found, stop here.
			if ( ! $l_int_pos_end ) {
				break;
			}
			// Calculate the length of the footnote.
			$l_int_length = $l_int_pos_end - $l_int_pos_start;

			// Get footnote text.
			$l_str_footnote_text = substr( $p_str_content, $l_int_pos_start + strlen( self::$a_str_start_tag ), $l_int_length - strlen( self::$a_str_start_tag ) );

			// Get tooltip text if present.
			self::$a_str_tooltip_shortcode        = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_TOOLTIP_EXCERPT_DELIMITER );
			self::$a_int_tooltip_shortcode_length = strlen( self::$a_str_tooltip_shortcode );
			$l_int_tooltip_text_length            = strpos( $l_str_footnote_text, (string) self::$a_str_tooltip_shortcode );
			$l_bool_has_tooltip_text              = (bool) $l_int_tooltip_text_length;
			$l_str_tooltip_text                   = $l_bool_has_tooltip_text ? substr( $l_str_footnote_text, 0, $l_int_tooltip_text_length ) : '';

			/*
			 * URL line wrapping for Unicode non conformant browsers.
			 *
			 * Despite Unicode recommends to line-wrap URLs at slashes, and Firefox follows
			 * the Unicode standard, Chrome does not, making long URLs hang out of tooltips
			 * or extend reference containers, so that the end is hidden outside the window
			 * and may eventually be viewed after we scroll horizontally or zoom out. It is
			 * up to the web page to make URLs breaking anywhere by wrapping them in a span
			 * that is assigned appropriate CSS properties and values.
			 * @see css/public.css
			 *
			 * The value of an href argument may have leading (and trailing) space.
			 * @link https://webmasters.stackexchange.com/questions/93540/are-spaces-in-href-valid
			 * Needs to replicate the relevant negative lookbehind at least with one and with two spaces.
			 * Note: The WordPress blog engine edits these values, cropping these leading/trailing spaces.
			 *
			 * TODO: Split into own method.
			 */
			if ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTE_URL_WRAP_ENABLED ) ) ) {

				$l_str_footnote_text = preg_replace(
					'#(?<![-\w\.!~\*\'\(\);]=[\'"])(?<![-\w\.!~\*\'\(\);]=[\'"] )(?<![-\w\.!~\*\'\(\);]=[\'"]  )(?<![-\w\.!~\*\'\(\);]=)(?<!/)((ht|f)tps?://[^\\s<]+)#',
					'<span class="footnote_url_wrap">$1</span>',
					$l_str_footnote_text
				);
			}

			// Text to be displayed instead of the footnote.
			$l_str_footnote_replace_text = '';

			// Whether hard links are enabled.
			if ( self::$a_bool_hard_links_enabled ) {

				// Get the configurable parts.
				self::$a_str_referrer_link_slug = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_REFERRER_FRAGMENT_ID_SLUG );
				self::$a_str_footnote_link_slug = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTE_FRAGMENT_ID_SLUG );
				self::$a_str_link_ids_separator = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_HARD_LINK_IDS_SEPARATOR );

				// Streamline ID concatenation.
				self::$a_str_post_container_id_compound  = self::$a_str_link_ids_separator;
				self::$a_str_post_container_id_compound .= self::$a_int_post_id;
				self::$a_str_post_container_id_compound .= self::$a_str_link_ids_separator;
				self::$a_str_post_container_id_compound .= self::$a_int_reference_container_id;
				self::$a_str_post_container_id_compound .= self::$a_str_link_ids_separator;

			}

			// Display the footnote referrers and the tooltips.
			if ( ! $p_bool_hide_footnotes_text ) {
				$l_int_index = Includes\Convert::index( $l_int_footnote_index, Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_COUNTER_STYLE ) );

				// Display only a truncated footnote text if option enabled.
				$l_bool_enable_excerpt = Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_MOUSE_OVER_BOX_EXCERPT_ENABLED ) );
				$l_int_max_length      = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_FOOTNOTES_MOUSE_OVER_BOX_EXCERPT_LENGTH );

				// Define excerpt text as footnote text by default.
				$l_str_excerpt_text = $l_str_footnote_text;

				/*
				 * Tooltip truncation.
				 *
				 * If the tooltip truncation option is enabled, it’s done based on character count,
				 * and a trailing incomplete word is cropped.
				 * This is equivalent to the WordPress default excerpt generation, i.e. without a
				 * custom excerpt and without a delimiter. But WordPress does word count, usually 55.
				 */
				if ( General::$a_bool_tooltips_enabled && $l_bool_enable_excerpt ) {
					$l_str_dummy_text = wp_strip_all_tags( $l_str_footnote_text );
					if ( is_int( $l_int_max_length ) && strlen( $l_str_dummy_text ) > $l_int_max_length ) {
						$l_str_excerpt_text  = substr( $l_str_dummy_text, 0, $l_int_max_length );
						$l_str_excerpt_text  = substr( $l_str_excerpt_text, 0, strrpos( $l_str_excerpt_text, ' ' ) );
						$l_str_excerpt_text .= '&nbsp;&#x2026; <';
						$l_str_excerpt_text .= self::$a_bool_hard_links_enabled ? 'a' : 'span';
						$l_str_excerpt_text .= ' class="footnote_tooltip_continue" ';

						// If AMP compatibility mode is enabled.
						if ( General::$a_bool_amp_enabled ) {

							// If the reference container is also collapsed by default.
							if ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_REFERENCE_CONTAINER_COLLAPSE ) ) ) {

								$l_str_excerpt_text .= ' on="tap:footnote_references_container_';
								$l_str_excerpt_text .= self::$a_int_post_id . '_' . self::$a_int_reference_container_id;
								$l_str_excerpt_text .= '.toggleClass(class=collapsed, force=false),footnotes_container_button_plus_';
								$l_str_excerpt_text .= self::$a_int_post_id . '_' . self::$a_int_reference_container_id;
								$l_str_excerpt_text .= '.toggleClass(class=collapsed, force=true),footnotes_container_button_minus_';
								$l_str_excerpt_text .= self::$a_int_post_id . '_' . self::$a_int_reference_container_id;
								$l_str_excerpt_text .= '.toggleClass(class=collapsed, force=false)"';
							}
						} else {

							// Don’t add onclick event in AMP compatibility mode.
							// Reverted wrong linting.
							$l_str_excerpt_text .= ' onclick="footnote_moveToReference_' . self::$a_int_post_id;
							$l_str_excerpt_text .= '_' . self::$a_int_reference_container_id;
							$l_str_excerpt_text .= '(\'footnote_plugin_reference_' . self::$a_int_post_id;
							$l_str_excerpt_text .= '_' . self::$a_int_reference_container_id;
							$l_str_excerpt_text .= "_$l_int_index');\"";
						}

						// If enabled, add the hard link fragment ID.
						if ( self::$a_bool_hard_links_enabled ) {

							$l_str_excerpt_text .= ' href="#';
							$l_str_excerpt_text .= self::$a_str_footnote_link_slug;
							$l_str_excerpt_text .= self::$a_str_post_container_id_compound;
							$l_str_excerpt_text .= $l_int_index;
							$l_str_excerpt_text .= '"';
						}

						$l_str_excerpt_text .= '>';

						// Configurable read-on button label.
						$l_str_excerpt_text .= Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_TOOLTIP_READON_LABEL );

						$l_str_excerpt_text .= self::$a_bool_hard_links_enabled ? '</a>' : '</span>';
					}
				}

				/*
				 * Referrers element superscript or baseline.
				 *
				 * Define the HTML element to use for the referrers.
				 */
				if ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_REFERRER_SUPERSCRIPT_TAGS ) ) ) {

					$l_str_sup_span = 'sup';

				} else {

					$l_str_sup_span = 'span';
				}

				// Whether hard links are enabled.
				if ( self::$a_bool_hard_links_enabled ) {

					self::$a_str_link_span      = 'a';
					self::$a_str_link_close_tag = '</a>';
					// Self::$a_str_link_open_tag will be defined as needed.

					// Compose hyperlink address (leading space is in template).
					$l_str_footnote_link_argument  = 'href="#';
					$l_str_footnote_link_argument .= self::$a_str_footnote_link_slug;
					$l_str_footnote_link_argument .= self::$a_str_post_container_id_compound;
					$l_str_footnote_link_argument .= $l_int_index;
					$l_str_footnote_link_argument .= '" class="footnote_hard_link"';

					/*
					 * Compose fragment ID anchor with offset, for use in reference container.
					 * Empty span, child of empty span, to avoid tall dotted rectangles in browser.
					 */
					$l_str_referrer_anchor_element  = '<span class="footnote_referrer_base"><span id="';
					$l_str_referrer_anchor_element .= self::$a_str_referrer_link_slug;
					$l_str_referrer_anchor_element .= self::$a_str_post_container_id_compound;
					$l_str_referrer_anchor_element .= $l_int_index;
					$l_str_referrer_anchor_element .= '" class="footnote_referrer_anchor"></span></span>';

				} else {
					/*
					 * Initialize hard link variables when hard links are disabled.
					 *
					 * If no hyperlink nor offset anchor is needed, initialize as empty.
					 */
					$l_str_footnote_link_argument  = '';
					$l_str_referrer_anchor_element = '';

					// The link element is set independently as it may be needed for styling.
					if ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_LINK_ELEMENT_ENABLED ) ) ) {

						self::$a_str_link_span      = 'a';
						self::$a_str_link_open_tag  = '<a>';
						self::$a_str_link_close_tag = '</a>';

					}
				}

				// Determine tooltip content.
				if ( General::$a_bool_tooltips_enabled ) {
					$l_str_tooltip_content = $l_bool_has_tooltip_text ? $l_str_tooltip_text : $l_str_excerpt_text;

					/*
					 * Ensures paragraph separation
					 *
					 * Ensures that footnotes containing paragraph separators get displayed correctly.
					 */
					$l_arr_paragraph_splitters = array( '#(</p *>|<p[^>]*>)#', '#(</div *>|<div[^>]*>)#' );
					$l_str_tooltip_content     = preg_replace( $l_arr_paragraph_splitters, '<br />', $l_str_tooltip_content );
				} else {
					$l_str_tooltip_content = '';
				}

				// Determine shrink width if alternative tooltips are enabled.
				$l_str_tooltip_style = '';
				if ( General::$a_bool_alternative_tooltips_enabled && General::$a_bool_tooltips_enabled ) {
					$l_int_tooltip_length = strlen( wp_strip_all_tags( $l_str_tooltip_content ) );
					if ( $l_int_tooltip_length < 70 ) {
						$l_str_tooltip_style  = ' style="width: ';
						$l_str_tooltip_style .= ( $l_int_tooltip_length * .7 );
						$l_str_tooltip_style .= 'em;"';
					}
				}

				// Fill in 'public/partials/footnote.html'.
				$l_obj_template->replace(
					array(
						'link-span'      => self::$a_str_link_span,
						'post_id'        => self::$a_int_post_id,
						'container_id'   => self::$a_int_reference_container_id,
						'note_id'        => $l_int_index,
						'hard-link'      => $l_str_footnote_link_argument,
						'sup-span'       => $l_str_sup_span,
						'before'         => Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_STYLING_BEFORE ),
						'index'          => $l_int_index,
						'after'          => Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_STYLING_AFTER ),
						'anchor-element' => $l_str_referrer_anchor_element,
						'style'          => $l_str_tooltip_style,
						'text'           => $l_str_tooltip_content,
					)
				);
				$l_str_footnote_replace_text = $l_obj_template->get_content();

				// Reset the template.
				$l_obj_template->reload();

				// If tooltips are enabled but neither AMP nor alternative are.
				if ( General::$a_bool_tooltips_enabled && ! General::$a_bool_amp_enabled && ! General::$a_bool_alternative_tooltips_enabled ) {

					$l_int_offset_y          = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_FOOTNOTES_MOUSE_OVER_BOX_OFFSET_Y );
					$l_int_offset_x          = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_FOOTNOTES_MOUSE_OVER_BOX_OFFSET_X );
					$l_int_fade_in_delay     = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_MOUSE_OVER_BOX_FADE_IN_DELAY );
					$l_int_fade_in_duration  = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_MOUSE_OVER_BOX_FADE_IN_DURATION );
					$l_int_fade_out_delay    = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_MOUSE_OVER_BOX_FADE_OUT_DELAY );
					$l_int_fade_out_duration = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_MOUSE_OVER_BOX_FADE_OUT_DURATION );

					// Fill in 'public/partials/tooltip.html'.
					$l_obj_template_tooltip->replace(
						array(
							'post_id'           => self::$a_int_post_id,
							'container_id'      => self::$a_int_reference_container_id,
							'note_id'           => $l_int_index,
							'position'          => Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_MOUSE_OVER_BOX_POSITION ),
							'offset-y'          => empty( $l_int_offset_y ) ? 0 : $l_int_offset_y,
							'offset-x'          => empty( $l_int_offset_x ) ? 0 : $l_int_offset_x,
							'fade-in-delay'     => empty( $l_int_fade_in_delay ) ? 0 : $l_int_fade_in_delay,
							'fade-in-duration'  => empty( $l_int_fade_in_duration ) ? 0 : $l_int_fade_in_duration,
							'fade-out-delay'    => empty( $l_int_fade_out_delay ) ? 0 : $l_int_fade_out_delay,
							'fade-out-duration' => empty( $l_int_fade_out_duration ) ? 0 : $l_int_fade_out_duration,
						)
					);
					$l_str_footnote_replace_text .= $l_obj_template_tooltip->get_content();
					$l_obj_template_tooltip->reload();
				}
			}
			// Replace the footnote with the template.
			$p_str_content = substr_replace( $p_str_content, $l_str_footnote_replace_text, $l_int_pos_start, $l_int_length + strlen( self::$a_str_end_tag ) );

			// Add footnote only if not empty.
			if ( ! empty( $l_str_footnote_text ) ) {
				// Set footnote to the output box at the end.
				self::$a_arr_footnotes[] = $l_str_footnote_text;
				// Increase footnote index.
				$l_int_footnote_index++;
			}

			/*
			 * Fixes a partial footnotes process outage happening when tooltips are truncated or disabled.
			 * Fixed a footnotes numbering bug happening under de facto rare circumstances.
			 *
			 * This assignment was overridden by another one, causing the algorithm to jump back
			 * near the post start to a position calculated as the sum of the length of the last
			 * footnote and the length of the last footnote replace text.
			 * A bug disturbing the order of the footnotes depending on the text before the first
			 * footnote, the length of the first footnote and the length of the templates for the
			 * footnote and the tooltip.
			 * Deleting both lines instead, to resume the search at the position where it left off,
			 * would have prevented also the following bug.
			 *
			 * The origin of the bug was present since the beginning (v1.0.0).
			 * For v1.3.2 the wrong code was refactored but remained wrong,
			 * and was unaffected by the v1.5.0 refactoring.
			 * The reason why the numbering disorder reverted to a partial process outage
			 * since 2.5.14 is that with this version, the plugin stopped processing the
			 * content multiple times, and started unifying the shortcodes instead, to fix
			 * the numbering disorder affecting delimiter shortcodes with pointy brackets
			 * and mixed escapement schemas.
			 */
			// Add offset to the new starting position.
			$l_int_pos_start += strlen( $l_str_footnote_replace_text );

		} while ( true );

		// Return content.
		return $p_str_content;
	}

	/**
	 * Generates the reference container.
	 *
	 * @since 1.5.0
	 */
	public function reference_container(): string {

		$l_str_use_backbutton_hint = null;
		// No footnotes have been replaced on this page.
		if ( empty( self::$a_arr_footnotes ) ) {
			return '';
		}

		/*
		 * Footnote index backlink symbol.
		 */

		// If the backlink symbol is enabled.
		if ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_REFERENCE_CONTAINER_BACKLINK_SYMBOL_ENABLE ) ) ) {

			// Get html arrow.
			$l_str_arrow = Includes\Convert::get_arrow( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_HYPERLINK_ARROW ) );
			// Set html arrow to the first one if invalid index defined.
			if ( is_array( $l_str_arrow ) ) {
				$l_str_arrow = Includes\Convert::get_arrow( 0 );
			}
			// Get user defined arrow.
			$l_str_arrow_user_defined = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_HYPERLINK_ARROW_USER_DEFINED );
			if ( ! empty( $l_str_arrow_user_defined ) ) {
				$l_str_arrow = $l_str_arrow_user_defined;
			}

			// Wrap the arrow in a @media print { display:hidden } span.
			$l_str_footnote_arrow  = '<span class="footnote_index_arrow">';
			$l_str_footnote_arrow .= $l_str_arrow . '</span>';

		} else {

			// If the backlink symbol isn’t enabled, set it to empty.
			$l_str_arrow          = '';
			$l_str_footnote_arrow = '';

		}

		/*
		 * Backlink separator.
		 *
		 * Initially an appended comma was hard-coded in this algorithm for enumerations.
		 * The comma in enumerations is not universally preferred.
		 */
		if ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_BACKLINKS_SEPARATOR_ENABLED ) ) ) {

			if ( empty( $l_str_separator ) ) {

				// If it is not, check which option is on.
				$l_str_separator_option = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_BACKLINKS_SEPARATOR_OPTION );
				$l_str_separator        = match ($l_str_separator_option) {
					'comma' => ',',
					'semicolon' => ';',
					'en_dash' => '&nbsp;&#x2013;',
					default => Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_BACKLINKS_SEPARATOR_CUSTOM ),
				};
			}
		} else {

			$l_str_separator = '';
		}

		/*
		 * Backlink terminator.
		 *
		 * Initially a dot was appended in the table row template.
		 */
		if ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_BACKLINKS_TERMINATOR_ENABLED ) ) ) {

			if ( empty( $l_str_terminator ) ) {

				// If it is not, check which option is on.
				$l_str_terminator_option = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_BACKLINKS_TERMINATOR_OPTION );
				$l_str_terminator        = match ($l_str_terminator_option) {
					'period' => '.',
					'parenthesis' => ')',
					'colon' => ':',
					default => Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_BACKLINKS_TERMINATOR_CUSTOM ),
				};
			}
		} else {

			$l_str_terminator = '';
		}

		/*
		 * Line breaks.
		 *
		 * The backlinks of combined footnotes are generally preferred in an enumeration.
		 * But when few footnotes are identical, stacking the items in list form is better.
		 * Variable number length and proportional character width require explicit line breaks.
		 * Otherwise, an ordinary space character offering a line break opportunity is inserted.
		 */
		$l_str_line_break = Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_BACKLINKS_LINE_BREAKS_ENABLED ) ) ? '<br />' : ' ';

		/*
		 * Line breaks for source readability.
		 *
		 * For maintenance and support, table rows in the reference container should be
		 * separated by an empty line. So we add these line breaks for source readability.
		 * Before the first table row (breaks between rows are ~200 lines below).
		 */
		$l_str_body = "\r\n\r\n";

		/*
		 * Reference container table row template load.
		 */
		$l_bool_combine_identical_footnotes = Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_COMBINE_IDENTICAL_FOOTNOTES ) );

		// AMP compatibility requires a full set of AMP compatible table row templates.
		if ( General::$a_bool_amp_enabled ) {
			// When combining identical footnotes is turned on, another template is needed.
			if ( $l_bool_combine_identical_footnotes ) {
				// The combining template allows for backlink clusters and supports cell clicking for single notes.
				$l_obj_template = new Includes\Template( \footnotes\includes\Template::C_STR_PUBLIC, 'amp-reference-container-body-combi' );
			} elseif ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_REFERENCE_CONTAINER_3COLUMN_LAYOUT_ENABLE ) ) ) {
				$l_obj_template = new Includes\Template( \footnotes\includes\Template::C_STR_PUBLIC, 'amp-reference-container-body-3column' );
			} elseif ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_REFERENCE_CONTAINER_BACKLINK_SYMBOL_SWITCH ) ) ) {
				$l_obj_template = new Includes\Template( \footnotes\includes\Template::C_STR_PUBLIC, 'amp-reference-container-body-switch' );
			} else {

						// Default is the standard template.
						$l_obj_template = new Includes\Template( \footnotes\includes\Template::C_STR_PUBLIC, 'amp-reference-container-body' );

			}
		} elseif ( $l_bool_combine_identical_footnotes ) {
			// The combining template allows for backlink clusters and supports cell clicking for single notes.
			$l_obj_template = new Includes\Template( \footnotes\includes\Template::C_STR_PUBLIC, 'reference-container-body-combi' );
		} elseif ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_REFERENCE_CONTAINER_3COLUMN_LAYOUT_ENABLE ) ) ) {
			$l_obj_template = new Includes\Template( \footnotes\includes\Template::C_STR_PUBLIC, 'reference-container-body-3column' );
		} elseif ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_REFERENCE_CONTAINER_BACKLINK_SYMBOL_SWITCH ) ) ) {
			$l_obj_template = new Includes\Template( \footnotes\includes\Template::C_STR_PUBLIC, 'reference-container-body-switch' );
		} else {

						// Default is the standard template.
						$l_obj_template = new Includes\Template( \footnotes\includes\Template::C_STR_PUBLIC, 'reference-container-body' );

		}

		/*
		 * Switch backlink symbol and footnote number.
		 */
		$l_bool_symbol_switch = Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_REFERENCE_CONTAINER_BACKLINK_SYMBOL_SWITCH ) );

		// Loop through all footnotes found in the page.
		$num_footnotes = count( self::$a_arr_footnotes );
		for ( $l_int_index = 0; $l_int_index < $num_footnotes; $l_int_index++ ) {

			// Get footnote text.
			$l_str_footnote_text = self::$a_arr_footnotes[ $l_int_index ];

			// If footnote is empty, go to the next one;.
			// With combine identicals turned on, identicals will be deleted and are skipped.
			if ( empty( $l_str_footnote_text ) ) {
				continue;
			}

			// Generate content of footnote index cell.
			$l_int_first_footnote_index = ( $l_int_index + 1 );

			// Get the footnote index string and.
			// Keep supporting legacy index placeholder.
			$l_str_footnote_id = Includes\Convert::index( ( $l_int_index + 1 ), Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_COUNTER_STYLE ) );

			/**
			 * Case of only one backlink per table row.
			 *
			 * If enabled, and for the case the footnote is single, compose hard link.
			 */
			// Define anyway.
			$l_str_hard_link_address = '';

			if ( self::$a_bool_hard_links_enabled ) {
				/*
				 * Use-Backbutton-Hint tooltip, optional and configurable.
				 *
				 * When hard links are enabled, clicks on the backlinks are logged in the browsing history.
				 * This tooltip hints to use the backbutton instead, so the history gets streamlined again.
				 * @link https://wordpress.org/support/topic/making-it-amp-compatible/#post-13837359
				 *
				 * @since 2.5.4
				 */
				if ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_BACKLINK_TOOLTIP_ENABLE ) ) ) {
					$l_str_use_backbutton_hint  = ' title="';
					$l_str_use_backbutton_hint .= Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_BACKLINK_TOOLTIP_TEXT );
					$l_str_use_backbutton_hint .= '"';
				} else {
					$l_str_use_backbutton_hint = '';
				}

				/**
				 * Compose fragment ID anchor with offset, for use in reference container.
				 * Empty span, child of empty span, to avoid tall dotted rectangles in browser.
				 */
				$l_str_footnote_anchor_element  = '<span class="footnote_item_base"><span id="';
				$l_str_footnote_anchor_element .= self::$a_str_footnote_link_slug;
				$l_str_footnote_anchor_element .= self::$a_str_post_container_id_compound;
				$l_str_footnote_anchor_element .= $l_str_footnote_id;
				$l_str_footnote_anchor_element .= '" class="footnote_item_anchor"></span></span>';

				// Compose optional hard link address.
				$l_str_hard_link_address   = ' href="#';
				$l_str_hard_link_address  .= self::$a_str_referrer_link_slug;
				$l_str_hard_link_address  .= self::$a_str_post_container_id_compound;
				$l_str_hard_link_address  .= $l_str_footnote_id . '"';
				$l_str_hard_link_address  .= $l_str_use_backbutton_hint;
				self::$a_str_link_open_tag = ' class="footnote_hard_back_link">';

			} else {
				// Define as empty, too.
				$l_str_footnote_anchor_element = '';
			}

			/*
			 * Support for combining identicals: compose enumerated backlinks.
			 *
			 * Prepare to have single footnotes, where the click event and
			 * optional hard link need to be set to cover the table cell,
			 * for better usability and UX.
			 *
			 * @since 2.1.1
			 */

			// Set a flag to check for the combined status of a footnote item.
			$l_bool_flag_combined = false;

			// Set otherwise unused variables as empty to avoid screwing up the placeholder array.
			$l_str_backlink_event     = '';
			$l_str_footnote_backlinks = '';
			$l_str_footnote_reference = '';

			if ( $l_bool_combine_identical_footnotes ) {

				// ID, optional hard link address, and class.
				$l_str_footnote_reference  = '<' . self::$a_str_link_span;
				$l_str_footnote_reference .= ' id="footnote_plugin_reference_';
				$l_str_footnote_reference .= self::$a_int_post_id;
				$l_str_footnote_reference .= '_' . self::$a_int_reference_container_id;
				$l_str_footnote_reference .= "_$l_str_footnote_id\"";
				if ( self::$a_bool_hard_links_enabled ) {
					$l_str_footnote_reference .= ' href="#';
					$l_str_footnote_reference .= self::$a_str_referrer_link_slug;
					$l_str_footnote_reference .= self::$a_str_post_container_id_compound;
					$l_str_footnote_reference .= $l_str_footnote_id . '"';
					$l_str_footnote_reference .= $l_str_use_backbutton_hint;
				}
				$l_str_footnote_reference .= ' class="footnote_backlink"';

				/*
				 * The click event goes in the table cell if footnote remains single.
				 */

				$l_str_backlink_event = ' onclick="footnote_moveToAnchor_';

				$l_str_backlink_event .= self::$a_int_post_id;
				$l_str_backlink_event .= '_' . self::$a_int_reference_container_id;
				$l_str_backlink_event .= "('footnote_plugin_tooltip_";
				$l_str_backlink_event .= self::$a_int_post_id;
				$l_str_backlink_event .= '_' . self::$a_int_reference_container_id;
				$l_str_backlink_event .= "_$l_str_footnote_id');\"";

				// The dedicated template enumerating backlinks uses another variable.
				$l_str_footnote_backlinks = $l_str_footnote_reference;

				// Append the click event right to the backlink item for enumerations;.
				// Else it goes in the table cell.
				$l_str_footnote_backlinks .= $l_str_backlink_event . '>';
				$l_str_footnote_reference .= '>';

				// Append the optional offset anchor for hard links.
				if ( self::$a_bool_hard_links_enabled ) {
					$l_str_footnote_reference .= $l_str_footnote_anchor_element;
					$l_str_footnote_backlinks .= $l_str_footnote_anchor_element;
				}

				// Continue both single note and notes cluster, depending on switch option status.
				if ( $l_bool_symbol_switch ) {

					$l_str_footnote_reference .= "$l_str_footnote_id$l_str_footnote_arrow";
					$l_str_footnote_backlinks .= "$l_str_footnote_id$l_str_footnote_arrow";

				} else {

					$l_str_footnote_reference .= "$l_str_footnote_arrow$l_str_footnote_id";
					$l_str_footnote_backlinks .= "$l_str_footnote_arrow$l_str_footnote_id";

				}

				// If that is the only footnote with this text, we’re almost done..

				// Check if it isn't the last footnote in the array.
				if ( $l_int_first_footnote_index < count( self::$a_arr_footnotes ) ) {

					// Get all footnotes that haven't passed yet.
					$num_footnotes = count( self::$a_arr_footnotes );
					for ( $l_int_check_index = $l_int_first_footnote_index; $l_int_check_index < $num_footnotes; $l_int_check_index++ ) {

						// Check if a further footnote is the same as the actual one.
						if ( self::$a_arr_footnotes[ $l_int_check_index ] === $l_str_footnote_text ) {

							// If so, set the further footnote as empty so it won't be displayed later.
							self::$a_arr_footnotes[ $l_int_check_index ] = '';

							// Set the flag to true for the combined status.
							$l_bool_flag_combined = true;

							// Update the footnote ID.
							$l_str_footnote_id = Includes\Convert::index( ( $l_int_check_index + 1 ), Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_COUNTER_STYLE ) );

							// Resume composing the backlinks enumeration.
							$l_str_footnote_backlinks .= "$l_str_separator</";
							$l_str_footnote_backlinks .= self::$a_str_link_span . '>';
							$l_str_footnote_backlinks .= $l_str_line_break;
							$l_str_footnote_backlinks .= '<' . self::$a_str_link_span;
							$l_str_footnote_backlinks .= ' id="footnote_plugin_reference_';
							$l_str_footnote_backlinks .= self::$a_int_post_id;
							$l_str_footnote_backlinks .= '_' . self::$a_int_reference_container_id;
							$l_str_footnote_backlinks .= "_$l_str_footnote_id\"";

							// Insert the optional hard link address.
							if ( self::$a_bool_hard_links_enabled ) {
								$l_str_footnote_backlinks .= ' href="#';
								$l_str_footnote_backlinks .= self::$a_str_referrer_link_slug;
								$l_str_footnote_backlinks .= self::$a_str_post_container_id_compound;
								$l_str_footnote_backlinks .= $l_str_footnote_id . '"';
								$l_str_footnote_backlinks .= $l_str_use_backbutton_hint;
							}

							$l_str_footnote_backlinks .= ' class="footnote_backlink"';

							// Reverted wrong linting.
							$l_str_footnote_backlinks .= ' onclick="footnote_moveToAnchor_';

							$l_str_footnote_backlinks .= self::$a_int_post_id;
							$l_str_footnote_backlinks .= '_' . self::$a_int_reference_container_id;
							$l_str_footnote_backlinks .= "('footnote_plugin_tooltip_";
							$l_str_footnote_backlinks .= self::$a_int_post_id;
							$l_str_footnote_backlinks .= '_' . self::$a_int_reference_container_id;
							$l_str_footnote_backlinks .= "_$l_str_footnote_id');\">";

							// Append the offset anchor for optional hard links.
							if ( self::$a_bool_hard_links_enabled ) {
								$l_str_footnote_backlinks .= '<span class="footnote_item_base"><span id="';
								$l_str_footnote_backlinks .= self::$a_str_footnote_link_slug;
								$l_str_footnote_backlinks .= self::$a_str_post_container_id_compound;
								$l_str_footnote_backlinks .= $l_str_footnote_id;
								$l_str_footnote_backlinks .= '" class="footnote_item_anchor"></span></span>';
							}

							$l_str_footnote_backlinks .= $l_bool_symbol_switch ? '' : $l_str_footnote_arrow;
							$l_str_footnote_backlinks .= $l_str_footnote_id;
							$l_str_footnote_backlinks .= $l_bool_symbol_switch ? $l_str_footnote_arrow : '';

						}
					}
				}

				// Append terminator and end tag.
				$l_str_footnote_reference .= $l_str_terminator . '</' . self::$a_str_link_span . '>';
				$l_str_footnote_backlinks .= $l_str_terminator . '</' . self::$a_str_link_span . '>';

			}

			// Line wrapping of URLs already fixed, see above.

			// Get reference container item text if tooltip text goes separate.
			$l_int_tooltip_text_length = strpos( $l_str_footnote_text, self::$a_str_tooltip_shortcode );
			$l_bool_has_tooltip_text   = (bool) $l_int_tooltip_text_length;
			if ( $l_bool_has_tooltip_text ) {
				$l_str_not_tooltip_text           = substr( $l_str_footnote_text, ( $l_int_tooltip_text_length + self::$a_int_tooltip_shortcode_length ) );
				self::$a_bool_mirror_tooltip_text = Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_TOOLTIP_EXCERPT_MIRROR_ENABLE ) );
				if ( self::$a_bool_mirror_tooltip_text ) {
					$l_str_tooltip_text              = substr( $l_str_footnote_text, 0, $l_int_tooltip_text_length );
					$l_str_reference_text_introducer = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_TOOLTIP_EXCERPT_MIRROR_SEPARATOR );
					$l_str_reference_text            = $l_str_tooltip_text . $l_str_reference_text_introducer . $l_str_not_tooltip_text;
				} else {
					$l_str_reference_text = $l_str_not_tooltip_text;
				}
			} else {
				$l_str_reference_text = $l_str_footnote_text;
			}

			// Replace all placeholders in table row template.
			$l_obj_template->replace(
				array(

					// Placeholder used in all templates.
					'text'           => $l_str_reference_text,

					// Used in standard layout W/O COMBINED FOOTNOTES.
					'post_id'        => self::$a_int_post_id,
					'container_id'   => self::$a_int_reference_container_id,
					'note_id'        => Includes\Convert::index( $l_int_first_footnote_index, Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_COUNTER_STYLE ) ),
					'link-start'     => self::$a_str_link_open_tag,
					'link-end'       => self::$a_str_link_close_tag,
					'link-span'      => self::$a_str_link_span,
					'terminator'     => $l_str_terminator,
					'anchor-element' => $l_str_footnote_anchor_element,
					'hard-link'      => $l_str_hard_link_address,

					// Used in standard layout WITH COMBINED IDENTICALS TURNED ON.
					'pointer'        => $l_bool_flag_combined ? '' : ' pointer',
					'event'          => $l_bool_flag_combined ? '' : $l_str_backlink_event,
					'backlinks'      => $l_bool_flag_combined ? $l_str_footnote_backlinks : $l_str_footnote_reference,

					// Legacy placeholders for use in legacy layout templates.
					'arrow'          => $l_str_footnote_arrow,
					'index'          => $l_str_footnote_id,
				)
			);

			$l_str_body .= $l_obj_template->get_content();

			// Extra line breaks for page source readability.
			$l_str_body .= "\r\n\r\n";

			$l_obj_template->reload();

		}

		// Call again for robustness when priority levels don’t match any longer.
		self::$a_int_scroll_offset = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_FOOTNOTES_SCROLL_OFFSET );

		// Streamline.
		$l_bool_collapse_default = Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_REFERENCE_CONTAINER_COLLAPSE ) );

		/*
		 * Reference container label.
		 *
		 * Themes may drop-cap a first letter of initial paragraphs, like this label.
		 * In case of empty label that would apply to the left half button character.
		 * Hence the point in setting an empty label to U+202F NARROW NO-BREAK SPACE.
		 */
		$l_str_reference_container_label = Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_REFERENCE_CONTAINER_NAME );

		// Select the reference container template.
		// Whether AMP compatibility mode is enabled.
		if ( General::$a_bool_amp_enabled ) {

			// Whether the reference container is collapsed by default.
			if ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_REFERENCE_CONTAINER_COLLAPSE ) ) ) {

				// Load 'public/partials/amp-reference-container-collapsed.html'.
				$l_obj_template_container = new Includes\Template( \footnotes\includes\Template::C_STR_PUBLIC, 'amp-reference-container-collapsed' );

			} else {

				// Load 'public/partials/amp-reference-container.html'.
				$l_obj_template_container = new Includes\Template( \footnotes\includes\Template::C_STR_PUBLIC, 'amp-reference-container' );
			}
		} elseif ( 'js' === General::$a_str_script_mode ) {

			// Load 'public/partials/js-reference-container.html'.
			$l_obj_template_container = new Includes\Template( \footnotes\includes\Template::C_STR_PUBLIC, 'js-reference-container' );

		} else {

			// Load 'public/partials/reference-container.html'.
			$l_obj_template_container = new Includes\Template( \footnotes\includes\Template::C_STR_PUBLIC, 'reference-container' );
		}

		$l_int_scroll_offset        = '';
		$l_int_scroll_down_delay    = '';
		$l_int_scroll_down_duration = '';
		$l_int_scroll_up_delay      = '';
		$l_int_scroll_up_duration   = '';

		if ( 'jquery' === General::$a_str_script_mode ) {

			$l_int_scroll_offset      = ( self::$a_int_scroll_offset / 100 );
			$l_int_scroll_up_duration = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_FOOTNOTES_SCROLL_DURATION );

			if ( Includes\Convert::to_bool( Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_FOOTNOTES_SCROLL_DURATION_ASYMMETRICITY ) ) ) {

				$l_int_scroll_down_duration = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_FOOTNOTES_SCROLL_DOWN_DURATION );

			} else {

				$l_int_scroll_down_duration = $l_int_scroll_up_duration;

			}

			$l_int_scroll_down_delay = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_FOOTNOTES_SCROLL_DOWN_DELAY );
			$l_int_scroll_up_delay   = (int) Includes\Settings::instance()->get( \footnotes\includes\Settings::C_INT_FOOTNOTES_SCROLL_UP_DELAY );

		}

		$l_obj_template_container->replace(
			array(
				'post_id'              => self::$a_int_post_id,
				'container_id'         => self::$a_int_reference_container_id,
				'element'              => Includes\Settings::instance()->get( \footnotes\includes\Settings::C_STR_REFERENCE_CONTAINER_LABEL_ELEMENT ),
				'name'                 => empty( $l_str_reference_container_label ) ? '&#x202F;' : $l_str_reference_container_label,
				'button-style'         => $l_bool_collapse_default ? '' : 'display: none;',
				'style'                => $l_bool_collapse_default ? 'display: none;' : '',
				'caption'              => ( empty( $l_str_reference_container_label ) || ' ' === $l_str_reference_container_label ) ? 'References' : $l_str_reference_container_label,
				'content'              => $l_str_body,
				'scroll-offset'        => $l_int_scroll_offset,
				'scroll-down-delay'    => $l_int_scroll_down_delay,
				'scroll-down-duration' => $l_int_scroll_down_duration,
				'scroll-up-delay'      => $l_int_scroll_up_delay,
				'scroll-up-duration'   => $l_int_scroll_up_duration,
			)
		);

		// Free all found footnotes if reference container will be displayed.
		self::$a_arr_footnotes = array();

		return $l_obj_template_container->get_content();
	}
}