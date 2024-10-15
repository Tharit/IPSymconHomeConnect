<?php

require_once(__DIR__ . '/../libs/ModuleUtilities.php');
require_once(__DIR__ . '/../libs/HCLDevice.php');

class HomeConnectLocalDishwasher extends HCLDevice
{
    use ModuleUtilities;

    const EVENTS = [
        [
                "uid" => 525,
                "name" => "BSH.Common.Event.AquaStopOccured",
                "desc" => "BSH.Common.Event.AquaStopOccured",
                "level" => "critical"
        ],
        [
                "uid" => 46,
                "name" => "BSH.Common.Event.ConfirmPermanentRemoteStart",
                "desc" => "BSH.Common.Event.ConfirmPermanentRemoteStart",
                "level" => "hint"
        ],
        [
                "uid" => 577,
                "name" => "BSH.Common.Event.ConnectLocalWiFi",
                "desc" => "BSH.Common.Event.ConnectLocalWiFi",
                "level" => "warning"
        ],
        [
                "uid" => 543,
                "name" => "BSH.Common.Event.LowWaterPressure",
                "desc" => "BSH.Common.Event.LowWaterPressure",
                "level" => "alert"
        ],
        [
                "uid" => 545,
                "name" => "BSH.Common.Event.ProgramAborted",
                "desc" => "BSH.Common.Event.ProgramAborted",
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
                "uid" => 4609,
                "name" => "Dishcare.Dishwasher.Event.CheckFilterSystem",
                "desc" => "Dishcare.Dishwasher.Event.CheckFilterSystem",
                "level" => "alert"
        ],
        [
                "uid" => 4611,
                "name" => "Dishcare.Dishwasher.Event.DrainPumpBlocked",
                "desc" => "Dishcare.Dishwasher.Event.DrainPumpBlocked",
                "level" => "alert"
        ],
        [
                "uid" => 4610,
                "name" => "Dishcare.Dishwasher.Event.DrainingNotPossible",
                "desc" => "Dishcare.Dishwasher.Event.DrainingNotPossible",
                "level" => "alert"
        ],
        [
                "uid" => 4608,
                "name" => "Dishcare.Dishwasher.Event.InternalError",
                "desc" => "Dishcare.Dishwasher.Event.InternalError",
                "level" => "alert"
        ],
        [
                "uid" => 4613,
                "name" => "Dishcare.Dishwasher.Event.LowVoltage",
                "desc" => "Dishcare.Dishwasher.Event.LowVoltage",
                "level" => "alert"
        ],
        [
                "uid" => 4655,
                "name" => "Dishcare.Dishwasher.Event.MachineCareAndFilterCleaningReminder",
                "desc" => "Dishcare.Dishwasher.Event.MachineCareAndFilterCleaningReminder",
                "level" => "hint"
        ],
        [
                "uid" => 4628,
                "name" => "Dishcare.Dishwasher.Event.MachineCareReminder",
                "desc" => "Dishcare.Dishwasher.Event.MachineCareReminder",
                "level" => "hint"
        ],
        [
                "uid" => 4625,
                "name" => "Dishcare.Dishwasher.Event.RinseAidLack",
                "desc" => "Dishcare.Dishwasher.Event.RinseAidLack",
                "level" => "hint"
        ],
        [
                "uid" => 4627,
                "name" => "Dishcare.Dishwasher.Event.RinseAidNearlyEmpty",
                "desc" => "Dishcare.Dishwasher.Event.RinseAidNearlyEmpty",
                "level" => "hint"
        ],
        [
                "uid" => 4624,
                "name" => "Dishcare.Dishwasher.Event.SaltLack",
                "desc" => "Dishcare.Dishwasher.Event.SaltLack",
                "level" => "hint"
        ],
        [
                "uid" => 4626,
                "name" => "Dishcare.Dishwasher.Event.SaltNearlyEmpty",
                "desc" => "Dishcare.Dishwasher.Event.SaltNearlyEmpty",
                "level" => "hint"
        ],
        [
                "uid" => 4660,
                "name" => "Dishcare.Dishwasher.Event.SmartFilterCleaningReminder",
                "desc" => "Dishcare.Dishwasher.Event.SmartFilterCleaningReminder",
                "level" => "hint"
        ],
        [
                "uid" => 4612,
                "name" => "Dishcare.Dishwasher.Event.WaterheaterCalcified",
                "desc" => "Dishcare.Dishwasher.Event.WaterheaterCalcified",
                "level" => "alert"
        ]
    ];

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        // properties
        $this->RegisterPropertyString('Topic', 'homeconnect/dishwasher');
        $this->RegisterPropertyInteger('script', '0');

        // variables
        $this->RegisterVariableBoolean("Connected", "Connected", "", 0);
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
            $activeProgram = $this->HCLGet($state, self::UID_ACTIVEPROGRAM, 0);

            $doorState = $this->HCLGet($state, self::UID_STATUS_DOORSTATE, self::VALUE_DOORSTATE_CLOSED);
            
            $remainingProgramTime = $this->HCLGet($state, self::UID_OPTION_REMAININGPROGRAMTIME, 0);
            $startInRelative = $this->HCLGet($state, self::UID_OPTION_STARTINRELATIVE, 0);

            $this->SetValue("Power", $powerState === self::VALUE_POWERSTATE_ON ? true : false);

            if($doorState !== self::VALUE_DOORSTATE_CLOSED) {
                $state = $this->HCLDoorStateToString('Door open');
            } else if($powerState !== self::VALUE_POWERSTATE_ON) {
                $state = 'Off';
            } else {
                if($operationState === self::VALUE_OPERATIONSTATE_DELAYEDSTART) {
                    $state = 'Start in ' . $this->HCLFormatDuration($startInRelative);
                } else if($operationState === self::VALUE_OPERATIONSTATE_RUN) {
                    if($remainingProgramTime) {
                        $state = $this->HCLFormatDuration($remainingProgramTime) . ' remaining';
                    } else {
                        $state = 'Running';
                    }
                } else {
                    $state = $this->HCLOperationStateToString($operationState);
                }
            }
            $this->SetValue("State", $state);
        }
    }

    public function RequestAction($Ident, $Value)
    {
        if($Ident === 'Power') {
            $this->HCLSendRequest(self::UID_SETTING_POWERSTATE, $Value === false ? self::VALUE_POWERSTATE_MAINSOFF : self::VALUE_POWERSTATE_ON);
        }
    }
}