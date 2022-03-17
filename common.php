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
        $this->SendDebug(__FUNCTION__, 'newHook=' . $ident, 0);
        $used = false;
        $instID = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}')[0];
        $hooks = json_decode(IPS_GetProperty($instID, 'Hooks'), true);
        $this->SendDebug(__FUNCTION__, 'Hooks=' . print_r($hooks, true), 0);
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
        $this->SendDebug(__FUNCTION__, 'WebHook=' . $ident, 0);
        $ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
        if (count($ids) > 0) {
            $hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
            $this->SendDebug(__FUNCTION__, 'Hooks=' . print_r($hooks, true), 0);
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

    private function GetArrayElem(array $data, string $var, $dflt)
    {
        $ret = $data;
        $vs = explode('.', $var);
        foreach ($vs as $v) {
            if (!isset($ret[$v])) {
                $ret = $dflt;
                break;
            }
            $ret = $ret[$v];
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

    private function TranslateFormat(string $str, array $vars = null)
    {
        $str = $this->Translate($str);
        if ($vars != null) {
            $str = strtr($str, $vars);
        }
        return $str;
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

        $this->SendDebug(__FUNCTION__, implode(', ', $m), 0);
        return $s;
    }

    private function GetInformationForm()
    {
        $form = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Information',
            'items'   => [
                [
                    'type'    => 'Label',
                    'caption' => $this->InstanceInfo($this->InstanceID),
                ],
            ],
        ];
        return $form;
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
                            'ObjectName'    => IPS_GetName($objID) . ' (' . IPS_GetName(IPS_GetParent($objID)) . '), Zeile ' . $ref['LineNumber'],
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

        $r = [
            'Referencing'    => $referencing,
            'ReferencedBy'   => $rferencedBy,
            'ReferencedVars' => $rferencedVars,
        ];

        return $r;
    }

    private function CommonRequestAction(string $ident, $params)
    {
        $this->SendDebug(__FUNCTION__, 'ident=' . $ident . ', params=' . $params, 0);

        $r = false;
        switch ($ident) {
            case 'UpdateFormField':
                $jparams = json_decode($params, true);
                if (isset($jparams['field']) && isset($jparams['param']) && isset($jparams['value'])) {
                    $this->UpdateFormField($jparams['field'], $jparams['param'], $jparams['value']);
                    $r = true;
                } else {
                    $this->SendDebug(__FUNCTION__, 'params must include field, param, value', 0);
                }
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

    private function GetReferencesForm()
    {
        $r = $this->ExplodeReferences($this->InstanceID);
        $this->SendDebug(__FUNCTION__, print_r($r, true), 0);

        $onClick_ReferencedBy = 'IPS_RequestAction($id, "UpdateFormField", json_encode(["field" => "openObject_ReferencedBy", "param" => "objectID", "value" => $ReferencedBy["ObjektID"]]));';
        $onClick_Referencing = 'IPS_RequestAction($id, "UpdateFormField", json_encode(["field" => "openObject_Referencing", "param" => "objectID", "value" => $Referencing["ObjektID"]]));';
        $onClick_ReferencedVars = 'IPS_RequestAction($id, "UpdateFormField", json_encode(["field" => "openObject_ReferencedVars", "param" => "objectID", "value" => $ReferencedVars["ObjektID"]]));';
        $rowCount_ReferencedBy = count($r['ReferencedBy']);
        $rowCount_Referencing = count($r['Referencing']);
        $rowCount_ReferencedVars = count($r['ReferencedVars']);

        $form = [
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
                            'values'   => $r['ReferencedBy'],
                            'caption'  => 'Objects using the instance',
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'objectID' => $rowCount_ReferencedBy > 0 ? $r['ReferencedBy'][0]['ObjektID'] : 0,
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
                            'values'   => $r['Referencing'],
                            'caption'  => 'by instance used objects',
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'objectID' => $rowCount_Referencing > 0 ? $r['Referencing'][0]['ObjektID'] : 0,
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
                            'values'   => $r['ReferencedVars'],
                            'caption'  => 'Referenced statusvariables',
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'objectID' => $rowCount_ReferencedVars > 0 ? $r['ReferencedVars'][0]['ObjektID'] : 0,
                            'visible'  => $rowCount_ReferencedVars > 0,
                            'name'     => 'openObject_ReferencedVars',
                            'caption'  => 'Open object',
                        ],
                    ],
                ],

            ],
        ];
        return $form;
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
}
