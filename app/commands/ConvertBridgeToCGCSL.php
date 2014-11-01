<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ConvertBridgeToCGCSL extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'convert:bridge-cg';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Read and convert Concur outbound file, store into local folder.';

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
		$this->info(date('Y-m-d H:i:s', $start) . ' Start to convert.');
		
		$config = array(
			'host'=>$this->option('host'),
			'username'=>$this->option('login'),
			'password'=>$this->option('pass'),
			'path'=>$this->option('path')
		);
		
		$this->comment(date('Y-m-d H:i:s') . ' Logging FTP...');
		$this->ftp_connection = ftp_connect($config['host'], 21);
		ftp_pasv($this->ftp_connection, true);
		ftp_login($this->ftp_connection, $config['username'], $config['password']);
		$this->info(date('Y-m-d H:i:s') . ' Logged into FTP server.');
		
		ftp_pasv($this->ftp_connection, true);
		$list = ftp_nlist($this->ftp_connection, $config['path']);
		$this->comment(date('Y-m-d H:i:s') . ' ' . count($list) . ' files on FTP listed.');
		
		foreach($list as $path)
		{
			
			$filename = basename($path);
			
			if(strtolower(File::extension($path)) !== 'txt'){
				continue;
			}
			
			while(!@ftp_pasv($this->ftp_connection, true)){
				$this->error('Failed to switch to passive mode. Trying again...');
			}

			while(!@ftp_get($this->ftp_connection, storage_path('imports') . '/' . $filename , $path, FTP_BINARY)){
				$this->error('Failed to read from FTP server. Trying again... (' . $path . ')');
			}
			
			$this->comment(date('Y-m-d H:i:s') . ' ' . $filename . ' downloaded.');
			
			$file_content = File::get(storage_path('imports') . '/' . $filename);
			
			$data = Convert::concurBridgeToCGCSL($file_content);
			
			$stored_file = Excel::create(preg_replace('/^.*\/|\.txt$/i', '', $path), function($excel) use($data){
				$excel->sheet('Sheet1', function($sheet) use($data){
					$sheet->setColumnFormat(array(
						'C'=>'@'
					));
					$sheet->fromArray($data);
				});
			})->store('xlsx', false, true);
			
			$this->info(date('Y-m-d H:i:s') . ' ' . $stored_file['file'] . ' converted');
			
			File::delete(storage_path('imports') . '/' . $filename);
			
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
