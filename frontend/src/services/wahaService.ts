import { fetchApi } from '@/lib/api'
import type {
  WahaMessage,
  SendTextMessageRequest,
  SendImageMessageRequest,
  SendLocationMessageRequest,
  SendVoiceMessageRequest,
  SendVideoMessageRequest,
  SendFileMessageRequest,
  GetMessagesResponse,
  ContactAboutResponse,
  ContactPictureResponse,
  CheckNumberResponse,
  BlockContactRequest,
  WahaSession,
  SessionCreate,
  SessionStatus,
} from '@/types/waha'

const WAHA_BASE = '/api/v1/waha'

export async function getChatMessages(
  chatId: string,
  limit: number = 50
): Promise<GetMessagesResponse> {
  const encodedChatId = encodeURIComponent(chatId)
  return fetchApi<GetMessagesResponse>(
    `${WAHA_BASE}/chats/${encodedChatId}/messages?limit=${limit}`,
    { method: 'GET' }
  )
}

export async function sendTextMessage(
  request: SendTextMessageRequest
): Promise<WahaMessage> {
  return fetchApi<WahaMessage>(`${WAHA_BASE}/messages/send-text`, {
    method: 'POST',
    body: JSON.stringify(request),
  })
}

export async function sendImageMessage(
  request: SendImageMessageRequest
): Promise<WahaMessage> {
  return fetchApi<WahaMessage>(`${WAHA_BASE}/messages/send-image`, {
    method: 'POST',
    body: JSON.stringify(request),
  })
}

export async function sendLocationMessage(
  request: SendLocationMessageRequest
): Promise<WahaMessage> {
  return fetchApi<WahaMessage>(`${WAHA_BASE}/messages/send-location`, {
    method: 'POST',
    body: JSON.stringify(request),
  })
}

export async function sendVoiceMessage(
  request: SendVoiceMessageRequest
): Promise<WahaMessage> {
  return fetchApi<WahaMessage>(`${WAHA_BASE}/messages/send-voice`, {
    method: 'POST',
    body: JSON.stringify(request),
  })
}

export async function sendVideoMessage(
  request: SendVideoMessageRequest
): Promise<WahaMessage> {
  return fetchApi<WahaMessage>(`${WAHA_BASE}/messages/send-video`, {
    method: 'POST',
    body: JSON.stringify(request),
  })
}

export async function sendFileMessage(
  request: SendFileMessageRequest
): Promise<WahaMessage> {
  return fetchApi<WahaMessage>(`${WAHA_BASE}/messages/send-file`, {
    method: 'POST',
    body: JSON.stringify(request),
  })
}

export async function deleteMessage(
  chatId: string,
  messageId: string
): Promise<void> {
  const encodedChatId = encodeURIComponent(chatId)
  const encodedMessageId = encodeURIComponent(messageId)
  return fetchApi<void>(
    `${WAHA_BASE}/chats/${encodedChatId}/messages/${encodedMessageId}`,
    { method: 'DELETE' }
  )
}

export async function editMessage(
  chatId: string,
  messageId: string,
  text: string
): Promise<WahaMessage> {
  const encodedChatId = encodeURIComponent(chatId)
  const encodedMessageId = encodeURIComponent(messageId)
  return fetchApi<WahaMessage>(
    `${WAHA_BASE}/chats/${encodedChatId}/messages/${encodedMessageId}`,
    {
      method: 'PUT',
      body: JSON.stringify({ text }),
    }
  )
}

// ============================================================================
// CONTACTS
// ============================================================================

export async function checkNumberExists(
  phone: string
): Promise<CheckNumberResponse> {
  return fetchApi<CheckNumberResponse>(
    `${WAHA_BASE}/check-number?phone=${encodeURIComponent(phone)}`,
    { method: 'GET' }
  )
}

export async function getContactAbout(
  contactId: string
): Promise<ContactAboutResponse> {
  return fetchApi<ContactAboutResponse>(
    `${WAHA_BASE}/contact-about?contact_id=${encodeURIComponent(contactId)}`,
    { method: 'GET' }
  )
}

export async function getContactPicture(
  contactId: string
): Promise<ContactPictureResponse> {
  return fetchApi<ContactPictureResponse>(
    `${WAHA_BASE}/contact-picture?contact_id=${encodeURIComponent(contactId)}`,
    { method: 'GET' }
  )
}

export async function blockContact(contactId: string): Promise<void> {
  const request: BlockContactRequest = { contact_id: contactId }
  return fetchApi<void>(`${WAHA_BASE}/contact/block`, {
    method: 'POST',
    body: JSON.stringify(request),
  })
}

export async function unblockContact(contactId: string): Promise<void> {
  const request: BlockContactRequest = { contact_id: contactId }
  return fetchApi<void>(`${WAHA_BASE}/contact/unblock`, {
    method: 'POST',
    body: JSON.stringify(request),
  })
}

// ============================================================================
// SESSIONS (WhatsApp Connection Management)
// ============================================================================

export async function listSessions(): Promise<WahaSession[]> {
  return fetchApi<WahaSession[]>(`${WAHA_BASE}/sessions`, {
    method: 'GET',
  })
}

export async function createSession(data: SessionCreate): Promise<WahaSession> {
  return fetchApi<WahaSession>(`${WAHA_BASE}/sessions`, {
    method: 'POST',
    body: JSON.stringify(data),
  })
}

export async function getSessionStatus(
  sessionName: string
): Promise<SessionStatus> {
  return fetchApi<SessionStatus>(
    `${WAHA_BASE}/sessions/${encodeURIComponent(sessionName)}/status`,
    {
      method: 'GET',
    }
  )
}

export async function startSession(
  sessionName: string
): Promise<SessionStatus> {
  return fetchApi<SessionStatus>(
    `${WAHA_BASE}/sessions/${encodeURIComponent(sessionName)}/start`,
    {
      method: 'POST',
    }
  )
}

export async function stopSession(sessionName: string): Promise<void> {
  return fetchApi<void>(
    `${WAHA_BASE}/sessions/${encodeURIComponent(sessionName)}/stop`,
    {
      method: 'POST',
    }
  )
}

export async function restartSession(sessionName: string): Promise<void> {
  return fetchApi<void>(
    `${WAHA_BASE}/sessions/${encodeURIComponent(sessionName)}/restart`,
    {
      method: 'POST',
    }
  )
}

export async function getScreenshot(): Promise<Blob> {
  const response = await fetch('/api/v1/screenshot', {
    method: 'GET',
    credentials: 'include',
    headers: {
      Accept: 'image/png',
    },
  })

  if (!response.ok) {
    throw new Error(`Failed to get screenshot: ${response.statusText}`)
  }

  return response.blob()
}
