<?php
// Copyright 1999-2017. Plesk International GmbH.

/**
 * Class IndexController
 */
class IndexController extends pm_Controller_Action
{
    protected $_accessLevel = ['admin'];
    private $api_key;
    const DEFAULT_TIMESPAN = 30;
    const UR_DELAY = 120;

    /**
     * Initialize controller
     */
    public function init()
    {
        parent::init();

        $this->view->headLink()->appendStylesheet(pm_Context::getBaseUrl().'/css/local.css');
        $this->view->headLink()->appendStylesheet(pm_Context::getBaseUrl().'/bootstrap/css/bootstrap.min.css');
        $this->view->headLink()->appendStylesheet(pm_Context::getBaseUrl().'/css/circle.css');
        $this->view->headLink()->appendStylesheet(pm_Context::getBaseUrl().'/font-awesome/css/font-awesome.min.css');

        // Use require.js to solve conflict between prototype (loaded by Plesk) and jquery (used by this extension)
        // Based on http://www.softec.lu/site/DevelopersCorner/BootstrapPrototypeConflict and http://jsfiddle.net/dgervalle/e8Apv/
        $this->view->headScript()->appendFile(pm_Context::getBaseUrl().'/js/require.min.js');
        $this->view->headScript()->appendFile(pm_Context::getBaseUrl().'/js/jquery.global.js'); // Loads jQuery and Bootstrap JS

        $this->api_key = pm_Settings::get('apikey', '');
        $this->timezone = pm_Settings::get('timezone', '');

        $this->view->pageTitle = 'Uptime Robot';
        $this->view->tabs = [
            [
                'title'  => pm_Locale::lmsg('overviewTitle'),
                'action' => 'overview',
            ],
            [
                'title'  => pm_Locale::lmsg('settingsTitle'),
                'action' => 'settings',
            ],
            [
                'title'  => pm_Locale::lmsg('synchronizeTitle'),
                'action' => 'synchronize',
            ]
        ];

        // Database features
        $this->_requestMapper = new Modules_UptimeRobot_Model_RequestMapper();

        if(isset($this->_requestMapper->_status) && is_array($this->_requestMapper->_status)) {
            $this->_status->addMessage(key($this->_requestMapper->_status), pm_Locale::lmsg(reset($this->_requestMapper->_status)[0], reset($this->_requestMapper->_status)[1]));
        }

        // Local mapping table between domains and monitors
        $this->mapping_table = $this->_requestMapper->getMappingTable();
    }
    protected $_requestMapper = null;

    /**
     * Index Action
     */
    public function indexAction()
    {
        if ($this->api_key) {
            $account = Modules_UptimeRobot_API::fetchUptimeRobotAccount($this->api_key);

            if ($account->stat == 'ok') {
                $this->_forward('overview');

                return;
            }
        }

        $this->_forward('setup');
    }

    /**
     * Setup Action
     */
    public function setupAction()
    {
        $this->view->apikeyForm = new pm_Form_Simple();
        $this->view->apikeyForm->addElement(
            'text', 'apikey', [
            'label'    => pm_Locale::lmsg('setupApiKeyInputLabel'),
            'required' => true,
            'value'    => $this->api_key
        ]);
        $tzlist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
        $this->view->apikeyForm->addElement(
            'select', 'timezone', [
            'label'    => pm_Locale::lmsg('setupTimezoneInputLabel'),
            'required' => true,
            'value'    => !empty($this->timezone) ? $this->timezone : date_default_timezone_get(),
            'multiOptions' => array_combine($tzlist, $tzlist),
        ]);
        $this->view->apikeyForm->addControlButtons(
            [
                'cancelHidden' => true,
                'sendTitle'    => pm_Locale::lmsg('setupApiKeySaveButton')
            ]);

        if ($this->getRequest()->isPost() && $this->view->apikeyForm->isValid($this->getRequest()->getPost())) {
            $api_key = $this->view->apikeyForm->getValue('apikey');
            pm_Settings::set('apikey', trim($api_key));
            $timezone = $this->view->apikeyForm->getValue('timezone');
            pm_Settings::set('timezone', trim($timezone));

            if ($api_key) {
                $account = Modules_UptimeRobot_API::fetchUptimeRobotAccount($api_key);
                if (isset($account->stat) && $account->stat == 'ok') {
                    $this->_status->addMessage('info', pm_Locale::lmsg('setupApiKeySaved'));
                } else {
                    $error = isset($account->errorMsg) ? $account->errorMsg : json_encode($account);
                    $this->_status->addError(pm_Locale::lmsg('setupApiKeyInvalid', [
                        'error' => $error
                    ]));
                }
            }

            $this->_helper->json(
                [
                    'redirect' => pm_Context::getBaseUrl()
                ]);
        }
    }

