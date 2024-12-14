<?php

require_once(__DIR__ . '/../libs/ModuleUtilities.php');
require_once(__DIR__ . '/../libs/HCLDevice.php');

class HomeConnectLocalHood extends HCLDevice
{
    use ModuleUtilities;

    // hood specific
    const UID_STATUS_GREASEFILTERSATURATION = 4100;
    const UID_SETTING_GREASEFILTERRESET = 55304;
    const UID_OPTION_VENTINGLEVEL = 55308;

    const UID_PROGRAM_NONE = 0;
    const UID_PROGRAM_AUTO = 55296;
    const UID_PROGRAM_MANUAL = 55307;
    const UID_PROGRAM_INTERVAL = 55306;
    const UID_PROGRAM_DELAYEDSHUTOFF = 55301;

    const UID_PROGRAMS = [
        self::UID_PROGRAM_NONE,
        self::UID_PROGRAM_AUTO,
        self::UID_PROGRAM_MANUAL,
        self::UID_PROGRAM_INTERVAL,
        self::UID_PROGRAM_DELAYEDSHUTOFF
    ];

    const UID_SETTING_LIGHTING = 53253;

    const EVENTS = [
        [
                "uid" => 577,
                "name" => "BSH.Common.Event.ConnectLocalWiFi",
                "desc" => "BSH.Common.Event.ConnectLocalWiFi",
                "level" => "warning"
        ],
        [
                "uid" => 559,
                "name" => "BSH.Common.Event.CustomerServiceRequest",
                "desc" => "BSH.Common.Event.CustomerServiceRequest",
                "level" => "hint"
        ],
        [
                "uid" => 540,
                "name" => "BSH.Common.Event.ProgramFinished",
                "desc" => "BSH.Common.Event.ProgramFinished",
                "level" => "hint"
        ],
        [
                "uid" => 593,
                "name" => "BSH.Common.Event.SoftwareDownloadAvailable",
                "desc" => "BSH.Common.Event.SoftwareDownloadAvailable",
                "level" => "hint"
        ],
        [
                "uid" => 21,
                "name" => "BSH.Common.Event.SoftwareUpdateAvailable",
                "desc" => "BSH.Common.Event.SoftwareUpdateAvailable",
                "level" => "hint"
        ],
        [
                "uid" => 595,
                "name" => "BSH.Common.Event.SoftwareUpdateSuccessful",
                "desc" => "BSH.Common.Event.SoftwareUpdateSuccessful",
                "level" => "hint"
        ],
        [
                "uid" => 55298,
                "name" => "Cooking.Common.Event.Hood.CarbonFilterMaxSaturationNearlyReached",
                "desc" => "Cooking.Common.Event.Hood.CarbonFilterMaxSaturationNearlyReached",
                "level" => "hint"
        ],
        [
                "uid" => 55299,
                "name" => "Cooking.Common.Event.Hood.CarbonFilterMaxSaturationReached",
                "desc" => "Cooking.Common.Event.Hood.CarbonFilterMaxSaturationReached",
                "level" => "warning"
        ],
        [
                "uid" => 55302,
                "name" => "Cooking.Common.Event.Hood.GreaseFilterMaxSaturationNearlyReached",
                "desc" => "Cooking.Common.Event.Hood.GreaseFilterMaxSaturationNearlyReached",
                "level" => "hint"
        ],
        [
                "uid" => 55303,
                "name" => "Cooking.Common.Event.Hood.GreaseFilterMaxSaturationReached",
                "desc" => "Cooking.Common.Event.Hood.GreaseFilterMaxSaturationReached",
                "level" => "warning"
        ],
        [
                "uid" => 55310,
                "name" => "Cooking.Common.Event.Hood.RegenerativeCarbonFilterLifeTimeExceeded",
                "desc" => "Cooking.Common.Event.Hood.RegenerativeCarbonFilterLifeTimeExceeded",
                "level" => "warning"
        ],
        [
                "uid" => 55314,
                "name" => "Cooking.Common.Event.Hood.RegenerativeCarbonFilterLifeTimeNearlyExceeded",
                "desc" => "Cooking.Common.Event.Hood.RegenerativeCarbonFilterLifeTimeNearlyExceeded",
                "level" => "hint"
        ],
        [
                "uid" => 55312,
                "name" => "Cooking.Common.Event.Hood.RegenerativeCarbonFilterMaxSaturationReached",
                "desc" => "Cooking.Common.Event.Hood.RegenerativeCarbonFilterMaxSaturationReached",
                "level" => "warning"
        ]
    ];

    /**
     * @TODO: define static variables for UIDs
     */

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        // properties
        $this->RegisterPropertyString('Topic', 'homeconnect/hood');
        $this->RegisterPropertyInteger('script', '0');

        // profiles
        $this->RegisterProfileIntegerEx('HomeConnectLocalHood.Program', 'Program', '', '', [
            [self::UID_PROGRAM_NONE, 'None',  '', -1],
            [self::UID_PROGRAM_AUTO, 'Auto',  '', -1],
            [self::UID_PROGRAM_MANUAL, 'Manual',  '', -1],
            [self::UID_PROGRAM_INTERVAL, 'Interval',  '', -1],
            [self::UID_PROGRAM_DELAYEDSHUTOFF, 'Delayed shut off',  '', -1]
        ]);

        $this->RegisterProfileIntegerEx('HomeConnectLocalHood.VentingLevel', 'Venting Level', '', '', [
            [0, 'Off',  '', -1],
            [1, '1',  '', -1],
            [2, '2',  '', -1],
            [3, '3',  '', -1],
            [4, 'Boost',  '', -1],
            [5, 'Super Boost',  '', -1]
        ]);

