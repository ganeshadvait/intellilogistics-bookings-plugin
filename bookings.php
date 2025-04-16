<?php
add_action( 'wp_enqueue_scripts', 'kariez_child_styles', 18 );
function kariez_child_styles() {
	wp_enqueue_style( 'child-style', get_stylesheet_uri() );
}
function enqueue_global_styles() {
    $stylesheet_uri = get_stylesheet_directory_uri() . '/style.css';
    $stylesheet_version = filemtime(get_stylesheet_directory() . '/style.css'); // Use filemtime for versioning
    wp_enqueue_style( 'global_styles', $stylesheet_uri, array(), $stylesheet_version );
}
add_action( 'wp_enqueue_scripts', 'enqueue_global_styles', 20 );

function getin_touch_popup_scripts() {
    wp_enqueue_script('get-in-touch-popup-js', get_stylesheet_directory_uri() . '/script.js', ['jquery'], null, true);
}
add_action('wp_enqueue_scripts', 'getin_touch_popup_scripts');
function Load_chat() {
    echo '
    <script>
        var url = "https://wati-integration-prod-service.clare.ai/v2/watiWidget.js?33987";
        var s = document.createElement("script");
        s.type = "text/javascript";
        s.async = true;
        s.src = url;
        
        var options = {
            "enabled": true,
            "chatButtonSetting": {
                "backgroundColor": "#00e785",
                "ctaText": "Chat with us",
                "borderRadius": "25",
                "marginLeft": "40",
                "marginRight": "20",
                "marginBottom": "20",
                "ctaIconWATI": false,
                "position": "left"
            },
            "brandSetting": {
                "brandName": "Intellilogistics",
                "brandSubTitle": "undefined",
                "brandImg": "https://www.wati.io/wp-content/uploads/2023/04/Wati-logo.svg",
                "welcomeText": "Hi there!\\nHow can I help you?",
                "messageText": "Hello, %0A I have a question about {{page_link}}",
                "backgroundColor": "#00e785",
                "ctaText": "Chat with us",
                "borderRadius": "25",
                "autoShow": false,
                "phoneNumber": "918179208586"
            }
        };

        s.onload = function() {
            CreateWhatsappChatWidget(options);
        };
        
        document.getElementsByTagName("head")[0].appendChild(s);
    </script>';
}
add_action("wp_footer", "Load_chat");


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
    // Enable WordPress DB access
    global $wpdb;

    // Define table names
    $table_name = $wpdb->prefix . 'moving_service';
    $bikebooking_table_name = 'biketransportbooking';

    // Log the function start
    error_log('🚀 Form handler triggered');

    // Check if required POST fields exist
    if (
        empty($_POST['name']) ||
        empty($_POST['phone']) ||
        empty($_POST['from_location']) ||
        empty($_POST['to_location'])
    ) {
        error_log('❌ Missing required fields: ' . print_r($_POST, true));
        wp_die('Invalid form submission. Required fields are missing.');
    }

    // Sanitize inputs
    $name = sanitize_text_field($_POST['name']);
    $phone = sanitize_text_field($_POST['phone']);
    $from_location = sanitize_text_field($_POST['from_location']);
    $to_location = sanitize_text_field($_POST['to_location']);
    $for_bike = isset($_POST['bike-parcel']) && $_POST['bike-parcel'] === 'yes' ? 'yes' : 'no';
    $parcel_details = isset($_POST['parcel_details']) ? sanitize_textarea_field($_POST['parcel_details']) : '';

    // Log sanitized data
    error_log('✅ Sanitized data: ' . print_r([
        'name' => $name,
        'phone' => $phone,
        'from' => $from_location,
        'to' => $to_location,
        'parcel_details' => $parcel_details,
        'bike_parcel' => $for_bike,
    ], true));


    // Insert into first table
    $insert1 = $wpdb->insert($table_name, array(
        'name'     => $name,
        'phone'           => $phone,
        'from_location'   => $from_location,
        'to_location'     => $to_location,
        'parcel_details'  => $parcel_details,
        'bike_parcel'     => $for_bike
    ));

    if ($insert1 === false) {
        error_log('❌ Insert to moving_service table failed: ' . $wpdb->last_error);
    } else {
        error_log('✅ Inserted into moving_service');
    }

  $insert2 = $wpdb->insert($bikebooking_table_name, array(
    'sender_name'    => $name,        // Must match column name exactly
    'phone'         => $phone,
    'from_location' => $from_location,
    'to_location'   => $to_location,
    'email'         => '',            // Empty string is fine if column allows NULL
    'pickup_date'   => current_time('mysql') // This format is correct
));

