<?php
/*
 * SheTrades-Rest-Api-Custom-Fields-Type-Services.php
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
 *  This class allow to list, retreive a single or all Custom Fields Type Services
 *    
 *
 */

require_once( 'SheTrades-Rest-Api-Custom-Fields-Type.php' );
  
if (!class_exists('SheTrades_Rest_Api_Custom_Fields_Type_Services'))
{
  class SheTrades_Rest_Api_Custom_Fields_Type_Services extends SheTrades_Rest_Api_Custom_Fields_Type {
    
    public function __construct() {
      parent::__construct(); 
      
      $this->rest_base = 'custom/field/service';
      $this->customField = new SheTrades_Xprofile_Custom_Fields_Type_Services();
      
    }
 



    /**
     * Retrieve fields.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Request List of thread object data.
     */
    public function get_items($request) {
     
      $search_terms = $request['search_terms'];
      $limits = $request['limits'];
      $data_type = $request['data_type'];
      
      $retval = array();
      $result = array();
      $names = array();
      
      $items = $this->customField->get_list_by_id();
      $cat = '-1';
      
        if (!empty($search_terms)) {

          $limits = 500;

          foreach ($items as $id => $name) {
         
            if(preg_match("/" . preg_quote($search_terms, '/') . "/i", $name)) {
              
              $item = new stdclass;
              $item->name = $name;
              $item->id = $id;
            
              $retval[] = $this->prepare_response_for_collection( $this->prepare_item_for_response( $item, $request ) );
            
              $limits--;
              if ($limits <= 0) {
                break;
              }

            } 
            // end foreach          
          }

          return rest_ensure_response($retval);
        }

        else {

          foreach ($items as $id => $name) {


            /* Case if $id has 6 or 7 numbers (e.g, "020002" or "0210202") */

            if (strlen($id) > 5){        // Whether the $id has 6 or 7 numbers

              if ((strlen($id) - 2) == strlen($data_type)){  // Size checking of the first segment ("02" or "021"). Have to be equal, given whole $data_type may have 4 or 2 numbers. Also checking that $data_type >= 4

                $first_segment_length = strlen($id) - 4;

                

                if (substr($data_type, 0, $first_segment_length + 2) == substr($id, 0, $first_segment_length + 2)) {  // if both values are equal ("02102" and "02102", or "0200" and "0200")
                  
                  $pid = substr($id, 0, $first_segment_length); // index is "021" or "02"
                  $listed[$cat][$pid][substr($id, 0, $first_segment_length + 2)][$id] = $id; // e.g, [021][02102][0210202] or [02][0200][020002]
                  $selected_id = $pid; 
                }
              }
            }



            /* Case if $id has 4 or 5 numbers (e.g, "0200" or "02102") */

            else if ((strlen($id) == 4) || (strlen($id) == 5)){        // Whether the $id has 4 or 5 numbers
              
              if (((strlen($id) - 2) == strlen($data_type)) || (strlen($id) == strlen($data_type))){       // Size checking of the first segment ("02" or "021"). Have to be equal, given whole $data_type may have 4 or 2 numbers. 

                $first_segment_length = strlen($id) - 2;

                if (substr($data_type, 0, $first_segment_length) == substr($id, 0, $first_segment_length)) {  // if both values are equal ("021" and "021", or "02" and "02")
                  
                  $pid = substr($id, 0, $first_segment_length); // index is "021" or "02"
                  $listed[$cat][$pid][$id] = array();
                  $selected_id = $pid;
                }
              }
            }

            // Условие на символы
            
            else if (strlen($id) == 1) {
              $cat = $id;
            }

            /* Both 3- and 2-numbers $id are stored there */
            else{  
              
              $listed[$cat][$id] = array();
            }

            $names[$id] = $name;
            
            // end foreach
          }

          foreach ($listed as $key => $value) {

              if ($key === $data_type) {continue;}
              
              if (!isset($value[$selected_id])) {
                $listed[$key] = array();
              }
          }

          $result['ids'] = $listed;
          $result['names'] = $names;

          return rest_ensure_response($result);
        }  
  
    }


    /**
     * Check if a given request has access to request items.
     * We do not request any login / password to access to this field
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|bool
     */
    public function get_items_permissions_check( $request ) {
      
      return true;
    }
    

  }
}