        // variables
        $this->RegisterVariableInteger("Connected", "Connected", "", 0);
        $this->RegisterVariableBoolean("Power", "Power", "~Switch", 1);
        $this->RegisterVariableString("State", "State", "", 2);
        $this->RegisterVariableInteger("Program", "Program", "HomeConnectLocalHood.Program", 3);
        $this->RegisterVariableInteger("VentingLevel", "Venting Level", "HomeConnectLocalHood.VentingLevel", 4);
        $this->RegisterVariableBoolean("Lighting", "Light", "~Switch", 5);
        $this->RegisterVariableInteger("GreaseFilterSaturation", "Grease Filter Saturation", "~Intensity.100", 6);
        $this->RegisterScript("ResetGreaseFilter", "Reset Grease Filter", "<?php\n HCL_ResetGreaseFilter(IPS_GetParent(\$_IPS['SELF']))", 7);

        $this->EnableAction("VentingLevel");
        $this->EnableAction("Program");
        $this->EnableAction("Lighting");
        $this->EnableAction("Power");

        $this->HCLInit();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $topic = $this->ReadPropertyString('Topic');
        $filter = implode('/', array_slice(explode('/', $topic), 0, -1)) . '/LWT|' . $topic . '/.*';
        $this->SendDebug('Filter', $filter, 0);
        $this->SetReceiveDataFilter('.*(' . $filter . ').*');

        $this->HCLRequestUpdate();
    }

    public function ReceiveData($JSONString)
    {
        $this->SendDebug('JSON', $JSONString, 0);
        if (empty($this->ReadPropertyString('Topic'))) return;

        $data = json_decode($JSONString);

        $Buffer = $data;

        if (fnmatch('*/LWT', $Buffer->Topic)) {
            $this->HCLUpdateConnected($Buffer->Topic, $Buffer->Payload);
        } else {
            $payload = json_decode($Buffer->Payload, true);

            // handle events
            $this->HCLHandleEvents(self::EVENTS, $payload);

            $state = $this->HCLUpdateState($payload);
            
            $program = 'N/A';
            
            $powerState = $this->HCLGet($state, self::UID_SETTING_POWERSTATE, self::VALUE_POWERSTATE_OFF);
            $operationState = $this->HCLGet($state, self::UID_STATUS_OPERATIONSTATE, self::VALUE_OPERATIONSTATE_INACTIVE);
            $activeProgram = $this->HCLGet($state, self::UID_ACTIVEPROGRAM, 0);
            
            $ventingLevel = $this->HCLGet($state, self::UID_OPTION_VENTINGLEVEL, 0);
            $saturation = $this->HCLGet($state, self::UID_STATUS_GREASEFILTERSATURATION, 0);
            $lighting = $this->HCLGet($state, self::UID_SETTING_LIGHTING, false);
            $remainingProgramTime = $this->HCLGet($state, self::UID_OPTION_REMAININGPROGRAMTIME, 0);

            if($activeProgram === self::UID_PROGRAM_AUTO) {
                $program = 'Auto';
            } else if($activeProgram === self::UID_PROGRAM_MANUAL) {
                $program = 'Manual';
            } else if($activeProgram === self::UID_PROGRAM_INTERVAL) {
                $program = 'Interval';
            } else if($activeProgram === self::UID_PROGRAM_DELAYEDSHUTOFF) {
                $program = 'Delayed shut off';
            }
            $this->SetValue("GreaseFilterSaturation", $saturation);
            $this->SetValue("Program", $activeProgram);
            $this->SetValue("Lighting", $lighting);
            $this->SetValue("VentingLevel", $ventingLevel);
            $this->SetValue("Power", $powerState === self::VALUE_POWERSTATE_ON ? true : false);

            if($powerState !== self::VALUE_POWERSTATE_ON) {
                $state = 'Off';
            } else {
                if($operationState === self::VALUE_OPERATIONSTATE_RUN) {
                    $details = $program;
                    // manual mode
                    if($activeProgram === self::UID_PROGRAM_MANUAL) {
                        $details = 'Level ' . $ventingLevel;
                    // interval or fan run on
                    } else if($activeProgram === self::UID_PROGRAM_INTERVAL || $activeProgram === self::UID_PROGRAM_DELAYEDSHUTOFF) {
                        $details .= ' (' . $this->HCLFormatDuration($remainingProgramTime) . ' remaining)';
                    }
                    $state = $details;
                } else {
                    $state = 'Inactive';
                }
            }
            $this->SetValue("State", $state);
        }
    }

    public function RequestAction($Ident, $Value)
    {
        if($Ident === 'Power') {
            $this->HCLSendRequest(self::UID_SETTING_POWERSTATE, $Value === false ? self::VALUE_POWERSTATE_OFF : self::VALUE_POWERSTATE_ON);
        } else if($Ident === 'Lighting') {
            $this->HCLSendRequest(self::UID_SETTING_LIGHTING, $Value === true ? true : false);
        } else if($Ident === 'Program') {
            if(!in_array($Value, self::UID_PROGRAMS)) return;
            if($Value === 0) {
                $this->HCLSendRequest(self::UID_SETTING_POWERSTATE, self::VALUE_POWERSTATE_OFF);
            } else {
                $this->HCLStartProgram($Value);
            }
        } else if($Ident === 'VentingLevel') {
            if($Value <= 0 || $Value >= 4) {
                $this->HCLSendRequest(self::UID_SETTING_POWERSTATE, self::VALUE_POWERSTATE_OFF);
            } else {
                $this->HCLStartProgram(self::UID_PROGRAM_MANUAL, [
                    ["uid" => self::UID_OPTION_VENTINGLEVEL, "value" => $Value]
                ]);
            }
        }
    }

    public function ResetGreaseFilter() {
        $this->HCLSendRequest(self::UID_SETTING_GREASEFILTERRESET, true);
    }
}