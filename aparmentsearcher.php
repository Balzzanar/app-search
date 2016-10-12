#!/usr/bin/php
<?php

require('dbhandler.php');
require('specials.php');
require('scorematrix.php');

$CONFIG = array(
		'next_search_revisit_offset' => 3000,
		'next_expose_recheck_offset' => 10800,
		'sleep_time' => 30,
		'sites'	=> array(
					'https://www.immobilienscout24.de/Suche/S-T/P-1/Wohnung-Miete/Fahrzeitsuche/M_fcnchen/-/113055/2029726/-/1276002059/60/2,00-/-/EURO--800,00?enteredFrom=one_step_search'
					),
		'sites_expose_url_prefix' => 'https://www.immobilienscout24.de/',
		'regexp' => array(
					'search' => '/data-go-to-expose-id=\"(\d+)\"/',
					'exposes' => array(
						'integers' => array(
							'price_cold' => '/<div class=\"is24qa-kaltmiete is24-value font-semibold\">\s(.+)\s€\s<\/div>/',
							'price_warm' => '/<dd class=\"is24qa-nebenkosten grid-item three-fifths\"> <span class=\"is24-operator\">.+<\/span>\s(.+)\s€\s<\/dd>/U',
							'rooms' => '/<div class=\"is24qa-zi is24-value font-semibold\">\s(.+)\s<\/div>/U',
							'size' => '/<div class=\"is24qa-flaeche is24-value font-semibold\">\s(.+)\sm²\s<\/div>/U'
						),
						'strings' => array(
							'floor' => '/<dd class=\"is24qa-etage grid-item three-fifths\">(.+)<\/dd>/U',
							'access' => '/<dd class=\"is24qa-bezugsfrei-ab grid-item three-fifths\">(.+)<\/dd>/U'
						),
						'special' => array(
							'pets' => '/<dd class=\"is24qa-haustiere grid-item three-fifths\">\s(.+)\s<\/dd>/U',
							'kausion' => '/<dd class=\"is24qa-kaution-o-genossenschaftsanteile is24-ex-spacelink grid-item three-fifths\".+>.+<\/dd>/',
							'adress' => '/<span class=\"block font-nowrap print-hide\">(.+)<\/div>/U'
						)
					),
		),
		'expose_not_found' => 'Immobilie nicht gefunden',
        'scorematrix' => $scorematrix,
        'mail_score_limit' => 50,
        'mail_time_offset' => 60*60*24 // Only once per day
	);
$DATABASE_HANDLER = new DBHandler();
$ERROR_NO = 0;

//preg_match_all("", $input_lines, $output_array);

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

//calculate_expose_score();
//die;

$next_search_revisit_time = 0;

while(true)
{
	/***
		# Check if it's time for revisiting the searches. (Unix time stamp, config how often)
			x Visit the search, and store all exposes.
		# Get all the exposes that needs to be looked at, (decided by a timing mekanism)
			x Check all the given urls, update the exposes and store them (one at the time). Set a new next_check value (config value)
		# Check if any exposes are a match with the given preferences (config)
			- If there are, mail them.
	*/

	if (time() > $next_search_revisit_time)
	{
		revisit_searches();
		$next_search_revisit_time = time() + $CONFIG['next_search_revisit_offset'];
	}

	collect_expose_information($DATABASE_HANDLER->Get_Exposes_For_Collection());
    calculate_expose_score();
    handle_high_score();

	echo "Sleeping... \n";
	sleep($CONFIG['sleep_time']);	
}

/**
 * Visists the configured search sites, and collects all exposes that it 
 * finds. Will move through all the pages on the search result as well.
 * The resulting exposes will then be stored on the databases, 
 * if they do not already exist.
 *
 * @name revisit_searches
 */
function revisit_searches()
{
	global $CONFIG;
	global $DATABASE_HANDLER;

    /* Generate all the paging sites. */
    $sites = array_map(function($site){
        $res = array();
        for ($i=1; $i<50; $i++)
        {
            $_site = str_replace('P-1', 'P-'.$i, $site);
            $res[] = $_site;
        }
        return $res;
    }, $CONFIG['sites']);

    $found_ids = array();
	foreach ($sites as $pages)
	{
        foreach ($pages as $page)
        {
            printf("Getting ids, for page: %s\n", $page);
            $expose_ids = get_all_expose_ids_from_page($page);
            if (count(array_diff($expose_ids, $found_ids)) < 1) break;
            $found_ids = array_merge($found_ids, $expose_ids);
        }
    }

    foreach ($found_ids as $expose_id)
    {
        $expose = $DATABASE_HANDLER->Get_Expose_By_Id($expose_id);
        if ($expose === false)
        {
            $expose = DBHandler::Get_default_expose();
            $expose['id'] = $expose_id;
            $expose['first_seen'] = time();
            $expose['url'] = $CONFIG['sites_expose_url_prefix'] . $expose_id;
            $DATABASE_HANDLER->Store_Expose($expose);
        }
    }
}

