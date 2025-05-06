//code from 13:03 06/05/25

<?php
add_action( 'wp_enqueue_scripts', 'kariez_child_styles', 18 );
function kariez_child_styles() {
	wp_enqueue_style( 'child-style', get_stylesheet_uri() );
}
function enqueue_global_styles_scripts() {
    // Enqueue Stylesheet
    $stylesheet_uri = get_stylesheet_directory_uri() . '/style.css';
    $stylesheet_version = filemtime(get_stylesheet_directory() . '/style.css');
    wp_enqueue_style('global_styles', $stylesheet_uri, array(), $stylesheet_version);

    // Enqueue JavaScript
    $script_uri = get_stylesheet_directory_uri() . '/script.js';
    $script_version = filemtime(get_stylesheet_directory() . '/script.js');
    wp_enqueue_script('global_scripts', $script_uri, array('jquery'), $script_version, true);
}
add_action('wp_enqueue_scripts', 'enqueue_global_styles_scripts', 20);


wp_localize_script('your-script-handle', 'bikeMovingAjax', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce'    => wp_create_nonce('bike_moving_nonce')
]);

function getin_touch_popup_scripts() {
    wp_enqueue_script('get-in-touch-popup-js', get_stylesheet_directory_uri() . '/script.js', ['jquery'], null, true);
}
add_action('wp_enqueue_scripts', 'getin_touch_popup_scripts');
//to add whatsapp button

// function Load_chat() {
//     echo '
//     <script>
//         var url = "https://wati-integration-prod-service.clare.ai/v2/watiWidget.js?33987";
//         var s = document.createElement("script");
//         s.type = "text/javascript";
//         s.async = true;
//         s.src = url;
        
//         var options = {
//             "enabled": true,
//             "chatButtonSetting": {
//                 "backgroundColor": "#00e785",
//                 "ctaText": "Chat with us",
//                 "borderRadius": "25",
//                 "marginLeft": "40",
//                 "marginRight": "20",
//                 "marginBottom": "20",
//                 "ctaIconWATI": false,
//                 "position": "left"
//             },
//             "brandSetting": {
//                 "brandName": "Intellilogistics",
//                 "brandSubTitle": "undefined",
//                 "brandImg": "https://www.wati.io/wp-content/uploads/2023/04/Wati-logo.svg",
//                 "welcomeText": "Hi there!\\nHow can I help you?",
//                 "messageText": "Hello, %0A I have a question about {{page_link}}",
//                 "backgroundColor": "#00e785",
//                 "ctaText": "Chat with us",
//                 "borderRadius": "25",
//                 "autoShow": false,
//                 "phoneNumber": "919900059918"
//             }
//         };

//         s.onload = function() {
//             CreateWhatsappChatWidget(options);
//         };
        
//         document.getElementsByTagName("head")[0].appendChild(s);
//     </script>';
// }
// add_action("wp_footer", "Load_chat");


function R_Leads() {
    add_menu_page(
        'Leads Manage',
        'Leads',
        'manage_options',
        'dbLead_dashboard',
        'dbLead_dashboard_page',
        'dashicons-groups',
        25
    );
}
add_action('admin_menu', 'R_Leads');


function dbLead_dashboard_page() {
    $bookings = get_bookings_data();
    
    echo '<div class="wrap"><h1>Leads Manager</h1>';
    if (!empty($bookings)) {
        echo '<table border="1" cellpadding="10" cellspacing="0">
                <tr>
                    <th>ID</th>
                    <th>Name</th>
					<th>Phone </th>
					<th>Parcel Details </th>
					<th>from_location</th>
					<th>to_location</th>
                    <th>Submitted At</th>
                </tr>';
        foreach ($bookings as $booking) {
            echo '<tr>
                    <td>' . esc_html($booking['id']) . '</td>
                    <td>' . esc_html($booking['name']) . '</td>
                    <td>' . esc_html($booking['phone']) . '</td>
					<td>' . esc_html($booking['parcel_details']) . '</td>
					<td>' . esc_html($booking['from_location']) . '</td>
					<td>' . esc_html($booking['to_location']) . '</td>
					<td>' .esc_html($booking['date_submitted']) . '</td>
                </tr>';
        }
        echo '</table>';
    } else {
        echo '<p>No leads found.</p>';
    }
 
    
    echo '<button id="refreshBtn" style="cursor:pointer;">Refresh</button>';
	echo '</div>';
    echo '<script>
        document.getElementById("refreshBtn").addEventListener("click", function() {
            location.reload();
        });
    </script>';
}
function get_bookings_data() {
	global $wpdb;
    $table_name = $wpdb->prefix . 'moving_service';
    return $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC", ARRAY_A);
}
function Reloadnow() {
    echo '<script>window.location.reload();</script>';
}

