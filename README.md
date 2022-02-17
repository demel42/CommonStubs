# CommonStubs

## trait StubsCommonLib

- bool SetValue(string $Ident, mixed $Value)<br>
- bool SaveValue(string $Ident, mixed $Value, bool &$IsChanged)<br>
- mixed GetValue(string $Ident)<br>
<br>

- void CreateVarProfile(string $Name, int $ProfileType, string $Suffix, float $MinValue, float $MaxValue, int $StepSize, int $Digits, string $Icon, array $Associations, bool $doReinstall)<br>
<br>

inspired by Nall-chan (https://github.com/Nall-chan/IPSSqueezeBox/blob/6bbdccc23a0de51bb3fbc114cefc3acf23c27a14/libs/SqueezeBoxTraits.php)<br>
- string __get(string $name)<br>
- void __set(string $name, string $value)<br>
<br>

- void SetMultiBuffer(string $name, string $value)<br>
- string GetMultiBuffer(string $name)<br>
<br>

- string GetMediaData(string $Name)<br>
- void SetMediaData(string $Name, string $data, int $Mediatyp, string $Extension, bool $Cached)<br>
<br>

- void HookIsUsed(string $WebHook)<br>
- void RegisterHook(string $WebHook)<br>
- string GetMimeType(string $extension)<br>
<br>

- mixed GetArrayElem(array $data, string $var, mixed $dflt)<br>
<br>

- string LimitOutput(string $str, int $maxLength = null)<br>
<br>

- string bool2str(bool $bval)<br>
<br>

- string GetConfigurationForm()<br>
- string GetStatusText()<br>
<br>

- string TranslateFormat(string $str, array $vars = null)<br>
<br>

- bool AdjustAction(string $Ident, bool $Mode)<br>
<br>

- int GetConnectionID()<br>
<br>

- void InstanceInfo(int $instID)<br>
- array GetInformationForm()<br>
