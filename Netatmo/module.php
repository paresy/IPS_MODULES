<?
	class Netatmo extends IPSModule
	{
		
		public function Create()
		{
			//Never delete this line!
			parent::Create();
			
			$this->RegisterPropertyString("username", "");
			$this->RegisterPropertyString("password", "");
			$this->RegisterPropertyString("client_id", "");
			$this->RegisterPropertyString("client_secret", "");
			$this->RegisterPropertyBoolean("debug", true);
		}
	
		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
			
			Update();

		}
		
		public function Update()
		{


			$username = $this->ReadPropertyString("username");
			$password = $this->ReadPropertyString("password");
			$client_id = $this->ReadPropertyString("client_id");
			$client_secret = $this->ReadPropertyString("client_secret");
			
			if((IPS_GetProperty($this->InstanceID, "username") != "") && (IPS_GetProperty($this->InstanceID, "password") != "") ) 
			&& (IPS_GetProperty($this->InstanceID, "client_id") != "") && (IPS_GetProperty($this->InstanceID, "client_secret") != "") {
			
			$scope = NAScopes::SCOPE_READ_STATION;

			$client = new NAApiClient(array("client_id" => $client_id, "client_secret" => $client_secret, "username" => $username, "password" => $password, "scope" => $scope));
			$helper = new NAApiHelper($client);

			try {
 			$tokens = $client->getAccessToken();

			} catch(NAClientException $ex) {
				echo "An error happend while trying to retrieve your tokens\n";
 				exit(-1);
			}

$deviceList = $client->api("devicelist");
if (IPS_GetProperty($this->InstanceID, "modul_1") == "innen") 
{
$carport = $deviceList["modules"][0]["_id"];

$regen = $deviceList["modules"][1]["_id"];

		// Innenmodul		
			$deviceID = $this->CreateCategoryByIdent($this->InstanceID, "Innenmodul", "Innenmodul");
			SetValue($this->CreateVariableByIdent($deviceID, "Latitude", "Latitude", 2), floatval($_POST['latitude']));
			SetValue($this->CreateVariableByIdent($deviceID, "Longitude", "Longitude", 2), floatval($_POST['longitude']));
		
		// Aussenmodul		
			$deviceID = $this->CreateCategoryByIdent($this->InstanceID, "Aussenmodul", "Aussenmodul");
			SetValue($this->CreateVariableByIdent($deviceID, "Latitude", "Latitude", 2), floatval($_POST['latitude']));
			SetValue($this->CreateVariableByIdent($deviceID, "Longitude", "Longitude", 2), floatval($_POST['longitude']));
		
		// Regenmesser		
			$deviceID = $this->CreateCategoryByIdent($this->InstanceID, "Regenmesser", "Regenmesser");
			SetValue($this->CreateVariableByIdent($deviceID, "Latitude", "Latitude", 2), floatval($_POST['latitude']));
			SetValue($this->CreateVariableByIdent($deviceID, "Longitude", "Longitude", 2), floatval($_POST['longitude']));
		
		// Windmesser
			$deviceID = $this->CreateCategoryByIdent($this->InstanceID, "Windmesser", "Windmesser");
			SetValue($this->CreateVariableByIdent($deviceID, "Latitude", "Latitude", 2), floatval($_POST['latitude']));
			SetValue($this->CreateVariableByIdent($deviceID, "Longitude", "Longitude", 2), floatval($_POST['longitude']));
		
		
			
		}
		
		private function ReduceGUIDToIdent($guid) {
			return str_replace(Array("{", "-", "}"), "", $guid);
		}
		

		private function CreateCategoryByIdent($id, $ident, $name)
		 {
			 $cid = @IPS_GetObjectIDByIdent($ident, $id);
			 if($cid === false)
			 {
				 $cid = IPS_CreateCategory();
				 IPS_SetParent($cid, $id);
				 IPS_SetName($cid, $name);
				 IPS_SetIdent($cid, $ident);
			 }
			 return $cid;
		}
		
		private function CreateVariableByIdent($id, $ident, $name, $type, $profile = "")
		 {
			 $vid = @IPS_GetObjectIDByIdent($ident, $id);
			 if($vid === false)
			 {
				 $vid = IPS_CreateVariable($type);
				 IPS_SetParent($vid, $id);
				 IPS_SetName($vid, $name);
				 IPS_SetIdent($vid, $ident);
				 if($profile != "")
					IPS_SetVariableCustomProfile($vid, $profile);
			 }
			 return $vid;
		}
		
		private function CreateInstanceByIdent($id, $ident, $name, $moduleid = "{24B57877-C24C-4690-8421-B41DCC22BE1B}")
		 {
			 $iid = @IPS_GetObjectIDByIdent($ident, $id);
			 if($iid === false)
			 {
				 $iid = IPS_CreateInstance($moduleid);
				 IPS_SetParent($iid, $id);
				 IPS_SetName($iid, $name);
				 IPS_SetIdent($iid, $ident);
			 }
			 return $iid;
		}
		
	
	}
?>





/// ALTES SCRIPT

<?php
/*
Authentication to Netatmo Server with the user credentials grant
*/




try
{

//echo $Carport;


  //          "a": 20.1,
    //        "b": 63

if(isset($deviceList["devices"][0]))
    {
    $device_id = $deviceList["devices"][0]["_id"];
    // Ok now retrieve last temperature and humidity from indoor/base
    $params = array("scale" =>"max",
    "type"=>"Temperature,Humidity,Co2,Pressure,Noise",
    "date_end"=>"last",
    "device_id"=>$device_id);
    $res = $client->api("getmeasure", $params);
    if(isset($res[0]) && isset($res[0]["beg_time"]))
        {
        $time = $res[0]["beg_time"];
        $t = $res[0]["value"][0][0];
        $h = $res[0]["value"][0][1];
        $co2 = $res[0]["value"][0][2];
        $pres = $res[0]["value"][0][3];
        $noise = $res[0]["value"][0][4];
//        echo "Temperature is $t ° Celsius @".date('c', $time)."\n";
//        echo "Humidity is $h % @".date('c', $time)."\n";
//        echo "CO2 is $co2 ppm @".date('c', $time)."\n";
//        echo "Luftdruck is $pres hPa @".date('c', $time)."\n";
//        echo "Lärm is $noise db @".date('c', $time)."\n";
        SetValue(57989 /*[Zentrale Funktionen\Netatmo\Koeppern\Innenmodul\Humidity]*/, $h);
        SetValueFloat(27551 /*[Zentrale Funktionen\Netatmo\Koeppern\Innenmodul\Temperature]*/, $t);
        SetValueFloat(48408 /*[Zentrale Funktionen\Netatmo\Koeppern\Innenmodul\CO2]*/, $co2);
        SetValueFloat(24051 /*[Zentrale Funktionen\Netatmo\Koeppern\Innenmodul\Noise]*/, $noise);
        SetValue(20059 /*[Zentrale Funktionen\Netatmo\Koeppern\Innenmodul\AbsolutePressure]*/, $pres);
        }
    }
        {
    $device_id = $deviceList["devices"][0]["_id"];
    // Ok now retrieve last temperature and humidity from outdoor
    $params = array("scale" =>"max",
    "type"=>"Temperature,Humidity",
    "date_end"=>"last",
    "device_id"=>$device_id,
    "module_id"=>$carport);
    $res = $client->api("getmeasure", $params);
    if(isset($res[0]) && isset($res[0]["beg_time"]))
        {
        $time = $res[0]["beg_time"];
        $t = $res[0]["value"][0][0];
        $h = $res[0]["value"][0][1];
//        echo "Aussentemperature is $t Celsius @".date('c', $time)."\n";
//        echo "Aussen-Humidity is $h % @".date('c', $time)."\n";
        SetValue(57713 /*[Zentrale Funktionen\Netatmo\Koeppern\Aussenmodul\Humidity]*/, $h);
        SetValueFloat(33854 /*[Zentrale Funktionen\Netatmo\Koeppern\Aussenmodul\Temperature]*/, $t);
        }

    
      $device_id = $deviceList["devices"][0]["_id"];
    $params = array("scale" =>"max",
    "type"=>"rain",
    "date_end"=>"last",
    "device_id"=>$device_id,
    "module_id"=>$regen);
    $res = $client->api("getmeasure", $params);
//    print_r($res);
    if(isset($res[0]) && isset($res[0]["beg_time"]))
        {
        $time = $res[0]["beg_time"];
        $t = $res[0]["value"][0][0];
  
     //   echo "Aussentemperature is $t Celsius @".date('c', $time)."\n";
     //   echo "Aussen-Humidity is $h % @".date('c', $time)."\n";
        SetValue(59005 /*[Zentrale Funktionen\Netatmo\Koeppern\Regen Koeppern\Rain]*/, $t);
      //  SetValueFloat(33854 /*[Zentrale Funktionen\Netatmo\Koeppern\Aussenmodul\Temperature]*/, $t);
        }
    }


if(isset($deviceList["devices"][0]))
    {
    $device_id = $deviceList["devices"][0]["_id"];
    // Ok now retrieve last temperature and humidity from indoor/base
    $params = array("scale" =>"1day",
    "type"=>"min_temp,max_temp,min_hum,max_hum,min_pressure,max_pressure,min_noise,max_noise",
    "date_end"=>"last",
    "device_id"=>$device_id);
    $res = $client->api("getmeasure", $params);
    if(isset($res[0]) && isset($res[0]["beg_time"]))
        {
        $time = $res[0]["beg_time"];
        $tmin = $res[0]["value"][0][0];
        $tmax = $res[0]["value"][0][1];
        $hmin = $res[0]["value"][0][2];
        $hmax = $res[0]["value"][0][3];
        $presmin = $res[0]["value"][0][4];
        $presmax = $res[0]["value"][0][5];
        $noisemin = $res[0]["value"][0][6];
        $noisemax = $res[0]["value"][0][7];

        SetValue(43864 /*[Zentrale Funktionen\Netatmo\Koeppern\Innenmodul\Min Temperature]*/, $tmin);
        SetValue(12767 /*[Zentrale Funktionen\Netatmo\Koeppern\Innenmodul\Max Temperature]*/, $tmax);

        SetValue(57264 /*[Zentrale Funktionen\Netatmo\Koeppern\Innenmodul\Min Humidity]*/ , $hmin);
        SetValue(35077 /*[Zentrale Funktionen\Netatmo\Koeppern\Innenmodul\Max Humidity]*/ , $hmax);
        
        SetValue(24212 /*[Zentrale Funktionen\Netatmo\Koeppern\Innenmodul\Min AbsolutePressure]*/ , $presmin);
        SetValue(58811 /*[Zentrale Funktionen\Netatmo\Koeppern\Innenmodul\Max AbsolutePressure]*/ , $presmax);
        
        SetValue(53094 /*[Zentrale Funktionen\Netatmo\Koeppern\Innenmodul\Min Noise]*/ , $noisemin);
        SetValue(15986 /*[Zentrale Funktionen\Netatmo\Koeppern\Innenmodul\Max Noise]*/ , $noisemax);
        
	   //  SetValueFloat(27551 /*[Zentrale Funktionen\Netatmo\Koeppern\Innenmodul\Temperature]*/, $t);
     //   SetValueFloat(48408 /*[Zentrale Funktionen\Netatmo\Koeppern\Innenmodul\CO2]*/, $co2);
     //   SetValueFloat(24051 /*[Zentrale Funktionen\Netatmo\Koeppern\Innenmodul\Noise]*/, $noise);
      //  SetValue(20059 /*[Zentrale Funktionen\Netatmo\Koeppern\Innenmodul\AbsolutePressure]*/, $pres);
        }
    }

    $device_id = $deviceList["devices"][0]["_id"];
    $params = array("scale" =>"1hour",
    "type"=>"sum_rain",
    "date_end"=>"last",
    "device_id"=>$device_id,
    "module_id"=>$regen);
    $res = $client->api("getmeasure", $params);
//    print_r($res);
    if(isset($res[0]) && isset($res[0]["beg_time"]))
        {
        $time = $res[0]["beg_time"];
        $t = $res[0]["value"][0][0];

     //   echo "Aussentemperature is $t Celsius @".date('c', $time)."\n";
     //   echo "Aussen-Humidity is $h % @".date('c', $time)."\n";
        SetValue(20861 /*[Zentrale Funktionen\Netatmo\Koeppern\Regen Koeppern\sum_rain_1]*/, $t);
      //  SetValueFloat(33854 /*[Zentrale Funktionen\Netatmo\Koeppern\Aussenmodul\Temperature]*/, $t);
        }

    
      $device_id = $deviceList["devices"][0]["_id"];
    $params = array("scale" =>"1day",
    "type"=>"sum_rain",
    "date_end"=>"last",
    "device_id"=>$device_id,
    "module_id"=>$regen);
    $res = $client->api("getmeasure", $params);
//    print_r($res);
    if(isset($res[0]) && isset($res[0]["beg_time"]))
        {
        $time = $res[0]["beg_time"];
        $t = $res[0]["value"][0][0];

     //   echo "Aussentemperature is $t Celsius @".date('c', $time)."\n";
     //   echo "Aussen-Humidity is $h % @".date('c', $time)."\n";
        SetValue(58216 /*[Zentrale Funktionen\Netatmo\Koeppern\Regen Koeppern\sum_rain_24]*/, $t);
      //  SetValueFloat(33854 /*[Zentrale Funktionen\Netatmo\Koeppern\Aussenmodul\Temperature]*/, $t);
        }

   $device_id = $deviceList["devices"][0]["_id"];
    // Ok now retrieve last temperature and humidity from outdoor
    $params = array("scale" =>"30min",
    "type"=>"min_temp,max_temp,min_hum,max_hum",
    "date_end"=>"last",
    "device_id"=>$device_id,
    "module_id"=>$carport);
    $res = $client->api("getmeasure", $params);
    if(isset($res[0]) && isset($res[0]["beg_time"]))
        {
        $time = $res[0]["beg_time"];
        $tmin = $res[0]["value"][0][0];
         $tmax = $res[0]["value"][0][1];
        $hmin = $res[0]["value"][0][2];
          $hmax = $res[0]["value"][0][3];
//        echo "Aussentemperature is $t Celsius @".date('c', $time)."\n";
//        echo "Aussen-Humidity is $h % @".date('c', $time)."\n";
        SetValue(52286 /*[Zentrale Funktionen\Netatmo\Koeppern\Aussenmodul\Min Humidity]*/, $hmin);
        SetValue(17470 /*[Zentrale Funktionen\Netatmo\Koeppern\Aussenmodul\Max Humidity]*/, $hmax);
        SetValueFloat(23563 /*[Zentrale Funktionen\Netatmo\Koeppern\Aussenmodul\Min Temperature]*/, $tmin);
        SetValueFloat(52406 /*[Zentrale Funktionen\Netatmo\Koeppern\Aussenmodul\Max Temperature]*/, $tmax);
        }
    
    
}
catch(NAClientException $ex)
{
echo "User does not have any devices\n";
}
?>


















// external Classes from Netatmo

<?php


define('CURL_ERROR_TYPE', 0);
define('API_ERROR_TYPE',1);//error return from api
define('INTERNAL_ERROR_TYPE', 2); //error because internal state is not consistent
define('JSON_ERROR_TYPE',3);
define('NOT_LOGGED_ERROR_TYPE', 4); //unable to get access token
define('BACKEND_BASE_URI', "https://api.netatmo.net/");
define('BACKEND_SERVICES_URI', "https://api.netatmo.net/api");
define('BACKEND_ACCESS_TOKEN_URI', "https://api.netatmo.net/oauth2/token");
define('BACKEND_AUTHORIZE_URI', "https://api.netatmo.net/oauth2/authorize");
/**
 * OAuth2.0 Netatmo client-side implementation.
 *
 * @author Originally written by Thomas Rosenblatt <thomas.rosenblatt@netatmo.com>.
 */
class NAApiClient
{
    /**
   * Array of persistent variables stored.
   */
    protected $conf = array();
    protected $refresh_token;
    protected $access_token;
    /**
   * Returns a persistent variable.
   *
   * To avoid problems, always use lower case for persistent variable names.
   *
   * @param $name
   *   The name of the variable to return.
   * @param $default
   *   The default value to use if this variable has never been set.
   *
   * @return
   *   The value of the variable.
   */
    public function getVariable($name, $default = NULL)
    {
        return isset($this->conf[$name]) ? $this->conf[$name] : $default;
    }
    /**
    * Returns the current refresh token
    */
    public function getRefreshToken()
    {
        return $this->refresh_token;
    }
    /**
    * Sets a persistent variable.
    *
    * To avoid problems, always use lower case for persistent variable names.
    *
    * @param $name
    *   The name of the variable to set.
    * @param $value
    *   The value to set.
    */
    public function setVariable($name, $value)
    {
        $this->conf[$name] = $value;
        return $this;
    }
    private function updateSession()
    {
        $cb = $this->getVariable("func_cb");
        $object = $this->getVariable("object_cb");
        if($object && $cb)
        {
            if(method_exists($object, $cb))
            {
                call_user_func_array(array($object, $cb), array(array("access_token"=> $this->access_token, "refresh_token" => $this->refresh_token)));
            }
        }
        else if($cb && is_callable($cb))
        {
            call_user_func_array($cb, array(array("access_token" => $this->access_token, "refresh_token" => $this->refresh_token)));
        }
    }
    private function setTokens($value)
    {
        if(isset($value["access_token"]))
        {
            $this->access_token = $value["access_token"];
            $update = true;
        }
        if(isset($value["refresh_token"]))
        {
            $this->refresh_token = $value["refresh_token"];
            $update = true;
        }
        if(isset($update)) $this->updateSession();
    }
    /**
     * Set token stored by application (in session generally) into this object
    **/
    public function setTokensFromStore($value)
    {
         if(isset($value["access_token"]))
            $this->access_token = $value["access_token"];
        if(isset($value["refresh_token"]))
            $this->refresh_token = $value["refresh_token"];
    }
    public function unsetTokens()
    {
        $this->access_token = null;
        $this->refresh_token = null;
    }
    /**
    * Initialize a NA OAuth2.0 Client.
    *
    * @param $config
    *   An associative array as below:
    *   - code: (optional) The authorization code.
    *   - username: (optional) The username.
    *   - password: (optional) The password.
    *   - client_id: (optional) The application ID.
    *   - client_secret: (optional) The application secret.
    *   - refresh_token: (optional) A stored refresh_token to use
    *   - access_token: (optional) A stored access_token to use
    *   - object_cb : (optionale) An object for which func_cb method will be applied if object_cb exists
    *   - func_cb : (optional) A method called back to store tokens in its context (session for instance)
    */
    public function __construct($config = array())
    {
        // If tokens are provided let's store it
        if(isset($config["access_token"]))
        {
            $this->access_token = $config["access_token"];
            unset($access_token);
        }
        if(isset($config["refresh_token"]))
        {
            $this->refresh_token = $config["refresh_token"];
        }
        // We must set uri first.
        $uri = array("base_uri" => BACKEND_BASE_URI, "services_uri" => BACKEND_SERVICES_URI, "access_token_uri" => BACKEND_ACCESS_TOKEN_URI, "authorize_uri" => BACKEND_AUTHORIZE_URI);
        foreach($uri as $key => $val)
        {
            if(isset($config[$key]))
            {
                $this->setVariable($key, $config[$key]);
                unset($config[$key]);
            }
            else
            {
                $this->setVariable($key, $val);
            }
        }
        // Other else configurations.
        foreach ($config as $name => $value)
        {
            $this->setVariable($name, $value);
        }
        if($this->getVariable("code") == null && isset($_GET["code"]))
        {
            $this->setVariable("code", $_GET["code"]);
        }
  }
    /**
   * Default options for cURL.
   */
    public static $CURL_OPTS = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_HEADER         => TRUE,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_USERAGENT      => 'netatmoclient',
        CURLOPT_SSL_VERIFYPEER => TRUE,
        CURLOPT_HTTPHEADER     => array("Accept: application/json"),
    );
    /**
    * Makes an HTTP request.
    *
    * This method can be overriden by subclasses if developers want to do
    * fancier things or use something other than cURL to make the request.
    *
    * @param $path
    *   The target path, relative to base_path/service_uri or an absolute URI.
    * @param $method
    *   (optional) The HTTP method (default 'GET').
    * @param $params
    *   (optional The GET/POST parameters.
    * @param $ch
    *   (optional) An initialized curl handle
    *
    * @return
    *   The json_decoded result or NAClientException if pb happend
    */
    public function makeRequest($path, $method = 'GET', $params = array())
    {
        $ch = curl_init();
        $opts = self::$CURL_OPTS;
        if ($params)
        {
            switch ($method)
            {
                case 'GET':
                    $path .= '?' . http_build_query($params, NULL, '&');
                break;
                // Method override as we always do a POST.
                default:
                    if ($this->getVariable('file_upload_support'))
                    {
                        $opts[CURLOPT_POSTFIELDS] = $params;
                    }
                    else
                    {
                        $opts[CURLOPT_POSTFIELDS] = http_build_query($params, NULL, '&');
                    }
                break;
            }
        }
        $opts[CURLOPT_URL] = $path;
        // Disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
        // for 2 seconds if the server does not support this header.
        if (isset($opts[CURLOPT_HTTPHEADER]))
        {
            $existing_headers = $opts[CURLOPT_HTTPHEADER];
            $existing_headers[] = 'Expect:';
            $ip = $this->getVariable("ip");
            if($ip)
                $existing_headers[] = 'CLIENT_IP: '.$ip;
            $opts[CURLOPT_HTTPHEADER] = $existing_headers;
        }
        else
        {
            $opts[CURLOPT_HTTPHEADER] = array('Expect:');
        }
        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);
        $errno = curl_errno($ch);
        // CURLE_SSL_CACERT || CURLE_SSL_CACERT_BADFILE
        if ($errno == 60 || $errno == 77)
        {
            echo "WARNING ! SSL_VERIFICATION has been disabled since ssl error retrieved. Please check your certificate http://curl.haxx.se/docs/sslcerts.html\n";
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            $result = curl_exec($ch);
        }
        if ($result === FALSE)
        {
            $e = new NACurlErrorType(curl_errno($ch), curl_error($ch));
            curl_close($ch);
            throw $e;
        }
        curl_close($ch);
        // Split the HTTP response into header and body.
        list($headers, $body) = explode("\r\n\r\n", $result);
        $headers = explode("\r\n", $headers);
        //Only 2XX response are considered as a success
        if(strpos($headers[0], 'HTTP/1.1 2') !== FALSE)
        {
            $decode = json_decode($body, TRUE);
            if(!$decode)
            {
                if (preg_match('/^HTTP\/1.1 ([0-9]{3,3}) (.*)$/', $headers[0], $matches))
                {
                    throw new NAJsonErrorType($matches[1], $matches[2]);
                }
                else throw new NAJsonErrorType(200, "OK");
            }
            return $decode;
        }
        else
        {
            if (!preg_match('/^HTTP\/1.1 ([0-9]{3,3}) (.*)$/', $headers[0], $matches))
            {
                $matches = array("", 400, "bad request");
            }
            $decode = json_decode($body, TRUE);
            if(!$decode)
            {
                throw new NAApiErrorType($matches[1], $matches[2], null);
            }
            throw new NAApiErrorType($matches[1], $matches[2], $decode);
        }
    }
    /**
    * Retrieve an access token following the best grant to recover it (order id : code, refresh_token, password)
    *
    * @return
    * A valid array containing at least an access_token as an index
    *  @throw
    * A NAClientException if unable to retrieve an access_token
    */
    public function getAccessToken()
    {
        //find best way to retrieve access_token
        if($this->access_token) return array("access_token" => $this->access_token);
        if($this->getVariable('code'))// grant_type == authorization_code.
        {
            return $this->getAccessTokenFromAuthorizationCode($this->getVariable('code'));
        }
        else if($this->refresh_token)// grant_type == refresh_token
        {
            return $this->getAccessTokenFromRefreshToken($this->refresh_token);
        }
        else if($this->getVariable('username') && $this->getVariable('password'))  //grant_type == password
        {
            return $this->getAccessTokenFromPassword($this->getVariable('username'), $this->getVariable('password'));
        }
        else throw new NAInternalErrorType("No access token stored");
    }
    /**
    * Get url to redirect to oauth2.0 netatmo authorize endpoint
    * This is the url where app server needing netatmo access need to route their user (via redirect)
    *
    *
    * @param scope
    *   scope used here
    * @param state
    *   state returned in redirect_uri
    */
    public function getAuthorizeUrl($state = null)
    {
        $redirect_uri = $this->getRedirectUri();
        if($state == null)
        {
            $state = rand();
        }
        $scope = $this->getVariable('scope');
        $params = array("scope" => $scope, "state" => $state, "client_id" => $this->getVariable("client_id"), "client_secret" => $this->getVariable("client_secret"), "response_type" => "code", "redirect_uri" => $redirect_uri);
        return $this->getUri($this->getVariable("authorize_uri"), $params);
    }
    /**
    * Get access token from OAuth2.0 token endpoint with authorization code.
    *
    * This function will only be activated if both access token URI, client
    * identifier and client secret are setup correctly.
    *
    * @param $code
    *   Authorization code issued by authorization server's authorization
    *   endpoint.
    *
    * @return
    *   A valid OAuth2.0 JSON decoded access token in associative array
    * @thrown
    *  A NAClientException if unable to retrieve an access_token
    */
    private function getAccessTokenFromAuthorizationCode($code)
    {
        $redirect_uri = $this->getRedirectUri();
        $scope = $this->getVariable('scope');
        if($this->getVariable('access_token_uri') && ($client_id = $this->getVariable('client_id')) != NULL && ($client_secret = $this->getVariable('client_secret')) != NULL && $redirect_uri != NULL)
        {
            $ret = $this->makeRequest($this->getVariable('access_token_uri'),
                'POST',
                array(
                    'grant_type' => 'authorization_code',
                    'client_id' => $client_id,
                    'client_secret' => $client_secret,
                    'code' => $code,
                    'redirect_uri' => $redirect_uri,
                    'scope' => $scope,
                )
            );
            $this->setTokens($ret);
            return $ret;
        }
        else
            throw new NAInternalErrorType("missing args for getting authorization code grant");
    }
  /**
   * Get access token from OAuth2.0 token endpoint with basic user
   * credentials.
   *
   * This function will only be activated if both username and password
   * are setup correctly.
   *
   * @param $username
   *   Username to be check with.
   * @param $password
   *   Password to be check with.
   *
   * @return
   *   A valid OAuth2.0 JSON decoded access token in associative array
   * @thrown
   *  A NAClientException if unable to retrieve an access_token
   */
    private function getAccessTokenFromPassword($username, $password)
    {
        $scope = $this->getVariable('scope');
        if ($this->getVariable('access_token_uri') && ($client_id = $this->getVariable('client_id')) != NULL && ($client_secret = $this->getVariable('client_secret')) != NULL)
        {
            $ret = $this->makeRequest(
                $this->getVariable('access_token_uri'),
                'POST',
                array(
                'grant_type' => 'password',
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'username' => $username,
                'password' => $password,
                'scope' => $scope,
                )
            );
            $this->setTokens($ret);
            return $ret;
        }
        else
            throw new NAInternalErrorType("missing args for getting password grant");
    }
    /**
    * Get access token from OAuth2.0 token endpoint with basic user
    * credentials.
    *
    * This function will only be activated if both username and password
    * are setup correctly.
    *
    * @param $username
    *   Username to be check with.
    * @param $password
    *   Password to be check with.
    *
    * @return
    *   A valid OAuth2.0 JSON decoded access token in associative array
    * @thrown
    *  A NAClientException if unable to retrieve an access_token
    */
    private function getAccessTokenFromRefreshToken()
    {
        if ($this->getVariable('access_token_uri') && ($client_id = $this->getVariable('client_id')) != NULL && ($client_secret = $this->getVariable('client_secret')) != NULL && ($refresh_token = $this->refresh_token) != NULL)
        {
            if($this->getVariable('scope') != null)
            {
                $ret = $this->makeRequest(
                    $this->getVariable('access_token_uri'),
                    'POST',
                    array(
                        'grant_type' => 'refresh_token',
                        'client_id' => $this->getVariable('client_id'),
                        'client_secret' => $this->getVariable('client_secret'),
                        'refresh_token' => $refresh_token,
                        'scope' => $this->getVariable('scope'),
                        )
                    );
            }
            else
            {
                $ret = $this->makeRequest(
                    $this->getVariable('access_token_uri'),
                    'POST',
                    array(
                        'grant_type' => 'refresh_token',
                        'client_id' => $this->getVariable('client_id'),
                        'client_secret' => $this->getVariable('client_secret'),
                        'refresh_token' => $refresh_token,
                        )
                    );
            }
            $this->setTokens($ret);
            return $ret;
        }
        else
            throw new NAInternalErrorType("missing args for getting refresh token grant");
    }
    /**
    * Make an OAuth2.0 Request.
    *
    * Automatically append "access_token" in query parameters
    *
    * @param $path
    *   The target path, relative to base_path/service_uri
    * @param $method
    *   (optional) The HTTP method (default 'GET').
    * @param $params
    *   (optional The GET/POST parameters.
    *
    * @return
    *   The JSON decoded response object.
    *
    * @throws OAuth2Exception
    */
    protected function makeOAuth2Request($path, $method = 'GET', $params = array(), $reget_token = true)
    {
        try
        {
            $res = $this->getAccessToken();
        }
        catch(NAApiErrorType $ex)
        {
            throw new NANotLoggedErrorType($ex->getCode(), $ex->getMessage());
        }
        $params["access_token"] = $res["access_token"];
        try
        {
            $res = $this->makeRequest($path, $method, $params);
            return $res;
        }
        catch(NAApiErrorType $ex)
        {
            if($reget_token == true)
            {
                switch($ex->getCode())
                {
                    case NARestErrorCode::INVALID_ACCESS_TOKEN:
                    case NARestErrorCode::ACCESS_TOKEN_EXPIRED:
                        //Ok token has expired let's retry once
                        if($this->refresh_token)
                        {
                            try
                            {
                                $this->getAccessTokenFromRefreshToken();//exception will be thrown otherwise
                }
                catch(Exception $ex2)
                            {
                                //Invalid refresh token TODO: Throw a special exception
                                throw $ex;
                            }
                        }
                        else throw $ex;
                        return $this->makeOAuth2Request($path, $method, $params, false);
                    break;
                    default:
                        throw $ex;
                }
            }
            else throw $ex;
        }
        return $res;
    }
    /**
     * Make an API call.
     *
     * Support both OAuth2.0 or normal GET/POST API call, with relative
     * or absolute URI.
     *
     * If no valid OAuth2.0 access token found in session object, this function
     * will automatically switch as normal remote API call without "access_token"
     * parameter.
     *
     * Assume server reply in JSON object and always decode during return. If
     * you hope to issue a raw query, please use makeRequest().
     *
     * @param $path
     *   The target path, relative to base_path/service_uri or an absolute URI.
     * @param $method
     *   (optional) The HTTP method (default 'GET').
     * @param $params
     *   (optional The GET/POST parameters.
     *
     * @return
     *   The JSON decoded body response object.
     *
     * @throws NAClientException
    */
    public function api($path, $method = 'GET', $params = array(), $secure = false)
    {
        if (is_array($method) && empty($params))
        {
            $params = $method;
            $method = 'GET';
        }
        // json_encode all params values that are not strings.
        foreach ($params as $key => $value)
        {
            if (!is_string($value))
            {
                $params[$key] = json_encode($value);
            }
        }
    $res = $this->makeOAuth2Request($this->getUri($path, array(), $secure), $method, $params);
        if(isset($res["body"])) return $res["body"];
    else return $res;
    }
    /**
     * Make a REST call to a Netatmo server that do not need access_token
     *
     * @param $path
     *   The target path, relative to base_path/service_uri or an absolute URI.
     * @param $method
     *   (optional) The HTTP method (default 'GET').
     * @param $params
     *   (optional The GET/POST parameters.
     *
     * @return
     *   The JSON decoded response object.
     *
     * @throws NAClientException
    */
    public function noTokenApi($path, $method = 'GET', $params = array())
    {
        if (is_array($method) && empty($params))
        {
            $params = $method;
            $method = 'GET';
        }
        // json_encode all params values that are not strings.
        foreach ($params as $key => $value)
        {
            if (!is_string($value))
            {
                $params[$key] = json_encode($value);
            }
        }
        return $this->makeRequest($path, $method, $params);
    }
    static public function str_replace_once($str_pattern, $str_replacement, $string)
    {
        if (strpos($string, $str_pattern) !== false)
        {
            $occurrence = strpos($string, $str_pattern);
            return substr_replace($string, $str_replacement, strpos($string, $str_pattern), strlen($str_pattern));
        }
        return $string;
    }
    /**
    * Since $_SERVER['REQUEST_URI'] is only available on Apache, we
    * generate an equivalent using other environment variables.
    */
    function getRequestUri()
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
        }
        else {
            if (isset($_SERVER['argv'])) {
                $uri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['argv'][0];
            }
            elseif (isset($_SERVER['QUERY_STRING'])) {
                $uri = $_SERVER['SCRIPT_NAME'] . '?' . $_SERVER['QUERY_STRING'];
            }
            else {
                $uri = $_SERVER['SCRIPT_NAME'];
            }
        }
        // Prevent multiple slashes to avoid cross site requests via the Form API.
        $uri = '/' . ltrim($uri, '/');
        return $uri;
    }
  /**
   * Returns the Current URL.
   *
   * @return
   *   The current URL.
   */
    protected function getCurrentUri()
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'
          ? 'https://'
          : 'http://';
        $current_uri = $protocol . $_SERVER['HTTP_HOST'] . $this->getRequestUri();
        $parts = parse_url($current_uri);
        $query = '';
        if (!empty($parts['query'])) {
          $params = array();
          parse_str($parts['query'], $params);
          $params = array_filter($params);
          if (!empty($params)) {
            $query = '?' . http_build_query($params, NULL, '&');
          }
        }
        // Use port if non default.
        $port = isset($parts['port']) &&
          (($protocol === 'http://' && $parts['port'] !== 80) || ($protocol === 'https://' && $parts['port'] !== 443))
          ? ':' . $parts['port'] : '';
        // Rebuild.
        return $protocol . $parts['host'] . $port . $parts['path'] . $query;
    }
    /**
     * Returns the Current URL.
     *
     * @return
     *   The current URL.
    */
    protected function getRedirectUri()
    {
        $redirect_uri = $this->getVariable("redirect_uri");
        if(!empty($redirect_uri)) return $redirect_uri;
        else return $this->getCurrentUri();
    }
    /**
    * Build the URL for given path and parameters.
    *
    * @param $path
    *   (optional) The path.
    * @param $params
    *   (optional) The query parameters in associative array.
    *
    * @return
    *   The URL for the given parameters.
    */
    protected function getUri($path = '', $params = array(), $secure = false)
    {
        $url = $this->getVariable('services_uri') ? $this->getVariable('services_uri') : $this->getVariable('base_uri');
        if($secure == true)
        {
            $url = self::str_replace_once("http", "https", $url);
        }
        if(!empty($path))
            if (substr($path, 0, 4) == "http")
                $url = $path;
            else if(substr($path, 0, 5) == "https")
                $url = $path;
            else
                $url = rtrim($url, '/') . '/' . ltrim($path, '/');
        if (!empty($params))
            $url .= '?' . http_build_query($params, NULL, '&');
        return $url;
    }
    public function getPartnerDevices()
    {
        return $this->api("partnerdevices", "POST");
    }
    /**
    * @param string url : webhook url
    * @param string app_type : type of webhook
    * @brief register a webhook notification sent to your app for the current user
    */
    protected function addWebhook($url, $app_type)
    {
        $params = array('url' => $url, 'app_type' => $app_type);
        $this->api('addwebhook', $params);
    }
    /**
    * @param string $app_type: type of webhook
    * @brief drop webhook notification for the current user
    */
    protected function dropWebhook($app_type)
    {
        $params = array('app_type' => $app_type);
        $this->api('dropwebhook', $params);
    }
}
/**
 * API Helpers
 *
 * @author Originally written by Fred Potter <fred.potter@netatmo.com>.
 */