    /**
     * Settings Action
     */
    public function settingsAction()
    {
        $this->view->settingsForm = new pm_Form_Simple();
        $this->view->settingsForm->addElement(
            'text', 'apikey', [
            'label' => 'API-Key',
            'value' => $this->api_key
        ]);
        $tzlist = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
        $this->view->settingsForm->addElement(
            'select', 'timezone', [
            'label'    => pm_Locale::lmsg('setupTimezoneInputLabel'),
            'required' => true,
            'value'    => !empty($this->timezone) ? $this->timezone : date_default_timezone_get(),
            'multiOptions' => array_combine($tzlist, $tzlist),
        ]);
        $this->view->settingsForm->addControlButtons(
            [
                'cancelHidden' => true,
                'sendTitle'    => 'Save'
            ]);

        if ($this->getRequest()->isPost() && $this->view->settingsForm->isValid($this->getRequest()->getPost())) {
            $api_key = $this->view->settingsForm->getValue('apikey');
            pm_Settings::set('apikey', trim($api_key));
            $timezone = $this->view->settingsForm->getValue('timezone');
            pm_Settings::set('timezone', trim($timezone));

            if ($api_key) {
                $account = Modules_UptimeRobot_API::fetchUptimeRobotAccount($api_key);

                if ($account->stat == 'ok') {
                    $this->_status->addMessage('info', pm_Locale::lmsg('setupApiKeySaved'));
                } else {
                    $this->_status->addError(pm_Locale::lmsg('setupApiKeyInvalid'));
                }
            }

            $this->_helper->json(array('redirect' => pm_Context::getBaseUrl()));

            return;
        }

        $account = Modules_UptimeRobot_API::fetchUptimeRobotAccountDetails($this->api_key);
        $this->view->accountForm = new pm_Form_Simple();
        $this->view->accountForm->addElement(
            'text', 'email', [
            'label'    => pm_Locale::lmsg('settingsMail'),
            'value'    => $account->email,
            'readonly' => true
        ]);
        $this->view->accountForm->addElement(
            'text', 'limit', [
            'label'    => pm_Locale::lmsg('settingsMonitorLimit'),
            'value'    => $account->monitor_limit,
            'readonly' => true
        ]);
        $this->view->accountForm->addElement(
            'text', 'interval', [
            'label'    => pm_Locale::lmsg('settingsMonitorInterval'),
            'value'    => $account->monitor_interval,
            'readonly' => true
        ]);
        $this->view->accountForm->addElement(
            'text', 'interval', [
            'label'    => pm_Locale::lmsg('settingsUpMonitor'),
            'value'    => $account->up_monitors,
            'readonly' => true
        ]);
        $this->view->accountForm->addElement(
            'text', 'interval', [
            'label'    => pm_Locale::lmsg('settingsDownMonitor'),
            'value'    => $account->down_monitors,
            'readonly' => true
        ]);
        $this->view->accountForm->addElement(
            'text', 'interval', [
            'label'    => pm_Locale::lmsg('settingsPausedMonitor'),
            'value'    => $account->paused_monitors,
            'readonly' => true
        ]);
    }

    /**
     * Overview Action
     */
    public function overviewAction()
    {
        $timespan = self::DEFAULT_TIMESPAN;

        if ($this->getRequest()->getQuery('timespan')) {
            $timespan = intval($this->getRequest()->getQuery('timespan'));
        }

        $monitors = Modules_UptimeRobot_API::fetchUptimeMonitors($this->api_key);
        $this->view->timespan = $timespan;
        $this->view->globalUptimePercentage = $this->_attachUptimePercentageToMonitors($monitors, $timespan);
        $this->view->monitorsList = new Modules_UptimeRobot_List_Monitors($this->view, $this->_request, $monitors);
        $this->view->eventsList = new Modules_UptimeRobot_List_Events($this->view, $this->_request, $monitors);

        $chartData = $this->_getChartDataFor($monitors, $timespan);
        $this->view->chartData = $chartData['data'];
        $this->view->chartMinRange = max(0, $chartData['minRange'] - 5);
        $this->view->chartMaxRange = $chartData['maxRange'];

        $this->view->monitors = $monitors;
    }

    /**
     * Events List Data Action
     */
    public function eventslistDataAction()
    {
        $monitors = Modules_UptimeRobot_API::fetchUptimeMonitors($this->api_key);
        $list = new Modules_UptimeRobot_List_Events($this->view, $this->_request, $monitors);
        $this->_helper->json($list->fetchData());
    }

    /**
     * Monitors List Data Action
     */
    public function monitorslistDataAction()
    {
        $monitors = Modules_UptimeRobot_API::fetchUptimeMonitors($this->api_key);
        $this->_attachUptimePercentageToMonitors($monitors);
        $list = new Modules_UptimeRobot_List_Monitors($this->view, $this->_request, $monitors);
        $this->_helper->json($list->fetchData());
    }

    /**
     * Synchronize Action
     */
    public function synchronizeAction()
    {
        $list = $this->_getMappingTableList();

        // List object for pm_View_Helper_RenderList
        $this->view->synchronize = $list;
    }

    /**
     * Synchronize List Data Action
     */
    public function synchronizeDataAction()
    {
        $list = $this->_getMappingTableList();

        // Json data from pm_View_List_Simple
        $this->_helper->json($list->fetchData());
    }

    /**
     * Associate a guid to an Uptime Robot monitor ID
     * Then redirects to Synchronize tab
     */
    public function mapAction()
    {
        $guid = $this->getRequest()->getParam('guid');
        $ur_id = $this->getRequest()->getParam('ur_id');

        // Validate url parameters
        if(!empty($guid) && preg_match('#^[a-f0-9-]+$#', $guid)
        && !empty($ur_id) && preg_match('#^[0-9]+$#', $ur_id)) {
            $pm_Domain = pm_Domain::getByGuid($guid);

            // Get UR monitor data
            $monitors = Modules_UptimeRobot_API::fetchUptimeMonitors($this->api_key, array($ur_id));

            $this->mapping_table[$guid] = array(
                'id' => !empty($this->mapping_table[$guid]) ? $this->mapping_table[$guid]->id : NULL,
                'guid' => $guid,
                'ur_id' => $ur_id,
                'url' => $pm_Domain->getName(),
                'create_datetime' => $monitors[0]->create_datetime,
                'delete_datetime' => 0,
                );

            // Update mapping table
            if($db_error = $this->_requestMapper->saveMapping($this->mapping_table[$guid])) {
                $this->_status->addMessage('error', $db_error);
            }
            else {
                $this->_status->addMessage('info', pm_Locale::lmsg('synchronizeMapDone', [ 'domain' => $pm_Domain->getName(), 'ur_id' => $ur_id ]));
            }
        }
        else {
            $this->_status->addMessage('error', pm_Locale::lmsg('synchronizeInvalidRequest'));
        }

        $this->_redirect('index/synchronize');
    }

