<?php

class HCLDevice extends IPSModule {

    // generic
    const UID_ACTIVEPROGRAM = 256;
    
    const UID_SETTING_POWERSTATE = 539;
    
    const UID_STATUS_OPERATIONSTATE = 552;
    const UID_STATUS_DOORSTATE = 527;
    
    const UID_OPTION_REMAININGPROGRAMTIME = 544;
    const UID_OPTION_ELAPSEDPROGRAMTIME = 528;
    const UID_OPTION_DURATION = 548;
    const UID_OPTION_STARTINRELATIVE = 558;
    
    const VALUE_DOORSTATE_OPEN = 0;
    const VALUE_DOORSTATE_CLOSED = 1;
    const VALUE_DOORSTATE_LOCKED = 2;
    const VALUE_DOORSTATE_AJAR = 3;
    
    const VALUE_POWERSTATE_MAINSOFF = 0;
    const VALUE_POWERSTATE_OFF = 1;
    const VALUE_POWERSTATE_ON = 2;
    const VALUE_POWERSTATE_STANDBY = 3;
    
    const VALUE_OPERATIONSTATE_INACTIVE         = 0;
    const VALUE_OPERATIONSTATE_READY            = 1;
    const VALUE_OPERATIONSTATE_DELAYEDSTART     = 2;
    const VALUE_OPERATIONSTATE_RUN              = 3;
    const VALUE_OPERATIONSTATE_PAUSE            = 4;
    const VALUE_OPERATIONSTATE_ACTIONREQUIRED   = 5;
    const VALUE_OPERATIONSTATE_FINISHED         = 6;
    const VALUE_OPERATIONSTATE_ERROR            = 7;
    const VALUE_OPERATIONSTATE_ABORTING         = 8;

    protected function HCLDoorStateToString($state) {
        if($state === self::VALUE_DOORSTATE_OPEN) return "Open";
        if($state === self::VALUE_DOORSTATE_CLOSED) return "Closed";
        if($state === self::VALUE_DOORSTATE_LOCKED) return "Locked";
        if($state === self::VALUE_DOORSTATE_AJAR) return "Ajar";
    }
    
    protected function HCLOperationStateToString($state) {
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

    protected function HCLInit() {
        $this->MUSetBuffer('DaemonConnected', false);
        $this->MUSetBuffer('DeviceConnected', false);
        $this->MUSetBuffer('State', []);
    }

    protected function HCLUpdateConnected($topic, $payload) {
        $connected = $payload === 'online' ? true : false;
        if($topic === $this->ReadPropertyString('Topic') . '/LWT') {
            $this->MUSetBuffer('DeviceConnected', $connected);
            $connected = $connected && $this->MUGetBuffer('DaemonConnected');
        } else {
            $this->MUSetBuffer('DaemonConnected', $connected);
            $connected = $connected && $this->MUGetBuffer('DeviceConnected');
        }
        $this->SetValue("Connected", $connected);
    }
    
    protected function HCLGet($state, $uid, $default) {
        if(isset($state[$uid])) return $state[$uid];
        return $default;
    }
    
    protected function HCLUpdateState($payload) {
        $state = $this->MUGetBuffer('State');
        foreach($payload as $key => $value) {
            $state[$key] = $value;
        }
        $this->MUSetBuffer('State', $state);
        return $state;
    }

    protected function HCLFormatDuration($duration) {
        $minutes = floor(($duration % 3600) / 60);
        $hours = floor($duration / 3600);

        $value = str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . 'h';
        return $value;
    }

    protected function HCLStartProgram($program, $options = null)
    {
        $payload = ["program" => $program];
        if($options) {
            $payload["options"] = $options;
        }

        //MQTT Server
        $Server['DataID'] = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
        $Server['PacketType'] = 3;
        $Server['QualityOfService'] = 0;
        $Server['Retain'] = false;
        $Server['Topic'] = $this->ReadPropertyString('Topic') . '/activeProgram';
        $Server['Payload'] = json_encode($payload);
        $ServerJSON = json_encode($Server, JSON_UNESCAPED_SLASHES);
        $resultServer = $this->SendDataToParent($ServerJSON);
    }
    
    protected function HCLSendRequest(int $uid, $Value)
    {
        //MQTT Server
        $Server['DataID'] = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
        $Server['PacketType'] = 3;
        $Server['QualityOfService'] = 0;
        $Server['Retain'] = false;
        $Server['Topic'] = $this->ReadPropertyString('Topic') . '/set';
        $Server['Payload'] = json_encode(["uid" => $uid, "value" => $Value]);
        $ServerJSON = json_encode($Server, JSON_UNESCAPED_SLASHES);
        $resultServer = $this->SendDataToParent($ServerJSON);
    }

    protected function HCLRequestUpdate()
    {
        //MQTT Server
        $Server['DataID'] = '{043EA491-0325-4ADD-8FC2-A30C8EEB4D3F}';
        $Server['PacketType'] = 3;
        $Server['QualityOfService'] = 0;
        $Server['Retain'] = false;
        $Server['Topic'] = $this->ReadPropertyString('Topic') . '/update';
        $Server['Payload'] = json_encode(["time" => time()]);
        $ServerJSON = json_encode($Server, JSON_UNESCAPED_SLASHES);
        $resultServer = $this->SendDataToParent($ServerJSON);
    }
}