class NAApiHelper
{
    public $client;
    public $devices = array();
    public function __construct($client)
    {
        $this->client = $client;
    }
    public function api($method, $action, $params = array())
    {
        if(isset($this->client))
            return $this->client->api($method, $action, $params);
        else return NULL;
    }
    public function simplifyDeviceList($app_type = "app_station")
    {
        $this->devices = $this->client->api("devicelist", "POST", array("app_type" => $app_type));
        foreach($this->devices["devices"] as $d => $device)
        {
            $moduledetails = array();
            foreach($device["modules"] as $module)
            {
                foreach($this->devices["modules"] as $moduledetail)
                {
                    if($module == $moduledetail['_id'])
                    {
                        $moduledetails[] = $moduledetail;
                    }
                }
            }
            unset($this->devices["devices"][$d]["modules"]);
            $this->devices["devices"][$d]["modules"]=$moduledetails;
        }
        unset($this->devices["modules"]);
        return($this->devices);
    }
    public function getMeasure($device, $device_type, $date_begin, $module=null, $module_type = null)
    {
        $params = array("scale" => "max", "date_begin" => $date_begin, "date_end" => $date_begin+5*60, "device_id" => $device);
        $result = array();
        if(!is_null($module))
        {
            switch($module_type)
            {
                case "NAModule1":
                    $params["type"] = "Temperature,Humidity";
                break;
                case "NAModule4":
                    $params["type"] = "Temperature,CO2,Humidity";
                break;
                case "NAModule3":
                    $params["type"] = "Rain";
                break;
            }
            $params["module_id"] = $module;
        }
        else
        {
            switch($device_type)
            {
                case "NAMain":
                    $params["type"] = "Temperature,CO2,Humidity,Pressure,Noise";
                break;
                case "NAPlug":
                    $params["type"] = "Temperature,Sp_Temperature,BoilerOn,BoilerOff";
            }
        }
        $types = explode(",", $params["type"]);
        if($types === FALSE)
        {
            $types = array($params["type"]);
        }
        $meas = $this->client->api("getmeasure", "POST", $params);
        if(isset($meas[0]))
        {
            $result['time'] = $meas[0]['beg_time'];
            foreach($meas[0]['value'][0] as $key => $val)
            {
                $result[$types[$key]] = $val;
            }
        }
        return($result);
    }
    public function getLastMeasures()
    {
        $results = array();
        foreach ($this->devices["devices"] as $device)
        {
            $result = array();
            if(isset($device["station_name"])) $result["station_name"] = $device["station_name"];
            if(isset($device["modules"][0])) $result["modules"][0]["module_name"] = $device["module_name"];
            $result["modules"][0] = array_merge($result["modules"][0], $device["dashboard_data"]);
            foreach ($device["modules"] as $module)
            {
                $addmodule = array();
                if(isset($module["module_name"])) $addmodule["module_name"] = $module["module_name"];
                $addmodule = array_merge($addmodule, $module["dashboard_data"]);
                $result["modules"][] = $addmodule;
            }
            $results[] = $result;
        }
        return($results);
    }
    public function getAllMeasures($date_begin)
    {
        $results = array();
        foreach ($this->devices["devices"] as $device)
        {
            $result = array();
            if(isset($device["station_name"])) $result["station_name"] = $device["station_name"];
            if(isset($device["modules"][0])) $result["modules"][0]["module_name"] = $device["module_name"];
            $result["modules"][0] = array_merge($result["modules"][0], $this->getMeasure($device["_id"], $device["type"], $date_begin));
            foreach ($device["modules"] as $module)
            {
                $addmodule = array();
                if(isset($module["module_name"])) $addmodule["module_name"] = $module["module_name"];
                $addmodule = array_merge($addmodule, $this->getMeasure($device["_id"], $device["type"], $date_begin, $module["_id"], $module["type"]));
                $result["modules"][] = $addmodule;
            }
            $results[] = $result;
        }
        return($results);
    }
}
?>

