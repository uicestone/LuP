<?php

class TestController extends BaseController {
	
	function index()
	{
		$soi_data = DB::connection('soi')->table('V_ELEAVE_SOI_BASIC_DATA')->get();

		if(Input::get('type') === 'json')
		{
			foreach($soi_data as $soi_row)
			{
				echo json_encode($soi_row) . "," . "\n";
			}
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
