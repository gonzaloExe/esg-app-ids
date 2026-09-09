param([switch]$Install)

$Port = 17890
$Base = Join-Path $env:ProgramData "ESG"
$IdFile = Join-Path $Base "pc-id.txt"

function Ensure-Id {
    if (!(Test-Path $Base)) { New-Item -ItemType Directory -Path $Base -Force | Out-Null }
    if (!(Test-Path $IdFile)) {
        [guid]::NewGuid().ToString() | Set-Content -Path $IdFile -Encoding ASCII
    }
    (Get-Content $IdFile -Raw).Trim()
}

function Get-Info {
    $pcid = Ensure-Id
    $cs = Get-CimInstance Win32_ComputerSystem
    $os = Get-CimInstance Win32_OperatingSystem
    $cpu = Get-CimInstance Win32_Processor | Select-Object -First 1
    $board = Get-CimInstance Win32_BaseBoard | Select-Object -First 1
    $disk = Get-CimInstance Win32_DiskDrive | Where-Object {$_.Size} | Select-Object -First 1
    $c = Get-CimInstance Win32_LogicalDisk -Filter "DeviceID='C:'" | Select-Object -First 1
    $nic = Get-CimInstance Win32_NetworkAdapterConfiguration -Filter "IPEnabled=True" |
        Where-Object {$_.MACAddress} | Select-Object -First 1

    $totalGB = if($os.TotalVisibleMemorySize){[math]::Round($os.TotalVisibleMemorySize/1MB,2)}else{$null}
    $freeGB = if($os.FreePhysicalMemory){[math]::Round($os.FreePhysicalMemory/1MB,2)}else{$null}
    $diskGB = if($disk.Size){[math]::Round($disk.Size/1GB,2)}else{$null}
    $ctotal = if($c.Size){[math]::Round($c.Size/1GB,2)}else{$null}
    $cfree = if($c.FreeSpace){[math]::Round($c.FreeSpace/1GB,2)}else{$null}
    $cpct = if($c.Size){[math]::Round(($c.FreeSpace/$c.Size)*100,2)}else{$null}

    [ordered]@{
        pc_id=$pcid
        pc_nombre=$env:COMPUTERNAME
        usuario_windows=$env:USERNAME
        sistema_operativo=$os.Caption
        arquitectura=if($cs.SystemType){$cs.SystemType}else{"x86_64"}
        procesador=if($cpu.Name){$cpu.Name}else{"No detectado"}
        version_php="Agente Windows"
        ip_local=if($nic.IPAddress){($nic.IPAddress | Where-Object {$_ -match '^\d+\.'} | Select-Object -First 1)}else{"No detectada"}
        ram_total_gb=$totalGB
        ram_disponible_gb=$freeGB
        placa_manufacturer=if($board.Manufacturer){$board.Manufacturer}else{"No detectado"}
        placa_product=if($board.Product){$board.Product}else{"No detectado"}
        disco_modelo=if($disk.Model){$disk.Model}else{"No detectado"}
        disco_tamano_gb=$diskGB
        disco_c_total_gb=$ctotal
        disco_c_libre_gb=$cfree
        disco_c_porcentaje_libre=$cpct
        mac_address=if($nic.MACAddress){$nic.MACAddress}else{"No detectada"}
        cpu_nombre=if($cpu.Name){$cpu.Name}else{"No detectado"}
        cpu_cores=$cpu.NumberOfCores
        cpu_logical=$cpu.NumberOfLogicalProcessors
        fecha_deteccion=(Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
    }
}

if($Install){
    if(-not ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)){
        Write-Host "Ejecutá PowerShell como Administrador para instalar el agente." -ForegroundColor Red; exit 1
    }
    if(!(Test-Path $Base)){New-Item -ItemType Directory -Path $Base -Force|Out-Null}
    $target=Join-Path $Base "ESG-Agent.ps1"
    Copy-Item -LiteralPath $PSCommandPath -Destination $target -Force
    $action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$target`""
    $trigger = New-ScheduledTaskTrigger -AtLogOn -User $env:USERNAME
    Register-ScheduledTask -TaskName "ESG-Agent" -Action $action -Trigger $trigger -Description "Agente local ESG" -Force | Out-Null
    Start-Process powershell.exe -ArgumentList "-NoProfile -ExecutionPolicy Bypass -File `"$target`"" -WindowStyle Hidden
    Write-Host "Agente ESG instalado. PC ID: $(Ensure-Id)"
    exit
}

$listener = New-Object System.Net.HttpListener
$listener.Prefixes.Add("http://127.0.0.1:$Port/")
try{$listener.Start()}catch{Write-Host "No se pudo iniciar el agente en el puerto $Port. Ejecutá el agente como Administrador una vez." -ForegroundColor Red; exit 1}
Write-Host "ESG Agent escuchando en http://127.0.0.1:$Port/"

while($listener.IsListening){
    try{
        $ctx=$listener.GetContext()
        $ctx.Response.Headers.Add("Access-Control-Allow-Origin","*")
        $ctx.Response.Headers.Add("Access-Control-Allow-Methods","GET, OPTIONS")
        $ctx.Response.Headers.Add("Access-Control-Allow-Headers","Content-Type")
        if($ctx.Request.HttpMethod -eq "OPTIONS"){$ctx.Response.StatusCode=204;$ctx.Response.Close();continue}
        if($ctx.Request.Url.AbsolutePath -eq "/info"){
            $json=(Get-Info | ConvertTo-Json -Depth 5 -Compress)
            $bytes=[Text.Encoding]::UTF8.GetBytes($json)
            $ctx.Response.ContentType="application/json; charset=utf-8"
            $ctx.Response.ContentLength64=$bytes.Length
            $ctx.Response.OutputStream.Write($bytes,0,$bytes.Length)
        }else{
            $ctx.Response.StatusCode=404
        }
        $ctx.Response.Close()
    }catch{}
}
