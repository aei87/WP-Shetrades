<?php
/*
 * SheTrades_Options.php
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
 *  2017/04/02  E.Abdullin      Initial implementation
 *
 *  Description:
 *
 *  This class contains all the Shetrades options
 *    
 *
 */
 

if (!class_exists('SheTrades_Options')) {
  
  class SheTrades_Options {
    
  
    public function __construct () {

    }

    /**
     * Custom initialization
     *
     */


    /**
     * SendEmails options
     *
     *
     */

    static public function SendEmails() {
        
        
        $options = new stdClass;

        $options->filters[1]['label'] = 'Name';
        $options->filters[1]['id'] = '1';
        $options->filters[1]['type'] = 'string';

        $options->filters[2]['label'] = 'Country';
        $options->filters[2]['id'] = '17';
        $options->filters[2]['type'] = 'string';

        $options->filters[3]['label'] = 'Number of Female Employees';
        $options->filters[3]['id'] = '21';
        $options->filters[3]['type'] = 'numeric';

        $options->filters[4]['label'] = 'Number of Employees';
        $options->filters[4]['id'] = '50';
        $options->filters[4]['type'] = 'numeric';

        $options->filters[5]['label'] = 'Membership type';
        $options->filters[5]['id'] = '-1';
        $options->filters[5]['type'] = 'membership';

        $options->from_address = get_bloginfo('admin_email');
        $options->from_name = get_bloginfo('name'); 

        $options->bp_taxonomy_name = 'bp_member_type';

        return $options;
    }
    
 
   
    /**
     * ExportUsers options
     *
     *
     */

    static public function ExportUsers() {
              
        $options = new stdClass;



        // Country
        
        $options->filters[$i]['label'] = 'Are in this country';
        $options->filters[$i]['id'] = 'field_c17';
        $options->filters[$i]['name'] = 'field_c17';    
        $options->filters[$i]['type'] = 'simple';

        ob_start();
        SheTrades_Profile::dump_field(SheTrades_Profile::FIELDTYPE_COUNTRY, SheTrades_Profile::ORGANISATION_CTRY, '');
        $options->filters[$i]['value'] = ob_get_contents();
        ob_end_clean();     
        $i++;


        // Verified
        
        $options->filters[$i]['label'] = 'Is verified member';
        $options->filters[$i]['id'] = 'field_verified';
        $options->filters[$i]['name'] = 'field_verified';
        $options->filters[$i]['type'] = 'simple';

        ob_start();

        ?>
          <select name="field_verified" class="pure-u-23-24 selectator">
            <option value="0" <?php if ( isset ( $_GET [ 'field_verified' ] ) && ( $_GET [ 'field_verified' ] == '0') ) echo "selected"; ?>><?php _e('---' , 'shetrades_theme'); ?></option>
            <option value="1" <?php if ( isset ( $_GET [ 'field_verified' ] ) && ( $_GET [ 'field_verified' ] == '1') ) echo "selected"; ?>><?php _e('Yes' , 'shetrades_theme'); ?></option>
          </select>

        <?php

        $options->filters[$i]['value'] = ob_get_contents();
        ob_end_clean();     
        $i++;



        // Sell these products
        
        $options->filters[$i]['label'] = 'Sell these products';
        $options->filters[$i]['id'] = 'field_c51';
        $options->filters[$i]['name'] = 'field_c51[]';
        $options->filters[$i]['type'] = 'multiple';

        ob_start();
        SheTrades_Profile::dump_field_multi_ajax(SheTrades_Profile::FIELDTYPE_PRODUCT, SheTrades_Profile::ORGANISATION_PSEL, '');
        $options->filters[$i]['value'] = ob_get_contents();
        ob_end_clean();     
        $i++;


        // Buy these products
        
        $options->filters[$i]['label'] = 'Buy these products';
        $options->filters[$i]['id'] = 'field_c53';
        $options->filters[$i]['name'] = 'field_c53[]';
        $options->filters[$i]['type'] = 'multiple';

        ob_start();
        SheTrades_Profile::dump_field_multi_ajax(SheTrades_Profile::FIELDTYPE_PRODUCT, SheTrades_Profile::ORGANISATION_PBUY, '');
        $options->filters[$i]['value'] = ob_get_contents();
        ob_end_clean();
        $i++;        

        
        // Offer these services
        
        $options->filters[$i]['label'] = 'Offer these services';
        $options->filters[$i]['id'] = 'field_c52';
        $options->filters[$i]['name'] = 'field_c52[]';
        $options->filters[$i]['type'] = 'multiple';

        ob_start();
        SheTrades_Profile::dump_field_multi_ajax(SheTrades_Profile::FIELDTYPE_SERVICE, SheTrades_Profile::ORGANISATION_SSEL, '');
        $options->filters[$i]['value'] = ob_get_contents();
        ob_end_clean();     
        $i++;

        
        // Buy these services
        
        $options->filters[$i]['label'] = 'Buy these services';
        $options->filters[$i]['id'] = 'field_c54';
        $options->filters[$i]['name'] = 'field_c54[]';
        $options->filters[$i]['type'] = 'multiple';

        ob_start();
        SheTrades_Profile::dump_field_multi_ajax(SheTrades_Profile::FIELDTYPE_SERVICE, SheTrades_Profile::ORGANISATION_SBUY, '');
        $options->filters[$i]['value'] = ob_get_contents();
        ob_end_clean();    
        $i++;



        $options->from_address = get_bloginfo('admin_email');
        $options->from_name = get_bloginfo('name'); 

        $options->bp_taxonomy_name = 'bp_member_type';

        return $options;
    }

  }

}