    /**
     * Dissociate a guid from an Uptime Robot monitor ID
     * Then redirects to Synchronize tab
     */
    public function unmapAction()
    {
        $guid = $this->getRequest()->getParam('guid');

        // Validate url parameters
        if(!empty($guid) && preg_match('#^[a-f0-9-]+$#', $guid)) {
            if(array_key_exists($guid, $this->mapping_table)) {
                $old_mapping = $this->mapping_table[$guid];

                // Update mapping table
                if($db_error = $this->_requestMapper->deleteMapping($this->mapping_table[$guid])) {
                    $this->_status->addMessage('error', $db_error);
                }
                else {
                    $this->_status->addMessage('info', pm_Locale::lmsg('synchronizeUnmapDone', [ 'domain' => $old_mapping->url, 'ur_id' => $old_mapping->ur_id ]));
                    unset($this->mapping_table[$guid]);
                }
            }
            else {
                $this->_status->addMessage('error', pm_Locale::lmsg('synchronizeMappingNotFound', [ 'guid' => $guid ]));
            }
        }
        else {
            $this->_status->addMessage('error', pm_Locale::lmsg('synchronizeInvalidRequest'));
        }

        $this->_redirect('index/synchronize');
    }

    /**
     * Creates a new Uptime Robot monitor and updates local mapping table
     * Then redirects to Synchronize tab
     */
    public function createmonitorAction()
    { 
        $guid = $this->getRequest()->getParam('guid');

        // Validate url parameters
        if(!empty($guid) && preg_match('#^[a-f0-9-]+$#', $guid)) {
            $pm_Domain = pm_Domain::getByGuid($guid);
            
            // Find if domain is served in ssl
            $ssl = array(FALSE);
            $request = '<site><get><filter><guid>'.$guid.'</guid></filter><dataset><hosting/></dataset></get></site>';
            foreach(pm_ApiRpc::getService()->call($request)->site->get->result->data->hosting->vrt_hst->property as $property) {
                if($property->name == 'ssl') {
                    $ssl = $property->value;
                }
            }

            $json = Modules_UptimeRobot_API::createUptimeMonitor($this->api_key, $pm_Domain->getName(), array(
                'friendly_name' => $pm_Domain->getDisplayName(),
                'ssl' => $ssl[0],
                )); // { "stat": "ok", "monitor": { "id": 777810874, "status": 1 }}

            if($json && $json->stat == 'ok' && !empty($json->monitor->id)) {
                $this->mapping_table[$guid] = array(
                    'ur_id' => $json->monitor->id,
                    'url' => $pm_Domain->getName(),
                    'create_datetime' => time(),
                    'delete_datetime' => 0,
                    );

                // Update mapping table
                if($db_error = $this->_requestMapper->saveMapping($this->mapping_table[$guid])) {
                    $this->_status->addMessage('error', $db_error);
                }
                else {
                    $this->_status->addMessage('info', pm_Locale::lmsg('synchronizeCreateMonitorDone', [ 'domain' => $pm_Domain->getName(), 'ur_id' => $json->monitor->id ]));
                }
            }
            elseif($json) {
                $this->_status->addMessage('info', pm_Locale::lmsg('synchronizeCreateMonitorNOK', [ 'domain' => $pm_Domain->getName(), 'json' => json_encode($json) ]));
            }
            else {
                $this->_status->addMessage('info', pm_Locale::lmsg('synchronizeNoResponse'));
            }
        }
        else {
            $this->_status->addMessage('error', pm_Locale::lmsg('synchronizeInvalidRequest'));
        }

        $this->_redirect('index/synchronize');
    }

    /**
     * Updates an existing Uptime Robot monitor with domain data
     * Then redirects to Synchronize tab
     */
    public function updatemonitorAction()
    { 
        $guid = $this->getRequest()->getParam('guid');
        $ur_id = $this->getRequest()->getParam('ur_id');

        // Validate url parameters
        if(!empty($guid) && preg_match('#^[a-f0-9-]+$#', $guid)
        && !empty($ur_id) && preg_match('#^[0-9]+$#', $ur_id)) {
            $pm_Domain = pm_Domain::getByGuid($guid);
            
            // Find if domain is served in ssl
            $ssl = array(FALSE);
            $request = '<site><get><filter><guid>'.$guid.'</guid></filter><dataset><hosting/></dataset></get></site>';
            foreach(pm_ApiRpc::getService()->call($request)->site->get->result->data->hosting->vrt_hst->property as $property) {
                if($property->name == 'ssl') {
                    $ssl = $property->value;
                }
            }

            $json = Modules_UptimeRobot_API::editUptimeMonitor($this->api_key, $pm_Domain->getName(), $ur_id, array(
                'friendly_name' => $pm_Domain->getDisplayName(),
                'ssl' => $ssl[0],
                )); // {"stat":"ok","monitor":{"id":777712827}}

            if($json && $json->stat == 'ok' && !empty($json->monitor->id)) {
                $this->_status->addMessage('info', pm_Locale::lmsg('synchronizeUpdateMonitorDone', [ 'domain' => $pm_Domain->getName(), 'ur_id' => $json->monitor->id ]));
            }
            elseif($json) {
                $this->_status->addMessage('info', pm_Locale::lmsg('synchronizeUpdateMonitorNOK', [ 'domain' => $pm_Domain->getName(), 'json' => json_encode($json) ]));
            }
            else {
                $this->_status->addMessage('info', pm_Locale::lmsg('synchronizeNoResponse'));
            }
        }
        else {
            $this->_status->addMessage('error', pm_Locale::lmsg('synchronizeInvalidRequest'));
        }

        $this->_redirect('index/synchronize');
    }

