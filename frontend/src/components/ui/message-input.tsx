"use client"

import * as React from "react"
import { cn } from "@/lib/utils"
import { Textarea } from "@/components/ui/textarea"
import { Button } from "@/components/ui/button"
import { Paperclip, Send, Image, Smile } from "lucide-react"

interface MessageInputProps extends React.HTMLAttributes<HTMLDivElement> {
  onSend?: (message: string) => void
  onAttachment?: (file: File) => void
  placeholder?: string
  showAttachment?: boolean
  showImage?: boolean
  showEmoji?: boolean
  disabled?: boolean
}

export function MessageInput({
  onSend,
  onAttachment,
  placeholder = "Digite uma mensagem...",
  showAttachment = true,
  showImage = false,
  showEmoji = false,
  disabled = false,
  className,
  ...props
}: MessageInputProps) {
  const [message, setMessage] = React.useState("")
  const fileInputRef = React.useRef<HTMLInputElement>(null)

  const handleSend = () => {
    if (message.trim() && onSend) {
      onSend(message)
      setMessage("")
    }
  }

  const handleKeyDown = (e: React.KeyboardEvent) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault()
      handleSend()
    }
  }

  const handleAttachmentClick = () => {
    fileInputRef.current?.click()
  }

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file && onAttachment) {
      onAttachment(file)
    }
    if (fileInputRef.current) {
      fileInputRef.current.value = ''
    }
  }

  return (
    <div
      className={cn(
        "flex items-end gap-2 p-4 border-t bg-background",
        className
      )}
      {...props}
    >
      <input
        ref={fileInputRef}
        type="file"
        className="hidden"
        onChange={handleFileChange}
        accept="image/*,video/*,audio/*,.pdf,.doc,.docx,.xls,.xlsx"
        disabled={disabled}
      />
      <div className="flex gap-1">
        {showAttachment && (
          <Button
            variant="ghost"
            size="icon-sm"
            disabled={disabled}
            aria-label="Attach file"
            onClick={handleAttachmentClick}
            type="button"
          >
            <Paperclip className="size-4" />
          </Button>
        )}
        {showImage && (
          <Button
            variant="ghost"
            size="icon-sm"
            disabled={disabled}
            aria-label="Attach image"
          >
            <Image className="size-4" />
          </Button>
        )}
        {showEmoji && (
          <Button
            variant="ghost"
            size="icon-sm"
            disabled={disabled}
            aria-label="Add emoji"
          >
            <Smile className="size-4" />
          </Button>
        )}
      </div>

      <Textarea
        id="message-input"
        name="message"
        value={message}
        onChange={(e) => setMessage(e.target.value)}
        onKeyDown={handleKeyDown}
        placeholder={placeholder}
        disabled={disabled}
        className="min-h-[44px] max-h-32 resize-none"
        rows={1}
      />

      <Button
        onClick={handleSend}
        disabled={disabled || !message.trim()}
        size="icon"
        aria-label="Enviar mensagem"
      >
        <Send className="size-4" />
      </Button>
    </div>
  )
}
