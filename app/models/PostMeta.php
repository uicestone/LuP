<?php

class PostMeta extends Eloquent {
	
	protected $connection = 'apaconnect';
	protected $table = 'postmeta';
	protected $fillable = array('meta_key', 'meta_value');

}
