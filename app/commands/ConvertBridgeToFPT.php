<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ConvertBridgeToFPT extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'convert:bridge-fpt';

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
		
		foreach($list as $file)
		{
			if(!preg_match('/.txt$/i', $file)){
				continue;
			}
			ob_start();
			ftp_pasv($conn, true);
			ftp_get($conn, "php://output", $file, FTP_BINARY) || exit('error getting file through ftp.');
			$file_content = ob_get_contents();
			ob_end_clean();			
			
//			$file_content = file_get_contents($file);
			
			$data = preg_split('/[\r\n]+/', $file_content);
			
			array_walk($data, function(&$line){
				$line = preg_split('/\|+/', $line);
			});
			
			$stored_file = Excel::create(preg_replace('/^.*\/|\.txt$/i', '', $file), function($excel) use($data){
				$excel->sheet('Sheet1', function($sheet) use($data){
					$sheet->fromArray($data, null, 'A1', false, false);
				});
			})->store('xlsx', false, true);
			
			echo $stored_file['file'] . ' converted' . "\n";
			
//			ftp_pasv($conn, true);
//			ftp_put($conn, $config['path'] . '/' . $stored_file['file'], $stored_file['full'], FTP_BINARY) || exit('error putting file through ftp.');
			
//			unlink($stored_file['full']);
			
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
