<?php

class Employee extends Eloquent {
	
	protected $fillable = array('Company code','Company','ID Number','SAP HR ID','Employee Name','Last Name','First Name','User Name','E-Mail Address','Local Grade','Cost Center','Direct Manager ID','Direct Manager','Employee Group');
	
}