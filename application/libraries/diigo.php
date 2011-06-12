<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Diigo library for CodeIgniter
 * 
 * Save this file under applications/libraries
 * 
 * At the time this library was written there were only two operations
 * available in Diigo API:
 * 
 * - Get Bookmarks
 * - Post Bookmark
 * 
 * See http://www.diigo.com/tools/api for more information
 * 
 * @package diigo
 * @author Julian
 * @copyright 2011
 * @version $Id$
 * @access public
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
class diigo
{
    private $ci;
    
    private $api_url = "http://secure.diigo.com/api/v2/";
    private $api_url_ssl = "https://secure.diigo.com/api/v2/";
    
    private $format = 'json';
    
    private $response;
    
    private $username;
    
    private $password;
    
    private $use_ssl = FALSE;
    
    private $debug = TRUE;
    
    public function __construct($params = null)
    {
        // do something with params
        
        $this->ci =& get_instance();
        
        $this->ci->load->config('diigo');

        // config values
        
        $this->username = $this->ci->config->item('diigo_username');
        
        $this->password = $this->ci->config->item('diigo_password');
        
        $use_ssl = $this->ci->config->item('diigo_use_ssl');
        if (isset($use_ssl)) 
        {
            if ($this->debug) log_message('debug', 'Diigo: using SSL ' . $this->api_url_ssl );
            $this->use_ssl = $use_ssl;
        }
    }
    
    /**
     * diigo::get_bookmarks()
     * 
     * Get bookmarks from your Diigo account.
     * 
     * params is an arraw containing values in 
     * http://www.diigo.com/tools/api
     * 
     * 
     * 
     * @return
     */
    public function get_bookmarks($params = array() )
    {
        $method = 'bookmarks';
        $type = 'GET';
        
        $this->_call( $method, $type, $params );
        
        return $this->response;
    }
    
    /**
     * diigo::post_bookmark()
     * 
     * Post a new URL to Diigo.
     * 
     * Notice that if the URL already exists in your account, it will return a successful message but
     * the link won't be replaced with your new info.
     * 
     * params is an array containing values as described in the documentation
     * http://www.diigo.com/tools/api
     * 
     * @return
     */
    public function post_bookmark($params = array() )
    {
        $method = 'bookmarks';
        $type = 'POST';
        
        $this->_call( $method, $type, $params );
        
        return $this->response;
    }
    
    private function _call($method, $type, $params)
    {
        // Add in the primary login and apiKey
		//$params = array_merge(array('login' => $this->username), $params);
		
		// Create the argument string
		$url = $this->api_url . $method;
		
        $fields = "?";
        
		foreach ($params as $key => $value)
		{
			if (!is_array($value))
			{
				$fields .= http_build_query(array($key => $value)) . '&';
			}
			else
			{
				foreach ($value as $sub)
				{
					$fields .= http_build_query(array($key => $sub)) . '&';
				}
			}
		}
        
        $credentials = $this->username . ":" . $this->password; 
        
        $headers = array( 
            //"Content-type: text/xml;charset=\"utf-8\"", 
            "Authorization: Basic " . base64_encode($credentials) 
        );
        
        if ($this->debug) log_message('debug', 'Diigo: curl url '. $url );
        if ($this->debug) log_message('debug', 'Diigo: type '. $type );
        if ($this->debug) log_message('debug', 'Diigo: username '. $this->username );
		
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 50);
		curl_setopt($curl, CURLOPT_URL, $url );
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        
        if (strtoupper($type) == 'POST')
        {
            curl_setopt($curl, CURLOPT_POST, TRUE);  
            curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);    // put fields in body
            
            if ($this->debug) log_message('debug', 'Diigo: curl url params '. $fields );
            
        } else if (strtoupper($type) == 'GET')
        {
            $url = $url.$fields;    // put fields in the URL
            if ($this->debug) log_message('debug', 'Diigo: curl url with params '. $url );
            
            curl_setopt($curl, CURLOPT_URL, $url );
            
        } else 
        {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($type) );
            curl_setopt($curl, CURLOPT_POSTFIELDS, $fields);    // put fields in body
        } 

        $response = curl_exec($curl);

        if (curl_errno($curl)> 0)
        {
            log_message('error', 'Diigo: curl_exec() Error ' . curl_errno($curl) );
        } else {
            log_message('error', 'Diigo: curl_exec() OK!');
            curl_close($curl);
        }

		if ($response)
		{
            if ($this->debug) log_message('debug', 'Diigo: curl_exec() response '.print_r($response, TRUE)  );
          
			$this->response = ($this->format == 'json') ? json_decode($response, TRUE) : $response;
			return TRUE;
		}
		else
        {
            if ($this->debug) log_message('error', 'Diigo: curl_exec() failed to get response ');
            
            return FALSE; 
        }
        
    }
    
}

/* End of file diigo.php */