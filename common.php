<?php

declare(strict_types=1);

trait StubsCommonLib
{
    protected function SetValue($ident, $value)
    {
        @$varID = $this->GetIDForIdent($ident);
        if ($varID == false) {
            $this->SendDebug(__FUNCTION__, 'missing variable ' . $ident, 0);
            return;
        }

        @$ret = parent::SetValue($ident, $value);
        if ($ret == false) {
            $this->SendDebug(__FUNCTION__, 'mismatch of value "' . $value . '" for variable ' . $ident, 0);
        }
    }

    private function SaveValue(string $ident, $value, bool &$IsChanged)
    {
        @$varID = $this->GetIDForIdent($ident);
        if ($varID == false) {
            $this->SendDebug(__FUNCTION__, 'missing variable ' . $ident, 0);
            return;
        }

        if (parent::GetValue($ident) != $value) {
            $IsChanged = true;
        }

        @$ret = parent::SetValue($ident, $value);
        if ($ret == false) {
            $this->SendDebug(__FUNCTION__, 'mismatch of value "' . $value . '" for variable ' . $ident, 0);
            return;
        }
    }

    protected function GetValue($ident)
    {
        @$varID = $this->GetIDForIdent($ident);
        if ($varID == false) {
            $this->SendDebug(__FUNCTION__, 'missing variable ' . $ident, 0);
            return false;
        }

        $ret = parent::GetValue($ident);
        return $ret;
    }

    private function CreateVarProfile(string $ident, int $varType, string $suffix, float $min, float $max, int $stepSize, int $digits, string $icon, $associations = null, bool $doReinstall)
    {
        if ($doReinstall && IPS_VariableProfileExists($ident)) {
            IPS_DeleteVariableProfile($ident);
        }
        if (!IPS_VariableProfileExists($ident)) {
            IPS_CreateVariableProfile($ident, $varType);
            IPS_SetVariableProfileText($ident, '', $suffix);
            if (in_array($varType, [VARIABLETYPE_INTEGER, VARIABLETYPE_FLOAT])) {
                IPS_SetVariableProfileValues($ident, $min, $max, $stepSize);
                IPS_SetVariableProfileDigits($ident, $digits);
            }
            IPS_SetVariableProfileIcon($ident, $icon);
            if ($associations != null) {
                foreach ($associations as $a) {
                    $w = isset($a['Wert']) ? $a['Wert'] : '';
                    $n = isset($a['Name']) ? $a['Name'] : '';
                    $i = isset($a['Icon']) ? $a['Icon'] : '';
                    $f = isset($a['Farbe']) ? $a['Farbe'] : 0;
                    IPS_SetVariableProfileAssociation($ident, $w, $n, $i, $f);
                }
            }
        }
    }

    private function CheckVarProfile4Value(string $ident, $value)
    {
        $name = false;
        if (IPS_VariableProfileExists($ident)) {
            $VarProfil = IPS_GetVariableProfile($ident);
            $Associations = $VarProfil['Associations'];
            foreach ($Associations as $Association) {
                if ($value == $Association['Value']) {
                    $name = $Association['Name'];
                    break;
                }
            }
        }
        return $name;
    }

    // inspired by Nall-chan
    //   https://github.com/Nall-chan/IPSSqueezeBox/blob/6bbdccc23a0de51bb3fbc114cefc3acf23c27a14/libs/SqueezeBoxTraits.php
    public function __get(string $ident)
    {
        $n = strpos($ident, 'Multi_');
        if (strpos($ident, 'Multi_') === 0) {
            $curCount = $this->GetBuffer('BufferCount_' . $ident);
            if ($curCount == false) {
                $curCount = 0;
            }
            $data = '';
            for ($i = 0; $i < $curCount; $i++) {
                $data .= $this->GetBuffer('BufferPart' . $i . '_' . $ident);
            }
        } else {
            $data = $this->GetBuffer($ident);
        }
        return unserialize($data);
    }

    public function __set(string $ident, string $value)
    {
        $data = serialize($value);
        $n = strpos($ident, 'Multi_');
        if (strpos($ident, 'Multi_') === 0) {
            $oldCount = $this->GetBuffer('BufferCount_' . $ident);
            if ($oldCount == false) {
                $oldCount = 0;
            }
            $parts = str_split($data, 8000);
            $newCount = count($parts);
            $this->SetBuffer('BufferCount_' . $ident, $newCount);
            for ($i = 0; $i < $newCount; $i++) {
                $this->SetBuffer('BufferPart' . $i . '_' . $ident, $parts[$i]);
            }
            for ($i = $newCount; $i < $oldCount; $i++) {
                $this->SetBuffer('BufferPart' . $i . '_' . $ident, '');
            }
        } else {
            $this->SetBuffer($ident, $data);
        }
    }

    private function SetMultiBuffer(string $ident, string $value)
    {
        $this->{'Multi_' . $ident} = $value;
    }

    private function GetMultiBuffer(string $ident)
    {
        $value = $this->{'Multi_' . $ident};
        return $value;
    }

    private function GetMediaData(string $ident)
    {
        $name = $this->Translate($ident);
        @$mediaID = IPS_GetMediaIDByName($name, $this->InstanceID);
        if ($mediaID == false) {
            $this->SendDebug(__FUNCTION__, 'missing media-object ' . $ident, 0);
            return false;
        }
        $data = base64_decode(IPS_GetMediaContent($mediaID));
        return $data;
    }

    private function SetMediaData(string $ident, string $data, int $mediatyp, string $extension, bool $cached)
    {
        $n = strlen(base64_encode($data));
        $this->SendDebug(__FUNCTION__, 'write ' . $n . ' bytes to media-object ' . $ident, 0);
        $name = $this->Translate($ident);
        @$mediaID = IPS_GetMediaIDByName($name, $this->InstanceID);
        if ($mediaID == false) {
            $mediaID = IPS_CreateMedia($mediatyp);
            if ($mediaID == false) {
                $this->SendDebug(__FUNCTION__, 'unable to create media-object ' . $ident, 0);
                return false;
            }
            $filename = 'media' . DIRECTORY_SEPARATOR . $this->InstanceID . '-' . $ident . $extension;
            IPS_SetMediaFile($mediaID, $filename, false);
            IPS_SetName($mediaID, $name);
            IPS_SetParent($mediaID, $this->InstanceID);
            $this->SendDebug(__FUNCTION__, 'media-object ' . $ident . ' created, filename=' . $filename, 0);
        }
        IPS_SetMediaCached($mediaID, $cached);
        IPS_SetMediaContent($mediaID, base64_encode($data));
    }