<?php
class NARestErrorCode
{
    const ACCESS_TOKEN_MISSING = 1;
    const INVALID_ACCESS_TOKEN = 2;
    const ACCESS_TOKEN_EXPIRED = 3;
    const INCONSISTENCY_ERROR = 4;
    const APPLICATION_DEACTIVATED = 5;
    const INVALID_EMAIL = 6;
    const NOTHING_TO_MODIFY = 7;
    const EMAIL_ALREADY_EXISTS = 8;
    const DEVICE_NOT_FOUND = 9;
    const MISSING_ARGS = 10;
    const INTERNAL_ERROR = 11;
    const DEVICE_OR_SECRET_NO_MATCH = 12;
    const OPERATION_FORBIDDEN = 13;
    const APPLICATION_NAME_ALREADY_EXISTS = 14;
    const NO_PLACES_IN_DEVICE = 15;
    const MGT_KEY_MISSING = 16;
    const BAD_MGT_KEY = 17;
    const DEVICE_ID_ALREADY_EXISTS = 18;
    const IP_NOT_FOUND = 19;
    const TOO_MANY_USER_WITH_IP = 20;
    const INVALID_ARG = 21;
    const APPLICATION_NOT_FOUND = 22;
    const USER_NOT_FOUND = 23;
    const INVALID_TIMEZONE = 24;
    const INVALID_DATE = 25;
    const MAX_USAGE_REACHED = 26;
    const MEASURE_ALREADY_EXISTS = 27;
    const ALREADY_DEVICE_OWNER = 28;
    const INVALID_IP = 29;
    const INVALID_REFRESH_TOKEN = 30;
    const NOT_FOUND = 31;
    const BAD_PASSWORD = 32;
    const FORCE_ASSOCIATE = 33;
    const MODULE_ALREADY_PAIRED = 34;
    const UNABLE_TO_EXECUTE = 35;
    const PROHIBITTED_STRING = 36;
    const CAMERA_NO_SPACE_AVAILABLE = 37;
    const PASSWORD_COMPLEXITY_TOO_LOW = 38;
    const TOO_MANY_CONNECTION_FAILURE = 39;
}

