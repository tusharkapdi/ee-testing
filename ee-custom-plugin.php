<?php
/*
Plugin Name: Isme Customization
Plugin URI: http://www.amplebrain.com
Description: Zoho Integration and Other Customization Work
Version: 1.0
Author: tusharkapdi
Author URI: https://profiles.wordpress.org/tusharkapdi/
Copyright 2022 Tushar Kapdi (email : tusharkapdi@gmail.com)
*/
require_once ( plugin_dir_path( __FILE__ ) . 'zoho-class.php' );

require_once ( plugin_dir_path( __FILE__ ) . 'zoho-keys.php' );

/*Outof concept code start*/
// Show xero details on user page on admin
function adding_zoho_details_on_user_profile_page($user){
    if(is_object($user)){
        $zohomember = esc_attr( get_the_author_meta( '_zohomember', $user->ID ) );
        $zohoprofile = get_the_author_meta( '_zohoprofile', $user->ID );
    }else{
        $zohomember = null;
        $zohoprofile = null;
    }
    ?>
    <h3>Zoho contact information</h3>
    <table class="form-table">
        <tr>
            <th><label>Zoho Membership</label></th>
            <td>
                <b><?php echo ($zohomember == 0) ? "Non-Member" : "Member"; ?></b><br />
                <span class="description"><i>User email is avilable in Zoho: <b>Member</b></i></span><br />
                <span class="description"><i>User email is Not avilable in Zoho: <b>Non-Member</b></i></span>
            </td>
        </tr>
        <tr>
            <th><label>Zoho Profile Details</label></th>
            <td>
                <?php
                if( is_array($zohoprofile) && count($zohoprofile) ){
                	foreach ($zohoprofile as $key => $value) {
	                	echo "<b>".$key."</b>: ".$value."<br>";
	                }
                }else{
                	echo "<b>N/A</b>";
                }
                ?><br />
                <span class="description"><i>Zoho Contact Details</i></span>
            </td>
        </tr>
    </table>
<?php
}
add_action( 'show_user_profile', 'adding_zoho_details_on_user_profile_page' );
add_action( 'edit_user_profile', 'adding_zoho_details_on_user_profile_page' );

/*Ultimate Member: Action on user registration after save details*/
add_action( 'um_after_save_registration_details', 'ab_after_save_um_registration_details', 8, 2 );
function ab_after_save_um_registration_details( $user_id, $submitted ) {
    	
	$abzohokeys = get_option( 'abzohokeys' );
	$RefreshToken = get_option( 'AB_ZOHO_REFRESHTOKEN' );

	$emailaddress = $submitted['user_email'];

	$TotalEmails = Callzohoapi::getTotalEmailInZoho( $abzohokeys, $RefreshToken, $emailaddress );

	var_dump($TotalEmails);
	if( $TotalEmails[0] == 0 ){
		
		update_user_meta( $user_id, '_zohomember', 0 );

		$user = new WP_User( $user_id );
		$user->add_cap( 'zoho_non_member');
		
	}else{
		update_user_meta( $user_id, '_zohomember', 1 );
		update_user_meta( $user_id, '_zohoprofile', $TotalEmails[1] );

		$user = new WP_User( $user_id );
		$user->add_cap( 'zoho_member');

		/*echo("Total Emails: " . $TotalEmails[0] . "\n");
		foreach ($TotalEmails[1] as $key => $value) {
			echo $key." => ".$value."<br>";
		}*/
	}
}
/*Outof concept code over*/

/*UM customization*/
function ab_profile_header_cover_area( $args ) {
	global $wp;
    
    if( get_current_user_id() != $wp->query_vars['um_user'] &&  $args['form_id'] = 24){
    	
    	wp_redirect( home_url() ); exit;
    	//echo '<pre>';print_r($args);echo '</pre>';
    }
}
add_action( 'um_profile_before_header', 'ab_profile_header_cover_area', 10, 1 );

add_filter( 'um_edit_newsletter_email_address_field_value', 'ab_change_newsletter_email_field_value_on_profile_edit', 10, 2 );
function ab_change_newsletter_email_field_value_on_profile_edit( $value, $key ) {

	$current_user = wp_get_current_user();
	
	$value = $current_user->user_email;
	return $value;
}

add_filter( 'FHEE__EED_WP_Users_Ticket_Selector__maybe_restrict_ticket_option_by_cap__no_access_msg_html', 'ab_add_capability_class_ticket_option_inside_row', 10, 5 );
function ab_add_capability_class_ticket_option_inside_row(
    $full_html_content,
    $inner_message,
    $tkt,
    $ticket_price,
    $tkt_status
) {

	$cap_required = $tkt->get_extra_meta('ee_ticket_cap_required', true);

	$full_html_content = str_replace('<td class="', '<td class="cap_'.$cap_required.' ', $full_html_content);
	
	$user_id = get_current_user_id();
    if( $user_id ){
    	return $full_html_content;
    }else{
    	return '<td class="cap_'.$cap_required.' loregbox" colspan="3">To sign up for this course you must login to your account.<br><a href="'.get_permalink(27).'" class="fusion-button fusion-button-default-size button-default">Login</a> <a href="'.get_permalink(28).'" class="fusion-button fusion-button-default-size button-default">Register</a></td>';
    }
    //return $full_html_content;
}