class SheTrades_Profile {
  
  // !! Hardcoded base on the ID in the database !!
  const ORGANISATION_NAME = 1;    //Organisation Name
  const ORGANISATION_TYPE = 167;  //Organisation Type
  const ORGANISATION_SIZE = 168;  //Organisation Size
  const ORGANISATION_DESC = 41;  //Company Description
  const ORGANISATION_REPN = 4;  //Name of business representative
  const ORGANISATION_REPT = 5;  //Title of Representative
  const ORGANISATION_REPJ = 45;  //Job Title of Representative
  const ORGANISATION_HEAD = 46;  //Is your company headed by a woman?
  const ORGANISATION_PERC = 49;  //Percentage of the business owned by Women
  const ORGANISATION_NEMP = 50;  //Number of Employees
  const ORGANISATION_FEMP = 21;  //Number of Female Employees
  const ORGANISATION_FMAN = 139;  //Number of Women in Management Team
  const ORGANISATION_PSEL = 51;  //Product am selling
  const ORGANISATION_SSEL = 52;  //Services am offering / selling
  const ORGANISATION_PBUY = 53;  //Products am buying
  const ORGANISATION_SBUY = 54;  //Services am buying
  const ORGANISATION_VERI = 55;  //Request verification if your company is a member of the following networks
  const ORGANISATION_INTE = 100;  //What is your main interest at SheTrades?
  const ORGANISATION_EXPT = 29;  //Annual value of exports in USD for most recent financial year
  const ORGANISATION_EXPX = 107;  //Do you have exporting experience?
  const ORGANISATION_EXPY = 30;  //If so, how many years have you been exporting?
  const ORGANISATION_EXPC = 31;  //Which countries do you export to?
  const ORGANISATION_PORT = 110;  //Where do you export from? (enter the port)
  const ORGANISATION_PRIC = 32;  //Please indicate your primary customers
  const ORGANISATION_TURN = 28;  //Annual value of sales in USD for most recent financial year
  const ORGANISATION_CTRY = 17;  //Country
  const ORGANISATION_ADDR = 15;  //Address
  const ORGANISATION_CITY = 16;  //City
  const ORGANISATION_ZIPC = 13;  //Zip code
  const ORGANISATION_PHON = 18;  //Telephone
  const ORGANISATION_FAXN = 19;  //Fax
  const ORGANISATION_WURL = 2;  //Website URL
  const ORGANISATION_YEAR = 20;  //Year of Establishment
  const ORGANISATION_CERT = 22;  //Certifications
  const ORGANISATION_FACB = 104;  //Facebook
  const ORGANISATION_TWIT = 105;  //Twitter
  const ORGANISATION_LINK = 106;  //LinkedIn
  const ORGANISATION_LEAD = 111;  //Lead Time in days
//  const ORGANISATION_NAME = 1;  //Organisation Name
  
