# CommonStubs

## trait StubsCommonLib

`bool SetValue(string $ident, mixed $value)`<br>
`bool SaveValue(string $ident, mixed $value, bool &$isChanged)`<br>
`mixed GetValue(string $ident)`<br>
<br>

`void CreateVarProfile(string $ident, int $varType, string $suffix, float $min, float $max, int $stepSize, int $digits, string $icon, $associations = null, bool $doReinstall)`<br>
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
`string GetMimeType(string $extension)`<br>
<br>

`bool OAuthIsUsed(string $ident)`<br>
`void RegisterOAuth(string $ident)`<br>
<br>

`mixed GetArrayElem(array $data, string $var, mixed $dflt, bool &$fnd = null)`<br>
`string LimitOutput(string $str, int $maxLength = null)`<br>
`string bool2str(bool $bval)`<br>
`string format_float(float $number, int $dec_points)`<br>
`string seconds2duration(int $sec)`<br>
`string HttpCode2Text(int $code)`<br>
<br>

`string GetConfigurationForm()`<br>
`string GetStatusText()`<br>
<br>

`string TranslateFormat(string $str, array $vars = null)`<br>
<br>

`string PrintTimer(string $name)`<br>
`string MaintainTimer(string $name, int $msec)`<br>
<br>

`bool AdjustAction(string $Ident, bool $Mode)`<br>
<br>

`string GetConnectUrl()`<br>
`int GetConnectStatus()`<br>
`string GetConnectStatusText()`<br>
<br>

`int GetConnectionID()`<br>
<br>

`string InstanceInfo(int $instID)`<br>
`array GetInformationForm()`<br>

Ergänzung der *locale.json*
```
"Information": "Information",
```

`string ScriptType2Name(int $scriptType)`<br>
`string EventType2Name(int $eventType)`<br>
`string ObjectType2Name(int $objectType)`<br>

`bool CommonRequestAction(string $ident, variant $params)`<br>

`string GetModulePrefix()`<br>

`array ExplodeReferences(int $instID)`<br>
`array GetReferencesForm()`<br>

Ergänzung der *locale.json*
```
"Category": "Kategorie",
"Instance": "Instanz",
"Variable": "Variable",
"Script": "Skript",
"Event": "Ereignis",
"Medium": "Medium",
"Link": "Verknüpfung",
"PHP script": "PHP-Skript",
"Flow plan": "Ablaufplan",
"Triggered event": "Ausgelöstes Ereignis",
"Cyclic event": "Zyklisches Ereignis",
"Scheduled event": "Geplantes Ereignis",
"References": "Referenzen",
"by instance used objects": "durch die Instanz verwendete Objekte",
"Objects using the instance": "Objekte, die die Instanz verwenden",
"Referenced statusvariables": "referenzierte Statusvariablen",
"Ident": "Ident",
"Object": "Objekt",
"Area": "Bereich",
"Name": "Bezeichnung",
"Open object": "Objekt öffnen",
"Timer": "Timer",
"Interval": "Intervall",
"Next run": "nächste Ausführung",
"Last run": "letzte Ausführung",
"Timer information": "Timer-Information",
```

`void PushCallChain(string $func)`<br>
`void PopCallChain(string $func)`<br>
`string PrintCallChain(bool $complete)`<br>