class NAClientErrorCode
{
    const OAUTH_INVALID_GRANT = -1;
    const OAUTH_OTHER = -2;
}

class NAScopes
{
    const SCOPE_READ_STATION = "read_station";
    const SCOPE_READ_THERM = "read_thermostat";
    const SCOPE_WRITE_THERM = "write_thermostat";
    const SCOPE_READ_CAMERA = "read_camera";
    const SCOPE_WRITE_CAMERA = "write_camera";
    const SCOPE_ACCESS_CAMERA = "access_camera";
    const SCOPE_READ_JUNE = "read_june";
    const SCOPE_WRITE_JUNE = "write_june";
    static $defaultScopes = array(NAScopes::SCOPE_READ_STATION);
    static $validScopes = array(NAScopes::SCOPE_READ_STATION, NAScopes::SCOPE_READ_THERM, NAScopes::SCOPE_WRITE_THERM, NAScopes::SCOPE_READ_CAMERA, NAScopes::SCOPE_WRITE_CAMERA, NAScopes::SCOPE_ACCESS_CAMERA, NAScopes::SCOPE_READ_JUNE, NAScopes::SCOPE_WRITE_JUNE);
    // scope allowed to everyone (no need to be approved)
    static $basicScopes = array(NAScopes::SCOPE_READ_STATION, NAScopes::SCOPE_READ_THERM, NASCopes::SCOPE_WRITE_THERM, NAScopes::SCOPE_READ_CAMERA, NAScopes::SCOPE_WRITE_CAMERA, NAScopes::SCOPE_READ_JUNE, NAScopes::SCOPE_WRITE_JUNE);
}

