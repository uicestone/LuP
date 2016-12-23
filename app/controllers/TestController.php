<?php

function utf8ize($d) {
    if (is_array($d)) {
        foreach ($d as $k => $v) {
            $d[$k] = utf8ize($v);
        }
    } else if (is_string ($d)) {
        return utf8_encode($d);
    }
    return $d;
}

function array_dealed($array) {

    $hrman_pernr = array();
    $hrman_legacy = array();

    foreach($array as $item) {
        if(!in_array($item['ZZHRMAN_PERNR'], $hrman_pernr) && !is_null($item['ZZHRMAN_PERNR'])) {
            array_push($hrman_pernr, $item['ZZHRMAN_PERNR']);
        }
    }

    foreach($array as $item) {
        if(in_array($item['PERNR'], $hrman_pernr)) {
            $hrman_legacy[$item['PERNR']] = $item['ZZLEGACY_ID'];
        }
    }

    foreach($array as &$item) {
        if(array_key_exists($item['ZZHRMAN_PERNR'], $hrman_legacy)) {
            $item['ZZHRMAN_LEGACY_ID'] = $hrman_legacy[$item['ZZHRMAN_PERNR']];
        }
    }

    return $array;
}

function excel_match($input_file) {
    $obj_PHPExcel = PHPExcel_IOFactory::load($input_file);
    return $sheet_data = $obj_PHPExcel->getActiveSheet()->toArray(null, true, true, true);
}

class TestController extends BaseController {

