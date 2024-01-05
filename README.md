# CommonStubs

## trait StubsCommonLib

`bool SetValue(string $ident, mixed $value)`<br>
`bool SaveValue(string $ident, mixed $value, bool &$isChanged)`<br>
`mixed GetValue(string $ident)`<br>
`mixed GetValueFormatted(string $ident)`<br>
<br>

`void CreateVarProfile(string $ident, int $varType, string $suffix, float $min, float $max, float $stepSize, int $digits, string $icon, $associations = null, bool $doReinstall)`<br>
`string CheckVarProfile4Value(string $ident, mixed $value)`<br>
<br>

inspired by Nall-chan (https://github.com/Nall-chan/IPSSqueezeBox/blob/6bbdccc23a0de51bb3fbc114cefc3acf23c27a14/libs/SqueezeBoxTraits.php)`<br>
`string __get(string $name)`<br>
`void __set(string $name, string $value)`<br>
<br>

`void SetMultiBuffer(string $name, string $value)`<br>
`string GetMultiBuffer(string $name)`<br>
<br>

`string GetMediaData(string $Name)`<br>
`void SetMediaData(string $Name, string $data, int $Mediatyp, string $Extension, bool $Cached)`<br>
<br>

`bool HookIsUsed(string $ident)`<br>
`void RegisterHook(string $ident)`<br>
<br>

`string GetMimeType(string $extension)`<br>
<br>

`string GetUserDir(bool $absolute_path)`<br>
`string GetSkinsDir(bool $absolute_path)`<br>
<br>

`bool OAuthIsUsed(string $ident)`<br>
`void RegisterOAuth(string $ident)`<br>
<br>

`mixed GetArrayElem(array $data, string $var, mixed $dflt, bool &$fnd = null)`<br>
`string LimitOutput(string $str, int $maxLength = null)`<br>
`string bool2str(bool $bval)`<br>
`string format_float(float $number, int $dec_points)`<br>
`string seconds2duration(float $sec)`<br>
`string size2str(int $size)`<br>
`string bitmap2str(int $val, int $len)`<br>
`int bit_set(int $val, int $bit)`<br>
`int bit_clear(int $val, int $bit)`<br>
`int bit_test(int $val, int $bit)`<br>
`string HttpCode2Text(int $code)`<br>
<br>

`array GetSystemLocation()`<br>
<br>

`array GetConfiguratorLocation(int $catID)`<br>
`string GetConfigurationForm()`<br>
<br>

`string MaintainStatus(int $status)`<br>
`string GetStatusText()`<br>
<br>

`array GetTranslations()`<br>
`string GetTranslationInfo()`<br>
`string Translate(string $text)`<br>
`string TranslateFormat(string $text, array $vars = null)`<br>
<br>

`array GetTimerByName(string $name)`<br>
`string PrintTimer(string $name)`<br>
`string MaintainTimer(string $name, int $msec)`<br>
<br>

`bool AdjustAction(string $Ident, bool $Mode)`<br>
<br>

`string GetConnectUrl()`<br>
`int GetConnectStatus()`<br>
`string GetConnectStatusText()`<br>

`int GetConnectionID()`<br>

`void PopupMessage(string $text)`<br>

`array GetInstallVarProfilesFormItem()`<br>

`string InstanceInfo(int $instID)`<br>
`array GetInformationFormAction()`<br>

`string ScriptType2Name(int $scriptType)`<br>
`string EventType2Name(int $eventType)`<br>
`string ObjectType2Name(int $objectType)`<br>

`bool CommonRequestAction(string $ident, variant $params)`<br>

`string GetModulePrefix()`<br>

`bool IsValidID(int $id)`<br>

`void MaintainReferences(array $propertyNames)`<br>
`void MaintainReferences4Script(string $text)`<br>
`void MaintainReferences4Action(mixed $action)`<br>

`void UnregisterMessages(array $messagesIds)`<br>
`void RegisterObjectMessages(array $objIDs, array $messagesIds)`<br>

`array ExplodeReferences(int $instID)`<br>
`array GetReferencesFormAction()`<br>

`void PushCallChain(string $func)`<br>
`void PopCallChain(string $func)`<br>
`string PrintCallChain(bool $complete)`<br>

`string CheckPrerequisites()`<br>
dazu bei Bedarf in module.php: `array CheckModulePrerequisites()`<br>
`string CheckConfiguration()`<br>
dazu bei Bedarf in module.php: `array CheckModuleConfiguration()`<br>
`array GetCommonFormElements(string $title)`<br>

`int version2num(string $version)`<br>
`string version2str(array $info)`<br>
`array GetModuleVersion()`<br>

`string CheckUpdate()`<br>
dazu bei Bedarf in module.php: `string CheckModuleUpdate(array $oldInfo, array $newInfo)`<br>
`bool CompleteUpdate()`<br>
dazu bei Bedarf in module.php: `string CompleteModuleUpdate(array $oldInfo, array $newInfo)`<br>
`array GetCompleteUpdateFormAction()`<br>

`void AddModuleActivity(string $log, int $maxLen = 20)`<br>
`array GetModuleActivityFormAction()`<br>

`void SetVariableLogging(string $ident, int $aggregationType)`<br>
`void UnsetVariableLogging(string $ident)`<br>

`void ApiCallsSetLimits(array $limits)`<br>
`void ApiCallsCollect(string $url, string $err, int $statuscode)`<br>
`array GetApiCallStatsFormItem()`<br>
`void ShowApiCallStats()`<br>
`void ClearApiCallStats()`<br>

`void SetupDataCache(int $expires_in)`<br>
`void RefreshDataCache()`<br>
`array ReadDataCache()`<br>
`void WriteDataCache(array $dataCache, int $dataTstamp)`<br>
`array GetRefreshDataCacheFormAction()`<br>

`void CommonConstruct(string $dir)`<br>
`void CommonDestruct()`<br>

Definierte Konstanten
```
IS_INVALIDPREREQUISITES
IS_UPDATEUNCOMPLETED
IS_INVALIDCONFIG
IS_NOSYMCONCONNECT
```
