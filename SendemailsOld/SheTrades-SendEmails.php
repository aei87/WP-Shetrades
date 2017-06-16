<?php
/*
 * SheTrades-SendEmails.php
 *
 * Copyright (C) 2016, ITC (International Trade Centre). all rights reserved.
 *
 *
 * No part of this software may be reproduced in any form or by any means
 * - graphic, electronic or mechanical, including photocopying, recording,
 * taping or information storage and retrieval systems -
 * except with the written permission of ITC (International Trade Centre).
 *
 *
 * This notice may not be removed.
 *
 *  History:
 *
 *  Modified:   By:             Reason:
 *  ---------   ---             -------
 *  2017/03/25  E.Abdullin         Initial implementation
 *
 *  Description:
 *
 *  This class is used to send Emails via Admin Section
 *    
 *
 */
 

if (!class_exists('SheTrades_SendEmails')) {
  
  class SheTrades_SendEmails {
    
  
    public $filters = array();
    public $from_address;
    public $from_name;
    public $bp_taxonomy_name;

    public function __construct () {
      
        // getting options..
        $options = Shetrades_Options::SendEmails();
        
        $this->filters = $options->filters;
        $this->from_address = $options->from_address;
        $this->from_name = $options->from_name;
        $this->bp_taxonomy_name = $options->bp_taxonomy_name;     
        
        // adding actions..
        add_action( 'admin_menu', array( $this, 'add_menu_option' )); 
        add_action('admin_enqueue_scripts', array( $this, 'email_users_enqueue_scripts' )) ;
        add_action('wp_ajax_get_filtered_users', array( $this, 'get_filtered_users_callback' ));

    }


    /*
     [ tagjs tagcss tagadd tagscript tagengueue taginclude tagregister tagstyle ]
     * Adds some JS
    */


    public function email_users_enqueue_scripts($hook) {
        
        wp_register_script('mailusers-chosen', plugin_dir_url( __DIR__ ) . '/js/chosen/chosen.jquery.min.js', array('jquery'), false, true) ;
        wp_register_script('mailusers', plugin_dir_url( __DIR__ ) . '/js/mailusers.js', array('jquery', 'mailusers-chosen'), false, true) ;
        wp_register_style('mailusers-chosen', plugin_dir_url( __DIR__ ) . '/js/chosen/chosen.min.css') ;

        wp_enqueue_script('mailusers-chosen') ;
        wp_enqueue_script('mailusers') ;
        wp_enqueue_style('mailusers-chosen') ;
        
    }



    /*
       [ tagmenu tagadd tagoption ] 
     * Adds a menu option
    */

    public function add_menu_option() {
        
        add_menu_page( 'Send Emails', 'Send Emails', 'manage_options', 'send_emails', array( $this, 'add_menu_page' ), '', 554 );
        //remove_submenu_page( 'send_emails', 'send_emails' );
    
    }



    /*

       [ tagform tagprocess tagpost tagselectbox tagselect ]
         
     * Menu option callback
    */

    public function add_menu_page()
    {

        $err_msg = '';

        // Send the email if it has been requested
        if (array_key_exists('send', $_POST) && $_POST['send']=='true') { 
            if (! isset( $_POST['mailusers_send_to_group_nonce'] )  
                || ! wp_verify_nonce( $_POST['mailusers_send_to_group_nonce'], 'mailusers_send_to_group' ) ) {

                wp_die(printf('<div class="error fade"><p>%s</p></div>',
                    __('WordPress nonce failed to verify, requested action terminated.')));
            }
            // No error and nonce ok, send the mail
        
            // Analyse form input, check for blank fields
            if ( !isset( $_POST['mail_format'] ) || trim($_POST['mail_format'])=='' ) {
                $err_msg = $err_msg . __('You must specify the mail format.') . '<br/>';
            } else {
                $mail_format = $_POST['mail_format'];
            }
        
            if ( !isset($_POST['filter_users']) || !is_array($_POST['filter_users']) || empty($_POST['filter_users']) ) {
                $err_msg = $err_msg . __('You must select at least a user.') . '<br/>';
            } else {
                $filter_users = $_POST['filter_users'];
            }
        
            if ( !isset( $_POST['subject'] ) || trim($_POST['subject'])=='' ) {
                $err_msg = $err_msg . __('You must enter a subject.') . '<br/>';
            } else {
                $subject = $_POST['subject'];
            }
        
            if ( !isset( $_POST['mailcontent'] ) || trim($_POST['mailcontent'])=='' ) {
                $err_msg = $err_msg . __('You must enter some content.') . '<br/>';
            } else {
                $mail_content = $_POST['mailcontent'];
            }
            
        }

        if (!isset($filter_users)) {
            $filter_users = array();
        }

        if (!isset($mail_format)) {
            $mail_format = 'html';
        }

        if (!isset($subject)) {
            $subject = '';
        }

        if (!isset($mail_content)) {
            $mail_content = '';
        }     
    
        $subject = $this->mailusers_preg_quote($subject);
        $mail_content = $this->mailusers_preg_quote($mail_content);

        if (array_key_exists('send', $_POST) && ($_POST['send']=='true') && ($err_msg == '')) {
            // No error, send the mail
            
            if ($mail_format=='html') {
                $mail_content = wpautop($mail_content);
            }       
            
            ?><div class="wrap"> <?php

            if (empty($filter_users)) {
                ?><p><strong>No recipients were found.</strong></p><?php
            } else {

                $sent = $this->send_mail($filter_users, $subject, $mail_content, $mail_format);
                if (false === $sent) {
                    print '<div class="error fade"><p>There was a problem trying to send email to users.</p></div>';
                } else if (0 === count($sent)) {
                    print '<div class="error fade"><p>No email has been sent to other users. This may be because no valid email addresses were found</p></div>';
                } else if (count($sent) > 0 && count($sent) == count($filter_users)){
                
                ?><div class="updated fade">
                    <p><b><?php echo 'Email sent to '.count($sent).' user(s):'; ?></b></p>
                    <?php

                        foreach ($sent as $key => $value) {
                           ?> <p><?php echo $value; ?></p> <?php
                        }

                    ?>
                </div>
                <?php
                } else if (count($sent) > count($filter_users)) {
                    print '<div class="error fade"><p>WARNING: More email has been sent than the number of recipients found.</p></div>';
                } else {
                    echo '<div class="updated fade"><p>Email has been sent to '.$num_sent.' users, but '.count($filter_users).' recipients were originally found. Perhaps some users don\'t have valid email addresses?</p></div>';
                }
                $this->users_form($err_msg, $send_targets, $mail_format, $subject, $mail_content);
            }
            ?></div><?php
        } else {
            // Redirect to the form page
           $this->users_form($err_msg, $send_targets, $mail_format, $subject, $mail_content);
        }

    }




    /*
     [ tagform taguser tagusers tagjs tagajax tagdynamic tagdynamically tagcreate tagnonce tagpost tageditor tagwpeditor tagload]
     * Menu option callback
    */

    public function users_form($err_msg, $send_targets, $mail_format, $subject, $mail_content)
    {

        if (!isset($send_targets)) { $send_targets = array(); }
        if (!isset($mail_format)) { $mail_format = 'html'; }
        if (!isset($subject)) { $subject = ''; }
        if (!isset($mail_content)) { $mail_content = '';}

        ?>

        <div class="wrap">
            <div id="icon-users" class="icon32"><br/></div>
            <h2><?php echo 'Send an Email to a Group of Users'; ?></h2>

            <?php   if (isset($err_msg) && $err_msg!='') { ?>
                    <div class="error fade"><p><?php echo $err_msg; ?><p></div>
                    <p>Please correct the errors displayed above and try again.</p>
            <?php   } ?>

            <form name="SendEmail" action="" method="post">
                <?php wp_nonce_field( 'mailusers_send_to_group', 'mailusers_send_to_group_nonce' ); ?>
                <input type="hidden" name="send" value="true" />
                <input type="hidden" name="fromName" value="<?php echo $this->from_name;?>" />
                <input type="hidden" name="fromAddress" value="<?php echo $this->from_address; ?>" />
                <input type="hidden" name="group_mode" value="<?php echo $mailusers_send_to_group_mode;?>" />


                <table>
	                <tr>

		                <td class="left_col">
		                	<table class="form-table" width="100%" cellspacing="2" cellpadding="5">
               
               					<tr>
				                    <th scope="row" valign="top">Mail format</th>
				                    <td><select class="mailusers-select" name="mail_format" style="width: 158px;">
				                        <option value="html" <?php if ($mail_format=='html') echo 'selected="selected"'; ?>>HTML</option>
				                        <option value="plaintext" <?php if ($mail_format=='plaintext') echo 'selected="selected"'; ?>>Plain text</option>
				                    </select></td>
				                </tr>

				                <tr>
				                    <th scope="row" valign="top"><label>Sender</label></th>
				                    <td><?php echo $this->from_name;?> &lt;<?php echo $this->from_address;?>&gt;</td>
				                </tr>
			                 
				                <tr>

				                    <th scope="row" valign="top"><label for="send_targets">Users
				                        <br/><br/>
				                            <small>You can select multiple groups by pressing the CTRL key.</small>
				                        <br/><br/>
				                        </label>
				                    </th>
				                    <td id="user_selectbox" class="user_selectbox"> <b>Please choose at least one filter</b> </td>

				                </tr>

				                <tr>
				                    <th scope="row" valign="top"><label for="subject">Subject</label></th>
				                    <td><input type="text" id="subject" name="subject" value="<?php echo format_to_edit($subject);?>" style="width: 647px;" /></td>
				                </tr>
				                <tr>
				                    <th scope="row" valign="top"><label for="mailcontent">Message</label></th>
				                    <td>
				                        <div id="mail-content-editor" style="width: 647px;">
				                        <?php
				                            if ($mail_format=='html') {
				                                wp_editor(stripslashes($mail_content), "mailcontent");
				                            } else {
				                        ?>
				                            <textarea rows="10" cols="80" name="mailcontent" id="mailcontent" style="width: 647px;"><?php echo stripslashes($mail_content);?></textarea>
				                        <?php 
				                            }
				                        ?>
				                        </div>
				                    </td>
				                </tr>

			                </table>

			                <p class="submit">
			                    <input class="button-primary" type="submit" name="Submit" value="Send Email" />
			                </p>

		                </td>

		                <td class="right_col">
		                	<table class="form-table" width="100%" cellspacing="2" cellpadding="5">
				               
				                <script type="text/javascript">
				                     var filters = {};
				                </script>

				                <?php


				                foreach ($this->filters as $key => $filter) {

				                ?>

				                <script type="text/javascript">

				                    filters[<?php echo $filter['id'];  ?>] = {};
				                    filters[<?php echo $filter['id'];  ?>]['id'] = '<?php echo $filter['id'];  ?>';
				                    filters[<?php echo $filter['id'];  ?>]['type'] = '<?php echo $filter['type'];  ?>';

				                </script>

				                <tr>
				                    <th scope="row" valign="top"><label for="send_targets"><?php echo $filter['label']; ?>
				                    <br/><br/>
				                    <small>You can select multiple groups by pressing the CTRL key.</small>
				                    <br/><br/>
				                    </label></th>
				                    <td>
				                        <select data-placeholder="Please choose..." class="mailusers-select" id="filter_<?php echo $filter['id']; ?>" name="filter_<?php echo $filter['id']; ?>[]" multiple="multiple" size="8" style="width: 300px; height: 250px;">
				                        <?php 

				                            $targets = $this->get_filter_values($filter);
				                            foreach ($targets as $key => $value)
				                            {
				                                ?>
				                                <option value="<?php echo $value; ?>"?
				                                    <?php echo (in_array($value, $_POST['filter_'.$filter['id']]) ? ' selected="yes"' : '');?>>
				                                    <?php echo $value;  ?>
				                                </option>
				                                <?php  
				                            }

				                            ?>
				                        </select>
				                    </td>
				                </tr>


					            <?php
				                
				                }

			                    $ajax_nonce = wp_create_nonce("nonce_for_admin_get_users");
			               		
			               		?>

			               		<tr>
				                    <td class="filter_submit"><input class="button-primary" id="filter_submit" type="submit" name="Filter" value="Filter Users" /></td>
				                    
				                    <script type="text/javascript">
				                     
				                        jQuery( "#filter_submit" ).click(function(e) { 

				                            e.preventDefault();

				                            jQuery.each(filters, function (index, value){ 

				                                filters[index]['value'] = {};

				                                jQuery('#filter_'+index+' :selected').each(function(i, selected){  
				                                    filters[index]['value'][i] = jQuery.trim(jQuery(selected).text())
				                                });

				                            });
				                                                        
				                            var data = {
				                                action: 'get_filtered_users',
				                                security: '<?php echo $ajax_nonce; ?>',
				                                filters: filters
				                            };

				                            jQuery.post( ajaxurl, data, function(response) {
				                                response = jQuery.parseJSON(response);
				                                jQuery('#user_selectbox').html(response['userbox']);
				                                jQuery(".mailusers-select").chosen();
				                            });


				                        });

				                    </script>

				                    <style type="text/css">

				                        .filter_submit{
				                            padding:0px !important;
				                            margin-top:-30px;
				                            padding-bottom: 50px !important;
				                        }

				                        .user_selectbox{
				                            padding-top: 30px !important;
				                        }

				                        .left_col, .right_col{
				                        	padding-left:30px;
				                        	padding-right:30px;
				                        	vertical-align: top;
				                        	padding-top:30px;
				                        }

				                        .chosen-container{
				                        	width:300px !important;
				                        }

				                    </style>

				                </tr>

					    		</table>            
					    </td>

	                </tr>
                </table>

               
            </form>
        </div>
        <?php
    }



    /* 
        [ tagbuddypress tagmembership tagxprofile tagwpdb tagterm tagterms tagget]
        Gets filters
    */

    public function get_filter_values($filter) {

        global $wpdb;
        
        if ($filter['type'] == 'membership') {

            $terms = get_terms([
                'taxonomy' => $this->bp_taxonomy_name,
                'hide_empty' => false,
            ]);

            foreach ($terms as $key => $term) {
                $filter_values[] = $term->name;
            }
        }
        else if ($filter['type'] == 'numeric') {
                $filter_values = $wpdb->get_col( $wpdb->prepare("
                SELECT DISTINCT value FROM wp_bp_xprofile_data
                WHERE field_id = %s ORDER BY value * 1 ASC 
                ", $filter['id'])); 
        }
        else{
                $filter_values = $wpdb->get_col( $wpdb->prepare("
                SELECT DISTINCT value FROM wp_bp_xprofile_data
                WHERE field_id = %s ORDER BY value ASC 
                ", $filter['id']));
        } 

        return $filter_values;
        
    }





    /**
    [ tagajax tagnonce tagcheck tagcallback tagintersect tagmerge tagarray tagterm tagtaxonomy tagget tagselect tagselectbox tagselected ]

     * Get the users
    */

    function get_filtered_users_callback() {

        check_ajax_referer( 'nonce_for_admin_get_users', 'security' );
        
        $filters = $_POST['filters'];

        ob_start();
        
        global $wpdb;

        foreach ($filters as $filter_id => $filter) {

            $users_new = array();

            foreach ($filter['value'] as $key => $value) {
                
                if ($filter['type'] == 'membership') { 
                   
                    $term = get_term_by('name', $value, $this->bp_taxonomy_name);
                    $users_arr = get_objects_in_term( $term->term_id, $this->bp_taxonomy_name);
                    $users_new = array_merge($users_new, $users_arr);
                }
                else{ 
                   
                    $users_new = array_merge($users_new,$wpdb->get_col( $wpdb->prepare("
                                SELECT user_id FROM wp_bp_xprofile_data
                                WHERE field_id = %s  
                                AND value = %s 
                                ", $filter['id'], $value)));
                    
                } 

            }

            if ((!isset($users_result)) && (!empty($users_new))) {
                ?><?php
                $users_result = array();
                $users_result = array_merge($users_result, $users_new); 
            }
            else if (!empty($users_new)) { 
               ?><pre><?php //echo var_dump($users_result); ?></pre><?php 
               ?><pre><?php //echo var_dump($users_new); ?></pre><?php 
               $users_result = array_intersect($users_result, $users_new);

               ?><pre><?php //echo var_dump($users_result); ?></pre><?php 
               //$users = array_unique($users);
            }
        
           
        }
      
        ?> 
           
       
        <select data-placeholder="Choose recipients ..." class="mailusers-select" id="filter_users" name="filter_users[]" multiple="multiple" size="18" style="width: 654px; height: 350px;">
        <?php 

            foreach ($users_result as $key => $value)
            {
                
                $user_email = get_user_by('ID', $value);
                $user_email = $user_email->user_email
                
                ?>
                <option value="<?php echo $user_email; ?>"?
                    <?php echo (in_array($user_email, $send_targets) ? ' selected="yes"' : '');?>>
                    <?php echo $user_email;  ?>
                </option>
                <?php  
            }

            ?>
        </select>




        <?php

        $userbox = ob_get_contents();
        ob_end_clean();
         
        echo(json_encode( array('status'=>'ok','userbox'=>$userbox) ));
        wp_die();
        
    }




    /**

     [ taguser tagusers tagget tagbuddypress tagmembership tagterm tagtaxonomy tagobject tagname ]

     * Get the users
    */

    function get_users($filter_values) {
        
        global $wpdb;

        $users = array();

        foreach ($filter_values as $filter_id => $filter_value) {
            
            if ($filter['type'] == 'membership') {
                $term = get_term_by('name', $filter_value, $this->bp_taxonomy_name);
                $users_arr = get_objects_in_term( $term->term_id, $this->bp_taxonomy_name);
                foreach ($users_arr as $users_arr_key => $users_arr_id) {
                    $users_arr_obj = new stdClass;
                    $users_arr_obj->user_id = $users_arr_id;
                    $users_obj[] = $users_arr_obj;
                }
                $users = array_merge($users, $users_obj);
            }
            else{
                $users = array_merge($users, $wpdb->get_results( $wpdb->prepare("
                            SELECT DISTINCT user_id FROM wp_bp_xprofile_data
                            WHERE field_id = %s  
                            AND value = %s 
                            ", $filter['id'], $filter_value)));
            }
           
        }
        
        return $users ;
    }


    /**
     * Protect against special characters (e.g. $) in the post content
     * being processed as part of the preg_replace() replacement string.
     *
     * @see http://www.procata.com/blog/archives/2005/11/13/two-preg_replace-escaping-gotchas/
     */
    public function mailusers_preg_quote($str) {
        return preg_replace('/(\$|\\\\)(?=\d)/', '\\\\\1', $str);
    }




    
    /**
       [ tagemail tagmail tagsendmail tagbcc tagcc ]

     * Delivers email to recipients in HTML or plaintext
     *
     * Returns array of recipient's emails.
    */

    public function send_mail($users_emails = array(), $subject = '', $message = '', $type='plaintext') {
            
        $headers = array();

        //$to = sprintf('%s <%s>', $this->from_name, $this->from_address);
        $to = sprintf('%s <%s>', 'Client', $this->from_address);


        if ('' == $message) { return false; }

        //  Build headers
        $headers[] = sprintf('From: "%s" <%s>', $this->from_name, $this->from_address);

        //  Return path defaults to sender email if not specified
        $headers[] = sprintf('Reply-To: "%s" <%s>', $this->from_name, $this->from_address);

        $subject = stripslashes($subject);
        $message = stripslashes($message);


        if ('html' == $type) {
                $headers[] = 'MIME-Version: 1.0';
                $headers[] = sprintf('Content-Type: %s; charset="%s"', get_bloginfo('html_type'), get_bloginfo('charset')) ;

            $mailtext = "<html><head><title>" . $subject . "</title></head><body>".$message."</body></html>";

        } else {
                $headers[] = 'MIME-Version: 1.0';
                $headers[] = sprintf('Content-Type: text/plain; charset="%s"', get_bloginfo('charset')) ;
                $message = preg_replace('|&[^a][^m][^p].{0,3};|', '', $message);
                $message = preg_replace('|&amp;|', '&', $message);
                $mailtext = wordwrap(strip_tags($message), 80, "\n");
        }


        $bcc = array();

        foreach ($users_emails as $key => $user_email){

            $bcc[] = sprintf('Bcc: %s', $user_email) ;
            $sent[] = $user_email; 

        }
       
        @wp_mail($to, $subject, $mailtext, array_merge($headers, $bcc)) ; 

        return $sent;
    }


 }      
}






























