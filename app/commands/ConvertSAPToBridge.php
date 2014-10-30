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
	protected $description = 'Read local stored payment confirmation file, convert and write to Concur inbound.';

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
		$this->comment(Date('Y-m-d H:i:s', $start) . ' Start to convert.');
		
		$config = array(
			'host'=>$this->option('host'),
			'username'=>$this->option('login'),
			'password'=>$this->option('pass'),
			'path'=>$this->option('path')
		);
		
		$conn = null;
		
		$list = File::files(storage_path('imports'));
		
		foreach($list as $path)
		{
			if(File::extension($path) !== 'xls' && File::extension($path) !== 'xlsx'){
				continue;
			}
			
			Excel::load($path, function($reader) use($config, $path)
			{
				$result = $reader->noHeading()->toArray();

				$data = array();

				foreach($result[0] as $sheet_line){

					if(strtolower(trim($sheet_line[12])) !== 'success' || !$sheet_line[4]){
						continue;
					}
					$data[] = $sheet_line;
				}

				$output = '100,FL,ID' . "\n";

				foreach($data as $item){
					$line_data = array(
						600,
						$item[6] > 0 ? - round($item[6] * 100) : round($item[6] * 100), // Amount
						method_exists($item[10], 'format') ? $item[10]->format('Ymd') : date('Ymd',strtotime($item[10])),
						$item[8] === 'D' ? null : ($item[8] === 'E' ? 'ICBC-corporate' : 'ICBC'),
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
					
					$last_item_date = method_exists($item[10], 'format') ? $item[10]->format('Ymd') : date('Ymd',strtotime($item[10]));
					
				}

				$export_file_name = 'PYMT_CONFIRM_' . $last_item_date . '.txt';
				
				file_put_contents(storage_path('exports') . '/' . $export_file_name, $output);
				
				$this->comment(date('Y-m-d H:i:s') . ' ' . $export_file_name . ' converted, uploading to FTP server...');
				
				global $conn;
				
				if(empty($conn)){
					$this->comment(Date('Y-m-d H:i:s') . ' Logging FTP...');
					$conn = ftp_connect($config['host']);
					ftp_pasv($conn, true);
					ftp_login($conn, $config['username'], $config['password']);
					$this->info(Date('Y-m-d H:i:s') . ' Logged into FTP server.');
				}
				
				while(!@ftp_pasv($conn, true)){
					$this->error('Failed to switch to passive mode. Trying again...');
				}
				
				while(!@ftp_put($conn, $config['path'] . '/' . $export_file_name, storage_path('exports') . '/' . $export_file_name, FTP_BINARY)){
					$this->error('Failed to write to FTP server. Trying again...');
				}
				
				File::delete($path);
				File::delete(storage_path('exports') . '/' . $export_file_name);
				
				$this->info(date('Y-m-d H:i:s') . ' ' . $export_file_name . ' uploaded to FTP server.');
				
			});
			
		}
		
		$this->info(date('Y-m-d H:i:s') . ' completed (in ' . round(microtime(true) - $start, 3) . 's)');
		
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
