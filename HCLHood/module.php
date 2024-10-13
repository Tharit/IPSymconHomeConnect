<?php

require_once(__DIR__ . '/../libs/ModuleUtilities.php');
require_once(__DIR__ . '/../libs/HCLUtilities.php');

class HomeConnectLocalHood extends IPSModule
{
    use ModuleUtilities;
    use HCLUtilities;

    public function Create()
    {
        // buffers
        $this->HCLInit(__DIR__);

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
        $this->RegisterVariableBoolean("Connected", "Connected", "", 0);
        $this->RegisterVariableBoolean("Power", "Power", "~Switch", 1);
        $this->RegisterVariableString("State", "State", "", 2);
        $this->RegisterVariableInteger("Program", "Program", "HomeConnectLocalHood.Program", 3);
        $this->RegisterVariableInteger("VentingLevel", "Venting Level", "HomeConnectLocalHood.VentingLevel", 4);
        $this->RegisterVariableBoolean("Lighting", "Light", "~Switch", 5);
        $this->RegisterVariableInteger("GreaseFilterSaturation", "Grease Filter Saturation", "~Intensity.100", 6);
        $this->RegisterScript("ResetGreaseFilter", "Reset Grease Filter", "<?php\n HCL_ResetGreaseFilter(IPS_GetParent(\$_IPS['SELF']))", 7);

        $this->EnableAction("VentingLevel");
        $this->EnableAction("Program");
        $this->EnableAction("Lighting");
        $this->EnableAction("Power");
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
            $this->HCLUpdateConnected($Buffer->Topic, $Buffer->Payload);
        } else {
            $payload = json_decode($Buffer->Payload);

            $state = $this->HCLUpdateState($payload);
            
            /*
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
                $this->SetValue("Power", $payload->PowerState === 'On' ? true : false);

                if($payload->PowerState !== 'On') {
                    $state = $payload->PowerState;
                    // remap 'Standby' to Off
                    if($state === 'Standby') $state = 'Off';
                } else {
                    if($payload->OperationState === 'Run') {
                        $details = $program;
                        // manual mode
                        if($payload->ActiveProgram === 55307) {
                            $details = 'Level ' . $payload->VentingLevel;
                        // interval or fan run on
                        } else if($payload->ActiveProgram === 55306 || $payload->ActiveProgram === 55301) {
                            $details .= ' (' . $this->FormatDuration($payload->RemainingProgramTime) . ' remaining)';
                        }
                        $state = $details;
                    } else {
                        $state = $payload->OperationState;
                    }
                }
                $this->SetValue("State", $state);
            }
            */
        }
    }

    public function RequestAction($Ident, $Value)
    {
        if($Ident === 'Power') {
            $this->SendRequest(539, $Value === false ? 1 : 2);
        } else if($Ident === 'Lighting') {
            $this->SendRequest(53253, $Value === true ? true : false);
        } else if($Ident === 'Program') {
            if(!in_array($Value, [0, 55296, 55307, 55306, 55301])) return;
            if($Value === 0) {
                $this->SendRequest(539, 1);
            } else {
                $this->StartProgram($Value);
            }
        } else if($Ident === 'VentingLevel') {
            if($Value <= 0 || $Value >= 4) {
                $this->SendRequest(539, 1);
            } else {
                $this->StartProgram(55307, [["uid" => 55308, "value" => $Value]]);
            }
        }
    }

    public function ResetGreaseFilter() {
        $this->SendRequest(55304, true);
    }
}