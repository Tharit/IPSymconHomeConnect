<?php

require_once(__DIR__ . '/../libs/ModuleUtilities.php');
require_once(__DIR__ . '/../libs/HCLDevice.php');

class HomeConnectLocalOven extends HCLDevice
{
    use ModuleUtilities;

    // oven specific
    const VALUE_PROGRAM_MICROWAVE_90W   = 8705;
    const VALUE_PROGRAM_MICROWAVE_180W  = 8706;
    const VALUE_PROGRAM_MICROWAVE_360W  = 8707;
    const VALUE_PROGRAM_MICROWAVE_600W  = 8708;
    const VALUE_PROGRAM_MICROWAVE_MAX   = 8709;

    const VALUE_PROGRAMS_MICROWAVE = [
        self::VALUE_PROGRAM_MICROWAVE_90W ,
        self::VALUE_PROGRAM_MICROWAVE_180W,
        self::VALUE_PROGRAM_MICROWAVE_360W,
        self::VALUE_PROGRAM_MICROWAVE_600W,
        self::VALUE_PROGRAM_MICROWAVE_MAX 
    ];

    const UID_STATUS_CURRENTCAVITYTEMPERATURE = 4096;
    const UID_STATUS_CURRENTMEATPROBETEMPERATURE = 4097;
    const UID_STATUS_MEATPROBEPLUGGED = 4100;
    const UID_STATUS_MEATPROBETEMPERATURE = 5121;
    
    const UID_OPTION_SETPOINTTEMPERATURE = 5120;

    const EVENTS = [
        [
                "uid" => 567,
                "name" => "BSH.Common.Event.AlarmClockElapsed",
                "desc" => "BSH.Common.Event.AlarmClockElapsed",
                "level" => "hint"
        ],
        [
                "uid" => 559,
                "name" => "BSH.Common.Event.CustomerServiceRequest",
                "desc" => "BSH.Common.Event.CustomerServiceRequest",
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
                "uid" => 4625,
                "name" => "Cooking.Oven.Event.ApplianceModuleError",
                "desc" => "Cooking.Oven.Event.ApplianceModuleError",
                "level" => "alert"
        ],
        [
                "uid" => 4615,
                "name" => "Cooking.Oven.Event.BakingSensorProgramAborted",
                "desc" => "Cooking.Oven.Event.BakingSensorProgramAborted",
                "level" => "warning"
        ],
        [
                "uid" => 4622,
                "name" => "Cooking.Oven.Event.CavityHeatup",
                "desc" => "Cooking.Oven.Event.CavityHeatup",
                "level" => "hint"
        ],
        [
                "uid" => 4617,
                "name" => "Cooking.Oven.Event.CavityTemperatureTooHigh",
                "desc" => "Cooking.Oven.Event.CavityTemperatureTooHigh",
                "level" => "warning"
        ],
        [
                "uid" => 4609,
                "name" => "Cooking.Oven.Event.CloseFrontPanel",
                "desc" => "Cooking.Oven.Event.CloseFrontPanel",
                "level" => "warning"
        ],
        [
                "uid" => 4620,
                "name" => "Cooking.Oven.Event.DescalingRequest",
                "desc" => "Cooking.Oven.Event.DescalingRequest",
                "level" => "hint"
        ],
        [
                "uid" => 4626,
                "name" => "Cooking.Oven.Event.EmptyWaterContainer",
                "desc" => "Cooking.Oven.Event.EmptyWaterContainer",
                "level" => "warning"
        ],
        [
                "uid" => 4614,
                "name" => "Cooking.Oven.Event.ForceDraining",
                "desc" => "Cooking.Oven.Event.ForceDraining",
                "level" => "warning"
        ],
        [
                "uid" => 4613,
                "name" => "Cooking.Oven.Event.ForceFlushing",
                "desc" => "Cooking.Oven.Event.ForceFlushing",
                "level" => "warning"
        ],
        [
                "uid" => 4608,
                "name" => "Cooking.Oven.Event.InsertWaterContainer",
                "desc" => "Cooking.Oven.Event.InsertWaterContainer",
                "level" => "warning"
        ],
        [
                "uid" => 4624,
                "name" => "Cooking.Oven.Event.MeatprobeIncompatibleOperation",
                "desc" => "Cooking.Oven.Event.MeatprobeIncompatibleOperation",
                "level" => "warning"
        ],
        [
                "uid" => 4623,
                "name" => "Cooking.Oven.Event.OperatingTimeLimitReached",
                "desc" => "Cooking.Oven.Event.OperatingTimeLimitReached",
                "level" => "warning"
        ],
        [
                "uid" => 4616,
                "name" => "Cooking.Oven.Event.OvenLockWhileCoolingDown",
                "desc" => "Cooking.Oven.Event.OvenLockWhileCoolingDown",
                "level" => "warning"
        ],
        [
                "uid" => 4612,
                "name" => "Cooking.Oven.Event.PreheatFinished",
                "desc" => "Cooking.Oven.Event.PreheatFinished",
                "level" => "hint"
        ],
        [
                "uid" => 4618,
                "name" => "Cooking.Oven.Event.SubsequentCookingRequest",
                "desc" => "Cooking.Oven.Event.SubsequentCookingRequest",
                "level" => "hint"
        ],
        [
                "uid" => 4619,
                "name" => "Cooking.Oven.Event.UserInteractionRequired",
                "desc" => "Cooking.Oven.Event.UserInteractionRequired",
                "level" => "hint"
        ],
        [
                "uid" => 4611,
                "name" => "Cooking.Oven.Event.WaterContainerEmpty",
                "desc" => "Cooking.Oven.Event.WaterContainerEmpty",
                "level" => "warning"
        ],
        [
                "uid" => 4610,
                "name" => "Cooking.Oven.Event.WaterContainerNearlyEmpty",
                "desc" => "Cooking.Oven.Event.WaterContainerNearlyEmpty",
                "level" => "hint"
        ]
    ];

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        // properties
        $this->RegisterPropertyString('Topic', 'homeconnect/oven');
        $this->RegisterPropertyInteger('script', '0');

