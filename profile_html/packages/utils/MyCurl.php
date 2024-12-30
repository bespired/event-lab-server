<?php

class MyCurl
{

    public static function assets($type)
    {
        include_once "Dot.php";

        $env  = Dot::handle();
        $host = $env->apacheCurl;
        if ($host === 'serverIp') {
            $host = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : $host;
        }

        $curl = curl_init();

        $url = "https://$host/assets/index.php?$type";

        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => 3,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'GET',

            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
        ]);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;

    }
}
