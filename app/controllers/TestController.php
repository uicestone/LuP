<?php

class TestController extends BaseController {
	
	function index()
	{
		File::delete(storage_path('imports') . '/SAE_ACCOUNT_JV_20141023.TXT');
	}
}
