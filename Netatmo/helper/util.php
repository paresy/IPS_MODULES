<?

	function handleError($message, $exit = FALSE)
	{
		$this->echoLog( $message);
    		if($exit)
    		{
        		exit(-1);
    		}
	}
	
	function printTimeInTz($time, $timezone, $format)
	{
    		try
    		{
			$tz = new DateTimeZone($timezone);
    		}
    		catch(Exception $ex)
    		{
        		$tz = new DateTimeZone("GMT");
    		}
    		$date = new DateTime();
    		$date->setTimezone($tz);
    		$date->setTimestamp($time);
    		$this->echoLog( $date->format($format));
	}
	
	function printBorder($message)
	{
    		$size = strlen($message);
    		for($i = 0; $i < $size; $i++)
    		{
        		$this->echoLog("-");
    		}	
    		$this->echoLog("\n");
	}
	
	function printMessageWithBorder($message)
	{
    		$message = "- " . $message . " -";
    		$this->printBorder($message);
    		$this->echoLog($message . "\n");
    		$this->printBorder($message);
	}

	function echoLog($message) 
	{
		global $echoString;	
		$echoString = $echoString . $message;
	}

	function printMeasure($measurements, $type, $tz, $title = NULL, $monthly = FALSE)
	{
		if(!empty($measurements))
    		{
        		if(!empty($title))
        		{
            			$this->printMessageWithBorder($title);
        		}
        		if($monthly)
        		{
	         		$dateFormat = 'F: ';
		     	}
		    	else 
		 	{	
		       		$dateFormat = 'j F: ';
		       	}
		       	//array of requested info type, needed to map result values to what they mean
		       	$keys = explode(",", $type);
		       	foreach($measurements as $timestamp => $values)
        		{
        	  		$this->printTimeinTz($timestamp, $tz, $dateFormat);
        	   		$this->echoLog("\n");
        	   		foreach($values as $key => $val)
        	   		{
        		       		$this->echoLog( $keys[$key] . ": ");
        		     		if($keys[$key] === "time_utc" || preg_match("/^date_.*/", $keys[$key]))
        		   		{
                	 			$this->echoLog ($this->printTimeInTz($val, $tz, "j F H:i"));
                			}
                			else
                			{
                	 			$this->echoLog( $val);
                	  			$this->printUnit($keys[$key]);
                			}	
                			if(count($values)-1 === $key || $monthly)
                			{
                	  			$this->echoLog( "\n");
                			}
                			else 
                			{	
                				$this->echoLog( ", ");
                			}
            			}
        		}
    		}
	}

	/**
 	* function printing a weather station or modules basic information such as id, name, dashboard data, modules (if main device), type(if module)
 	*
 	*/
	function printWSBasicInfo($device)
	{
		if(isset($device['station_name']))
		{
			$this->echoLog("- ".$device['station_name']. " -\n");
		}
    		else if(isset($device['module_name']))
        	{
        		$this->echoLog("- ".$device['module_name']. " -\n");
    			$this->echoLog("id: " . $device['_id']. "\n");
        	}
    		if(isset($device['type']))
    		{
        		$this->echoLog("type: ");
        		switch($device['type'])
        		{
            			// Outdoor Module
            			case "NAModule1": $this->echoLog("Outdoor\n");
                              		break;
            			//Wind Sensor
            			case "NAModule2": $this->echoLog("Wind Sensor\n");
                        		break;
            			//Rain Gauge
            			case "NAModule3": $this->echoLog("Rain Gauge\n");
                              		break;
            			//Indoor Module
            			case "NAModule4": $this->echoLog("Indoor\n");
                              		break;
            			case "NAMain" : $this->echoLog("Main device \n");
                            		break;
        		}
    		}
    		if(isset($device['place']['timezone']))
        		$tz = $device['place']['timezone'];
    		else 
    		{
    			$tz = 'GMT';
    		}
    		if(isset($device['dashboard_data']))
    		{
        		$this->echoLog("Last data: \n");
        		foreach($device['dashboard_data'] as $key => $val)
        		{
            			if($key === 'time_utc' || preg_match("/^date_.*/", $key))
            			{
                			$this->echoLog( $key .": ");
                			$this->printTimeInTz($val, $tz, 'j F H:i');
                			$this->echoLog("\n");
            			}
            			else if(is_array($val))
            			{
                			//do nothing : don't print historic
            			}
            			else 
            			{
                			$this->echoLog($key .": " . $val);
                			$this->printUnit($key);
                			$this->echoLog( "\n");
            			}
        		}
    		}
        	if(isset($device['modules']))
        	{
            		$this->echoLog(" \n\nModules: \n");
            		foreach($device['modules'] as $module)
                		$this->printWSBasicInfo($module);
        	}
    	$this->echoLog("       ----------------------   \n");
	}
	
