<?php
/**
 * Created by PhpStorm.
 * User: seb
 * Date: 26/03/15
 * Time: 17:40
 */
function menuclass($script)
{
    $bn=basename($_SERVER['SCRIPT_FILENAME']);
    $bn=str_replace('.php', '', $bn);
    return ($bn==$script)?"active":"inactive";
}
