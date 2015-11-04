<?php
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
	
		}
	
		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
			
		//	Update();

		}
		
		public function Update()
		{


			$username = $this->ReadPropertyString("username");
			$password = $this->ReadPropertyString("password");
			$client_id = $this->ReadPropertyString("client_id");
			$client_secret = $this->ReadPropertyString("client_secret");
			
			if(
			(IPS_GetProperty($this->InstanceID, "username") != "") 
			&& (IPS_GetProperty($this->InstanceID, "password") != "")  
			&& (IPS_GetProperty($this->InstanceID, "client_id") != "") 
			&& (IPS_GetProperty($this->InstanceID, "client_secret") != "")
			){
			

//App client configuration
$scope = NAScopes::SCOPE_READ_STATION;
$config = array("client_id" => $client_id,
                "client_secret" => $client_secret,
                "username" => $username,
                "password" => $password);

$client = new NAWSApiClient($config);

//Authentication with Netatmo server (OAuth2)
try
{
    $tokens = $client->getAccessToken();
}
catch(NAClientException $ex)
{
    handleError("An error happened while trying to retrieve your tokens: " .$ex->getMessage()."\n", TRUE);
}

//Retrieve user's Weather Stations Information

try
{
    //retrieve all stations belonging to the user, and also his favorite ones
    $data = $client->getData(NULL, TRUE);
  //  printMessageWithBorder("Weather Stations Basic Information");
  echo "Weather Stations Basic Information";
}
catch(NAClientException $ex)
{
    handleError("An error occured while retrieving data: ". $ex->getMessage()."\n", TRUE);
}

if(empty($data['devices']))
{
    echo 'No devices affiliated to user';
}
else
{

    $users = array();
    $friends = array();
    $fav = array();
    $device = $data['devices'][0];
    $tz = isset($device['place']['timezone']) ? $device['place']['timezone'] : "GMT";

    //devices are already sorted in the following way: first weather stations owned by user, then "friend" WS, and finally favorites stations. Still let's store them in different arrays according to their type
    foreach($data['devices'] as $device)
    {

        //favorites have both "favorite" and "read_only" flag set to true, whereas friends only have read_only
        if(isset($device['favorite']) && $device['favorite'])
            $fav[] = $device;
        else if(isset($device['read_only']) && $device['read_only'])
            $friends[] = $device;
        else $users[] = $device;
    }

    //print first User's device Then friends, then favorite
    printDevices($users, "User's weather stations");
    printDevices($friends, "User's friends weather stations");
    printDevices($fav, "User's favorite weather stations");

    // now get some daily measurements for the last 30 days
     $type = "temperature,Co2,humidity,noise,pressure";

    //first for the main device
    try
    {
        $measure = $client->getMeasure($device['_id'], NULL, "1day" , $type, time() - 24*3600*30, time(), 30,  FALSE, FALSE);
        printMeasure($measure, $type, $tz, $device['_id'] ."'s daily measurements of the last 30 days");
    }
    catch(NAClientException $ex)
    {
        handleError("An error occured while retrieving main device's daily measurements: " . $ex->getMessage() . "\n");
    }

    //Then for its modules
    foreach($device['modules'] as $module)
    {
        //requested data type depends on the module's type
        switch($module['type'])
        {
            case "NAModule3": $type = "sum_rain";
                              break;
            case "NAModule2": $type = "WindStrength,WindAngle,GustStrength,GustAngle,date_max_gust";
                              break;
            case "NAModule1" : $type = "temperature,humidity";
                               break;
            default : $type = "temperature,Co2,humidity,noise,pressure";
        }
        try
        {
            $measure = $client->getMeasure($device['_id'], $module['_id'], "1day" , $type, time()-24*3600*30 , time(), 30,  FALSE, FALSE);
            printMeasure($measure, $type, $tz, $module['_id']. "'s daily measurements of the last 30 days ");
        }
        catch(NAClientException $ex)
        {
            handleError("An error occured while retrieving main device's daily measurements: " . $ex->getMessage() . "\n");
        }

    }

    //Finally, retrieve general info about last month for main device
    $type = "max_temp,date_max_temp,min_temp,date_min_temp,max_hum,date_max_hum,min_hum,date_min_hum,max_pressure,date_max_pressure,min_pressure,date_min_pressure,max_noise,date_max_noise,min_noise,date_min_noise,max_co2,date_max_co2,min_co2,date_min_co2";
    try
    {
        $measures = $client->getMeasure($device['_id'], NULL, "1month", $type, NULL, "last", 1, FALSE, FALSE);
        printMeasure($measures, $type, $tz, "Last month information of " .$device['_id'], TRUE);
    }
    catch(NAClientException $ex)
    {
        handleError("An error occcured while retrieving last month info: ".$ex->getMessage() . " \n");
    }

			}
		}
}
/**
 * Prints a list of devices
 *
 */
 function NAW_printDevices($devices, $title = NULL)
{
    if(!is_null($devices) && is_array($devices) && !empty($devices))
    {
        if(!is_null($title))
            printMessageWithBorder($title);

        foreach($devices as $device)
        {
            printWSBasicInfo($device);
        }
    }
}

    
    
