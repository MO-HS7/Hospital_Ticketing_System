param(
  [string]$BaseUrl = 'http://localhost:8000/api',
  [string]$PatientEmail = 'patient@hospital.com',
  [string]$PatientPassword = 'password',
  [string]$DoctorEmail = 'doctor@hospital.com',
  [string]$DoctorPassword = 'password',
  [string]$DepartmentId = '',
  [ValidateSet('appointment', 'maintenance')][string]$TicketType = 'appointment',
  [string]$Subject = 'PS sanity flow',
  [string]$Description = 'end-to-end',
  [ValidateSet('low', 'medium', 'high', 'urgent')][string]$Priority = 'medium',
  [string]$NoteBody = 'PS sanity note'
)

$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'

function Prefix([string]$s, [int]$n) {
  if ($null -eq $s) { return $null }
  $len = [Math]::Min($n, $s.Length)
  return $s.Substring(0, $len)
}

function CountOf($v) {
  if ($null -eq $v) { return 0 }
  return @($v).Count
}

function Invoke-ApiJson {
  param(
    [Parameter(Mandatory = $true)][ValidateSet('Get', 'Post', 'Put', 'Patch', 'Delete')][string]$Method,
    [Parameter(Mandatory = $true)][string]$Path,
    [hashtable]$Headers = @{},
    $Body = $null
  )

  $p = $Path.TrimStart('/')
  $uri = ($BaseUrl.TrimEnd('/') + '/' + $p)

  $params = @{
    Method = $Method
    Uri = $uri
    Headers = $Headers
    ErrorAction = 'Stop'
  }

  if ($null -ne $Body) {
    $params.ContentType = 'application/json'
    $params.Body = ($Body | ConvertTo-Json -Depth 10)
  }

  try {
    return Invoke-RestMethod @params
  } catch {
    $ex = $_.Exception
    $resp = $ex.Response

    $err = [ordered]@{
      message = $ex.Message
      method = $Method
      uri = $uri
      statusCode = $null
      statusDescription = $null
      bodyPrefix400 = $null
    }

    if ($resp) {
      try { $err.statusCode = [int]$resp.StatusCode } catch {}
      try { $err.statusDescription = $resp.StatusDescription } catch {}

      try {
        $stream = $resp.GetResponseStream()
        if ($stream) {
          $reader = New-Object System.IO.StreamReader($stream)
          $raw = $reader.ReadToEnd()
          $err.bodyPrefix400 = Prefix $raw 400
        }
      } catch {}
    }

    throw (New-Object System.Exception (($err | ConvertTo-Json -Compress -Depth 6)))
  }
}

function Extract-Token($loginObj) {
  if ($null -eq $loginObj) { return $null }
  if ($loginObj.PSObject.Properties.Match('token').Count -gt 0) { return $loginObj.token }
  if ($loginObj.PSObject.Properties.Match('data').Count -gt 0) {
    if ($loginObj.data -and $loginObj.data.PSObject.Properties.Match('token').Count -gt 0) { return $loginObj.data.token }
  }
  return $null
}

function Extract-TicketId($ticketObj) {
  if ($null -eq $ticketObj) { return $null }
  if ($ticketObj.PSObject.Properties.Match('id').Count -gt 0) { return $ticketObj.id }
  if ($ticketObj.PSObject.Properties.Match('data').Count -gt 0) {
    if ($ticketObj.data -and $ticketObj.data.PSObject.Properties.Match('id').Count -gt 0) { return $ticketObj.data.id }
  }
  if ($ticketObj.PSObject.Properties.Match('ticket').Count -gt 0) {
    if ($ticketObj.ticket -and $ticketObj.ticket.PSObject.Properties.Match('id').Count -gt 0) { return $ticketObj.ticket.id }
  }
  return $null
}

$result = [ordered]@{
  ok = $false
  baseUrl = $BaseUrl
  ticketId = $null
  steps = [ordered]@{}
  error = $null
}