class NAWebhook
{
    const UNIT_TEST = "test";
    const LOW_BATTERY = "low_battery";
    const BOILER_NOT_RESPONDING = "boiler_not_responding";
    const BOILER_RESPONDING = "boiler_responding";
}

class NAPublicConst
{
    const UNIT_METRIC = 0;
    const UNIT_US = 1;
    const UNIT_TYPE_NUMBER = 2;

    const UNIT_WIND_KMH = 0;
    const UNIT_WIND_MPH = 1;
    const UNIT_WIND_MS = 2;
    const UNIT_WIND_BEAUFORT = 3;
    const UNIT_WIND_KNOT = 4;
    const UNIT_WIND_NUMBER = 5;

    const UNIT_PRESSURE_MBAR = 0;
    const UNIT_PRESSURE_MERCURY = 1;
    const UNIT_PRESSURE_TORR = 2;
    const UNIT_PRESSURE_NUMBER = 3;

    const FEEL_LIKE_HUMIDEX_ALGO = 0;
    const FEEL_LIKE_HEAT_ALGO = 1;
    const FEEL_LIKE_NUMBER = 2;

    const KIND_READ_TIMELINE = 0;
    const KIND_NOT_READ_TIMELINE = 1;
    const KIND_BOTH_TIMELINE = 2;
}

