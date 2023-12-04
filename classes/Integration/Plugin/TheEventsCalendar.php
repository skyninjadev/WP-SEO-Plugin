<?php
/**
 * TheEventsCalendar Class
 *
 * @package nitropack
 */

namespace NitroPack\Integration\Plugin;

if ( ! function_exists( 'is_plugin_active' ) ) {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

/**
 * TheEventsCalendar Class
 */
class TheEventsCalendar {

	const STAGE = 'early';

	const WIDGET_ID = 'tribe-widget-events-list';

	/**
	 * Check if plugin "The Events Calendar" is active
	 *
	 * @return bool
	 */
	public static function isActive() {     //phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return is_plugin_active( 'the-events-calendar/the-events-calendar.php' );
	}

	/**
	 * Initialize the integration
	 *
	 * @param string $stage Stage.
	 *
	 * @return void
	 */
	public function init( $stage ) {  //phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		if ($this -> isActive()) {

            if (!wp_doing_ajax()) {
	            add_filter( 'dynamic_sidebar_params', [$this, 'filter_dynamic_sidebar_params'] );
	            add_filter( 'widget_output', [$this, 'widget_output_filter'], 10, 4 );
            }
			add_action( 'wp_ajax_nitropack_widget_output_ajax', [$this, 'widget_output_ajax'] );
			add_action( 'wp_ajax_nopriv_nitropack_widget_output_ajax', [$this, 'widget_output_ajax'] );
		}
	}

	/**
	 * Filter dynamic sidebar params
	 *
	 * @param array $sidebar_params Sidebar params.
	 *
	 * @return mixed
	 */
	public function filter_dynamic_sidebar_params( $sidebar_params ) {

		if ( is_admin() ) {
			return $sidebar_params;
		}

		global $wp_registered_widgets;
		$widget_id = $sidebar_params[0]['widget_id'];

		if ( strpos($widget_id, self::WIDGET_ID) !== false) {
			$wp_registered_widgets[ $widget_id ]['original_callback'] = $wp_registered_widgets[ $widget_id ]['callback'];
			$wp_registered_widgets[ $widget_id ]['callback'] = [$this, 'custom_widget_callback_function'];
		}
		return $sidebar_params;
	}

	/**
	 * Widget output filter
	 *
	 * @return void
	 */
	public function custom_widget_callback_function() {

		global $wp_registered_widgets;
		$original_callback_params = func_get_args();

		$widget_id         = $original_callback_params[0]['widget_id'];
		$original_callback = $wp_registered_widgets[ $widget_id ]['original_callback'];

		$wp_registered_widgets[ $widget_id ]['callback'] = $original_callback;

		$widget_id_base = $original_callback[0]->id_base;
		$sidebar_id     = $original_callback_params[0]['id'];

		if ( is_callable( $original_callback ) ) {

			ob_start();
			call_user_func_array( $original_callback, $original_callback_params );
			$widget_output = ob_get_clean();

			echo wp_kses_post(apply_filters( 'widget_output', $widget_output, $widget_id_base, $widget_id, $sidebar_id ));
		}
	}

	/**
	 * Filter the widget's output.
	 *
	 * @param string $widget_output  The widget's output.
	 * @param string $widget_id_base The widget's base ID.
	 * @param string $widget_id      The widget's full ID.
	 * @param string $sidebar_id     The current sidebar ID.
	 */
	public function widget_output_filter( $widget_output, $widget_id_base, $widget_id, $sidebar_id ) {

			wp_enqueue_script( 'nitropack-widget-ajax-script', NITROPACK_PLUGIN_DIR_URL . 'view/javascript/widgets_ajax.js?np_v=' . NITROPACK_VERSION, array('jquery'), NITROPACK_VERSION, true );
			wp_localize_script( 'nitropack-widget-ajax-script', 'nitropack_widget_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

			ob_start();
			?>
			<div class="nitropack-widget-ajax" data-widget-id="<?php echo esc_attr($widget_id); ?>" data-sidebar-id="<?php echo esc_attr($sidebar_id); ?>"><img src="<?php echo esc_url(NITROPACK_PLUGIN_DIR_URL . 'view/images/loading.gif'); ?>" alt="loading" /></div>
			<?php
			$widget_output = ob_get_clean();

		    return $widget_output;
	}

    /**
     * Widget output ajax
     *
     * @return void
     */
	public function widget_output_ajax(){

		global $wp_registered_sidebars, $wp_registered_widgets;

        $widget_id = isset($_GET['widget_id']) ? sanitize_text_field(wp_unslash($_GET['widget_id'])) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$sidebar_id = isset($_GET['sidebar_id']) ? sanitize_text_field(wp_unslash($_GET['sidebar_id'])) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

	    if( !empty($widget_id) && isset($wp_registered_widgets[$widget_id]) && isset($wp_registered_sidebars[$sidebar_id]) && isset($wp_registered_widgets[$widget_id]["callback"])) {

	        $original_callback = $wp_registered_widgets[$widget_id]['callback'];

            $params = [];

		    $params[] = $wp_registered_sidebars[$sidebar_id];

		    if (isset($wp_registered_widgets[ $widget_id ]['params'][0])) {
			    $params[] = $wp_registered_widgets[ $widget_id ]['params'][0];
		    }

		    if (is_callable($original_callback)) {

		        call_user_func_array($original_callback, $params);
            }
        }

		wp_die();
	}
}
