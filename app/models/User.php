<?php

class User extends Eloquent {
	
	protected $connection = 'apaconnect';
	protected $fillable = array('user_login', 'user_nickname', 'user_email', 'user_status', 'display_name', 'employee_id');
	
	public $timestamps = false;

	public function meta()
	{
		return $this->hasMany('UserMeta', 'user_id', 'ID');
	}
}