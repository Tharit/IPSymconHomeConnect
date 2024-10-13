<?php

require_once(__DIR__ . '/../libs/ModuleUtilities.php');
require_once(__DIR__ . '/../libs/HCLUtilities.php');

class HomeConnectLocalHood extends IPSModule
{
    use ModuleUtilities;
    use HCLUtilities;

    protected static UID_STATUS_GREASEFILTERSATURATION = 4100;
    protected static UID_SETTING_GREASEFILTERRESET = 55304;
    protected static UID_OPTION_VENTINGLEVEL = 55308;
    protected static UID_STATUS_POWERSTATE = 539;
    protected static UID_ACTIVEPROGRAM = 256;
    protected static UID_OPERATIONSTATE = 552;
    protected static UID_REMAININGPROGRAMTIME = 544;
    protected static VALUE_POWERSTATE_OFF = 1;
    protected static VALUE_POWERSTATE_ON = 2;

    protected static VALUE_OPERATIONSTATE_INACTIVE = 0;
    protected static VALUE_OPERATIONSTATE_RUN = 3;

    protected static UID_PROGRAM_NONE = 0;
    protected static UID_PROGRAM_AUTO = 55296;
    
    protected static UID_PROGRAM_NONE = 0;
    protected static UID_PROGRAM_AUTO = 55296;
    protected static UID_PROGRAM_MANUAL = 55307;
    protected static UID_PROGRAM_INTERVAL = 55306;
    protected static UID_PROGRAM_DELAYEDSHUTOFF = 55301;

    protected static UID_PROGRAMS = [
        HomeConnectLocalHood::UID_PROGRAM_NONE,
        HomeConnectLocalHood::UID_PROGRAM_AUTO,
        HomeConnectLocalHood::UID_PROGRAM_MANUAL,
        HomeConnectLocalHood::UID_PROGRAM_INTERVAL,
        HomeConnectLocalHood::UID_PROGRAM_DELAYEDSHUTOFF
    ];

    protected static UID_SETTING_LIGHTING = 53253;

    /**
     * @TODO: define static variables for UIDs
     */