        // variables
        $this->RegisterVariableBoolean("Connected", "Connected", "", 0);
        $this->RegisterVariableBoolean("Power", "Power", "~Switch", 1);
        $this->RegisterVariableString("State", "State", "", 2);
        $this->RegisterVariableFloat("CurrentCavityTemperature", "Temperature", "~Temperature", 3);

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
            $elapsedProgramTime = $this->HCLGet($state, self::UID_OPTION_ELAPSEDPROGRAMTIME, 0);
            $duration = $this->HCLGet($state, self::UID_OPTION_DURATION, 0);
            $startInRelative = $this->HCLGet($state, self::UID_OPTION_STARTINRELATIVE, 0);

            $currentCavityTemperature = $this->HCLGet($state, self::UID_STATUS_CURRENTCAVITYTEMPERATURE, 0);
            $setpointTemperature = $this->HCLGet($state, self::UID_OPTION_SETPOINTTEMPERATURE, 0);

            $currentMeatprobeTemperature = $this->HCLGet($state, self::UID_STATUS_CURRENTMEATPROBETEMPERATURE, 0);
            $meatprobeTemperature = $this->HCLGet($state, self::UID_STATUS_MEATPROBETEMPERATURE, 0);
            $meatprobePlugged = $this->HCLGet($state, self::UID_STATUS_MEATPROBEPLUGGED, false);

            $this->SetValue("CurrentCavityTemperature", $currentCavityTemperature);
            $this->SetValue("Power", $powerState === self::VALUE_POWERSTATE_ON ? true : false);

            if($doorState !== self::VALUE_DOORSTATE_CLOSED) {
                $state = 'Door open';
            } else if($powerState !== self::VALUE_POWERSTATE_ON) {
                $state = 'Off';
            } else {
                $showRemaining = $this->HCLGetTimestamp(self::UID_OPTION_REMAININGPROGRAMTIME) >= $this->HCLGetTimestamp(self::UID_OPTION_ELAPSEDPROGRAMTIME);
                if($operationState === self::VALUE_OPERATIONSTATE_DELAYEDSTART) {
                    $state = 'Start in ' . $this->HCLFormatDuration($startInRelative);
                } else if($operationState === self::VALUE_OPERATIONSTATE_RUN) {
                    if($meatprobePlugged) {
                        $state = 'Running (' . floor($currentMeatprobeTemperature) . '/' . $meatprobeTemperature . ')';
                    } else if(!in_array($activeProgram, self::VALUE_PROGRAMS_MICROWAVE) &&
                        $currentCavityTemperature < $setpointTemperature) {
                        $state = 'Preheating (' . floor($currentCavityTemperature) . '/' . $setpointTemperature . ')';
                    } else if($showRemaining) { // @TODO: this is apparently not cleared when starting another program.. how to fix?
                        $state = $this->HCLFormatDuration($remainingProgramTime) . ' remaining';
                    } else if($elapsedProgramTime) {
                        $state = $this->HCLFormatDuration($elapsedProgramTime) . ' elapsed';
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
            $this->HCLSendRequest(self::UID_SETTING_POWERSTATE, $Value === false ? self::VALUE_POWERSTATE_STANDBY : self::VALUE_POWERSTATE_ON);
        }
    }
}