class NAConstants
{
    const FAVORITES_MAX = 5;
}

/*
 * Defines the min and max values of the sensors.
 */
class NAStationSensorsMinMax
{
    const TEMP_MIN = -40;
    const TEMP_MAX = 60;
    const HUM_MIN = 1;
    const HUM_MAX = 99;
    const CO2_MIN = 300;
    const CO2_MAX = 4000;
    const PRESSURE_MIN = 700;
    const PRESSURE_MAX = 1300;
    const NOISE_MIN = 10;
    const NOISE_MAX = 120;
    const RAIN_MIN = 2;
    const RAIN_MAX = 300;
    const WIND_MIN = 5;
    const WIND_MAX = 150;
}

class NAScheduleTime
{
    const WEEK_WAKEUP_TIME_DEFAULT = 420;
    const WEEK_SLEEP_TIME_DEFAULT = 1320;
    const WEEK_WORK_TIME_DEFAULT = 480;
    const WEEK_WORK_TIME_BACK_DEFAULT = 1140;
    const WEEK_WORK_LUNCH_TIME_DEFAULT = 720;
    const WEEK_WORK_LUNCH_TIME_BACK_DEFAULT = 810;
}

class NAWifiRssiThreshold
{
    const RSSI_THRESHOLD_0 = 86;/*bad signal*/
    const RSSI_THRESHOLD_1 = 71;/*middle quality signal*/
    const RSSI_THRESHOLD_2 = 56;/*good signal*/
}

