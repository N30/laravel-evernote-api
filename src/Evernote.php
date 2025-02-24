<?php

namespace N30\LaravelEvernoteApi;

class Evernote
{
    /** @var  string */
    protected $token;

    /** @var  boolean */
    protected $sandbox;

    /** @var  boolean */
    protected $china;

    /** @var  string */
    protected $key;

    /** @var  string */
    protected $secret;

    /** @var  string */
    protected $callback;

    protected $consumer_secret;

    protected $token_secret;

    protected $supportLinkedSandbox;

    protected $params = array();

    public function __construct($token=NULL,$oauthVerifier=NULL)
    {
        $this->sandbox              = 0;
        $this->supportLinkedSandbox = 0;//$supportLinkedSandbox;
        $this->china                = 0;//$china;

        $this->params['oauth_callback']         = null;
        $this->params['oauth_consumer_key']     = config('evernote.key');
        $this->params['oauth_nonce']            = $this->getOauthNonce();
        $this->params['oauth_signature']        = null;
        $this->params['oauth_signature_method'] = 'HMAC-SHA1';
        $this->params['oauth_timestamp']        = $this->getOauthTimestamp();
        $this->params['oauth_token']            = $token;
        $this->params['oauth_verifier']         = $oauthVerifier;
        $this->params['oauth_version']          = '1.0';

        $this->token_secret = '';

        $this->token    = $token;
        $this->sandbox  = config('evernote.sandbox');
        $this->china    = config('evernote.china');
        $this->key      = config('evernote.key');
        $this->secret   = config('evernote.secret');
        $this->callback = config('evernote.callback');
    }

   /* public function __construct($token = null)
    {
        $this->token    = $token;
        $this->sandbox  = config('evernote.sandbox');
        $this->china    = config('evernote.china');
        $this->key      = config('evernote.key');
        $this->secret   = config('evernote.secret');
        $this->callback = config('evernote.callback');
    }*/

    /**
     * @return string|null
     */
    public function authorize()
    {
        
        $oauth_handler = new \Evernote\Auth\OauthHandler($this->sandbox, false, $this->china);
        try {
            
            $oauth_data  = $oauth_handler->authorize($this->key, $this->secret, $this->getCallbackUrl());

            if (isset($oauth_data['oauth_token'])) {
                $this->token = $oauth_data['oauth_token'];
                $ret         = $this->token;

            } else {
                $ret = null;
 
                  
            }
        } catch (\Evernote\Exception\AuthorizationDeniedException $e) {
            //If the user decline the authorization, an exception is thrown.
            $ret = null;
        } catch (\Exception $e) {
            $ret = null;
        }

        return $ret;
    }

    /**
     * @return string
     */
    public function getCallbackUrl()
    {
        return url($this->callback);
    }

    /**
     * @param string $token
     *
     * @return array
     */
    public function notebookList($token)
    {
        $client = new \Evernote\Client($token, $this->sandbox, null, null, $this->china);

        $notebooks = $client->listNotebooks();

        return $notebooks;
    }

    

    public function authorize2()
    {
        $consumer_key = config('evernote.key');
        $consumer_secret = config('evernote.secret');
        $callback = config('evernote.callback');

        $this->params['oauth_callback']         = $callback;
        $this->params['oauth_consumer_key']     = $consumer_key;

        $this->consumer_secret = $consumer_secret;

        // first call
        if (!array_key_exists('oauth_verifier', $_GET) && !array_key_exists('oauth_token', $_GET)) {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            unset($this->params['oauth_token']);
            unset($this->params['oauth_verifier']);

            $temporaryCredentials = $this->getTemporaryCredentials();

            $_SESSION['oauth_token_secret'] = $temporaryCredentials['oauth_token_secret'];;

            $authorizationUrl = 'Location: '
                . $this->getBaseUrl('OAuth.action?oauth_token=')
                . $temporaryCredentials['oauth_token'];


            if ($this->supportLinkedSandbox) {
                $authorizationUrl .= '&supportLinkedSandbox=true';
            }
            header($authorizationUrl);
	        exit();

        // the user declined the authorization
        } elseif (!array_key_exists('oauth_verifier', $_GET) && array_key_exists('oauth_token', $_GET)) {
            throw new AuthorizationDeniedException('Authorization declined.');
        //the user authorized the app
        } else {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $this->token_secret = $_SESSION['oauth_token_secret'];

            $this->params['oauth_token']    = $_GET['oauth_token'];
            $this->params['oauth_verifier'] = $_GET['oauth_verifier'];
            unset($this->params['oauth_callback']);

            return $this->getTemporaryCredentials();
        }

    }

