<?php

require_once(__DIR__ . '/../libs/ModuleUtilities.php');
require_once(__DIR__ . '/../libs/HCLDevice.php');

class HomeConnectLocalHob extends HCLDevice
{
    use ModuleUtilities;

    const EVENTS = [
        [
                "uid" => 567,
                "name" => "BSH.Common.Event.AlarmClockElapsed",
                "desc" => "BSH.Common.Event.AlarmClockElapsed",
                "level" => "hint"
        ],
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
                "uid" => 53248,
                "name" => "Cooking.Common.Event.ApplianceModuleError",
                "desc" => "Cooking.Common.Event.ApplianceModuleError",
                "level" => "alert"
        ],
        [
                "uid" => 53249,
                "name" => "Cooking.Common.Event.ApplianceOverheated",
                "desc" => "Cooking.Common.Event.ApplianceOverheated",
                "level" => "alert"
        ],
        [
                "uid" => 53252,
                "name" => "Cooking.Common.Event.ConfirmActionAtAppliance",
                "desc" => "Cooking.Common.Event.ConfirmActionAtAppliance",
                "level" => "alert"
        ],
        [
                "uid" => 4610,
                "name" => "Cooking.Hob.Event.CookingSensorBatteryEmpty",
                "desc" => "Cooking.Hob.Event.CookingSensorBatteryEmpty",
                "level" => "hint"
        ],
        [
                "uid" => 4608,
                "name" => "Cooking.Hob.Event.CookingSensorDetected",
                "desc" => "Cooking.Hob.Event.CookingSensorDetected",
                "level" => "hint"
        ],
        [
                "uid" => 4611,
                "name" => "Cooking.Hob.Event.CookingSensorPairingSuccessful",
                "desc" => "Cooking.Hob.Event.CookingSensorPairingSuccessful",
                "level" => "hint"
        ],
        [
                "uid" => 4609,
                "name" => "Cooking.Hob.Event.CookingSensorRequired",
                "desc" => "Cooking.Hob.Event.CookingSensorRequired",
                "level" => "hint"
        ]
    ];
 
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        // properties
        $this->RegisterPropertyString('Topic', 'homeconnect/hob');
        $this->RegisterPropertyInteger('script', '0');

        // variables
        $this->RegisterVariableInteger("Connected", "Connected", "", 0);
        $this->RegisterVariableBoolean("Power", "Power", "~Switch", 1);
        $this->RegisterVariableString("State", "State", "", 2);

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

            $powerState = $this->HCLGet($state, self::UID_SETTING_POWERSTATE, self::VALUE_POWERSTATE_STANDBY);
            $operationState = $this->HCLGet($state, self::UID_STATUS_OPERATIONSTATE, self::VALUE_OPERATIONSTATE_INACTIVE);
            
            $this->SetValue("Power", $powerState === self::VALUE_POWERSTATE_ON ? true : false);

            if($powerState !== self::VALUE_POWERSTATE_ON) {
                $state = 'Off';
            } else {
                $state = $this->HCLOperationStateToString($operationState);
            }
            $this->SetValue("State", $state);
        }
    }

    public function RequestAction($Ident, $Value)
    {
        if($Ident === 'Power') {
            $this->HCLSendRequest(self::UID_SETTING_POWERSTATE, $Value === false ? self::VALUE_POWERSTATE_OFF : self::VALUE_POWERSTATE_ON);
        }
    }
}