    /**
     * Removes an Uptime Robot monitor and updates local mapping table
     * Then redirects to Synchronize tab
     */
    public function deletemonitorAction()
    {
        $guid = $this->getRequest()->getParam('guid');
        $ur_id = $this->getRequest()->getParam('ur_id');

        if(!empty($guid) && preg_match('#^[a-f0-9-]+$#', $guid)
        && !empty($ur_id) && preg_match('#^[0-9]+$#', $ur_id)) {
            $json = Modules_UptimeRobot_API::deleteUptimeMonitor($this->api_key, $ur_id); // { "stat":"ok", "monitor":{ "id":777712827 }}

            if($json && $json->stat == 'ok') {
                $this->mapping_table[$guid]->delete_datetime = time();

                // Update mapping table
                if($db_error = $this->_requestMapper->saveMapping($this->mapping_table[$guid])) {
                    $this->_status->addMessage('error', $db_error);
                }
                else {
                    $this->_status->addMessage('info', pm_Locale::lmsg('synchronizeDeleteMonitorDone', [ 'domain' => $this->mapping_table[$guid]->url, 'ur_id' => $this->mapping_table[$guid]->ur_id ]));
                }
            }
            elseif($json) {
                $this->_status->addMessage('error', pm_Locale::lmsg('synchronizeDeleteMonitorNOK', [ 'ur_id' => $ur_id, 'json' => json_encode($json) ]));
            }
            else {
                $this->_status->addMessage('error', pm_Locale::lmsg('synchronizeNoResponse'));
            }
        }
        else {
            $this->_status->addMessage('error', pm_Locale::lmsg('synchronizeInvalidRequest'));
        }
        
        $this->_redirect('index/synchronize');
    }

    /**
     * Creates the chart data
     *
     * @param $monitors
     * @param $timespan
     *
     * @return array
     */
    public function _getChartDataFor($monitors, $timespan)
    {
        $lastXDays = $this->_getLastDays($timespan);
        $monitorsLength = count($monitors);

        $yOnline = [];
        $yOffline = [];
        $textsOffline = [];
        $textsOnline = [];

        $minOnlinePercentage = 100;
        $maxOfflinePercentage = 0;

        foreach ($lastXDays as $currentDay) {
            $duration = 0;
            $textOffline = '';
            $textOnline = '';

            foreach ($monitors as $monitor) {
                foreach ($monitor->logs as $log) {
                    if ($currentDay != date('Y-m-d', $log->datetime)) {
                        continue;
                    }
                    if (1 == $log->type) { // offline
                        $duration += ($log->duration / 60 / 60); //seconds => hours
                        $textOffline .= $monitor->url.': '.($this->_getHTMLByDuration($log->duration)).'<br>';
                    }
                }
            }

            if ($monitorsLength === 0) {
                $offlinePercentage = 0;    
            } else {
                $offlinePercentage = ($duration / (24 * $monitorsLength)) * 100;
            }

            $onlinePercentage = 100 - $offlinePercentage;
            $minOnlinePercentage = min($minOnlinePercentage, $onlinePercentage);
            $maxOfflinePercentage = max($maxOfflinePercentage, $offlinePercentage);
            $yOffline[] = $offlinePercentage.'%';
            $yOnline[] = $onlinePercentage.'%';
            $textsOffline[] = $textOffline;
            $textsOnline[] = $textOnline;
        }

        $data = [];

        $data[] = array(
            'x'         => $lastXDays,
            'y'         => $yOnline,
            'name'      => pm_Locale::lmsg('overviewGraphOnline'),
            'type'      => 'bar',
            'hoverinfo' => 'text',
            'text'      => $textsOnline,
            'marker'    => [
                'color' => 'rgb(182, 240, 125)'
            ]
        );

        $data[] = array(
            'x'         => $lastXDays,
            'y'         => $yOffline,
            'name'      => pm_Locale::lmsg('overviewGraphOffline'),
            'type'      => 'bar',
            'hoverinfo' => 'text',
            'text'      => $textsOffline,
            'marker'    => [
                'color' => 'rgb(240, 125, 125)'
            ]
        );

        return [
            'data'     => $data,
            'minRange' => $minOnlinePercentage,
            'maxRange' => $maxOfflinePercentage
        ];
    }

    /**
     * Get HTML by duration in seconds
     *
     * @param $durationInSeconds
     *
     * @return string
     */
    private function _getHTMLByDuration($durationInSeconds)
    {
        $hours = floor($durationInSeconds / 3600);
        $minutes = floor(($durationInSeconds / 60) % 60);
        $seconds = $durationInSeconds % 60;

        $output = '';

        if ($hours < 10) {
            $output .= '0';
        }

        $output .= $hours.'h, ';

        if ($minutes < 10) {
            $output .= '0';
        }

        $output .= $minutes.'m';

        return $output;
    }

