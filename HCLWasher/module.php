<?php

require_once(__DIR__ . '/../libs/ModuleUtilities.php');
require_once(__DIR__ . '/../libs/HCLDevice.php');

class HomeConnectLocalWasher extends HCLDevice
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
                "uid" => 559,
                "name" => "BSH.Common.Event.CustomerServiceRequest",
                "desc" => "BSH.Common.Event.CustomerServiceRequest",
                "level" => "hint"
        ],
        [
                "uid" => 598,
                "name" => "BSH.Common.Event.HomeConnectApplianceDataMissing",
                "desc" => "BSH.Common.Event.HomeConnectApplianceDataMissing",
                "level" => "hint"
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
                "uid" => 21,
                "name" => "BSH.Common.Event.SoftwareUpdateAvailable",
                "desc" => "BSH.Common.Event.SoftwareUpdateAvailable",
                "level" => "hint"
        ],
        [
                "uid" => 18954,
                "name" => "LaundryCare.Common.Event.DelayedShutdown",
                "desc" => "LaundryCare.Common.Event.DelayedShutdown",
                "level" => "hint"
        ],
        [
                "uid" => 18955,
                "name" => "LaundryCare.Common.Event.DelayedShutdownCanceled",
                "desc" => "LaundryCare.Common.Event.DelayedShutdownCanceled",
                "level" => "info"
        ],
        [
                "uid" => 18953,
                "name" => "LaundryCare.Common.Event.DoorLock.WaterLevelTooHigh",
                "desc" => "LaundryCare.Common.Event.DoorLock.WaterLevelTooHigh",
                "level" => "warning"
        ],
        [
                "uid" => 18950,
                "name" => "LaundryCare.Common.Event.DoorNotLockable",
                "desc" => "LaundryCare.Common.Event.DoorNotLockable",
                "level" => "alert"
        ],
        [
                "uid" => 18949,
                "name" => "LaundryCare.Common.Event.DoorNotUnlockable",
                "desc" => "LaundryCare.Common.Event.DoorNotUnlockable",
                "level" => "hint"
        ],
        [
                "uid" => 18951,
                "name" => "LaundryCare.Common.Event.DoorOpen",
                "desc" => "LaundryCare.Common.Event.DoorOpen",
                "level" => "alert"
        ],
        [
                "uid" => 18946,
                "name" => "LaundryCare.Common.Event.FatalErrorOccured",
                "desc" => "LaundryCare.Common.Event.FatalErrorOccured",
                "level" => "critical"
        ],
        [
                "uid" => 18952,
                "name" => "LaundryCare.Common.Event.FoamDetection",
                "desc" => "LaundryCare.Common.Event.FoamDetection",
                "level" => "hint"
        ],
        [
                "uid" => 18956,
                "name" => "LaundryCare.Common.Event.SupplyPower.SupplyVoltageTooLow",
                "desc" => "LaundryCare.Common.Event.SupplyPower.SupplyVoltageTooLow",
                "level" => "alert"
        ],
        [
                "uid" => 16399,
                "name" => "LaundryCare.Washer.Event.Circulation.Pump.ErrorLockedRotor",
                "desc" => "LaundryCare.Washer.Event.Circulation.Pump.ErrorLockedRotor",
                "level" => "hint"
        ],
        [
                "uid" => 16398,
                "name" => "LaundryCare.Washer.Event.Circulation.Pump.ErrorMaxTorque",
                "desc" => "LaundryCare.Washer.Event.Circulation.Pump.ErrorMaxTorque",
                "level" => "hint"
        ],
        [
                "uid" => 16389,
                "name" => "LaundryCare.Washer.Event.DrumCleanReminder",
                "desc" => "LaundryCare.Washer.Event.DrumCleanReminder",
                "level" => "hint"
        ],
        [
                "uid" => 16394,
                "name" => "LaundryCare.Washer.Event.IDos.IDosOpenTray",
                "desc" => "LaundryCare.Washer.Event.IDos.IDosOpenTray",
                "level" => "warning"
        ],
        [
                "uid" => 16385,
                "name" => "LaundryCare.Washer.Event.IDos1FillLevelPoor",
                "desc" => "LaundryCare.Washer.Event.IDos1FillLevelPoor",
                "level" => "warning"
        ],
        [
                "uid" => 16386,
                "name" => "LaundryCare.Washer.Event.IDos2FillLevelPoor",
                "desc" => "LaundryCare.Washer.Event.IDos2FillLevelPoor",
                "level" => "warning"
        ],
        [
                "uid" => 16390,
                "name" => "LaundryCare.Washer.Event.IDosUnitDefect",
                "desc" => "LaundryCare.Washer.Event.IDosUnitDefect",
                "level" => "alert"
        ],
        [
                "uid" => 16391,
                "name" => "LaundryCare.Washer.Event.PumpError",
                "desc" => "LaundryCare.Washer.Event.PumpError",
                "level" => "critical"
        ],
        [
                "uid" => 16387,
                "name" => "LaundryCare.Washer.Event.ReleaseRinseHoldPending",
                "desc" => "LaundryCare.Washer.Event.ReleaseRinseHoldPending",
                "level" => "hint"
        ],
        [
                "uid" => 16393,
                "name" => "LaundryCare.Washer.Event.Spin.SpinAbort",
                "desc" => "LaundryCare.Washer.Event.Spin.SpinAbort",
                "level" => "hint"
        ]
    ];

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        // properties
        $this->RegisterPropertyString('Topic', 'homeconnect/washer');
        $this->RegisterPropertyInteger('script', '0');
        
        // profiles
        $this->RegisterProfileBooleanEx('HomeConnectLocalWasher.Power', 'Power', '', '', [
            [false, 'Off',  '', -1],
            [true, 'On',  '', -1]
        ]);

        // variables
        $this->RegisterVariableInteger("Connected", "Connected", "", 0);
        $this->RegisterVariableBoolean("Power", "Power", "HomeConnectLocalWasher.Power", 1);
        $this->RegisterVariableString("State", "State", "", 2);

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
            $connected = $this->HCLUpdateConnected($Buffer->Topic, $Buffer->Payload);
            if(!$connected) {
                $this->SetValue("Power", false);
                $this->SetValue("State", 'Off');
            }
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
            
            $estimatedTotalProgramTime = $this->HCLGet($state, self::UID_OPTION_ESTIMATEDTOTALPROGRAMTIME, 0);
            $finishInRelative = $this->HCLGet($state, self::UID_OPTION_FINISHINRELATIVE, 0);
            
            $powerStateBool = $powerState === self::VALUE_POWERSTATE_ON && $this->GetValue('Connected') === 2 ? true : false;
            $this->SetValue("Power", $powerStateBool);
            /*
            // @TODO: figure out how to send commands
            if($powerStateBool) {
                $this->EnableAction("Power");
            } else {
                $this->DisableAction("Power");
            }
                */

            // device sometimes fails to report powerstate correctly before turning off (mainsoff)
            if($this->GetValue('Connected') == 1) {
                $state = 'Off';
            } else if($doorState !== self::VALUE_DOORSTATE_CLOSED && $doorState !== self::VALUE_DOORSTATE_LOCKED) {
                $state = 'Door ' . $this->HCLDoorStateToString($doorState);
            } else if($powerState !== self::VALUE_POWERSTATE_ON) {
                $state = 'Off';
            } else {
                if($operationState === self::VALUE_OPERATIONSTATE_DELAYEDSTART) {
                    $state = 'Start in ' . $this->HCLFormatDuration($finishInRelative - $estimatedTotalProgramTime);
                } else if($operationState === self::VALUE_OPERATIONSTATE_RUN) {
                    if($remainingProgramTime) {
                        $state = $this->HCLFormatDuration($remainingProgramTime) . ' remaining';
                    } else {
                        //@TODO if event finished is set, show finished here
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
        // @TODO: figure out how to send commands
        /*
        if($Ident === 'Power') {
            $this->HCLSendRequest(self::COMMAND_MAINSPOWEROFF, true);
        }
        */
    }
}