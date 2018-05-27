
# Grant users permission to run http services
# As administrator run the following command:
# netsh http add urlacl url=http://+:8008/ user=Everyone

Param(
	[string]$ListenUrl = 'http://+:8008/'
)

Add-Type -AssemblyName System.Web # For [System.Web.HttpUtility]::ParseQueryString (possibly used in resource handlers)

<#
    .SYNOPSIS
        Extracts the resource name from the URL
        
    .DESCRIPTION
        Takes "/time/month?SomeQuery=1" and returns "time" i.e the first node in the URL path
#>
function Get-ResourceFromURL {
    Param(
        [Parameter(Mandatory=$true)]
        [string]$RawURL
    )
    Write-Verbose "Get-ResourceFromURL| RawURL: $RawURL"
    $Resource = (($RawURL -split "\?")[0] -split "/")[1] # element 0 is empty because of leading "/"
    Write-Verbose "Get-ResourceFromURL| Extracted resource: $Resource"
    $VerificationRegex = '^(?:[a-zA-Z0-9]+|favicon.ico)$'
    if (-not ($Resource -match $VerificationRegex)){
        Write-Verbose "Get-ResourceFromURL| Resource name does not match the verification RegEx: $VerificationRegex"
        throw "Resource identifier contains invalid characters"
    }
    $Resource
}

<#
    .SYNOPSIS
        Receives HTTP request body
#>
function Receive-Request {
    Param(
        [Parameter(Mandatory,ValueFromPipelineByPropertyName)]
        $Request
    )
    $Output = ""
    $Size = $Request.ContentLength64 + 1
    $buffer = New-Object byte[] $Size
    do {
        $count = $Request.InputStream.Read($buffer, 0, $Size)
        Write-Verbose "Receive-Request | Received $count bytes"
        $Output += $Request.ContentEncoding.GetString($buffer, 0, $count)
    } until($count -lt $Size)
 
    $Request.InputStream.Close()
    $Output
}

<#
    .SYNOPSIS
        Writes the response and closes Response object
#>
function Send-Response {
    Param(
        [Parameter(Position=0, Mandatory=$true)]
        $ResponseObject,        
        [Parameter(Position=1, Mandatory=$true)]
        [int]$StatusCode = 200,        
		[Parameter(Position=2, ValueFromPipeline=$true)]
        $Content = ""
    )
    # Seems like we need to set the status code first before we write data. Otherwise 200 is set by default
    $ResponseObject.StatusCode = $StatusCode
	Write-Verbose "Send-ResponseNew| Content type is $($Content.GetType().ToString())"
	switch($Content.GetType().ToString()){
		"System.Object[]" { $buffer = $Content }
		default { $buffer = [System.Text.Encoding]::UTF8.GetBytes($Content) }
	}	
	$ResponseObject.ContentLength64 = $buffer.Length
    $ResponseObject.OutputStream.Write($buffer, 0, $buffer.Length)
    $ResponseObject.Close()
}

$listener = New-Object System.Net.HttpListener
$listener.Prefixes.Add($ListenUrl)
$listener.Start()
 
Write-Host "Listening at $ListenUrl..."

try {
while ($listener.IsListening)
{
    $Context = $listener.GetContext() # Blocking while waiting for request
    try{
        Write-Host "$(Get-Date)"
        Write-Host "> $($Context.Request.RemoteEndPoint.ToString()) -> $($Context.Request.HttpMethod) $($Context.Request.RawUrl)"    

        # Receive content if POST or PUT
        $postdata = ""
        if(($Context.Request.HttpMethod -eq 'POST') -or ($Context.Request.HttpMethod -eq 'PUT')){
            Write-Verbose "MainLoop| Receiving POST content"
            $postdata = Receive-Request $Context.Request
            $Context.Request | Add-Member -MemberType NoteProperty -Name RawContent -Value $postdata
        }

        # Process request
        if ($Context.Request.RawUrl -like "/logontoken=*") {
            
            $logontoken = ($Context.Request.RawUrl -split "=")[1]
            Write-Host $logontoken $postdata

            $ResponseBody = ""
            if($logontoken -replace '-','' -match ('^[a-zA-Z0-9]+$')) {
			    $postdata | Set-Content  "C:\inetpub\wwwroot\iisidp\logontokens\$($logontoken).txt"
                $ResponseBody = "Logon token saved."
            } else {
                $ResponseBody = "Failed to save logon token $logontoken"
            }

            Write-Verbose "MainLoop| <Back from handler"
			Write-Host "  $($ResponseBody.Length) byte response"
                    
            Send-Response $Context.Response 200 $ResponseBody            
        } else {
            Write-Verbose "MainLoop| Unmanaged rest request"
            Send-Response $Context.Response 404 "Unmanaged rest request"
        }
		
        
    } catch {
        Write-Verbose "MainLoop| Exception: $($_.ToString())"
        Send-Response $Context.Response 500 $_.ToString()
    }

    Write-Host "< $($Context.Response.StatusCode)"
}
} finally {
    $listener.Stop()
}
