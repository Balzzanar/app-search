<?php

function _special__pets($data, $regexp, &$expose)
{
    $result = DBHandler::TABLE_EXPOSES_MAYBE;
    preg_match_all($regexp, $data, $output);
    if (isset($output[1][0]))
    {
        if ($output[1][0] == 'Nein')
        {
            $result = DBHandler::TABLE_EXPOSES_FALSE;
        }
        if ($output[1][0] == 'Ja')
        {
            $result = DBHandler::TABLE_EXPOSES_TRUE;
        }
    }
    $expose['pets'] = $result;
}

function _special__kausion($data, $regexp, &$expose)
{
    $result = 0;
    preg_match_all($regexp, $data, $output);
    if (isset($output[0][0]))
    {
        $output = $output[0][0];
        $data = explode('</dd>', $output);
        $data = explode('data-ng-non-bindable>', $data[0]);
        $result = (int)trim($data[1]);
    }
    $expose['kausion'] = $result;
}


function _special__adress($data, $regexp, &$expose)
{
    $street = '';
    $city = '';
    preg_match_all($regexp, $data, $output);
    if (isset($output[0][0]))
    {
        $output = $output[0][0];

        $street = explode('(', $output)[0];
        $street = trim(explode('">', $street)[1]);
        $street = str_replace(',', '', $street);
        $city = explode(')', $output)[1];
        $city = explode('</span>', $city)[1];
        $city = trim(explode('</div>', $city)[0]);
    }
    $expose['city'] = $city;
    $expose['street'] = $street;
}

?>