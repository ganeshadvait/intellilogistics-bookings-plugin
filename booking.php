//refined version from ai code

function handle_moving_service_form_submission() {
	
	// Start session if not already started
    if (!session_id()) {
        session_start();
    }

    // Verify nonce for security
    check_ajax_referer('moving_service_nonce', 'security');

    global $wpdb;
    $table_name = $wpdb->prefix . 'moving_service';
    $bikebooking_table_name = 'biketransportbooking';

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
        'from_location' => sanitize_text_field($_POST['from_location']),
        'to_location' => sanitize_text_field($_POST['to_location']),
        'parcel_details' => isset($_POST['parcel_details']) ? sanitize_textarea_field($_POST['parcel_details']) : '',
        'bike_parcel' => isset($_POST['bike-parcel']) && $_POST['bike-parcel'] === 'yes' ? 'yes' : 'no'
    ];
    // Store in session
    $_SESSION['moving_form_data'] = $data;
    // Insert into database
    $insert1 = $wpdb->insert($table_name, $data);
    $insert2 = $wpdb->insert($bikebooking_table_name, [
        'sender_name' => $data['name'],
        'phone' => $data['phone'],
        'from_location' => $data['from_location'],
        'to_location' => $data['to_location'],
        'email' => '',
        'pickup_date' => current_time('mysql')
    ]);

    // Send email
    $to = 'ganesh@advaitlabs.com';
    $subject = 'New Moving Service Request';
    $message = "Name: {$data['name']}<br>Phone: {$data['phone']}<br>From: {$data['from_location']}<br>To: {$data['to_location']}";
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    wp_mail($to, $subject, $message, $headers);

    // Return success response
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
    <form id="moving-service-form" class="form" method="POST">
        <input type="hidden" name="action" value="moving_service_form">
        <p class="title">Safe & Easy</p>
        <p class="message">Best Packing and Moving</p>

        <div class="flex">
            <!-- First Dropdown (From Location) -->
            <label>
                <div class="select-menu">
                    <div class="select-btn">
                        <span class="sBtn-text">Select From</span>
                        <input type="hidden" name="from_location" id="from_location" required="">
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
                        <input type="hidden" name="to_location" id="to_location" required="">
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
            <textarea name="parcel_details" class="input textarea" required=""></textarea>
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
		 <div id="moving-success" style="display: none;">
  🎉 Your request was submitted successfully!
</div>
		
</section>

   
      
<?php
    return ob_get_clean();
}
add_shortcode('hero_wp_form', 'hero_form');
function load_step_Two() {
	?>
 <section class="step_two_overlay" id="steptwooverlay" style="display: none;">
  <!-- Step 2 content here -->
			  <!-- Step Two Content Here -->
  <div class="modal-content">
       <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <p><strong>FROM</strong><br><?php echo isset($_SESSION['moving_form_data']['from_location']) ? esc_html($_SESSION['moving_form_data']['from_location']) : ''; ?></p>
            </div>
            <div>
                <p><strong>TO</strong><br><?php echo isset($_SESSION['moving_form_data']['to_location']) ? esc_html($_SESSION['moving_form_data']['to_location']) : ''; ?></p>
            </div>
            <div>
                <p><strong>DATE</strong><br><?php echo esc_html(current_time('F j, Y')); ?></p>
            </div>
            <div>
                <button style="background: #e74c3c; color: white; border: none; padding: 8px 16px; border-radius: 5px;">Change</button>
            </div>
        </div>

        <form>
            <div style="margin-bottom: 20px;">
                <p><strong>Delivery Speed</strong></p>
                <label><input type="radio" name="speed" value="express"> Express</label>
                <label><input type="radio" name="speed" value="standard" checked> Standard</label>
            </div>

            <div style="margin-bottom: 20px;">
                <p><strong>Pickup Type</strong></p>
                <label><input type="radio" name="pickup" value="door"> Door Pick Up</label>
                <label><input type="radio" name="pickup" value="hub" checked> Hub Pickup</label>
            </div>

            <div style="margin-bottom: 20px;">
                <p><strong>Delivery Type</strong></p>
                <label><input type="radio" name="delivery" value="door"> Door Delivery</label>
                <label><input type="radio" name="delivery" value="hub" checked> Hub Delivery</label>
            </div>

            <div style="margin-bottom: 20px;">
                <p><strong>Damage Insurance Scheme</strong> <a href="#" style="color: #e74c3c;">(Learn More)</a></p>
                <label><input type="radio" name="insurance" value="opt_out" checked> Opt Out</label>
                <label><input type="radio" name="insurance" value="opt_in"> Opt In</label>
            </div>

            <!-- Price Summary -->
            <div style="background: #ffeeee; border-radius: 10px; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.05);">
                <h2 style="color: #e74c3c;">Freight charges</h2>
                <p><em>(Inclusive of all taxes with GST)</em></p>
                <table style="width: 100%; font-size: 16px;">
                    <tr><td>Bike Transportation</td><td style="text-align: right;">₹ 2650.00</td></tr>
                    <tr><td>Fastrack Delivery Charges</td><td style="text-align: right;">₹ 0.00</td></tr>
                    <tr><td>Pickup Charges</td><td style="text-align: right;">₹ 0.00</td></tr>
                    <tr><td>Door Delivery Charges</td><td style="text-align: right;">₹ 0.00</td></tr>
                    <tr><td>Bike Insurance Charges</td><td style="text-align: right;">₹ 0.00</td></tr>
                    <tr><td>Total TAX (GST)</td><td style="text-align: right;">₹ 477.00</td></tr>
                    <tr>
                        <td style="font-weight: bold;">GRAND TOTAL</td>
                        <td style="text-align: right; font-size: 20px; font-weight: bold;">₹ 3127.00</td>
                    </tr>
                </table>
            </div>

            <div style="text-align: center; margin-top: 20px;">
                <button type="submit" style="background: #e74c3c; color: white; padding: 10px 20px; border-radius: 5px; font-size: 16px;">Review</button>
            </div>
        </form>
  </div>
</section>
 <?php
}
add_action('wp_footer', 'load_step_Two');
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
            
            // Add AJAX security nonce (see step 3 below)
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
                if (data.success) {
                    // Show success and next step
                    document.getElementById("moving-success").style.display = "block";
                    document.getElementById("steptwooverlay").style.display = "block";
                    
                    // You can populate step two with data if needed:
                    // document.querySelector('#step2-from').textContent = data.data.from_location;
                } else {
                    // Handle errors
                    alert(data.data.message || 'Error submitting form');
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