/**
 * Grabs all the exposes that has not been flagged for offline, 
 * and starts to collect information about them. This is done by visiting
 * the given url, and parsing the result with the help of helper functions.
 *
 * @name collect_expose_information
 */
function collect_expose_information($exposes)
{
	global $CONFIG;
	global $DATABASE_HANDLER;
    global $ERROR_NO;

	printf("Infomation collection %d in total\n", count($exposes)-1);
	$itr = 0;
	foreach ($exposes as $expose)
	{
        set_error_handler(function($errno) {
            /* In order to catch 404s */
            global $ERROR_NO;
            $ERROR_NO = $errno;
        });

		$data = file_get_contents($expose['url']);
        restore_error_handler();

		if ($ERROR_NO == 2 || strpos($data, $CONFIG['expose_not_found']) != false)
		{
			$expose['online'] = DBHandler::TABLE_EXPOSES_FALSE;
			$DATABASE_HANDLER->Update_Expose($expose);
			printf("(%d/%d), Expose (%s) was not online, marked as offline\n", $itr, count($exposes)-1, $expose['id']);
		    $ERROR_NO = 0;
			continue;
		}

        $expose['online'] = DBHandler::TABLE_EXPOSES_TRUE;
		printf("(%d/%d), Expose is online, collecting information for expose (%s)\n", $itr, count($exposes)-1, $expose['id']);

		/* Get all integer values */
		foreach ($CONFIG['regexp']['exposes']['integers'] as $data_key => $regexp)
		{
			preg_match_all($regexp, $data, $output);
			if (isset($output[1][0]))
			{
				$expose[$data_key] = (int)$output[1][0];
			}
		}

		/* Get all string values */
		foreach ($CONFIG['regexp']['exposes']['strings'] as $data_key => $regexp)
		{
			preg_match_all($regexp, $data, $output);
			if (isset($output[1][0]))
			{
				$expose[$data_key] = trim($output[1][0]);
			}
		}

		/* Get all special values */
		foreach ($CONFIG['regexp']['exposes']['special'] as $data_key => $regexp)
		{
			$func = '_special__'.$data_key;
			$func($data, $regexp, $expose);
		}


		$expose['next_check'] = time() + $CONFIG['next_expose_recheck_offset'];
		$DATABASE_HANDLER->Update_Expose($expose);
        $itr++;
        $ERROR_NO = 0;
	}
}

/**
 * Based on all the previously collected information, a score
 * is calculated and stored on the expose. The score is based on a set of
 * threshholds, defined in scorematrix.php.
 *
 * @name calculate_expose_score
 */
function calculate_expose_score()
{
    global $DATABASE_HANDLER;
    global $CONFIG;

    $exposes = $DATABASE_HANDLER->Get_Exposes_For_Scorecalc();
    $scorearray = $CONFIG['scorematrix'];

    foreach ($exposes as $expose_key => $expose_value)
    {
        $score = 0;
        foreach ($exposes[$expose_key] as $key => $value)
        {
            if (array_key_exists($key, $scorearray))
            {
                $score += get_score($value, $scorearray[$key]);
            }
        }
        $exposes[$expose_key]['score'] = $score;
		$DATABASE_HANDLER->Update_Expose($expose);
    }
}

/**
 * Uses the score calculated in the previous step to determend 
 * if there are any exposes that score high, if so. That expose 
 * is then mailed.
 *
 * @name handle_high_score
 */
function handle_high_score()
{
    global $DATABASE_HANDLER;
    global $CONFIG;

    $exposes = $DATABASE_HANDLER->Get_Exposes_By_Score($CONFIG['mail_score_limit']);

}

function get_all_expose_ids_from_page($page)
{
    global $CONFIG;

    $data = file_get_contents($page);
    preg_match_all($CONFIG['regexp']['search'], $data, $output);
    if (!isset($output[1]))
    {
        printf("No exposes found for page: %s\n", $page);
        return;
    }
    $output = $output[1];
    $expose_ids = array_unique($output);
    return $expose_ids;
}


function get_score($value, $thresholds)
{
    $resscore = 0;
    $_score = 0;

    foreach ($thresholds['eq'] as $threshold => $score)
    {
        if ($value == $threshold) $_score = $score;
    }
    $resscore += $_score;
    $_score = 0;

    foreach ($thresholds['lt'] as $threshold => $score)
    {
        if ($value < $threshold) $_score = $score;
    }
    $resscore += $_score;
    $_score = 0;

    foreach ($thresholds['gt'] as $threshold => $score)
    {
        if ($value > $threshold) $_score = $score;
    }
    $resscore += $_score;
    return $resscore;
}




?>
