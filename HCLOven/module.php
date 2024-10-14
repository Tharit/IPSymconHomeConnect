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
    
        $this->HCLInit();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

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
            $payload = json_decode($Buffer->Payload);
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

            $this->SetValue("CurrentCavityTemperature", $currentCavityTemperature);
            $this->SetValue("Power", $powerState === self::VALUE_POWERSTATE_ON ? true : false);

            if($doorState !== self::VALUE_DOORSTATE_CLOSED) {
                $state = 'Door open';
            } else if($powerState !== self::VALUE_POWERSTATE_ON) {
                $state = 'Off';
            } else {
                if($operationState === self::VALUE_OPERATIONSTATE_DELAYEDSTART) {
                    $state = 'Start in ' . $this->HCLFormatDuration($startInRelative);
                } else if($operationState === self::VALUE_OPERATIONSTATE_RUN) {
                    if($duration) {
                        $state = $this->HCLFormatDuration($remainingProgramTime) . ' remaining';
                    } else if(!in_array($activeProgram, self::VALUE_PROGRAMS_MICROWAVE) &&
                         $currentCavityTemperature < $setpointTemperature) {
                        $state = 'Preheating (' . floor($currentCavityTemperature) . '/' . $setpointTemperature . ')';
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