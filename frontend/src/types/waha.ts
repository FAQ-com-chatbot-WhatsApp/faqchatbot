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

export interface WahaContact {
  id: string
  name: string  
  phone: string
  avatar?: string
  about?: string
  isBlocked?: boolean
  lastSeen?: number
}

export interface ContactAboutResponse {
  about: string | null
}

export interface ContactPictureResponse {
  url: string | null
}

export interface CheckNumberResponse {
  exists: boolean
  jid?: string
}

export interface BlockContactRequest {
  contact_id: string
}

export interface WahaSession {
  id: number
  name: string
  status: 'STOPPED' | 'STARTING' | 'SCAN_QR_CODE' | 'WORKING' | 'FAILED'
  webhook_url?: string | null
  qr_code?: string | null
  connected_phone?: string | null
  connected_at?: string | null
  is_active: boolean
  created_at: string
  updated_at: string
}

export interface SessionCreate {
  name: string
  webhook_url?: string
  config?: Record<string, any>
}

export interface SessionStatus {
  name: string
  status: string
  qr_code?: string | null
  connected_phone?: string | null
}

export interface SendVoiceMessageRequest {
  chat_id: string
  file_url?: string
  file_data?: string
  mimetype?: string
  convert?: boolean
}

export interface SendVideoMessageRequest {
  chat_id: string
  file_url?: string
  file_data?: string
  filename?: string
  caption?: string
  mimetype?: string
  convert?: boolean
}

export interface SendFileMessageRequest {
  chat_id: string
  file_url: string
  filename?: string
  mimetype?: string
  caption?: string
}