class NARadioRssiTreshold
{
    const RADIO_THRESHOLD_0 = 90;/*low signal*/
    const RADIO_THRESHOLD_1 = 80;/*signal medium*/
    const RADIO_THRESHOLD_2 = 70;/*signal high*/
    const RADIO_THRESHOLD_3 = 60;/*full signal*/
}

class NABatteryLevelWindGaugeModule
{
    /* Battery range: 6000 ... 3950 */
    const WG_BATTERY_LEVEL_0 = 5590;/*full*/
    const WG_BATTERY_LEVEL_1 = 5180;/*high*/
    const WG_BATTERY_LEVEL_2 = 4770;/*medium*/
    const WG_BATTERY_LEVEL_3 = 4360;/*low*/
    /* below 4360: very low */
}

class NABatteryLevelIndoorModule
{
    /* Battery range: 6000 ... 4200 */
    const INDOOR_BATTERY_LEVEL_0 = 5640;/*full*/
    const INDOOR_BATTERY_LEVEL_1 = 5280;/*high*/
    const INDOOR_BATTERY_LEVEL_2 = 4920;/*medium*/
    const INDOOR_BATTERY_LEVEL_3 = 4560;/*low*/
    /* Below 4560: very low */
}

class NABatteryLevelModule
{
    /* Battery range: 6000 ... 3600 */
    const BATTERY_LEVEL_0 = 5500;/*full*/
    const BATTERY_LEVEL_1 = 5000;/*high*/
    const BATTERY_LEVEL_2 = 4500;/*medium*/
    const BATTERY_LEVEL_3 = 4000;/*low*/
    /* below 4000: very low */
}

class NABatteryLevelThermostat
{
    /* Battery range: 4500 ... 3000 */
    const THERMOSTAT_BATTERY_LEVEL_0 = 4100;/*full*/
    const THERMOSTAT_BATTERY_LEVEL_1 = 3600;/*high*/
    const THERMOSTAT_BATTERY_LEVEL_2 = 3300;/*medium*/
    const THERMOSTAT_BATTERY_LEVEL_3 = 3000;/*low*/
    /* below 3000: very low */
}

class NATimeBeforeDataExpire
{
    const TIME_BEFORE_UNKNONWN_THERMOSTAT = 7200;
    const TIME_BEFORE_UNKNONWN_STATION = 7200;
    const TIME_BEFORE_UNKNONWN_CAMERA = 46800; // 13h
}

class NAHeatingEnergy
{
    const THERMOSTAT_HEATING_ENERGY_UNKNOWN = "unknown";
    const THERMOSTAT_HEATING_ENERGY_GAS = "gas";
    const THERMOSTAT_HEATING_ENERGY_OIL = "oil";
    const THERMOSTAT_HEATING_ENERGY_WOOD = "wood";
    const THERMOSTAT_HEATING_ENERGY_ELEC = "elec";
    const THERMOSTAT_HEATING_ENERGY_PAC = "pac";
    const THERMOSTAT_HEATING_ENERGY_SUNHYBRID = "sunhybrid";
}

class NAHeatingType
{
    const THERMOSTAT_HEATING_TYPE_UNKNOWN = "unknown";
    const THERMOSTAT_HEATING_TYPE_SUBFLOOR = "subfloor";
    const THERMOSTAT_HEATING_TYPE_RADIATOR = "radiator";
}

class NAHomeType
{
    const THERMOSTAT_HOME_TYPE_UNKNOWN = "unknown";
    const THERMOSTAT_HOME_TYPE_HOUSE = "house";
    const THERMOSTAT_HOME_TYPE_FLAT = "flat";
}

class NAPluvioLevel // en mm
{
    const RAIN_NULL = 0.1; // <
    const RAIN_WEAK = 3; // <
    const RAIN_MIDDLE = 8; // <
    const RAIN_STRONG = 15; // or > 8, don't use this value
}

