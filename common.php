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

    protected function GetValueFormatted($ident)
    {
        @$varID = $this->GetIDForIdent($ident);
        if ($varID == false) {
            $this->SendDebug(__FUNCTION__, 'missing variable ' . $ident, 0);
            return false;
        }

        $ret = GetValueFormatted($varID);
        return $ret;
    }

    private function CreateVarProfile(string $ident, int $varType, string $suffix, float $min, float $max, float $stepSize, int $digits, string $icon, $associations = null, bool $doReinstall)
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
                    if ($clientID['TargetID'] != $this->InstanceID) {
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

    private function seconds2duration(float $val)
    {
        $sec = (int) $val;
        $msec = ($val * 1000) % 1000;

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
            $duration .= sprintf('%ds', (int) $sec);
            $sec = floor($sec);
        }
        if ($msec > 0) {
            $duration .= sprintf('%dms', $msec);
        }
        return $duration;
    }

    private function int2bitmap(int $val, int $n)
    {
        $s = '';
        for ($i = $n - 1; $i >= 0; $i--) {
            $x = 1 << $i;
            $s .= ($val & $x) == $x ? '1' : '0';
        }
        return $s;
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

    private $translationCache = '';

    private function GetTranslations()
    {
        $translations = $this->translationCache;
        if ($translations == '') {
            $translations = [];
            foreach (['/../libs/CommonStubs/', '/../libs/', '/'] as $subdir) {
                $m = '';
                $filename = $this->ModuleDir . $subdir . 'translation.json';
                if (file_exists($filename)) {
                    $s = file_get_contents($filename);
                    if ($s != false) {
                        $j = json_decode($s, true);
                        if (is_array($j)) {
                            if (isset($j['translations'])) {
                                $translations = array_replace_recursive($translations, $j['translations']);
                            } else {
                                $m .= 'invalid format';
                            }
                        } else {
                            $m .= 'unable to decode json';
                        }
                    } else {
                        $m .= 'empty';
                    }
                } else {
                    $m .= 'not found';
                }
                if ($m != '') {
                    $this->SendDebug(__FUNCTION__, 'filename=' . $filename . ' - ' . $m, 0);
                }
            }
            // $this->SendDebug(__FUNCTION__, 'translations=' . print_r($translations, true), 0);
            $this->translationCache = $translations;
        }
        return $translations;
    }

    private function GetTranslationInfo()
    {
        if (IPS_GetKernelVersion() >= 6.1) {
            $lang = IPS_GetSystemLanguage();
        } else {
            $lang = isset($_ENV['LANG']) ? $_ENV['LANG'] : '';
            if (preg_match('/([^\.]*)\..*/', $lang, $r)) {
                $lang = $r[1];
            }
        }
        $code = explode('_', $lang)[0];

        $translations = $this->GetTranslations();
        if ($translations != false) {
            $s = 'missing';
            if (isset($translations[$lang])) {
                $s = $lang;
            } else {
                if (isset($translations[$code])) {
                    $s = $code;
                } elseif ($code == 'en') {
                    $s = 'not required';
                }
            }
        } else {
            $s = 'corrupt';
        }
        return $s . ' (lang=' . $lang . ')';
        return $s;
    }

    public function Translate($text)
    {
        if ($text == '') {
            return $text;
        }

        $b = false;
        $translations = $this->GetTranslations();
        if ($translations != false) {
            if (IPS_GetKernelVersion() >= 6.1) {
                $lang = IPS_GetSystemLanguage();
            } else {
                $lang = isset($_ENV['LANG']) ? $_ENV['LANG'] : '';
                if (preg_match('/([^\.]*)\..*/', $lang, $r)) {
                    $lang = $r[1];
                }
            }
            if (isset($translations[$lang][$text])) {
                $text = $translations[$lang][$text];
                $b = true;
            } else {
                $code = explode('_', $lang)[0];
                if (isset($translations[$code][$text])) {
                    $text = $translations[$code][$text];
                    $b = true;
                } elseif ($code == 'en') {
                    $b = true;
                }
            }
        }
        if ($b == false) {
            $this->SendDebug(__FUNCTION__, 'unable to translate "' . $text . '"', 0);
        }
        return $text;
    }

    private function TranslateFormat(string $text, array $vars = null)
    {
        $s = $this->Translate($text);
        if ($vars != null) {
            $s = strtr($s, $vars);
        }
        return $s;
    }

    private function GetSystemLocation()
    {
        $id = IPS_GetInstanceListByModuleID('{45E97A63-F870-408A-B259-2933F7EABF74}')[0];
        return json_decode(IPS_GetProperty($id, 'Location'), true);
    }

    private function GetConfiguratorLocation(int $catID)
    {
        $tree_position = [];
        if ($catID > 0 && IPS_CategoryExists($catID)) {
            $tree_position[] = IPS_GetName($catID);
            $parID = IPS_GetObject($catID)['ParentID'];
            while ($parID > 0) {
                $tree_position[] = IPS_GetName($parID);
                $parID = IPS_GetObject($parID)['ParentID'];
            }
            $tree_position = array_reverse($tree_position);
        }
        return $tree_position;
    }

    public function GetConfigurationForm()
    {
        $formElements = $this->GetFormElements();
        $formActions = $this->GetFormActions();
        $formStatus = $this->GetFormStatus();
        $translations = $this->GetTranslations();

        $jform = [
            'elements'     => $formElements,
            'actions'      => $formActions,
            'status'       => $formStatus,
            'translations' => $translations,
        ];
        $form = json_encode($jform);
        if ($form == '') {
            $this->SendDebug(__FUNCTION__, 'json_error=' . json_last_error_msg(), 0);
            $this->SendDebug(__FUNCTION__, '=> elements=' . print_r($formElements, true), 0);
            $this->SendDebug(__FUNCTION__, '=> actions=' . print_r($formActions, true), 0);
            $this->SendDebug(__FUNCTION__, '=> status=' . print_r($formStatus, true), 0);
            $this->SendDebug(__FUNCTION__, '=> translations=' . print_r($translations, true), 0);
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

        $this->SendDebug(__FUNCTION__, implode(', ', $m) . ', translation=' . $this->GetTranslationInfo(), 0);
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

    private function GetInstallVarProfilesFormItem()
    {
        $item = [
            'type'    => 'Button',
            'caption' => 'Re-install variable-profiles',
            'onClick' => 'IPS_RequestAction($id, "InstallVarProfiles", "");'
        ];
        return $item;
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

    private function IsValidID($id)
    {
        return $id >= 10000;
    }

    private function MaintainReferences(array $propertyNames = null)
    {
        $refs = $this->GetReferenceList();
        foreach ($refs as $ref) {
            $this->UnregisterReference($ref);
        }
        if (is_array($propertyNames)) {
            foreach ($propertyNames as $name) {
                $oid = $this->ReadPropertyInteger($name);
                if ($this->IsValidID($oid)) {
                    $this->RegisterReference($oid);
                }
            }
        }
    }

    private function MaintainReferences4Script($text)
    {
        if ($text == '') {
            return;
        }
        $lines = explode(PHP_EOL, $text);
        foreach ($lines as $line) {
            $patternV = [
                '/[^!=><]=[\t ]*([0-9]{5})[^0-9]/',
                '/[\t ]*=[\t ]*([0-9]{5})[^0-9]/',
                '/\([\t ]*([0-9]{5})[^0-9]/',
            ];
            foreach ($patternV as $pattern) {
                if (preg_match_all($pattern, $line, $r)) {
                    foreach ($r[1] as $id) {
                        if ($this->IsValidID($id)) {
                            $this->RegisterReference($id);
                        }
                    }
                }
            }
        }
    }

    private function MaintainReferences4Action($action)
    {
        if (is_array($action) == false) {
            @$action = json_decode($action, true);
        }
        if (is_array($action)) {
            if (isset($action['parameters']['TARGET'])) {
                $objID = $action['parameters']['TARGET'];
                if ($this->IsValidID($objID)) {
                    $this->RegisterReference($objID);
                }
            }
            if (isset($action['parameters']['SCRIPT'])) {
                $text = $action['parameters']['SCRIPT'];
                $this->MaintainReferences4Script($text);
            }
        }
    }

    private function UnregisterMessages(array $messagesIds)
    {
        $messageList = $this->GetMessageList();
        foreach ($messageList as $objId => $msgIds) {
            foreach ($msgIds as $msgId) {
                if (in_array($msgId, $messagesIds)) {
                    $this->UnregisterMessage($objId, $msgId);
                }
            }
        }
    }

    private function RegisterObjectMessages(array $objIDs, array $messagesIds)
    {
        $messageBases = [
            OBJECTTYPE_INSTANCE => IPS_INSTANCEMESSAGE,
            OBJECTTYPE_VARIABLE => IPS_VARIABLEMESSAGE,
            OBJECTTYPE_SCRIPT   => IPS_SCRIPTMESSAGE,
            OBJECTTYPE_EVENT    => IPS_EVENTMESSAGE,
            OBJECTTYPE_MEDIA    => IPS_MEDIAMESSAGE,
            OBJECTTYPE_LINK     => IPS_LINKMESSAGE,
        ];

        foreach ($objIDs as $objID) {
            if (IPS_ObjectExists($objID)) {
                $obj = IPS_GetObject($objID);
                $objType = $obj['ObjectType'];

                if (isset($messageBases[$objType])) {
                    $messageBase = $messageBases[$objType];
                    foreach ($messagesIds as $messageId) {
                        if ($messageId > $messageBase && $messageId < ($messageBase + 100)) {
                            $this->RegisterMessage($objID, $messageId);
                        }
                    }
                }
            }
        }
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
            if (IPS_ObjectExists($objID) == false) {
                continue;
            }
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
            if (IPS_ObjectExists($objID) == false) {
                continue;
            }
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
            if (IPS_ObjectExists($objID) == false) {
                continue;
            }
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
            if (IPS_ObjectExists($objID) == false) {
                continue;
            }
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

    private function PopupMessage(string $text)
    {
        $this->UpdateFormField('MessagePopup_text', 'caption', $text);
        $this->UpdateFormField('MessagePopup', 'visible', true);
    }

    private function CommonRequestAction(string $ident, $params)
    {
        $r = false;
        switch ($ident) {
            case 'UpdateFormField':
                $jparams = json_decode($params, true);
                if (isset($jparams['field']) && isset($jparams['param']) && isset($jparams['value'])) {
                    $this->UpdateFormField($jparams['field'], $jparams['param'], $jparams['value']);
                } else {
                    $this->SendDebug(__FUNCTION__, 'params must include field, param, value', 0);
                }
                $r = true;
                break;
            case 'UpdateFormData':
                $jparams = json_decode($params, true);
                if (isset($jparams['area'])) {
                    switch ($jparams['area']) {
                        case 'References':
                            $v = $this->ExplodeReferences($this->InstanceID);
                            foreach (['ReferencedBy', 'Referencing', 'ReferencedVars', 'ReferencedTimer'] as $ident) {
                                $this->UpdateFormField($ident, 'values', json_encode($v[$ident]));
                                $this->UpdateFormField($ident, 'rowCount', count($v[$ident]) > 0 ? count($v[$ident]) : 1);
                            }
                            break;
                        case 'ModuleActivity':
                            $logV = $this->ReadModuleActivity();
                            $this->UpdateFormField('ModuleActivity', 'values', json_encode($logV));
                            $this->UpdateFormField('ModuleActivity', 'rowCount', count($logV) > 0 ? count($logV) : 1);
                            break;
                        default:
                            $this->SendDebug(__FUNCTION__, 'unsupported area ' . $jparams['area'], 0);
                            break;
                    }
                }
                $r = true;
                break;
            case 'InstallVarProfiles':
                $this->InstallVarProfiles(true);
                $r = true;
                break;
            case 'ShowApiCallStats':
                $this->ShowApiCallStats();
                $r = true;
                break;
            case 'PopupMessage':
                $this->PopupMessage($params);
                $r = true;
                break;
            case 'CompleteUpdate':
                $this->CompleteUpdate();
                $r = true;
                break;
            default:
                break;
        }
        return $r;
    }

    private function GetModuleDir()
    {
        return $this->ModuleDir;
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
                    'caption' => 'Refresh panel',
                    'onClick' => 'IPS_RequestAction($id, "UpdateFormData", json_encode(["area" => "References"]));',
                ],
            ],
            'onClick' => 'IPS_RequestAction($id, "UpdateFormData", json_encode(["area" => "References"]));',
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
        // $this->SendDebug(__FUNCTION__, 'func=' . $func . ', _IPS=' . print_r($_IPS, true), 0);
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
        // $this->SendDebug(__FUNCTION__, 'func=' . $func . ', _IPS=' . print_r($_IPS, true), 0);
    }

    private function PrintCallChain(bool $complete)
    {
        $cause = isset($_IPS['ENVIRONMENT']) ? $_IPS['ENVIRONMENT'] : $_IPS['SENDER'];
        if (in_array($cause, ['PHPModule', 'RunScript'])) {
            $stack = isset($_IPS['stack']) ? $_IPS['stack'] : [];
            $c = count($stack);
            if ($c > 0) {
                $cause = $stack[$c - 1]['func'];
            }
        }

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

    private function GetTimerByName(string $name)
    {
        $timerList = IPS_GetTimerList();
        foreach ($timerList as $t) {
            $timer = IPS_GetTimer($t);
            if ($timer['InstanceID'] == $this->InstanceID && $timer['Name'] == $name) {
                return $timer;
            }
        }
        return false;
    }

    private function PrintTimer(string $name)
    {
        $s = '';
        $timer = $this->GetTimerByName($name);
        if ($timer != false) {
            $s = 'timer=' . $timer['Name'] . '(' . $timer['TimerID'] . ')';

            $duration = $this->seconds2duration($timer['Interval'] / 1000);
            if ($duration == '') {
                $duration = '-';
            }
            $s .= ', interval=' . $duration;

            $ts = $timer['NextRun'];
            if ($ts) {
                if ($timer['Running']) {
                    $ts = time() + ($timer['Interval'] / 1000);
                }
                $s .= ', next=' . date('H:i:s', $ts);
            }
        }
        return $s;
    }

    private function MaintainTimer(string $name, int $msec)
    {
        if ($msec < 0) {
            $this->SendDebug(__FUNCTION__, 'timer ' . $name . ' is to be set ' . $msec . 'ms - this is not permitted', 0);
            $msec = 0;
        }
        $this->SetTimerInterval($name, $msec);
        $this->SendDebug(__FUNCTION__, $this->PrintTimer($name), 0);
    }

    private function MaintainStatus(int $status)
    {
        if ($this->GetStatus() != $status) {
            $this->SetStatus($status);
            $this->SendDebug(__FUNCTION__, 'change status to ' . $this->GetStatus() . '(' . $this->GetStatusText() . ')', 0);
        }
    }

    private function version2num($version)
    {
        if (is_array($version)) {
            $version = isset($version['Version']) ? $version['Version'] : '';
        }
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
            $this->SendDebug(__FUNCTION__, 'no old version saved, force current', 0);
            $oldInfo = [
                'Version' => $lib['Version'],
                'Build'   => $lib['Build'],
                'Date'    => $lib['Date'],
                'tstamp'  => time(),
            ];
            $this->WriteAttributeString('UpdateInfo', json_encode($oldInfo));
            return false;
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

                $s .= $this->Translate('The use of possible affected status variables can be checked in the expansion panel "References"') . PHP_EOL;
                $s .= PHP_EOL;

                $s .= $this->Translate('Press button \'Complete update\' to carry out the required work');
                return $s;
            }
        }

        $this->SendDebug(__FUNCTION__, 'different version, nothing todo (' . $m . ')', 0);

        $newInfo['tstamp'] = time();
        $this->WriteAttributeString('UpdateInfo', json_encode($newInfo));

        return false;
    }

    private function CompleteUpdate()
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
                $r = $this->CompleteModuleUpdate($oldInfo, $newInfo);
                if ($r != false) {
                    $this->SendDebug(__FUNCTION__, 'unable to complete the update (' . $m . ') => ' . $r, 0);

                    $s = $this->Translate('Unable to complete the update') . PHP_EOL;
                    $s .= PHP_EOL;
                    $s .= $r . PHP_EOL;
                    $s .= PHP_EOL;
                    $s .= PHP_EOL;
                    $s .= $this->Translate('old version') . ': ' . ($oldVersion != '' ? $oldVersion : $this->Translate('unknown')) . PHP_EOL;
                    $s .= $this->Translate('new version') . ': ' . $newVersion . PHP_EOL;
                    $s .= PHP_EOL;
                    $s .= PHP_EOL;
                    $s .= $this->Translate('please contact the author') . PHP_EOL;
                    $this->RequestAction('PopupMessage', $s);

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
            'onClick' => 'IPS_RequestAction($id, "CompleteUpdate", "");',
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
            'type'    => 'PopupAlert',
            'name'    => 'MessagePopup',
            'visible' => false,
            'popup'   => [
                'items'   => [
                    [
                        'type'    => 'Label',
                        'name'    => 'MessagePopup_text',
                        'caption' => '',
                    ],
                ],
            ],
        ];

        if (method_exists($this, 'GetBrandImage')) {
            $formElements[] = [
                'type'  => 'Image',
                'image' => 'data:image/png;base64,' . $this->GetBrandImage()
            ];
        }

        if ($title != '') {
            $formElements[] = [
                'type'    => 'Label',
                'caption' => $title,
            ];
        }

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
    public static $IS_NOSYMCONCONNECT = IS_EBASE + 4;

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
            ['code' => self::$IS_NOSYMCONCONNECT, 'icon' => 'error', 'caption' => 'Instance is inactive (no Symcon-Connect)']
        ];

        return $formStatus;
    }

    private function ReadModuleActivity()
    {
        $logV = json_decode($this->GetBuffer('moduleActivity'), true);
        if ($logV == false) {
            $logV = [];
        }
        return $logV;
    }

    private function AddModuleActivity(string $log, int $maxLen = 20)
    {
        $oldLogV = $this->ReadModuleActivity();
        $newLogV = [];
        if ($log != '') {
            $newLogV[] = [
                'tstamp' => date('d.m.Y H:i:s', time()),
                'log'    => $log,
            ];
        }
        foreach ($oldLogV as $ent) {
            if (strlen(json_encode($newLogV)) + strlen(json_encode($ent)) > 8000) {
                break;
            }
            $newLogV[] = $ent;
            if (count($newLogV) == $maxLen) {
                break;
            }
        }
        // $this->SendDebug(__FUNCTION__, 'log=' . $log . ', newLogV='. print_r($newLogV, true), 0);
        $this->SetBuffer('moduleActivity', json_encode($newLogV));
    }

    private function GetModuleActivityFormAction()
    {
        $logV = $this->ReadModuleActivity();

        $formAction = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Module activity',
            'items'   => [
                [
                    'type'     => 'List',
                    'name'     => 'ModuleActivity',
                    'columns'  => [
                        [
                            'name'     => 'tstamp',
                            'width'    => '160px',
                            'caption'  => 'Timestamp',
                        ],
                        [
                            'name'     => 'log',
                            'width'    => 'auto',
                            'caption'  => 'Activity',
                        ],
                    ],
                    'add'      => false,
                    'delete'   => false,
                    'rowCount' => count($logV) > 0 ? count($logV) : 1,
                    'values'   => $logV,
                    'caption'  => '',
                ],

                [
                    'type'    => 'Button',
                    'caption' => 'Refresh panel',
                    'onClick' => 'IPS_RequestAction($id, "UpdateFormData", json_encode(["area" => "ModuleActivity"]));',
                ],
            ],
            'onClick' => 'IPS_RequestAction($id, "UpdateFormData", json_encode(["area" => "ModuleActivity"]));',
        ];
        return $formAction;
    }

    private function SetVariableLogging(string $ident, int $aggregationType)
    {
        @$varID = $this->GetIDForIdent($ident);
        if ($varID == false) {
            $this->SendDebug(__FUNCTION__, 'missing variable ' . $ident, 0);
            return false;
        }
        $archivID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
        $reAggregate = AC_GetLoggingStatus($archivID, $varID) == false || AC_GetAggregationType($archivID, $varID) != $aggregationType;
        AC_SetLoggingStatus($archivID, $varID, true);
        AC_SetAggregationType($archivID, $varID, $aggregationType);
        if ($reAggregate) {
            AC_ReAggregateVariable($archivID, $varID);
        }
    }

    private function UnsetVariableLogging(string $ident)
    {
        @$varID = $this->GetIDForIdent($ident);
        if ($varID == false) {
            $this->SendDebug(__FUNCTION__, 'missing variable ' . $ident, 0);
            return false;
        }
        $archivID = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}')[0];
        AC_SetLoggingStatus($archivID, $varID, false);
    }

    private function ApiCallsSetInfo(array $limits, string $notes)
    {
        $stats = json_decode($this->ReadAttributeString('ApiCallStats'), true);
        if ($stats == false) {
            $stats = [
                'tstamps' => [],
                'entries' => [],
            ];
        }
        $stats['limits'] = $limits;
        $stats['notes'] = $notes;
        $this->WriteAttributeString('ApiCallStats', json_encode($stats));
    }

    private function ApiCallsCollect(string $url, string $err, int $statuscode)
    {
        if (preg_match('!(^[^:]*://([^@]*@|)([^?]*|.*)|([^@]*@|)([^?]*|.*))!', $url, $r) && count($r)) {
            $uri = $r[count($r) - 1];
        } else {
            $uri = '';
        }

        $callerID = isset($_IPS['CallerID']) ? $_IPS['CallerID'] : $this->InstanceID;
        $this->SendDebug(__FUNCTION__, 'caller=' . $callerID . '(' . IPS_GetName($callerID) . '), uri=' . $uri . ', err=' . $err . ', statuscode=' . $statuscode, 0);

        $now = time();
        $stats = json_decode($this->ReadAttributeString('ApiCallStats'), true);
        if ($stats == false) {
            $stats = [];
        }

        $month_tstamp = (int) $this->GetArrayElem($stats, 'tstamps.month', 0);
        $day_tstamp = (int) $this->GetArrayElem($stats, 'tstamps.day', 0);
        $hour_tstamp = (int) $this->GetArrayElem($stats, 'tstamps.hour', 0);

        $total_ref_tstamp = (int) $this->GetArrayElem($stats, 'tstamps.total', time());
        $month_ref_tstamp = strtotime(date('01.m.Y 00:00:00', $now));
        $day_ref_tstamp = strtotime(date('d.m.Y 00:00:00', $now));
        $hour_ref_tstamp = strtotime(date('d.m.Y H:00:00', $now));

        $stats_new = [
            'tstamps' => [
                'total' => $total_ref_tstamp,
                'month' => $month_ref_tstamp,
                'day'   => $day_ref_tstamp,
                'hour'  => $hour_ref_tstamp,
            ],
            'entries' => [],
            'limits'  => (array) $this->GetArrayElem($stats, 'limits', []),
        ];

        $entries = (array) $this->GetArrayElem($stats, 'entries', []);
        $entries_new = [];

        $b = false;
        foreach ($entries as $entry) {
            if ($month_ref_tstamp != $month_tstamp) {
                $entry['last_month'] = (int) $this->GetArrayElem($entry, 'month', 0);
                $entry['month'] = 0;
            }
            if ($day_ref_tstamp != $day_tstamp) {
                $entry['last_day'] = (int) $this->GetArrayElem($entry, 'day', 0);
                $entry['day'] = 0;
            }
            if ($hour_ref_tstamp != $hour_tstamp) {
                $entry['last_hour'] = (int) $this->GetArrayElem($entry, 'hour', 0);
                $entry['hour'] = 0;
            }
            if ($entry['uri'] == $uri && $entry['err'] == $err && $entry['callerID'] == $callerID) {
                $entry['total'] = $entry['total'] + 1;
                $entry['month'] = $entry['month'] + 1;
                $entry['day'] = $entry['day'] + 1;
                $entry['hour'] = $entry['hour'] + 1;
                $b = true;
            }
            $entries_new[] = $entry;
        }

        if ($b == false) {
            $entry = [
                'callerID'   => $callerID,
                'uri'        => $uri,
                'err'        => $err,
                'total'      => 1,
                'month'      => 1,
                'day'        => 1,
                'hour'       => 1,
                'last_month' => 0,
                'last_day'   => 0,
                'last_hour'  => 0,
            ];
            $entries_new[] = $entry;
        }

        $stats_new['entries'] = $entries_new;

        $this->SendDebug(__FUNCTION__, 'ApiCalls=' . print_r($stats_new, true), 0);

        $this->WriteAttributeString('ApiCallStats', json_encode($stats_new));
    }

    private function GetApiCallStatsFormItem()
    {
        $item = [
            'type'    => 'Button',
            'caption' => 'Show API call statistics',
            'onClick' => 'IPS_RequestAction($id, "ShowApiCallStats", "");'
        ];
        return $item;
    }

    private function ShowApiCallStats()
    {
        $stats = json_decode($this->ReadAttributeString('ApiCallStats'), true);
        if ($stats == false) {
            $msg = 'no collected data';
            $this->RequestAction('PopupMessage', $msg);
            return;
        }

        $entries = $stats['entries'];

        $callerIDs = [0];
        foreach ($entries as $entry) {
            if (array_search($entry['callerID'], $callerIDs) == false) {
                $callerIDs[] = $entry['callerID'];
            }
        }

        $msg = 'Statistics of API calls';
        if (isset($stats['tstamps']['total'])) {
            $msg .= ' (since ' . date('d.m.y H:i:s', $stats['tstamps']['total']) . ')';
        }
        $msg .= PHP_EOL;
        $msg .= PHP_EOL;

        foreach ($callerIDs as $callerID) {
            if ($callerID) {
                $msg .= $callerID . '(' . IPS_GetName($callerID) . ')' . PHP_EOL;
            } else {
                $msg .= 'all instances' . PHP_EOL;
            }

            $total_ok = $total_err = 0;
            $month_ok = $month_err = $last_month_ok = $last_month_err = 0;
            $day_ok = $day_err = $last_day_ok = $last_day_err = 0;
            $hour_ok = $hour_err = $last_hour_ok = $last_hour_err = 0;

            foreach ($entries as $entry) {
                if ($callerID && $entry['callerID'] != $callerID) {
                    continue;
                }
                if ($entry['err'] == '') {
                    $total_ok += $entry['total'];
                    $month_ok += $entry['month'];
                    $day_ok += $entry['day'];
                    $hour_ok += $entry['hour'];
                    $last_month_ok += $entry['last_month'];
                    $last_day_ok += $entry['last_day'];
                    $last_hour_ok += $entry['last_hour'];
                } else {
                    $total_err += $entry['total'];
                    $month_err += $entry['month'];
                    $day_err += $entry['day'];
                    $hour_err += $entry['hour'];
                    $last_month_err += $entry['last_month'];
                    $last_day_err += $entry['last_day'];
                    $last_hour_err += $entry['last_hour'];
                }
            }

            $msg .= '- total......... ok=' . $total_ok . '/err=' . $total_err . PHP_EOL;

            $m = [];
            $m[] = 'month: ok=' . $month_ok . '/err=' . $month_err;
            $m[] = 'day: ok=' . $day_ok . '/err=' . $day_err;
            $m[] = 'hour: ok=' . $hour_ok . '/err=' . $hour_err;
            $msg .= '- current..... ' . implode('; ', $m) . PHP_EOL;

            $m = [];
            $m[] = 'month: ok=' . $last_month_ok . '/err=' . $last_month_err;
            $m[] = 'day: ok=' . $last_day_ok . '/err=' . $last_day_err;
            $m[] = 'hour: ok=' . $last_hour_ok . '/err=' . $last_hour_err;
            $msg .= '- previous... ' . implode('; ', $m) . PHP_EOL;

            $msg .= PHP_EOL;
        }

        $limits = (array) $this->GetArrayElem($stats, 'limits', []);
        if ($limits != []) {
            $msg .= PHP_EOL;
            $msg .= 'known limitations:' . PHP_EOL;
            foreach ($limits as $limit) {
                $value = $this->GetArrayElem($limit, 'value', 0);
                $unit = $this->GetArrayElem($limit, 'unit', '');
                if ($value > 0) {
                    $msg .= '- ' . $value . ' per ' . $unit . PHP_EOL;
                }
            }
        }

        $notes = $this->GetArrayElem($stats, 'notes', '');
        if ($notes != '') {
            $msg .= PHP_EOL;
            $msg .= 'additional informations:' . PHP_EOL;
			$msg .= $notes . PHP_EOL;
		}

        $this->RequestAction('PopupMessage', $msg);
    }
}
