<?php

class Wbs extends Eloquent {
	protected $table = 'wbs';
	protected $fillable = array('code', 'project_name', 'project_representatives', 'project_costcenter', 'closed_or_not');
}