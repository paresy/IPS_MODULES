<?

//require_once(__DIR__ . "/netatmo.php");  // Netatmo Helper Klasse
require_once(__DIR__ . "/netatmo_api/Clients/NAWSApiClient.php");
require_once(__DIR__ . "/netatmo_api/Constants/AppliCommonPublic.php");
require_once(__DIR__ . "/netatmo_api/Utils.php");
require_once(__DIR__ . "/netatmo_api/Config.php");

    // Klassendefinition

    
    class Netatmo extends IPSModule {
    	
    private $client ;
    private $tokens ;     	
    private $refresh_token ;
    private $access_token ;
    private $deviceList;
        // Der Konstruktor des Moduls
        // Überschreibt den Standard Kontruktor von IPS
        public function __construct($InstanceID) {
            // Diese Zeile nicht löschen
            parent::__construct($InstanceID);
 
            // Selbsterstellter Code
        }
 
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() {
            // Diese Zeile nicht löschen.
        	parent::Create();
	$this->RegisterPropertyString("username", "");
	$this->RegisterPropertyString("password", "");
	$this->RegisterPropertyString("client_id", "");
	$this->RegisterPropertyString("client_secret", "");
        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();
      IPS_LogMessage(__CLASS__, __FUNCTION__); //                   
       IPS_LogMessage('Config', print_r(json_decode(IPS_GetConfiguration($this->InstanceID)), 1));
   	$this->PrepareConnection();
        }
 
	private function PrepareConnection() 
	{
 	global $client;
    	global $tokens ;     	
    	global $refresh_token ;
    	global $access_token ;
    	
	$config = array();
	$config['client_id'] = $this->ReadPropertyString("client_id");
	$config['client_secret'] = $this->ReadPropertyString("client_secret");
	//application will have access to station and theromstat
	$config['scope'] = "read_station";
	$client = new NAWSApiClient($config);
    		
    	$username = $this->ReadPropertyString("username");
	$pwd = $this->ReadPropertyString("password");
	$client->setVariable("username", $username);
	$client->setVariable("password", $pwd);

	//Authentication with Netatmo server (OAuth2)
try
{
    $tokens = $client->getAccessToken();
}
catch(NAClientException $ex)
{
    handleError("An error happened while trying to retrieve your tokens: " .$ex->getMessage()."\n", TRUE);
	     	 IPS_LogMessage(__CLASS__, "ALL OK !!!!");
		$this->SetStatus(102);// login OK
     		}
	
    
    }
 
	
	public function GetData() {
	
	global $client;
    	global $tokens ;     	
    	global $refresh_token ;
    	global $access_token ;
	global $deviceList;
    	
	$this->PrepareConnection();	
	
//	$deviceList = $client->api("devicelist");	
//	 IPS_LogMessage(__CLASS__, "Devicelist: ". print_r($deviceList ,1));	
//	 echo print_r($deviceList);
		
		
	//Retrieve user's Weather Stations Information
try
{
    //retrieve all stations belonging to the user, and also his favorite ones
    $data = $client->getData(NULL, TRUE);
    printMessageWithBorder("Weather Stations Basic Information");
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
	
		
	}
	

    }
?>
