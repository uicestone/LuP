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
			Excel::create('SOI Data', function($excel) use($soi_data_array_dealed)
			{
				$excel->sheet('SOI Data', function($sheet) use($soi_data_array_dealed)
				{
					$sheet->fromArray($soi_data_array_dealed);
                    $sheet->cell('AT1', 'ZZHRMAN_LEGACY_ID'); // Add field name to AT1 cell
                });
			})->export('xlsx');
		}
        elseif(Input::get('type') === 'array')
        {
            print_r($soi_data_array_dealed);
        }
	}
}
