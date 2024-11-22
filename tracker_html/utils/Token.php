<?php

include_once "Handle.php";
include_once "Tools.php";

class Token
{

    public static function createSession()
    {
        $profile = Handle::create('sess', 'SRST', time());
        $token   = str_replace('sess', 's' . Tools::visitCode(time()), $profile);

        return $token;
    }

    public static function createVisitor()
    {
        $profile = Handle::create('vist', 'VLST', time());
        $token   = str_replace('vist', 'l' . Tools::visitCode(time()), $profile);

        return $token;
    }

    public static function createReturn($profile, $id)
    {
        $token = str_replace('prof', 'p' . Tools::visitCode(time()));
        $token .= $id ? '--' . $id : '';

        return $token;
    }
}