    /**
     * Gets the last days for the chart
     *
     * @param $daysAmount
     *
     * @return array
     */
    private function _getLastDays($daysAmount)
    {
        $days = array();

        for ($i = $daysAmount; $i >= 0; $i--) {
            $days[] = date("Y-m-d", strtotime('-'.$i.' days'));
        }

        return $days;
    }

    /**
     * Creates the global uptime percentage for monitors
     *
     * @param $monitors
     * @param $timespan
     *
     * @return float
     */
    private function _attachUptimePercentageToMonitors(&$monitors, $timespan = 30)
    {
        // 24 hours, 7 days, 30 days, 60 days, 180 days, 360 days
        $perdiods = [
            24,
            24 * 7,
            24 * 30,
            24 * 60,
            24 * 180,
            24 * 360
        ];
        $timespan = 24 * $timespan;

        $globalUptimes = [];

        foreach ($monitors as &$monitor) {
            $monitor->uptime = [];

            // Do not check when monitor is paused
            // 0 = paused; 1 = not checked yet; 2 = up; 8 = seems down; 9 = down
            if($monitor->status === 0){
                $monitor->uptime = false;
                continue;
            }

            foreach ($perdiods as &$period) {
                $durations = $this->_getOverallUptime($monitor, $period);

                // Init global uptime for period
                if (array_key_exists($period, $globalUptimes) == false) {
                    $globalUptimes[$period] = [];
                    $globalUptimes[$period]['online'] = 0;
                    $globalUptimes[$period]['offline'] = 0;
                }

                // Add to global uptime for each period seperated
                $globalUptimes[$period]['online'] += $durations['durationOnline'];
                $globalUptimes[$period]['offline'] += $durations['durationOffline'];

                // Calculate monitor uptime
                $uptimePercentage = $this->_calculateUptimePercentage($durations['durationOnline'], $durations['durationOffline']);
                $uptimePercentage = round($uptimePercentage, 2, PHP_ROUND_HALF_DOWN);
                $monitor->uptime[$period] = $uptimePercentage;
            }
        }

        if (!isset($globalUptimes[$timespan]) || ($globalUptimes[$timespan]['online'] + $globalUptimes[$timespan]['offline']) === 0) {
            return false;
        }

        $timespanUptimePercentage = $this->_calculateUptimePercentage($globalUptimes[$timespan]['online'], $globalUptimes[$timespan]['offline']);
        return round($timespanUptimePercentage, 2, PHP_ROUND_HALF_DOWN);
    }

    /**
     * Gets the overall uptime value
     *
     * @param $monitor
     * @param $withinTheLastHours
     *
     * @return array
     */
    private function _getOverallUptime(&$monitor, $withinTheLastHours)
    {
        // calculate the timestamp from where the stats will be calculated
        $date = new DateTime();
        $tosub = new DateInterval('PT'.$withinTheLastHours.'H');
        $date->sub($tosub);
        $x = $date->getTimestamp();

        $durationOffline = 0;
        $durationOnline = 0;

        // sort by datetime asc
        usort(
            $monitor->logs, function($a, $b) {
            if ($a->datetime == $b->datetime) {
                return 0;
            }

            return ($a->datetime < $b->datetime) ? -1 : 1;
        });

        $index = -1;

        // detect the index where to start from
        $length = count($monitor->logs);

        for ($i = 0; $i < $length; $i++) {

            // care about all entries that are later then x, but also take the last one that was smaller then x
            if ($monitor->logs[$i]->datetime > $x) {
                if ($i - 1 >= 0) {
                    $index = $i - 1; // keep the entry before also, there a splitted result will be calculated
                } else {
                    $index = $i;
                }

                break;
            }
        }

        if ($index == -1) {
            $index = $length - 1;
        }

        // collect data
        $first = true;

        for ($j = $index; $j < $length; $j++) {

            // calculate the first splitted time and add to offline or online sum
            if ($first == true) {
                $delta = $x - $monitor->logs[$j]->datetime;
                $splitted = $monitor->logs[$j]->duration - $delta;

                if ($monitor->logs[$j]->type == 1) {
                    $durationOffline += $splitted;
                } else if ($monitor->logs[$j]->type == 2) {
                    $durationOnline += $splitted;
                }

                $first = false;
                continue;
            }

            // add the rest of the entries
            if ($monitor->logs[$j]->type == 1) {
                $durationOffline += $monitor->logs[$j]->duration;
            } else if ($monitor->logs[$j]->type == 2) {
                $durationOnline += $monitor->logs[$j]->duration;
            }
        }

        return [
            'durationOffline' => $durationOffline,
            'durationOnline'  => $durationOnline
        ];
    }

    /**
     * Calculates the uptime percentage
     *
     * @param $durationOnline
     * @param $durationOffline
     *
     * @return float|int
     */
    private function _calculateUptimePercentage($durationOnline, $durationOffline)
    {
        if($durationOnline === 0 && $durationOffline === 0){
            return 100; // 
        }

        $sum = $durationOffline + $durationOnline;
        $overallUptimePercentage = ($durationOnline / $sum) * 100;

        return $overallUptimePercentage;
    }

