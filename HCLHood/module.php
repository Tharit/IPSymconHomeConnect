<?php

require_once(__DIR__ . '/../libs/ModuleUtilities.php');
require_once(__DIR__ . '/../libs/HCLUtilities.php');

class HomeConnectLocalHood extends IPSModule
{
    use ModuleUtilities;
    use HCLUtilities;

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        // properties
        $this->RegisterPropertyString('Topic', 'homeconnect/hood');

        // profiles
        $this->RegisterProfileIntegerEx('HomeConnectLocalHood.Program', 'Program', '', '', [
            [0, 'None',  '', -1],
            [55296, 'Auto',  '', -1],
            [55307, 'Manual',  '', -1],
            [55306, 'Interval',  '', -1],
            [55301, 'Delayed shut off',  '', -1]
        ]);

        $this->RegisterProfileIntegerEx('HomeConnectLocalHood.VentingLevel', 'Venting Level', '', '', [
            [0, 'Off',  '', -1],
            [1, '1',  '', -1],
            [2, '2',  '', -1],
            [3, '3',  '', -1]
        ]);

        // variables
        $this->RegisterVariableBoolean("Connected", "Connected");
        $this->RegisterVariableString("State", "State");
        $this->RegisterVariableInteger("GreaseFilterSaturation", "Grease Filter Saturation", "~Intensity.100");
        $this->RegisterVariableInteger("VentingLevel", "Venting Level", "HomeConnectLocalHood.VentingLevel");
        $this->RegisterVariableInteger("Program", "Program", "HomeConnectLocalHood.Program");
        $this->RegisterVariableBoolean("Lighting", "Light", "~Switch");
        $this->EnableAction("VentingLevel");
        $this->EnableAction("Program");
        $this->EnableAction("Lighting");

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
                
                $program = 'N/A';
                if($payload->ActiveProgram === 55296) {
                    $program = 'Auto';
                } else if($payload->ActiveProgram === 55307) {
                    $program = 'Manual';
                } else if($payload->ActiveProgram === 55306) {
                    $program = 'Interval';
                } else if($payload->ActiveProgram === 55301) {
                    $program = 'Delayed shut off';
                }
                $this->SetValue("GreaseFilterSaturation", $payload->GreaseFilterSaturation);
                $this->SetValue("Program", $payload->ActiveProgram);
                $this->SetValue("Lighting", $payload->Lighting);
                $this->SetValue("VentingLevel", $payload->VentingLevel);

                if($payload->PowerState !== 'On') {
                    $state = $payload->PowerState;
                    // remap 'Standby' to Off
                    if($state === 'Standby') $state = 'Off';
                } else {
                    if($payload->OperationState !== 'Inactive') {
                        $details = $program;
                        // manual mode
                        if($payload->ActiveProgram === 55307) {
                            $details .= ' - Level ' + $payload->VentingLevel;
                        // interval or fan run on
                        } else if($payload->ActiveProgram === 55306 || $payload->ActiveProgram === 55301) {
                            $details .= ' - ' . $this->FormatDuration($payload->RemainingProgramTime) . ' remaining';
                        }
                        $state = 'Running (' . $details . ')';
                    } else {
                        $state = $payload->OperationState;
                    }
                }
                $this->SetValue("State", $state);
                // PowerState
                // OperationState
                // ActiveProgram (auto = 55296, manual = 55307, interval ventilation = 55306, fan run on = 55301)
                // Lighting
                // GreaseFilterMaxSaturationNearlyReached
                // GreaseFilterMaxSaturationReached
                // VentingLevel (0, 1, 2, ...)
            }
        }
    }

    public function RequestAction($Ident, $Value)
    {
        if($ident === 'Lighting') {
            $this->SendRequest(53253, $Value === true ? true : false);
        } else if($ident === 'Program') {
            if(!in_array($Value, [0, 55296, 55307, 55306, 55301])) return;
            $this->StartProgram($Value);
        } else if($ident === 'VentingLevel') {
            if($Value <= 0 || $Value >= 4) {
                $this->StartProgram(0);
            } else if($this->GetValue("Program") !== 55307) {
                $this->StartProgram(55307, [["uid" => 55308, "value" => $Value]]);
            } else {
                $this->SendRequest(55308, max(0, min(3, $Value)));
            }
        }
    }
}