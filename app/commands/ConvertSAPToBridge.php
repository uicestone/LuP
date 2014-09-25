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
		ftp_pasv($conn, true);
		$list = ftp_nlist($conn, $config['path']);
//		$list = array('./SAE_ACCOUNT_CG_20140912.txt');
		
		foreach($list as $path)
		{
			if(!preg_match('/Payment/', $path) || (File::extension($path) !== 'xls' && File::extension($path) !== 'xlsx')){
				continue;
			}
			
			ftp_pasv($conn, true);
			ftp_get($conn, storage_path() . '/input.' . File::extension($path), $path, FTP_BINARY) || exit('error getting file through ftp.');
			
			Excel::load(storage_path() . '/input.' . File::extension($path), function($reader) use($conn, $path)
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
						$item[9]->format('Ymd'),
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
				}

				file_put_contents(storage_path() . '/output.txt', $output);

				ftp_pasv($conn, true);
				ftp_put($conn, preg_replace('/.xlsx*$/', '.txt', $path), storage_path() . '/output.txt', FTP_BINARY) || exit('error putting file through ftp.');
				
				File::delete(array(storage_path() . '/output.txt', storage_path() . '/input.' . File::extension($path)));
				
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