if ($insert2 === false) {
    error_log('❌ Insert failed. Error: ' . $wpdb->last_error);
    error_log('❌ Failed query: ' . $wpdb->last_query);
} else {
    error_log('✅ Inserted into biketransportbooking. ID: ' . $wpdb->insert_id);
}

    // Send email (optional)
    $to = array('ganesh@advaitlabs.com', 'vamshi@advaitlabs.com');
    $subject = 'New Moving Service Request';
    $message = "
        <strong>Name:</strong> $name <br>
        <strong>Phone:</strong> $phone <br>
        <strong>From:</strong> $from_location <br>
        <strong>To:</strong> $to_location <br>
        <strong>Parcel Details:</strong> $parcel_details <br>
        <strong>This is For Bike:</strong> $for_bike
    ";
    $headers = array('Content-Type: text/html; charset=UTF-8', 'From: Your Website <noreply@yourwebsite.com>');

    wp_mail($to, $subject, $message, $headers);

    // Redirect back with success flag
    wp_redirect(add_query_arg('form_submitted', 'success', wp_get_referer()));
    exit;
}

// Hook for both logged-in and non-logged-in users
add_action('admin_post_nopriv_moving_service_form', 'handle_moving_service_form_submission');
add_action('admin_post_moving_service_form', 'handle_moving_service_form_submission');

// function handle_moving_service_form_submission() {
//     global $wpdb;
//     $table_name = $wpdb->prefix . 'wp_moving_service';

//     // Validate input
//     if (!isset($_POST['name'], $_POST['phone'], $_POST['from_location'], $_POST['to_location'])) {
//         wp_die('Invalid form submission.');
//     }

//     // Sanitize form data
//     $name = sanitize_text_field($_POST['name']);
//     $phone = sanitize_text_field($_POST['phone']);
//     $from_location = sanitize_text_field($_POST['from_location']);
//     $to_location = sanitize_text_field($_POST['to_location']);
// 	$for_bike = isset($_POST['bike-parcel']) && $_POST['bike-parcel'] === 'yes' ? 'yes' : 'no';
//     $parcel_details = isset($_POST['parcel_details']) ? sanitize_textarea_field($_POST['parcel_details']) : '';
    
//     // Ensure database table exists
//     create_moving_service_table_if_needed();

//     // Insert into database
//     $wpdb->insert($table_name, array(
//         'sender_name' => $name,
//         'phone' => $phone,
//         'from_location' => $from_location,
//         'to_location' => $to_location,
//         'parcel_details' => $parcel_details,
// 		'bike_parcel' => $for_bike
//     ));
	
	
//     // Prepare email details
// $to = array('ganesh@advaitlabs.com', 'vamshi@advaitlabs.com', 'swaroop@advaitlabs.com');
// $subject = 'New Moving Service Request';
// $message = "
// <strong>Name:</strong> $name <br>
// <strong>Phone:</strong> $phone <br>
// <strong>From:</strong> $from_location <br>
// <strong>To:</strong> $to_location <br>
// <strong>Parcel Details:</strong> $parcel_details <br>
// <strong>These is For Bike: </strong> $for_bike
// ";
// $headers = array('Content-Type: text/html; charset=UTF-8', 'From: Your Website <noreply@yourwebsite.com>');

// // Send the email
// wp_mail($to, $subject, $message, $headers);

//     // Redirect with success message
//     wp_redirect(add_query_arg('form_submitted', 'success', wp_get_referer()));
//     exit;
// }

