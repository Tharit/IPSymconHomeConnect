<?php

require_once(__DIR__ . '/../libs/ModuleUtilities.php');
require_once(__DIR__ . '/../libs/HCLDevice.php');

class HomeConnectLocalDryer extends HCLDevice
{
    use ModuleUtilities;

    const EVENT_DRYINGPROCESSFINISHED = 17668;
    
    const EVENTS = [
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
            "uid" => 18956,
            "name" => "LaundryCare.Common.Event.SupplyPower.SupplyVoltageTooLow",
            "desc" => "LaundryCare.Common.Event.SupplyPower.SupplyVoltageTooLow",
            "level" => "alert"
        ],
        [
            "uid" => 17665,
            "name" => "LaundryCare.Dryer.Event.CondensateContainerFull",
            "desc" => "LaundryCare.Dryer.Event.CondensateContainerFull",
            "level" => "alert"
        ],
        [
            "uid" => 17672,
            "name" => "LaundryCare.Dryer.Event.CondensateTray.TrayOpen",
            "desc" => "LaundryCare.Dryer.Event.CondensateTray.TrayOpen",
            "level" => "warning"
        ],
        [
            "uid" => 17685,
            "name" => "LaundryCare.Dryer.Event.ConnectedDry.DontDry",
            "desc" => "LaundryCare.Dryer.Event.ConnectedDry.DontDry",
            "level" => "hint"
        ],
        [
            "uid" => 17680,
            "name" => "LaundryCare.Dryer.Event.ConnectedDry.DontDrySilk",
            "desc" => "LaundryCare.Dryer.Event.ConnectedDry.DontDrySilk",
            "level" => "hint"
        ],
        [
            "uid" => 17684,
            "name" => "LaundryCare.Dryer.Event.ConnectedDry.LaundryTooMoist",
            "desc" => "LaundryCare.Dryer.Event.ConnectedDry.LaundryTooMoist",
            "level" => "hint"
        ],
        [
            "uid" => 17682,
            "name" => "LaundryCare.Dryer.Event.ConnectedDry.NoDryingProgram",
            "desc" => "LaundryCare.Dryer.Event.ConnectedDry.NoDryingProgram",
            "level" => "hint"
        ],
        [
            "uid" => 17681,
            "name" => "LaundryCare.Dryer.Event.ConnectedDry.SingleDryLargeItems",
            "desc" => "LaundryCare.Dryer.Event.ConnectedDry.SingleDryLargeItems",
            "level" => "hint"
        ],
        [
            "uid" => 17686,
            "name" => "LaundryCare.Dryer.Event.ConnectedDry.UseBasket",
            "desc" => "LaundryCare.Dryer.Event.ConnectedDry.UseBasket",
            "level" => "hint"
        ],
        [
            "uid" => 17683,
            "name" => "LaundryCare.Dryer.Event.ConnectedDry.WasherTooLoaded",
            "desc" => "LaundryCare.Dryer.Event.ConnectedDry.WasherTooLoaded",
            "level" => "hint"
        ],
        [
            "uid" => 17669,
            "name" => "LaundryCare.Dryer.Event.CoolDownPhaseRunning",
            "desc" => "LaundryCare.Dryer.Event.CoolDownPhaseRunning",
            "level" => "hint"
        ],
        [
            "uid" => 17671,
            "name" => "LaundryCare.Dryer.Event.DryerSelfCleaning.CleanLintFilter",
            "desc" => "LaundryCare.Dryer.Event.DryerSelfCleaning.CleanLintFilter",
            "level" => "warning"
        ],
        [
            "uid" => 17670,
            "name" => "LaundryCare.Dryer.Event.DryerSelfCleaning.CleanSelfCleaningModule",
            "desc" => "LaundryCare.Dryer.Event.DryerSelfCleaning.CleanSelfCleaningModule",
            "level" => "warning"
        ],
        [
            "uid" => 17668,
            "name" => "LaundryCare.Dryer.Event.DryingProcessFinished",
            "desc" => "LaundryCare.Dryer.Event.DryingProcessFinished",
            "level" => "hint"
        ],
        [
            "uid" => 17687,
            "name" => "LaundryCare.Dryer.Event.EvaporatorFrozen",
            "desc" => "LaundryCare.Dryer.Event.EvaporatorFrozen",
            "level" => "warning"
        ],
        [
            "uid" => 17666,
            "name" => "LaundryCare.Dryer.Event.LintFilterFull",
            "desc" => "LaundryCare.Dryer.Event.LintFilterFull",
            "level" => "alert"
        ],
        [
            "uid" => 17677,
            "name" => "LaundryCare.Dryer.Event.Maintenance.DepthFillAgent",
            "desc" => "LaundryCare.Dryer.Event.Maintenance.DepthFillAgent",
            "level" => "hint"
        ],
        [
            "uid" => 17679,
            "name" => "LaundryCare.Dryer.Event.Maintenance.DepthFillWater",
            "desc" => "LaundryCare.Dryer.Event.Maintenance.DepthFillWater",
            "level" => "warning"
        ],
        [
            "uid" => 17676,
            "name" => "LaundryCare.Dryer.Event.Maintenance.DrainSet",
            "desc" => "LaundryCare.Dryer.Event.Maintenance.DrainSet",
            "level" => "hint"
        ],
        [
            "uid" => 17678,
            "name" => "LaundryCare.Dryer.Event.Maintenance.QuickFillWater",
            "desc" => "LaundryCare.Dryer.Event.Maintenance.QuickFillWater",
            "level" => "hint"
        ],
        [
            "uid" => 17673,
            "name" => "LaundryCare.Dryer.Event.Maintenance.Remind",
            "desc" => "LaundryCare.Dryer.Event.Maintenance.Remind",
            "level" => "warning"
        ],
        [
            "uid" => 17667,
            "name" => "LaundryCare.Dryer.Event.RefresherContainerEmpty",
            "desc" => "LaundryCare.Dryer.Event.RefresherContainerEmpty",
            "level" => "alert"
        ]
    ];

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        // properties
        $this->RegisterPropertyString('Topic', 'homeconnect/dryer');
        $this->RegisterPropertyInteger('script', '0');

        // profiles
        $this->RegisterProfileBooleanEx('HomeConnectLocalDryer.Power', 'Power', '', '', [
            [false, 'Off',  '', -1],
            [true, 'On',  '', -1]
        ]);

        // variables
        $this->RegisterVariableInteger("Connected", "Connected", "", 0);
        $this->RegisterVariableBoolean("Power", "Power", "HomeConnectLocalDryer.Power", 1);
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

    private function UpdateState($state) {
        $powerState = $this->HCLGet($state, self::UID_SETTING_POWERSTATE, self::VALUE_POWERSTATE_STANDBY);
        $operationState = $this->HCLGet($state, self::UID_STATUS_OPERATIONSTATE, self::VALUE_OPERATIONSTATE_INACTIVE);
        $activeProgram = $this->HCLGet($state, self::UID_ACTIVEPROGRAM, 0);

        $doorState = $this->HCLGet($state, self::UID_STATUS_DOORSTATE, self::VALUE_DOORSTATE_CLOSED);
        
        $estimatedTotalProgramTime = $this->HCLGet($state, self::UID_OPTION_ESTIMATEDTOTALPROGRAMTIME, 0);
        $remainingProgramTime = $this->HCLGet($state, self::UID_OPTION_REMAININGPROGRAMTIME, 0);
        $finishInRelative = $this->HCLGet($state, self::UID_OPTION_FINISHINRELATIVE, 0);
        
        $powerStateBool = $powerState === self::VALUE_POWERSTATE_ON && $this->GetValue('Connected') === 3 ? true : false;
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
                // if event LaundryCare.Dryer.Event.DryingProcessFinished is set, show finished here
                if($this->HCLGet($state, self::EVENT_DRYINGPROCESSFINISHED, 0) === 1) {
                    $state = 'Drying finished - Anti-crease';
                } else if($remainingProgramTime) {
                    $state = 'Running - ' . $this->HCLFormatDuration($remainingProgramTime) . ' remaining';
                } else {
                    $state = 'Running';
                }
            } else {
                $state = $this->HCLOperationStateToString($operationState);
            }
        }
        $this->SetValue("State", $state);
    }

    public function ReceiveData($JSONString)
    {
        $this->SendDebug('JSON', $JSONString, 0);
        if (empty($this->ReadPropertyString('Topic'))) return;

        $data = json_decode($JSONString);

        $Buffer = $data;

        if (fnmatch('*/LWT', $Buffer->Topic)) {
            $connected = $this->HCLUpdateConnected($Buffer->Topic, $Buffer->Payload);
            // workaround: device sometimes does not correctly report powerstate when turning off/on
            // we know:
            // - off = power off (mains off) = no connection
            // - no standby mode
            // => set powerstate based on connection
            if($connected !== 3) {
                $power = self::VALUE_POWERSTATE_MAINSOFF;
            } else {
                $power = self::VALUE_POWERSTATE_ON;
            }
            $state = $this->HCLUpdateState([
                self::UID_SETTING_POWERSTATE => $power
            ]);
            $this->UpdateState($state);
        } else {
            $payload = json_decode($Buffer->Payload, true);

            // handle events
            $this->HCLHandleEvents(self::EVENTS, $payload);

            $state = $this->HCLUpdateState($payload);
            $this->UpdateState($state);
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