    protected function getBaseUrl($prefix = '')
    {
        $baseUrl = '';
        if (true === $this->sandbox) {
            $baseUrl = "https://sandbox.evernote.com";
        } elseif (true === $this->china) {
            $baseUrl = "https://app.yinxiang.com";
        } else {
            $baseUrl = "https://www.evernote.com";     
        }
        $baseUrl .= $prefix == '' ? '' : '/' . $prefix;   

        return $baseUrl;     
    }

    public function params()
    {
        $headers = array();
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        $headers['Authorization'] = $this->getAuthorizationHeaderString();
 
        return ($this->params);

    }
    public function getTemporaryCredentials()
    {
        $headers = array();
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        $headers['Authorization'] = $this->getAuthorizationHeaderString();

        $arguments = array();

        $handle  = curl_init(); 
        curl_setopt_array($handle, array(
            CURLOPT_POST           => true,
            CURLOPT_URL            => $this->getBaseUrl('oauth'),
            CURLOPT_HTTPHEADER     => $this->formatHeaders($headers),
            CURLOPT_POSTFIELDS     => http_build_query($arguments, '', '&'),
            CURLOPT_HEADER         => true,
            CURLOPT_RETURNTRANSFER => true
        ));

        $raw = curl_exec($handle);

        # close curl handle
        curl_close($handle);

        $responseBody = $this->getResponseBody($raw);

        parse_str($responseBody, $parts);

        return $parts;
    }

    protected function getResponseBody($raw)
    {
        list($headers, $text) = explode("\r\n\r\n", $raw, 2);
        if (strpos($headers, ' 100 Continue') !== false) {
            list(, $text) = explode("\r\n\r\n", $text, 2);
        }

        return $text;
    }

    protected function getOauthNonce()
    {
        return md5(mt_rand());
    }

    protected function getOauthTimestamp()
    {
        return time();
    }

    protected function getOauthSignature()
    {
        $baseString   = $this->getSignatureBaseString();
        $signatureKey = $this->getSignatureKey();

        $oauth_signature = base64_encode(
            hash_hmac(
                'sha1',
                $baseString,
                $signatureKey,
                true
            )
        );

        return rawurlencode($oauth_signature);
    }

    protected function getSignatureBaseString()
    {
        $params = $this->params;
        unset($params['oauth_signature']);

        if (array_key_exists('oauth_callback', $params)) {
            $params['oauth_callback'] = rawurlencode($params['oauth_callback']);
        }

        return 'POST&' . rawurlencode($this->getBaseUrl('oauth')) .'&' . rawurlencode($this->formatParametersString($params, '&'));
    }

    protected function formatParametersString($params, $glue, $enclosure = '')
    {
        $result = array();
        foreach ($params as $key => $value) {
            $result[] = $key . '=' . $enclosure . $value . $enclosure;
        }

        return implode($glue, $result);
    }

    protected function getAuthorizationHeaderString()
    {
        $params = $this->params;
        $params['oauth_signature'] = $this->getOauthSignature();
        $this->params['oauth_signature'] = $params['oauth_signature'];  

        return 'OAuth ' . $this->formatParametersString($params, ', ', '"');
    }

    protected function getSignatureKey()
    {
        return $this->consumer_secret . '&' . $this->token_secret;
    }

    protected function formatHeaders($headers)
    {
        $result = array();
        foreach($headers as $key => $value) {
            $result[] = "$key: $value";
        }

        return $result;
    }
     
}