// // Hook for form processing
// add_action('admin_post_nopriv_moving_service_form', 'handle_moving_service_form_submission');
// add_action('admin_post_moving_service_form', 'handle_moving_service_form_submission');

 
function hero_form() {
    ob_start();
    ?>

    <?php if (isset($_GET['form_submitted']) && $_GET['form_submitted'] === 'success') : ?>
        <p style="color: green;">Form submitted successfully!</p>
    <?php endif; ?>

     <style>
  
    .form {
  display: flex;
  flex-direction: column;
  gap: 10px;
 border-radius: 14px;
  background-color: #fff;
  padding: 20px;
  position: relative;
}

.title {
  font-size: 28px;
  color: #FFCC00;
  font-weight: 600;
  letter-spacing: -1px;
  position: relative;
  display: flex;
  align-items: center;
  padding-left: 30px;
  margin: 0;
}

.title::before,.title::after {
  position: absolute;
  content: "";
  height: 16px;
  width: 16px;
  border-radius: 50%;
  left: 0px;
  background-color: #FFCC00;
}

.title::before {
  width: 18px;
  height: 18px;
  background-color: #FFCC00;
}

.title::after {
  width: 18px;
  height: 18px;
  animation: pulse 1s linear infinite;
}
.message{
    margin: 0;
}
.message, .signin {
  color: rgba(88, 87, 87, 0.822);
  font-size: 14px;
}

.signin {
  text-align: center;
}

.signin a {
  color: royalblue;
}

.signin a:hover {
  text-decoration: underline royalblue;
}

.flex {
    display: flex
;
    width: 100%;
    gap: 6px;
    justify-content: space-between;
}

.form label {
  position: relative;
  width: 100%;
}

.form label .input {
  width: 100%;
  padding: 10px 10px 20px 10px;
  outline: 0;
  border: 1px solid #ededef;
  border-radius: 10px;
}

.form label .input + span {
  position: absolute;
  left: 10px;
  top: 15px;
  color: rgb(0, 0, 0);
  font-size: 0.9em;
  cursor: text;
  transition: 0.3s ease;
}
.flex-row {
  display: flex;
  flex-direction: row;
  align-items: center;
  gap: 10px;
  justify-content: space-between;
}
.form label .input:placeholder-shown + span {
  top: 15px;
  font-size: 0.9em;
}

.form label .input:focus + span,.form label .input:valid + span {
  top: 30px;
  font-size: 0.7em;
  font-weight: 600;
}



.submit {
  border: none;
  outline: none;
  background-color: royalblue;
  padding: 10px;
  border-radius: 10px;
  color: #fff;
  font-size: 16px;
  transform: .3s ease;
}

.submit:hover {
  background-color: rgb(56, 90, 194);
}

@keyframes pulse {
  from {
    transform: scale(0.9);
    opacity: 1;
  }

  to {
    transform: scale(1.8);
    opacity: 0;
  }
}




.span {
  font-size: 14px;
  margin-left: 5px;
  color: #2d79f3;
  font-weight: 500;
  cursor: pointer;
}

.button-submit {
  margin: 0;
  background-color: #151717;
  border: none;
  color: white;
  font-size: 15px;
  font-weight: 500;
  border-radius: 10px;
  height: 50px;
  width: 100%;
  cursor: pointer;
  z-index: 10;
}

.p {
  text-align: center;
  color: black;
  font-size: 14px;
  margin:0;
}

.btnn {
  margin-top: 0;
  width: 100%;
  height: 50px;
  border-radius: 10px;
  display: flex;
  justify-content: center;
  align-items: center;
  font-weight: 500;
  gap: 8px;
  border: 1px solid #ededef;
  background-color: white;
  cursor: pointer;
  transition: 0.2s ease-in-out;
  color: #000;
}

.btnn:hover {
  border: 1px solid #2d79f3;
  background: #fff;
  ;
}
.sBtn-text {
    color: #000 !important;
}

.select-menu .select-btn {
  display: flex;
  background: #fff;
  padding: 10px;
  font-size: 14px;
  font-weight: 400;
  border-radius: 8px;
  align-items: center;
  cursor: pointer;
  justify-content: space-between;
  border:  1px solid #ededef;
}
.select-menu .options {
  position: absolute;
  width: 100%;
  overflow-y: auto;
  max-height: 295px;
  padding: 10px;
  margin-top: 10px;
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
  animation-name: fadeInDown;
  -webkit-animation-name: fadeInDown;
  animation-duration: 0.35s;
  animation-fill-mode: both;
  -webkit-animation-duration: 0.35s;
  -webkit-animation-fill-mode: both;
}
.select-menu .options .option {
  display: flex;
  height: 55px;
  cursor: pointer;
  padding: 0 16px;
  border-radius: 8px;
  align-items: center;
  background: #fff;
}
.select-menu .options .option:hover {
  background: #f2f2f2;
}
.select-menu .options .option .option-text {
  font-size: 16px;
  color: #000;
}
.select-menu.active .options {
  display: block;
  opacity: 0;
  z-index: 20;
  animation-name: fadeInUp;
  -webkit-animation-name: fadeInUp;
  animation-duration: 0.4s;
  animation-fill-mode: both;
  -webkit-animation-duration: 0.4s;
  -webkit-animation-fill-mode: both;
}

@keyframes fadeInUp {
  from {
    transform: translate3d(0, 30px, 0);
  }
  to {
    transform: translate3d(0, 0, 0);
    opacity: 1;
  }
}
@keyframes fadeInDown {
  from {
    transform: translate3d(0, 0, 0);
    opacity: 1;
  }
  to {
    transform: translate3d(0, 20px, 0);
    opacity: 0;
  }
}

.form-container_hero{
    width: 100%;
   
}
  
@keyframes fadeInUp {
  from {
    transform: translate3d(0, 30px, 0);
  }
  to {
    transform: translate3d(0, 0, 0);
    opacity: 1;
  }
}
@keyframes fadeInDown {
  from {
    transform: translate3d(0, 0, 0);
    opacity: 1;
  }
  to {
    transform: translate3d(0, 20px, 0);
    opacity: 0;
  }
}

.form-container_hero {
  width: 100%;
}
.checkbox-wrapper {
  --checkbox-size: 25px;
  --checkbox-color: #ffcc00;
  --checkbox-shadow: rgba(208, 255, 0, 0.3);
  --checkbox-border: #ffcc00;
  display: flex;
  align-items: center;
  position: relative;
  cursor: pointer;
  padding: 0 10px;
}

.checkbox-wrapper input {
  position: absolute;
  opacity: 0;
  cursor: pointer;
  height: 0;
  width: 0;
}

.checkbox-wrapper .checkmark {
  position: relative;
  width: var(--checkbox-size);
  height: var(--checkbox-size);
  border: 2px solid var(--checkbox-border);
  border-radius: 8px;
  transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
  display: flex;
  justify-content: center;
  align-items: center;
  background: #fff;
  box-shadow: 0 0 15px var(--checkbox-shadow);
  overflow: hidden;
}

.checkbox-wrapper .checkmark::before {
  content: "";
  position: absolute;
  width: 100%;
  height: 100%;
  background: linear-gradient(45deg, var(--checkbox-color), #ffcc00);
  opacity: 0;
  transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
  transform: scale(0) rotate(-45deg);
}

.checkbox-wrapper input:checked ~ .checkmark::before {
  opacity: 1;
  transform: scale(1) rotate(0);
}

.checkbox-wrapper .checkmark svg {
  width: 0;
  height: 0;
  color: #1a1a1a;
  z-index: 1;
  transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
  filter: drop-shadow(0 0 2px rgba(0, 0, 0, 0.5));
}

.checkbox-wrapper input:checked ~ .checkmark svg {
  width: 18px;
  height: 18px;
  transform: rotate(360deg);
}

.checkbox-wrapper:hover .checkmark {
  border-color: var(--checkbox-color);
  transform: scale(1.1);
  box-shadow: 0 0 20px var(--checkbox-shadow), 0 0 40px var(--checkbox-shadow),
    inset 0 0 10px var(--checkbox-shadow);
}

.checkbox-wrapper input:checked ~ .checkmark {
  animation: pulse 1s cubic-bezier(0.68, -0.55, 0.265, 1.55);
}

@keyframes pulse {
  0% {
    transform: scale(1);
    box-shadow: 0 0 20px var(--checkbox-shadow);
  }
  50% {
    transform: scale(0.9);
    box-shadow: 0 0 30px var(--checkbox-shadow), 0 0 50px var(--checkbox-shadow);
  }
  100% {
    transform: scale(1);
    box-shadow: 0 0 20px var(--checkbox-shadow);
  }
}

.checkbox-wrapper .label {
  margin-left: 15px;
  color: var(--checkbox-color);
  font-size: 14px;
  text-shadow: 0 0 10px var(--checkbox-shadow);
  opacity: 0.9;
  transition: all 0.3s;
}

.checkbox-wrapper:hover .label {
  opacity: 1;
  transform: translateX(5px);
}

/* Glowing dots animation */
.checkbox-wrapper::after,
.checkbox-wrapper::before {
  content: "";
  position: absolute;
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: var(--checkbox-color);
  opacity: 0;
  transition: all 0.5s;
}

.checkbox-wrapper::before {
  left: -10px;
  top: 50%;
}

.checkbox-wrapper::after {
  right: -10px;
  top: 50%;
}

.checkbox-wrapper:hover::before {
  opacity: 1;
  transform: translateX(-10px);
  box-shadow: 0 0 10px var(--checkbox-color);
}

.checkbox-wrapper:hover::after {
  opacity: 1;
  transform: translateX(10px);
  box-shadow: 0 0 10px var(--checkbox-color);
}

    </style>
     <section class="form-container_hero">
    <form class="form" method="POST" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="moving_service_form">
        <p class="title">Safe & Easy</p>
        <p class="message">Best Packing and Moving</p>

        <div class="flex">
            <!-- First Dropdown (From Location) -->
            <label>
                <div class="select-menu">
                    <div class="select-btn">
                        <span class="sBtn-text">Select From</span>
                        <input type="hidden" name="from_location" id="from_location">
                        <svg width="14" height="15" viewBox="0 0 14 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3.5 5.75L7 9.25L10.5 5.75" stroke="#131316" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </div>
                    <ul class="options">
                        <li class="option" data-value="Location 1"><span class="option-text">Location 1</span></li>
                        <li class="option" data-value="Location 2"><span class="option-text">Location 2</span></li>
                        <li class="option" data-value="Location 3"><span class="option-text">Location 3</span></li>
                        <li class="option" data-value="Location 4"><span class="option-text">Location 4</span></li>
                    </ul>
                </div>
            </label>

            <!-- Second Dropdown (To Location) -->
            <label>
                <div class="select-menu">
                    <div class="select-btn">
                        <span class="sBtn-text">Select To</span>
                        <input type="hidden" name="to_location" id="to_location">
                        <svg width="14" height="15" viewBox="0 0 14 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M3.5 5.75L7 9.25L10.5 5.75" stroke="#131316" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </div>
                    <ul class="options">
                        <li class="option" data-value="Destination 1"><span class="option-text">Destination 1</span></li>
                        <li class="option" data-value="Destination 2"><span class="option-text">Destination 2</span></li>
                        <li class="option" data-value="Destination 3"><span class="option-text">Destination 3</span></li>
                        <li class="option" data-value="Destination 4"><span class="option-text">Destination 4</span></li>
                    </ul>
                </div>
            </label>
        </div> 

        <label>
            <input required="" name="name" placeholder="" type="text" class="input">
            <span>Name</span>
        </label>

        <label>
            <input required="" name="phone" placeholder="" type="tel" class="input">
            <span>Phone</span>
        </label>

        <label>
            <textarea name="parcel_details" class="input textarea"></textarea>
            <span>Parcel Details</span>
        </label>
           <label class="checkbox-wrapper">
          <input
            class="for_bike"
            type="checkbox"
            name="bike-parcel"
            value="yes"
          />
          <div class="checkmark">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path
                d="M20 6L9 17L4 12"
                stroke-width="3"
                stroke-linecap="round"
                stroke-linejoin="round"
              ></path>
            </svg>
          </div>
          <span class="label">For Bike Parcel</span>
        </label>
        <button type="submit" class="button-submit">Get Quote Now</button>
<!-- 		<p class="p line">Or With</p> -->

                <div class="flex-row">
                    <button class="btnn google">
					  <img src="https://intelizenlogistics.com/wp-content/uploads/2025/04/call-icon-2.svg" alt="Call Icon" style="width: 18px; height: 18px; vertical-align: middle; margin-bottom: 1px;">
					  Call Now
					</button>
                    <button class="btnn apple">
                        <img src="https://intelizenlogistics.com/wp-content/uploads/2025/04/whatsapp-icon.svg" alt="Whatsapp Icon" style="width: 20px; height: 20px; vertical-align: middle; margin-bottom: 1px;">
					  Whatsapp
					</button>
                </div>
    </form>
</section>

<script>
document.querySelectorAll(".select-menu").forEach(menu => {
    const selectBtn = menu.querySelector(".select-btn");
    const sBtnText = menu.querySelector(".sBtn-text");
    const hiddenInput = menu.querySelector("input[type='hidden']");

    selectBtn.onclick = () => menu.classList.toggle("active");

    menu.querySelectorAll(".option").forEach(option => {
        option.onclick = () => {
            sBtnText.innerText = option.querySelector(".option-text").innerText;
            hiddenInput.value = option.getAttribute("data-value");
            menu.classList.remove("active");
        };
    });
});

// Close dropdowns when clicking outside
document.addEventListener("click", (event) => {
    document.querySelectorAll(".select-menu").forEach(menu => {
        if (!menu.contains(event.target)) {
            menu.classList.remove("active");
        }
    });
});
</script>
     
   
      
<?php
    return ob_get_clean();
}
add_shortcode('hero_wp_form', 'hero_form');
