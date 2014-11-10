<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class LoadWbs extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'load:wbs';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Load WBS - Cost Center mapping from Excel file.';

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
		$reader = Excel::selectSheets($this->option('sheet-name'))->load($this->argument('path'));
		
		$data = $reader->toArray();
		
		if(!$data){
			throw new Exception('Cannot read data from ' . $this->argument('path') . '.');
		}
		
		DB::table('wbs')->truncate();
		foreach($reader->toArray() as $row){
			$row['code'] = $row['wbs'];
			Wbs::create($row);
		}
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('path', InputArgument::REQUIRED, 'The WBS Excel file path.')
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
			array('sheet-name', null, InputOption::VALUE_REQUIRED, 'WBS sheet name in the Excel book.')
		);
	}

}
