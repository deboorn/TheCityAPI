<?php 

	/**
	 * @author Daniel Boorn - daniel.boorn@gmail.com
	 * @copyright Daniel Boorn
	 * @license Creative Commons Attribution-NonCommercial 3.0 Unported (CC BY-NC 3.0)
	 */

	require_once('vendor/com.rapiddigitalllc/thecity/admin.php');

	/**
	 * Endpoint Examples using CHAINABLE NAMING CONVENTION
	 * 06/11/2013 - All endpoints are avaiable. See api_paths.json (if needed)
	 */
	
	
	$tc = \TheCity\Admin::forge();
	
	# get users
	//$users = $tc->users()->index()->get();
	//var_dump($users);

	# get users (same as above with use of chainable forge method)
	//$users = \TheCity\Admin::forge()->users()->index()->get();
	//var_dump($users);
	
	
	# search users
	/*	
	$users = $tc->users()->index(array(
		'filter' => 'created_in_the_last_7_Days',
		'include_participation' => true,
		'include_custom_fields' => true,
	))->get();
	var_dump($users);
	*/

	# show user
	//$user = $tc->users(1397567)->show()->get();
	//var_dump($user);
	
	# create user from array
	/*
	$user = $tc->users()->create( array(
		'first' => 'Jane',
		'last' => 'Doe',
		'gender' => 'Female',
		'email' => 'random15@example.com',
		'staff' => false,
		//...
	))->get();
	var_dump($user);
	*/
		
	# create user from array
	/*
	$user = $tc->users()->create( (object) array(
		'first' => 'Misty',
		'last' => 'Doe',
		'gender' => 'Female',
		'email' => 'random156@example.com',
		'staff' => false,
		//...
	))->get();
	var_dump($user);
	*/
	
	# error handling example (e.g. for duplicate email)
	/*
	try{
		$user = $tc->users()->create( array(
			'first' => 'Misty',
			'last' => 'Doe',
			'gender' => 'Female',
			'email' => 'random156@example.com',
			'staff' => false,
		))->get();
	}catch(\TheCity\Exception $e){
		var_dump($e->getMessage(),$e->getCode(),$e->getResponse());
	}
	*/	
	
	# get user's addresses
	//$addresses = $tc->users($userId)->addresses()->index()->get();
	
	# get specific address for user
	//$address = $tc->users($userId)->addresses($addressId)->show()->get();
	
	# get user process
	//$process = $tc->users($userId)->processes($processId)->show()->get();
	
	# users proces answers
	//$answers = $tc->users($userId)->processes($processId)->answers()->index()->get();
	
	

?>