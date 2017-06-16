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
 

if (!class_exists('SheTrades_ExportUsers')) {
  
  class SheTrades_ExportUsers {
    
    public $filters = array();
    public $bp_taxonomy_name;
    public $errors;

    public function __construct () {
             
        // adding actions..
        add_action('admin_menu', array( $this, 'add_menu_option' )); 
        add_action('admin_enqueue_scripts', array( $this, 'email_users_enqueue_scripts' )) ;
        add_action('wp_ajax_get_filtered_users_v2', array( $this, 'get_filtered_users_callback_v2' ));

        $this->errors = new WP_Error();
    }


    /*
     [ tagjs tagcss tagadd tagscript tagengueue taginclude tagregister tagstyle ]
     * Adds some JS
    */


    public function email_users_enqueue_scripts($hook) {
           
        wp_register_script('selectator', plugin_dir_url( __DIR__ ) . '/js/all.js', array('jquery'), false, true);
        wp_enqueue_script('selectator');

        wp_register_style('font-awesome', plugin_dir_url( __DIR__ ) . '/css/font-awesome.min.css');
        wp_enqueue_style('font-awesome');

        wp_register_style('ajax_fields', plugin_dir_url( __DIR__ ) . '/css/ajax_fields.css');
        wp_enqueue_style('ajax_fields');

        wp_register_style('export_users', plugin_dir_url( __DIR__ ) . '/css/admin_export_users.css');
        wp_enqueue_style('export_users');

        wp_register_style('ajax_loader', plugin_dir_url( __DIR__ ) . '/css/ajax_loader.css');
        wp_enqueue_style('ajax_loader');

    }



    /*
       [ tagmenu tagadd tagoption ] 
     * Adds a menu option
    */

    public function add_menu_option() {
        
        add_menu_page( 'Export Users', 'Export Users', 'manage_options', 'export_users', array( $this, 'add_menu_page' ), '', 554 );
    }



    /*

       [ tagform tagprocess tagpost tagselectbox tagselect tagexport tagcsv tagbuffer tagob]
         
     * Menu option callback
    */

    public function add_menu_page()
    {

        global $bp, $wpdb; 

        $options = Shetrades_Options::ExportUsers();

        $this->filters = $options->filters;

        $err_msg = '';

        if ((isset($_POST['download'])) && (!empty($_POST['download']))) {
            
            $filename = 'members.csv';
            $user_ids = explode(',', $_POST['user_ids']);
            $result = array();

            foreach ($user_ids as $key => $value) {
                
                $result[$key]['id'] = $value;             
                $name = $wpdb->get_col("
                                        SELECT value FROM {$bp->profile->table_name_data}
                                        WHERE (field_id = 1  || field_id = 17)
                                        AND user_id = '".$value."' 
                                        ");     

                $result[$key]['name'] = $name[0];
                $result[$key]['country'] = $name[1];
            }

            header('Content-Type: application/csv');
            header('Content-Disposition: attachment; filename="'.$filename.'";');

            ob_end_clean();

            $f = fopen('php://output', 'w');            
            
            foreach ($result as $fields) {
                fputcsv($f, $fields,' ');
            }

            fclose($f);

            exit();

        }

        $this->users_form($err_msg);
    }




    /*
     [ tagform taguser tagusers tagjs tagajax tagdynamic tagdynamically tagcreate tagnonce tagload tagerror tagerrors tagui tagui1 tagadmin tagpage tagpage1 tagadmin1 tagexport tagloader tagspinner]
     * Menu option callback
    */

    public function users_form($err_msg)
    {

        ?>

        <div class="wrap export_users">
            <div id="icon-users" class="icon32"><br/></div>

            <h2 class="nav-tab-wrapper">
                <a href="http://liveshetrades.fvds.ru/wp-admin/admin.php?page=export_users" class="nav-tab nav-tab-active">Export users</a>
                <a href="http://liveshetrades.fvds.ru/wp-admin/admin.php?page=send_emails" class="nav-tab">Send Emails</a>
            </h2>


            <!-- Static Errors / Notices -->

            <?php

            /*
            if ($this->errors->get_error_code()) {

                foreach($this->errors->get_error_messages() as $error){ die('~~~~~~~~~');
                    ?>
                        <div class="error notice">Error!</div>
                    <?php        
                }
            }
            */
            ?>

            <!-- Ajax Errors / Notices -->
            <form name="ExportUsers" action="" method="post">
            
                <div id="message_wrapper">
                    <div class="ps_notice" id="user_notice">Please choose at least one filter..</div> 
                    <div class="ps_info ps_notice" id="user_selectbox"></div>
                    <div class="ps_error ps_notice" id="errors"></div>
                </div>

                <h2 class="h2"><?php echo 'Exports Shetrades members'; ?></h2>

                <?php wp_nonce_field( 'mailusers_send_to_group', 'mailusers_send_to_group_nonce' ); ?>

                <table style="width:100%;">
	                <tr>

		                <td class="left_col">

                            Click to download .csv file with filtered members
			                
                            <p class="submit">
			                    <input disabled class="button-primary" id="download" type="submit" name="download" value="Download .csv" />
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
                                    
                                    filters['<?php echo $filter['id']; ?>'] = {}; 

                                    <?php 

                                        foreach ($filter as $key2 => $value2) {

                                            $value2 = str_replace(PHP_EOL, "", $value2);
                                            echo 'filters[\''.$filter['id'].'\'][\''.$key2.'\'] = \''.$value2.'\';';
                                        }          

                                    ?>

    				                </script>

    				                <tr>
    				                    <th scope="row" class="label" valign="top"><label><?php _e($filter['label'] , 'shetrades_theme'); ?>
    				                    <br/>
    				                    <span class="label_small"><small>ID = "<?php echo $filter['id']; ?>" <br> 
                                        <?php
                                            if ($filter['type'] === 'multiple') {
                                                
                                            }
    				                    ?>
    				                    </small></span></label>
                                        </th>    				                  
                                        <td>
                                          <?php echo $filter['value']; ?>
                                        </td>
    				                </tr>


    					            <?php
				                
				                }

			                    $ajax_nonce = wp_create_nonce("nonce_for_admin_get_users");
			               		
			               		?>

			               		<tr>
				                    <td class="filter_submit"><input class="button-primary" id="filter_submit" type="submit" name="Filter" value="Filter members" /></td>
				                    
				                    <script type="text/javascript">
				                     
				                        jQuery( "#filter_submit" ).click(function(e) { 

				                            e.preventDefault();

                                            jQuery('.loader').fadeIn(300);
                                            jQuery('#user_selectbox').fadeOut(300);
                                            jQuery('#errors').fadeOut(300);
                                            jQuery('#user_notice').fadeOut(300);


                                            var select_values = {};

                                            jQuery.each(filters, function (index, value){ 

                                                var name = value['name'];
                                                var id = value['id'];

                                                select_values[id] = {};

                                                var a = jQuery('select[name="' + name + '"] option:selected').each(function(){
                                                    select_values[id][jQuery(this).val()] = jQuery(this).val();
                                                });
                                            });
                                                    

                                            jQuery.ajax({ 
                                                
                                                type: "POST", 
                                                url: ajaxurl, 
                                                
                                                data: {
                                                    action: 'get_filtered_users_v2',
                                                    security: '<?php echo $ajax_nonce; ?>',
                                                    filters: filters,
                                                    select_values: select_values
                                                }, 

                                                success: function(response) { 

                                                    
                                                    response = jQuery.parseJSON(response);
                                                    jQuery('.loader').fadeOut(1200);

                                                    setTimeout(function() {

                                                        //errors
                                                        if (response['errors']) {

                                                            jQuery('#errors').html(response['errors']);
                                                            jQuery('#errors').fadeIn(300);
                                                            jQuery('#download').prop('disabled', true);
                                                        }
                                                        
                                                        //users
                                                        else{

                                                            jQuery('#user_selectbox').html(response['userbox']);
                                                            jQuery('#user_selectbox').fadeIn(300);
                                                            
                                                            if (response['count'] > 0) {
                                                                jQuery('#download').prop('disabled', false);
                                                            }
                                                        }

                                                    }, 1000);
                                                    
                                                },
                                            });
				                        });

				                    </script>

				                </tr>

					    		</table>            
					    </td>

	                </tr>
                </table>

               
            </form>
        </div>


        <div class="loader" style="display: none;">
            <div class="cube-wrapper">
                <div class="cube-folding">
                    <span class="leaf1"></span>
                    <span class="leaf2"></span>
                    <span class="leaf3"></span>
                    <span class="leaf4"></span>
                </div>
                <span class="loading" data-name="Loading">Loading</span>
            </div>
        </div>
  
        <?php
    }




    /**
    [ tagajax tagnonce tagcheck tagcallback tagarray tagterm tagget tagselect tagbuffer tagob]

     * Get the users
    */


    function get_filtered_users_callback_v2() {

        check_ajax_referer( 'nonce_for_admin_get_users', 'security' );
        
        $select_values = $_POST['select_values'];

        ob_start();
        
        global $wpdb;

        $users_new = SheTrades_Xprofile_Hooks::get_users_by_codes($select_values);

        $errors = false;

        if (count($users_new) == 0) {

            $errors = 'Nothing was found';
        }

        ?> 
             
        <input type="hidden" name="user_ids" value="<?php echo implode(',', $users_new); ?>"> 
        <b><?php echo $count = count($users_new); ?> entries</b> was found

        <?php

        $userbox = ob_get_contents();
        ob_end_clean();
         
        echo(json_encode( array('errors' => $errors, 'count' => $count, 'userbox' => $userbox) ));
        wp_die();
        
    }

   

 }      
}






























