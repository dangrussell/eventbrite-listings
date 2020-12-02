<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://github.com/dangrussell/
 * @since      1.0.0
 *
 * @package    Eventbrite_Listings
 * @subpackage Eventbrite_Listings/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Eventbrite_Listings
 * @subpackage Eventbrite_Listings/public
 * @author     Dan Russell <dan.g.russell@gmail.com>
 */
class Eventbrite_Listings_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param string $plugin_name       The name of the plugin.
	 * @param string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Eventbrite_Listings_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Eventbrite_Listings_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/eventbrite-listings-public.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Eventbrite_Listings_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Eventbrite_Listings_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/eventbrite-listings-public.js', array( 'jquery' ), $this->version, false );

		wp_enqueue_script(
			'eb_widgets',
			'https://www.eventbrite.com/static/widgets/eb_widgets.js',
			null,
			$this->version,
			false
		);

		function eb_api(
			string $token,
			string $endpoint,
			string $query_params = '',
			string $json_facet = '',
			array $existing_arr = array(),
			string $continuation = '',
			string $api_url = 'https://www.eventbriteapi.com',
			string $api_ver = 'v3' ) {
			$curl = curl_init();

			$curl_url = $api_url . '/' . $api_ver . '/' . $endpoint . '/?' . $query_params;

			if ( '' !== $continuation ) {
				$curl_url .= '&continuation=' . $continuation;
			}

			curl_setopt_array(
				$curl,
				array(
					CURLOPT_URL            => $curl_url,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING       => '',
					CURLOPT_MAXREDIRS      => 10,
					CURLOPT_TIMEOUT        => 60,
					CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
					CURLOPT_CUSTOMREQUEST  => 'GET',
					CURLOPT_POSTFIELDS     => '',
					CURLOPT_HTTPHEADER     => array(
						'Accept: */*',
						'Authorization: Bearer ' . $token,
						'Cache-Control: no-cache',
						'Connection: keep-alive',
						'Host: www.eventbriteapi.com',
						'Accept-Encoding: gzip, deflate',
					),
				)
			);

			$response_json = curl_exec( $curl );
			$err           = curl_error( $curl );

			curl_close( $curl );

			if ( $err ) {
				echo 'cURL Error #:' . esc_html( $err );
				return false;
			} else {
				$response_arr = json_decode( $response_json, true );

				if ( '' !== $json_facet ) {
					if ( isset( $existing_arr[ $json_facet ] ) ) {
						$response_arr[ $json_facet ] = array_merge( $response_arr[ $json_facet ], $existing_arr[ $json_facet ] );
					}
				} else {
					if ( is_array( $response_arr ) ) {
						$response_arr = array_merge( $response_arr, $existing_arr );
					}
				}

				if ( isset( $response_arr['pagination'] ) ) {

					if ( $response_arr['pagination']['page_number'] < $response_arr['pagination']['page_count'] ) {

						$new_continuation = $response_arr['pagination']['continuation']; // add the continuation token.

						return eb_api( $token, $endpoint, $query_params, $json_facet, $response_arr, $new_continuation );
					} else {

						if ( '' !== $json_facet ) {
							$eb_api_response_arr = $response_arr[ $json_facet ];
						} else {
							$eb_api_response_arr = $response_arr;
						}

						return $eb_api_response_arr;
					}
				} else {

					if ( '' !== $json_facet ) {
						$eb_api_response_arr = $response_arr[ $json_facet ];
					} else {
						$eb_api_response_arr = $response_arr;
					}

					return $eb_api_response_arr;
				}
			}
		}

		/**
		 * Button label
		 *
		 * @param array  $event Event.
		 * @param array  $event_tix Tickets.
		 * @param bool   $sold_out Sold out.
		 * @param string $tix_label Ticket label.
		 * @return string
		 */
		function display_event_card( array $event, array $event_tix, bool $sold_out, string $tix_label = '' ): string {
			$date_label = '';
			if ( ( strtotime( $event['end']['local'] ) - strtotime( $event['start']['local'] ) ) / 60 / 60 > 24 ) {
				/* Event is longer than one day. */
				$date_label = 'Starts ';
			}

			$button['price'] = '';
			$num_tix         = count( $event_tix );
			$is_donation     = false;
			for ( $j = 0; $j < $num_tix; $j++ ) {
				$fb_value = $event_tix[ $j ]['cost']['value'] / 100;

				$button['price'] .= '$';
				$button['price'] .= $fb_value;

				if ( ( $j + 1 ) < $num_tix ) {
					$button['price'] .= ' / ';
				}

				if ( true === $event_tix[ $j ]['donation'] ) {
					$is_donation = true;
				}
			}

			$button['text'] = 'Learn More';

			if ( '' === $button['price'] ) {
				$button['text'] = '';
			}

			if ( $sold_out ) {
				$button['text']  = 'Join Waitlist';
				$button['price'] = '';
			}

			if ( $is_donation ) {
				$button['price'] = 'Donation';
			}

			// TODO: Use true HTML type builder, rather than building a string.
			$event_card  = '<div class="col-xs-12 col-sm-6 col-md-6 col-lg-4 center-block" id="' . $event['id'] . '">';
			$event_card .= '<div class="panel panel-default"';
			if ( '' !== $event['logo']['edge_color'] ) {
				$event_card .= ' style="border-color:' . $event['logo']['edge_color'] . ';"';
			}
			$event_card .= '>';
			$event_card .= '<a href="https://www.eventbrite.com/e/' . $event['id'] . '">';
			$event_card .= '<img src="' . $event['logo']['url'] . '" class="img-responsive center-block" alt="' . htmlentities( $event['name']['text'], ENT_COMPAT ) . '" loading="lazy" height="200" width="400">';
			$event_card .= '</a>';
			$event_card .= '<div class="panel-body">';
			$event_card .= '<strong>';
			$event_card .= $date_label;
			$event_card .= date( 'l, F j', strtotime( $event['start']['local'] ) );
			$event_card .= '<span class="hidden-xs">';
			$event_card .= date( 'S', strtotime( $event['start']['local'] ) );
			$event_card .= '</span> at ';
			$event_card .= date( 'g:i A', strtotime( $event['start']['local'] ) );
			$event_card .= '</strong>';
			$event_card .= '<h3>';
			$event_card .= '<a href="https://www.eventbrite.com/e/' . $event['id'] . '">' . $event['name']['text'] . '</a>';

			if ( '' !== $tix_label ) {
				$event_card .= '<span class="label">' . $tix_label . '</span>';
			}

			$event_card .= '</h3>';

			if ( '' !== $button['text'] ) {
				$event_card .= '<a class="btn btn-primary btn-lg btn-block" id="eventbrite-modal-trigger-' . $event['id'] . '">';
				$event_card .= $button['text'];
				if ( '' !== $button['price'] ) {
					$event_card .= '&nbsp;( ' . $button['price'] . ')';
				}
				$event_card .= '</a>';
			}
			$event_card .= '</div>';

			if ( '' !== $button['text'] ) {
				$event_card          .= '<script>';
				$widget_config_string = get_widget_config( $event['id'] );
				$event_card          .= 'window.EBWidgets.createWidget({' . $widget_config_string . '});';
				$event_card          .= '</script>';
			}

			$event_card .= '<div class="panel-footer">';

			if ( true === $event['online_event'] ) {
				$event_card .= '<strong>Online</strong>';
			} else {
				$event_card .= '<strong>' . $event['venue']['name'] . '</strong><br />';
				if ( $event['venue']['address']['address_1'] ) {
					$event_card .= $event['venue']['address']['address_1'] . ', ';
				}
				if ( $event['venue']['address']['address_2'] ) {
					$event_card .= $event['venue']['address']['address_2'] . ', ';
				}
				if ( $event['venue']['address']['city'] ) {
					$event_card .= $event['venue']['address']['city'];
				}
				if ( $event['venue']['address']['region'] ) {
					$event_card .= ', ' . $event['venue']['address']['region'];
				}
				$event_card .= '<span class="hidden-xs"> ';
				$event_card .= $event['venue']['address']['postal_code'];
				$event_card .= '</span>';
			}

			$event_card .= '</div>';

			$event_card .= '</div>';
			$event_card .= '<script type="application/ld+json">' . event_json( $event ) . '</script>';
			$event_card .= '</div>';

			return $event_card;
		}

		function get_widget_config(
			int $event_id,
			string $aff = '',
			string $discount = '',
			string $type = 'modal'
			): string {
			$widget_config_string        = '';
			$widget_config               = array();
			$widget_config['widgetType'] = "'checkout'";
			$widget_config['eventId']    = "'" . $event_id . "'";
			if ( 'modal' === $type ) {
				$widget_config['modal']                 = 'true';
				$widget_config['modalTriggerElementId'] = "'eventbrite-modal-trigger-" . $event_id . "'";
			} elseif ( 'iframe' === $type ) {
				$widget_config['iframeContainerId']     = "'eventbrite-widget-container-" . $event_id . "'";
				$widget_config['iframeContainerHeight'] = 550;
			}

			$widget_config['affiliateCode'] = "'" . $aff . "'";
			if ( '' !== $discount ) {
				$widget_config['promoCode'] = "'" . $discount . "'";
			}

			$widget_keys = array_keys( $widget_config );
			$last_key    = end( $widget_keys );
			foreach ( $widget_config as $key => $val ) {
				$widget_config_string .= $key . ':' . $val;
				if ( $key !== $last_key ) {
					$widget_config_string .= ',';
				}
			}
			return $widget_config_string;
		}

		function date_compare( array $a, array $b ): int {
			$t1 = strtotime( $a['start']['local'] );
			$t2 = strtotime( $b['start']['local'] );
			return $t1 - $t2;
		}

		function build_desc( string $desc ): string {
			$desc_built = '';
			$desc_regex = '/([.?!]\s)/';
			$desc_array = preg_split( $desc_regex, $desc, null, PREG_SPLIT_DELIM_CAPTURE );
			$desc_len   = count( $desc_array );

			for ( $d = 0; $d < $desc_len; $d++ ) {
				if ( strlen( $desc_built ) < 120 ) {
					$desc_built .= $desc_array[ $d ];
					if ( 1 === strlen( $desc_array[ $d ] ) ) {
						$desc_built .= ' ';
					}

					$last_d = $d;
				}
			}

			if ( 0 === ( $last_d % 2 ) ) {
				$e = $last_d + 1;
				if ( isset( $desc_array[ $e ] ) ) {
					$desc_built .= $desc_array[ $e ];
				}
			}

			if ( strpos( $desc_built, '===' ) ) {
				$desc_built = substr( $desc_built, 0, strpos( $desc_built, '===' ) );
			}

			return trim( $desc_built );
		}

		function fix_eventbrite_html( string $html_to_fix ): string {
			$ugly_html_from_eventbrite = array(
				/* HTML improvements */
				'<SPAN>',
				'</SPAN>',
				'<P>===</P>',
				'<BR>',
				'&nbsp;',
				'<hr>',

				/* HTML case */
				'STRONG>',
			);

			$preferred_html = array(
				/* HTML improvements */
				'',
				'',
				'<hr />',
				'<br />',
				' ',
				'<hr />',

				/* HTML case */
				'strong>',
			);

			$fixed_html = trim( str_replace( $ugly_html_from_eventbrite, $preferred_html, $html_to_fix ) );

			return $fixed_html;
		}

		function format_description( $desc ): string {
			$json_description = str_replace(
				array(
					"\n",
					"\r",
					chr( 194 ) . chr( 160 ),
				),
				' ',
				$desc
			);
			$json_description = trim( $json_description );
			return $json_description;
		}

		function event_json( $event ) {
			// TODO: Use true JSON type builder, rather than array that is then JSON encoded.
			$json['@context'] = 'https://schema.org';
			$json['@type']    = 'Event';

			$json['name']        = $event['name']['text'];
			$json['description'] = format_description( $event['description']['text'] );
			$json['startDate']   = $event['start']['utc'];
			$json['endDate']     = $event['end']['utc'];
			if ( true === $event['online_event'] ) {
				$json['eventAttendanceMode'] = 'https://schema.org/OnlineEventAttendanceMode';

				$json['location']['@type'] = 'VirtualLocation';
				$json['location']['url']   = 'https://www.eventbrite.com/e/' . $event['id'];
			} else {
				$address['@type'] = 'PostalAddress';
				if ( $event['venue']['address']['address_1'] ) {
					$address['streetAddress'] = $event['venue']['address']['address_1'];
					if ( $event['venue']['address']['address_2'] ) {
						$address['streetAddress'] .= ', ';
						$address['streetAddress'] .= $event['venue']['address']['address_2'];
					}
				}
				$address['addressLocality'] = $event['venue']['address']['city'];
				$address['addressRegion']   = $event['venue']['address']['region'];
				$address['postalCode']      = $event['venue']['address']['postal_code'];
				$address['addressCountry']  = $event['venue']['address']['country'];

				$json['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';

				$json['location']['@type']   = 'Place';
				$json['location']['name']    = $event['venue']['name'];
				$json['location']['address'] = $address;
			}

			$json['eventStatus'] = 'https://schema.org/EventScheduled';

			$organizer['@type'] = 'Organization';
			$organizer['name']  = $event['organizer']['name'];
			$organizer['url']   = $event['organizer']['url'];
			$json['organizer']  = $organizer;

			$json['image'] = array( $event['logo']['original']['url'], $event['logo']['url'] );
			$json['url']   = 'https://www.eventbrite.com/e/' . $event['id'];

			$json['performer'] = $event['organizer']['name'];

			$json['offers'] = array();

			$num_classes = count( $event['ticket_classes'] );
			for ( $j = 0; $j < $num_classes; $j++ ) {
				$price = $event['ticket_classes'][ $j ]['cost']['major_value'] + $event['ticket_classes'][ $j ]['fee']['major_value'];
				$price = number_format( (float) $price, 2, '.', '' );

				$offer['@type'] = 'Offer';
				$offer['name']  = $event['ticket_classes'][ $j ]['name'];
				if ( $event['ticket_classes'][ $j ]['description'] ) {
					$offer['description'] = $event['ticket_classes'][ $j ]['description'];
				}
				$offer['price'] = $price;
				if ( $event['ticket_classes'][ $j ]['cost']['currency'] ) {
					$offer['priceCurrency'] = $event['ticket_classes'][ $j ]['cost']['currency'];
				} else {
					$offer['priceCurrency'] = 'USD';
				}
				$offer['validFrom'] = $event['created'];
				if ( isset( $event['ticket_classes'][ $j ]['sales_end'] ) ) {
					$offer['validThrough'] = $event['ticket_classes'][ $j ]['sales_end'];
				} else {
					$offer['validThrough'] = $event['start']['utc'];
				}
				$offer['url'] = 'https://www.eventbrite.com/e/' . $event['id'];
				if ( 'AVAILABLE' === $event['ticket_classes'][ $j ]['on_sale_status'] ) {
					$offer['availability'] = 'https://schema.org/InStock';
				} else {
					$offer['availability'] = 'https://schema.org/SoldOut';
				}

				array_push( $json['offers'], $offer );
				unset( $offer );
			}

			return wp_json_encode( $json, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
		}

		/**
		 * Plural label
		 *
		 * @param int $count A number that needs labelling.
		 * @return string
		 */
		function plural( int $count ): string {
			if ( 1 === $count ) {
				return '';
			} else {
				return 's';
			}
		}

		/**
		 * Ticket label
		 *
		 * @param array $ticket_classes An array of ticket classes.
		 * @return string
		 */
		function tix_label( array $ticket_classes ): string {
			if ( 0 !== tix_cap( $ticket_classes ) ) { // Don't do label on events with no limit.
				if ( tix_sold_out( $ticket_classes ) ) {
					return 'SOLD OUT!';
				}

				if ( tix_sold_percent( $ticket_classes ) >= 50 ) {
					$tix_left = tix_left( $ticket_classes );

					if ( $tix_left < 10 ) {
						return $tix_left . ' spot' . plural( $tix_left ) . ' left';
					} else {
						return 'Selling fast!';
					}
				}
			}

			return '';
		}

		/**
		 * Tickets sold?
		 *
		 * @param array $ticket_classes An array of ticket classes.
		 * @return int
		 */
		function tix_sold( array $ticket_classes ): int {
			$tix_sold = 0;

			$num_classes = count( $ticket_classes );
			for ( $j = 0; $j < $num_classes; $j++ ) {
				if ( isset( $ticket_classes[ $j ]['quantity_sold'] ) ) {
					/* Spots remaining */
					$tix_sold += (int) $ticket_classes[ $j ]['quantity_sold'];
				}
			}

			return $tix_sold;
		}

		/**
		 * Percent sold out?
		 *
		 * @param array $ticket_classes An array of ticket classes.
		 * @return int
		 */
		function tix_sold_percent( array $ticket_classes ): int {
			return ( tix_sold( $ticket_classes ) / tix_cap( $ticket_classes ) ) * 100;
		}

		/**
		 * Capacity?
		 *
		 * @param array $ticket_classes An array of ticket classes.
		 * @return int
		 */
		function tix_cap( array $ticket_classes ): int {
			if ( isset( $ticket_classes[0]['quantity_total'] ) ) {
				/* assume a shared capacity for all ticket classes. This is *not* always true... */
				$tix_cap = (int) $ticket_classes[0]['quantity_total'];
			} else {
				$tix_cap = 0;
			}

			return $tix_cap;
		}

		/**
		 * How many tickets left?
		 *
		 * @param array $ticket_classes An array of ticket classes.
		 * @return int
		 */
		function tix_left( array $ticket_classes ): int {
			return tix_cap( $ticket_classes ) - tix_sold( $ticket_classes );
		}

		/**
		 * Sold out?
		 *
		 * @param array $ticket_classes An array of ticket classes.
		 * @return bool
		 */
		function tix_sold_out( array $ticket_classes ): bool {
			if ( tix_left( $ticket_classes ) < 1 && tix_cap( $ticket_classes ) !== 0 ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Handles tickets.
		 *
		 * @param array $ticket_classes An array of ticket classes.
		 * @return array
		 */
		function tix_handler( array $ticket_classes ): array {
			$event_tix   = array();
			$event_tix_i = 0;

			$num_classes = count( $ticket_classes );

			for ( $j = 0; $j < $num_classes; $j++ ) {
				$hidden = '0';
				if ( isset( $ticket_classes[ $j ]['hidden'] ) ) {
					$hidden = $ticket_classes[ $j ]['hidden'];
				}
				if (
					false === strpos( $ticket_classes[ $j ]['name'], 'Support' )
					&&
					'1' !== $hidden
					&&
					'UNAVAILABLE' !== $ticket_classes[ $j ]['on_sale_status']
				) {
					$event_tix[ $event_tix_i ]['cost']['value'] = $ticket_classes[ $j ]['cost']['value'];
					$event_tix[ $event_tix_i ]['donation']      = $ticket_classes[ $j ]['donation'];
					$event_tix_i++;
				}
			}

			return $event_tix;
		}

		/**
		 * This is the shortcode function.
		 *
		 * @since    1.0.0
		 * @param array $atts_in Shortcode attributes.
		 */
		function eventbrite_listings_func( array $atts_in ) {
			$atts_local = shortcode_atts(
				array(
					'organization' => '', /* Top-level organization ID */
					'token'        => '', /* API token */
				),
				$atts_in,
				'eventbrite-listings'
			);

			$shown_events = 0;
			$list_schema  = '';

			$use_cache = 0;

			/* LOAD FROM EVENTBRITE, NOT SINGLE EVENT */
			$events_endpoint = 'organizations/' . $atts_local['organization'] . '/events';

			$events_query_params  = 'status=live,started';
			$events_query_params .= '&order_by=start_asc';
			$events_query_params .= '&time_filter=current_future';
			$events_query_params .= '&expand=venue,organizer,ticket_classes';

			$organizer_events = eb_api( $atts_local['token'], $events_endpoint, $events_query_params );

			usort( $organizer_events['events'], 'date_compare' ); // Sort array by date.

			$num_events = count( $organizer_events['events'] );

			$num_events_to_show = $num_events;

			ob_start(); ?>
			<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.1/js/bootstrap.min.js" integrity="sha512-oBTprMeNEKCnqfuqKd6sbvFzmFQtlXS3e0C/RGFV0hD6QzhHV+ODfaQbAlmY6/q0ubbwlAM/nCJjkrgA3waLzg==" crossorigin="anonymous"></script>
			<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css" integrity="sha512-Dop/vW3iOtayerlYAqCgkVr2aTr2ErwwTYOvRFUpzl2VhCMJyjQF0Q9TjUXIo6JhuM/3i0vVEt2e/7QQmnHQqw==" crossorigin="anonymous" />
			<section class="container-fluid">
				<div class="row">
					<?php
					for ( $i = 0; $i < $num_events_to_show; $i++ ) {
						$listed = false;
						if ( isset( $organizer_events['events'][ $i ]['listed'] ) ) {
							$listed = $organizer_events['events'][ $i ]['listed'];
						}
						if ( true === $listed ) {
							$showago = time() - ( ( 60 * 60 ) * 1.5 );

							if ( strtotime( $organizer_events['events'][ $i ]['start']['local'] ) >= $showago ) {
								/*
								This if prevents old events in cache from showing. $start today's date as defined in core file.
								*/
								$event_tix = tix_handler( $organizer_events['events'][ $i ]['ticket_classes'] );
								$sold_out  = tix_sold_out( $organizer_events['events'][ $i ]['ticket_classes'] );
								$tix_label = tix_label( $organizer_events['events'][ $i ]['ticket_classes'] );

								echo display_event_card( $organizer_events['events'][ $i ], $event_tix, $sold_out, $tix_label );

								if ( ( $shown_events + 1 ) % 3 === 0 ) {
									echo '<div class="clearfix visible-lg-block"></div>';
								}
								if ( ( $shown_events + 1 ) % 2 === 0 ) {
									echo '<div class="clearfix visible-sm-block visible-md-block"></div>';
									/* <!--<div class="clearfix visible-md-block"></div>--> */
								}

								$shown_events++;

								$list_schema .= '
							{
								"@type":"ListItem",
								"position":' . $shown_events . ',
								"url":"https://www.eventbrite.com/e/' . $organizer_events['events'][ $i ]['id'] . '"
							},';
							} /* ends date-checking if statement */
						} /* ends listed-checking if statement */
					} /* ends events loop */
					/* ends div row */
					if ( 0 === $shown_events ) {
						?>
						<p class="lead">There are currently no upcoming events posted for this section. Please check back soon.</p>
						<?php
					}
					?>
				</div>
			</section><?php /* ends section container-fluid */ ?>
			<script type="application/ld+json">
				{
					"@context": "https://schema.org",
					"@type": "ItemList",
					"itemListElement": [<?php echo substr( $list_schema, 0, -1 ); ?>]
				}
			</script>
			<?php
			return ob_get_clean();
		}
		add_shortcode( 'eventbrite-listings', 'eventbrite_listings_func' );
	}

}
