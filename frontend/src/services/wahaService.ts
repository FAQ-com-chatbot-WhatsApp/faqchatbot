import { fetchApi } from "@/lib/api"
import type {
  WahaMessage,
  WahaChat,
  SendTextMessageRequest,
  SendImageMessageRequest,
  SendLocationMessageRequest,
  GetMessagesResponse,
} from "@/types/waha"

const WAHA_BASE = "/waha"

export async function getChatMessages(
  chatId: string,
  limit: number = 50
): Promise<GetMessagesResponse> {
  const encodedChatId = encodeURIComponent(chatId)
  return fetchApi<GetMessagesResponse>(
    `${WAHA_BASE}/chats/${encodedChatId}/messages?limit=${limit}`,
    { method: "GET" }
  )
}

export async function sendTextMessage(
  request: SendTextMessageRequest
): Promise<WahaMessage> {
  return fetchApi<WahaMessage>(`${WAHA_BASE}/messages/text`, {
    method: "POST",
    body: JSON.stringify(request),
  })
}

export async function sendImageMessage(
  request: SendImageMessageRequest
): Promise<WahaMessage> {
  return fetchApi<WahaMessage>(`${WAHA_BASE}/messages/image`, {
    method: "POST",
    body: JSON.stringify(request),
  })
}

export async function sendLocationMessage(
  request: SendLocationMessageRequest
): Promise<WahaMessage> {
  return fetchApi<WahaMessage>(`${WAHA_BASE}/messages/location`, {
    method: "POST",
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
    { method: "DELETE" }
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
      method: "PUT",
      body: JSON.stringify({ text }),
    }
  )
}