    /**
     * Builds data for Synchronize tab list
     */
    private function _getMappingTableList()
    {
        // Get Uptime Monitors
        $monitors = Modules_UptimeRobot_API::fetchUptimeMonitors($this->api_key, array(), TRUE); // indexed by id

        $monitor_ids = $monitors_urls = array();
        foreach($monitors as $monitor) {
            $monitor_ids[] = $monitor->id;
            $monitors_urls[preg_replace('#^http(?:s)?://(.*)/?$#U', '$1', $monitor->url)][] = $monitor->id;
        }

        //$this->_status->addMessage('info', print_r($this->mapping_table, true));
        $this->_status->addMessage('info', date_default_timezone_get());
        $this->_status->addMessage('info', print_r(Modules_UptimeRobot_API::fetchUptimeRobotAccount($this->api_key), true));

        $data = array();
        foreach(pm_Domain::getAllDomains() as $id=>$pm_Domain) {
            $guid = $pm_Domain->getGuid();
            $actions = array();
            $show_others_alert = TRUE;

            $plesk_status = '<i class="fa fa-check-circle text-success"></i> '.pm_Locale::lmsg('synchronizePleskIs').' '.($pm_Domain->isActive() ? pm_Locale::lmsg('synchronizeActive') : pm_Locale::lmsg('synchronizeInactiveOrDisabled'));

            // Mapping found
            if(array_key_exists($guid, $this->mapping_table) && ($this->mapping_table[$guid]->delete_datetime == 0 || time() < $this->mapping_table[$guid]->delete_datetime + self::UR_DELAY)) {
                $mapping_status = '<i class="fa fa-check-circle text-success"></i> '.pm_Locale::lmsg('synchronizeMappingOK');
                $actions['mapunmap'] = '<a href="'.$this->_helper->url('unmap', 'index').'?guid='.$guid.'"><i class="fa fa-chain-broken text-info"></i> '.pm_Locale::lmsg('synchronizeUnmap').'</a>';

                // Uptime Robot getMonitors list is not reliable when monitor was just created/deleted
                // Pending status
                $delay_create = $this->mapping_table[$guid]->create_datetime + self::UR_DELAY - time();
                $delay_delete = $this->mapping_table[$guid]->delete_datetime + self::UR_DELAY - time();
                $pending = $delay_create > 0 ? array('Create' => $delay_create/60 > 1 ? ceil($delay_create/60).' min' : $delay_create.' sec') : FALSE;
                $pending = $delay_delete > 0 ? array('Delete' => $delay_delete/60 > 1 ? ceil($delay_delete/60).' min' : $delay_delete.' sec') : $pending;
                
                // Monitor creation/deletion is pending 
                if($pending) {
                    $ur_status = '<i class="fa fa-circle-o-notch fa-spin text-info"></i> '.pm_Locale::lmsg('synchronizePending'.key($pending), [ 'delay' => reset($pending) ]);
                    $show_others_alert = FALSE;
                }
                // Monitor exists
                elseif(in_array($this->mapping_table[$guid]->ur_id, $monitor_ids)) {
                    $ur_status = '<span data-toggle="tooltip" data-html="true" title="'.$this->_tooltip_content_monitor($monitors[$this->mapping_table[$guid]->ur_id]).'"><i class="fa fa-check-circle text-success"></i> '.pm_Locale::lmsg('synchronizeURid', [ 'ur_id' => $this->mapping_table[$guid]->ur_id ]).'<span>';
                    
                    $actions['updatemonitor'] = '<a href="'.$this->_helper->url('updateMonitor', 'index').'?guid='.$guid.'&ur_id='.$this->mapping_table[$guid]->ur_id.'"><i class="fa fa-refresh text-info"></i> '.pm_Locale::lmsg('synchronizeUpdateMonitor').'</a>';
                    $actions['monitor'] = '<a href="'.$this->_helper->url('deleteMonitor', 'index').'?guid='.$guid.'&ur_id='.$this->mapping_table[$guid]->ur_id.'" onclick="if(!confirm(\''.pm_Locale::lmsg('synchronizeDeleteMonitorMessage', [ 'ur_id' => $this->mapping_table[$guid]->ur_id ]).'\')) { return false; }"><i class="fa fa-trash text-info"></i> '.pm_Locale::lmsg('synchronizeDeleteMonitor').'</a>';
                    $show_others_alert = FALSE;
                }
                // Monitor does not exist
                else {
                    $ur_status = '<i class="fa fa-ban text-danger"></i> '.pm_Locale::lmsg('synchronizeMonitorNotFound', [ 'ur_id' => $this->mapping_table[$guid]->ur_id ]);
                    $actions['monitor'] = '<a href="'.$this->_helper->url('createMonitor', 'index').'?guid='.$guid.'"><i class="fa fa-plus text-info"></i> '.pm_Locale::lmsg('synchronizeCreateMonitor').'</a>';
                }
            }
            // No mapping
            else {
                $mapping_status = '<i class="fa fa-ban text-danger"></i> '.pm_Locale::lmsg('synchronizeNoMapping');
                $ur_status = '<i class="fa fa-ban text-danger"></i> '.pm_Locale::lmsg('synchronizeNoMonitorToMap');
                $actions['monitor'] = '<a href="'.$this->_helper->url('createMonitor', 'index').'?guid='.$guid.'"><i class="fa fa-plus text-info"></i> '.pm_Locale::lmsg('synchronizeCreateMonitor').'</a>';
            }

            // Alternatives among UR monitors ?
            $monitor_urls = array_key_exists($pm_Domain->getName(), $monitors_urls) ? $monitors_urls[$pm_Domain->getName()] : (array_key_exists('www.'.$pm_Domain->getName(), $monitors_urls) ? $monitors_urls['www.'.$pm_Domain->getName()] : FALSE);
            if(!empty($monitor_urls)) {
                if($show_others_alert) {
                    $ur_status = '<i class="fa fa-exclamation-triangle text-warning"></i> '.pm_Locale::lmsg('synchronizeMayBeMapped');
                }
                // Can be mapped
                if(!array_key_exists($guid, $this->mapping_table)) {
                    $actions['mapunmap'] = implode('</li><li>', array_map(function($monitor_id) use ($guid, $monitors, $pm_Domain) {
                        return '<a href="'.$this->_helper->url('map', 'index').'?guid='.$guid.'&ur_id='.$monitor_id.'" class="list-group-item" data-toggle="tooltip" data-html="true" data-placement="left" title="'.$this->_tooltip_content_monitor($monitors[$monitor_id]).'"><i class="fa fa-chain text-info"></i> '.pm_Locale::lmsg('synchronizeMapToMonitor').' '.$monitor_id.'</a>';
                    }, $monitor_urls));
                }
                // Can be remapped
                elseif(!in_array($this->mapping_table[$guid]->ur_id, $monitor_ids)) {
                    $actions['remap'] = implode('</li><li>', array_map(function($monitor_id) use ($guid, $monitors, $pm_Domain) {
                        return '<a href="'.$this->_helper->url('map', 'index').'?guid='.$guid.'&ur_id='.$monitor_id.'" class="list-group-item" onclick="if(!confirm(\''.pm_Locale::lmsg('synchronizeRemapMessage', [ 'domain' => $pm_Domain->getName() ]).'\')) { return false; }" data-toggle="tooltip" data-html="true" data-placement="left" title="'.$this->_tooltip_content_monitor($monitors[$monitor_id]).'"><i class="fa fa-random text-info"></i> '.pm_Locale::lmsg('synchronizeRemapToMonitor').' '.$monitor_id.'</a>';
                    }, $monitor_urls));
                }
            }

            $data[$guid] = array(
                'id' => $pm_Domain->getId(),
                'name' => $pm_Domain->getName(),
                'ip' => gethostbyname(trim($pm_Domain->getName())),
                'plesk_status' => $plesk_status,
                'mapping_status' => $mapping_status,
                'ur_status' => $ur_status,
                'actions' => $actions,
                );
        }
    
        // Find missing domains that still exist in mapping table
        foreach($this->mapping_table as $guid=>$monitor) {
            $actions = array();

            // GUID not yet exists in data. This means domain no more exists 
            if(!array_key_exists($guid, $data)) {
                $mapping_status = '<i class="fa fa-check-circle text-success"></i> '.pm_Locale::lmsg('synchronizeMappingStillExists');
                $actions['mapunmap'] = '<a href="'.$this->_helper->url('unmap', 'index').'?guid='.$guid.'" onclick="if(!confirm(\''.pm_Locale::lmsg('synchronizeUnmapMessageNoDomain').'\')) { return false; }"><i class="fa fa-chain-broken text-info"></i> '.pm_Locale::lmsg('synchronizeUnmap').'</a>';

                // Uptime Robot getMonitors list is not reliable when monitor was just created/deleted
                // Pending status
                $delay_create = $monitor->create_datetime + self::UR_DELAY - time();
                $delay_delete = $monitor->delete_datetime + self::UR_DELAY - time();
                $pending = $delay_create > 0 ? array('Create' => $delay_create/60 > 1 ? ceil($delay_create/60).' min' : $delay_create.' sec') : FALSE;
                $pending = $delay_delete > 0 ? array('Delete' => $delay_delete/60 > 1 ? ceil($delay_delete/60).' min' : $delay_delete.' sec') : $pending;

                if($pending) {
                    $ur_status = '<i class="fa fa-circle-o-notch fa-spin text-info"></i> '.pm_Locale::lmsg('synchronizePending'.key($pending), [ 'delay' => reset($pending) ]);
                }
                elseif(in_array($monitor->ur_id, $monitor_ids)) {
                    $ur_status = '<span data-toggle="tooltip" data-html="true" title="'.$this->_tooltip_content_monitor($monitors[$monitor->ur_id]).'"><i class="fa fa-check-circle text-success"></i> '.pm_Locale::lmsg('synchronizeMonitorStillExists', [ 'ur_id' => $monitor->ur_id ]).'</span>';
                    $actions['monitor'] = '<a href="'.$this->_helper->url('deleteMonitor', 'index').'?guid='.$guid.'&ur_id='.$monitor->ur_id.'" onclick="if(!confirm(\''.pm_Locale::lmsg('synchronizeDeleteMonitorMessage').'\')) { return false; }"><i class="fa fa-trash text-info"></i> '.pm_Locale::lmsg('synchronizeDeleteMonitor').'</a>';
                }
                else {
                    $ur_status = '<i class="fa fa-ban text-danger"></i> '.pm_Locale::lmsg('synchronizeMonitorNotFound', [ 'ur_id' => $monitor->ur_id ]);
                }

                $data[$guid] = array(
                    'id' => '#',
                    'name' => $monitor->url,
                    'ip' => gethostbyname(trim($monitor->url)),
                    'plesk_status' => '<i class="fa fa-ban text-danger"></i> '.pm_Locale::lmsg('synchronizeNoMoreInPlesk'),
                    'mapping_status' => $mapping_status,
                    'ur_status' => $ur_status,
                    'actions' => $actions,
                    );
            }
        }

        // Get Server IPs
        $request = '<ip><get/></ip>';
        $ips = array();
        $jsonIps = json_decode(json_encode(pm_ApiRpc::getService()->call($request)->ip->get->result->addresses));
        foreach($jsonIps->ip_info as $ip_info) {
            $ips[] = $ip_info->ip_address;
        }

        // Bulk mods
        foreach($data as $guid=>&$line) {
            $line['ip'] = $line['ip'] != $line['name'] ? (!in_array($line['ip'], $ips) ? '<i class="fa fa-external-link text-warning"></i> '.pm_Locale::lmsg('synchronizeExternal') : '<i class="fa fa-server text-success"></i> '.pm_Locale::lmsg('synchronizeLocal')).': '.$line['ip'] : '<i class="fa fa-chain-broken text-danger"></i> '.pm_Locale::lmsg('synchronizeUnableToGetIP');
            $line['actions'] = '<div class="dropdown"><span class="btn-group list-menu"><span class="btn btn-list-menu"><button type="button" class="dropdown-toggle" data-toggle="dropdown" data-target="#gen-id-'.$guid.'"><i class="icon"><img src="/theme-skins/heavy-metal//icons/16/plesk/menu.png" alt=""></i> <em class="caret"></em></button></span></span>
            <div id="gen-id-'.$guid.'" class="popup-box popup-menu dropdown-menu collapsed"><table class="popup-wrapper" cellspacing="0"><tbody><tr><td class="popup-container"><div class="popup-content"><div class="popup-content-area"><ul><li>'.implode('</li><li>', $line['actions']).'</li></div></div></td></tr></tbody></table></div></div>';
        }
        unset($line);

        $list = new pm_View_List_Simple($this->view, $this->_request);
        $list->setData($data);
        $list->setColumns([
            //pm_View_List_Simple::COLUMN_SELECTION,
            'id' => [
                'title' => pm_Locale::lmsg('synchronizeId'), 
                'noEscape' => true, 
                'searchable' => true, 
            ],
            'name' => [
                'title' => pm_Locale::lmsg('synchronizeDomain'), 
                'noEscape' => true, 
                'searchable' => true, 
            ],
            'ip' => [
                'title' => 'IP', 
                'noEscape' => true, 
                'searchable' => true, 
            ],
            'plesk_status' => [
                'title' => pm_Locale::lmsg('synchronizePleskStatus'), 
                'noEscape' => true, 
                'sortable' => true, 
            ],
            'mapping_status' => [
                'title' => pm_Locale::lmsg('synchronizeMappingStatus'), 
                'noEscape' => true, 
                'sortable' => true, 
            ],
            'ur_status' => [
                'title' => pm_Locale::lmsg('synchronizeURstatus'), 
                'noEscape' => true, 
                'sortable' => true, 
            ], 
            'actions' => [
                'title' => pm_Locale::lmsg('synchronizeActions'), 
                'noEscape' => true, 
                'sortable' => false, 
            ],
        ]);

        /*
        $list->setTools([
            [
                'title' => 'Check', 
                'description' => 'Link to controller custom and action test of the extension', 
                'controller' => 'custom', 
                'action' => 'test', 
            ], [
                'title' => 'Remove selection', 
                'description' => 'Remove selected rows.', 
                'execGroupOperation' => $this->_helper->url('remove') , 
            ], 
        ]);
        */

        // Take into account synchronizeDataAction corresponds to the URL /synchronize-data/
        $list->setDataUrl(['action' => 'synchronize-data']);
        return $list;
    }

