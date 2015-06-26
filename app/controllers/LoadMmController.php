<?php

class LoadMmController extends BaseController {
	
	function index()
	{
		
		$heading_line = 6;
		
		$raw = array_map(function($line_raw)
		{
			return array_map('trim', preg_split('/[\t]+/', $line_raw));
		}
		, explode("\n", file_get_contents(storage_path('imports/PR_PO.xlsx'))));
		
		$headings = $raw[$heading_line];
		
		$data = array();
		
		foreach($raw as $row_index => $raw_row)
		{
			if($row_index <= $heading_line || count($raw_row) !== count($headings))
			{
				continue;
			}
			
			$row = array();
			
			foreach($raw_row as $column_index => $cell)
			{
				if(!$headings[$column_index] || isset($row[$headings[$column_index]])){
					continue;
				}
				
				$row[$headings[$column_index]] = $cell;
			}
			
			$data[] = $row;
		}
		
	}
	
}