  const FIELDTYPE_CHECKBOX       = 'checkbox'  ; //BP_XProfile_Field_Type_Checkbox
  const FIELDTYPE_DATEBOX        = 'datebox'  ; //BP_XProfile_Field_Type_Datebox
  const FIELDTYPE_MULTISELECTBOX = 'multiselectbox'; //BP_XProfile_Field_Type_Multiselectbox
  const FIELDTYPE_NUMBER         = 'number'  ; //BP_XProfile_Field_Type_Number
  const FIELDTYPE_URL            = 'url'  ; //BP_XProfile_Field_Type_URL
  const FIELDTYPE_RADIO          = 'radio'  ; //BP_XProfile_Field_Type_Radiobutton
  const FIELDTYPE_SELECTBOX      = 'selectbox'  ; //BP_XProfile_Field_Type_Selectbox
  const FIELDTYPE_TEXTAREA       = 'textarea'  ; //BP_XProfile_Field_Type_Textarea
  const FIELDTYPE_TEXTBOX        = 'textbox'  ; //BP_XProfile_Field_Type_Textbox
  const FIELDTYPE_COUNTRY        = 'country'  ; //SheTrades_Xprofile_Custom_Fields_Type_Countries
  const FIELDTYPE_CUSTOMER       = 'customer'  ; //SheTrades_Xprofile_Custom_Fields_Type_Customers
  const FIELDTYPE_CERTIFICATION  = 'certification' ; //SheTrades_Xprofile_Custom_Fields_Type_Certifications
  const FIELDTYPE_MULTICOUNTRY   = 'multicountry' ; //SheTrades_Xprofile_Custom_Fields_Type_Multicountries
  const FIELDTYPE_PRODUCT        = 'product'  ; //SheTrades_Xprofile_Custom_Fields_Type_Products
  const FIELDTYPE_SERVICE        = 'service'  ; //SheTrades_Xprofile_Custom_Fields_Type_Services
  const FIELDTYPE_VERIFIER       = 'verifier'  ; //SheTrades_Xprofile_Custom_Fields_Type_Verifiers
  const FIELDTYPE_TYPE           = 'type'  ; //SheTrades_Xprofile_Custom_Fields_Type_Organisation_Types
  const FIELDTYPE_SIZE           = 'size'  ; //SheTrades_Xprofile_Custom_Fields_Type_Organisation_Sizes

  
  
  private $shetrades_bp_profile;
  
  public function __construct($user_id = null) {
    //print_r(BP_XProfile_ProfileData::get_all_for_user( bp_get_member_user_id() ) );
    //$shetrades_bp_profile = BP_XProfile_ProfileData::get_all_for_user( bp_get_member_user_id() );
    //print_r($shetrades_bp_profile);
    
    // We put all the date into an array which will be easier to manipulate
    $this->shetrades_bp_profile = array ( );
    while ( bp_profile_groups ( ) ) {  
    
      bp_the_profile_group ( ); 
      if ( bp_profile_group_has_fields ( ) ) {
        
        $key = bp_get_the_profile_group_name();
        while ( bp_profile_fields ( ) ) {
          
          bp_the_profile_field ( ); 
          if ( bp_field_has_data() ) {
            global $field; 
            // [id] => 167
            // [group_id] => 1
            // [parent_id] => 0
            // [type] => type
            // [name] => Organisation Type
            // [description] => 
            // [is_required] => 1
            // [can_delete] => 1
            // [field_order] => 3
            // [option_order] => 0
            // [order_by] => custom
            // [is_default_option] => 0
            // [default_visibility:protected] =>             
            $info = new stdClass;
            $info->name = bp_get_the_profile_field_name ( );
            $info->value = trim( strip_tags (bp_get_the_profile_field_value ( ), "<a>" ) );
            $info->id = $field->id;
            // We store it twice in case we want to get it from the name
            $this->shetrades_bp_profile[$info->id] = $info;
            $this->shetrades_bp_profile[$info->name] = $info;
          }
        }
      }
    }
    //print_r($this->shetrades_bp_profile);
  }
  