function handle_moving_service_form_submission() {
    // Verify nonce for security
    check_ajax_referer('moving_service_nonce', 'security');

    global $wpdb;
    $table_name = $wpdb->prefix . 'moving_service';

    // Check required fields
    $errors = [];
    if (empty($_POST['name'])) $errors['name'] = 'Name is required';
    if (empty($_POST['phone'])) $errors['phone'] = 'Phone is required';
    if (empty($_POST['from_location'])) $errors['from_location'] = 'From location is required';
    if (empty($_POST['to_location'])) $errors['to_location'] = 'To location is required';

    if (!empty($errors)) {
        wp_send_json_error([
            'message' => 'Please fill all required fields',
            'errors' => $errors
        ], 400);
    }

    // Sanitize inputs
    $data = [
        'name' => sanitize_text_field($_POST['name']),
        'phone' => sanitize_text_field($_POST['phone']),
        'email' => sanitize_text_field($_POST['email']),
        'from_location' => sanitize_text_field($_POST['from_location']),
        'to_location' => sanitize_text_field($_POST['to_location']),
        'parcel_details' => isset($_POST['parcel_details']) ? sanitize_textarea_field($_POST['parcel_details']) : '',
        'bike_parcel' => isset($_POST['bike-parcel']) && $_POST['bike-parcel'] === 'yes' ? 'yes' : 'no'
    ];

    $bike_parcel_status = ($data['bike_parcel'] === 'yes') ? 'Yes' : 'No';

    // Confirmation email to user
    $user_email = $data['email'];
    if (!empty($user_email) && is_email($user_email)) {
        date_default_timezone_set('Asia/Kolkata');
        $user_subject = '✅ Your Moving Service Booking is Confirmed - ' . date('d M Y, h:i A') . ' 🕒';

        $user_message = <<<HTML
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; margin: 0 auto; background-color: #fff; padding: 40px; border-radius: 10px; border: 1px solid #ebeff6; box-shadow: 0 4px 10px 0 #14142b0a;">
    <h2 style="color: #ff2d46;">🚚 Your Moving Service Booking is Confirmed</h2>
    <p style="font-size: 16px; color: #333;">Dear <strong>{$data['name']}</strong>,</p>
    <p style="font-size: 15px; color: #333;">Thank you for your request. Your booking is confirmed! Here are the details you submitted:</p>
    <table style="width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 15px;">
        <tr><td style="padding: 8px;"><strong>Name:</strong></td><td style="padding: 8px;">{$data['name']}</td></tr>
        <tr style="background-color: #f0f8ff;"><td style="padding: 8px;"><strong>Phone:</strong></td><td style="padding: 8px;">{$data['phone']}</td></tr>
        <tr><td style="padding: 8px;"><strong>Email:</strong></td><td style="padding: 8px;">{$data['email']}</td></tr>
        <tr style="background-color: #f0f8ff;"><td style="padding: 8px;"><strong>From:</strong></td><td style="padding: 8px;">{$data['from_location']}</td></tr>
        <tr><td style="padding: 8px;"><strong>To:</strong></td><td style="padding: 8px;">{$data['to_location']}</td></tr>
        <tr style="background-color: #f0f8ff;"><td style="padding: 8px;"><strong>Parcel Details:</strong></td><td style="padding: 8px;">{$data['parcel_details']}</td></tr>
        <tr><td style="padding: 8px;"><strong>Bike Parcel:</strong></td><td style="padding: 8px;">{$bike_parcel_status}</td></tr>
    </table>
    <div style="margin-top: 25px; font-size: 15px; color: #444;">
        <p>If you have any questions, feel free to reach out:</p>
        <p>📧 <a href="mailto:support@intelizenlogistics.com" style="color: #1e90ff;">support@intelizenlogistics.com</a><br>
        📞 <a href="tel:+919900059918" style="color: #1e90ff;">+91 99000 59918</a></p>
    </div>
    <p style="margin-top: 30px; font-size: 14px; color: #888;">Regards,<br><strong>Intelizen Logistics Team</strong></p>
</div>
HTML;
           add_filter('wp_mail_from', function() {
             return 'no-reply@intelizenlogistics.com';
              });

            add_filter('wp_mail_from_name', function() {
               return 'Intelizenlogistics';
             });

            $movingservice_headersuser = [
              'Content-Type: text/html; charset=UTF-8',
              'Reply-To: support@intelizenlogistics.com'
             ];
            wp_mail($user_email, $user_subject, $user_message, $movingservice_headersuser);
            }

    // Insert into DB
    $wpdb->insert($table_name, $data);

    
	date_default_timezone_set('Asia/Kolkata');
$houseparcel_subject = 'New Moving Service Request - ' . date('Y-m-d h:i:s A');
    $message = <<<HTML
<div style="font-family: 'Segoe UI', Roboto, Arial, sans-serif; background-color: #fff6f3; padding: 40px; border-radius: 16px; border: 1px solid #ffe9e1; max-width: 700px; margin: 0 auto; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
    <h2 style="color: #ff5a3c; margin-bottom: 10px;">📦 New Moving Service Request</h2>
    <p style="color: #333; font-size: 16px; margin-bottom: 20px;">A new booking has been submitted with the following details:</p>
    <table style="width: 100%; border-collapse: collapse; font-size: 15px;">
        <tr><td style="padding: 10px;"><strong style="color: #ff5a3c;">Name:</strong></td><td style="padding: 10px;">{$data['name']}</td></tr>
        <tr style="background-color: #fff0ec;"><td style="padding: 10px;"><strong style="color: #ff5a3c;">Phone:</strong></td><td style="padding: 10px;">{$data['phone']}</td></tr>
        <tr><td style="padding: 10px;"><strong style="color: #ff5a3c;">Email:</strong></td><td style="padding: 10px;">{$data['email']}</td></tr>
        <tr style="background-color: #fff0ec;"><td style="padding: 10px;"><strong style="color: #ff5a3c;">From:</strong></td><td style="padding: 10px;">{$data['from_location']}</td></tr>
        <tr><td style="padding: 10px;"><strong style="color: #ff5a3c;">To:</strong></td><td style="padding: 10px;">{$data['to_location']}</td></tr>
        <tr style="background-color: #fff0ec;"><td style="padding: 10px;"><strong style="color: #ff5a3c;">Parcel Details:</strong></td><td style="padding: 10px;">{$data['parcel_details']}</td></tr>
        <tr><td style="padding: 10px;"><strong style="color: #ff5a3c;">Bike Parcel:</strong></td><td style="padding: 10px;">{$bike_parcel_status}</td></tr>
    </table>
    <div style="margin-top: 30px; text-align: center;">
        <a href="mailto:support@intelizenlogistics.com" style="display: inline-block; background-color: #ff5a3c; color: #fff; padding: 12px 25px; border-radius: 25px; text-decoration: none; font-weight: bold; font-size: 14px;">
            📧 Get in Touch
        </a>
    </div>
    <p style="margin-top: 25px; font-size: 13px; color: #999; text-align: center;">
        Sent from the Intelizen Logistics Website
    </p>
</div>
HTML;
	add_filter('wp_mail_from', function() {
        return 'no-reply@intelizenlogistics.com';
    });
    
    add_filter('wp_mail_from_name', function() {
        return 'Intelizenlogistics';
    });
   $movingservice_headersadmin = [
    'Content-Type: text/html; charset=UTF-8',
    'Reply-To: support@intelizenlogistics.com'
];
    $admin_emails = ['ganesh@advaitlabs.com', 'support@intelizenlogistics.com'];
    wp_mail($admin_emails, $houseparcel_subject, $message, $movingservice_headersadmin);

    // Return response
    wp_send_json_success([
        'message' => 'Form submitted successfully!',
        'data' => $data
    ]);
}