	function index()
	{
		$soi_data = DB::connection('soi')->table('V_ELEAVE_SOI_BASIC_DATA')->get();
        $peoplefinder_data = DB::connection('people_finder')->table('V_PEOPLE_FINDER_SOI_BASIC_DATA')->get();

        $soi_data_array = utf8ize(array_map(function($line)
        {
            return (array) $line;
        },
        $soi_data));

		if(Input::get('type') === 'json')
		{
			$soi_data_json = json_encode($soi_data_array);
			echo $soi_data_json;
		}
		elseif(Input::get('type') === 'excel')
		{
			Excel::create($sftp_file = 'E-Leave-' . date("Y-m-d"), function($excel) use($soi_data_array)
			{ 
                $FID_array = array();
                $EXPATS = array();
                $approvers = array();
                foreach($soi_data_array as $row => &$value) {

                    if(!in_array($value['PERSONID_EXT'], $FID_array)) {
                        array_push($FID_array, $value['PERSONID_EXT']);
                    }

                    if($value['PERSG'] === '8' && !in_array($value['PERSONID_EXT'], $EXPATS)) {
                        array_push($EXPATS, $value['PERSONID_EXT']);
                    }

                    if(!in_array($value['ZZHRMAN_PERSID'], $approvers)) {
                        array_push($approvers, $value['ZZHRMAN_PERSID']);
                    }

                    if(!in_array($value['ZZHRREP_PERSID'], $approvers)) {
                        array_push($approvers, $value['ZZHRREP_PERSID']);
                    }

                    if(!in_array($value['ZZHRNEXT_PERSID'], $approvers)) {
                        array_push($approvers, $value['ZZHRNEXT_PERSID']);
                    }

                    if(!in_array($value['ZZCHIEF_DEP'], $approvers)) {
                        array_push($approvers, $value['ZZCHIEF_DEP']);
                    }

                } 
				$excel->sheet('E-Leave', function($sheet) use($soi_data_array, $FID_array, $EXPATS, $approvers)
				{      
                    $startdate_data = excel_match('working-start-date.xlsx');
                    $cnusers_data = excel_match('chinaadusers.xlsx');
                    
                    foreach($soi_data_array as $row => &$value) {

                        /* Remove user if EXPAT OR base in HONG KONG OR left OR department is EXPAT to APAC */
                        if((in_array($value['PERSONID_EXT'], $EXPATS) && $value['PERSG'] === '9') || $value['BUKRS'] === 'G698' || (!empty($value['ZZTERM_DATE']) && strtotime($value['ZZTERM_DATE']) < strtotime('2016-01-01'))) {
                            unset($soi_data_array[$row]);
                        } else {

                            /* Set next level manager as HRBP if can't find user in rows */
                            if(!in_array($value['ZZHRNEXT_PERSID'], $FID_array)) {
                                $value['ZZHRNEXT_PERSID'] = $value['ZZHRREP_PERSID'];
                            }

                            /* Merge multiple department codes as local one */
                            if($value['ZZDEP_OM_TXT'] === 'SUPPLY CHAIN MANAGEMENT' && $value['ZZDEP_OM'] !== '50015673') {
                                $value['ZZDEP_OM'] = '50015673';
                            }

                            if($value['ZZDEP_OM_TXT'] === 'HUMAN RESOURCES' && $value['ZZDEP_OM'] !== '50012998') {
                                $value['ZZDEP_OM'] = '50012998';
                            }

                            if($value['ZZDEP_OM_TXT'] === 'FINANCE' && $value['ZZDEP_OM'] !== '50019577') {
                                $value['ZZDEP_OM'] = '50019577';
                            }

                            if($value['ZZDEP_OM_TXT'] === 'BUSINESS DEVELOPMENT' && $value['ZZDEP_OM'] !== '50012369') {
                                $value['ZZDEP_OM'] = '50012369';
                            }                        

                            /* Set Product Planning Chief FID as Kim's */
                            if($value['ZZDEP_OM'] === '50019542') {
                                $value['ZZCHIEF_DEP'] = 'F28014236';
                            }

                            /* Change Kelly to Guido in manager & chief column */
                            if($value['ZZHRMAN_PERSID'] === 'F15110910') {
                                $value['ZZHRMAN_PERSID'] = 'F15021867';
                                $value['ZZHRMAN_VORNA'] = 'GUIDO';
                                $value['ZZHRMAN_NACHN'] = 'BONINO';
                                $value['ZZHRMAN_MAIL'] = 'GUIDO.BONINO@FCAGROUP.COM';
                            }

                            if($value['ZZCHIEF_DEP'] === 'F15110910') {
                                $value['ZZCHIEF_DEP'] = 'F15021867';
                                $value['ZZCHIEF_DEP_VORN'] = 'GUIDO';
                                $value['ZZCHIEF_DEP_NACH'] = 'BONINO';
                            }

                            /* Set the chief of department Business Development as Roberto */
                            if($value['ZZDEP_OM'] === '50012369') {
                                $value['ZZCHIEF_DEP'] = 'F15073841';
                            }
                     
                            /* Get work start date from input file */
                            foreach($startdate_data as $item) {
                                if(($item['BB'] !== "#N/A" || $item['BC'] !== "#N/A") && $item['BA'] === $value['PERSONID_EXT']) {
                                    $value['START_DATE'] = date('Y-m-d', strtotime($item['BD']));
                                }
                            }

                            /* Replace TID with CID for China employees */
                            foreach($cnusers_data as $item) {
                                if(strtoupper(substr(trim($item['B']), 1, -1)) === strtoupper(substr(trim($value['ZZUSERID']), 1, -1))) {
                                    
                                    $value['ZZUSERID'] = strtoupper(trim($item['B']));
                                    
                                    if(empty($value['ZZMAIL'])) {
                                        $value['ZZMAIL'] = strtoupper(trim($item['J']));
                                    }

                                }
                            }
                        }

                        /* Exclude data without email or userid */
                        if(empty($value['ZZMAIL']) || empty($value['ZZUSERID']) || empty($value['ZZHIRE_DATE']) || empty($value['ZZDEP_OM']) || empty($value['ZZDEP_OM_TXT']) || empty($value['NACHN']) || empty($value['VORNA']) || empty($value['ZZGESCH_TXT']) || empty($value['ZZPERID']) || empty($value['ZZORGLV']) || empty($value['PLANS']) || empty($value['ZZPLANS_TXT']) || empty($value['ZZCHIEF_DEP']) || empty($value['PERSONID_EXT'])) {
                            unset($soi_data_array[$row]);
                        }

                        if((in_array($value['PERSONID_EXT'], $EXPATS) && !in_array($value['PERSONID_EXT'], $approvers)) || $value['PERSONID_EXT'] === 'F15073841' || $value['PERSONID_EXT'] === 'F15021867') {
                            // $value['UNSET'] = "True";
                            unset($soi_data_array[$row]);
                        }
                        
                    }

					$sheet->fromArray($soi_data_array);
                });

                $excel->sheet('Users with Empty Values', function($sheet) use($soi_data_array, $EXPATS, $approvers)
                {
                    foreach($soi_data_array as $row => &$value) {

                        if((in_array($value['PERSONID_EXT'], $EXPATS) && !in_array($value['PERSONID_EXT'], $approvers)) || $value['PERSONID_EXT'] === 'F15073841' || $value['PERSONID_EXT'] === 'F15021867') {
                            unset($soi_data_array[$row]);
                        }

                        if((in_array($value['PERSONID_EXT'], $EXPATS) && $value['PERSG'] === '9') || $value['BUKRS'] === 'G698' || !empty($value['ZZTERM_DATE'])) {
                            unset($soi_data_array[$row]);
                        }

                        if(!empty($value['ZZMAIL']) && !empty($value['ZZUSERID']) && !empty($value['ZZHIRE_DATE']) && !empty($value['ZZDEP_OM']) && !empty($value['ZZDEP_OM_TXT']) && !empty($value['NACHN']) && !empty($value['VORNA']) && !empty($value['ZZGESCH_TXT']) && !empty($value['ZZPERID']) && !empty($value['ZZORGLV']) && !empty($value['PLANS']) && !empty($value['ZZPLANS_TXT']) && !empty($value['ZZHRMAN_PERSID']) && !empty($value['ZZHRREP_PERSID']) && !empty($value['ZZCHIEF_DEP']) && !empty($value['PERSONID_EXT'])) {
                            unset($soi_data_array[$row]);
                        }

                    }

                    $sheet->fromArray($soi_data_array);
                });

                $excel->sheet('Expats not in Approval', function($sheet) use($soi_data_array, $EXPATS, $approvers)
                {
                    foreach($soi_data_array as $row => &$value) {

                        if((in_array($value['PERSONID_EXT'], $EXPATS) && $value['PERSG'] === '9') || $value['BUKRS'] === 'G698' || !empty($value['ZZTERM_DATE'])) {
                            unset($soi_data_array[$row]);
                        }

                        if(!(in_array($value['PERSONID_EXT'], $EXPATS) && !in_array($value['PERSONID_EXT'], $approvers))) {
                            unset($soi_data_array[$row]);
                        }
                    }

                    $sheet->fromArray($soi_data_array);
                });
			})->store('xlsx', storage_path('/E-leave'));

            require("SFTPConnection.php");

            Excel::create('SOI_data', function($excel) use($soi_data_array)
            {
                $excel->sheet('E-Leave', function($sheet) use($soi_data_array)
                {
                    $sheet->fromArray($soi_data_array);
                });
            })->export('xlsx');
		}
        elseif(Input::get('type') === 'array')
        {
            print_r($soi_data_array);
        }
        elseif(Input::get('type') === 'peoplefinder')
        {
            $peoplefinder_data_array = array_map(function($line)
            {
                return (array) $line;
            },
            $peoplefinder_data);

            Excel::create('PeopleFinder_data', function($excel) use($peoplefinder_data_array)
            {
                $excel->sheet('PeopleFinder', function($sheet) use($peoplefinder_data_array)
                {
                    $sheet->fromArray($peoplefinder_data_array);
                });
            })->export('xlsx');
        }
        elseif(Input::get('type') === 'datasync')
        {
            $EXPATS = array();
            foreach($peoplefinder_data as $user) {

                if($user->PERSG === '8' && !in_array($user->PERSONID_EXT, $EXPATS)) {
                    array_push($EXPATS, $user->PERSONID_EXT);
                }
            }

            DB::connection('apaconnect')->delete("DELETE FROM wp_usermeta
                                                  WHERE meta_key = ? || meta_key = ? || meta_key = ? || meta_key = ? || meta_key = ? || meta_key = ? || meta_key = ? || meta_key = ? || meta_key = ? || meta_key = ? || meta_key = ?",
                                                  ['first_name', 'last_name', 'nickname', 'company_name', 'department', 'working_site_country', 'cost_center', 'manager_id', 'chief_id', 'title', 'gender']);

            foreach($peoplefinder_data as $user) {

                if(empty($user->ZZMAIL) || $user->STAT2 === '0' || (in_array($user->PERSONID_EXT, $EXPATS) && $user->PERSG === '9')) {

                    continue;

                } else {
                    
                    //DB::connection('apaconnect')->update("UPDATE wp_users SET display_name = ? WHERE user_email = ?", [$user->VORNA . ' ' . $user->NACHN, $user->ZZMAIL]);

                    DB::connection('apaconnect')->insert("INSERT IGNORE INTO wp_users (user_login, user_nicename, user_email, display_name, employee_id)
                                                          VALUES (?, ?, ?, ?, ?)",
                                                          [$user->ZZMAIL, $user->VORNA . ' ' . $user->NACHN, $user->ZZMAIL, $user->VORNA . ' ' . $user->NACHN, $user->PERSONID_EXT]);

                    DB::connection('apaconnect')->insert("INSERT IGNORE INTO wp_usermeta (user_id, meta_key, meta_value)
                                                          SELECT wp_users.ID , ?, ? FROM wp_users WHERE user_email = ?",
                                                          ['first_name', $user->VORNA, $user->ZZMAIL]);

                    DB::connection('apaconnect')->insert("INSERT IGNORE INTO wp_usermeta (user_id, meta_key, meta_value)
                                                          SELECT wp_users.ID , ?, ? FROM wp_users WHERE user_email = ?",
                                                          ['last_name', $user->NACHN, $user->ZZMAIL]);

                    DB::connection('apaconnect')->insert("INSERT IGNORE INTO wp_usermeta (user_id, meta_key, meta_value)
                                                          SELECT wp_users.ID , ?, ? FROM wp_users WHERE user_email = ?",
                                                          ['nickname', $user->VORNA . ' ' . $user->NACHN, $user->ZZMAIL]);

                    DB::connection('apaconnect')->insert("INSERT IGNORE INTO wp_usermeta (user_id, meta_key, meta_value)
                                                          SELECT wp_users.ID , ?, ? FROM wp_users WHERE user_email = ?",
                                                          ['company_name', $user->BUTXT, $user->ZZMAIL]);

                    DB::connection('apaconnect')->insert("INSERT IGNORE INTO wp_usermeta (user_id, meta_key, meta_value)
                                                          SELECT wp_users.ID , ?, ? FROM wp_users WHERE user_email = ?",
                                                          ['department', $user->ZZDEP_OM_TXT, $user->ZZMAIL]);

                    DB::connection('apaconnect')->insert("INSERT IGNORE INTO wp_usermeta (user_id, meta_key, meta_value)
                                                          SELECT wp_users.ID , ?, ? FROM wp_users WHERE user_email = ?",
                                                          ['working_site_country', $user->ZZWERKS_TXT, $user->ZZMAIL]);

                    DB::connection('apaconnect')->insert("INSERT IGNORE INTO wp_usermeta (user_id, meta_key, meta_value)
                                                          SELECT wp_users.ID , ?, ? FROM wp_users WHERE user_email = ?",
                                                          ['cost_center', $user->KOSTL, $user->ZZMAIL]);

                    DB::connection('apaconnect')->insert("INSERT IGNORE INTO wp_usermeta (user_id, meta_key, meta_value)
                                                          SELECT wp_users.ID , ?, ? FROM wp_users WHERE user_email = ?",
                                                          ['manager_id', $user->ZZHRMAN_PERSID, $user->ZZMAIL]);

                    DB::connection('apaconnect')->insert("INSERT IGNORE INTO wp_usermeta (user_id, meta_key, meta_value)
                                                          SELECT wp_users.ID , ?, ? FROM wp_users WHERE user_email = ?",
                                                          ['chief_id', $user->ZZCHIEF_DEP, $user->ZZMAIL]);

                    DB::connection('apaconnect')->insert("INSERT IGNORE INTO wp_usermeta (user_id, meta_key, meta_value)
                                                          SELECT wp_users.ID , ?, ? FROM wp_users WHERE user_email = ?",
                                                          ['title', $user->ZZPLANS_TXT, $user->ZZMAIL]);

                    DB::connection('apaconnect')->insert("INSERT IGNORE INTO wp_usermeta (user_id, meta_key, meta_value)
                                                          SELECT wp_users.ID , ?, ? FROM wp_users WHERE user_email = ?",
                                                          ['gender', $user->ZZGESCH_TXT, $user->ZZMAIL]);

                }
            }
        }
	}
}
