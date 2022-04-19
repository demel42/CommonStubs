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

`string TranslateFormat(string $text, array $vars = null)`<br>
<br>

`string PrintTimer(string $name)`<br>
`string MaintainTimer(string $name, int $msec)`<br>
<br>

`bool AdjustAction(string $Ident, bool $Mode)`<br>
<br>

`string GetConnectUrl()`<br>
`int GetConnectStatus()`<br>
`string GetConnectStatusText()`<br>

`int GetConnectionID()`<br>
<br>

`string InstanceInfo(int $instID)`<br>
`array GetInformationFormAction()`<br>

`string ScriptType2Name(int $scriptType)`<br>
`string EventType2Name(int $eventType)`<br>
`string ObjectType2Name(int $objectType)`<br>

`bool CommonRequestAction(string $ident, variant $params)`<br>

`string GetModulePrefix()`<br>

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

`string CheckUpdate()`<br>
dazu bei Bedarf in module.php: `string CheckModuleUpdate(array $oldInfo, array $newInfo)`<br>
`bool CompleteUpdate()`<br>
dazu bei Bedarf in module.php: `string CompleteModuleUpdate(array $oldInfo, array $newInfo)`<br>
`array GetCompleteUpdateFormAction()`<br>

Definierte Konstanten
```
IS_INVALIDPREREQUISITES
IS_UPDATEUNCOMPLETED
IS_INVALIDCONFIG
IS_DEACTIVATED
```

Ergänzung der *locale.json*
```
"The following system prerequisites are missing": "Folgende Systemvoraussetzungen fehlen",

"The following points of the configuration are incorrect": "Die folgenden Punkte der Konfiguration sind fehlerhaft",

"Still something to do to complete the update": "Es ist noch etwas zu tun, um das Update abzuschließen",
"old version": "alte Version",
"new version": "neue Version",
"unknwon": "unbekannt",
"Press button 'Complete update' to carry out the required work": "Taste 'Update abschliessen' betätigen um die erforderlichen Arbeiten auszuführen",
"Complete update": "Update abschliessen",
"Unable to complete the update": "Das Update kann nicht abgeschlossen werden",
"please contact the author": "Bitte kontaktieren Sie den Autor",

"Status: Symcon Connect is OK!": "Status: Symcon Connect ist OK!",
"Error: Symcon Connect is not active!": "Fehler: Symcon Connect ist nicht aktiv!",

"Instance has no active parent instance": "Instanz hat keine aktive übergeordnete Instanz",


"Disable instance": "Instanz deaktivieren",


"Expert area": "Experten-Bereich",
"Re-install variable-profiles": "Variablenprofile erneut einrichten",

"Information": "Information",

"Test area": "Test-Bereich",

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
"Refresh references panel": "Referenzen-Panel aktualisieren",

"Instance getting created": "Instanz wird erstellt",
"Instance is active": "Instanz ist aktiv",
"Instance is deleted": "Instanz wird gelöscht",
"Instance is inactive": "Instanz ist inaktiv",
"Instance is not created": "Instanz wurde nicht erzeugt",

"Instance is inactive (invalid preconditions)": "Instanz ist inaktiv (ungültige Voraussetzungen)",
"Instance is inactive (update not completed)": "Instanz ist inaktiv (Update nicht abgeschlossen)",
"Instance is inactive (invalid configuration)": "Instanz ist inaktiv (ungültige Konfiguration)",
"Instance is inactive (deactivated)": "Instanz ist inaktiv (deaktiviert)",

```