function NAW_handleError($message, $exit = FALSE)
{
    echo $message;
    if($exit)
        exit(-1);
}

function NAW_printTimeInTz($time, $timezone, $format)
{
    try{
        $tz = new DateTimeZone($timezone);
    }
    catch(Exception $ex)
    {
        $tz = new DateTimeZone("GMT");
    }
    $date = new DateTime();
    $date->setTimezone($tz);
    $date->setTimestamp($time);
    echo $date->format($format);
}

function NAW_printBorder($message)
{
    $size = strlen($message);
    for($i = 0; $i < $size; $i++)
        echo("-");
    echo("\n");
}

function NAW_printMessageWithBorder($message)
{
    $message = "- " . $message . " -";
    printBorder($message);
    echo $message . "\n";
    printBorder($message);
}

function NAW_printMeasure($measurements, $type, $tz, $title = NULL, $monthly = FALSE)
{
    if(!empty($measurements))
    {
        if(!empty($title))
            printMessageWithBorder($title);

        if($monthly)
            $dateFormat = 'F: ';
        else $dateFormat = 'j F: ';
        //array of requested info type, needed to map result values to what they mean
        $keys = explode(",", $type);

        foreach($measurements as $timestamp => $values)
        {
            printTimeinTz($timestamp, $tz, $dateFormat);
             echo"\n";
            foreach($values as $key => $val)
            {
                echo $keys[$key] . ": ";
                if($keys[$key] === "time_utc" || preg_match("/^date_.*/", $keys[$key]))
                    echo printTimeInTz($val, $tz, "j F H:i");
                else{
                    echo $val;
                    printUnit($keys[$key]);
                }
                if(count($values)-1 === $key || $monthly)
                    echo "\n";
                else echo ", ";
            }
        }
    }
}

/**
 * function NAW_printing a weather station or modules basic information such as id, name, dashboard data, modules (if main device), type(if module)
 *
 */
function NAW_printWSBasicInfo($device)
{
    if(isset($device['station_name']))
        echo ("- ".$device['station_name']. " -\n");
    else if($device['module_name'])
        echo ("- ".$device['module_name']. " -\n");

    echo ("id: " . $device['_id']. "\n");

    if(isset($device['type']))
    {
        echo ("type: ");
        switch($device['type'])
        {
            // Outdoor Module
            case "NAModule1": echo ("Outdoor\n");
                              break;
            //Wind Sensor
            case "NAModule2": echo("Wind Sensor\n");
                              break;

            //Rain Gauge
            case "NAModule3": echo("Rain Gauge\n");
                              break;
            //Indoor Module
            case "NAModule4": echo("Indoor\n");
                              break;
            case "NAMain" : echo ("Main device \n");
                            break;
        }

    }

    if(isset($device['place']['timezone']))
        $tz = $device['place']['timezone'];
    else $tz = 'GMT';

    if(isset($device['dashboard_data']))
    {
        echo ("Last data: \n");
        foreach($device['dashboard_data'] as $key => $val)
        {
            if($key === 'time_utc' || preg_match("/^date_.*/", $key))
            {
                echo $key .": ";
                printTimeInTz($val, $tz, 'j F H:i');
                echo ("\n");
            }
            else if(is_array($val))
            {
                //do nothing : don't print historic
            }
            else {
                echo ($key .": " . $val);
                printUnit($key);
                echo "\n";
            }
        }

        if(isset($device['modules']))
        {
            echo (" \n\nModules: \n");
            foreach($device['modules'] as $module)
                printWSBasicInfo($module);
        }
    }

    echo"       ----------------------   \n";
}

