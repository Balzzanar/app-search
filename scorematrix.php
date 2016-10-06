<?php 
$scorematrix = array(
                'pets' => array(
                    'eq' => array(
                        'N' => -50,
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
                        300 => 5,
                        250 => 10,
                        200 => 15,
                        150 => 20,
                        100 => 25
                    ),
                    'gt' => array()
                )
			);
?>