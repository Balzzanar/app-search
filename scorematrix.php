<?php 
$scorematrix = array(
                'pets' => array(
                    'eq' => array(
                        'N' => -100,
                        'Y' => 50
                    ),
                    'lt' => array(),
                    'gt' => array()
                ),
                'price_cold' => array(
                    'eq' => array(
                        0 => -100
                    ),
                    'lt' => array(
                        1000 => 10,
                        800 => 15,
                        700 => 20,
                        600 => 25
                    ),
                    'gt' => array()
                ),
                'price_warm' => array(
                    'eq' => array(
                        0 => -100
                    ),
                    'lt' => array(
                        300 => 0,
                        250 => 5,
                        200 => 10,
                        150 => 20,
                        100 => 25
                    ),
                    'gt' => array()
                ),
                'rooms' => array(
                    'eq' => array(
                        '1' => -100,
                        '2' => 10,
                        '3' => 15,
                        '4' => 20,
                        '5' => 25
                    ),
                    'lt' => array(),
                    'gt' => array()
                )
			);
#$scorematrix = array(
#			);
?>
