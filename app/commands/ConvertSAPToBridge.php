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
	 * The FTP connection to use, will be lazy loaded and only for once.
	 * @var Resource
	 */
	protected $ftp_connection;

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
		
		$list = File::files(storage_path('imports'));
		
		foreach($list as $path)
		{
			$this->info($path);
			
			try{
				
				if(strtolower(File::extension($path)) !== 'xls' && strtolower(File::extension($path)) !== 'xlsx'){
					continue;
				}

				Excel::selectSheetsByIndex(0)->load($path, function($reader) use($config, $path)
				{
					$result = $reader->noHeading()->toArray();

					$data = array();

					foreach($result as $sheet_line){
						
						if(strtolower(trim($sheet_line[12])) !== 'success' || !$sheet_line[4]){
							continue;
						}
						$data[] = $sheet_line;
					}

					$output = '100,FL,ID' . "\n";

					foreach($data as $index => $item){
						
						$line_data = array(
							600,
							$item[6] > 0 ? - round($item[6] * 100) : round($item[6] * 100), // Amount
							method_exists($item[9], 'format') ? $item[9]->format('Ymd') : date('Ymd',strtotime(str_replace('.', '/', $item[9]))),
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

					}
					
					preg_match('/\d{8}/', $path, $matches);
					
					if(!$matches){
						throw new Exception('date not found in ' . $path);
					}
					
					$export_file_name = 'PYMT_CONFIRM_' . $matches[0] . '.txt';

					file_put_contents(storage_path('exports') . '/' . $export_file_name, $output);

					$this->comment(date('Y-m-d H:i:s') . ' ' . $export_file_name . ' converted, uploading to FTP server...');

					if(empty($this->ftp_connection)){
						$this->comment(Date('Y-m-d H:i:s') . ' Logging FTP...');
						$this->ftp_connection = ftp_connect($config['host'], 21);
						ftp_pasv($this->ftp_connection, true);
						ftp_login($this->ftp_connection, $config['username'], $config['password']);
						$this->info(Date('Y-m-d H:i:s') . ' Logged into FTP server.');
					}

					for($i = 0; $i < 20 && !@ftp_pasv($this->ftp_connection, true); $i ++){
						$this->error(Date('Y-m-d H:i:s') . ' Failed to switch to passive mode. ' . ($i === 19 ? 'Aborting.' : 'Trying again...'));
					}

					for($i = 0; $i < 20 && !@ftp_put($this->ftp_connection, $config['path'] . '/' . $export_file_name, storage_path('exports') . '/' . $export_file_name, FTP_BINARY); $i ++){
						$this->error(Date('Y-m-d H:i:s') . ' Failed to write to FTP server. ' . ($i === 19 ? 'Aborting.' : 'Trying again...'));
					}

					File::delete($path);
					File::delete(storage_path('exports') . '/' . $export_file_name);

					$this->info(date('Y-m-d H:i:s') . ' ' . $export_file_name . ' uploaded to FTP server.');

				});
			
			} catch (Exception $ex) {
				$this->error($ex->getMessage());
			}
		}
		
		$this->info(date('Y-m-d H:i:s') . ' Completed. (in ' . round(microtime(true) - $start, 3) . 's)');
		
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
