<?php

require('dbhandler.php');

$CONFIG = array(
		'next_search_revisit_time' => 0,
		'next_search_revisit_offset' => 3000,
		'sleep_time' => 30,
		'sites'	=> array(
					'https://www.immobilienscout24.de/Suche/S-T/Wohnung-Miete/Fahrzeitsuche/M_fcnchen/-/113055/2029726/-/1276002059/60/2,00-/-/EURO--800,00?enteredFrom=one_step_search'
					),
		'sites_expose_url_prefix' => 'https://www.immobilienscout24.de/',
		'regexp' => array(
					'search' => '/data-go-to-expose-id=\"(\d+)\"/',
					'exposes' => array(
						'price_cold' => '/<div class=\"is24qa-kaltmiete is24-value font-semibold\">\s(.+)\s€\s<\/div>/'
						),
					),
	);
$DATABASE_HANDLER = new DBHandler();

/*
collected, rooms, size, online) values(:id, :name, :price_warm,
									:price_cold, :first_seen, :last_seen, :care, :pets, :zipcode,
									:city, :dist_work, :kausion, :url, :collected, :rooms, :size, :online)');

*/
		$expose = array(
		'id' => '123',
		'name' => 'asd',
		'price_warm' => 12,
		'price_cold' => 21,
		'first_seen' => 123123,
		'last_seen' => 14134,
		'care' => 'N',
		'pets' => 'Y',
		'zipcode' => 'asdasd',
		'city' => 'dddd',
		'dist_work' => 222,
		'kausion' => 333,
		'url' => 'adassdasadsdadsasdaasdasd',
		'collected' => 'Y',
		'rooms' => 4,
		'size' => 3323,
		'online' => 'Y',
		'next_check' => 4444
	);
	//$DATABASE_HANDLER->Store_Expose($expose);



while(true)
{
	/***
		# Check if it's time for revisiting the searches. (Unix time stamp, config how often)
			- Visit the search, and store all exposes.
		# Get all the exposes that needs to be looked at, (decided by a timing mekanism)
			- Check all the given urls, update the exposes and store them (one at the time). Set a new next_check value (config value)
		# Check if any exposes are a match with the given preferences (config)
			- If there are, mail them.
	*/

	if (time() > $CONFIG['next_search_revisit_time'])
	{
		revisit_searches();
		$CONFIG['next_search_revisit_time'] = time() + $CONFIG['next_search_revisit_offset'];
	}

	collect_expose_information($DATABASE_HANDLER->Get_Exposes_For_Collection());

	check_for_valid_exposes();

	echo "Sleeping... \n";
	sleep($CONFIG['sleep_time']);	
}


function revisit_searches()
{
	global $CONFIG;
	global $DATABASE_HANDLER;

	foreach ($CONFIG['sites'] as $site)
	{
		$data = file_get_contents($site);
		preg_match_all($CONFIG['regexp']['search'], $data, $output);
		if (!isset($output[1]))
		{
			echo "No exposes found!\n";
			return;
		}
		$output = $output[1];
		$expose_ids = array_unique($output);
		foreach ($expose_ids as $expose_id)
		{
			$expose = $DATABASE_HANDLER->Get_Expose_By_Id($expose_id);
			if ($expose === false)
			{
				$expose = _get_default_expose();
				$expose['id'] = $expose_id;
				$expose['first_seen'] = time();
				$expose['url'] = $CONFIG['sites_expose_url_prefix'] . $expose_id;
				$DATABASE_HANDLER->Store_Expose($expose);
			}
		}
	}
}


function collect_expose_information($exposes)
{
	global $CONFIG;

	foreach ($exposes as $expose)
	{
		$data = file_get_contents($expose['url']);
		foreach ($CONFIG['regexp']['exposes'] as $data_key => $regexp)
		{
			preg_match_all($regexp, $data, $output);
			if (isset($output[1][0]))
			{
				$expose[$data_key] = $output[1][0];
			}
		}
		var_dump($expose);
		die;
	}
}


function check_for_valid_exposes()
{
	# STUB!
}



function _get_default_expose()
{
	return 	array(
		'id' => '',
		'name' => '',
		'price_warm' => 0,
		'price_cold' => 0,
		'first_seen' => 0,
		'last_seen' => 0,
		'care' => '',
		'pets' => '',
		'zipcode' => '',
		'city' => '',
		'dist_work' => 0,
		'kausion' => 0,
		'url' => '',
		'collected' => '',
		'rooms' => 0,
		'size' => 0,
		'online' => '',
		'next_check' => 0
	);
}
?>