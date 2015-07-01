<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class LoadMm extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'load:mm';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Load SAP MM dump data.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$this->comment('converting SAP dump data into arrays...'); $timepoint = microtime(true);
		$purchase_order_items = $this->sapDumptoArray(storage_path('imports/PR PO.txt'), 'Purch.Doc.', 6);
		$goods_receipt_items = $this->sapDumptoArray(storage_path('imports/GR.txt'), 'Mat. Doc.', 3);
		$this->info('completed in ' . round(microtime(true) - $timepoint, 3));
		
//		print_r($purchase_order_items[0]); print_r($goods_receipt_items[0]);exit;
//		$user = User::find(1); print_r($user->meta);exit;
		$purchase_orders = array();
		$goods_receipts = array();
		
//		$purchase_order_numbers = array_column($purchase_order_items, 'Purch.Doc.');
//		echo count(array_unique($purchase_order_numbers)), ' / ', count($purchase_order_numbers) . "\n";
//		var_export(in_array('4500074319', $purchase_order_numbers));
		
		// group PO items into POs
		$this->comment('group PO items into POs...'); $timepoint = microtime(true);
		
//		$purchase_order_items = array_slice($purchase_order_items, 0, 300);
		
		foreach($purchase_order_items as $index => $purchase_order_item)
		{
			$purchase_order_keys = array_keys(array_where($purchase_orders, function($index, $purchase_order) use($purchase_order_item)
			{
				return $purchase_order['post_name'] === $purchase_order_item['Purch.Doc.'];
			}
			));
			
			if(!$purchase_order_keys)
			{
				$purchase_order = array(
					'post_type'=>'purchase_order',
					'post_status'=>'private',
					'post_title'=>$purchase_order_item['Purch.Doc.'],
					'post_name'=>$purchase_order_item['Purch.Doc.'],
					
					'currency'=>$purchase_order_item['Crcy'],
					'amount'=>0,
					'quantity'=>0,
					'contact_person_logon_id'=>$purchase_order_item['Created by'],
				);
				
				$purchase_orders[] = $purchase_order;
				
				$purchase_order_key = count($purchase_orders) - 1;
			}
			else
			{
				$purchase_order_key = array_pop($purchase_order_keys);
			}
			
			$purchase_orders[$purchase_order_key]['amount'] += $purchase_order_item['Net Value'];
			$purchase_orders[$purchase_order_key]['quantity'] += $purchase_order_item['PO Quantity'];
		}
		$this->info('completed in ' . round(microtime(true) - $timepoint, 3));
		
		// group GR items into GRs
		$this->comment('group GR items into GRs...'); $timepoint = microtime(true);
		foreach($goods_receipt_items as $index => $goods_receipt_item)
		{
			
			if($goods_receipt_item['HCt'] === 'Q')
			{
				continue;
			}
			
			$goods_receipt_keys = array_keys(array_where($goods_receipts, function($index, $goods_receipt) use($goods_receipt_item)
			{
				return $goods_receipt['post_name'] === $goods_receipt_item['Mat. Doc.'];
			}
			));
			
			$related_purchase_order_items = array_where($purchase_order_items, function($index, $purchase_order_item) use($goods_receipt_item)
			{
				return $purchase_order_item['Purch.Doc.'] === $goods_receipt_item['Purch.Doc.'] && (int)$purchase_order_item['Item'] === (int)$goods_receipt_item['Item'];
			});
			
			if(count($related_purchase_order_items) !== 1)
			{
				// no PO info found for this GR
				continue;
			}
			
			$related_purchase_order_item = array_pop($related_purchase_order_items);
			
			if(!$goods_receipt_keys)
			{
				$goods_receipt = array(
					'post_type'=>'goods_receipt',
					'post_status'=>'private',
					'post_title'=>$goods_receipt_item['Mat. Doc.'],
					'post_name'=>$goods_receipt_item['Mat. Doc.'],
					
					'purchase_order_number'=>$goods_receipt_item['Purch.Doc.'],
					'amount'=>0
				);
				
				$goods_receipts[] = $goods_receipt;
				
				$goods_receipt_key = count($goods_receipts) - 1;
			}
			else
			{
				$goods_receipt_key = array_pop($goods_receipt_keys);
			}
			
			$goods_receipt_amount = $related_purchase_order_item['Net Price'] / $related_purchase_order_item['Per'] * $goods_receipt_item['Quantity'];
			
			if($goods_receipt_item['MvT'] === '102')
			{
				$goods_receipt_amount = -$goods_receipt_amount;
			}
			
			$goods_receipts[$goods_receipt_key]['amount'] += round($goods_receipt_amount, 2);
			
		}
		$this->info('completed in ' . round(microtime(true) - $timepoint, 3));
		
		$this->comment('getting existing POs and GRs...'); $timepoint = microtime(true);
		$existing_purchase_order_names = Post::where('post_type', 'purchase_order')->where('post_status', 'private')->get()->map(function($item)
		{
			return $item->post_name;
		}
		)->toArray();
		
		$existing_good_receipt_names = Post::where('post_type', 'good_receipt')->where('post_status', 'private')->get()->map(function($item)
		{
			return $item->post_name;
		}
		)->toArray();
		$this->info('completed in ' . round(microtime(true) - $timepoint, 3));
		
		$this->comment('saving POs...'); $timepoint = microtime(true);
		foreach($purchase_orders as $index => $purchase_order)
		{
			if(!in_array($purchase_order['post_name'], $existing_purchase_order_names))
			{
				$post = Post::create($purchase_order);
				
				$post->meta()->saveMany(array(
					new PostMeta(array('meta_key'=>'currency', 'meta_value'=>$purchase_order['currency'])),
					new PostMeta(array('meta_key'=>'amount', 'meta_value'=>$purchase_order['amount'])),
					new PostMeta(array('meta_key'=>'quantity', 'meta_value'=>$purchase_order['quantity'])),
				));
				
				$contact_person = User::whereHas('meta', function($query) use($purchase_order)
				{
					$query->where('meta_key', '=', 'logon_id')->where('meta_value', '=', $purchase_order['contact_person_logon_id']);
				}
				)->first();
				
				if(!$contact_person)
				{
					continue;
				}
				
				$meta_cost_center = $contact_person->meta()->where('meta_key', 'cost_center')->first();

				if(!$meta_cost_center)
				{
					continue;
				}
				
				$cost_center_name = $meta_cost_center->meta_value;

				$cost_center = Post::where('post_type', 'cost_center')->where('post_name', $cost_center_name)->first();

				if(!$cost_center)
				{
					$this->error('cost center not found: ' . $cost_center_name);
					continue;
				}
				
				$post->meta()->save(new PostMeta(array('meta_key'=>'contact_person_id', 'meta_value'=>$contact_person->ID)));
				$post->meta()->save(new PostMeta(array('meta_key'=>'cost_center_id', 'meta_value'=>$cost_center->ID)));
				
			}
			else
			{
				$post = Post::where('post_type', 'purchase_order')->where('post_status', 'private')->where('post_name', $purchase_order['post_name'])->first();
				$post->meta()->where('meta_key', 'amount')->update(array('meta_value'=>$purchase_order['amount']));
				$post->meta()->where('meta_key', 'quantity')->update(array('meta_value'=>$purchase_order['quantity']));
			}
			
			if($index % 100 === 99)
			{
				$this->info(($index + 1) . ' Completed');
			}
		}
		$this->info('completed in ' . round(microtime(true) - $timepoint, 3));
		
		$this->comment('saving GRs...'); $timepoint = microtime(true);
		foreach($goods_receipts as $index => $goods_receipt)
		{
			if(!in_array($goods_receipt['post_name'], $existing_good_receipt_names))
			{
				$post = Post::create($goods_receipt);
				$related_purchase_order = Post::where('post_type', 'purchase_order')->where('post_status', 'private')->where('post_name', $goods_receipt['purchase_order_number'])->first();
				if(!$related_purchase_order)
				{
					$this->error('Related purchase order not found for GR No.' . $goods_receipt['post_name'] . ', PO No.' . $goods_receipt['purchase_order_number']);
					continue;
				}
				$post->meta()->saveMany(array(
					new PostMeta(array('meta_key'=>'purchase_order_id', 'meta_value'=>$related_purchase_order->ID)),
					new PostMeta(array('meta_key'=>'amount', 'meta_value'=>$goods_receipt['amount'])),
				));
			}
			else
			{
				$post = Post::where('post_type', 'goods_receipt')->where('post_status', 'private')->where('post_name', $goods_receipt['post_name'])->first();
				$post->meta()->where('meta_key', 'amount')->update(array('meta_value'=>$goods_receipt['amount']));
			}
			
			if($index % 100 === 99)
			{
				$this->info(($index + 1) . ' Completed');
			}
		}
		
		$this->info('completed in ' . round(microtime(true) - $timepoint, 3));
		
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			
		);
	}

	protected function sapDumptoArray($file, $key, $heading_line)
	{
		$raw = array_map(function($line_raw)
		{
			return array_map('trim', preg_split('/\t/', $line_raw));
		}
		, explode("\n", str_replace("\0", '', file_get_contents($file))));
		
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
				
				if(preg_match('/value|price|quantity/i', $headings[$column_index]))
				{
					$cell = str_replace(',', '.', str_replace('.', '', $cell));
				}
				
				$row[$headings[$column_index]] = $cell;
			}
			
			$data[] = $row;
		}
		
		$data = array_filter($data, function($row) use($key)
		{
			return isset($row[$key]) && $row[$key];
		});
		
		return $data;
	}
	
}