try {
  $health = Invoke-ApiJson -Method Get -Path 'health' -Headers @{ Accept = 'application/json' }
  $result.steps.health = $health.status

  $patientLogin = Invoke-ApiJson -Method Post -Path 'auth/login' -Headers @{ Accept = 'application/json' } -Body @{ email = $PatientEmail; password = $PatientPassword }
  $patientToken = Extract-Token $patientLogin
  $result.steps.patientTokenLen = if ($patientToken) { $patientToken.Length } else { 0 }

  $doctorLogin = Invoke-ApiJson -Method Post -Path 'auth/login' -Headers @{ Accept = 'application/json' } -Body @{ email = $DoctorEmail; password = $DoctorPassword }
  $doctorToken = Extract-Token $doctorLogin
  $result.steps.doctorTokenLen = if ($doctorToken) { $doctorToken.Length } else { 0 }

  $deptResp = Invoke-ApiJson -Method Get -Path 'departments' -Headers @{ Accept = 'application/json' }
  $deptId = $null
  if ($DepartmentId -and $DepartmentId.Trim().Length -gt 0) {
    $deptId = $DepartmentId
  } else {
    if ($deptResp.data -and @($deptResp.data).Count -gt 0) { $deptId = $deptResp.data[0].id }
  }
  if (-not $deptId) { throw (New-Object System.Exception ('Could not determine department_id')) }
  $result.steps.departmentId = $deptId

  $ticket = Invoke-ApiJson -Method Post -Path 'tickets' -Headers @{ Authorization = "Bearer $patientToken"; Accept = 'application/json' } -Body @{ department_id = $deptId; type = $TicketType; subject = $Subject; description = $Description; priority = $Priority }
  $ticketId = Extract-TicketId $ticket
  if (-not $ticketId) { throw (New-Object System.Exception ('Could not determine ticket id from POST /tickets response')) }
  $result.ticketId = $ticketId

  $show1 = Invoke-ApiJson -Method Get -Path ("tickets/$ticketId") -Headers @{ Authorization = "Bearer $patientToken"; Accept = 'application/json' }
  $result.steps.show1 = [ordered]@{
    status = $show1.status
    notesCount = (CountOf $show1.notes)
    eventsCount = (CountOf $show1.events)
    eventTypes = if ($null -eq $show1.events) { @() } else { @($show1.events | ForEach-Object { $_.event_type }) }
  }

  $note = Invoke-ApiJson -Method Post -Path ("tickets/$ticketId/notes") -Headers @{ Authorization = "Bearer $patientToken"; Accept = 'application/json' } -Body @{ body = $NoteBody }
  $result.steps.note = [ordered]@{
    id = $note.id
    body = $note.body
  }

  $show2 = Invoke-ApiJson -Method Get -Path ("tickets/$ticketId") -Headers @{ Authorization = "Bearer $patientToken"; Accept = 'application/json' }
  $result.steps.show2 = [ordered]@{
    status = $show2.status
    notesCount = (CountOf $show2.notes)
    eventsCount = (CountOf $show2.events)
    eventTypes = if ($null -eq $show2.events) { @() } else { @($show2.events | ForEach-Object { $_.event_type }) }
  }

  $accept = Invoke-ApiJson -Method Post -Path ("tickets/$ticketId/accept") -Headers @{ Authorization = "Bearer $doctorToken"; Accept = 'application/json' }
  $result.steps.accept = [ordered]@{
    status = $accept.status
    assigned_to = $accept.assigned_to
    accepted_at = $accept.accepted_at
  }

  $show3 = Invoke-ApiJson -Method Get -Path ("tickets/$ticketId") -Headers @{ Authorization = "Bearer $doctorToken"; Accept = 'application/json' }
  $result.steps.show3 = [ordered]@{
    status = $show3.status
    notesCount = (CountOf $show3.notes)
    eventsCount = (CountOf $show3.events)
    eventTypes = if ($null -eq $show3.events) { @() } else { @($show3.events | ForEach-Object { $_.event_type }) }
  }

  $complete = Invoke-ApiJson -Method Post -Path ("tickets/$ticketId/complete") -Headers @{ Authorization = "Bearer $doctorToken"; Accept = 'application/json' }
  $result.steps.complete = [ordered]@{
    status = $complete.status
    completed_at = $complete.completed_at
  }

  $final = Invoke-ApiJson -Method Get -Path ("tickets/$ticketId") -Headers @{ Authorization = "Bearer $doctorToken"; Accept = 'application/json' }
  $result.steps.final = [ordered]@{
    status = $final.status
    notesCount = (CountOf $final.notes)
    eventsCount = (CountOf $final.events)
    eventTypes = if ($null -eq $final.events) { @() } else { @($final.events | ForEach-Object { $_.event_type }) }
  }

  $result.ok = $true
} catch {
  $msg = $_.Exception.Message
  try {
    $result.error = ($msg | ConvertFrom-Json)
  } catch {
    $result.error = $msg
  }
}

Write-Output ($result | ConvertTo-Json -Compress -Depth 12)

if (-not $result.ok) { exit 1 }