function ab_remove_member_non_member_ticket_upon_capability() {
	
	$user_id = get_current_user_id();
    if( $user_id ){

    	$user = new WP_User( $user_id );
    	if( $user->has_cap( 'zoho_member' ) ){

    		$custom_script = "jQuery('body.single-espresso_events .event-tickets table tr .cap_zoho_non_member').parent().hide();";
    	}else if( $user->has_cap( 'zoho_non_member' ) ){

    		$custom_script = "jQuery('body.single-espresso_events .event-tickets table tr .cap_zoho_member').parent().hide();";
    	}
    	echo "<script>jQuery(function() { ".$custom_script." });</script>";
    }else{
		$custom_script = "jQuery('body.single-espresso_events .event-tickets table tr .cap_zoho_member').parent().hide();";
		$custom_script .= "jQuery('body.single-espresso_events .event-tickets table tr .loregbox').parent().parent().parent().find('thead').hide();";
        echo "<script>jQuery(function() { ".$custom_script." });</script>";
    }
}
add_action( 'wp_head', 'ab_remove_member_non_member_ticket_upon_capability' );

function ab_add_capability_selection_in_create_ticket_admin($tkt_row, $TKT_ID)
{
    echo '<h5 class="tickets-heading">Ticket is for Member/Non-Member?</h5> <select class="capabilityoption"><option value="">Select Option</option><option value="zoho_member">Member</option><option value="zoho_non_member">Non-Member</option></select>';
}
add_action( 'AHEE__event_tickets_datetime_ticket_row_template__advanced_details_end', 'ab_add_capability_selection_in_create_ticket_admin', 10, 2);
function ab_add_capability_selection_javascript_admin() {
    
    //Leagcy Editor code
    echo '<script type="text/javascript">
    	jQuery(document).on("change", ".capabilityoption", function() {
    	//jQuery( ".capabilityoption" ).change(function() {
			jQuery(this).parent().find(".wp-user-ticket-capability-container").find("input.TKT-capability").val(jQuery(this).val());
		});

		jQuery(function() {
			jQuery( ".capabilityoption" ).each(function() {
				jQuery( this ).val( jQuery(this).parent().find(".wp-user-ticket-capability-container").find("input.TKT-capability").val() );
			});
		});
    </script>';

    //Advanced Editor code
    echo '<script type="text/javascript"> jQuery(document).on("change", "#capabilityRequired", function() { 
    		if(jQuery(this).val() == "custom"){ 
    			jQuery(this).parent().parent().append(\'<div id="MNMsecelctbox"><div class="ee-form-item__label"><label for="capabilityoptionA" class="chakra-form__label ee-form__label css-2gx1h6">Ticket is for Member/Non-Member?</label></div><div class="ee-select-wrapper css-dsdwpa"><select class="capabilityoptionA" id="capabilityoptionA"><option value="">Select Option</option><option value="zoho_member">Member</option><option value="zoho_non_member">Non-Member</option></select></div><div id="MNMtxt"></div></div>\'); 
    		}else{ 
    			jQuery("#MNMsecelctbox").remove(); 
    		} 
    	});

		jQuery(document).on("change", ".capabilityoptionA", function(){ 
			//const capbox = document.getElementById("customCapabilityRequired");
			//capbox.replaceWith(capbox.cloneNode(true));

			jQuery(this).parent().parent().parent().parent().find(".ee-form-item-name__customCapabilityRequired").find("input#customCapabilityRequired").val(jQuery(this).val());
			jQuery(this).parent().parent().parent().parent().find(".ee-form-item-name__customCapabilityRequired").find("input#customCapabilityRequired").attr("value", jQuery(this).val());

			jQuery(this).parent().parent().find("#MNMtxt").html("Type/Replace this text in \"Custom Capability\" Textbox: <b>" + jQuery(this).val() + "</b>");
		});

		//function myFunction() {}
		//getElementById("customCapabilityRequired").removeEventListener("click", myFunction, true);

		jQuery(function() {
			jQuery(document).on("click", ".css-r6z5ec .ee-dropdown-menu__list button", function(event) {
				if(jQuery(this).data("index") == 0){
					setTimeout(function(){ 
						if(jQuery("#capabilityRequired").val() == "custom"){
							jQuery("#capabilityRequired").parent().parent().append(\'<div id="MNMsecelctbox"><div class="ee-form-item__label"><label for="capabilityoptionA" class="chakra-form__label ee-form__label css-2gx1h6">Ticket is for Member/Non-Member?</label></div><div class="ee-select-wrapper css-dsdwpa"><select class="capabilityoptionA" id="capabilityoptionA"><option value="">Select Option</option><option value="zoho_member">Member</option><option value="zoho_non_member">Non-Member</option></select></div><div id="MNMtxt"></div></div>\');
			    		}else{
			    			jQuery("#MNMsecelctbox").remove();
			    		}

			    		jQuery( ".capabilityoptionA" ).each(function() {
							jQuery( this ).val( jQuery(this).parent().parent().parent().parent().find(".ee-form-item-name__customCapabilityRequired").find("input#customCapabilityRequired").val() );
						});
					}, 1000);
				}
			});
		});
    </script>';
}
add_action('admin_footer', 'ab_add_capability_selection_javascript_admin');
