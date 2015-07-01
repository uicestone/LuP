<?php

class Post extends Eloquent {
	
	protected $connection = 'apaconnect';
	protected $fillable = array('post_author', 'post_content', 'post_date', 'post_date_gmt', 'post_modified', 'post_modified_gmt', 'post_title', 'post_name', 'post_excerpt', 'post_status', 'post_type');
	
	public $timestamps = false;

	public function meta()
	{
		return $this->hasMany('PostMeta', 'post_id');
	}
	
	public static function boot()
	{
		parent::boot();
		
		Post::creating(function($post)
		{
			$post->post_date = date('Y-m-d H:i:s');
			$post->post_date_gmt = gmdate('Y-m-d H:i:s');
			$post->post_modified = date('Y-m-d H:i:s');
			$post->post_modified_gmt = gmdate('Y-m-d H:i:s');
			return $post;
		});
		
		Post::updating(function($post)
		{
			$post->post_modified = date('Y-m-d H:i:s');
			$post->post_modified_gmt = gmdate('Y-m-d H:i:s');
			return $post;
		});
	}
	
}