<?

private function CreateCategoryByIdent($id, $ident, $name)
 {
 $cid = @IPS_GetObjectIDByIdent($this->maskUmlaute($ident), $id);
 if($cid === false)
 {
				 $cid = IPS_CreateCategory();
				 IPS_SetParent($cid, $id);
				 IPS_SetName($cid, $name);
				 IPS_SetIdent($cid, $this->maskUmlaute($ident));
			 }
			 return $cid;
		}
		
private function CreateVariableByIdent($id, $ident, $name, $value, $type, $profile = "")
		 {
			 $vid = @IPS_GetObjectIDByIdent($this->maskUmlaute($ident), $id);
		 if($vid === false)
			 {
				 $vid = IPS_CreateVariable($type);
				 IPS_SetParent($vid, $id);
				 IPS_SetName($vid, $name);
				 IPS_SetIdent($vid, $this->maskUmlaute($ident));
				 if($profile != "")
					IPS_SetVariableCustomProfile($vid, $profile);
			 }
	
			SetValue($vid,$value);
		//	 IPS_LogMessage('NETATMO',$name .": " . $value);
			 return $vid;
		}
		
private function CreateInstanceByIdent($id, $ident, $name, $moduleid = "{24B57877-C24C-4690-8421-B41DCC22BE1B}")
	 {
			 $iid = @IPS_GetObjectIDByIdent($this->maskUmlaute($ident), $id);
			 if($iid === false)
			 {
				 $iid = IPS_CreateInstance($moduleid);
				 IPS_SetParent($iid, $id);
				 IPS_SetName($iid, $name);
				 IPS_SetIdent($iid, $this->maskUmlaute($ident));
			 }
			 return $iid;
		}	
		
		
		?>
