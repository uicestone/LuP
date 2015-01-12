<?php

class Convert {
	
	protected static function getVendorName($item)
	{
		$vendor_code = null;
		foreach($item['body'] as $record){
			if($record[3]){
				$vendor_code = $record[3];
				break;
			}
		}
		$mapping = DB::table('vendor_mapping')->where('vendor_code_new', $vendor_code)->first();
		return $mapping ? $mapping->chinese_name : 'N/A';
	}

	protected static function getOldVendor($new_vendor)
	{
		$mapping = DB::table('vendor_mapping')->where('vendor_code_new', $new_vendor)->first();
		return $mapping ? $mapping->vendor_code_old : $new_vendor;
	}
	
	protected static function getReference($item){
		foreach($item['body'] as $record){
			if($record[12]){
				return $record['12'];
			}
		}
	}

	public static function concurBridgeToCGCSL($input_text)
	{
		$lines = preg_split('/\n/', $input_text);
		
		array_walk($lines, function(&$line)
		{
			$line = explode('|', $line);
		});
		
		$items = array();
		
		foreach($lines as $line)
		{
			if($line[0] === 'H'){
				$items[$line[1]] = array('head'=>$line);
			}
			elseif($line[0] === 'D'){
				$items[$line[1]]['body'][] = $line;
			}
		}
		
		$output = array();
		
		$trasaction_no = 1;
		$document_line_item_no = 1;
		
		foreach($items as $item)
		{
			foreach($item['body'] as $index => $record)
			{
				$output[] = array(
					'Transaction NO.' => $trasaction_no, // Transaction number
					'Doc.Header text' => $record[6], // Report ID
					'Company code' => $item['head'][2], // Company code
					'Document Date' => date('d.m.Y', strtotime($item['head'][3])), // Document Date
					'Posting Date' => date('d.m.Y', strtotime($item['head'][4])), // Posting Date
					'Year' => date('Y', strtotime($item['head'][4])),
					'Period' => date('m', strtotime($item['head'][4])),
					'Document Type' => 'KR',
					'Reference' => self::getReference($item), // Partner bank type
					'Document Line item no.' => $document_line_item_no,
					'G/L Account' => $record[4], // G/L Account,
					'Customer' => null,
					'Vendor' => $record[3] ? self::getOldVendor($record[3]) : null, // Vendor
					'Sp.GL Indicator' => trim($record[13]), // Sepcial G/L Indicator
					'Text' => $record[3] ? $item['head'][6] : ($record[9] . '/' . self::getVendorName($item)), // Comments
					'Assignment ' => $record[11], // EPS
					'Tax code' => $record[7], // Tax code
					'Cost Center' => $record[10], // Cost Center
					'Profir Center' => null,
					'Base line date' => date('d.m.Y', strtotime($item['head'][4])), // Same as posting date
					'Currency' => 'RMB', // Currency
					'Amount' => in_array($record[2], array(31, 39, 50)) ?  '-' . $record[5] : $record[5]  // Amount
				);
				
				$document_line_item_no ++;
				
			}
			
			$trasaction_no ++;
			
		}
		
		return $output;
		
	}
	
	public static function concurBridgeToFPT($input_text)
	{
		$lines = preg_split('/\n/', $input_text);
		
		array_walk($lines, function(&$line)
		{
			$line = explode('|', $line);
		});
		
		$items = array();
		
		foreach($lines as $line)
		{
			if($line[0] === 'H'){
				$items[$line[1]] = array('head'=>$line);
			}
			elseif($line[0] === 'D'){
				$items[$line[1]]['body'][] = $line;
			}
		}
		
		$output = array();
		
		$trasaction_no = 1;
		$document_line_item_no = 1;
		
		foreach($items as $item)
		{
			foreach($item['body'] as $index => $record)
			{
				$output[] = array(
					'Transaction NO.' => $record[1], // Transaction number
					'Company code' => $item['head'][2], // Company code
					'Document Type' => 'KR',
					'Document Date' => date('Ymd', strtotime($item['head'][3])), // Document Date
					'Posting Date' => date('Ymd', strtotime($item['head'][4])), // Posting Date
					'Currency' => $item['head'][5],
					'Reference' => $item['head'][6],
					'Doc.Header text' => $item['head'][7], // Header Text
					'Posting Key' => $record[2], // Posting Key
					'Vendor' => $record[3], // Vendor
					'G/L Account' => $record[4], // G/L Account,
					'Amount' => $record[5],  // Amount
					'reference key 3' => $record[6],
					'Tax Code' => $record[7], // Tax code
					'Assignment ' => $record[12], // EPS
					'Text' => $record[9], // Comments
					'Cost Center' => $record[10], // Cost Center
					'WBS' => $record[11],
					'Partner bank type' => null,
					'Sepcial G/L Indicator' => null,
//					'Year' => date('Y', strtotime($item['head'][4])),
//					'Period' => date('m', strtotime($item['head'][4])),
//					'Reference' => self::getReference($item), // Partner bank type
//					'Document Line item no.' => $document_line_item_no,
//					'Customer' => null,
//					'Sp.GL Indicator' => trim($record[13]), // Sepcial G/L Indicator
//					'Profir Center' => null,
//					'Base line date' => date('d.m.Y', strtotime($item['head'][4])), // Same as posting date
				);
				
				$document_line_item_no ++;
				
			}
			
			$trasaction_no ++;
			
		}
		
		return $output;
		
	}
	
}
