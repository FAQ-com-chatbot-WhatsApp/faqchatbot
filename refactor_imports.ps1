$root = "back\src\robbot"
$files = Get-ChildItem -Path $root -Filter *.py -Recurse

$replacements = @{
    "from robbot.domain.enums" = "from robbot.domain.shared.enums"
    "from robbot.domain.value_objects" = "from robbot.domain.shared.value_objects"
    "from robbot.domain.entities import Lead" = "from robbot.domain.leads.lead import Lead"
    "from robbot.domain.entities import Conversation" = "from robbot.domain.conversations.conversation import Conversation"
    "from robbot.domain.mappers import LeadMapper" = "from robbot.domain.leads.mapper import LeadMapper"
    "from robbot.domain.mappers import ConversationMapper" = "from robbot.domain.conversations.mapper import ConversationMapper"
    "from robbot.infra.db.models" = "from robbot.infra.persistence.models"
    "from robbot.adapters.repositories" = "from robbot.infra.persistence.repositories"
    "from robbot.adapters.external.waha_client" = "from robbot.infra.integrations.waha.waha_client"
    "from robbot.adapters.external.llm_client" = "from robbot.infra.integrations.llm.llm_client"
    "from robbot.adapters.external.chroma_vector_store" = "from robbot.infra.integrations.vector_store.chroma_vector_store"
    "from robbot.services.lead_service" = "from robbot.services.leads.lead_service"
    "from robbot.services.conversation_orchestrator" = "from robbot.services.bot.conversation_orchestrator"
    "from robbot.services.conversation_pipeline" = "from robbot.services.bot.conversation_pipeline"
    "from robbot.services.conversation_service" = "from robbot.services.bot.conversation_service"
    "from robbot.services.transcription_service" = "from robbot.services.communication.transcription_service"
    "from robbot.services.text_sanitizer" = "from robbot.services.communication.text_sanitizer"
    "from robbot.services.persistent_memory" = "from robbot.services.ai.persistent_memory"
    "from robbot.services.answered_questions" = "from robbot.services.ai.answered_questions"
    "from robbot.services.intent_detector" = "from robbot.services.ai.intent_detector"
    "from robbot.services.context_service" = "from robbot.services.ai.context_service"
    "from robbot.services.handoff_service" = "from robbot.services.handoff.handoff_service"
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
