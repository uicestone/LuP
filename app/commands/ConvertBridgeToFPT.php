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
		$this->info(Date('Y-m-d H:i:s', $start) . ' Start to convert.');

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
			
			for($i = 0; $i < 20 && !@ftp_pasv($this->ftp_connection, true); $i ++){
				$this->error(date('Y-m-d H:i:s') . ' ' . 'Failed to switch to passive mode. ' . ($i === 19 ? 'Aborting.' : 'Trying again...'));
			}

			for($i = 0; $i < 20 && !@ftp_get($this->ftp_connection, storage_path('imports') . '/' . $filename , $path, FTP_BINARY); $i ++){
				$this->error(date('Y-m-d H:i:s') . ' ' . 'Failed to read from FTP server. ' . ($i === 19 ? 'Aborting.' : 'Trying again...'));
			}
			
			$this->comment(date('Y-m-d H:i:s') . ' ' . $filename . ' downloaded.');
			
			$file_content = File::get(storage_path('imports') . '/' . $filename);
			
			$data = Convert::concurBridgeToFPT($file_content);
			
			array_walk($data, function(&$line)
			{
				!empty($line['WBS']) && $line['WBS'] = trim($line['WBS']);
				
				if($this->option('map-wbs') && !empty($line['WBS']))
				{
					$wbs = Wbs::getByKeyword($line['WBS']);
					
					if($wbs)
					{
						$line['Cost Center'] = $wbs->project_costcenter;
						$line['WBS'] = $wbs->code . '_TRAV_COST';
					}
					else
					{
						$line['WBS'] .= ' [Cost Center not found for this WBS]';
					}
				}
			});
			
			$stored_file = Excel::create(preg_replace('/^.*\/|\.txt$/i', '', $path) . '-T2', function($excel) use($data){
				$excel->sheet('Sheet1', function($sheet) use($data){
					$sheet->fromArray($data);
				});
			})->store('xlsx', false, true);
			
			$this->info(date('Y-m-d H:i:s') . ' ' . $stored_file['file'] . ' converted');
			
			File::delete(storage_path('imports') . '/' . $filename);
			
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
			array('map-wbs', 'w', InputOption::VALUE_NONE, 'whether to map Cost Center to WBS Cost Center'),
		);
	}

}
