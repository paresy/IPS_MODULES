<?
﻿class Oscam extends IPSModule {
    	
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
	$this->RegisterPropertyString("serverurl", "");
	//$this->RegisterTimer("ReadOscam", 300, 'OSC_getData($_IPS[\'TARGET\']);');
        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();
      IPS_LogMessage(__CLASS__, __FUNCTION__); //                   
       IPS_LogMessage('Config', print_r(json_decode(IPS_GetConfiguration($this->InstanceID)), 1));
 //  	$this->checkConnection();
   	$this->SetStatus(102);// login OK
        }
        
        
       
public function getData(){
$url = $this->ReadPropertyString("serverurl");
$user = $this->ReadPropertyString("username");
$pw = $this->ReadPropertyString("password");
$context  = stream_context_create(array('http' => array('header' => 'Accept: application/xml')));
$url = "$url/oscamapi.html?part=userstats";
$ch = curl_init();
$file =fopen ("c:\\tmp\\temp.html", "a+" );
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt ($ch ,CURLOPT_FILE ,$file );
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "$user:$pw");
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0)");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
$output = curl_exec($ch);
curl_close($ch);

//$xml= stream_get_contents($output);
//$xml = file_get_contents($url, true, $context);
$xml = simplexml_load_string($output);
//print_r ($xml);
//var_dump($xml);
$start=($xml->xpath('/oscam')[0]->attributes()[2][0]);
$uptime1 =intval($xml->xpath('/oscam')[0]->attributes()[3][0]);
//echo $uptime1;
//$result = mysql_query("SELECT val_num FROM system_params where name = 'oscam_runtime'",$dbh);
//$row = mysql_fetch_assoc($result);
$name= ( "Server");
vars("$name",$this->InstanceID,'Uptime',$uptime1,1,"dapor.zaehler", true,0);
vars("$name",$this->InstanceID,'Start',"$start",3,"", false, 99);
//$val_num = $row['val_num'] ;

//echo "val_num = " .$val_num;

foreach( $xml->xpath('/oscam/users/user') as $child){
//echo $child;
//print_r( $child);
$name= ( $child->attributes()[0][0]);
$status=( $child->attributes()[1][0]);
//echo "status " . $name . " " .$status;
$ip=( $child->attributes()[2][0]);
$cwok=Intval($child->xpath('stats')[0]->cwok);
$cwnok=Intval($child->xpath('stats')[0]->cwnok);
$cwignore=Intval($child->xpath('stats')[0]->cwignore);
$cwtimeout=Intval($child->xpath('stats')[0]->cwtimeout);
$cwcache=Intval($child->xpath('stats')[0]->cwcache);
$cwtun=Intval($child->xpath('stats')[0]->cwtun);
$cwlastresptime=Intval($child->xpath('stats')[0]->cwlastresptime);
$emmok=Intval($child->xpath('stats')[0]->emmok);
$emmnok=Intval($child->xpath('stats')[0]->emmnok);
$timeon=Intval($child->xpath('stats')[0]->timeonchannel);
$cwrate=round(((floatval($child->xpath('stats')[0]->cwrate))),2);
$expectsleep=($child->xpath('stats')[0]->expectsleep);

$position=99;
 if ($cwlastresptime > 0)
    {
    $position=1;
     }
//$system = (vars("$name",14040 /*[Archive Handler]*/,35483,"Name","$name",3,"", false));
vars("$name",$this->InstanceID,'Status',"$status",3,"", false, $position);
vars("$name",$this->InstanceID,'Ip',"$ip",3,"", false, $position);
vars("$name",$this->InstanceID,'OK',$cwok,1,"dapor.zaehler", true, $position);
vars("$name",$this->InstanceID,'Not-OK',$cwnok,1,"dapor.zaehler", true, $position);
vars("$name",$this->InstanceID,'Cache',$cwcache,1,"dapor.zaehler", true, $position);
vars("$name",$this->InstanceID,'Response',$cwlastresptime,1,"dapor.oscam.response", true, $position);
vars("$name",$this->InstanceID,'Belastung',$cwrate,2,"", true, $position);
vars("$name",$this->InstanceID,'TimeOnChannel',$timeon,1,"dapor.zaehler", true, $position);
vars("$name",$this->InstanceID,'EMM-OK',$emmok,1,"dapor.zaehler", true, $position);
vars("$name",$this->InstanceID,'EMM-NotOK',$emmnok,1,"dapor.zaehler", true, $position);
//  insert / update

//echo $query;
//$result = mysql_query ($query, $dbh);

//echo $result;

} // ende foreach
 
