<?php

class Wbs extends Eloquent {
	
	protected $table = 'wbs';
	protected $fillable = array('code', 'project_name', 'project_representatives', 'project_costcenter', 'closed_or_not');
	
	public static function getByKeyword($keyword)
	{
		$wbs_keywords = array($keyword);
		
		$matches = array();
		preg_match_all('/[\d\w]{7}/', $keyword, $matches);
		
		foreach($matches[0] as $match)
		{
			$wbs_keywords[] = $match;
		}
		
		foreach($wbs_keywords as $keyword)
		{
			$wbs = Wbs::where('code', $keyword)->where('closed_or_not', 'Open')->first();

			if($wbs){
				break;
			}

		}
		
		if(!empty($wbs))
		{
			return $wbs;
		}

	}
	
}