    /**
     * Generates html for monitor tooltip content
     * @param  object $monitor uptime robot monitor
     * @return string          html
     */
    private function _tooltip_content_monitor($monitor)
    {
        $content = '
        <div class="text-left">
            <strong>ID</strong> '.$monitor->id.'<br />
            <strong>Name</strong> '.htmlentities($monitor->friendly_name).'<br />
            <strong>URL</strong> '.htmlentities($monitor->url).'<br />
            <strong>Created on</strong> '.date('d/m/Y', $monitor->create_datetime).'
        </div>';

        return str_replace('"', '\'', str_replace("\n", '', $content)); // because it's used in html attribute
    }

    /**
    * Search a value recursively and returns true or false
    * If $key is not the empty string, the value has to be found associated with this key
    * If $primary is true, returns the first key of first dimension under which $needle was found
    *
    * Description
    * mixed _in_array_recursive ( mixed $needle , array $haystack [, mixed $key = '' [, bool $strict = false [, bool $primary = false] ] ] )
    * 
    * @param  mixed  $needle    the searched value
    * @param  array  $haystack  the searched array
    * @param  mixed  $key       the optional key associated to $needle
    * @param  bool   $strict    whether to compare strictly $needle and $key with their counterpart in array
    * @param  bool   $primary   return key of first dimension when needle is found
    * @return mixed             key of first dimension or true if needle was found, false if needle was not found
    */
    private function _in_array_recursive($needle, $haystack, $key = '', $strict = FALSE, $primary =  FALSE)
    {
        if(!is_array($haystack)) {
            return FALSE;
        }

        foreach($haystack as $k=>$value) {
            if(($strict ? $value === $needle : $value == $needle)
            && ($key === '' || ($strict ? $k === $key : $k == $key))
            || (is_array($value) && $this->_in_array_recursive($needle, $value, $key, $strict, $primary) !== FALSE)) {
                if($primary) {
                    return $k;
                }
                return TRUE;
            }
        }

        return FALSE;
    }

}
