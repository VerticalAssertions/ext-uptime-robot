<?php
// Copyright 1999-2017. Plesk International GmbH.

/**
 * Class Modules_UptimeRobot_API
 *
 * Helper class for the Uptime Robot API
 */
class Modules_UptimeRobot_API
{
    /**
     * Fetches the account data for the transmitted API key
     *
     * @param string $apikey
     *
     * @return mixed|stdClass
     */
    public static function fetchUptimeRobotAccount($apikey)
    {
        return self::doApiCallCurl($apikey, 'https://api.uptimerobot.com/v2/getAccountDetails');
    }

    /**
     * Fetches the account stats for the transmitted API key
     *
     * @param string $apikey
     *
     * @return mixed|stdClass
     */
    public static function fetchUptimeRobotAccountStat($apikey)
    {
        $response = self::doApiCallCurl($apikey, 'https://api.uptimerobot.com/v2/getAccountDetails');

        if (!empty($response->stat)) {
            return $response->stat;
        }

        return new stdClass();
    }

    /**
     * Fetches the account information for the transmitted API key
     *
     * @param string $apikey
     *
     * @return mixed|stdClass
     */
    public static function fetchUptimeRobotAccountDetails($apikey)
    {
        $response = self::doApiCallCurl($apikey, 'https://api.uptimerobot.com/v2/getAccountDetails');

        if (!empty($response->account)) {
            return $response->account;
        }

        return new stdClass();
    }

    /**
     * Fetches alert contacts
     *
     * @param string $apikey
     *
     * @return mixed|stdClass
     */
    public static function fetchAlertContacts($apikey)
    {
        return self::doApiCallCurl($apikey, 'https://api.uptimerobot.com/v2/getAlertContacts');
    }

    /**
     * Fetches all monitors with logs for the transmitted API key
     *
     * @param string $apikey
     * @param array  $monitors
     * @param bool   $id_index use UR monitor_id as index in output array
     *
     * @return array of monitors
     */
    public static function fetchUptimeMonitors($apikey, $monitors = array(), $id_index = FALSE)
    {
        // UR API pagines getMonitors method with 50 results max
        $nb_monitors = NULL;
        $offset = 0;
        $responses = array();
        while(is_null($nb_monitors) || $offset < $nb_monitors) {
            $response = self::doApiCallCurl($apikey, 'https://api.uptimerobot.com/v2/getMonitors', !empty($monitors) ? array('monitors' => implode('-', $monitors), 'logs' => 1, 'offset' => $offset) : array('logs' => 1, 'offset' => $offset));

            $nb_monitors = is_null($nb_monitors) && !empty($response->pagination->total) ? $response->pagination->total : $nb_monitors;
            $offset += !empty($response->pagination->limit) ? $response->pagination->limit : 0;

            if (!empty($response->monitors)) {
                $responses = array_merge($responses, $response->monitors);
            }
        }

        if($id_index) {
            $responses_id_indexed = array();
            foreach($responses as $monitor) {
                $responses_id_indexed[$monitor->id] = $monitor;
            }
            $responses = $responses_id_indexed;
        }

        return $responses;
    }

    /**
     * Create monitor for the transmitted url and API key
     *
     * @param string $apikey
     * @param string $url
     * @param array  $options optional parameters
     *
     * @return mixed|stdClass
     */
    public static function createUptimeMonitor($apikey, $domain, $options)
    {
        $params = array(
            'url' => 'http'.($options['ssl'] ? 's' : '').'://'.$domain,
            'type' => 1,
            'friendly_name' => !empty($options['friendly_name']) ? $options['friendly_name'] : $domain,
            );

        $response = self::doApiCallCurl($apikey, 'https://api.uptimerobot.com/v2/newMonitor', $params);

        if (!empty($response)) {
            return $response;
        }

        return FALSE;
    }

    /**
     * Edit monitor for the transmitted url and API key
     *
     * @param string $apikey
     * @param string $url
     * @param array  $options optional parameters
     *
     * @return mixed|stdClass
     */
    public static function editUptimeMonitor($apikey, $domain, $id, $options)
    {
        $params = array(
            'id' => $id,
            'url' => 'http'.($options['ssl'] ? 's' : '').'://'.($options['www'] ? 'www.' : '').$domain,
            'type' => 1,
            'friendly_name' => !empty($options['friendly_name']) ? $options['friendly_name'] : $domain,
            );

        $response = self::doApiCallCurl($apikey, 'https://api.uptimerobot.com/v2/editMonitor', $params);

        if (!empty($response)) {
            return $response;
        }

        return FALSE;
    }

    /**
     * Delete monitor for the transmitted monitor ID and API key
     *
     * @param string $apikey
     * @param string $id
     *
     * @return mixed|stdClass
     */
    public static function deleteUptimeMonitor($apikey, $id)
    {
        $response = self::doApiCallCurl($apikey, 'https://api.uptimerobot.com/v2/deleteMonitor', array('id' => $id));

        if (!empty($response)) {
            return $response;
        }

        return FALSE;
    }

    /**
     * Helper function for the cURL request to the Uptime Robot API
     *
     * @param string $apikey
     * @param string $command
     * @param array  $post_fields_extra
     *
     * @return mixed|stdClass
     */
    private static function doApiCallCurl($apikey, $command, $post_fields_extra = array())
    {
        $post_fields = array(
            'api_key' => $apikey,
            'format'  => 'json'
        );

        if (!empty($post_fields_extra)) {
            $post_fields = array_merge($post_fields, $post_fields_extra);
        }

        $curl = curl_init();
        curl_setopt_array(
            $curl, array(
            CURLOPT_URL            => $command,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => http_build_query($post_fields),
            CURLOPT_HTTPHEADER     => array(
                'cache-control: no-cache',
                'content-type: application/x-www-form-urlencoded'
            )
        ));

        if (pm_ProductInfo::isWindows()) {
            $caPath = __DIR__ . '/externals/cacert.pem';
            $caPath = str_replace('/', DIRECTORY_SEPARATOR, $caPath);

            curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
            curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,1);
            curl_setopt($curl,CURLOPT_CAINFO,$caPath);
            curl_setopt($curl,CURLOPT_CAPATH,$caPath);
        }

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            $result = new stdClass();
            $result->errorMsg = $err;
            return $result;
        }

        return json_decode($response);
    }
}
