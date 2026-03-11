export interface WahaMessage {
  id: string
  chatId: string
  from: string
  to: string
  body: string
  timestamp: number
  fromMe: boolean
  type: 'chat' | 'image' | 'video' | 'document' | 'location'
  mediaUrl?: string
  caption?: string
}

export interface WahaChat {
  id: string
  name: string
  lastMessage?: string
  lastMessageTime?: number
  unreadCount?: number
  isGroup?: boolean
}

export interface SendTextMessageRequest {
  chat_id: string
  text: string
}

export interface SendImageMessageRequest {
  chat_id: string
  url: string
  caption?: string
}

export interface SendLocationMessageRequest {
  chat_id: string
  latitude: number
  longitude: number
  title?: string
}

export interface GetMessagesResponse {
  messages: WahaMessage[]
  total: number
}