add_action('wp_ajax_moving_service_form', 'handle_moving_service_form_submission');
add_action('wp_ajax_nopriv_moving_service_form', 'handle_moving_service_form_submission');





 
function hero_form() {
    ob_start();
    ?>

    <?php if (isset($_GET['form_submitted']) && $_GET['form_submitted'] === 'success') : ?>
        <p style="color: green;">Form submitted successfully!</p>
    <?php endif; ?>

   
     <section class="form-container_hero">
		   <div class="tabswitcher">
        <div class="tab_bike_parcel active tabswitch" data-target="bike">
          <span class="tab_text">Bike Parcel</span>
        </div>
        <div class="tab_household_parcel tabswitch" data-target="parcel">
          <span class="tab_text">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              width="16"
              height="16"
              fill="none"
              viewBox="0 0 16 16"
            >
              <path stroke="currentColor" d="M1.5 14.5v-8l6.5-5 6.5 5v8h-13Z" />
              <path stroke="currentColor" d="M6.5 14.5v-5h3v5" />
            </svg>
            House holds Parcel</span
          >
        </div>
      </div>
   <form
        id="moving-service-form"
        class="form"
        method="POST"
      >
        <input type="hidden" name="action" value="moving_service_form" />
       
        <p class="message">Fast & Easy shipping for Bikes, Luggage, Household goods</p>

        <div class="flex">
          <!-- First Dropdown (From Location) -->
          <label>
            <div class="select-menu">
              <div class="select-btn">
                <span class="sBtn-text">Select From</span>
                <input
                  type="hidden"
                  name="from_location"
                  id="from_location"
                  required=""
                />
                <svg
                  width="14"
                  height="15"
                  viewBox="0 0 14 15"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    d="M3.5 5.75L7 9.25L10.5 5.75"
                    stroke="#131316"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  ></path>
                </svg>
              </div>
              <ul class="options">
                <li class="option" data-value="Location 1">
                  <span class="option-text">Hyderabad</span>
                </li>
                <li class="option" data-value="Location 2">
                  <span class="option-text">Bangalore</span>
                </li>
                <li class="option" data-value="Location 3">
                  <span class="option-text">Pune</span>
                </li>
                <li class="option" data-value="Location 4">
                  <span class="option-text">Vizag</span>
                </li>
                <li class="option" data-value="Location 4">
                  <span class="option-text">Tirupathi</span>
                </li>
                <li class="option" data-value="Location 4">
                  <span class="option-text">Chennai</span>
                </li>
                <li class="option" data-value="Location 4">
                  <span class="option-text">Delhi</span>
                </li>
                <li class="option" data-value="Location 4">
                  <span class="option-text">Vijayawada</span>
                </li>
              </ul>
            </div>
          </label>

          <!-- Second Dropdown (To Location) -->
          <label>
            <div class="select-menu">
              <div class="select-btn">
                <span class="sBtn-text">Select To</span>
                <input
                  type="hidden"
                  name="to_location"
                  id="to_location"
                  required=""
                />
                <svg
                  width="14"
                  height="15"
                  viewBox="0 0 14 15"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    d="M3.5 5.75L7 9.25L10.5 5.75"
                    stroke="#131316"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  ></path>
                </svg>
              </div>
              <ul class="options">
                <li class="option" data-value="Destination 1">
                  <span class="option-text">Hyderabad</span>
                </li>
                <li class="option" data-value="Destination 2">
                  <span class="option-text">Bangalore</span>
                </li>
                <li class="option" data-value="Destination 3">
                  <span class="option-text">Pune</span>
                </li>
                <li class="option" data-value="Destination 4">
                  <span class="option-text">Vizag</span>
                </li>
                <li class="option" data-value="Destination 4">
                  <span class="option-text">Tirupathi</span>
                </li>
                <li class="option" data-value="Destination 4">
                  <span class="option-text">Chennai</span>
                </li>
                <li class="option" data-value="Destination 4">
                  <span class="option-text">Delhi</span>
                </li>
                <li class="option" data-value="Destination 4">
                  <span class="option-text">Vijayawada</span>
                </li>
              </ul>
            </div>
          </label>
        </div>

        <label>
          <input
            required=""
            name="name"
            placeholder=""
            type="text"
            class="input"
          />
          <span>Name</span>
        </label>
        <div class="flex">
          <label>
            <input
              required=""
              name="phone"
              placeholder=""
              type="tel"
              class="input"
            />
            <span>Phone</span>
          </label>
          <label>
            <input
              required=""
              name="email"
              placeholder=""
              type="email"
              class="input"
            />
            <span>Email</span>
          </label>
        </div>
        <label>
          <textarea
            name="parcel_details"
            class="input textarea"
            required=""
          ></textarea>
          <span>Parcel Details</span>
        </label>
       
        <button type="submit" class="button-submit">Get Quote Now</button>
        <!-- 		<p class="p line">Or With</p> -->

        <div class="flex-row">
          <a href="tel:+919900059918" class="btnn google">
            <img
              src="https://intelizenlogistics.com/wp-content/uploads/2025/04/call-icon-2.svg"
              alt="Call Icon"
              style="
                width: 18px;
                height: 18px;
                vertical-align: middle;
                margin-bottom: 1px;
              "
            />
            Call Now
          </a>
          <a href="https://wa.me/9900059918" class="btnn apple">
            <img
              src="https://intelizenlogistics.com/wp-content/uploads/2025/04/whatsapp-icon.svg"
              alt="Whatsapp Icon"
              style="
                width: 20px;
                height: 20px;
                vertical-align: middle;
                margin-bottom: 1px;
              "
            />
            Whatsapp
          </a>
        </div>
      </form>
      <form
        id="bike-moving-form"
        class="form"
        method="POST"
      >
        <input type="hidden" name="action" value="basic-bike-form" />
         <p class="message">Fast & Easy shipping for Bikes, Luggage, Household goods</p>

        <div class="flex">
          <!-- First Dropdown (From Location) -->
          <label>
            <div class="select-menu">
              <div class="select-btn">
                <span class="sBtn-text">Select From</span>
                <input
                  type="hidden"
                  name="from_location"
                  id="from_location"
                  required=""
                />
                <svg
                  width="14"
                  height="15"
                  viewBox="0 0 14 15"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    d="M3.5 5.75L7 9.25L10.5 5.75"
                    stroke="#131316"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  ></path>
                </svg>
              </div>
             <ul class="options">
               <li class="option" data-value="Hyderabad">
               <span class="option-text">Hyderabad</span>
             </li>
             <li class="option" data-value="Bangalore">
             <span class="option-text">Bangalore</span>
             </li>
             <li class="option" data-value="Pune">
             <span class="option-text">Pune</span>
             </li>
             <li class="option" data-value="Vizag">
             <span class="option-text">Vizag</span>
             </li>
             <li class="option" data-value="Tirupati">
             <span class="option-text">Tirupati</span>
             </li>
             <li class="option" data-value="Chennai">
             <span class="option-text">Chennai</span>
             </li>
             <li class="option" data-value="Delhi">
             <span class="option-text">Delhi</span>
             </li>
             <li class="option" data-value="Vijayawada">
             <span class="option-text">Vijayawada</span>
             </li>
             </ul>

            </div>
          </label>

          <!-- Second Dropdown (To Location) -->
          <label>
            <div class="select-menu">
              <div class="select-btn">
                <span class="sBtn-text">Select To</span>
                <input
                  type="hidden"
                  name="to_location"
                  id="to_location"
                  required=""
                />
                <svg
                  width="14"
                  height="15"
                  viewBox="0 0 14 15"
                  fill="none"
                  xmlns="http://www.w3.org/2000/svg"
                >
                  <path
                    d="M3.5 5.75L7 9.25L10.5 5.75"
                    stroke="#131316"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                  ></path>
                </svg>
              </div>
              <ul class="options">
                  <li class="option" data-value="Hyderabad">
                  <span class="option-text">Hyderabad</span>
                  </li>
                 <li class="option" data-value="Bangalore">
                 <span class="option-text">Bangalore</span>
                 </li>
                 <li class="option" data-value="Pune">
                 <span class="option-text">Pune</span>
                 </li>
                 <li class="option" data-value="Vizag">
                <span class="option-text">Vizag</span>
                </li>
                <li class="option" data-value="Tirupati">
               <span class="option-text">Tirupati</span>
               </li>
               <li class="option" data-value="Chennai">
               <span class="option-text">Chennai</span>
               </li>
               <li class="option" data-value="Delhi">
               <span class="option-text">Delhi</span>
               </li>
              <li class="option" data-value="Vijayawada">
              <span class="option-text">Vijayawada</span>
               </li>
               </ul>

            </div>
          </label>
        </div>
 
        <div class="flex"> 
	 <label>
          <input
            required=""
            name="name"
            placeholder=""
            type="text"
            class="input"
          />
          <span>Name</span>
        </label>
	   <label>
		   <input
				  required=""
				  name="date"
				  placeholder=""
				  type="date"
				  class="input datepicker"
				  /> 
	</label>
	   </div>
        <div class="flex">
          <label>
            <input
              required=""
              name="phone"
              placeholder=""
              type="tel"
              class="input"
            />
            <span>Phone</span>
          </label>
          <label>
            <input
              required=""
              name="email"
              placeholder=""
              type="email"
              class="input"
            />
            <span>Email</span>
          </label>
        </div>
        <label>
          <textarea
            name="parcel_details"
            class="input textarea"
            required=""
          ></textarea>
          <span>Parcel Details</span>
        </label>
      
        <button type="submit" class="button-submit">Get Quote Now</button>
        <!-- 		<p class="p line">Or With</p> -->

        <div class="flex-row">
          <a href="tel:+919900059918" class="btnn google">
            <img
              src="https://intelizenlogistics.com/wp-content/uploads/2025/04/call-icon-2.svg"
              alt="Call Icon"
              style="
                width: 18px;
                height: 18px;
                vertical-align: middle;
                margin-bottom: 1px;
              "
            />
            Call Now
          </a>
          <a href="https://wa.me/9900059918" class="btnn apple">
            <img
              src="https://intelizenlogistics.com/wp-content/uploads/2025/04/whatsapp-icon.svg"
              alt="Whatsapp Icon"
              style="
                width: 20px;
                height: 20px;
                vertical-align: middle;
                margin-bottom: 1px;
              "
            />
            Whatsapp
          </a>
        </div>
      </form>
		   <section class="success___box">
      <div>
        <h3 style="font-weight:700;">Thank you .</h3>
        <p>
          We have received your booking for transport services . Our
          representative will call you in next few hours with further details .
        </p>
        <li>For any queries :</li>
        <div class="here___flex__box">
          <button class="btn__action">call now</button>
          <button class="btn__action">whatsapp</button>
        </div>
      </div>
    </section>
 <section class="step_two_overlay" id="steptwooverlay" style="display: none">
  <div class="modal-content">
    <div
      style="
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
      "
    >
      <p style="margin:0;">
        <strong>FROM</strong><br />
        <span id="step2-from">
          <span id="step2-from-text"></span>
        </span>
      </p>
      <p style="margin:0;">
        <strong>TO</strong><br />
        <span id="step2-to"></span>
      </p>
      <p style="margin:0;">
        <strong>DATE</strong><br />
        <span id="step2-date"></span>
      </p>
    </div>
    <p style="font-size: 12px; margin: 5px 0;" onclick="closeStepTwoOverlay()">To change details Click here</p>
    <form id="freight-form">
      <!-- Delivery Speed -->
      <div style="margin-bottom: 10px" class="delivery__section">
        <p><strong>Delivery Speed</strong></p>
        <div class="toggle-button-cover">
          <div id="button-3" class="deliverybutton r">
            <input
              class="checkbox"
              type="checkbox"
              id="speed-toggle"
              name="speed_toggle"
              value="standard"
            />
            <div class="knobs"></div>
            <div class="layer"></div>
          </div>
        </div>

        <!-- Hidden input to hold actual speed value for price calc -->
        <input type="hidden" name="speed" id="hidden-speed" value="express" />
      </div>

      <!-- Pickup Type -->
      <div style="margin-bottom: 10px" class="pickup__section">
        <div class="for__labelwith__toggle">
          <div>
            <p><strong>Pickup Type</strong></p>
          </div>
          <div>
            <div class="toggle-button-coverpickup">
              <div id="button-3pickup" class="deliverybuttonpickup r">
                <!-- 👇 Add name="speed_toggle" and value="standard" (default) -->
                <input
                  class="checkboxpickup"
                  type="checkbox"
                  id="pickup-toggle"
                  name="pickup_toggle"
                  value="hub"
                />
                <div class="knobspickup"></div>
                <div class="layerpickup"></div>
              </div>
            </div>

            <!-- Hidden input to hold actual speed value for price calc -->
            <input type="hidden" name="pickup" id="hidden-pickup" value="hub" />
          </div>
        </div>
        <div class="for__pickupaddress_for__pickuptype">
          <div id="pickup-address" style="display: none; margin-top: 10px">
            <label>
              Pickup Address:<br />
              <input
                type="text"
                class="full__address__inputs"
                name="pickup_address"
                style="width: 100%; padding: 5px"
              />
            </label>
          </div>
        </div>
      </div>

      <!-- Delivery Type -->
      <div style="margin-bottom: 10px" class="pickup__section">
        <div class="for__delivery_labl__with__toggle">
          <div>
            <p><strong>Delivery Type</strong></p>
          </div>
          <div>
            <div class="toggle-button-coverdelivery">
              <div id="button-3delivery" class="deliverybuttondelivery r">
                <input
                  class="checkboxdelivery"
                  type="checkbox"
                  id="delivery-toggle"
                  name="delivery_toggle"
                  value="hub"
                />
                <div class="knobsdelivery"></div>
                <div class="layerdelivery"></div>
              </div>
            </div>
            <input
              type="hidden"
              name="delivery"
              id="hidden-delivery"
              value="hub"
            />
            <!-- Hidden input to hold actual speed value for price calc -->
          </div>
        </div>
        <div class="for__door__delivery_address_form">
          <!-- Pickup address input (already in your code) -->
          <div id="delivery-address" style="display: none; margin-top: 10px">
            <label>
              Pickup Address:<br />
              <input
                type="text"
                name="delivery_address"
                class="full__address__inputs"
                style="width: 100%; padding: 5px"
              />
            </label>
          </div>
        </div>
      </div>

      <!-- Insurance -->
      <div style="margin-bottom: 20px" class="insurance__section">
        <p>
          <strong>Damage Insurance Scheme</strong>
          <a href="#" style="color: #e74c3c">(Learn More)</a>
        </p>

        <input type="hidden" name="insurance" value="opt_out" />
        <!-- This hidden input sets default value to "opt_out" -->

        <div class="toggler">
          <input
            id="toggler-1"
            name="toggle-insurance"
            type="checkbox"
            value="1"
            onchange="updateInsuranceValue(this)"
          />
          <label for="toggler-1">
            <svg
              class="toggler-on"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 130.2 130.2"
            >
              <polyline
                class="path check"
                points="100.2,40.2 51.5,88.8 29.8,67.5"
              ></polyline>
            </svg>
            <svg
              class="toggler-off"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 130.2 130.2"
            >
              <line
                class="path line"
                x1="34.4"
                y1="34.4"
                x2="95.8"
                y2="95.8"
              ></line>
              <line
                class="path line"
                x1="95.8"
                y1="34.4"
                x2="34.4"
                y2="95.8"
              ></line>
            </svg>
          </label>
        </div>
      </div>
      <!-- Charges Summary -->
      <!-- Charges Summary -->
      <div
        style="background: #f5f6f7; border-radius: 10px;"
        id="charges-summary"
      >
        <div class="hide__chargesrow">
          <h2 style="color: #e74c3c" class="fr__heading fr_heading_heading">Freight charges</h2>
          <p class="fr__heading fr__heading_para"><em>(Inclusive of all taxes with GST)</em></p>
          <div class="charge-row">
            <p>Bike Transportation</p>
            <p class="price">₹ 2650.00</p>
          </div>
          <div class="charge-row">
            <p>Fastrack Delivery Charges</p>
            <p class="price">₹ 0.00</p>
          </div>
          <div class="charge-row">
            <p>Pickup Charges</p>
            <p class="price">₹ 0.00</p>
          </div>
          <div class="charge-row">
            <p>Door Delivery Charges</p>
            <p class="price">₹ 0.00</p>
          </div>
          <div class="charge-row">
            <p>Bike Insurance Charges</p>
            <p class="price">₹ 0.00</p>
          </div>
          <div class="charge-row">
            <p>Total TAX (GST)</p>
            <p class="price">₹ 0.00</p>
          </div>
        </div>

        <div class="charge-row grand-total">
          <p style="margin: 0"><strong>GRAND TOTAL</strong></p>
          <p class="price" style="font-size: 18px; margin: 0">
            <strong>₹ 0.00</strong>
          </p>
        </div>
      </div>

      <!-- Button -->
      <div style="text-align: center; margin-top: 20px">
        <button type="button" class="after__calculations____review">
              Confirm Booking
             </button>

      </div>
    </form>
    <span class="step_two_close"
      ><svg
        xmlns="http://www.w3.org/2000/svg"
        width="16"
        height="16"
        fill="none"
        viewBox="0 0 16 16"
      >
        <path stroke="currentColor" d="m3 3 10 10M13 3 3 13" /></svg
    ></span>
  </div>
</section>

		 <div id="moving-success" style="display: none;">
  🎉 Your request was submitted successfully!
</div>
		
</section>

   
      
<?php
    return ob_get_clean();
}
add_shortcode('hero_wp_form', 'hero_form');