class NAPluvioCalibration
{
    const RAIN_SCALE_MIN = 0.01;
    const RAIN_SCALE_MAX = 0.25;
    const RAIN_SCALE_ML_MIN = 0;
    const RAIN_SCALE_ML_MAX = 3;
}

//CAMERA SPECIFIC DATA
class NACameraEventType
{
    const CET_PERSON = "person";
    const CET_PERSON_AWAY = "person_away";
    const CET_MODEL_IMPROVED = "model_improved";
    const CET_MOVEMENT = "movement";
    const CET_CONNECTION = "connection";
    const CET_DISCONNECTION = "disconnection";
    const CET_ON = "on";
    const CET_OFF = "off";
    const CET_END_RECORDING = "end_recording";
    const CET_LIVE = "live_rec";
    const CET_BOOT = "boot";
    const CET_SD = "sd";
    const CET_ALIM = "alim";
}

class NACameraEventInfo
{
    const CEI_ID = "id";
    const CEI_TYPE = "type";
    const CEI_TIME = "time";
    const CEI_PERSON_ID = "person_id";
    const CEI_SNAPSHOT = "snapshot";
    const CEI_VIDEO_ID = "video_id";
    const CEI_VIDEO_STATUS = "video_status";
    const CEI_CAMERA_ID = "camera_id";
    const CEI_MESSAGE = "message";
    const CEI_SUB_TYPE = "sub_type";
    const CEI_IS_ARRIVAL = "is_arrival";
    const CEI_ALARM_ID = "alarm_id";
    const CEI_ALARM_TYPE = "alarm_type";
}

class NACameraPersonInfo
{
    const CPI_ID = "id";
    const CPI_LAST_SEEN = "last_seen";
    const CPI_FACE = "face";
    const CPI_OUT_OF_SIGHT = "out_of_sight";
    const CPI_PSEUDO = "pseudo";
    const CPI_IS_CURRENT_USER = "is_current_user";
}

class NACameraImageInfo
{
    const CII_ID = "id";
    const CII_VERSION = "version";
    const CII_KEY = "key";
}

class NACameraHomeInfo
{
    const CHI_ID = "id";
    const CHI_NAME = "name";
    const CHI_PLACE = "place";
    const CHI_PERSONS = "persons";
    const CHI_EVENTS = "events";
    const CHI_CAMERAS = "cameras";
}

class NACameraInfo
{
    const CI_ID = "id";
    const CI_NAME = "name";
    const CI_LIVE_URL = "live_url";
    const CI_STATUS = "status";
    const CI_SD_STATUS = "sd_status";
    const CI_ALIM_STATUS = "alim_status";
    const CI_IS_LOCAL = "is_local";
    const CI_VPN_URL = "vpn_url";
}

class NACameraStatus
{
    const CS_ON = "on";
    const CS_OFF = "off" ;
    const CS_DISCONNECTED = "disconnected";
}

class APIResponseFields
{
    const APIRF_SYNC_ORDER = "sync";
    const APIRF_KEEP_RECORD_ORDER = "keep_record";
    const APIRF_VIDEO_ID = "video_id";
    const APIRF_SYNC_ORDER_LIST = "sync_order_list";
    const APIRF_EVENTS_LIST = "events_list";
    const APIRF_PERSONS_LIST = "persons_list";
    const APIRF_HOMES = "homes";
    const APIRF_USER = "user";
    const APIRF_GLOBAL_INFO = "global_info";
}


class NACameraVideoStatus
{
    const CVS_RECORDING = "recording";
    const CVS_DELETED = "deleted";
    const CVS_AVAILABLE = "available";
    const CVS_ERROR = "error";
}

class NACameraSDEvent
{
    const CSDE_ABSENT = 1;
    const CSDE_INSERTED = 2;
    const CSDE_FORMATED = 3;
    const CSDE_OK = 4;
    const CSDE_DEFECT = 5;
    const CSDE_INCOMPATIBLE = 6;
    const CSDE_TOO_SMALL = 7;

    static $issueEvents = array(NACameraSDEvent::CSDE_ABSENT, NACameraSDEvent::CSDE_DEFECT, NACameraSDEvent::CSDE_INCOMPATIBLE, NACameraSDEvent::CSDE_TOO_SMALL);
}

class NACameraAlimSubStatus
{
    const CASS_DEFECT = 1;
    const CASS_OK = 2;
}

class NAThermZone {
const THERMOSTAT_SCHEDULE_SLOT_DAY      = 0x00;
const THERMOSTAT_SCHEDULE_SLOT_NIGHT    = 0x01;
const THERMOSTAT_SCHEDULE_SLOT_AWAY     = 0x02;
const THERMOSTAT_SCHEDULE_SLOT_HG       = 0x03;
const THERMOSTAT_SCHEDULE_SLOT_PERSO    = 0x04;
const THERMOSTAT_SCHEDULE_SLOT_ECO      = 0x05;
const THERMOSTAT_SCHEDULE_HOT_WATER_ON  = 0x06;
const THERMOSTAT_SCHEDULE_HOT_WATER_OFF = 0x07;
}

?>

<?php
/**
* Exception thrown by Netatmo SDK
*/
class NASDKException extends Exception
{
    public function __construct($code, $message)
    {
        parent::__construct($message, $code);
    }
}
class NASDKError
{
    const UNABLE_TO_CAST = 601;
    const NOT_FOUND = 602;
    const INVALID_FIELD = 603;
    const FORBIDDEN_OPERATION = 604;
}
?>

<?php

require_once("NASDKException.php");

/**
 * OAuth2.0 Netatmo exception handling
 *
 * @author Originally written by Thomas Rosenblatt <thomas.rosenblatt@netatmo.com>.
 */
class NAClientException extends NASDKException
{
    public $error_type;
    /**
    * Make a new API Exception with the given result.
    *
    * @param $result
    *   The result from the API server.
    */
    public function __construct($code, $message, $error_type)
    {
        $this->error_type = $error_type;
        parent::__construct($code, $message);
    }
}


class NAApiErrorType extends NAClientException
{
    public $http_code;
    public $http_message;
    public $result;
    function __construct($code, $message, $result)
    {
        $this->http_code = $code;
        $this->http_message = $message;
        $this->result = $result;
        if(isset($result["error"]) && is_array($result["error"]) && isset($result["error"]["code"]))
        {
            parent::__construct($result["error"]["code"], $result["error"]["message"], API_ERROR_TYPE);
        }
        else
        {
            parent::__construct($code, $message, API_ERROR_TYPE);
        }
    }
}

class NACurlErrorType extends NAClientException
{
    function __construct($code, $message)
    {
        parent::__construct($code, $message, CURL_ERROR_TYPE);
    }
}

class NAJsonErrorType extends NAClientException
{
    function __construct($code, $message)
    {
        parent::__construct($code, $message, JSON_ERROR_TYPE);
    }
}

class NAInternalErrorType extends NAClientException
{
    function __construct($message)
    {
        parent::__construct(0, $message, INTERNAL_ERROR_TYPE);
    }
}

class NANotLoggedErrorType extends NAClientException
{
    function __construct($code, $message)
    {
        parent::__construct($code, $message, NOT_LOGGED_ERROR_TYPE);
    }
}
?>




