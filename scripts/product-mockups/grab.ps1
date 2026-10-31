# Picks up a file a page just "downloaded" in the owner's Chrome (which asks where to save each file):
# Chrome already writes the whole file to Downloads\<guid>.tmp (or <name>.crdownload) while its Save As
# dialog waits. Copies the newest such file (written after -Since, at least -MinBytes) to -To, then presses Cancel
# on Chrome's Save As dialog and removes the leftover .tmp. Prints the copied size.
param(
    [Parameter(Mandatory)][string]$To,
    [Parameter(Mandatory)][datetime]$Since,
    [long]$MinBytes = 1000,
    [int]$WaitSec = 30
)
$ErrorActionPreference = 'Stop'
$dl = Join-Path $env:USERPROFILE 'Downloads'
$sw = [Diagnostics.Stopwatch]::StartNew()
$tmp = $null
do {
    # Chrome names the pending file <guid>.tmp, or <name>.crdownload in newer builds
    $tmp = Get-ChildItem (Join-Path $dl '*') -Include '*.tmp', '*.crdownload' -Force -ErrorAction SilentlyContinue |
        Where-Object { $_.LastWriteTime -ge $Since -and $_.Length -ge $MinBytes } |
        Sort-Object LastWriteTime -Descending | Select-Object -First 1
    if (-not $tmp) { Start-Sleep -Milliseconds 400 }
} while (-not $tmp -and $sw.Elapsed.TotalSeconds -lt $WaitSec)
if (-not $tmp) { Write-Output "NO-FILE"; exit 1 }

# wait until the size stops changing
$last = -1
while ($true) { $len = [IO.FileInfo]::new($tmp.FullName).Length; if ($len -eq $last) { break }; $last = $len; Start-Sleep -Milliseconds 500 }
New-Item -ItemType Directory -Force -Path (Split-Path $To) | Out-Null
Copy-Item $tmp.FullName $To -Force

Add-Type -AssemblyName UIAutomationClient, UIAutomationTypes
if (-not ('GPM' -as [type])) {
    Add-Type @"
using System; using System.Runtime.InteropServices;
public static class GPM { [DllImport("user32.dll")] public static extern bool PostMessage(IntPtr h, uint m, IntPtr w, IntPtr l); }
"@
}
$AE = [System.Windows.Automation.AutomationElement]; $TS = [System.Windows.Automation.TreeScope]
$cancelled = 0
foreach ($w in $AE::RootElement.FindAll($TS::Children, (New-Object System.Windows.Automation.PropertyCondition($AE::ClassNameProperty, 'Chrome_WidgetWin_1')))) {
    foreach ($d in $w.FindAll($TS::Descendants, (New-Object System.Windows.Automation.PropertyCondition($AE::ClassNameProperty, '#32770')))) {
        if ($d.Current.Name -notmatch 'Save') { continue }
        # IDCANCEL straight to the dialog: works without focus and whatever the Cancel control's type
        [void][GPM]::PostMessage([IntPtr]$d.Current.NativeWindowHandle, 0x0111, [IntPtr]2, [IntPtr]::Zero)
        $cancelled++
    }
}
Start-Sleep -Milliseconds 800
if (Test-Path $tmp.FullName) { Remove-Item $tmp.FullName -Force -ErrorAction SilentlyContinue }
Write-Output ("OK {0:N0} bytes -> {1} (save-as cancelled: {2})" -f ([IO.FileInfo]::new($To).Length), $To, $cancelled)
