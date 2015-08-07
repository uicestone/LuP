<?php

class TestController extends BaseController {
	
	function index()
	{
		$soi_data = DB::connection('soi')->table('V_ELEAVE_SOI_BASIC_DATA')->get();
		
		foreach($soi_data as $soi_row)
		{
			print_r($soi_row);
		}
	}
}