function loadform_Script() {
    ?>
    <script>	
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.getElementById("moving-service-form");
            
            if (form) {
                form.addEventListener("submit", function(e) {
                    e.preventDefault();
                    
                    // Show loading state
                    const submitButton = form.querySelector('button[type="submit"]');
                    const originalText = submitButton.textContent;
                    submitButton.textContent = 'Processing...';
                    submitButton.disabled = true;
                    
                    // Get form data
                    const formData = new FormData(form);
                    
                    // Add AJAX security nonce
                    formData.append('security', '<?php echo wp_create_nonce("moving_service_nonce"); ?>');
                    
                    // Make AJAX request
                    fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log("API Response:", data);
                        if (data.success) {
                            document.getElementById("moving-success").style.display = "block";
							document.getElementById("moving-service-form").style.display = "none";
							document.querySelector(".tabswitcher").style.display = "none";
                            document.querySelector(".success___box").classList.add("active");				
                            document.querySelectorAll("input, textarea, select").forEach(el => el.value = "");

                        } else {
                            alert("Error: " + data.data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('There was an error. Please try again.');
                    })
                    .finally(() => {
                        submitButton.textContent = originalText;
                        submitButton.disabled = false;
                    });
                });
            }
          
        });

        


       

     
    </script>
    <?php
}
add_action('wp_footer', 'loadform_Script');

