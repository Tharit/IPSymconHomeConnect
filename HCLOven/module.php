<?php

require_once(__DIR__ . '/../libs/ModuleUtilities.php');

class HomeConnectLocalOven extends IPSModule
{
    use ModuleUtilities;

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        // properties
        $this->RegisterPropertyString('Topic', 'homeconnect/oven');

        // variables
        $this->RegisterVariableBoolean("Connected", "Connected");
        $this->RegisterVariableString("State", "State");
        $this->RegisterVariableFloat("CurrentCavityTemperature", "Temperature", "~Temperature");

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
                
                if($payload->DoorState !== 'Closed') {
                    $state = 'Door open';
                } else if($payload->PowerState !== 'On') {
                    // Standby
                    $state = $payload->PowerState;
                } else {
                    if($payload->OperationState !== 'Inactive') {
                        /*if($payload->AlarmClock) {
                            $state = $payload->AlarmClock . "|" . $payload->AlarmClockElapsed;
                        } else if(!$payload->PreheatFinished) {
                            $state = "Preheating (" . $payload->CurrentCavityTemperature . '/' . $payload->CavityHeatup . ')';
                        } else
                        */
                        if($payload->RemainingProgramTime) {
                            $state = 'Running (' . $payload->RemainingProgramTime . 's remaining)';
                        } else if($payload->CurrentCavityTemperature < $payload->SetpointTemperature) {
                            $state = 'Preheating (' . floor($payload->CurrentCavityTemperature) . '/' . $payload->SetpointTemperature . ')';
                        } else {
                            $state = 'Running (' . $payload->ElapsedProgramTime . 's elapsed)';
                        }
                    } else {
                        $state = $payload->OperationState;
                    }

                    // Duration
                    // ElapsedProgramTime (including preheating)
                    // RemainingProgramTime
                }
                $this->SetValue("State", $state);
                // PowerState
                // OperationState
                // PreheatFinished
                // DoorState
                // CavityHeatup
                // ProgramFinished
                // AlarmClock
                // AlarmClockElapsed
            }
        }
    }

    public function RequestAction($Ident, $Value)
    {
    }

    public function SendRequest(string $Ident, string $Value)
    {
    }
}