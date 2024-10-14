<?php

require_once(__DIR__ . '/../libs/ModuleUtilities.php');
require_once(__DIR__ . '/../libs/HCLUtilities.php');

class HomeConnectLocalOven extends IPSModule
{
    use ModuleUtilities;
    use HCLUtilities;

    // generic
    const UID_ACTIVEPROGRAM = 256;
    
    const UID_SETTING_POWERSTATE = 539;
    
    const UID_STATUS_OPERATIONSTATE = 552;
    const UID_STATUS_DOORSTATE = 527;
    
    const UID_OPTION_REMAININGPROGRAMTIME = 544;
    const UID_OPTION_ELAPSEDPROGRAMTIME = 528;
    const UID_OPTION_DURATION = 548;
    
    const VALUE_DOORSTATE_OPEN = 0;
    const VALUE_DOORSTATE_CLOSED = 1;
    
    const VALUE_POWERSTATE_OFF = 1;
    const VALUE_POWERSTATE_ON = 2;

    const VALUE_OPERATIONSTATE_INACTIVE         = 0;
    const VALUE_OPERATIONSTATE_READY            = 1;
    const VALUE_OPERATIONSTATE_DELAYEDSTART     = 2;
    const VALUE_OPERATIONSTATE_RUN              = 3;
    const VALUE_OPERATIONSTATE_PAUSE            = 4;
    const VALUE_OPERATIONSTATE_ACTIONREQUIRED   = 5;
    const VALUE_OPERATIONSTATE_FINISHED         = 6;
    const VALUE_OPERATIONSTATE_ERROR            = 7;
    const VALUE_OPERATIONSTATE_ABORTING         = 8;

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

    protected function OperationStateToString($state) {
        if($state === self::VALUE_OPERATIONSTATE_INACTIVE      ) return "Inactive";
        if($state === self::VALUE_OPERATIONSTATE_READY         ) return "Ready";
        if($state === self::VALUE_OPERATIONSTATE_DELAYEDSTART  ) return "Delayed Start";
        if($state === self::VALUE_OPERATIONSTATE_RUN           ) return "Run";
        if($state === self::VALUE_OPERATIONSTATE_PAUSE         ) return "Pause";
        if($state === self::VALUE_OPERATIONSTATE_ACTIONREQUIRED) return "Action required";
        if($state === self::VALUE_OPERATIONSTATE_FINISHED      ) return "Finished";
        if($state === self::VALUE_OPERATIONSTATE_ERROR         ) return "Error";
        if($state === self::VALUE_OPERATIONSTATE_ABORTING      ) return "Aborting";
    }

    // oven specific
    const UID_STATUS_CURRENTCAVITYTEMPERATURE = 4096;
    
    const UID_OPTION_SETPOINTTEMPERATURE = 5120;

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        // properties
        $this->RegisterPropertyString('Topic', 'homeconnect/oven');

        // variables
        $this->RegisterVariableBoolean("Connected", "Connected", "", 0);
        $this->RegisterVariableBoolean("Power", "Power", "~Switch", 1);
        $this->RegisterVariableString("State", "State", "", 2);
        $this->RegisterVariableFloat("CurrentCavityTemperature", "Temperature", "~Temperature", 3);

        $this->EnableAction("Power");
    
        // buffers
        $this->MUSetBuffer('DaemonConnected', false);
        $this->MUSetBuffer('DeviceConnected', false);
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $topic = $this->ReadPropertyString('Topic');
        $filter = implode('/', array_slice(explode('/', $topic), 0, -1)) . '/LWT|' . $topic . '/.*';
        $this->SendDebug('Filter', $filter, 0);
        $this->SetReceiveDataFilter('.*(' . $filter . ').*');

        $this->RequestUpdate();
    }

    public function ReceiveData($JSONString)
    {
        $this->SendDebug('JSON', $JSONString, 0);
        if (empty($this->ReadPropertyString('Topic'))) return;

        $data = json_decode($JSONString);

        $Buffer = $data;

        if (fnmatch('*/LWT', $Buffer->Topic)) {
            $connected = $Buffer->Payload === 'online' ? true : false;
            if($Buffer->Topic === $this->ReadPropertyString('Topic') . '/LWT') {
                $this->MUSetBuffer('DeviceConnected', $connected);
                $connected = $connected && $this->MUGetBuffer('DaemonConnected');
            } else {
                $this->MUSetBuffer('DaemonConnected', $connected);
                $connected = $connected && $this->MUGetBuffer('DeviceConnected');
            }
            $this->SetValue("Connected", $connected);
        } else {
            $payload = json_decode($Buffer->Payload);
            $state = $this->HCLUpdateState($payload);
            
            $powerState = $this->HCLGet($state, self::UID_SETTING_POWERSTATE, self::VALUE_POWERSTATE_STANDBY);
            $operationState = $this->HCLGet($state, self::UID_STATUS_OPERATIONSTATE, self::VALUE_OPERATIONSTATE_INACTIVE);
            $activeProgram = $this->HCLGet($state, self::UID_ACTIVEPROGRAM, 0);

            $doorState = $this->HCLGet($state, self::UID_STATUS_DOORSTATE, self::VALUE_DOORSTATE_CLOSED);
            
            $remainingProgramTime = $this->HCLGet($state, self::UID_OPTION_REMAININGPROGRAMTIME, 0);
            $elapsedProgramTime = $this->HCLGet($state, self::UID_OPTION_ELAPSEDPROGRAMTIME, 0);
            $duration = $this->HCLGet($state, self::UID_OPTION_DURATION, 0);

            $currentCavityTemperature = $this->HCLGet($state, self::UID_STATUS_CURRENTCAVITYTEMPERATURE, 0);

            $this->SetValue("CurrentCavityTemperature", $currentCavityTemperature);
            $this->SetValue("Power", $powerState === self::VALUE_POWERSTATE_ON ? true : false);

            if($doorState !== self::VALUE_DOORSTATE_CLOSED) {
                $state = 'Door open';
            } else if($powerState !== self::VALUE_POWERSTATE_ON) {
                $state = 'Off';
            } else {
                if($operationState === self::VALUE_OPERATIONSTATE_DELAYEDSTART) {
                    $state = 'Start in ' . $this->FormatDuration($remainingProgramTime);
                } else if($operationState === self::VALUE_OPERATIONSTATE_RUN) {
                    if($duration) {
                        $state = $this->FormatDuration($remainingProgramTime) . ' remaining';
                    } else if(!in_array($activeProgram, self::VALUE_PROGRAMS_MICROWAVE) &&
                         $currentCavityTemperature < $setpointTemperature) {
                        $state = 'Preheating (' . floor($currentCavityTemperature) . '/' . $setpointTemperature . ')';
                    } else if($elapsedProgramTime) {
                        $state = $this->FormatDuration($elapsedProgramTime) . ' elapsed';
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
            $this->SendRequest(self::UID_SETTING_POWERSTATE, $Value === false ? self::VALUE_POWERSTATE_STANDBY : self::VALUE_POWERSTATE_ON);
        }
    }
}