function printUnit($key)
{
    $typeUnit = array('temp' => '°C', 'hum' => '%', 'noise' => 'db', 'strength' => 'km/h', 'angle' => '°', 'rain' => 'mm', 'pressure' => 'mbar', 'co2' => 'ppm');
    foreach($typeUnit as $type => $unit)
    {
        if(preg_match("/.*$type.*/i", $key))
        {
            $this->echoLog( " ".$unit);
            return;
        }
    }
}
/** THERM Utils function **/
/*
* @brief print a thermostat basic information in CLI
*/
function printThermBasicInfo($dev)
{
    //Device
    $this->echoLog(" -".$dev['station_name']."- \n");
    $this->echoLog(" id: ".$dev['_id']." \n");
    $this->echoLog("Modules : \n");
    // Device's modules info
    foreach($dev['modules'] as $module)
    {
        $this->echoLog("    - ".$module['module_name']." -\n");
        //module last measurements
        $this->echoLog("    Last Measure date : ");
        $this->printTimeInTz($module['measured']['time'], $dev['place']['timezone'], 'j F H:i');
        $this->echoLog("\n");
        $this->echoLog("    Last Temperature measured: ". $module['measured']['temperature']);
        $this->printUnit("temperature");
        $this->echoLog("\n");
        $this->echoLog("    Last Temperature setpoint: ". $module['measured']['setpoint_temp']);
        $this->printUnit('setpoint_temp');
        $this->echoLog("\n");
        $this->echoLog("    Program List: \n");
        //program list
        foreach($module['therm_program_list'] as $program)
        {
            if(isset($program['name']))
                $this->echoLog("        -".$program['name']."- \n");
            else $this->echoLog("        -Standard- \n");
            $this->echoLog("        id: ".$program['program_id']." \n");
            if(isset($program['selected']) && $program['selected'] === TRUE)
            {
                $this->echoLog( "         This is the current program \n");
            }
        }
    }
}
/**
* @brief returns the current program of a therm module
*/
function getCurrentProgram($module)
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
function getCurrentMode($module)
{
    $initialMode = $module["setpoint"]["setpoint_mode"];
    $initialTemp = isset($module["setpoint"]["setpoint_temp"]) ? $module["setpoint"]["setpoint_temp"]: NULL;
    $initialEndtime = isset($module['setpoint']['setpoint_endtime']) ? $module['setpoint']['setpoint_endtime'] : NULL;
    return array($initialMode, $initialTemp, $initialEndtime);
}


function removeHTMLTags($string)
{
   return preg_replace("/<.*?>/", "", $string);
}


 function RegisterTimer($ident, $interval, $script) {
    $id = @IPS_GetObjectIDByIdent($ident, $this->InstanceID);

    if ($id && IPS_GetEvent($id)['EventType'] <> 1) {
      IPS_DeleteEvent($id);
      $id = 0;
    }

    if (!$id) {
      $id = IPS_CreateEvent(1);
      IPS_SetParent($id, $this->InstanceID);
      IPS_SetIdent($id, $ident);
    }

    IPS_SetName($id, $ident);
    IPS_SetHidden($id, true);
    IPS_SetEventScript($id, "\$id = \$_IPS['TARGET'];\n$script;");

    if (!IPS_EventExists($id)) throw new Exception("Ident with name $ident is used for wrong object type");

    if (!($interval > 0)) {
      IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, 1);
      IPS_SetEventActive($id, false);
    } else {
      IPS_SetEventCyclic($id, 0, 0, 0, 0, 1, $interval);
      IPS_SetEventActive($id, true);
    }
  }  
  ?>
