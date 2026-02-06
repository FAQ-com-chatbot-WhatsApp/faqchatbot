$root = "back\tests"
$files = Get-ChildItem -Path $root -Filter *.py -Recurse

$replacements = @{
    "from robbot.infra.db.models."              = "from robbot.infra.persistence.models."
    "from robbot.domain.enums"                  = "from robbot.domain.shared.enums"
    "from robbot.adapters.repositories"         = "from robbot.infra.persistence.repositories"
    "from robbot.services.lead_service"         = "from robbot.services.leads.lead_service"
    "from robbot.services.conversation_service" = "from robbot.services.bot.conversation_service"
}

foreach ($file in $files) {
    $content = Get-Content $file.FullName -Raw
    $changed = $false
    foreach ($old in $replacements.Keys) {
        if ($content -match [regex]::Escape($old)) {
            $content = $content -replace [regex]::Escape($old), $replacements[$old]
            $changed = $true
        }
    }
    if ($changed) {
        Set-Content $file.FullName $content
        Write-Host "Updated $($file.FullName)"
    }
}

Write-Host "Test imports refactoring complete!"