//$result = mysql_query("update system_params set val_num = $uptime where name = 'oscam_runtime'",$dbh);
//mysql_close($dbh);
//echo 'OSCAM-> OK';


foreach( $xml->xpath('/oscam/totals') as $child){
//echo $child;
//print_r( $child);
$name= ( "Server");

//echo "status " . $name . " " .$status;

$cwok=Intval($child->cwok);
$cwnok=Intval($child->cwnok);
$cwignore=Intval($child->cwignore);
$cwtimeout=Intval($child->cwtimeout);
$cwcache=Intval($child->cwcache);
//$cwtun=($child->cwtun);

$usertotal=intval($child->usertotal);
$userdisabled=intval($child->userdisabled);
$userexpired=intval($child->userexpired);
$useractive=intval($child->useractive);
$userconnected=intval($child->userconnected);
$useronline=intval($child->useronline);
$position=0;
 
//$system = (vars("$name",14040 /*[Archive Handler]*/,35483,"Name","$name",3,"", false));

//vars("$name",14040 /*[Archive Handler]*/,35483,'Ip',"$ip",3,"", false, $position);
vars("$name",$this->InstanceID,'OK',$cwok,1,"dapor.zaehler", true, $position);
vars("$name",$this->InstanceID,'Not-OK',$cwnok,1,"dapor.zaehler", true, $position);
vars("$name",$this->InstanceID,'Cache',$cwcache,1,"dapor.zaehler", true, $position);
vars("$name",$this->InstanceID,'Ignore',$cwignore,1,"dapor.zaehler", true, $position);
vars("$name",$this->InstanceID,'Timeout',$cwtimeout,1,"dapor.zaehler", true, $position);

vars("$name",$this->InstanceID,'Usertotal', $usertotal,1,"dapor.zaehler", true, $position);
vars("$name",$this->InstanceID,'Userdisabled',$userdisabled,1,"dapor.zaehler", true, $position);
vars("$name",$this->InstanceID,'Userexpired',$userexpired,1,"dapor.zaehler", true, $position);
vars("$name",$this->InstanceID,'Useractive',$useractive,1,"dapor.zaehler", true, $position);
vars("$name",$this->InstanceID,'Userconnected',$userconnected,1,"dapor.zaehler", true, $position);
vars("$name",$this->InstanceID,'Useronline',$useronline,1,"dapor.zaehler", true, $position);


//echo $query;
//$result = mysql_query ($query, $dbh);

//echo $result;

} // ende foreach




function vars($system, $ParentID, $Variname, $wert, $VariTyp, $VariProfile ,$logging, $position)
{
$systemID = @IPS_GetInstanceIDByName($system, $ParentID);
//echo "SYSTEM= ".$system;
 if ($systemID == false)
    {
        $systemID = IPS_CreateInstance("{485D0419-BE97-4548-AA9C-C083EB82E61E}");
        IPS_SetName($systemID,$system);
        IPS_SetParent($systemID,$ParentID);
      IPS_ApplyChanges($systemID);
    }
$VariID = @IPS_GetVariableIDByName($Variname, $systemID);
    if ($VariID == false)
    {
        $VariID = IPS_CreateVariable ($VariTyp);
        IPS_SetVariableCustomProfile($VariID, $VariProfile);
        IPS_SetName($VariID,$Variname);
        IPS_SetParent($VariID,$systemID);
    }
   //     AC_SetLoggingStatus($arhid, $VariID, $logging);
    //        AC_SetGraphStatus($arhid,$VariID, $logging);
    //        IPS_ApplyChanges($arhid);
   // IPS_SetVariableCustomProfile($VariID, $VariProfile);
    SetValue($VariID, $wert);
	 IPS_SetPosition($systemID,  $position);
}



       
       
       
        
        
    }      
        
?>
