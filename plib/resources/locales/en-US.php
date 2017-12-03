<?php
// Copyright 1999-2017. Plesk International GmbH.

$messages = [
    'UptimeRobotButtonTitle'            => 'Uptime Robot statistics',
    'UptimeRobotButtonDescription'      => 'Shows list of monitors, status, average uptime. You may also create and delete monitors for local domains and subdomains',
    'noDefaultAlertContact'             => 'No default alert contact has been set: new monitors won\'t warn anyone. You can set it on <a href="%%settingsurl%%">extension settings</a>',
    'setupApiKeyInputLabel'             => 'Main API Key',
    'setupApiKeySaveButton'             => 'Save',
    'setupApiKeyInvalid'                => 'Invalid API Key: %%error%%.',
    'setupTimezoneInputLabel'           => 'Timezone set in Uptime Robot account',
    'setupDefaultAlertContact'          => 'Default alert contact',
    'setupNewTo'                        => 'I am new to Uptime Robot',
    'setupNewToDescription'             => 'Click the link below to create a new Uptime Robot account on the Uptime Robot website. This page will open in a new tab. After signing up, click the button on the right-hand side of this page.',
    'setupCreateAccount'                => 'Create a new account',
    'setupHaveAccount'                  => 'I have an Uptime Robot account',
    'setupHaveAccountDescription'       => 'Please insert your Uptime Robot "Main API Key" into the following form. The API key can be found on the <a href="https://uptimerobot.com/dashboard.php#mySettings" target="_blank">Uptime Robot settings page</a> at the bottom, after you logged in to your Uptime Robot account.',
    'setupMappingTable'                 => 'Mapping table is empty: no monitor for this server.',
    'setupDatadirNotWritable'           => 'Data directory <em>%%datapath%%</em> should be writable for <em>psaadm:psaadm</em>',
    'setupDatabaseNotWritable'          => 'Database file <em>%%dbpath%%</em> should be writable for <em>psaadm:psaadm</em>',
    'setupUnableToCreateDatabase'       => 'Unable to create database file <em>%%dbpath%%</em> with error: %%errormsg%%',
    'setupDatabaseCreated'              => 'Uptime Robot database created',
    'setupUnableToCreateMappingTable'   => 'Unable to create mapping table with error: %%errormsg%%',
    'overviewTitle'                     => 'Overview',
    'overviewGlobalUptimeText'          => 'Uptime for all domains within the <strong>last %%timespan%%</strong> days.',
    'overviewMonitors'                  => 'Monitors',
    'overviewMonitorsDescription'       => 'To manage your monitors please visit <a href="https://uptimerobot.com/dashboard#mainDashboard" target="_blank">Uptime Robot site</a>.',
    'overviewMonitorID'                 => 'ID',
    'overviewMonitorType'               => 'Type',
    'overviewMonitorStatus'             => 'Status',
    'overviewMonitorStatusPaused'       => 'Paused',
    'overviewMonitorStatusNotChecked'   => 'Not checked yet',
    'overviewMonitorStatusUp'           => 'Up',
    'overviewMonitorStatusSeemsDown'    => 'Seems Down',
    'overviewMonitorStatusDown'         => 'Down',
    'overviewMonitorStatusUnknown'      => 'Unkown Status',
    'overviewMonitorURL'                => 'URL',
    'overviewMonitorUptime'             => 'Uptime: ',
    'overviewMonitorTypeHttp'           => 'HTTP(s)',
    'overviewMonitorTypeKeyword'        => 'Keyword',
    'overviewMonitorTypePing'           => 'Ping',
    'overviewMonitorTypePort'           => 'Port',
    'overviewMonitorTypeUnknown'        => 'Unknown',
    'overviewMonitorLast24Hours'        => 'last 24 hours',
    'overviewMonitorLast60days'         => 'last 60 days',
    'overviewMonitorLast360days'        => 'last 360 days',
    'overviewEvents'                    => 'Events',
    'overviewGraphHeading'              => 'Global Online/Offline Percentage',
    'overviewGraphOfflineTimes'         => 'Online/Offline Percentage',
    'overviewGraphLast'                 => 'Last',
    'overviewGraphDays'                 => 'days',
    'overviewGraphOffline'              => 'Offline',
    'overviewGraphOnline'               => 'Online',
    'overviewEventColEvent'             => 'Event',
    'overviewEventColMonitor'           => 'Monitor',
    'overviewEventColDateTime'          => 'Date-Time',
    'overviewEventColReason'            => 'Reason',
    'overviewEventColDuration'          => 'Duration',
    'overviewEventOnline'               => 'Online',
    'overviewEventOffline'              => 'Offline',
    'overviewEventStarted'              => 'Started',
    'overviewEventPaused'               => 'Paused',
    'overviewEventUnknown'              => 'Unknown Event Type',
    'overviewMonitorsDays'              => 'days',
    'overviewMonitorsHours'             => 'hours',
    'overviewGlobalUptime'              => 'Global Uptime',
    'overviewGlobalUptimeNA'            => 'n.a.',
    'overviewTimespan7'                 => '7 days',
    'overviewTimespan30'                => '30 days',
    'overviewTimespan60'                => '60 days',
    'overviewTimespan180'               => '180 days',
    'overviewTimespan360'               => '360 days',
    'settingsTitle'                     => 'Settings',
    'settingsAccountDetails'            => 'Account Details',
    'settingsApiKey'                    => 'API Key',
    'settingsMail'                      => 'Email',
    'settingsMonitorLimit'              => 'Monitor Limit',
    'settingsMonitorInterval'           => 'Monitor Interval',
    'settingsUpMonitor'                 => 'Up Monitors',
    'settingsDownMonitor'               => 'Down Monitors',
    'settingsPausedMonitor'             => 'Paused Monitors',
    'settingsSaved'                     => 'Settings saved successfully',
    'settingsMonitorSuffix'             => 'Monitors name suffix',
    'settingsMonitorSuffixSaved'        => 'Monitor name suffix saved successfully!',
    'settingsMonitorSuffixInvalid'      => 'Invalid monitor name suffix!',
    'synchronizeMonitors'               => 'Domains and monitors mapping',
    'synchronizeTitle'                  => 'Synchronize',
    'synchronizeNoResponse'             => 'Uptime Robot sent no response',
    'synchronizeNotHostedOnServer'      => 'not hosted on this server',
    'synchronizeUnableToGetIP'          => 'Unable to get IP',
    'synchronizeDomain'                 => 'Domains',
    'synchronizePleskStatus'            => 'Domain status',
    'synchronizeMappingStatus'          => 'Mapping status',
    'synchronizeURstatus'               => 'Uptime Robot monitor status',
    'synchronizePleskIs'                => 'Plesk domain is', // status
    'synchronizeActive'                 => 'ACTIVE',
    'synchronizeInactiveOrDisabled'     => 'INACTIVE or DISABLED',
    'synchronizeNoMoreInPlesk'          => 'Domain no more in Plesk',
    'synchronizeURid'                   => 'Monitor found with id %%ur_id%%', // ur_id
    'synchronizeNoMapping'              => 'Domain mapped to no monitor',
    'synchronizeMappingOK'              => 'Domain mapped',
    'synchronizeActions'                => 'Actions',
    'synchronizeUnmap'                  => 'Unmap',
    'synchronizeMapToMonitor'           => 'Map to monitor',
    'synchronizeRemapToMonitor'         => 'Remap to monitor',
    'synchronizeDeleteMonitor'          => 'Delete monitor',
    'synchronizeCreateMonitor'          => 'Create monitor',
    'synchronizeUpdateMonitor'          => 'Update monitor',
    'synchronizeRemapMessage'           => "You are about to map the domain %%domain%% to another monitor.\\nDo you wish to continue?", // domain
    'synchronizeDeleteMonitorMessage'   => "You are about to delete the monitor with id %%ur_id%%.\\nDo you wish to continue?", // ur_id
    'synchronizeUnmapMessage'           => "You are about to delete the mapping entry.\\nDo you wish to continue?",
    'synchronizeUnmapMessageNoDomain'   => "This mapping entry will disappear from list and you won't be able to delete UR monitor.\\nDo you wish to continue?",
    'synchronizeMapDone'                => 'Domain %%domain%% has been mapped to monitor %%ur_id%%', // domain, ur_id
    'synchronizeUnmapDone'              => 'Domain %%domain%% has been unmapped from monitor %%ur_id%%', // domain, ur_id
    'synchronizeCreateMonitorDone'      => 'Uptime Robot monitor created for domain %%domain%% with id %%ur_id%%', // domain, ur_id
    'synchronizeCreateMonitorNOK'       => 'Unable to create monitor for domain %%domain%%. Received output: %%json%%', // domain, json
    'synchronizeUpdateMonitorDone'      => 'Monitor with id %%ur_id%% updated for domain %%domain%%', // domain, ur_id
    'synchronizeUpdateMonitorNOK'       => 'Unable to update monitor with id %%ur_id%%.Output received: %%json%%', //ur_id, json
    'synchronizeDeleteMonitorDone'      => 'Uptime Robot monitor %%ur_id%% deleted', // ur_id
    'synchronizeDeleteMonitorNOK'       => 'Unable to delete monitor with id %%ur_id%%. Output received: %%json%%', // ur_id, json
    'synchronizeDomainNotFound'         => 'No domain matches GUID %%guid%%', // guid
    'synchronizeMappingNotFound'        => 'No mapping matches GUID %%guid%%', // guid
    'synchronizeInvalidRequest'         => 'The request you submitted is invalid',
    'synchronizeId'                     => 'Id',
    'synchronizePendingCreate'          => 'Monitor just created. Refresh page in about %%delay%%', // delay
    'synchronizePendingDelete'          => 'Monitor just deleted. Refresh page in about %%delay%%', // delay
    'synchronizeMayBeMapped'            => 'May be mapped to a monitor',
    'synchronizeMayBeRemapped'          => 'May be mapped to another monitor',
    'synchronizeMonitorNotFound'        => 'Monitor %%ur_id%% not found', // ur_id
    'synchronizeNoMonitorToMap'         => 'No monitor to map',
    'synchronizeMappingStillExists'     => 'Mapping still exists',
    'synchronizeMonitorStillExists'     => 'Monitor %ur_id%% still exists', //ur_id
    'synchronizeExternal'               => 'External',
    'synchronizeLocal'                  => 'Local',

];