//for bike moving form
// AJAX handler for form submission
add_action('wp_ajax_bike-moving-form', 'handle_bike_moving_form');
add_action('wp_ajax_nopriv_bike-moving-form', 'handle_bike_moving_form');

//for bike moving basic details 
add_action('wp_ajax_basic-bike-form', 'handle_basic_bike_form');
add_action('wp_ajax_nopriv_basic-bike-form', 'handle_basic_bike_form');


function handle_basic_bike_form() {
    check_ajax_referer('bike_moving_nonce', 'security');
    global $wpdb;

    $table = 'bikebasicdetails';

    $name  = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone']);
    $from  = sanitize_text_field($_POST['from_location']);
    $to    = sanitize_text_field($_POST['to_location']);
    $date  = sanitize_text_field($_POST['date']);

    $inserted = $wpdb->insert($table, [
        'from_location' => $from,
        'to_location'   => $to,
        'pickup_date'   => $date,
        'sender_name'   => $name,
        'phone'         => $phone,
        'email'         => $email,
        'created_at'    => current_time('mysql')
    ]);

    if (!$inserted) {
        wp_send_json_error(['message' => 'Failed to save basic booking.', 'sql_error' => $wpdb->last_error]);
    }
	
	 // Admin email only - Basic Booking Notification

$admin_subject = 'New Entry Bike Booking - ' . date('Y-m-d H:i');
$admin_message = '
<html>
<head>
  <style>
     @import url("https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap");

       body, .container {
  font-family: "DM Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

    body {
      background: #f4f4f4;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 600px;
      margin: 30px auto;
      background: #ffffff;
      border-radius: 12px;
      overflow: hidden;
      border: 1px solid rgba(61, 36, 144, 0.1);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    }
    .header {
      background: linear-gradient(90deg, #7c3aed, #9333ea);
      color: #fff;
      padding: 20px;
      text-align: center;
      font-size: 22px;
    }
    .content {
      padding: 30px 25px;
      color: #333;
    }
    .details {
      background: #f9f9ff;
      padding: 20px;
      border-radius: 10px;
      margin-top: 15px;
      margin-bottom: 10px;
    }
    .details div {
      margin-bottom: 12px;
    }
    .label {
      font-weight: 600;
      color: #555;
    }
    .value {
      margin-left: 5px;
      color: #222;
    }
    .footer {
      text-align: center;
      font-size: 13px;
      color: #aaa;
      padding: 20px;
    }
	  .email_full__button {
        background: #7612fa;
        color: #ffffff;
        font-size: 14px;
        font-weight: 700;
        line-height: 20px;
        letter-spacing: 0.3px;
        margin: 0;
        text-decoration: none;
        text-transform: none;
        padding: 15px 10px 14px 10px;
        display: block;
        border-radius: 12px;
        border: 1px solid #dadada;
        width: 100%;
        height: 55px;
        font-family: "DM Sans", -apple-system, BlinkMacSystemFont, "Segoe UI",
          Roboto, Helvetica, Arial, sans-serif;
      }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">🚴‍♂️ New Basic Bike Booking</div>
    <div class="content">
      <p>Hi Admin,</p>
      <p>New basic bike booking request has been received:</p>
      <div class="details">
        <div><span class="label">From:</span><span class="value">' . esc_html($from) . '</span></div>
        <div><span class="label">To:</span><span class="value">' . esc_html($to) . '</span></div>
        <div><span class="label">Date:</span><span class="value">' . esc_html($date) . '</span></div>
        <div><span class="label">Name:</span><span class="value">' . esc_html($name) . '</span></div>
        <div><span class="label">Phone:</span><span class="value">' . esc_html($phone) . '</span></div>
        <div><span class="label">Email:</span><span class="value">' . esc_html($email) . '</span></div>
		<button class="email_full__button">Call Now</button>
      </div>
    </div>
    <div class="footer">
      © ' . date('Y') . ' Move My Bike | Admin Notification Only
    </div>
  </div>
</body>
</html>';
           
	  add_filter('wp_mail_from', function() {
    return 'support@intelizenlogistics.com';
});

add_filter('wp_mail_from_name', function() {
    return 'Intelizenlogistics';
});

$headers_admin = [
    'Content-Type: text/html; charset=UTF-8'
];

$admins = ['ganesh@advaitlabs.com', 'support@intelizenlogistics.com'];

foreach ($admins as $admin) {
    wp_mail($admin, $admin_subject, $admin_message, $headers_admin);
}




    wp_send_json_success([
        'message' => 'Basic booking saved!',
        'booking_id' => $wpdb->insert_id,
        'submitted_data' => [
            'from_location' => $from,
            'to_location'   => $to,
            'pickup_date'   => $date,
            'sender_name'   => $name,
            'phone'         => $phone,
            'email'         => $email
        ]
    ]);

    wp_die();
}




//for full details booking request
function handle_bike_moving_form() {
    check_ajax_referer('bike_moving_nonce', 'security');

    global $wpdb;
    $table = 'biketransportbooking';

    // Step 1: User Inputs
    $name    = sanitize_text_field($_POST['name']);
    $email   = sanitize_email($_POST['email']);
    $phone   = sanitize_text_field($_POST['phone']);
    $from    = sanitize_text_field($_POST['from_location']);
    $to      = sanitize_text_field($_POST['to_location']);
    $date    = sanitize_text_field($_POST['date']);
    $parcel  = sanitize_textarea_field($_POST['parcel_details']);

    // Step 2: Charges
    $base_price       = floatval($_POST['base_price'] ?? 0);
    $speed_type       = sanitize_text_field($_POST['speed_type'] ?? '');
    $speed_charge     = floatval($_POST['speed_charge'] ?? 0);
    $pickup_type      = sanitize_text_field($_POST['pickup_type'] ?? '');
    $pickup_charge    = floatval($_POST['pickup_charge'] ?? 0);
    $delivery_type    = sanitize_text_field($_POST['delivery_type'] ?? '');
    $delivery_charge  = floatval($_POST['delivery_charge'] ?? 0);
    $insurance_type   = sanitize_text_field($_POST['insurance_type'] ?? '');
    $insurance_charge = floatval($_POST['insurance_charge'] ?? 0);
    $tax              = floatval($_POST['tax'] ?? 0);
    $total_price      = floatval($_POST['total_price'] ?? 0);

    // Insert ALL fields in one go
    $inserted = $wpdb->insert($table, [
        'from_location'     => $from,
        'to_location'       => $to,
        'pickup_date'       => $date,
        'sender_name'       => $name,
        'phone'             => $phone,
        'email'             => $email,
        'parcel_details'    => $parcel,

        'base_price'        => $base_price,
        'speed_type'        => $speed_type,
        'speed_charge'      => $speed_charge,
        'pickup_type'       => $pickup_type,
        'pickup_charge'     => $pickup_charge,
        'delivery_type'     => $delivery_type,
        'delivery_charge'   => $delivery_charge,
        'insurance_type'    => $insurance_type,
        'insurance_charge'  => $insurance_charge,
        'tax'               => $tax,
        'total_price'       => $total_price,
        'created_at'        => current_time('mysql')
    ]);

    if (!$inserted) {
         wp_send_json_error([
        'message' => 'Booking failed to save.',
        'sql_error' => $wpdb->last_error // 🔍 helpful debug
    ]);
    }

    $admin_message = '
<html>
<head>
  <style>
     @import url("https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap");

     body, .container {
  font-family: "DM Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}

    .container { background: #fff; padding: 20px; border-radius: 8px; max-width: 600px; margin: auto; }
    .header { background: #6d28d9; color: #fff; padding: 15px 20px; border-radius: 8px 8px 0 0; font-size: 20px; }
    .section { padding: 10px 0; }
    .label { font-weight: bold; }
    .footer { font-size: 12px; color: #888; margin-top: 30px; text-align: center; }
    .button {
      display: inline-block;
      background: #7e22ce;
      color: #fff;
      padding: 10px 18px;
      border-radius: 5px;
      text-decoration: none;
      margin-top: 15px;
    }
	  .email_full__button {
        background: #7612fa;
        color: #ffffff;
        font-size: 14px;
        font-weight: 700;
        line-height: 20px;
        letter-spacing: 0.3px;
        margin: 0;
        text-decoration: none;
        text-transform: none;
        padding: 15px 10px 14px 10px;
        display: block;
        border-radius: 12px;
        border: 1px solid #dadada;
        width: 100%;
        height: 55px;
        font-family: "DM Sans", -apple-system, BlinkMacSystemFont, "Segoe UI",
          Roboto, Helvetica, Arial, sans-serif;
      }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">🚚 New Bike Booking Received</div>

    <div class="section"><span class="label">From:</span> ' . esc_html($from) . '</div>
    <div class="section"><span class="label">To:</span> ' . esc_html($to) . '</div>
    <div class="section"><span class="label">Pickup Date:</span> ' . esc_html($date) . '</div>
    <div class="section"><span class="label">Name:</span> ' . esc_html($name) . '</div>
    <div class="section"><span class="label">Phone:</span> ' . esc_html($phone) . '</div>
    <div class="section"><span class="label">Email:</span> ' . esc_html($email) . '</div>
    <div class="section"><span class="label">Parcel Details:</span> ' . nl2br(esc_html($parcel)) . '</div>

    <div class="section"><span class="label">Base Price:</span> ₹' . number_format($base_price, 2) . '</div>
    <div class="section"><span class="label">Speed Type:</span> ' . esc_html($speed_type) . ' (₹' . number_format($speed_charge, 2) . ')</div>
    <div class="section"><span class="label">Pickup Type:</span> ' . esc_html($pickup_type) . ' (₹' . number_format($pickup_charge, 2) . ')</div>
    <div class="section"><span class="label">Delivery Type:</span> ' . esc_html($delivery_type) . ' (₹' . number_format($delivery_charge, 2) . ')</div>
    <div class="section"><span class="label">Insurance:</span> ' . esc_html($insurance_type) . ' (₹' . number_format($insurance_charge, 2) . ')</div>
    <div class="section"><span class="label">Tax:</span> ₹' . number_format($tax, 2) . '</div>
    <div class="section"><span class="label">Total:</span> <strong>₹' . number_format($total_price, 2) . '</strong></div>

    <div class="section">
       <button class="email_full__button">Thank you</button>
    </div>

    <div class="footer">This is an automated email from Move My Bike | © 2025</div>
  </div>
</body>
</html>
';


	     // Set global "From" email and name for all wp_mail() calls
add_filter('wp_mail_from', function() {
    return 'no-reply@intelizenlogistics.com';
});

add_filter('wp_mail_from_name', function() {
    return 'Intelizenlogistics';
});

$headers_admin = [
    'Content-Type: text/html; charset=UTF-8'
];

$date_time = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
$time = $date_time->format('g:i A');  // e.g., 1:45 PM

$admin_subject = "📦 New Bike Booking Request - $name from $from to $to on $date at $time";
$internal_recipients = ['ganesh@advaitlabs.com', 'support@intelizenlogistics.com'];

foreach ($internal_recipients as $recipient) {
    wp_mail($recipient, $admin_subject, $admin_message, $headers_admin);
}


    
   $name  = sanitize_text_field($_POST['name'] ?? 'Customer');
$email = sanitize_email($_POST['email'] ?? '');
$from  = sanitize_text_field($_POST['from_location'] ?? '');
$to    = sanitize_text_field($_POST['to_location'] ?? '');
$date  = sanitize_text_field($_POST['date'] ?? date('Y-m-d'));

$userdate_time = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
$usertime = $userdate_time->format('g:i A');  // e.g., 1:45 PM

$user_subject = "✅ {$name}, your bike booking from {$from} to {$to} on {$date} is confirmed! $usertime";
$user_message = '
<html>
  <head>
    <style>
      @import url("https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap");

      body, .container {
  font-family: "DM Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
}


      body {
        background: #f4f4f4;
        margin: 0;
        padding: 0;
      }
      .container {
        max-width: 600px;
        margin: 30px auto;
        background: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid rgba(61, 36, 144, 0.1);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
      }
      .header {
        background: linear-gradient(90deg, #7c3aed, #9333ea);
        color: #fff;
        padding: 20px;
        text-align: center;
        font-size: 22px;
      }
      .content {
        padding: 30px 25px;
        color: #333;
      }
      .content p {
        margin-bottom: 20px;
        line-height: 1.6;
      }
      .details {
        background: #f9f9ff;
        padding: 20px;
        border-radius: 10px;
        margin-top: 15px;
        margin-bottom: 10px;
      }
      .details div {
        margin-bottom: 15px;
      }
      .label {
        font-weight: 600;
        color: #555;
      }
      .value {
        margin-left: 5px;
        color: #222;
      }
      .footer {
        text-align: center;
        font-size: 13px;
        color: #aaa;
        padding: 20px;
      }
      .email_full__button {
        background: #7612fa;
        color: #ffffff;
        font-size: 14px;
        font-weight: 700;
        line-height: 20px;
        letter-spacing: 0.3px;
        margin: 0;
        text-decoration: none;
        text-transform: none;
        padding: 15px 10px 14px 10px;
        display: block;
        border-radius: 12px;
        border: 1px solid #dadada;
        width: 100%;
        height: 55px;
        font-family: "DM Sans", -apple-system, BlinkMacSystemFont, "Segoe UI",
          Roboto, Helvetica, Arial, sans-serif;
      }
    </style>
  </head>
  <body>
    <div class="container">
      <div class="header">🚚 Booking Confirmed!</div>
      <div class="content">
        <p>Hi <strong>' . esc_html($name) . '</strong>,</p>

        <p>
          Thank you for booking your bike transport with
          <strong>Move My Bike</strong>. Your pickup has been scheduled for
          <strong>' . esc_html($date) . '</strong> from
          <strong>' . esc_html($from) . '</strong> to
          <strong>' . esc_html($to) . '</strong>.
        </p>

        <p>Here are your booking details:</p>

        <div class="details">
          <div>
            <span class="label">Name:</span
            ><span class="value">' . esc_html($name) . '</span>
          </div>
          <div>
            <span class="label">Phone:</span
            ><span class="value">' . esc_html($phone) . '</span>
          </div>
          <div>
            <span class="label">Email:</span
            ><span class="value">' . esc_html($email) . '</span>
          </div>
          <div>
            <span class="label">Pickup Date:</span
            ><span class="value">' . esc_html($date) . '</span>
          </div>
          <div>
            <span class="label">From:</span
            ><span class="value">' . esc_html($from) . '</span>
          </div>
          <div>
            <span class="label">To:</span
            ><span class="value">' . esc_html($to) . '</span>
          </div>
          <div>
            <span class="label">Parcel Details:</span
            ><span class="value">' . nl2br(esc_html($parcel)) . '</span>
          </div>
          <div>
            <span class="label">Speed Type:</span
            ><span class="value">' . esc_html($speed_type) . '</span>
          </div>
          <div>
            <span class="label">Pickup:</span
            ><span class="value"
              >' . esc_html($pickup_type) . ' (₹' .
              number_format($pickup_charge, 2) . ')</span
            >
          </div>
          <div>
            <span class="label">Delivery:</span
            ><span class="value"
              >' . esc_html($delivery_type) . ' (₹' .
              number_format($delivery_charge, 2) . ')</span
            >
          </div>
          <div>
            <span class="label">Insurance:</span
            ><span class="value"
              >' . esc_html($insurance_type) . ' (₹' .
              number_format($insurance_charge, 2) . ')</span
            >
          </div>
          <div>
            <span class="label">Base Price:</span
            ><span class="value">₹' . number_format($base_price, 2) . '</span>
          </div>
          <div>
            <span class="label">Tax (18%):</span
            ><span class="value">₹' . number_format($tax, 2) . '</span>
          </div>
          <div>
            <span class="label"><strong>Total:</strong></span
            ><span class="value"
              ><strong>₹' . number_format($total_price, 2) . '</strong></span
            >
          </div>
          <button class="email_full__button">Thank you</button>
        </div>

        <p>
          If any detail looks incorrect, please reply to this email immediately.
        </p>
      </div>
      <div class="footer">
        © ' . date('Y') . ' Move My Bike | This is an automated message. Do not
        reply.
      </div>
    </div>
  </body>
</html>

';
	 $headers_foruser = [
             'Content-Type: text/html; charset=UTF-8',
             'From: Intelizenlogistics <no-reply@intelizenlogistics.com>' // Set the From address here
            ];
     wp_mail($email, $user_subject, $user_message, $headers_foruser);
    // Prepare response data
    $bookingdata = [
        'from_location'   => $from,
        'to_location'     => $to,
        'pickup_date'     => $date,
        'sender_name'     => $name,
        'phone'           => $phone,
        'email'           => $email,
        'parcel_details'  => $parcel,
        'base_price'      => $base_price,
        'speed_type'      => $speed_type,
        'speed_charge'    => $speed_charge,
        'pickup_type'     => $pickup_type,
        'pickup_charge'   => $pickup_charge,
        'delivery_type'   => $delivery_type,
        'delivery_charge' => $delivery_charge,
        'insurance_type'  => $insurance_type,
        'insurance_charge'=> $insurance_charge,
        'tax'             => $tax,
        'total_price'     => $total_price
    ];

    // ✅ Send one response only
    wp_send_json_success([
        'message' => 'Booking saved with full data!',
        'submitted_data' => $bookingdata,
        'booking_id' => $wpdb->insert_id
    ]);

    wp_die();
}


function enqueue_bike_move_form_script() {
    wp_enqueue_script(
        'bike-moving-ajax',
        get_stylesheet_directory_uri() . '/bike-move.js',
		 array('global_scripts'),
        time(), // Prevent caching
        true
    );

    wp_localize_script('bike-moving-ajax', 'bikeMoveData', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('bike_moving_nonce'),
    ]);
}
add_action('wp_enqueue_scripts', 'enqueue_bike_move_form_script');


//function for shortcode services
function create_moving_service_table_if_needed() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'moving_service';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            from_location VARCHAR(255) NOT NULL,
            to_location VARCHAR(255) NOT NULL,
            parcel_details TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'create_moving_service_table_if_needed');

function servicesList_shortcode() {
	ob_start();

	$args = array(
		'post_type'      => 'rt-service',
		'posts_per_page' => 6,
		'post_status'    => 'publish',
	);

	$query = new WP_Query($args);

	if ($query->have_posts()) {
		echo '<div class="services-list-grid row g-4">';
		while ($query->have_posts()) {
			$query->the_post();

			$id = get_the_ID();
			$content = get_the_excerpt();
			$content = wp_trim_words( $content, kariez_option( 'rt_service_excerpt_limit' ), '' );

			$rt_service_icon = get_post_meta( $id, 'rt_service_icon', true );
			$service_icon_bg = get_post_meta( $id, 'rt_service_color', true );
			$service_bg      = !empty( $service_icon_bg ) ? 'style="color: ' . $service_icon_bg . '"' : '';

			$icon_class = !empty( $rt_service_icon ) ? 'service-item-icon' : 'service-item-image';
			?>

			<div class="col-lg-4 col-md-6">
				<article id="post-<?php the_ID(); ?>" class="service-card">
					<div class="service-item <?php echo esc_attr( $icon_class ); ?>">
						<div class="service-thumbs">
							<?php if (!empty( $rt_service_icon )) { ?>
								<div class="service-icon">
									<i <?php echo wp_specialchars_decode( esc_attr( $service_bg ), ENT_COMPAT ); ?>
										class="<?php kariez_html( $rt_service_icon , false ); ?>">
									</i>
								</div>
							<?php } else {
								kariez_post_thumbnail('kariez-size3');
							} ?>
						</div>
						<div class="service-content">
							<h2 class="service-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
							<?php if ( kariez_option( 'rt_service_ar_excerpt' ) ) { ?>
								<p><?php kariez_html( $content , false ); ?></p>
							<?php } ?>
							<?php if ( kariez_option( 'rt_service_read_more' ) ) { ?>
								<div class="rt-button">
									<a class="btn button-4" href="<?php the_permalink(); ?>">
										<span class="button-text"><?php esc_html_e('See Details', 'kariez'); ?></span>
										<span class="btn-round-shape"><i class="icon-arrow-right"></i></span>
									</a>
								</div>
							<?php } ?>
						</div>
					</div>
				</article>
			</div>

			<?php
		}
		echo '</div>';
		wp_reset_postdata();
	} else {
		echo '<p>No services found.</p>';
	}

	return ob_get_clean();
}
add_shortcode('services_list', 'servicesList_shortcode');


require_once get_stylesheet_directory() . '/bikebookingpage.php';
require_once get_stylesheet_directory() . '/truckloadanimation.php';




