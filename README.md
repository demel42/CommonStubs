# CommonStubs

## trait StubsCommonLib

- boolean SetValue(string $Ident, mixed $Value)<br>
- boolean SaveValue(string $Ident, mixed $Value, boolean &$IsChanged)<br>
- mixed GetValue(string $Ident)<br>

- void CreateVarProfile(string $Name, int $ProfileType, string $Suffix, float $MinValue, float $MaxValue, int $StepSize, int $Digits, string $Icon, array $Associations, bool $doReinstall)<br>

- mixed GetArrayElem(array $data, string $var, mixed $dflt)<br>

inspired by Nall-chan<br>
   https://github.com/Nall-chan/IPSSqueezeBox/blob/6bbdccc23a0de51bb3fbc114cefc3acf23c27a14/libs/SqueezeBoxTraits.php<br>
- string __get(string $name)<br>
- void __set(string $name, string $value)<br>

- void SetMultiBuffer(string $name, string $value)<br>
- string GetMultiBuffer(string $name)<br>

- string GetMediaData(string $Name)<br>
- void SetMediaData(string $Name, string $data, int $Mediatyp, string $Extension, boolean $Cached)<br>

- void RegisterHook(string $WebHook)<br>
- string GetMimeType(string $extension)<br>

- string bool2str(boolean $bval)<br>

- string GetConfigurationForm()<br>
- string GetStatusText()<br>

- string TranslateFormat(string $str, array $vars = null)<br>

- void InstanceInfo(int $instID)<br>

- array GetInformationForm()<br>
