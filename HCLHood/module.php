<?php

require_once(__DIR__ . '/../libs/ModuleUtilities.php');

class HomeConnectLocalHood extends IPSModule
{
    use ModuleUtilities;

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        // properties
        $this->RegisterPropertyString('Topic', 'homeconnect/hood');

        // variables
        $this->RegisterVariableBoolean("Connected", "Connected");
        $this->RegisterVariableString("State", "State");
        $this->RegisterVariableInteger("GreaseFilterSaturation", "Grease Filter Saturation");
        $this->RegisterVariableBoolean("Lighting", "Light");

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
                
                $this->SetValue("GreaseFilterSaturation", $payload->GreaseFilterSaturation);
                $this->SetValue("Lighting", $payload->Lighting);

                if($payload->PowerState !== 'On') {
                    // Off
                    $state = $payload->PowerState;
                } else {
                    if($payload->OperationState !== 'Inactive') {
                        $state = 'Running (' . $payload->ActiveProgram . ')';
                    } else {
                        $state = $payload->OperationState;
                    }
                }
                $this->SetValue("State", $state);
                // PowerState
                // OperationState
                // ActiveProgram
                // Lighting
                // GreaseFilterMaxSaturationNearlyReached
                // GreaseFilterMaxSaturationReached
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