  public function get_formated_value ($id_or_name, $default_value = "", $before = "", $after = "") {
    if ( isset ( $this->shetrades_bp_profile[$id_or_name] ) && !empty ( $this->shetrades_bp_profile[$id_or_name]->value ) ) {
      return $before . number_format_i18n ( $this->shetrades_bp_profile[$id_or_name]->value ) . $after;
    }
    return $default_value;
  }
  
  public function get_value ($id_or_name, $default_value = "", $before = "", $after = "") {
    if ( isset ( $this->shetrades_bp_profile[$id_or_name] ) && !empty ( $this->shetrades_bp_profile[$id_or_name]->value ) ) {
      return $before . $this->shetrades_bp_profile[$id_or_name]->value . $after;
    }
    return $default_value;
  }
  
  public function get_values ($id_or_name, $default_value = "", $before = "", $after = "") {
    if ( isset ( $this->shetrades_bp_profile[$id_or_name] ) && !empty ( $this->shetrades_bp_profile[$id_or_name]->value ) ) {
      $string = "";
      $list = explode (',', $this->shetrades_bp_profile[$id_or_name]->value );
      foreach ($list as $entry) {
        $string .='<span class="round-selection">' . trim( $entry ). '</span> ';
      }
      
      return $before . $string . $after;
    }
    return $default_value;
  }
  
  static public function dump_field( $type, $id, $default_values, $search_type = "c" ) {
    
    echo '<select name="field_' . $search_type . $id .'" class="pure-u-23-24 selectator">' . "\n";

    $key = 'field_' . $search_type . $id;
    
    if ( ! empty ( $default_values ) ) { 
      $_POST['field_' . $id ] = $default_values;
    } else if ( isset ( $_GET [ $key ] ) ) {
      $_POST['field_' . $id ] = $_GET [ $key ];
    }
    $field_type = bp_xprofile_create_field_type( $type );
    $field_type->field_obj = new stdClass;
    $field_type->field_obj->id = $id;
    $field_type->edit_field_options_html( array ( 'user_id' => '' ) );
    echo '</select>' . "\n";
    
  }
  
  static public function dump_field_multi( $type, $id, $default_values, $search_type = "c" ) {
    
    echo '<select  multiple="multiple" name="field_' . $search_type . $id .'[]" class="pure-u-23-24 selectator">' . "\n";

    $key = 'field_' . $search_type . $id;
    
    if ( ! empty ( $default_values ) ) { 
      $_POST['field_' . $id ] = $default_values;
    } else if ( isset ( $_GET [ $key ] ) ) {
      $_POST['field_' . $id ] = $_GET [ $key ];
    }
    $field_type = bp_xprofile_create_field_type( $type );
    $field_type->field_obj = new stdClass;
    $field_type->field_obj->id = $id;
    $field_type->edit_field_options_html( array ( 'user_id' => '' ) );

    echo '</select>' . "\n";
    
  }
  
  static public function dump_field_multi_ajax( $type, $id, $default_values, $search_type = "c" ) {
    
    echo '<select  multiple="multiple" name="field_' . $search_type . $id .'[]" class="pure-u-23-24 select2">' . "\n";
    
    $key = 'field_' . $search_type . $id;
    
    if ( isset ( $_GET [ $key ] ) && is_array ( $_GET [ $key ] ) ) { 
      $field_type = bp_xprofile_create_field_type( $type );
      $field_type->field_obj = new stdClass;
      $field_type->field_obj->id = $id;
      $list = $field_type->get_list_by_id();
      foreach ( $_GET [ $key ] as $id ) {
        if ( isset($list [ $id ] ) ) {
          echo '<option value="' . $id . '" selected>' . $list [ $id ] . '</option>';
        }
      }
    }
    echo '</select>' . "\n";
    
  }
  
}