function NAW_printUnit($key)
{
    $typeUnit = array('temp' => '°C', 'hum' => '%', 'noise' => 'db', 'strength' => 'km/h', 'angle' => '°', 'rain' => 'mm', 'pressure' => 'mbar', 'co2' => 'ppm');
    foreach($typeUnit as $type => $unit)
    {
        if(preg_match("/.*$type.*/i", $key))
        {
            echo " ".$unit;
            return;
        }
    }
}

/** THERM Utils function NAW_**/
/*
* @brief print a thermostat basic information in CLI
*/
function NAW_printThermBasicInfo($dev)
{
    //Device
    echo (" -".$dev['station_name']."- \n");
    echo (" id: ".$dev['_id']." \n");
    echo ("Modules : \n");
    // Device's modules info
    foreach($dev['modules'] as $module)
    {
        echo ("    - ".$module['module_name']." -\n");

        //module last measurements
        echo ("    Last Measure date : ");
        printTimeInTz($module['measured']['time'], $dev['place']['timezone'], 'j F H:i');
        echo("\n");
        echo ("    Last Temperature measured: ". $module['measured']['temperature']);
        printUnit("temperature");
        echo("\n");
        echo ("    Last Temperature setpoint: ". $module['measured']['setpoint_temp']);
        printUnit('setpoint_temp');
        echo("\n");
        echo ("    Program List: \n");

        //program list
        foreach($module['therm_program_list'] as $program)
        {
            if(isset($program['name']))
                echo ("        -".$program['name']."- \n");
            else echo("        -Standard- \n");
            echo ("        id: ".$program['program_id']." \n");
            if(isset($program['selected']) && $program['selected'] === TRUE)
            {
                echo "         This is the current program \n";
            }
        }
    }

}

/**
* @brief returns the current program of a therm module
*/
function NAW_getCurrentProgram($module)
{
    foreach($module['therm_program_list'] as $program)
    {
        if(isset($program['selected']) && $program['selected'] === TRUE)
            return $program['program_id'];
    }
    //not found
    return NULL;
}

/**
* @brief returns the current setpoint of a therm module along with its setpoint temperature and endtime if defined
*/
function NAW_getCurrentMode($module)
{
    $initialMode = $module["setpoint"]["setpoint_mode"];
    $initialTemp = isset($module["setpoint"]["setpoint_temp"]) ? $module["setpoint"]["setpoint_temp"]: NULL;
    $initialEndtime = isset($module['setpoint']['setpoint_endtime']) ? $module['setpoint']['setpoint_endtime'] : NULL;

    return array($initialMode, $initialTemp, $initialEndtime);

}

function NAW_printHomeInformation(NAHome $home)
{
    !is_null($home->getName()) ? printMessageWithBorder($home->getName()) : printMessageWithBorder($home->getId());
    echo ("id: ". $home->getId() ."\n");

    $tz = $home->getTimezone();
    $persons = $home->getPersons();
	
    if(!empty($persons))
    {
        printMessageWithBorder("Persons");
        //print person list
        foreach($persons as $person)
        {
            printPersonInformation($person, $tz);
        }
    }

    if((!empty($home->getEvents())))
    {
        printMessageWithBorder('Timeline of Events');
        //print event list
        foreach($home->getEvents() as $event)
        {
            printEventInformation($event, $tz);
        }
    }

    if(!empty($home->getCameras()))
    {
        printMessageWithBorder("Cameras");
        foreach($home->getCameras() as $camera)
        {
            printCameraInformation($camera);
        }
    }
}


function NAW_printPersonInformation(NAPerson $person, $tz)
{
    $person->isKnown() ? printMessageWithBorder($person->getPseudo()) : printMessageWithBorder("Inconnu");
    echo("id: ". $person->getId(). "\n");
    if($person->isAway())  echo("is away from home \n" );
    else echo("is home \n");

    echo ("Last seen on: ");
    printTimeInTz($person->getLastSeen(), $tz, "j F H:i");
    echo ("\n");
}

function NAW_printEventInformation(NAEvent $event, $tz)
{
  printTimeInTz($event->getTime(), $tz, "j F H:i");
  $message = removeHTMLTags($event->getMessage());
  echo(": ".$message. "\n");
}

function NAW_printCameraInformation(NACamera $camera)
{
    !is_null($camera->getName()) ? printMessageWithBorder($camera->getName()) : printMessageWithBorder($camera->getId());

    echo("id: ". $camera->getId() ."\n");
    echo("Monitoring status: ". $camera->getVar(NACameraInfo::CI_STATUS) ."\n");
    echo("SD card status: " .$camera->getVar(NACameraInfo::CI_SD_STATUS) . "\n");
    echo ("Power status: ". $camera->getVar(NACameraInfo::CI_ALIM_STATUS) ."\n");

    if($camera->getGlobalStatus())
        $globalStatus = "OK";
    else $globalStatus = "NOK";

    echo ("Global Status: ". $globalStatus ."\n");

}