    public function Create()
    {
        // buffers
        $this->HCLInit(json_decode(file_get_contents(__DIR__ . '/device.json')));

        //Never delete this line!
        parent::Create();
        $this->ConnectParent('{C6D2AEB3-6E1F-4B2E-8E69-3A1A00246850}');

        // properties
        $this->RegisterPropertyString('Topic', 'homeconnect/hood');

        // profiles
        $this->RegisterProfileIntegerEx('HomeConnectLocalHood.Program', 'Program', '', '', [
            [HomeConnectLocalHood::UID_PROGRAM_NONE, 'None',  '', -1],
            [HomeConnectLocalHood::UID_PROGRAM_AUTO, 'Auto',  '', -1],
            [HomeConnectLocalHood::UID_PROGRAM_MANUAL, 'Manual',  '', -1],
            [HomeConnectLocalHood::UID_PROGRAM_INTERVAL, 'Interval',  '', -1],
            [HomeConnectLocalHood::UID_PROGRAM_DELAYEDSHUTOFF, 'Delayed shut off',  '', -1]
        ]);

        $this->RegisterProfileIntegerEx('HomeConnectLocalHood.VentingLevel', 'Venting Level', '', '', [
            [0, 'Off',  '', -1],
            [1, '1',  '', -1],
            [2, '2',  '', -1],
            [3, '3',  '', -1],
            [4, 'Boost',  '', -1],
            [5, 'Super Boost',  '', -1]
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
            
            if(isset($state[HomeConnectLocalHood::UID_SETTING_POWERSTATE])) {
                
                $program = 'N/A';
                
                $activeProgram = $state[HomeConnectLocalHood::UID_ACTIVEPROGRAM];
                $powerState = $state[HomeConnectLocalHood::UID_SETTING_POWERSTATE];
                $ventingLevel = $state[HomeConnectLocalHood::UID_OPTION_VENTINGLEVEL];

                if($activeProgram === HomeConnectLocalHood::UID_PROGRAM_AUTO) {
                    $program = 'Auto';
                } else if($activeProgram === HomeConnectLocalHood::UID_PROGRAM_MANUAL) {
                    $program = 'Manual';
                } else if($activeProgram === HomeConnectLocalHood::UID_PROGRAM_INTERVAL) {
                    $program = 'Interval';
                } else if($activeProgram === HomeConnectLocalHood::UID_PROGRAM_DELAYEDSHUTOFF) {
                    $program = 'Delayed shut off';
                }
                $this->SetValue("GreaseFilterSaturation", $state[HomeConnectLocalHood::UID_STATUS_GREASEFILTERSATURATION]);
                $this->SetValue("Program", $activeProgram);
                $this->SetValue("Lighting", $state[HomeConnectLocalHood::UID_SETTING_LIGHTING]);
                $this->SetValue("VentingLevel", $ventingLevel);
                $this->SetValue("Power", $powerState === HomeConnectLocalHood::VALUE_POWERSTATE_ON ? true : false);

                if($powerState !== HomeConnectLocalHood::VALUE_POWERSTATE_ON) {
                    $state = 'Off';
                } else {
                    $operationState = $state[HomeConnectLocalHood::UID_OPERATIONSTATE];
                    if($operationState === HomeConnectLocalHood::VALUE_OPERATIONSTATE_RUN) {
                        $details = $program;
                        // manual mode
                        if($payload->ActiveProgram === HomeConnectLocalHood::UID_PROGRAM_MANUAL) {
                            $details = 'Level ' . $ventingLevel;
                        // interval or fan run on
                        } else if($activeProgram === HomeConnectLocalHood::UID_PROGRAM_INTERVAL || $activeProgram === HomeConnectLocalHood::UID_PROGRAM_DELAYEDSHUTOFF) {
                            $details .= ' (' . $this->FormatDuration($state[HomeConnectLocalHood::UID_REMAININGPROGRAMTIME]) . ' remaining)';
                        }
                        $state = $details;
                    } else {
                        $state = 'Inactive';
                    }
                }
                $this->SetValue("State", $state);
            }
        }
    }

    public function RequestAction($Ident, $Value)
    {
        if($Ident === 'Power') {
            $this->SendRequest(HomeConnectLocalHood::UID_SETTING_POWERSTATE, $Value === false ? HomeConnectLocalHood::VALUE_POWERSTATE_OFF : HomeConnectLocalHood::VALUE_POWERSTATE_ON);
        } else if($Ident === 'Lighting') {
            $this->SendRequest(HomeConnectLocalHood::UID_SETTING_LIGHTING, $Value === true ? true : false);
        } else if($Ident === 'Program') {
            if(!in_array($Value, HomeConnectLocalHood::UID_PROGRAMS)) return;
            if($Value === 0) {
                $this->SendRequest(HomeConnectLocalHood::UID_SETTING_POWERSTATE, HomeConnectLocalHood::VALUE_POWERSTATE_OFF);
            } else {
                $this->StartProgram($Value);
            }
        } else if($Ident === 'VentingLevel') {
            if($Value <= 0 || $Value >= 4) {
                $this->SendRequest(HomeConnectLocalHood::UID_SETTING_POWERSTATE, HomeConnectLocalHood::VALUE_POWERSTATE_OFF);
            } else {
                $this->StartProgram(HomeConnectLocalHood::UID_PROGRAM_MANUAL, [
                    ["uid" => HomeConnectLocalHood::UID_OPTION_VENTINGLEVEL, "value" => $Value]
                ]);
            }
        }
    }

    public function ResetGreaseFilter() {
        $this->SendRequest(HomeConnectLocalHood::UID_SETTING_GREASEFILTERRESET, true);
    }
}