<?php

require_once(__DIR__ . '/../libs/ModuleUtilities.php');
require_once(__DIR__ . '/../libs/HCLUtilities.php');

class HomeConnectLocalOven extends IPSModule
{
    use ModuleUtilities;
    use HCLUtilities;

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

            if(isset($payload->PowerState)) {

                $this->SetValue("CurrentCavityTemperature", $payload->CurrentCavityTemperature);
                $this->SetValue("Power", $payload->PowerState === 'On' ? true : false);

                if($payload->DoorState !== 'Closed') {
                    $state = 'Door open';
                } else if($payload->PowerState !== 'On') {
                    $state = $payload->PowerState;
                    // remap 'Standby' to Off
                    if($state === 'Standby') $state = 'Off';
                } else {
                    if($payload->OperationState === 'DelayedStart') {
                        $state = 'Start in ' . $this->FormatDuration($payload->RemainingProgramTime);
                    } else if($payload->OperationState === 'Run') {
                        // @TODO: values are initially not present, check for their existence
                        if($payload->Duration) {
                            $state = $this->FormatDuration($payload->RemainingProgramTime) . ' remaining';
                        } else if($payload->CurrentCavityTemperature < $payload->SetpointTemperature) {
                            $state = 'Preheating (' . floor($payload->CurrentCavityTemperature) . '/' . $payload->SetpointTemperature . ')';
                        } else if($payload->ElapsedProgramTime) {
                            $state = $this->FormatDuration($payload->ElapsedProgramTime) . ' elapsed';
                        } else {
                            $state = 'Running';
                        }
                    } else {
                        $state = $payload->OperationState;
                    }
                }
                $this->SetValue("State", $state);
            }
        }
    }

    public function RequestAction($Ident, $Value)
    {
        if($Ident === 'Power') {
            $this->SendRequest(539, $Value === false ? 3 : 2);
        }
    }
}