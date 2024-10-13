<?php

trait HCLUtilities {

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

    protected function FormatDuration($duration) {
        $minutes = floor(($duration % 3600) / 60);
        $hours = floor($duration / 3600);

        $value = str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT) . 'h';
        return $value;
    }

    protected function StartProgram($program, $options = null)
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
    
    protected function SendRequest(int $uid, $Value)
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

    protected function RequestUpdate()
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