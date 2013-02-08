<?php
/// API client for 23 Video.

/// Provides signing of requests sent either as GET requests or URL encoded 
/// POST requests.
class VisualVideo
{
    private $_host; ///< Host of the 23 Video site.
    private $_consumerKey; ///< Consumer key.
    private $_consumerSecret; ///< Consumer secret.
    private $_accessToken; ///< Access token.
    private $_accessTokenSecret; ///< Access token secret.
    
    
    /// Initialize an API client.
    
    /// @param host Host of the 23 Video site including protocol such as 
    /// http://reinvent.23video.com.
    /// @param consumerKey Consumer key.
    /// @param consumerSecret Consumer secret.
    /// @param accessToken Access token.
    /// @param accessToeknSecret Access token secret.
    public function __construct($host, 
                                $consumerKey, 
                                $consumerSecret, 
                                $accessToken, 
                                $accessTokenSecret)
    {
        $this->_host = $host;
        $this->_consumerKey = $consumerKey;
        $this->_consumerSecret = $consumerSecret;
        $this->_accessToken = $accessToken;
        $this->_accessTokenSecret = $accessTokenSecret;
    }
    
    
    /// Generate a random alphanumeric string.
    
    /// @param length Number of random octets to generate.
    /// @returns a random alphanumeric string of the specified length.
    private function _randomAlphaNumeric($length)
    {
	$allowed = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";	
	$allowedCount = strlen($allowed);

        $result = '';
        while ($length--)
            $result .= $allowed[rand(0, $allowedCount - 1)];
	return $result;
    }
    
    
    /// Sign a string with HMAC SHA1.
    
    /// Implemented directly from http://laughingmeme.org/code/hmacsha1.php.txt.
    /// 
    /// @param value Value to sign.
    /// @param secret Secret to use for HMAC signing.
    private function _hmacSha1($value, 
                               $secret)
    {
        $blocksize=64;
        $hashfunc='sha1';
        if (strlen($secret)>$blocksize)
            $secret=pack('H*', $hashfunc($secret));
        $secret=str_pad($secret,$blocksize,chr(0x00));
        $ipad=str_repeat(chr(0x36),$blocksize);
        $opad=str_repeat(chr(0x5c),$blocksize);
        $hmac = pack(
            'H*',$hashfunc(
                ($secret^$opad).pack(
                    'H*',$hashfunc(
                        ($secret^$ipad).$value
                        )
                    )
                )
            );
        return base64_encode($hmac);
    }
    

    /// Encode a URL, following the specs of OAuth signing
    
    /// @param string String to sign.
    private function _urlencode($string)
    {
        $signedString = urlencode($string);
        $signedString = str_replace('%2d', '-', $signedString);
        $signedString = str_replace('%5f', '_', $signedString);
        $signedString = str_replace('%2e', '.', $signedString);
        $signedString = str_replace('%7e', '~', $signedString);
        $signedString = str_replace('+', '%20', $signedString);
        return($signedString);
    }

    
    /// Sign a request.
    
    /// @param method HTTP method.
    /// @param request Request object.
    /// @param endpoint Endpoint URI.
    /// @param parameters Parameters to include in the request.
    private function _signRequest($method, 
                                  $request, 
                                  $endpoint, 
                                  $parameters)
    {
        // Determine the current timestamp.
        $timestamp = time();
        
        // Determine a nonce.
        $nonce = $this->_randomAlphaNumeric(16);
        
        // Add the OAuth specific parameters.
        $parameters["oauth_consumer_key"] = $this->_consumerKey;
        $parameters["oauth_token"] = $this->_accessToken;
        $parameters["oauth_signature_method"] = "HMAC-SHA1";
        $parameters["oauth_timestamp"] = $timestamp;
        $parameters["oauth_nonce"] = $nonce;
        $parameters["oauth_version"] = "1.0";
        
        // Sort the parameters by name.
        ksort($parameters);
        
        // Concatenate all parameters into an escaped string.
        $concatenatedParameters = "";
        
        foreach ($parameters as $k => $v)
            $concatenatedParameters .= ($concatenatedParameters == '' ? '' : '&') . $this->_urlencode($k) . '=' . $this->_urlencode($v);
        
        // Sign the signature base string.
        $signatureBaseString = $method . "&" . $this->_urlencode($this->_host . $endpoint) . "&" . $this->_urlencode($concatenatedParameters);
        $signatureKey = $this->_consumerSecret . "&" . $this->_accessTokenSecret;
        $signature = $this->_hmacSha1($signatureBaseString, 
                                      $signatureKey);
        
        curl_setopt($request,
                    CURLOPT_HTTPHEADER,
                    array('Authorization: OAuth realm="http://' . $this->_host . '", ' . 
                          'oauth_consumer_key="' . $this->_urlencode($this->_consumerKey) . '", ' .
                          'oauth_token="' . $this->_urlencode($this->_accessToken) . '", ' . 
                          'oauth_signature_method="HMAC-SHA1", ' . 
                          'oauth_signature="' . $this->_urlencode($signature) . '", ' . 
                          'oauth_timestamp="' . $timestamp . '", ' .
                          'oauth_nonce="' . $nonce . '", ' .
                          'oauth_version="1.0"'
                        ));
    }
    
    
    /// Perform a GET request.
    
    /// @param endpoint URL endpoint starting with the root slash.
    /// @param parameters Request parameters.
    /// @returns a string containing the body of the response.
    public function get($endpoint, 
                        $parameters = array())
    {
        // Construct the URL.
        $url = $this->_host . $endpoint;
        $first = true;
        foreach ($parameters as $k => $v)
        {
            $url .= ($first ? '?' : '&') . $this->_urlencode($k) . '=' . $this->_urlencode($v);
            $first = false;
        }
        
        // Set up the request, sign it and run with it.
        $request = curl_init($url);
        $this->_signRequest('GET', 
                            $request, 
                            $endpoint, 
                            $parameters);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); 
        $response = curl_exec($request);
        curl_close($request);
        return $response;
    }
    
    
    /// Perform a URL encoded POST request.
    
    /// @param endpoint URL endpoint starting with the root slash.
    /// @param parameters Request parameters.
    /// @returns a string containing the body of the response.
    public function post($endpoint, 
                         $parameters = array())
    {
        // Construct the POST data.
        $url = $this->_host . $endpoint;
        
        $data = '';
        foreach ($parameters as $k => $v)
            $data .= ($data == '' ? '' : '&') . $this->_urlencode($k) . '=' . $this->_urlencode($v);
        
        // Set up the request, sign it and run with it.
        $request = curl_init($url);
        curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($request, CURLOPT_POST, count($parameters));
        curl_setopt($request, CURLOPT_POSTFIELDS, $data);
        $this->_signRequest('POST', 
                            $request, 
                            $endpoint, 
                            $parameters);
        $response = curl_exec($request);
        curl_close($request);
        return $response;
    }
}
?>