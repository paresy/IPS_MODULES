<?

//require_once(__DIR__ . "/netatmo.php");  // Netatmo Helper Klasse
require_once(__DIR__ . "/netatmo_api/Clients/NAApiClient.php");
//require_once(__DIR__ . "/netatmo_api/Utils.php");
//require_once(__DIR__ . "/netatmo_api/Config.php");

    // Klassendefinition
    
    class Netatmo extends IPSModule {
 
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
        }
 
    	public function GetDeviceList() {
    		
    	

	$config = array();
	$config['client_id'] = $this->ReadPropertyInteger("client_id");
	$config['client_secret'] = $this->ReadPropertyInteger("client_secret");
	//application will have access to station and theromstat
	$config['scope'] = 'read_station';
	$this->$client = new NAApiClient($config);
    		
    	$username = $this->ReadPropertyInteger("username");
	$pwd = $this->ReadPropertyInteger("password");
	$client->setVariable("username", $username);
	$client->setVariable("password", $pwd);
	try
	{
		$tokens = $client->getAccessToken();        
		$refresh_token = $tokens["refresh_token"];
		$access_token = $tokens["access_token"];
	}
	catch(NAClientException $ex)
	{
    	echo "An error happend while trying to retrieve your tokens\n";
	}	
    		
    		
    	}
	

    }
?>
