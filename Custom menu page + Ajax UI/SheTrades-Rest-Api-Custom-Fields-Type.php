<?php
/*
 * SheTrades-Rest-Api-Custom-Fields-Type.php
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
 *  This class allow to edit, list, retreive a single or all Custom-Fields-Types
 *    
 *
 */

require_once( 'SheTrades-Rest-Api-Base.php' );
  
if (!class_exists('SheTrades_Rest_Api_Custom_Fields_Type'))
{
  class SheTrades_Rest_Api_Custom_Fields_Type extends SheTrades_Rest_Api_Base {
    
    protected $customField;
    
    public function __construct() {
      parent::__construct(); 
      
      $this->rest_base = 'custom/field'; 
    }

    /**
     * Register the plugin routes.
     *
     */
    public function register_routes() {  

      register_rest_route( $this->namespace, '/' . $this->rest_base, array(
        // Get all threads
        array(
          'methods'             => WP_REST_Server::READABLE,
          'callback'            => array( $this, 'get_items' ),
          'permission_callback' => array( $this, 'get_items_permissions_check' ),
          'args'                => $this->get_collection_params(),
        ),
      ) );
        

      register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[a-zA-Z0-9]+)', array(
        array(
          'methods'             => WP_REST_Server::READABLE,
          'callback'            => array( $this, 'get_item' ),
          'permission_callback' => array( $this, 'get_item_permissions_check' ),
          'args'                => array(
            'context'           => $this->get_context_param( array( 'default' => 'view' ) ),
          ),
        ),
        'schema' => array( $this, 'get_public_item_schema' ),
      ) );


    }
    
    /**
     * Get the query params for collections of plugins.
     *
     * @return array
     */
    public function get_collection_params() {

      $params['search_terms'] = array(
        'description'       => __( 'Limit result set to items that match this search query.', SheTrades_Plugin::TEXTDOMAINNAME ),
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'validate_callback' => array($this, 'validate_callback'),
      );
      
      $params['data_type'] = array(
        'description'       => __( 'Type of data', SheTrades_Plugin::TEXTDOMAINNAME ),
        'type'              => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'validate_callback' => array($this, 'validate_callback'),
      );

      $params['limits'] = array(
        'description'       => __( 'Maximum number of results returned per result set.', SheTrades_Plugin::TEXTDOMAINNAME ),
        'default'           => 20,
        'type'              => 'integer',
        'sanitize_callback' => 'absint',
        'validate_callback' => array($this, 'validate_callback'),
      );

      return $params;
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
                  $listed[$pid][substr($id, 0, $first_segment_length + 2)][$id] = $id; // e.g, [021][02102][0210202] or [02][0200][020002]
                }
              }

              $names[$id] = $name;
            }



            /* Case if $id has 4 or 5 numbers (e.g, "0200" or "02102") */

    			  else if ((strlen($id) == 4) || (strlen($id) == 5)){        // Whether the $id has 4 or 5 numbers
    					
              if (((strlen($id) - 2) == strlen($data_type)) || (strlen($id) == strlen($data_type))){       // Size checking of the first segment ("02" or "021"). Have to be equal, given whole $data_type may have 4 or 2 numbers. 

                $first_segment_length = strlen($id) - 2;

                if (substr($data_type, 0, $first_segment_length) == substr($id, 0, $first_segment_length)) {	// if both values are equal ("021" and "021", or "02" and "02")
      						
                  $pid = substr($id, 0, $first_segment_length); // index is "021" or "02"
                  $listed[$pid][$id] = array();
      					}
              }

              $names[$id] = $name;
    				}



            /* Both 3- and 2-numbers $id are stored there */
    				else{  
    					
              $listed[$id] = array();

              $names[$id] = $name;
    				}

            // end foreach
          }

          $result['ids'] = $listed;
          $result['names'] = $names;

          return rest_ensure_response($result);
        }  
  
    }
    
    /**
     * Retrieve thread.
     *
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Request|WP_Error Plugin object data on success, WP_Error otherwise.
     */
    public function get_item( $request ) {
      
      $id = $request['id'];

      $items = $this->customField->get_list_by_id();

      if (isset($items[$id])) {
        
        $retval = array();
        $item = new stdclass;
        $item->name = $items[$id];
        $item->id = $id;
        $retval[] = $this->prepare_response_for_collection( $this->prepare_item_for_response( $item, $request ) );
        
        return rest_ensure_response( $retval );
      }
        
      return new WP_Error( 'rest_thread_invalid_id', __( 'Invalid thread id.' ), array( 'status' => 404 ) );
    }

    /**
     * Prepares item data for return as an object.
     *
     * @param stdClass $item Custom-Fields-Type data.
     * @param WP_REST_Request $request
     * @param boolean $is_raw Optional, not used. Defaults to false.
     * @return WP_REST_Response
     */
    public function prepare_item_for_response( $item, $request, $is_raw = false ) {
      
      $data = array(
        'id'            => $item->id,
        'name'         =>  $item->name,
      );

      $context = ! empty( $request['context'] ) ? $request['context'] : 'view';
      $data    = $this->add_additional_fields_to_object( $data, $request );
      $data    = $this->filter_response_by_context( $data, $context );

      $response = rest_ensure_response( $data );
      
      return $response;
    }

    

  }
}