export type Resource = {
  id: number;
  title: string;
  description: string;
  type: string;
  url: string;
  tags: string[];
  created_at: string;
  updated_at: string;
};

export type PaginationMeta = {
  page: number;
  per_page: number;
  total: number;
  last_page: number;
};

export type ResourceListResponse = {
  data: Resource[];
  meta: PaginationMeta;
};

export type ResourcePayload = {
  title: string;
  description: string;
  type: string;
  url: string;
  tags: string[];
};

export type SmartAssistPayload = {
  title: string;
  type: string;
  url: string;
};

export type SmartAssistResponse = {
  data: {
    description: string;
    tags: string[];
  };
};

export type ResourceFilters = {
  title: string;
  type: string;
  tag: string;
};