function NAW_removeHTMLTags($string)
{
   return preg_replace("/<.*?>/", "", $string);
}

private function NAW_ReduceGUIDToIdent($guid) {
	return str_replace(Array("{", "-", "}"), "", $guid);
}

private function NAW_CreateCategoryByIdent($id, $ident, $name)
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
		
		private function NAW_CreateVariableByIdent($id, $ident, $name, $type, $profile = "")
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
		
		private function NAW_CreateInstanceByIdent($id, $ident, $name, $moduleid = "{24B57877-C24C-4690-8421-B41DCC22BE1B}")
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







// external Classes from Netatmo


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
    public function NAW_getVariable($name, $default = NULL)
    {
        return isset($this->conf[$name]) ? $this->conf[$name] : $default;
    }
    /**
    * Returns the current refresh token
    */
    public function NAW_getRefreshToken()
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
    public function NAW_setVariable($name, $value)
    {
        $this->conf[$name] = $value;
        return $this;
    }
    private function NAW_updateSession()
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
    private function NAW_setTokens($value)
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
    public function NAW_setTokensFromStore($value)
    {
         if(isset($value["access_token"]))
            $this->access_token = $value["access_token"];
        if(isset($value["refresh_token"]))
            $this->refresh_token = $value["refresh_token"];
    }
    public function NAW_unsetTokens()
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
    public function NAW___construct($config = array())
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
    public function NAW_makeRequest($path, $method = 'GET', $params = array())
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
    public function NAW_getAccessToken()
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
    public function NAW_getAuthorizeUrl($state = null)
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
    * This function NAW_will only be activated if both access token URI, client
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
    private function NAW_getAccessTokenFromAuthorizationCode($code)
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
   * This function NAW_will only be activated if both username and password
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
    private function NAW_getAccessTokenFromPassword($username, $password)
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
    * This function NAW_will only be activated if both username and password
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
    private function NAW_getAccessTokenFromRefreshToken()
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
    protected function NAW_makeOAuth2Request($path, $method = 'GET', $params = array(), $reget_token = true)
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
    public function NAW_api($path, $method = 'GET', $params = array(), $secure = false)
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
    public function NAW_noTokenApi($path, $method = 'GET', $params = array())
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
    static public function NAW_str_replace_once($str_pattern, $str_replacement, $string)
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
    function NAW_getRequestUri()
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
    protected function NAW_getCurrentUri()
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
    protected function NAW_getRedirectUri()
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
    protected function NAW_getUri($path = '', $params = array(), $secure = false)
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
    public function NAW_getPartnerDevices()
    {
        return $this->api("partnerdevices", "POST");
    }
    /**
    * @param string url : webhook url
    * @param string app_type : type of webhook
    * @brief register a webhook notification sent to your app for the current user
    */
    protected function NAW_addWebhook($url, $app_type)
    {
        $params = array('url' => $url, 'app_type' => $app_type);
        $this->api('addwebhook', $params);
    }
    /**
    * @param string $app_type: type of webhook
    * @brief drop webhook notification for the current user
    */
    protected function NAW_dropWebhook($app_type)
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
    public function NAW___construct($client)
    {
        $this->client = $client;
    }
    public function NAW_api($method, $action, $params = array())
    {
        if(isset($this->client))
            return $this->client->api($method, $action, $params);
        else return NULL;
    }
    public function NAW_simplifyDeviceList($app_type = "app_station")
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
    public function NAW_getMeasure($device, $device_type, $date_begin, $module=null, $module_type = null)
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
    public function NAW_getLastMeasures()
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
    public function NAW_getAllMeasures($date_begin)
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

class NAThermZone 
{
const THERMOSTAT_SCHEDULE_SLOT_DAY      = 0x00;
const THERMOSTAT_SCHEDULE_SLOT_NIGHT    = 0x01;
const THERMOSTAT_SCHEDULE_SLOT_AWAY     = 0x02;
const THERMOSTAT_SCHEDULE_SLOT_HG       = 0x03;
const THERMOSTAT_SCHEDULE_SLOT_PERSO    = 0x04;
const THERMOSTAT_SCHEDULE_SLOT_ECO      = 0x05;
const THERMOSTAT_SCHEDULE_HOT_WATER_ON  = 0x06;
const THERMOSTAT_SCHEDULE_HOT_WATER_OFF = 0x07;
}