    private function HookIsUsed(string $ident)
    {
        $this->SendDebug(__FUNCTION__, 'ident=' . $ident, 0);
        $used = false;
        $instID = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}')[0];
        $hooks = json_decode(IPS_GetProperty($instID, 'Hooks'), true);
        foreach ($hooks as $hook) {
            if ($hook['Hook'] == $ident) {
                if ($hook['TargetID'] != $this->InstanceID) {
                    $used = true;
                }
                break;
            }
        }
        $this->SendDebug(__FUNCTION__, 'used=' . $this->bool2str($used), 0);
        return $used;
    }

    private function RegisterHook(string $ident)
    {
        $this->SendDebug(__FUNCTION__, 'ident=' . $ident, 0);
        $ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
        if (count($ids) > 0) {
            $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
            $found = false;
            foreach ($hooks as $index => $hook) {
                if ($hook['Hook'] == $ident) {
                    if ($hook['TargetID'] != $this->InstanceID) {
                        $this->SendDebug(__FUNCTION__, 'already exists with foreign TargetID ' . $hook['TargetID'] . ', overwrite with ' . $this->InstanceID, 0);
                        $hooks[$index]['TargetID'] = $this->InstanceID;
                    } else {
                        $this->SendDebug(__FUNCTION__, 'already exists with correct TargetID ' . $this->InstanceID, 0);
                    }
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $hooks[] = ['Hook' => $ident, 'TargetID' => $this->InstanceID];
                $this->SendDebug(__FUNCTION__, 'not found, create with TargetID ' . $this->InstanceID, 0);
            }
            IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
            IPS_ApplyChanges($ids[0]);
        }
    }

    private function GetMimeType(string $extension)
    {
        $lines = file(IPS_GetKernelDirEx() . 'mime.types');
        foreach ($lines as $line) {
            $type = explode("\t", $line, 2);
            if (count($type) == 2) {
                $types = explode(' ', trim($type[1]));
                foreach ($types as $ext) {
                    if ($ext == $extension) {
                        return $type[0];
                    }
                }
            }
        }
        return 'text/plain';
    }

    private function OAuthIsUsed(string $ident)
    {
        $this->SendDebug(__FUNCTION__, 'ident=' . $ident, 0);
        $used = false;
        $instID = IPS_GetInstanceListByModuleID('{F99BF07D-CECA-438B-A497-E4B55F139D37}')[0];
        $clientID = json_decode(IPS_GetProperty($instID, 'ClientIDs'), true);
        foreach ($clientID as $clientID) {
            if ($clientID['ClientID'] == $ident) {
                if ($clientID['TargetID'] != $this->InstanceID) {
                    $used = true;
                }
                break;
            }
        }
        $this->SendDebug(__FUNCTION__, 'used=' . $this->bool2str($used), 0);
        return $used;
    }

    private function RegisterOAuth(string $ident)
    {
        $this->SendDebug(__FUNCTION__, 'ident=' . $ident, 0);
        $ids = IPS_GetInstanceListByModuleID('{F99BF07D-CECA-438B-A497-E4B55F139D37}');
        if (count($ids) > 0) {
            $clientIDs = json_decode(IPS_GetProperty($ids[0], 'ClientIDs'), true);
            $found = false;
            foreach ($clientIDs as $index => $clientID) {
                if ($clientID['ClientID'] == $ident) {
                    if ($clientID['TargetID'] == $this->InstanceID) {
                        $this->SendDebug(__FUNCTION__, 'already exists with foreign TargetID ' . $clientID['TargetID'] . ', overwrite with ' . $this->InstanceID, 0);
                        $clientID['TargetID'] = $this->InstanceID;
                    } else {
                        $this->SendDebug(__FUNCTION__, 'already exists with correct TargetID ' . $this->InstanceID, 0);
                    }
                    $clientIDs[$index]['TargetID'] = $this->InstanceID;
                    $found = true;
                }
            }
            if (!$found) {
                $clientIDs[] = ['ClientID' => $ident, 'TargetID' => $this->InstanceID];
                $this->SendDebug(__FUNCTION__, 'not found, create with TargetID ' . $this->InstanceID, 0);
            }
            IPS_SetProperty($ids[0], 'ClientIDs', json_encode($clientIDs));
            IPS_ApplyChanges($ids[0]);
        }
    }

    private function AdjustAction(string $ident, bool $mode)
    {
        @$varID = $this->GetIDForIdent($ident);
        if ($varID == false) {
            $this->SendDebug(__FUNCTION__, 'missing variable ' . $ident, 0);
            return false;
        }

        $v = IPS_GetVariable($varID);
        $oldmode = $v['VariableAction'] != 0;

        $this->SendDebug(__FUNCTION__, 'MaintainAction(' . $ident . ', ' . $this->bool2str($mode) . ')', 0);
        $this->MaintainAction($ident, $mode);

        return $oldmode != $mode;
    }

    private function GetArrayElem($data, string $var, $dflt, bool &$fnd = null)
    {
        $ret = $data;
        if (is_array($data)) {
            $b = true;
            $vs = explode('.', $var);
            foreach ($vs as $v) {
                if (!isset($ret[$v])) {
                    $ret = $dflt;
                    $b = false;
                    break;
                }
                $ret = $ret[$v];
            }
        } else {
            $b = false;
        }
        if (is_null($fnd) == false) {
            $fnd = $b;
        }
        return $ret;
    }

    private function bool2str(bool $bval)
    {
        if (is_bool($bval)) {
            return $bval ? 'true' : 'false';
        }
        return $bval;
    }

    private function seconds2duration(int $sec)
    {
        $duration = '';
        if ($sec >= 3600) {
            $duration .= sprintf('%dh', floor($sec / 3600));
            $sec = $sec % 3600;
        }
        if ($sec >= 60) {
            $duration .= sprintf('%dm', floor($sec / 60));
            $sec = $sec % 60;
        }
        if ($sec > 0) {
            $duration .= sprintf('%ds', $sec);
            $sec = floor($sec);
        }

        return $duration;
    }

    private function format_float(float $number, int $dec_points = -1)
    {
        if (is_numeric((float) $number)) {
            $nk = abs($number - floor($number));
            $n = strlen((string) floatval($nk));
            $d = ($n > 1) ? $n - 2 : 0;
            if ($dec_points == -1 || $dec_points > $d) {
                $dec_points = $d;
            }
            $result = number_format($number, $dec_points, '.', '');
        } else {
            $result = false;
        }
        return $result;
    }

    private function LimitOutput($str, int $maxLength = null)
    {
        $lim = IPS_GetOption('ScriptOutputBufferLimit');
        if (is_null($maxLength)) {
            $maxLength = intval($lim / 10);
        } elseif ($maxLength == 0) {
            $maxLength = $lim - 1024;
        } elseif ($maxLength < 0) {
            $maxLength = $lim - $maxLength;
        } elseif ($maxLength > $lim) {
            $maxLength = $lim;
        }

        if (is_array($str)) {
            $str = print_r($str, true);
        }

        $len = strlen($str);
        if ($len > $maxLength) {
            $s = '»[cut=' . $maxLength . '/' . $len . ']';
            $cutLen = $maxLength - strlen($s);
            $str = substr($str, 0, $cutLen) . $s;
        }
        return $str;
    }

    private function HttpCode2Text(int $code)
    {
        $code2text = [
            // 1xx – Information
            100 => 'Continue',
            101 => 'Switching Protocols',
            102 => 'Processing',
            103 => 'Early Hints',
            // 2xx – Success
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            207 => 'Multi-Status',
            208 => 'Already Reported',
            226 => 'IM Used',
            // 3xx – Redirection
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found (Moved Temporarily)',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => '(reserviert)',
            307 => 'Temporary Redirect',
            308 => 'Permanent Redirect',
            // 4xx - Client error
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Payload Too Large',
            414 => 'URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Range Not Satisfiable',
            417 => 'Expectation Failed',
            418 => 'I’m a teapot',
            420 => 'Policy Not Fulfilled',
            421 => 'Misdirected Request',
            422 => 'Unprocessable Entity',
            423 => 'Locked',
            424 => 'Failed Dependency',
            425 => 'Too Early',
            426 => 'Upgrade Required',
            428 => 'Precondition Required',
            429 => 'Too Many Requests',
            431 => 'Request Header Fields Too Large',
            444 => 'No Response',
            449 => 'The request should be retried after doing the appropriate action',
            451 => 'Unavailable For Legal Reasons',
            499 => 'Client Closed Request',
            // 5xx - Server error
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version not supported',
            506 => 'Variant Also Negotiates',
            507 => 'Insufficient Storage',
            508 => 'Loop Detected',
            509 => 'Bandwidth Limit Exceeded',
            510 => 'Not Extended',
            511 => 'Network Authentication Required',
            599 => 'Network Connect Timeout Error',
        ];

        $result = isset($code2text[$code]) ? $code2text[$code] : ('Unknown http-error ' . $code);
        return $result;
    }

    public function GetConfigurationForm()
    {
        $formElements = $this->GetFormElements();
        $formActions = $this->GetFormActions();
        $formStatus = $this->GetFormStatus();

        $form = json_encode(['elements' => $formElements, 'actions' => $formActions, 'status' => $formStatus]);
        if ($form == '') {
            $this->SendDebug(__FUNCTION__, 'json_error=' . json_last_error_msg(), 0);
            $this->SendDebug(__FUNCTION__, '=> formElements=' . print_r($formElements, true), 0);
            $this->SendDebug(__FUNCTION__, '=> formActions=' . print_r($formActions, true), 0);
            $this->SendDebug(__FUNCTION__, '=> formStatus=' . print_r($formStatus, true), 0);
        }
        return $form;
    }

    private function GetConnectUrl()
    {
        $instID = IPS_GetInstanceListByModuleID('{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}')[0];
        $url = CC_GetConnectURL($instID);
        return $url;
    }

    private function GetConnectStatus()
    {
        $instID = IPS_GetInstanceListByModuleID('{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}')[0];
        return IPS_GetInstance($instID)['InstanceStatus'];
    }

    private function GetConnectStatusText()
    {
        if ($this->GetConnectStatus() != IS_ACTIVE) {
            $s = 'Error: Symcon Connect is not active!';
        } else {
            $s = 'Status: Symcon Connect is OK!';
        }
        return $s;
    }

    private function GetConnectionID()
    {
        $inst = IPS_GetInstance($this->InstanceID);
        $cID = $inst['ConnectionID'];

        return $cID;
    }

    private function GetStatusText()
    {
        $txt = false;
        $status = $this->GetStatus();
        $formStatus = $this->GetFormStatus();
        foreach ($formStatus as $item) {
            if ($item['code'] == $status) {
                $txt = $item['caption'];
                break;
            }
        }

        return $txt;
    }

    private function TranslateFormat(string $text, array $vars = null)
    {
        $s = $this->Translate($text);
        if ($vars != null) {
            $s = strtr($s, $vars);
        }
        return $s;
    }

    private function InstanceInfo(int $instID)
    {
        $obj = IPS_GetObject($instID);
        $inst = IPS_GetInstance($instID);
        $mod = IPS_GetModule($inst['ModuleInfo']['ModuleID']);
        $lib = IPS_GetLibrary($mod['LibraryID']);

        $s = '';
        $m = [];

        $s .= 'Modul "' . $mod['ModuleName'] . '"' . PHP_EOL;
        $s .= '  GUID: ' . $mod['ModuleID'] . PHP_EOL;
        $m[] = 'module=' . $mod['ModuleName'];

        $s .= PHP_EOL;

        $s .= 'Library "' . $lib['Name'] . '"' . PHP_EOL;
        $s .= '  GUID: ' . $lib['LibraryID'] . PHP_EOL;
        $s .= '  Version: ' . $lib['Version'] . PHP_EOL;
        $m[] = 'version=' . $lib['Version'];
        if ($lib['Build'] > 0) {
            $s .= '  Build: ' . $lib['Build'] . PHP_EOL;
            $m[] = 'build=' . $lib['Build'];
        }
        $ts = $lib['Date'];
        $d = $ts > 0 ? date('d.m.Y H:i:s', $ts) : '';
        $s .= '  Date: ' . $d . PHP_EOL;
        $m[] = 'date=' . $d;

        $src = '';
        $scID = IPS_GetInstanceListByModuleID('{F45B5D1F-56AE-4C61-9AB2-C87C63149EC3}')[0];
        $scList = SC_GetModuleInfoList($scID);
        foreach ($scList as $sc) {
            if ($sc['LibraryID'] == $lib['LibraryID']) {
                $src = ($src != '' ? ' + ' : '') . 'ModuleStore';
                switch ($sc['Channel']) {
                    case 1:
                        $src .= '/Beta';
                        break;
                    case 2:
                        $src .= '/Testing';
                        break;
                    default:
                        break;
                }
                $m[] = 'source=' . $src;
                break;
            }
        }
        $mcID = IPS_GetInstanceListByModuleID('{B8A5067A-AFC2-3798-FEDC-BCD02A45615E}')[0];
        $mcList = MC_GetModuleList($mcID);
        foreach ($mcList as $mc) {
            @$g = MC_GetModule($mcID, $mc);
            if ($g == false) {
                continue;
            }
            if ($g['LibraryID'] == $lib['LibraryID']) {
                @$r = MC_GetModuleRepositoryInfo($mcID, $mc);
                if ($r == false) {
                    continue;
                }
                $url = $r['ModuleURL'];
                if (preg_match('/^([^:]*):\/\/[^@]*@(.*)$/', $url, $p)) {
                    $url = $p[1] . '://' . $p[2];
                }
                $src = ($src != '' ? ' + ' : '') . $url;
                $branch = $r['ModuleBranch'];
                switch ($branch) {
                    case 'master':
                    case 'main':
                        $m[] = 'source=git';
                        break;
                    default:
                        $src .= ' [' . $branch . ']';
                        $m[] = 'source=git' . $src . '[' . $branch . ']';
                        break;
                }
                break;
            }
        }
        $s .= '  Source: ' . $src . PHP_EOL;

        @$updateInfo = $this->ReadAttributeString('UpdateInfo');
        if ($updateInfo != false) {
            @$updateInfo = json_decode($updateInfo, true);
            $ts = isset($updateInfo['tstamp']) ? $updateInfo['tstamp'] : 0;
            $u = $ts > 0 ? date('d.m.Y H:i:s', $ts) : '-';
            $s .= PHP_EOL;
            $s .= 'Updated: ' . $u . PHP_EOL;
        }

        $this->SendDebug(__FUNCTION__, implode(', ', $m), 0);
        return $s;
    }

    private function GetInformationFormAction()
    {
        $formAction = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Information',
            'items'   => [
                [
                    'type'    => 'Label',
                    'caption' => $this->InstanceInfo($this->InstanceID),
                ],
            ],
        ];
        return $formAction;
    }

    private function ScriptType2Name($scriptType)
    {
        $map = [
            SCRIPTTYPE_PHP  => 'PHP script',
            SCRIPTTYPE_FLOW => 'Flow plan',
        ];

        if (isset($map[$scriptType])) {
            return $this->Translate($map[$scriptType]);
        }
        return false;
    }

    private function EventType2Name($eventType)
    {
        $map = [
            EVENTTYPE_TRIGGER  => 'Triggered event',
            EVENTTYPE_CYCLIC   => 'Cyclic event',
            EVENTTYPE_SCHEDULE => 'Scheduled event',
        ];

        if (isset($map[$eventType])) {
            return $this->Translate($map[$eventType]);
        }
        return false;
    }

    private function ObjectType2Name($objectType)
    {
        $map = [
            OBJECTTYPE_CATEGORY => 'Category',
            OBJECTTYPE_INSTANCE => 'Instance',
            OBJECTTYPE_VARIABLE => 'Variable',
            OBJECTTYPE_SCRIPT   => 'Script',
            OBJECTTYPE_EVENT    => 'Event',
            OBJECTTYPE_MEDIA    => 'Medium',
            OBJECTTYPE_LINK     => 'Link',
        ];

        if (isset($map[$objectType])) {
            return $this->Translate($map[$objectType]);
        }
        return false;
    }

    private function cmp_refs($a, $b)
    {
        if (isset($a['VariableIdent']) && isset($b['VariableIdent'])) {
            if ($a['VariableIdent'] != $b['VariableIdent']) {
                return (strcmp($a['VariableIdent'], $b['VariableIdent']) < 0) ? -1 : 1;
            }
        }

        if ($a['ObjectArea'] != $b['ObjectArea']) {
            return (strcmp($a['ObjectArea'], $b['ObjectArea']) < 0) ? -1 : 1;
        }

        if ($a['ObjectName'] != $b['ObjectName']) {
            return (strcmp($a['ObjectName'], $b['ObjectName']) < 0) ? -1 : 1;
        }

        return ($a['ObjektID'] < $b['ObjektID']) ? -1 : 1;
    }

    private function cmp_timer($a, $b)
    {
        if (isset($a['Name']) && isset($b['Name'])) {
            if ($a['Name'] != $b['Name']) {
                return (strcmp($a['Name'], $b['Name']) < 0) ? -1 : 1;
            }
        }

        return ($a['TimerID'] < $b['TimerID']) ? -1 : 1;
    }

    private function ExplodeReferences($instID)
    {
        $inst = IPS_GetInstance($instID);
        $moduleID = $inst['ModuleInfo']['ModuleID'];

        $actionIDs = [];
        $actions = json_decode(IPS_GetActions(), true);
        foreach ($actions as $action) {
            if (isset($action['restrictions']['moduleID']) && is_array($action['restrictions']['moduleID'])) {
                foreach ($action['restrictions']['moduleID'] as $mID) {
                    if ($mID == $moduleID) {
                        $actionIDs[] = $action['id'];
                    }
                }
            }
        }

        $ucID = IPS_GetInstanceListByModuleID('{B69010EA-96D5-46DF-B885-24821B8C8DBD}')[0];

        // von Instanz referenzierte Objekte
        $referencing = [];
        $refIDs = IPS_GetReferenceList($instID);
        foreach ($refIDs as $objID) {
            $obj = IPS_GetObject($objID);
            $objectType = $obj['ObjectType'];
            switch ($objectType) {
                case OBJECTTYPE_CATEGORY:
                    $referencing[] = [
                        'ObjektID'   => $objID,
                        'ObjectType' => $objectType,
                        'ObjectArea' => $this->ObjectType2Name($objectType),
                        'ObjectName' => IPS_GetName($objID) . ' (' . IPS_GetName(IPS_GetParent($objID)) . ')',
                    ];
                    break;
                case OBJECTTYPE_INSTANCE:
                    $inst = IPS_GetInstance($objID);
                    $moduleType = $inst['ModuleInfo']['ModuleType'];
                    $referencing[] = [
                        'ObjektID'   => $objID,
                        'ObjectType' => $objectType,
                        'ModuleType' => $moduleType,
                        'ObjectArea' => $this->ObjectType2Name($objectType),
                        'ObjectName' => IPS_GetName($objID) . ' (' . IPS_GetName(IPS_GetParent($objID)) . ')',
                    ];
                    break;
                case OBJECTTYPE_VARIABLE:
                    $var = IPS_GetVariable($objID);
                    $variableType = $var['VariableType'];
                    $referencing[] = [
                        'ObjektID'     => $objID,
                        'ObjectType'   => $objectType,
                        'VariableType' => $variableType,
                        'ObjectArea'   => $this->ObjectType2Name($objectType),
                        'ObjectName'   => IPS_GetName($objID) . ' (' . IPS_GetName(IPS_GetParent($objID)) . ')',
                    ];
                    break;
                case OBJECTTYPE_SCRIPT:
                    $script = IPS_GetScript($objID);
                    $scriptType = $script['ScriptType'];
                    $referencing[] = [
                        'ObjektID'   => $objID,
                        'ObjectType' => $objectType,
                        'ScriptType' => $scriptType,
                        'ObjectArea' => $this->ScriptType2Name($scriptType),
                        'ObjectName' => IPS_GetName($objID) . ' (' . IPS_GetName(IPS_GetParent($objID)) . ')',
                    ];
                    break;
                case OBJECTTYPE_EVENT:
                    $event = IPS_GetEvent($objID);
                    $eventType = $event['EventType'];
                    $referencing[] = [
                        'ObjektID'   => $objID,
                        'ObjectType' => $objectType,
                        'EventType'  => $eventType,
                        'ObjectArea' => $this->EventType2Name($eventType),
                        'ObjectName' => IPS_GetName($objID),
                    ];
                    break;
                case OBJECTTYPE_MEDIA:
                    $media = IPS_GetMedia($objID);
                    $mediaType = $media['MediaType'];
                    $referencing[] = [
                        'ObjektID'   => $objID,
                        'ObjectType' => $objectType,
                        'MediaType'  => $mediaType,
                        'ObjectArea' => $this->ObjectType2Name($objectType),
                        'ObjectName' => IPS_GetName($objID) . ' (' . IPS_GetName(IPS_GetParent($objID)) . ')',
                    ];
                    break;
                case OBJECTTYPE_LINK:
                    $referencing[] = [
                        'ObjektID'   => $objID,
                        'ObjectType' => $objectType,
                        'ObjectArea' => $this->ObjectType2Name($objectType),
                        'ObjectName' => IPS_GetName($objID),
                    ];
                    break;
                default:
                    break;
            }
        }
        usort($referencing, [__CLASS__, 'cmp_refs']);

        // Instanz referenziert durch
        $rferencedBy = [];
        $refs = UC_FindReferences($ucID, $instID);
        foreach ($refs as $ref) {
            $objID = $ref['ObjectID'];
            $obj = IPS_GetObject($objID);
            $objectType = $obj['ObjectType'];
            switch ($objectType) {
                case OBJECTTYPE_INSTANCE:
                    $inst = IPS_GetInstance($objID);
                    $moduleType = $inst['ModuleInfo']['ModuleType'];
                    $rferencedBy[] = [
                        'ObjektID'   => $objID,
                        'ObjectType' => $objectType,
                        'ModuleType' => $moduleType,
                        'ObjectArea' => $this->ObjectType2Name($objectType),
                        'ObjectName' => IPS_GetName($objID) . ' (' . IPS_GetName(IPS_GetParent($objID)) . ')',
                    ];
                    break;
                case OBJECTTYPE_SCRIPT:
                    $script = IPS_GetScript($objID);
                    $scriptType = $script['ScriptType'];
                    $rferencedBy[] = [
                        'ObjektID'   => $objID,
                        'ObjectType' => $objectType,
                        'ScriptType' => $scriptType,
                        'ObjectArea' => $this->ScriptType2Name($scriptType),
                        'ObjectName' => IPS_GetName($objID) . ' (' . IPS_GetName(IPS_GetParent($objID)) . '), Zeile ' . $ref['LineNumber'],
                    ];
                    break;
                case OBJECTTYPE_LINK:
                    // skip
                    break;
                default:
                    $this->SendDebug(__FUNCTION__, 'unhandled object=' . print_r($obj, true), 0);
                    break;
            }
        }
        $objIDs = IPS_GetChildrenIDs($instID);
        foreach ($objIDs as $objID) {
            $obj = IPS_GetObject($objID);
            $objectType = $obj['ObjectType'];
            if ($objectType != OBJECTTYPE_EVENT) {
                continue;
            }
            $event = IPS_GetEvent($objID);
            $eventActionID = $event['EventActionID'];
            if (in_array($eventActionID, $actionIDs) == false) {
                continue;
            }
            $eventType = $event['EventType'];
            $rferencedBy[] = [
                'ObjektID'   => $objID,
                'ObjectType' => $objectType,
                'EventType'  => $eventType,
                'ObjectArea' => $this->EventType2Name($eventType),
                'ObjectName' => IPS_GetName($objID),
            ];
        }
        usort($rferencedBy, [__CLASS__, 'cmp_refs']);

        // Verwendung der Statusvariablen
        $rferencedVars = [];
        $objIDs = IPS_GetChildrenIDs($instID);
        foreach ($objIDs as $objID) {
            $obj = IPS_GetObject($objID);
            $objectType = $obj['ObjectType'];
            if ($objectType != OBJECTTYPE_VARIABLE) {
                continue;
            }
            if ($obj['ObjectIdent'] == '') {
                continue;
            }

            $varID = $objID;
            $varIdent = $obj['ObjectIdent'];
            $varName = IPS_GetName($objID);

            $refs = UC_FindReferences($ucID, $objID);
            foreach ($refs as $ref) {
                $chldID = $ref['ObjectID'];
                $chld = IPS_GetObject($chldID);
                $objectType = $chld['ObjectType'];
                switch ($objectType) {
                    case OBJECTTYPE_CATEGORY:
                        $rferencedVars[] = [
                            'VariableID'    => $varID,
                            'VariableIdent' => $varIdent,
                            'VariableName'  => $varName,
                            'ObjektID'      => $chldID,
                            'ObjectType'    => $objectType,
                            'ObjectArea'    => $this->ObjectType2Name($objectType),
                            'ObjectName'    => IPS_GetName($chldID) . ' (' . IPS_GetName(IPS_GetParent($chldID)) . ')',
                        ];
                        break;
                    case OBJECTTYPE_INSTANCE:
                        $inst = IPS_GetInstance($chldID);
                        $moduleType = $inst['ModuleInfo']['ModuleType'];
                        $rferencedVars[] = [
                            'VariableID'    => $varID,
                            'VariableIdent' => $varIdent,
                            'VariableName'  => $varName,
                            'ObjektID'      => $chldID,
                            'ObjectType'    => $objectType,
                            'ModuleType'    => $moduleType,
                            'ObjectArea'    => $this->ObjectType2Name($objectType),
                            'ObjectName'    => IPS_GetName($chldID) . ' (' . IPS_GetName(IPS_GetParent($chldID)) . ')',
                        ];
                        break;
                    case OBJECTTYPE_SCRIPT:
                        $script = IPS_GetScript($chldID);
                        $scriptType = $script['ScriptType'];
                        $rferencedVars[] = [
                            'VariableID'    => $varID,
                            'VariableIdent' => $varIdent,
                            'VariableName'  => $varName,
                            'ObjektID'      => $chldID,
                            'ObjectType'    => $objectType,
                            'ScriptType'    => $scriptType,
                            'ObjectArea'    => $this->ScriptType2Name($scriptType),
                            'ObjectName'    => IPS_GetName($chldID) . ' (' . IPS_GetName(IPS_GetParent($chldID)) . '), Zeile ' . $ref['LineNumber'],
                        ];
                        break;
                    case OBJECTTYPE_EVENT:
                        $event = IPS_GetEvent($chldID);
                        $eventType = $event['EventType'];
                        $rferencedVars[] = [
                            'VariableID'    => $varID,
                            'VariableIdent' => $varIdent,
                            'VariableName'  => $varName,
                            'ObjektID'      => $chldID,
                            'ObjectType'    => $objectType,
                            'EventType'     => $eventType,
                            'ObjectArea'    => $this->EventType2Name($eventType),
                            'ObjectName'    => IPS_GetName($chldID),
                        ];
                        break;
                    default:
                        break;
                }
            }
        }
        usort($rferencedVars, [__CLASS__, 'cmp_refs']);

        // Timer der Instanz
        $rferencedTimer = [];
        $timerList = IPS_GetTimerList();
        foreach ($timerList as $t) {
            $timer = IPS_GetTimer($t);
            if ($timer['InstanceID'] != $this->InstanceID) {
                continue;
            }

            $duration = $this->seconds2duration($timer['Interval'] / 1000);
            if ($duration == '') {
                $duration = '-';
            }
            $ts = $timer['NextRun'];
            $nextRun = $ts > 0 ? date('d.m.Y H:i:s', $ts) : '-';
            $ts = $timer['LastRun'];
            $lastRun = $ts > 0 ? date('d.m.Y H:i:s', $ts) : '-';
            $rferencedTimer[] = [
                'TimerID'  => $timer['TimerID'],
                'Name'     => $timer['Name'],
                'interval' => $duration,
                'nextRun'  => $nextRun,
                'lastRun'  => $lastRun,
            ];
        }
        usort($rferencedTimer, [__CLASS__, 'cmp_Timer']);

        $r = [
            'Referencing'     => $referencing,
            'ReferencedBy'    => $rferencedBy,
            'ReferencedVars'  => $rferencedVars,
            'ReferencedTimer' => $rferencedTimer,
        ];

        return $r;
    }

    private function CommonRequestAction(string $ident, $params)
    {
        $r = false;
        switch ($ident) {
            case 'UpdateFormField':
                $this->SendDebug(__FUNCTION__, 'ident=' . $ident . ', params=' . $params, 0);
                $jparams = json_decode($params, true);
                if (isset($jparams['field']) && isset($jparams['param']) && isset($jparams['value'])) {
                    $this->UpdateFormField($jparams['field'], $jparams['param'], $jparams['value']);
                    $r = true;
                } else {
                    $this->SendDebug(__FUNCTION__, 'params must include field, param, value', 0);
                }
                break;
            case 'UpdateFormData':
                $v = $this->ExplodeReferences($this->InstanceID);
                foreach (['ReferencedBy', 'Referencing', 'ReferencedVars', 'ReferencedTimer'] as $ident) {
                    $this->UpdateFormField($ident, 'values', json_encode($v[$ident]));
                    $this->UpdateFormField($ident, 'rowCount', count($v[$ident]));
                }
                $r = true;
                break;
            default:
                break;
        }
        return $r;
    }

    private function GetModulePrefix()
    {
        $inst = IPS_GetInstance($this->InstanceID);
        $mod = IPS_GetModule($inst['ModuleInfo']['ModuleID']);
        return $mod['Prefix'];
    }

    private function GetReferencesFormAction()
    {
        $v = $this->ExplodeReferences($this->InstanceID);
        // $this->SendDebug(__FUNCTION__, print_r($v, true), 0);

        $onClick_ReferencedBy = 'IPS_RequestAction($id, "UpdateFormField", json_encode(["field" => "openObject_ReferencedBy", "param" => "objectID", "value" => $ReferencedBy["ObjektID"]]));';
        $onClick_Referencing = 'IPS_RequestAction($id, "UpdateFormField", json_encode(["field" => "openObject_Referencing", "param" => "objectID", "value" => $Referencing["ObjektID"]]));';
        $onClick_ReferencedVars = 'IPS_RequestAction($id, "UpdateFormField", json_encode(["field" => "openObject_ReferencedVars", "param" => "objectID", "value" => $ReferencedVars["ObjektID"]]));';
        $rowCount_ReferencedBy = count($v['ReferencedBy']);
        $rowCount_Referencing = count($v['Referencing']);
        $rowCount_ReferencedVars = count($v['ReferencedVars']);
        $rowCount_ReferencedTimer = count($v['ReferencedTimer']);

        $formAction = [
            'type'    => 'ExpansionPanel',
            'caption' => 'References',
            'items'   => [
                [
                    'type'    => 'ColumnLayout',
                    'items'   => [
                        [
                            'type'     => 'List',
                            'name'     => 'ReferencedBy',
                            'columns'  => [
                                [
                                    'name'     => 'ObjektID',
                                    'width'    => '100px',
                                    'caption'  => 'Object',
                                    'onClick'  => $onClick_ReferencedBy,
                                ],
                                [
                                    'name'     => 'ObjectArea',
                                    'width'    => '200px',
                                    'caption'  => 'Area',
                                    'onClick'  => $onClick_ReferencedBy,
                                ],
                                [
                                    'name'     => 'ObjectName',
                                    'width'    => 'auto',
                                    'caption'  => 'Name',
                                    'onClick'  => $onClick_ReferencedBy,
                                ],
                            ],
                            'add'      => false,
                            'delete'   => false,
                            'rowCount' => $rowCount_ReferencedBy > 0 ? $rowCount_ReferencedBy : 1,
                            'values'   => $v['ReferencedBy'],
                            'caption'  => 'Objects using the instance',
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'objectID' => $rowCount_ReferencedBy > 0 ? $v['ReferencedBy'][0]['ObjektID'] : 0,
                            'visible'  => $rowCount_ReferencedBy > 0,
                            'name'     => 'openObject_ReferencedBy',
                            'caption'  => 'Open object',
                        ],
                    ],
                ],
                [
                    'type'    => 'ColumnLayout',
                    'items'   => [
                        [
                            'type'     => 'List',
                            'name'     => 'Referencing',
                            'columns'  => [
                                [
                                    'name'     => 'ObjektID',
                                    'width'    => '100px',
                                    'caption'  => 'Object',
                                    'onClick'  => $onClick_Referencing,
                                ],
                                [
                                    'name'     => 'ObjectArea',
                                    'width'    => '200px',
                                    'caption'  => 'Area',
                                    'onClick'  => $onClick_Referencing,
                                ],
                                [
                                    'name'     => 'ObjectName',
                                    'width'    => 'auto',
                                    'caption'  => 'Name',
                                    'onClick'  => $onClick_Referencing,
                                ],
                            ],
                            'add'      => false,
                            'delete'   => false,
                            'rowCount' => $rowCount_Referencing > 0 ? $rowCount_Referencing : 1,
                            'values'   => $v['Referencing'],
                            'caption'  => 'by instance used objects',
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'objectID' => $rowCount_Referencing > 0 ? $v['Referencing'][0]['ObjektID'] : 0,
                            'visible'  => $rowCount_Referencing > 0,
                            'name'     => 'openObject_Referencing',
                            'caption'  => 'Open object',
                        ],
                    ],
                ],

                [
                    'type'    => 'ColumnLayout',
                    'items'   => [
                        [
                            'type'     => 'List',
                            'name'     => 'ReferencedVars',
                            'columns'  => [
                                [
                                    'name'     => 'VariableID',
                                    'width'    => '100px',
                                    'caption'  => 'Variable',
                                    'onClick'  => $onClick_ReferencedVars,
                                ],
                                [
                                    'name'     => 'VariableIdent',
                                    'width'    => '200px',
                                    'caption'  => 'Ident',
                                    'onClick'  => $onClick_ReferencedVars,
                                ],
                                [
                                    'name'     => 'VariableName',
                                    'width'    => '300px',
                                    'caption'  => 'Name',
                                    'onClick'  => $onClick_ReferencedVars,
                                ],
                                [
                                    'name'     => 'ObjektID',
                                    'width'    => '100px',
                                    'caption'  => 'Object',
                                    'onClick'  => $onClick_ReferencedVars,
                                ],
                                [
                                    'name'     => 'ObjectArea',
                                    'width'    => '200px',
                                    'caption'  => 'Area',
                                    'onClick'  => $onClick_ReferencedVars,
                                ],
                                [
                                    'name'     => 'ObjectName',
                                    'width'    => 'auto',
                                    'caption'  => 'Name',
                                    'onClick'  => $onClick_ReferencedVars,
                                ],
                            ],
                            'add'      => false,
                            'delete'   => false,
                            'rowCount' => $rowCount_ReferencedVars > 0 ? $rowCount_ReferencedVars : 1,
                            'values'   => $v['ReferencedVars'],
                            'caption'  => 'Referenced statusvariables',
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'objectID' => $rowCount_ReferencedVars > 0 ? $v['ReferencedVars'][0]['ObjektID'] : 0,
                            'visible'  => $rowCount_ReferencedVars > 0,
                            'name'     => 'openObject_ReferencedVars',
                            'caption'  => 'Open object',
                        ],
                    ],
                ],

                [
                    'type'    => 'ColumnLayout',
                    'items'   => [
                        [
                            'type'     => 'List',
                            'name'     => 'ReferencedTimer',
                            'columns'  => [
                                [
                                    'name'     => 'TimerID',
                                    'width'    => '100px',
                                    'caption'  => 'Timer',
                                ],
                                [
                                    'name'     => 'Name',
                                    'width'    => '300px',
                                    'caption'  => 'Ident',
                                ],
                                [
                                    'name'     => 'interval',
                                    'width'    => '100px',
                                    'caption'  => 'Interval',
                                ],
                                [
                                    'name'     => 'nextRun',
                                    'width'    => '200px',
                                    'caption'  => 'Next run',
                                ],
                                [
                                    'name'     => 'lastRun',
                                    'width'    => '200px',
                                    'caption'  => 'Last run',
                                ],
                            ],
                            'add'      => false,
                            'delete'   => false,
                            'rowCount' => $rowCount_ReferencedTimer > 0 ? $rowCount_ReferencedTimer : 1,
                            'values'   => $v['ReferencedTimer'],
                            'caption'  => 'Timer information',
                        ],
                    ],
                ],

                [
                    'type'    => 'Button',
                    'caption' => 'Refresh references panel',
                    'onClick' => 'IPS_RequestAction($id, "UpdateFormData", "");',
                ],
            ],
            'onClick' => 'IPS_RequestAction($id, "UpdateFormData", "");',
        ];
        return $formAction;
    }

    private function PushCallChain(string $func)
    {
        if (isset($_IPS['thread']) == false) {
            foreach (IPS_GetScriptThreadList() as $t => $i) {
                $thread = IPS_GetScriptThread($i);
                if ($thread['ThreadID'] == $_IPS['THREAD']) {
                    $_IPS['thread'] = $thread;
                    break;
                }
            }
        }

        $stack = isset($_IPS['stack']) ? $_IPS['stack'] : [];
        $c = count($stack);
        if ($c == 0 || ($stack[$c - 1]['class'] != __CLASS__ || $stack[$c - 1]['func'] != $func)) {
            $stack[] = [
                'class'      => __CLASS__,
                'func'       => $func,
                'InstanceID' => $this->InstanceID,
            ];
        }
        $_IPS['stack'] = $stack;

        $chain = isset($_IPS['chain']) ? $_IPS['chain'] : [];
        if ($chain == []) {
            if (isset($_IPS['VARIABLE'])) {
                array_push($chain, $_IPS['VARIABLE']);
            }
            if (isset($_IPS['EVENT'])) {
                array_push($chain, $_IPS['EVENT']);
            }
            if (isset($_IPS['thread']['ScriptID']) && $_IPS['thread']['ScriptID'] > 0) {
                array_push($chain, $_IPS['thread']['ScriptID']);
            }
        }
        if ($chain == [] || end($chain) != $this->InstanceID) {
            array_push($chain, $this->InstanceID);
        }
        $_IPS['chain'] = $chain;
        // $this->SendDebug(__FUNCTION__, 'func=' . $func . ', environment=' . print_r($_IPS, true), 0);
    }

    private function PopCallChain(string $func)
    {
        $stack = isset($_IPS['stack']) ? $_IPS['stack'] : [];
        $c = count($stack);
        if ($c > 0 && $stack[$c - 1]['class'] == __CLASS__ && $stack[$c - 1]['func'] == $func) {
            array_pop($stack);
        }
        $_IPS['stack'] = $stack;

        $chain = isset($_IPS['chain']) ? $_IPS['chain'] : [];
        if (end($chain) == $this->InstanceID) {
            array_pop($chain);
        }
        $_IPS['chain'] = $chain;
        // $this->SendDebug(__FUNCTION__, 'func=' . $func . ', environment=' . print_r($_IPS, true), 0);
    }

    private function PrintCallChain(bool $complete)
    {
        $cause = isset($_IPS['ENVIRONMENT']) ? $_IPS['ENVIRONMENT'] : $_IPS['SENDER'];
        $chain = isset($_IPS['chain']) ? $_IPS['chain'] : [];
        if ($complete == false && end($chain) == $this->InstanceID) {
            array_pop($chain);
        }

        $chainS = [];
        foreach ($chain as $objID) {
            $obj = IPS_GetObject($objID);
            $objectType = $obj['ObjectType'];
            switch ($objectType) {
                case OBJECTTYPE_CATEGORY:
                case OBJECTTYPE_INSTANCE:
                case OBJECTTYPE_VARIABLE:
                case OBJECTTYPE_SCRIPT:
                case OBJECTTYPE_MEDIA:
                    $chainS[] = $this->ObjectType2Name($objectType) . ' #' . $objID . ' ' . IPS_GetName($objID) . ' (' . IPS_GetName(IPS_GetParent($objID)) . ')';
                    break;
                case OBJECTTYPE_EVENT:
                case OBJECTTYPE_LINK:
                    $chainS[] = $this->ObjectType2Name($objectType) . ' #' . $objID . ' ' . IPS_GetName($objID);
                    break;
                default:
                    break;
            }
        }
        $this->SendDebug(__FUNCTION__, 'cause=' . $cause . ', chain=' . print_r($chainS, true), 0);

        $s = $cause;
        if ($chain != []) {
            $s .= '[' . implode(',', $chain) . ']';
        }
        return $s;
    }

    private function PrintTimer(string $name)
    {
        $timerList = IPS_GetTimerList();
        foreach ($timerList as $t) {
            $timer = IPS_GetTimer($t);
            if ($timer['InstanceID'] != $this->InstanceID) {
                continue;
            }
            if ($timer['Name'] != $name) {
                continue;
            }

            $s = 'timer=' . $timer['Name'] . '(' . $timer['TimerID'] . ')';

            $duration = $this->seconds2duration($timer['Interval'] / 1000);
            if ($duration == '') {
                $duration = '-';
            }
            $s .= ', interval=' . $duration;

            if ($timer['NextRun']) {
                $s .= ', next=' . date('H:i:s', $timer['NextRun']);
            }
            return $s;
        }
        return false;
    }

    private function MaintainTimer(string $name, int $msec)
    {
        $this->SetTimerInterval($name, $msec);
        $this->SendDebug(__FUNCTION__, $this->PrintTimer($name), 0);
    }

    private function version2num(string $version)
    {
        $r = explode('.', $version);
        $num = 0;
        for ($i = 0; $i < 3; $i++) {
            $num *= 1000;
            $num += isset($r[$i]) ? intval($r[$i]) : 0;
        }
        return $num;
    }

    private function version2str($info)
    {
        $s = '';
        if (is_array($info) && isset($info['Version'])) {
            $s .= $info['Version'];
            if (isset($info['Build']) && $info['Build'] > 0) {
                $s .= '#' . $info['Build'];
            }
            if (isset($info['Date']) && $info['Date'] > 0) {
                $s .= ' (' . date('d.m.Y H:i:s', $info['Date']) . ')';
            }
        }
        return $s;
    }

    private function CheckUpdate()
    {
        $inst = IPS_GetInstance($this->InstanceID);
        $mod = IPS_GetModule($inst['ModuleInfo']['ModuleID']);
        $lib = IPS_GetLibrary($mod['LibraryID']);

        @$updateInfo = $this->ReadAttributeString('UpdateInfo');
        $oldInfo = json_decode($updateInfo != false ? $updateInfo : '', true);
        if ($oldInfo == false) {
            $oldInfo = [];
        }
        $oldVersion = $this->version2str($oldInfo);

        $newInfo = [
            'Version' => $lib['Version'],
            'Build'   => $lib['Build'],
            'Date'    => $lib['Date'],
        ];
        $newVersion = $this->version2str($newInfo);

        $m = 'old=' . $oldVersion . ', new=' . $newVersion;

        $eq = $oldVersion == $newVersion;

        if ($eq == true) {
            $this->SendDebug(__FUNCTION__, 'equal version (' . $m . ')', 0);
            return false;
        }

        if (method_exists($this, 'CheckModuleUpdate')) {
            $r = $this->CheckModuleUpdate($oldInfo, $newInfo);
            if ($r != []) {
                $this->SendDebug(__FUNCTION__, 'different version, something todo (' . $m . ')', 0);
                $s = $this->Translate('Still something to do to complete the update') . PHP_EOL;
                foreach ($r as $p) {
                    $s .= '- ' . $p . PHP_EOL;
                }

                $s .= PHP_EOL;
                $s .= PHP_EOL;

                $s .= $this->Translate('old version') . ': ' . ($oldVersion != '' ? $oldVersion : $this->Translate('unknown')) . PHP_EOL;
                $s .= $this->Translate('new version') . ': ' . $newVersion . PHP_EOL;

                $s .= PHP_EOL;
                $s .= PHP_EOL;

                $s .= $this->Translate('Press button \'Complete update\' to carry out the required work');
                return $s;
            }
        }

        $this->SendDebug(__FUNCTION__, 'different version, nothing todo (' . $m . ')', 0);

        $newInfo['tstamp'] = time();
        $this->WriteAttributeString('UpdateInfo', json_encode($newInfo));

        IPS_ApplyChanges($this->InstanceID);

        return false;
    }

    public function CompleteUpdate()
    {
        $inst = IPS_GetInstance($this->InstanceID);
        $mod = IPS_GetModule($inst['ModuleInfo']['ModuleID']);
        $lib = IPS_GetLibrary($mod['LibraryID']);

        @$updateInfo = $this->ReadAttributeString('UpdateInfo');
        $oldInfo = json_decode($updateInfo != false ? $updateInfo : '', true);
        if ($oldInfo == false) {
            $oldInfo = [];
        }
        $oldVersion = $this->version2str($oldInfo);

        $newInfo = [
            'Version' => $lib['Version'],
            'Build'   => $lib['Build'],
            'Date'    => $lib['Date'],
        ];
        $newVersion = $this->version2str($newInfo);

        $m = 'old=' . $oldVersion . ', new=' . $newVersion;

        $eq = $oldVersion == $newVersion;

        if ($eq == true) {
            $this->SendDebug(__FUNCTION__, 'equal version, nothing todo (' . $m . ')', 0);
        } else {
            if (method_exists($this, 'CompleteModuleUpdate')) {
                if ($this->CompleteModuleUpdate($oldInfo, $newInfo) == false) {
                    $this->SendDebug(__FUNCTION__, 'unable to perform update (' . $m . ')', 0);
                    return false;
                }
                $this->SendDebug(__FUNCTION__, 'update completed (' . $m . ')', 0);
            } else {
                $this->SendDebug(__FUNCTION__, 'different version but nothing done (' . $m . ')', 0);
            }
        }

        $newInfo['tstamp'] = time();
        $this->WriteAttributeString('UpdateInfo', json_encode($newInfo));

        IPS_ApplyChanges($this->InstanceID);

        return true;
    }

    private function GetCompleteUpdateFormAction()
    {
        $formAction = [
            'type'    => 'Button',
            'caption' => 'Complete update',
            'onClick' => $this->GetModulePrefix() . '_CompleteUpdate($id);'
        ];
        return $formAction;
    }

    private function CheckPrerequisites()
    {
        $s = '';
        $r = [];

        if (method_exists($this, 'CheckModulePrerequisites')) {
            $r = $this->CheckModulePrerequisites();
        }

        if ($r != []) {
            $s = $this->Translate('The following system prerequisites are missing') . ': ' . PHP_EOL;
            foreach ($r as $p) {
                $s .= '- ' . $p . PHP_EOL;
            }
        }

        return $s;
    }

    private function CheckConfiguration()
    {
        $s = '';
        $r = [];

        if (method_exists($this, 'CheckModuleConfiguration')) {
            $r = $this->CheckModuleConfiguration();
        }

        if ($r != []) {
            $s = $this->Translate('The following points of the configuration are incorrect') . ':' . PHP_EOL;
            foreach ($r as $p) {
                $s .= '- ' . $p . PHP_EOL;
            }
        }

        return $s;
    }

    private function GetCommonFormElements($title)
    {
        $formElements = [];

        $formElements[] = [
            'type'    => 'Label',
            'caption' => $title,
        ];

        if ($this->GetStatus() == self::$IS_UPDATEUNCOMPLETED) {
            $formElements[] = [
                'type'    => 'Label',
                'caption' => $this->CheckUpdate(),
            ];
            return $formElements;
        }

        $inst = IPS_GetInstance($this->InstanceID);
        $mod = IPS_GetModule($inst['ModuleInfo']['ModuleID']);
        if ($mod['ParentRequirements'] != []) {
            if ($this->HasActiveParent() == false) {
                $formElements[] = [
                    'type'    => 'Label',
                    'caption' => 'Instance has no active parent instance',
                ];
            }
        }

        @$s = $this->CheckConfiguration();
        if ($s != '') {
            $formElements[] = [
                'type'    => 'Label',
                'caption' => $s,
            ];
            $formElements[] = [
                'type'    => 'Label',
            ];
        }

        @$s = $this->CheckPrerequisites();
        if ($s != '') {
            $formElements[] = [
                'type'    => 'Label',
                'caption' => $s,
            ];
            $formElements[] = [
                'type'    => 'Label',
            ];
        }

        return $formElements;
    }

    public static $IS_INVALIDPREREQUISITES = IS_EBASE + 1;
    public static $IS_UPDATEUNCOMPLETED = IS_EBASE + 2;
    public static $IS_INVALIDCONFIG = IS_EBASE + 3;
    public static $IS_DEACTIVATED = IS_EBASE + 4;

    private function GetCommonFormStatus()
    {
        $formStatus = [
            ['code' => IS_CREATING, 'icon' => 'inactive', 'caption' => 'Instance getting created'],
            ['code' => IS_ACTIVE, 'icon' => 'active', 'caption' => 'Instance is active'],
            ['code' => IS_DELETING, 'icon' => 'inactive', 'caption' => 'Instance is deleted'],
            ['code' => IS_INACTIVE, 'icon' => 'inactive', 'caption' => 'Instance is inactive'],
            ['code' => IS_NOTCREATED, 'icon' => 'inactive', 'caption' => 'Instance is not created'],

            ['code' => self::$IS_INVALIDPREREQUISITES, 'icon' => 'error', 'caption' => 'Instance is inactive (invalid preconditions)'],
            ['code' => self::$IS_UPDATEUNCOMPLETED, 'icon' => 'error', 'caption' => 'Instance is inactive (update not completed)'],
            ['code' => self::$IS_INVALIDCONFIG, 'icon' => 'error', 'caption' => 'Instance is inactive (invalid configuration)'],
            ['code' => self::$IS_DEACTIVATED, 'icon' => 'inactive', 'caption' => 'Instance is inactive (deactivated)'],
        ];

        return $formStatus;
    }
}
