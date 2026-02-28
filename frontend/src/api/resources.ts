import { api } from './client';
import type {
  Resource,
  ResourceFilters,
  ResourceListResponse,
  ResourcePayload,
  SmartAssistPayload,
  SmartAssistResponse,
} from '../types/resource';

export async function getResources(
  page = 1,
  perPage = 10,
  filters: Partial<ResourceFilters> = {},
): Promise<ResourceListResponse> {
  const { data } = await api.get<ResourceListResponse>('/resources', {
    params: {
      page,
      per_page: perPage,
      title: filters.title,
      type: filters.type,
      tag: filters.tag,
    },
  });

  return data;
}

export async function createResource(payload: ResourcePayload): Promise<Resource> {
  const { data } = await api.post<{ data: Resource }>('/resources', payload);
  return data.data;
}

export async function updateResource(id: number, payload: Partial<ResourcePayload>): Promise<Resource> {
  const { data } = await api.put<{ data: Resource }>(`/resources/${id}`, payload);
  return data.data;
}

export async function deleteResource(id: number): Promise<void> {
  await api.delete(`/resources/${id}`);
}

export async function smartAssist(payload: SmartAssistPayload): Promise<SmartAssistResponse['data']> {
  const { data } = await api.post<SmartAssistResponse>('/resources/smart-assist', payload);
  return data.data;
}