/**
* Exception thrown by Netatmo SDK
*/
class NASDKException extends Exception
{
    public function NAW___construct($code, $message)
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
    public function NAW___construct($code, $message, $error_type)
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
    function NAW___construct($code, $message, $result)
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
    function NAW___construct($code, $message)
    {
        parent::__construct($code, $message, CURL_ERROR_TYPE);
    }
}

class NAJsonErrorType extends NAClientException
{
    function NAW___construct($code, $message)
    {
        parent::__construct($code, $message, JSON_ERROR_TYPE);
    }
}

class NAInternalErrorType extends NAClientException
{
    function NAW___construct($message)
    {
        parent::__construct(0, $message, INTERNAL_ERROR_TYPE);
    }
}

class NANotLoggedErrorType extends NAClientException
{
    function NAW___construct($code, $message)
    {
        parent::__construct($code, $message, NOT_LOGGED_ERROR_TYPE);
    }
}


/**
 * NETATMO WEATHER STATION API PHP CLIENT
 *
 * For more details upon NETATMO API Please check https://dev.netatmo.com/doc
 * @author Originally written by Enzo Macri <enzo.macri@netatmo.com>
 */
class NAWSApiClient extends NAApiClient
{

  /*
   * @type PRIVATE & PARTNER API
   * @param string $device_id
   * @param bool $get_favorites : used to retrieve (or not) user's favorite public weather stations
   * @return array of devices
   * @brief Method used to retrieve data for the given weather station or all weather station linked to the user
   */
   public function NAW_getData($device_id = NULL, $get_favorites = TRUE)
   {
       return $this->api('getstationsdata', 'GET', array($device_id, $get_favorites));
   }

   /*
    * @type PUBLIC, PRIVATE & PARTNER API
    * @param string $device_id
    * @param string $module_id (optional) if specified will retrieve the module's measurements, else it will retrieve the main device's measurements
    * @param string $scale : interval of time between two measurements. Allowed values : max, 30min, 1hour, 3hours, 1day, 1week, 1month
    * @param string $type : type of measurements you wanna retrieve. Ex : "Temperature, CO2, Humidity".
    * @param timestamp (utc) $start (optional) : starting timestamp of requested measurements
    * @param timestamp (utc) $end (optional) : ending timestamp of requested measurements.
    * @param int $limit (optional) : limits numbers of measurements returned (default & max : 1024)
    * @param bool $optimize (optional) : optimize the bandwith usage if true. Optimize = FALSE enables an easier result parsing
    * @param bool $realtime (optional) : Remove time offset (+scale/2) for scale bigger than max
    * @return array of measures and timestamp
    * @brief Method used to retrieve specifig measures of the given weather station
    */
   public function NAW_getMeasure($device_id, $module_id, $scale, $type, $start = NULL, $end = NULL, $limit = NULL, $optimize = NULL, $realtime = NULL)
   {
        $params = array('device_id' => $device_id,
                        'scale' => $scale,
                        'type' => $type);

        $optionals = array('module_id' => $module_id,
                           'date_begin' => $start,
                           'date_end' => $end,
                           'limit' => $limit,
                           'optimize' => $optimize,
                           'real_time' => $realtime);
        foreach($optionals as $key => $value)
        {
            if(!is_null($value)) $params[$key] = $value;
        }

       return $this->api('getmeasure', 'GET', $params);
   }

   public function NAW_getRainMeasure($device_id, $rainGauge_id, $scale, $start = NULL, $end = NULL, $limit = NULL, $optimize = NULL, $realtime = NULL)
   {
       if($scale === "max")
       {
           $type = "Rain";
       }
       else $type = "sum_rain";

       return $this->getMeasure($device_id, $rainGauge_id, $scale, $type, $start, $end, $limit, $optimize, $realtime);
   }

   public function NAW_getWindMeasure($device_id, $windSensor_id, $scale, $start = NULL, $end = NULL, $limit = NULL, $optimize = NULL, $realtime = NULL)
   {
       $type = "WindStrength,WindAngle,GustStrength,GustAngle,date_max_gust";
       return $this->getMeasure($device_id, $windSensor_id, $scale, $type, $start, $end, $limit, $optimize, $realtime);
   }

}






/**
* Class NAEvent
*/
class NAEvent extends NAObjectWithPicture
{
    private static $videoEvents = array(NACameraEventType::CET_PERSON, NACameraEventType::CET_MOVEMENT);

