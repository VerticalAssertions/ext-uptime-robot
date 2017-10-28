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

    /**
     * Initialize controller
     */
    public function init()
    {
        parent::init();

        $this->view->headLink()->appendStylesheet(pm_Context::getBaseUrl().'/css/circle.css');
        $this->view->headLink()->appendStylesheet(pm_Context::getBaseUrl().'/bootstrap/css/bootstrap.min.css');
        $this->view->headLink()->appendStylesheet(pm_Context::getBaseUrl().'/font-awesome/css/font-awesome.min.css');

        // Use require.js to solve conflict between prototype (loaded by Plesk) and jquery (used by this extension)
        // Based on http://www.softec.lu/site/DevelopersCorner/BootstrapPrototypeConflict and http://jsfiddle.net/dgervalle/e8Apv/
        $this->view->headScript()->appendFile(pm_Context::getBaseUrl().'/js/require.min.js');
        $this->view->headScript()->appendFile(pm_Context::getBaseUrl().'/js/jquery.global.js'); // Loads jQuery and Bootstrap JS

        $this->api_key = pm_Settings::get('apikey', '');
        //$this->monitor_suffix = pm_Settings::get('monitorsuffix', '');

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

        $this->mapping_table = unserialize(pm_Settings::get('mappingTable', serialize(array())));
    }

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
        $this->view->apikeyForm->addControlButtons(
            [
                'cancelHidden' => true,
                'sendTitle'    => pm_Locale::lmsg('setupApiKeySaveButton')
            ]);

        if ($this->getRequest()->isPost() && $this->view->apikeyForm->isValid($this->getRequest()->getPost())) {
            $api_key = $this->view->apikeyForm->getValue('apikey');
            pm_Settings::set('apikey', trim($api_key));

            if ($api_key) {
                $account = Modules_UptimeRobot_API::fetchUptimeRobotAccount($api_key);
                if ($account->stat == 'ok') {
                    $this->_status->addMessage('info', pm_Locale::lmsg('setupApiKeySaved'));
                } else {
                    $this->_status->addError(pm_Locale::lmsg('setupApiKeyInvalid').json_encode($account));
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
        /*
        $this->view->settingsForm->addElement(
            'text', 'monitorsuffix', [
            'label' => pm_Locale::lmsg('settingsMonitorSuffix'),
            'value' => $this->monitor_suffix
        ]);
        */
        $this->view->settingsForm->addControlButtons(
            [
                'cancelHidden' => true,
                'sendTitle'    => 'Save'
            ]);

        if ($this->getRequest()->isPost() && $this->view->settingsForm->isValid($this->getRequest()->getPost())) {
            $api_key = $this->view->settingsForm->getValue('apikey');
            pm_Settings::set('apikey', trim($api_key));

            if ($api_key) {
                $account = Modules_UptimeRobot_API::fetchUptimeRobotAccount($api_key);

                if ($account->stat == 'ok') {
                    $this->_status->addMessage('info', pm_Locale::lmsg('setupApiKeySaved'));
                } else {
                    $this->_status->addError(pm_Locale::lmsg('setupApiKeyInvalid'));
                }
            }

            /*
            $monitor_suffix = $this->view->settingsForm->getValue('monitorsuffix');

            if ($monitor_suffix) {
                if (preg_match('#^[a-z0-9. -]+$#', $monitor_suffix)) {
                    $this->_status->addMessage('info', pm_Locale::lmsg('settingsMonitorSuffixSaved'));
                    pm_Settings::set('monitorsuffix', trim($monitor_suffix));
                } else {
                    $this->_status->addError(pm_Locale::lmsg('settingsMonitorSuffixInvalid'));
                }
            }
            */

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
     * Synchronize Action
     */
    public function synchronizeAction()
    {

        $list = $this->_getMappingTableList();

        // List object for pm_View_Helper_RenderList
        $this->view->synchronize = $list;

        /*
        $this->view->synchronize = [
            [
                'icon' => '/theme/icons/32/plesk/refresh.png',
                'title' => pm_Locale::lmsg('synchronizeTitle'),
                'description' => '',//$this->lmsg('syncAllHint'),
                'link' => $this->_helper->url('sync-monitors'),
            ], [
                'icon' => '/theme/icons/32/plesk/remove-selected.png',
                'title' => pm_Locale::lmsg('synchronizeRemoveAllTitle'),
                'description' => '',//$this->lmsg('removeAllHint'),
                'link' => $this->_helper->url('remove-monitors'),
            ],
        ];
        */
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
        $this->view->monitorsList = Modules_UptimeRobot_List_Monitors::getList($monitors, $this->view, $this->_request);
        $this->view->eventsList = Modules_UptimeRobot_List_Events::getList($monitors, $this->view, $this->_request);

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
        $list = Modules_UptimeRobot_List_Events::getList($monitors, $this->view, $this->_request);
        $this->_helper->json($list->fetchData());
    }

    /**
     * Monitors List Data Action
     */
    public function monitorslistDataAction()
    {
        $monitors = Modules_UptimeRobot_API::fetchUptimeMonitors($this->api_key);
        $this->_attachUptimePercentageToMonitors($monitors);
        $list = Modules_UptimeRobot_List_Monitors::getList($monitors, $this->view, $this->_request);
        $this->_helper->json($list->fetchData());
    }

    /**
     * Sync websites list Action
     * Keeps monitors list up-to-date with plesk websites
     */
    public function syncMonitorsAction()
    {
        // NON-EXISTENT DOMAINS: monitor removal
        // Loop over monitors list
        $del_monitors = array('ok' => array(), 'nok' => array());
        foreach($this->mapping_table as $guid=>$monitor) {
            try {
                $pm_Domain = pm_Domain::getByGuid($guid);
            }
            catch(Exception $e) {
                $jsonRemoval = Modules_UptimeRobot_API::deleteUptimeMonitor($this->api_key, $monitor->ur_id); // { "stat":"ok", "monitor":{ "id":777712827 }}

                if($jsonRemoval && $jsonRemoval->stat == 'ok') {
                    $del_monitors['ok'][] = $monitor->url;
                    unset($this->mapping_table[$guid]);

                    // Store new mapping_table
                    pm_Settings::set('mappingTable', json_encode($this->mapping_table));
                }
                elseif($jsonCreation) {
                    $del_monitors['nok'][] = $monitor->url.' : '.json_encode($jsonCreation);
                }
                else {
                    $del_monitors['nok'][] = $monitor->url.': '.pm_Locale::lmsg('synchronizeNoResponse');
                }
            }
        }

        // DEPRECATED DOMAINS: monitor removal
        if(!empty($del_monitors['ok'])) {
            $this->_status->addMessage('info', pm_Locale::lmsg('synchronizeRemovalOK').implode(', ', $del_monitors['ok']));
        }
        if(!empty($del_monitors['nok'])) {
            $this->_status->addMessage('error', pm_Locale::lmsg('synchronizeRemovalNOK')."\n".implode("\n", $del_monitors['nok']));
        }

        // Get Server IP's
        $request = '<ip><get/></ip>';
        $ips = array();
        $jsonIps = json_decode(json_encode(pm_ApiRpc::getService()->call($request)->ip->get->result->addresses));
        foreach($jsonIps->ip_info as $ip_info) {
            $ips[] = $ip_info->ip_address;
        }

        // Loop over websites
        $new_monitors = array('ok' => array(), 'nok' => array(), 'no' => array());
        foreach(pm_Domain::getAllDomains() as $id=>$pm_Domain) {
            $guid = $pm_Domain->getGuid();
            $name = $pm_Domain->getName();
            $ip = gethostbyname(trim($name));

            // if domain does not resolve on that server, forget it
            if(!in_array($ip, $ips)) {
                $new_monitors['no'][] = $name.' : '.pm_Locale::lmsg('synchronizeNotHostedOnServer').' ('.($ip != $name ? 'IP '.$ip : pm_Locale::lmsg('synchronizeUnableToGetIP')).')';
                continue;
            }

            // GUID not in local mapping table: create monitor
            if(!array_key_exists($guid, $this->mapping_table)) {
                $display_name = $pm_Domain->getDisplayName();
                // Find if domain is served in ssl
                $ssl = array(FALSE);
                $request = '<site><get><filter><guid>'.$guid.'</guid></filter><dataset><hosting/></dataset></get></site>';
                foreach(pm_ApiRpc::getService()->call($request)->site->get->result->data->hosting->vrt_hst->property as $property) {
                    if($property->name == 'ssl') {
                        $ssl = $property->value;
                    }
                }

                $jsonCreation = Modules_UptimeRobot_API::createUptimeMonitor($this->api_key, $name, array(
                    'friendly_name' => $display_name,
                    'ssl' => $ssl[0],
                    )); // { "stat": "ok", "monitor": { "id": 777810874, "status": 1 }}

                if($jsonCreation && $jsonCreation->stat == 'ok' && !empty($jsonCreation->monitor->id)) {
                    $new_monitors['ok'][] = $name;
                    $this->mapping_table[$guid] = array(
                        'ur_id' => $jsonCreation->monitor->id,
                        'url' => $name,
                        );

                    // Store new mapping_table
                    pm_Settings::set('mappingTable', serialize($this->mapping_table));
                }
                elseif($jsonCreation) {
                    $new_monitors['nok'][] = $name.' : '.json_encode($jsonCreation);
                }
                else {
                    $new_monitors['nok'][] = $name.': '.pm_Locale::lmsg('synchronizeNoResponse');
                }
            }
        }

        if(!empty($new_monitors['ok'])) {
            $this->_status->addMessage('info', pm_Locale::lmsg('synchronizeCreationOK').implode(', ', $new_monitors['ok']));
        }
        if(!empty($new_monitors['no'])) {
            $this->_status->addMessage('warning', pm_Locale::lmsg('synchronizeCreationNO')."\n".implode("\n", $new_monitors['no']));
        }
        if(!empty($new_monitors['nok'])) {
            $this->_status->addMessage('error', pm_Locale::lmsg('synchronizeCreationNOK')."\n".implode("\n", $new_monitors['nok']));
        }
        $this->_status->addMessage('info', pm_Locale::lmsg('synchronizeDone'));
        $this->_redirect('index/synchronize');
    }

    public function removeAction()
    {
        $messages = [];
        foreach((array)$this->_getParam('ids') as $id) {

            // Here we should remove the object with id = $id

            $messages[] = ['status' => 'info', 'content' => "Row #$id was successfully removed."];
        }
        $this->_helper->json(['status' => 'success', 'statusMessages' => $messages]);
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
            $textsOnline = '';

            foreach ($monitors as &$monitor) {
                foreach ($monitor->logs as &$log) {
                    if ($currentDay == date('Y-m-d', $log->datetime) && $log->type == 1) {
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
            $textsOnline[] = $textsOnline;
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

        if (($globalUptimes[$timespan]['online'] + $globalUptimes[$timespan]['offline']) === 0) {
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
        $monitors = Modules_UptimeRobot_API::fetchUptimeMonitors($this->api_key);
        $monitors_ids = array_map(function($monitor) { return $monitors->id; }, $monitors);
        $monitor_urls = array();
        foreach($monitors as $monitor) {
            $monitor_urls[preg_replace('#^http(?:s)?://(.*)/?$#U', '$1', $monitor->url)][] = $monitor->id;
        }

        $this->_status->addMessage('info', print_r($monitor_urls, true));

        $data = array();
        foreach(pm_Domain::getAllDomains() as $id=>$pm_Domain) {
            $guid = $pm_Domain->getGuid();
            $actions = array(
                'monitor' => '<span class="text-muted"><i class="fa fa-remove"></i> '.pm_Locale::lmsg('synchronizeDeleteMonitor').'</span>',
                'mapunmap' => '<span class="text-muted"><i class="fa fa-chain-broken"></i> '.pm_Locale::lmsg('synchronizeUnmap').'</span>',
                'remap' => '<span class="text-muted"><i class="fa fa-random"></i> '.pm_Locale::lmsg('synchronizeRemap').'</span>',
                );

            if($pm_Domain->isActive()) {
                $plesk_status = '<i class="fa fa-check-circle text-success" title="'.pm_Locale::lmsg('synchronizePleskID').' '.$pm_Domain->getId().' '.pm_Locale::lmsg('synchronizeActive').'"></i>';
            }
            else {
                $plesk_status = '<i class="fa fa-exclamation-triangle text-warning" title="'.pm_Locale::lmsg('synchronizePleskID').' '.$pm_Domain->getId().' - '.pm_Locale::lmsg('synchronizeInactiveOrDisabled').'"></i>';
            }

            if(array_key_exists($guid, $this->mapping_table)) {
                $mapping_status = '<i class="fa fa-check-circle text-success"></i>';
                $actions['mapunmap'] = '<i class="fa fa-chain-broken text-info"></i> <a href="'.$this->_helper->url('unmap', 'index').'">'.pm_Locale::lmsg('synchronizeUnmap').'</a>';

                if(in_array($this->mapping_table[$guid]['ur_id'], $monitor_ids)) {
                    $ur_status = '<i class="fa fa-check-circle text-success" title="'.pm_Locale::lmsg('synchronizeURid').' '.$this->mapping_table[$guid]['ur_id'].'"></i>';
                    $actions['monitor'] = '<i class="fa fa-remove text-info"></i> <a href="'.$this->_helper->url('deleteMonitor', 'index').'?monitor_id='.$this->mapping_table[$guid]['ur_id'].'" onmousedown="if(!confirm(\''.pm_Locale::lmsg('synchronizeDeleteMonitorMessage').'\')) { return false; }">'.pm_Locale::lmsg('synchronizeDeleteMonitor').'</a>';
                }
                else {
                    $mapping_status = '<i class="fa fa-exclamation-triangle text-warning" title="'.pm_Locale::lmsg('synchronizeNotInUR').'"></i>';
                    $ur_status = '<i class="fa fa-ban text-danger" title="'.pm_Locale::lmsg('synchronizeNotInUR').'"></i>';
                    $actions['monitor'] = '<i class="fa fa-plus text-info"></i> <a href="'.$this->_helper->url('createMonitor', 'index').'?guid='.$guid.'">'.pm_Locale::lmsg('synchronizeCreateMonitor').'</a>';
                }
            }
            else {
                $mapping_status = '<i class="fa fa-ban text-danger" title="'.pm_Locale::lmsg('synchronizeNoMapping').'"></i>';
                $ur_status = '<i class="fa fa-ban text-danger" title="'.pm_Locale::lmsg('synchronizeNoMapping').'"></i>';
                $actions['monitor'] = '<i class="fa fa-plus text-info"></i> <a href="'.$this->_helper->url('createMonitor', 'index').'?guid='.$guid.'">'.pm_Locale::lmsg('synchronizeCreateMonitor').'</a>';
            }

            // Alternatives among UR monitors ?
            if(array_key_exists($pm_Domain->getName(), $monitor_urls)) {
                $ur_status = '<i class="fa fa-exclamation-triangle text-warning" title="'.pm_Locale::lmsg('synchronizeOtherInUR').'"></i>';
                if(!array_key_exists($guid, $this->mapping_table)) {
                    $actions['mapunmap'] = '<i class="fa fa-chain text-info"></i> <a href="'.$this->_helper->url('map', 'index').'?guid='.$guid.'">'.pm_Locale::lmsg('synchronizeMap').'</a>';
                }
                elseif(!in_array($this->mapping_table[$guid]['ur_id'], $monitor_ids)) {
                    $actions['remap'] = '<i class="fa fa-random text-info"></i> <a href="#" class="popover-trigger" data-target="#popover-'.$guid.'">'.pm_Locale::lmsg('synchronizeRemap').'</a><div class="list-group hidden" id="popover-'.$guid.'">'.implode('', array_map(function($monitor_id) { return '<a href="'.$this->_helper->url('remap', 'index').'?monitor_id='.$monitor_id.'" class="list-group-item" onmousedown="if(!confirm(\''.pm_Locale::lmsg('synchronizeRemapMessage').'\')) { return false; }">'.pm_Locale::lmsg('synchronizeURid').' '.$monitor_id.'</a>'; }, $monitor_ids)).'</div>'; 
                }
            }

            $data[$guid] = array(
                'name' => $pm_Domain->getName(),
                'plesk_status' => $plesk_status,
                'mapping_status' => $mapping_status,
                'ur_status' => $ur_status,
                'actions' => '<ul class="actions"><li>'.implode('</li><li>', $actions).'</li>',
                );
        }
    
        // Find missing domains that still exist in mapping table
        foreach($this->mapping_table as $guid=>$monitor) {
            $actions = array(
                'monitor' => '<span class="text-muted"><i class="fa fa-remove"></i> '.pm_Locale::lmsg('synchronizeDeleteMonitor').'</span>',
                'mapunmap' => '<span class="text-muted"><i class="fa fa-chain-broken"></i> '.pm_Locale::lmsg('synchronizeUnmap').'</span>',
                'remap' => '<span class="text-muted"><i class="fa fa-random"></i> '.pm_Locale::lmsg('synchronizeRemap').'</span>',
                );
            if(!array_key_exists($guid, $data)) {
                $mapping_status = '<i class="fa fa-check-circle text-success"></i>';
                $actions['mapunmap'] = '<i class="fa fa-chain-broken text-info"></i> <a href="'.$this->_helper->url('unmap', 'index').'?guid='.$guid.'" onmousedown="if(!confirm(\''.pm_Locale::lmsg('synchronizeUnmapMessage').'\')) { return false; }">'.pm_Locale::lmsg('synchronizeUnmap').'</a>';

                if(in_array($monitor['ur_id'], $monitor_ids)) {
                    $ur_status = '<i class="fa fa-check-circle text-success" title="'.pm_Locale::lmsg('synchronizeURid').' '.$monitor['ur_id'].'"></i>';
                    $actions['monitor'] = '<i class="fa fa-remove text-info"></i> <a href="'.$this->_helper->url('deleteMonitor', 'index').'?monitor_id='.$monitor['ur_id'].'" onmousedown="if(!confirm(\''.pm_Locale::lmsg('synchronizeDeleteMonitorMessage').'\')) { return false; }">'.pm_Locale::lmsg('synchronizeDeleteMonitor').'</a>';
                }
                else {
                    $mapping_status = '<i class="fa fa-exclamation-triangle text-warning" title="'.pm_Locale::lmsg('synchronizeNotInUR').'"></i>';
                    // Makes no sense to create monitor since domain no more exists
                    //$actions['monitor'] = '<i class="fa fa-plus text-info"></i> <a href="'.$this->_helper->url('createMonitor', 'index').'?guid='.$guid.'">'.pm_Locale::lmsg('synchronizeCreateMonitor').'</a>';

                    // Alternatives among UR monitors ?
                    if(array_key_exists($pm_Domain->getName(), $monitor_urls)) {
                        $ur_status = '<i class="fa fa-exclamation-triangle text-warning" title="'.pm_Locale::lmsg('synchronizeOtherInUR').'"></i>';
                        // Makes no sense to remap since domain no more exists
                        //$actions['remap'] = '<i class="fa fa-random text-info"></i> <a href="#" class="popover-trigger" data-target="#popover-'.$guid.'">'.pm_Locale::lmsg('synchronizeRemap').'</a><div class="list-group hidden" id="popover-'.$guid.'">'.implode('', array_map(function($monitor_id) { return '<a href="'.$this->_helper->url('remap', 'index').'?monitor_id='.$monitor_id.'" class="list-group-item" onmousedown="if(!confirm(\''.pm_Locale::lmsg('synchronizeRemapMessage').'\')) { return false; }">'.pm_Locale::lmsg('synchronizeURid').' '.$monitor_id.'</a>'; }, $monitor_urls)).'</div>';
                    }
                    else {
                        $ur_status = '<i class="fa fa-ban text-danger" title="'.pm_Locale::lmsg('synchronizeNotInUR').'"></i>';
                    }
                }

                $data[$guid] = array(
                    'name' => $monitor['url'],
                    'plesk_status' => '<i class="fa fa-ban text-danger" title="'.pm_Locale::lmsg('synchronizeNotInPlesk').'"></i>',
                    'mapping_status' => $mapping_status,
                    'ur_status' => $ur_status,
                    'actions' => '<ul class="actions"><li>'.implode('</li><li>', $actions).'</li>',
                    );
            }
        }


        $list = new pm_View_List_Simple($this->view, $this->_request);
        $list->setData($data);
        $list->setColumns([
            pm_View_List_Simple::COLUMN_SELECTION,
            'name' => [
                'title' => pm_Locale::lmsg('synchronizeDomain'), 
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

        // Take into account synchronizeDataAction corresponds to the URL /synchronize-data/
        $list->setDataUrl(['action' => 'synchronize-data']);
        return $list;
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
