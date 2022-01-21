<?php 
class Endeca {

  public function getCount($p_response_text) {
    $v_start_pos = strpos($p_response_text, "count");
    $v_end_pos = strpos($p_response_text, ">");
    if (($v_start_pos === false) || ($v_end_pos === false)) {
      return 0;
    } else {
      $v_count = substr($p_response_text, $v_start_pos+6, $v_end_pos-$v_start_pos-7);
      return trim($v_count, '"');
    }
  }

  public function getValue( $p_response, $p_key) {
    if ( ! is_array ($p_response)) {
      return null;
    }

    return $p_response[$p_key];
  }

  public function getSnippet( $p_snippet_name, &$p_response_text) {
    $v_snippet_preg = '/\<ene_delimiter[\r\s\t]+name="' . $p_snippet_name . '"[\r\s\t]*.*\>.*\<\/ene_delimiter[\r\s\t]*\>/isU';

    preg_match( $v_snippet_preg, $p_response_text, $a_match_list);

    $v_snippet = $a_match_list[0];

    return $v_snippet;
  }

  public function removeDelimiter( $p_snippet_name, &$p_response_text) {
    $v_ene_snippet_preg = '/\<ene_delimiter[\r\s\t]+name="' . $p_snippet_name . '"[\r\s\t]*.*\>(.*)\<\/ene_delimiter[\r\s\t]*\>/isU';

    preg_match( $v_ene_snippet_preg, $p_response_text, $va_match_list);

    $v_snippet = $va_match_list[1];

    return $v_snippet;
  }

  public function bridge( $p_request_array, $p_parameter_list = null) {
    $v_response = array();

    $p_request_array['referer'] = ( $GLOBALS['endeca_referer']
                                ? $GLOBALS['endeca_referer']
                                : $_SERVER['PHP_SELF'] );

    if ( ! is_array( $p_parameter_list))
      $p_parameter_list = array();

    //This will fix object reference errors thrown by Endeca UI
    if ( (isset( $p_request_array['n'])) && (!isset( $p_request_array['N'])) ) {
      $p_request_array['N'] = $p_request_array['n'];
      unset($p_request_array['n']);
    }

    // add a default N if no N or R value set
    if (( ! isset( $p_request_array['N'])) && ( ! isset( $p_request_array['R']))) {
      $p_request_array['N'] = 0;
    }

    // set a rollup-by value - this should be p_style_number
    $p_request_array['Nu'] = 'p_style_number';

    // these override the default values in the .aspx script
    // (the .aspx defaults to austsrvend002)
    $v_server_ene_map = array();

    if ( ! isset( $p_request_array['sid']))
      $p_request_array['sid'] = session_id();

    // generate the new url
    $v_url_list = array();

    foreach ($p_request_array as $v_key => $v_value) {
      $v_url_list[] = $v_key . '=' . urlEncode($v_value);
    }

    $v_new_url = ENDECA_SERVER_URL . '?' . join( '&', $v_url_list);

    // fread from the new URL
    $v_endeca_response_text = join( '', file($v_new_url));


    // return some metadata about the output
    $v_response['called_url'] = $v_new_url;
    $v_response['response'] = $v_endeca_response_text;
    // split the output into snippets
    $v_response['metadata'] = $this->getSnippet( 'metadata', $v_endeca_response_text);
    $v_response['left_nav_cat'] = $this->getSnippet( 'left_nav_cat', $v_endeca_response_text);
    $v_response['left_nav_player'] = $this->getSnippet( 'left_nav_player', $v_endeca_response_text);
    $v_response['top_nav'] = $this->getSnippet( 'top_nav', $v_endeca_response_text);
    $v_response['search_bar'] = $this->getSnippet( 'search_bar', $v_endeca_response_text);
    $v_response['search_box'] = $this->getSnippet( 'search_box', $v_endeca_response_text);
    $v_response['search_box_float'] = $this->getSnippet('search_box_float', $v_endeca_response_text);
    $v_response['bread_crumb'] = $this->getSnippet( 'bread_crumb', $v_endeca_response_text);
    $v_response['featured_products'] = $this->getSnippet( 'featured_products', $v_endeca_response_text);
    $v_response['hot_deals'] = $this->getSnippet( 'hot_deals', $v_endeca_response_text);
    $v_response['top_sellers'] = $this->getSnippet( 'top_sellers', $v_endeca_response_text);
    $v_response['top_sellers_float'] = $this->getSnippet( 'top_sellers_float', $v_endeca_response_text);

    $v_response['search_results'] = strstr($v_endeca_response_text,'<ene_delimiter name="search_results"');

    $_SESSION['s_last_search'] = $p_request_array;

    return $v_response;
  }

}
?>
