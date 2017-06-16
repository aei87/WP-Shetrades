<?php
/*
 * SheTrades-Xprofile-Hooks.php
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
 *  2016/05/01  R.Vidal         Initial implementation
 *
 *  Description:
 *
 *  This class is used to hook some Xprofile behavior
 *    
 *
 */
 

if (!class_exists('SheTrades_Xprofile_Hooks')) {
  
  class SheTrades_Xprofile_Hooks {
    
    const INTERNAL_NAME = 'internal';
    
    // !! Hardcoded base on the ID in the database !!
    const ORGANISATION_PSEL = 51;  //Product am selling
    const ORGANISATION_SSEL = 52;  //Services am offering / selling
    const ORGANISATION_PBUY = 53;  //Products am buying
    const ORGANISATION_SBUY = 54;  //Services am buying
    
  
    public function __construct () {
      
      /** Buddypress hook **/
      add_action( 'bp_init', array($this, 'init') );
      
      /** Buddypress Profile hook **/
      // Be sure have a very low priority as xprofile_sync_wp_profile is also changing the name but as we want
      add_action( 'xprofile_updated_profile', array($this, 'xprofile_updated_profile'),99 ,3 );
      add_action( 'bp_core_signup_user',      array($this, 'bp_core_signup_user') );
      add_action( 'bp_core_activated_user',   array($this, 'bp_core_activated_user') );
      add_action( 'bp_setup_nav',             array($this, 'bp_setup_nav' ), 15 );
      add_action( 'bp_core_get_user_domain',  array($this, 'bp_core_get_user_domain'), 10, 4 );
      add_action( 'bp_ajax_querystring',      array($this, 'bp_ajax_querystring') , 20,2);
      add_filter( 'bp_xprofile_get_visibility_levels', array($this, 'bp_xprofile_get_visibility_levels') );
      add_filter( 'bp_xprofile_get_hidden_field_types_for_user', array($this, 'bp_xprofile_get_hidden_field_types_for_user' ), 10, 3 );
      add_filter( 'xprofile_data_is_valid_field', array($this, 'xprofile_data_is_valid_field' ), 10, 2 );
      add_filter( 'bp_get_the_profile_field_name', array($this, 'bp_get_the_profile_field_name') );

    }

    /**
     * Custom initialization
     *
     */
    public function init() {
    }
    
    /**
     * Update WordPress nice name as its used for permalink for members. We use the Business Name or Name from XProfile.
     *
     *
     */
    private function update_nice_name($user_id) {

      if ( !empty( $user_id ) ) {

        $display_name = $user_nicename = null;
        
        $member_type = bp_get_member_type( $user_id );
        //    echo $member_type;
        switch ($member_type) {
          case SheTrades_Member_Type::MEMBERTYPE_VERIFIER:
            // We try the organisation first to define the slugs, see if we add an option in the plugin option menu
            $display_name = $user_nicename = xprofile_get_field_data( 'Name of Organisation', $user_id );
            break;
          default:
            // We try the company first to define the slugs, see if we add an option in the plugin option menu
            $display_name = $user_nicename = xprofile_get_field_data( 'Business Name', $user_id );
            break;
        }
        
        if (empty($user_nicename)) {
          // If empty we get the name
          $display_name = $user_nicename = xprofile_get_field_data( 'Name', $user_id );
        }

        if (!empty($user_nicename)) {
          $user_nicename = sanitize_user( $user_nicename, true ); 
          // From wp_insert_user nice name can't longer than 50 chars
          $user_nicename = mb_substr( $user_nicename, 0, 50 ); 
          // Make is like a slugs
          $user_nicename = sanitize_title( $user_nicename ); 
          
          // Rewrite the user_nicename base on the company name or full name
          $userdata = array(
            'ID'  =>  $user_id,
            'user_nicename'  =>  $user_nicename,
            'display_name'  =>  $display_name,
            'nickname'  =>  $display_name
          );
          wp_update_user($userdata);
        }
      }
    }
    
    /**
     * Update Buddypress member type based on the ownership XProfile.
     *
     *
     */
    private function update_member_type($user_id) {

      if ( !empty( $user_id ) ) {

        $member_type = bp_get_member_type( $user_id );
        //    echo $member_type;
        switch ($member_type) {
          case SheTrades_Member_Type::MEMBERTYPE_VERIFIER:
            break;
          case SheTrades_Member_Type::MEMBERTYPE_BUYER:
          case SheTrades_Member_Type::MEMBERTYPE_SELLER:
            $women_owned_percent = xprofile_get_field_data( SheTrades_Old_Mobile_Api::ORGANISATION_PERC, $user_id);
            $women_owned_percent = preg_replace('/[^0-9.]+/', '', $women_owned_percent);
            if ($women_owned_percent >= 30) {
              // Hardcoded!
              bp_set_member_type( $user_id, SheTrades_Member_Type::MEMBERTYPE_SELLER );
            } else {
              // Hardcoded!
              bp_set_member_type( $user_id, SheTrades_Member_Type::MEMBERTYPE_BUYER);
            }             
            break;
          default:
            break;
        }
      }
    }
    /**
     * Fires if the user has already been created.
     *
     * @since 1.2.2
     *
     * @param int    $user_id ID of the user being checked.
     */
    public function bp_core_activated_user( $user_id) {
      $this->update_nice_name($user_id);
      $this->update_member_type($user_id);
    }

    /**
     * Fires at the end of the process to sign up a user.
     *
     * @since 1.2.2
     *
     * @param bool|WP_Error   $user_id       True on success, WP_Error on failure.
     */
    public function bp_core_signup_user ($user_id) {
      if ( !is_wp_error( $user_id ) ) {
        $this->update_nice_name($user_id);
        $this->update_member_type($user_id);
      }        
    }
     
    
    /**
     * Fires after all of the profile fields have been saved.
     *
     * @since 1.0.0
     *
     * @param int   $user_id          ID of the user whose data is being saved.
     * @param array $posted_field_ids IDs of the fields that were submitted.
     * @param bool  $errors           Whether or not errors occurred during saving.
     */
    public function xprofile_updated_profile($user_id, $posted_field_ids, $errors ) {
      $this->update_nice_name($user_id);
      $this->update_member_type($user_id);
    }
    
    
    /**
     * Remove some links to allow user to change their profiles
     *
     * @since 1.0.0
     *
     */
    function bp_setup_nav() {
      bp_core_remove_nav_item( 'blogs' );
//      bp_core_remove_nav_item( 'groups' );
      bp_core_remove_subnav_item( 'messages', 'notices' );
      bp_core_remove_subnav_item( 'settings', 'capabilities' );
      bp_core_remove_subnav_item( 'settings', 'delete-account' );
      bp_core_remove_subnav_item( 'settings', 'profile' );
    }
    
    /**
     * overwrite the permalink of the company:
     * - By default it using the name
     * - If a company name is present use this one instead
     *
     */
    public function bp_core_get_user_domain($domain, $user_id, $user_nicename, $user_login) {
        
      if ( empty( $user_id ) ) {
        return $domain;
      }

//      $username = BP_XProfile_ProfileData::get_all_for_user( $user_id ); 
//      print_r($username);
      //print_r($domain);
      return $domain;

//      $after_domain = bp_core_enable_root_profiles() ? $username : bp_get_members_root_slug() . '/' . $username;
//      $domain       = trailingslashit( bp_get_root_domain() . '/' . $after_domain );
//      return $domain;
      
    }
    /**
     * add custom search from xprofile
     *
     */
    public function bp_ajax_querystring( $qs = false , $object = false) {
       if ( $object != 'members' ) {//hide for members only
        return $qs;
       }
       
       //check if we are listing friends?, do not exclude in this case
/*       if ( ! empty($args['user_id'] ) ) {
        return $qs;
       }*/

      $args = wp_parse_args($qs);
      $bp = buddypress();
       
      $users = $this->query_members ($args);

      if ( is_array($users) ) {

        if (isset ($args['include'])) { 
          $included = explode (',', $args['include']); 
          $users = array_intersect ($users, $included);
          if (count ($users) == 0) {
            $users = array (0);
          }
        }
        
        $args['include'] = implode (',', $users);
        $qs = build_query ($args);
      }

      return $qs;
    }









     /**

     [ tagwpdb tagdatabase tagdb tagprepare tagajax tagcallback tagregexp taglike tagarray tagmerge tagintersect tagbuddypress]
     * get a list of user ids to include query
     *
     */
     
      static function get_users_by_codes ($fields) {

        global $bp, $wpdb;  

        foreach ($fields as $key => $value) { 

          if ((!empty($value)) && ($key !== 'upage')){

            if (stripos($key, 'field_verified') !== false) { 

              if (($value[1] === '1') || ($value === '1')) { 

                $query = "SELECT initiator_user_id FROM {$wpdb->base_prefix}shetrades_verified WHERE is_verified = 1 GROUP BY initiator_user_id";
                $users_new = $wpdb->get_col ($query);
              }


            } else if (stripos ( $key, 'field_') !== false ) {
          
              $like = substr ($key, 6, 1);
              $id = substr ($key, 7);
                    
              if (((int)$id == self::ORGANISATION_PSEL) || ((int)$id == self::ORGANISATION_PBUY) || ((int)$id == self::ORGANISATION_SSEL) || ( (int)$id == self::ORGANISATION_SBUY)) {
                
                $like = '(1 = 0 ';

                foreach ($value as $product_key => $product_value) {
          
                  $like .= $wpdb->prepare(" OR value REGEXP %s", '"'.$product_value.'([0-9][0-9])*"');              
                }

                $like .= ')';

                $field = $wpdb->prepare("field_id = %s", $id);
                
                $users_new = $wpdb->get_col("
                                  SELECT user_id FROM {$bp->profile->table_name_data}
                                  WHERE $field 
                                  AND $like 
                                  ");


                                /*
                                  echo "
                                  SELECT user_id FROM {$bp->profile->table_name_data}
                                  WHERE $field 
                                  AND $like 
                                  ";
                            

                                  foreach ($users_new as $key1 => $value1) {
                                       
                                        $test['user_id'] = $value1; 
                                        
                                        $test['name'] = $wpdb->get_col("
                                        SELECT value FROM {$bp->profile->table_name_data}
                                        WHERE field_id = 1 
                                        AND user_id = '".$value1."' 
                                        ");

                                        $test['value'] = $wpdb->get_col("
                                        SELECT value FROM {$bp->profile->table_name_data}
                                        WHERE $field 
                                        AND user_id = '".$value1."' 
                                        ");

                                        ?><pre><?php echo var_dump($test); ?></pre><?php
                                  }
                                 
                                  */                                            
                        
              } 
              else if ($id > 0) { 

                if ($value[0] !== '') {

                $users_new = array();

                switch ($like) {
                    case 'e':
                      $like = "= '%s'";
                      break;
                    case 'l':
                      $like = "LIKE '%s'";
                      break;
                    case 'c':
                      $like = "LIKE '%%%s%%'";
                      break;
                    case 'n':
                      $like = "= %d";
                      break;
                    case 'g':
                      $like = ">= %d";
                      break;
                    case 'i':
                      // to complete
                      $like = "= %d";
                      break;

                }

                $like = $wpdb->prepare("value ". $like, $value);
                $field = $wpdb->prepare("field_id = %s", $id);
                
                $users_new = $wpdb->get_col("
                                  SELECT user_id FROM {$bp->profile->table_name_data}
                                  WHERE $field 
                                  AND $like 
                                  ");
                }
              }
      

            }

         
            if (isset($users_new))
            {  $id;
              if (!isset($users_result)) { 
                $users_result = $users_new;
              } else{ 
                $users_result = array_intersect($users_result, $users_new);
              } 

              unset($users_new);  
            }  

          }            

      }

      return $users_result;
     }


     /**
     * get a list of user ids to include query
     *
     */
     
     private function query_members ($args) {
      return $this->get_users_by_codes($_GET);
    }




    /**
     * add a new visibility for admin only
     *
     */
    public function bp_xprofile_get_visibility_levels( $allowed_visibilities ) {
      
      $allowed_visibilities[self::INTERNAL_NAME] = array(
        'id'      => self::INTERNAL_NAME,
        'label'   => _x( 'Internal', 'Visibility level setting', 'bp-extended-profile-visibility' )
      );  
      
      return $allowed_visibilities;
    }
    
    public function bp_xprofile_get_hidden_field_types_for_user( $hidden_levels, $displayed_user_id, $current_user_id ) {

      if( ! is_super_admin() ) {
        
        $hidden_levels[] = self::INTERNAL_NAME; //profile field with this privacy level will be hidden for the user
        
      }
      
      return $hidden_levels;
      
    }
    
    /**
     * Force at least to have one service or product to sell for Seller
     * and one service or product to sell for buyer
     *
     * See if can make it more generic
     */
    public function xprofile_data_is_valid_field ($retvalue, $data) {
      
      // Explode the posted field IDs into an array so we know which
      // fields have been submitted.
      $posted_field_ids = wp_parse_id_list( $_POST['field_ids'] );
      // Is there any of the field we are looking for
      if (in_array ( self::ORGANISATION_PSEL, $posted_field_ids ) ) {
        $user_id = $data->user_id;
        
        $member_type = bp_get_member_type( $user_id );
        
        if ( ( $member_type == SheTrades_Member_Type::MEMBERTYPE_SELLER ) && empty( $_POST['field_' . self::ORGANISATION_PSEL] ) && empty( $_POST['field_' . self::ORGANISATION_SSEL] ) ) {
          bp_core_add_message( __( 'You to select at least one prodcut or one service to sell', SheTrades_Plugin::TEXTDOMAINNAME ), 'error' );
          bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_profile_slug() . '/edit/group/' . bp_action_variable( 1 ) ) );
          exit;
        }
        if ( ( $member_type == SheTrades_Member_Type::MEMBERTYPE_BUYER ) && empty( $_POST['field_' . self::ORGANISATION_PBUY] ) && empty( $_POST['field_' . self::ORGANISATION_SBUY] ) ) {
          bp_core_add_message( __( 'You to select at least one prodcut or one service to buy', SheTrades_Plugin::TEXTDOMAINNAME ), 'error' );
          bp_core_redirect( trailingslashit( bp_displayed_user_domain() . bp_get_profile_slug() . '/edit/group/' . bp_action_variable( 1 ) ) );
          exit;
        }
      }
      return true;
    }
    
    /**
     * Force call of __ to transalate the field name
     *
     */
    public function bp_get_the_profile_field_name ($name) {
    
      return __( $name );
    
    }
    
  }
}
