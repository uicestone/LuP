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

class TestController extends BaseController {

	function index()
	{
		$soi_data = DB::connection('soi')->table('V_ELEAVE_SOI_BASIC_DATA')->get();

		if(Input::get('type') === 'json')
		{
			$soi_data_array = array_map(function($line)
			{
				return (array) $line;
			},
			$soi_data);

			$soi_data_json = json_encode(utf8ize($soi_data_array));
			echo $soi_data_json;
		}
		elseif(Input::get('type') === 'excel')
		{

			$soi_data_array = array_map(function($line)
			{
				return (array) $line;
			},
			$soi_data);

			Excel::create('SOI Data', function($excel) use($soi_data_array)
			{
				$excel->sheet('SOI Data', function($sheet) use($soi_data_array)
				{
					$sheet->fromArray($soi_data_array);
				});
			}
			)->export('xlsx');
		}
	}
}
