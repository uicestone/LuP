<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ConvertSAPToBridge extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'convert:sap-bridge';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = '';

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
		$start = microtime(true);
		echo 'Convertion started at ' . Date('Y-m-d H:i:s', $start) . '.' . "\n";
		
		$config = array(
			'host'=>$this->option('host'),
			'username'=>$this->option('login'),
			'password'=>$this->option('pass'),
			'path'=>$this->option('path')
		);
		
		$conn = ftp_connect($config['host']);

		ftp_pasv($conn, true);
		ftp_login($conn, $config['username'], $config['password']);
		
		$list = File::files(storage_path('imports'));
		
		foreach($list as $path)
		{
			if(!preg_match('/payment/i', $path) || (File::extension($path) !== 'xls' && File::extension($path) !== 'xlsx')){
				continue;
			}
			
			Excel::load($path, function($reader) use($conn, $config, $path)
			{
				$result = $reader->noHeading()->toArray();

				$data = array();

				foreach($result[0] as $sheet_line){

					if(strtolower(trim($sheet_line[11])) !== 'success'){
						continue;
					}
					$data[] = $sheet_line;
				}

				$output = '100,FL,ID' . "\n";

				foreach($data as $item){
					$line_data = array(
						600,
						- $item[6] * 100, // Amount
						method_exists($item[9], 'format') ? $item[9]->format('Ymd') : date('Ymd',strtotime($item[9])),
						null,
						null,
						$item[7], // Document No.
						$item[4], // Report ID
						$item[5], // Currency
						null,
						null,
						null,
						null,
						null,
					);
					$output .= implode(',', $line_data) . "\n";
					
					$last_item_date = method_exists($item[9], 'format') ? $item[9]->format('Ymd') : date('Ymd',strtotime($item[9]));
					
				}

				$export_file_name = '/PYMT_CONFIRM_' . $last_item_date . '.txt';
				
				file_put_contents(storage_path('exports') . '/' . $export_file_name, $output);

				ftp_pasv($conn, true);
				ftp_put($conn, $config['path'] . $export_file_name, storage_path('exports') . '/' . $export_file_name, FTP_BINARY) || exit('error putting file through ftp.');
				
				File::delete(array(storage_path() . '/' . $export_file_name, storage_path() . '/*'));
				
				echo $path . ' converted' . "\n";
			});
			
		}
		
		echo 'Completed. Convertion took ' . (microtime(true) - $start) . ' seconds.' . "\n";
		
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
			array('host', 's', InputOption::VALUE_REQUIRED, 'ftp server host'),
			array('login', 'u', InputOption::VALUE_REQUIRED, 'username of ftp account'),
			array('pass', 'p', InputOption::VALUE_REQUIRED, 'password of ftp account'),
			array('path', 'd', InputOption::VALUE_REQUIRED, 'path to source txt file'),
		);
	}

}
