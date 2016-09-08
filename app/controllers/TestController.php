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

class TestController extends BaseController {

	function index()
	{
		$soi_data = DB::connection('soi')->table('V_ELEAVE_SOI_BASIC_DATA')->get();
        $peoplefinder_data = DB::connection('people_finder')->table('V_PEOPLE_FINDER_SOI_BASIC_DATA')->get();

        $soi_data_array = array_map(function($line)
        {
            return (array) $line;
        },
        $soi_data);

        $soi_data_array_dealed = array_dealed($soi_data_array);

		if(Input::get('type') === 'json')
		{
			$soi_data_json = json_encode(utf8ize($soi_data_array_dealed));
			echo $soi_data_json;
		}
		elseif(Input::get('type') === 'excel')
		{
			Excel::create($sftp_file = 'E-Leave-' . date("Y-m-d"), function($excel) use($soi_data_array_dealed)
			{
				$excel->sheet('E-Leave', function($sheet) use($soi_data_array_dealed)
				{
                    $input_file = 'CNUsers.xlsx';

                    if (!file_exists($input_file)) {
        	            exit("No File!");
                    }

                    $obj_PHPExcel = PHPExcel_IOFactory::load($input_file);

                    $sheet_data = $obj_PHPExcel->getActiveSheet()->toArray(null, true, true, true);

                    foreach ($soi_data_array_dealed as $row => &$value) {

                        if ($value['ZZLEGACY_ID'] === '782141') {
                            $value['ZZMAIL'] = 'RICARDO.RODRIGUEZ@FCAGROUP.COM';
                        }

                        if ($value['BUKRS'] == 'G698' || /*empty($value['ZZMAIL']) ||*/ (!empty($value['ZZTERM_DATE']) && $value['ZZTERM_DATE'] < date("2016-09-01"))) {
                            unset($soi_data_array_dealed[$row]);
                        } else {
                            foreach ($sheet_data as $item) {

                                if ($value['ZZUSERID'] === strtoupper(trim($item['D']))) {
                                    $value['ZZMAIL'] = strtoupper(trim($item['B']));
                                }
                            }
                        }
                    }

					$sheet->fromArray($soi_data_array_dealed);
                    $sheet->cell('AT1', 'ZZHRMAN_LEGACY_ID'); // Add field name to AT1 cell
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
            print_r($soi_data_array_dealed);
        }
        elseif(Input::get('type') === 'peoplefinder')
        {
            $peoplefinder_data_array = array_map(function($line)
            {
                return (array) $line;
            },
            $peoplefinder_data);

            //$peoplefinder_data_json = json_encode(utf8ize($peoplefinder_data_array));
            //echo $peoplefinder_data_json;

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
            
            DB::connection('apaconnect')->delete("DELETE FROM wp_usermeta
                                                  WHERE meta_key = ? || meta_key = ? || meta_key = ? || meta_key = ? || meta_key = ? || meta_key = ? || meta_key = ? || meta_key = ? || meta_key = ? || meta_key = ? || meta_key = ?",
                                                  ['first_name', 'last_name', 'nickname', 'company_name', 'department', 'working_site_country', 'cost_center', 'manager_id', 'chief_id', 'title', 'gender']);

            foreach($peoplefinder_data as $user) {

                if(empty($user->ZZMAIL)) {

                    continue;

                } else {

                    //DB::connection('apaconnect')->update("UPDATE wp_users SET employee_id = ? WHERE user_email = ?", [$user->PERSONID_EXT, $user->ZZMAIL]);

                    DB::connection('apaconnect')->insert("INSERT IGNORE INTO wp_users (user_login, user_nicename, user_email, display_name, employee_id)
                                                          VALUES (?, ?, ?, ?, ?)",
                                                          [$user->ZZMAIL, $user->VORNA . ' ' . $user->NACHN, $user->ZZMAIL, $user->VORNA, $user->PERSONID_EXT]);

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