    /**
    *
    * @brief returns event's snapshot
    */
    public function NAW_getSnapshot()
    {
        $snapshot = $this->getVar(NACameraEventInfo::CEI_SNAPSHOT);
        return $this->getPictureURL($snapshot);
    }

    /**
    * @return string
    * @brief returns event's description
    */
    public function NAW_getMessage()
    {
        return $this->getVar(NACameraEventInfo::CEI_MESSAGE);
    }

    /**
    * @return timestamp
    * @brief returns at which time the event has been triggered
    */
    public function NAW_getTime()
    {
        return $this->getVar(NACameraEventInfo::CEI_TIME);
    }

    /**
    * @return string
    * @brief returns the event's type
    */
    public function NAW_getEventType()
    {
        return $this->getVar(NACameraEventInfo::CEI_TYPE);
    }

    /**
    * @return int
    * @brief returns event's subtype for SD Card & power adapter events
    * @throw NASDKException
    */
    public function NAW_getEventSubType()
    {
        if($this->getEventType() === NACameraEventType::CET_SD
            || $this->getEventType() === NACameraEventType::CET_ALIM)
        {
            return $this->getVar(NACameraEventInfo::CEI_SUB_TYPE);
        }
        else throw new NASDKException(NASDKError::INVALID_FIELD, "This field does not exist for this type of event");
    }

    /**
    * @return string
    * @brief returns id of the camera that triggered the event
    */
    public function NAW_getCameraId()
    {
        return $this->getVar(NACameraEventInfo::CEI_CAMERA_ID);
    }

    /**
    * @return string
    * @brief returns id of the person seen in the event
    * @throw NASDKException
    */
    public function NAW_getPersonId()
    {
        if($this->getEventType() === NACameraEventType::CET_PERSON
            || $this->getEventType() === NACameraEventType::CET_PERSON_AWAY
        )
        {
            return $this->getVar(NACameraEventInfo::CEI_PERSON_ID);
        }
        else throw new NASDKException(NASDKError::INVALID_FIELD, "This field does not exist for this type of event");

    }

    public function NAW_hasVideo()
    {
        if(in_array($this->getEventType(), $this->videoEvents))
            return TRUE;
        else return FALSE;
    }

    /**
    * @return string
    * @brief returns event's video id
    * @throw NASDKException
    */
    public function NAW_getVideo()
    {
        if($this->hasVideo())
            return $this->getVar(NACameraEventInfo::CEI_VIDEO_ID);
        else throw new NASDKException(NASDKError::INVALID_FIELD, "This type of event does not have videos");
    }

    /**
    * @return string
    * @brief returns event's video status
    * @throw NASDKException
    */
    public function NAW_getVideoStatus()
    {
        if($this->hasVideo())
            return $this->getVar(NACameraEventInfo::CEI_VIDEO_STATUS);
        else throw new NASDKException(NASDKError::INVALID_FIELD, "This type of event does not have videos");

    }

    /**
    * @return boolean
    * @brief returns whether or not this event corresponds to the moment where the person arrived home
    * @throw NASDKException
    */
    public function NAW_isPersonArrival()
    {
        if($this->getEventType() === NACameraEventType::CET_PERSON)
        {
            return $this->getVar(NACameraEventInfo::CEI_IS_ARRIVAL);
        }
        else throw new NASDKException(NASDKError::INVALID_FIELD, "This field does not exist for this type of event");

    }
}





/**
* NAObject Class
* Abstact class, parent of every objects
*/
abstract class NAObject
{
    protected $object = array();

    public function NAW___construct($array)
    {
        $this->object = $array;
    }

    /**
    * @param string field : array key
    * @param $default : default value in case field is not set
    * @return object field or default if field is not set
    * @brief returns an object's field
    */
    public function NAW_getVar($field, $default = NULL)
    {
        if(isset($this->object[$field]))
            return $this->object[$field];
        else return $default;
    }

    /**
    * @param string $field : field to be set
    * @param $value value to set to field
    * @brief set an object's field
    */
    public function NAW_setVar($field, $value)
    {
        $this->object[$field] = $value;
    }

    /**
    * @return id
    * @btief returns object id
    */
    public function NAW_getId()
    {
        return $this->getVar("id");
    }

    /**
    * @return array $object
    * @brief return this object as an array
    */
    public function NAW_toArray()
    {
        return $this->object;
    }

