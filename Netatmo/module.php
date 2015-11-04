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

			$client = new NAApiClient(array("client_id" => $client_id, "client_secret" => $client_secret, "username" => $test_username, "password" => $test_password, "scope" => $scope));
			$helper = new NAApiHelper($client);

			try {
 			$tokens = $client->getAccessToken();

			} catch(NAClientException $ex) {
				echo "An error happend while trying to retrieve your tokens\n";
 				exit(-1);
			}


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
require_once 'NAApiClient.php';
require_once 'Config.php';



try
{
$deviceList = $client->api("devicelist");
//echo "Station: " . $deviceList["devices"][0]["station_name"] . "; Device ID: " . $deviceList["devices"][0]["_id"] . "; Modulname: " . $deviceList["devices"][0]["module_name"] ."\n";
//echo "Aussenmodule: ID: ".  $deviceList["modules"][0]["_id"]. "; Modulname: " . $deviceList["modules"][0]["module_name"] ."\n";
//echo "regenmmodule: ID: ".  $deviceList["modules"][1]["_id"]. "; Modulname: " . $deviceList["modules"][1]["module_name"] ."\n";
//echo "\n";
$carport = $deviceList["modules"][0]["_id"];
$regen = $deviceList["modules"][1]["_id"];
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
