<?php

class UserMeta extends Eloquent {
	
	protected $connection = 'apaconnect';
	protected $table = 'usermeta';
	protected $fillable = array('meta_key', 'meta_value');
	public $timestamps = false;
	
}
