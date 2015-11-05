<?

require_once(__DIR__ . "/netatmo.php");  // Netatmo Helper Klasse

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
 
    
	

    }
?>
