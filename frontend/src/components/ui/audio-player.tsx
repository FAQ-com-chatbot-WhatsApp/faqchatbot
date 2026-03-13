"use client"

import { useState } from "react"
import { Button } from "./button"

interface AudioPlayerProps {
  audioUrl: string
  transcription?: string
  sender?: "user" | "other"
}

export function AudioPlayer({ audioUrl, transcription, sender = "other" }: AudioPlayerProps) {
  const [showTranscription, setShowTranscription] = useState(false)

  // Strip the "[Áudio transcrito]: " prefix if present
  const cleanTranscription = transcription
    ? transcription.replace(/^\[Áudio transcrito\]:\s*/i, '')
      .replace(/^\[Áudio recebido.*\]$/i, 'Transcrição não disponível')
    : undefined

  // Check if transcription is actually available (not an error message)
  const hasValidTranscription = cleanTranscription &&
    !cleanTranscription.includes('transcrição falhou') &&
    !cleanTranscription.includes('erro na transcrição') &&
    cleanTranscription !== 'Transcrição não disponível'

  return (
    <div className="flex flex-col gap-2 max-w-md">
      {audioUrl ? (
        <div className="flex items-center gap-2">
          <audio
            controls
            className="flex-1 h-10"
            src={audioUrl}
            preload="metadata"
          >
            Seu navegador não suporta o elemento de áudio.
          </audio>
        </div>
      ) : (
        <div className="flex items-center gap-2 p-2 bg-muted/50 rounded text-sm text-muted-foreground">
          🎤 Áudio recebido (visualização não disponível)
        </div>
      )}

      {hasValidTranscription && (
        <div className="flex flex-col gap-1">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => setShowTranscription(!showTranscription)}
            className={`text-xs ${sender === "user" ? "text-primary-foreground/80 hover:text-primary-foreground" : "text-muted-foreground hover:text-foreground"}`}
          >
            {showTranscription ? "Ocultar transcrição" : "Ver transcrição"}
          </Button>

          {showTranscription && (
            <div className={`text-sm p-2 rounded-md border ${sender === "user" ? "bg-primary/10 border-primary/20" : "bg-muted border-border"}`}>
              {cleanTranscription}
            </div>
          )}
        </div>
      )}

      {!hasValidTranscription && transcription && (
        <div className="text-xs text-muted-foreground italic">
          {cleanTranscription}
        </div>
      )}
    </div>
  )
}
