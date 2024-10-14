<?php

require_once(__DIR__ . '/../libs/ModuleUtilities.php');
require_once(__DIR__ . '/../libs/HCLDevice.php');

class HomeConnectLocalWasher extends HCLDevice
{
    use ModuleUtilities;

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        // properties
        $this->RegisterPropertyString('Topic', 'homeconnect/washer');

        // profiles
        $this->RegisterProfileBooleanEx('HomeConnectLocalWasher.Power', 'Power', '', '', [
            [false, 'Off',  '', -1],
            [true, 'On',  '', -1]
        ]);

        // variables
        $this->RegisterVariableBoolean("Connected", "Connected", "", 0);
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
            $payload = json_decode($Buffer->Payload);
            $state = $this->HCLUpdateState($payload);

            $powerState = $this->HCLGet($state, self::UID_SETTING_POWERSTATE, self::VALUE_POWERSTATE_STANDBY);
            $operationState = $this->HCLGet($state, self::UID_STATUS_OPERATIONSTATE, self::VALUE_OPERATIONSTATE_INACTIVE);
            $activeProgram = $this->HCLGet($state, self::UID_ACTIVEPROGRAM, 0);

            $doorState = $this->HCLGet($state, self::UID_STATUS_DOORSTATE, self::VALUE_DOORSTATE_CLOSED);
            
            $remainingProgramTime = $this->HCLGet($state, self::UID_OPTION_REMAININGPROGRAMTIME, 0);
            
            $estimatedTotalProgramTime = $this->HCLGet($state, self::UID_OPTION_ESTIMATEDTOTALPROGRAMTIME, 0);
            $finishInRelative = $this->HCLGet($state, self::UID_OPTION_FINISHINRELATIVE, 0);
            
            $powerStateBool = $powerState === self::VALUE_POWERSTATE_ON ? true : false;
            $this->SetValue("Power", $powerStateBool);
            /*
            // @TODO: figure out how to send commands
            if($powerStateBool) {
                $this->EnableAction("Power");
            } else {
                $this->DisableAction("Power");
            }
                */

            if($doorState !== self::VALUE_DOORSTATE_CLOSED) {
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