    /**
    * @return JSON document
    * @brief returns object as a JSON document
    */
    public function NAW_toJson()
    {
        return json_encode($this->toArray());
    }

    /**
    * @return string
    * @brief return string representation of object : JSON doc
    */
    public function NAW___toString()
    {
        return $this->toJson();
    }

}

abstract class NAObjectWithPicture extends NAObject
{
    public function NAW_getPictureURL($picture, $baseURI = 'https://api.netatmo.com/api/getcamerapicture')
    {
        if(isset($picture[NACameraImageInfo::CII_ID]) && isset($picture[NACameraImageInfo::CII_KEY]))
        {
            return $baseURI.'?image_id='.$picture[NACameraImageInfo::CII_ID].'&key='.$picture[NACameraImageInfo::CII_KEY];
        }
        else return NULL;

    }

}


/**
* class NAPerson
*/
class NAPerson extends NAObjectWithPicture
{
    /**
    * @return bool
    * @brief returns whether or not this person is known
    */
    public function NAW_isKnown()
    {
        if($this->getVar(NACameraPersonInfo::CPI_PSEUDO, FALSE))
            return TRUE;
        else return FALSE;
    }

    /**
    * @return bool
    * @brief returns whether or not this person is unknown
    */

    public function NAW_isUnknown()
    {
        return !$this->isKnown();
    }

    /**
    * @return bool
    * @brief returns whether or not this person is at home
    */
    public function NAW_isAway()
    {
        return $this->getVar(NACameraPersonInfo::CPI_OUT_OF_SIGHT);
    }

    public function NAW_getFace()
    {
        $face = $this->getVar(NACameraPersonInfo::CPI_FACE);
        return $this->getPictureURL($face);
    }

    /**
    * @return timestamp
    * @brief returns last time this person has been seen
    */
    public function NAW_getLastSeen()
    {
        return $this->getVar(NACameraPersonInfo::CPI_LAST_SEEN);
    }

    /**
    * @return string
    * @brief returns this person's name
    */
    public function NAW_getPseudo()
    {
        return $this->getVar(NACameraPersonInfo::CPI_PSEUDO);
    }
}


/**
* Class NAHome
*
*/
class NAHome extends NAObject
{

    public function NAW___construct($array)
    {
        parent::__construct($array);

        if(isset($array[NACameraHomeInfo::CHI_PERSONS]))
        {
            $personArray = array();
            foreach($array[NACameraHomeInfo::CHI_PERSONS] as $person)
            {
                $personArray[] = new NAPerson($person);
            }
            $this->object[NACameraHomeInfo::CHI_PERSONS] = $personArray;
        }

        if(isset($array[NACameraHomeInfo::CHI_EVENTS]))
        {
            $eventArray = array();
            foreach($array[NACameraHomeInfo::CHI_EVENTS] as $event)
            {
                $eventArray[] = new NAEvent($event);
            }
            $this->object[NACameraHomeInfo::CHI_EVENTS] = $eventArray;
        }

        if(isset($array[NACameraHomeInfo::CHI_CAMERAS]))
        {
            $cameraArray = array();
            foreach($array[NACameraHomeInfo::CHI_CAMERAS] as $camera)
            {
                $cameraArray[] = new NACamera($camera);
            }
            $this->object[NACameraHomeInfo::CHI_CAMERAS] = $cameraArray;
        }
    }

    /**
    * @return string
    * @brief returns home's name
    */
    public function NAW_getName()
    {
        return $this->getVar(NACameraHomeInfo::CHI_NAME);
    }

    /**
    * @return array of event objects
    * @brief returns home timeline of event
    */
    public function NAW_getEvents()
    {
        return $this->getVar(NACameraHomeInfo::CHI_EVENTS, array());
    }

    /**
    * @return array of person objects
    * @brief returns every person belonging to this home
    */
    public function NAW_getPersons()
    {
        return $this->getVar(NACameraHomeInfo::CHI_PERSONS, array());
    }

    /**
    * @return array of person objects
    * @brief returns every known person belonging to this home
    */
    public function NAW_getKnownPersons()
    {
        $knowns = array();
        foreach($this->getVar(NACameraHomeInfo::CHI_PERSONS, array()) as $person)
        {
            if($person->isKnown())
                $knowns[] = $person;
        }
        return $knowns;
    }

    /**
    * @return array of person objects
    * @brief returns every unknown person belonging to this home
    */
    public function NAW_getUnknownPersons()
    {
        $unknowns = array();
        foreach($this->getVar(NACameraHomeInfo::CHI_PERSONS, array()) as $person)
        {
            if($person->isUnknown())
                $unknowns[] = $person;
        }
        return $unknowns;
    }

    /**
    * @return array of camera objects
    * @brief returns every camera belonging to this home
    */
    public function NAW_getCameras()
    {
        return $this->getVar(NACameraHomeInfo::CHI_CAMERAS, array());
    }

    /**
    * @return string
    * @brief returns home's timezone
    */
    public function NAW_getTimezone()
    {
        $place = $this->getVar(NACameraHomeInfo::CHI_PLACE);
        return isset($place['timezone'])? $place['timezone'] : 'GMT';
    }

    /**
    * @return NACamera
    * @brief return the camera object corresponding to the id asked
    * @throw NASDKErrorException
    */
    public function NAW_getCamera($camera_id)
    {
        foreach($this->getVar(NACameraHomeInfo::CHI_CAMERAS, array()) as $camera)
        {
            if($camera->getId() === $camera_id)
            {
                return $camera;
            }
        }
        throw new NASDKException(NASDKError::NOT_FOUND, "camera $camera_id not found in home: " . $this->getId());
    }

    /**
    * @return NAPerson
    * @brief returns NAPerson object corresponding to the id in parameter
    * @throw NASDKErrorException
    */
    public function NAW_getPerson($person_id)
    {
        foreach($this->getVar(NACameraHomeInfo::CHI_PERSONS, array()) as $camera)
        {
            if($person->getId() === $person_id)
                return $person;
        }

        throw new NASDKException(NASDKError::NOT_FOUND, "person $person_id not found in home: " . $this->getId());
    }

    /**
    * @return array of NAPerson
    * @brief returns every person that are not home
    */
    public function NAW_getPersonAway()
    {
        $away = array();

        foreach($this->getVar(NACameraHomeInfo::CHI_PERSONS, array()) as $person)
        {
            if($person->isAway())
                $away[] = $person;
        }
        return $away;
    }

    /**
    * @return array of NAPerson
    * @brief returns every person that are home
    */
    public function NAW_getPersonAtHome()
    {
        $home = array();

        foreach($this->getVar(NACameraHomeInfo::CHI_PERSONS, array()) as $person)
        {
            if(!$person->isAway())
                $home[] = $person;
        }
        return $home;
    }
}

/**
* Class NACamera
*
*/
class NACamera extends NAObject
{
    public function NAW_getGlobalStatus()
    {
        $on_off = $this->getVar(NACameraInfo::CI_STATUS);
        $sd = $this->getVar(NACameraInfo::CI_SD_STATUS);
        $power = $this->getVar(NACameraInfo::CI_ALIM_STATUS);

        if($on_off === NACameraStatus::CS_ON
            && $sd === NACameraStatus::CS_ON
            && $power === NACameraStatus::CS_ON)
        {
            return TRUE;
        }
        return FALSE;
    }

    /**
    * @return string $name
    * @brief returns the camera name
    */
    public function NAW_getName()
    {
        return $this->getVar(NACameraInfo::CI_NAME);
    }

    /**
    * @return string $vpn_url
    * @brief returns the vpn_url of the camera
    * @throw new NASDKErrorException
    */
    public function NAW_getVpnUrl()
    {
        if(!is_null($this->getVar(NACameraInfo::CI_VPN_URL)))
            return $this->getVar(NACameraInfo::CI_VPN_URL);
        else throw new NASDKErrorException(NASDKErrorCode::FORBIDDEN_OPERATION, "You don't have access to this field due to the scope of your application");
    }

    /**
    * @return boolean $is_local
    * @brief returns whether or not the camera shares the same public address than this application
    * @throw new NASDKErrorException
    */
    public function NAW_isLocal()
    {
        if(!is_null($this->getVar(NACameraInfo::CI_IS_LOCAL)))
            return $this->getVar(NACameraInfo::CI_IS_LOCAL);
        else throw new NASDKErrorException(NASDKErrorCode::FORBIDDEN_OPERATION, "You don't have access to this field due to the scope of your application");
    }

    public function NAW_getSDCardStatus()
    {
        return $this->getVar(NACameraInfo::CI_SD_STATUS);
    }

    public function NAW_getPowerAdapterStatus()
    {
        return $this->getVar(NACameraInfo::CI_ALIM_STATUS);
    }

    public function NAW_getMonitoringStatus()
    {
        return $this->getVar(NACameraInfo::CI_STATUS);
    }
}



?>

